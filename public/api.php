<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/CharacterService.php';
require_once __DIR__ . '/../src/MessageService.php';
require_once __DIR__ . '/../src/OllamaClient.php';

use App\Config;
use App\Database;
use App\CharacterService;
use App\MessageService;
use App\OllamaClient;

// Load configuration
Config::load(__DIR__ . '/../.env');

// Initialize Database and Services
try {
    $dbPath = __DIR__ . '/../data/app.sqlite';
    $db = new Database($dbPath);
    $characterService = new CharacterService($db->getConnection());
    $messageService = new MessageService($db->getConnection());
    $ollamaClient = new OllamaClient(Config::get('OLLAMA_HOST'), Config::get('OLLAMA_MODEL'));
} catch (\Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Initialization error: ' . $e->getMessage()]);
    exit;
}

// Helper to send JSON response
function jsonResponse($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Get request parameters
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Read JSON body for POST requests
$input = [];
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

try {
    switch ($action) {
        case 'list_characters':
            jsonResponse($characterService->getAllWithLastMessage());
            break;

        case 'get_character':
            $id = (int)$_GET['id'];
            $char = $characterService->getById($id);
            if (!$char) jsonResponse(['error' => 'Character not found'], 404);
            jsonResponse($char);
            break;

        case 'create_character':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $id = $characterService->create($name, $description);
            jsonResponse(['id' => $id], 201);
            break;

        case 'update_character':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $id = (int)$_GET['id'];
            $name = $input['name'] ?? '';
            $description = $input['description'] ?? '';
            $characterService->update($id, $name, $description);
            jsonResponse(['success' => true]);
            break;

        case 'delete_character':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $id = (int)$_GET['id'];
            $characterService->delete($id);
            jsonResponse(['success' => true]);
            break;

        case 'get_messages':
            $charId = (int)$_GET['character_id'];
            jsonResponse($messageService->getMessagesByCharacterId($charId));
            break;

        case 'send_message':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $charId = (int)$_GET['character_id'];
            $userContent = $input['content'] ?? '';

            if (empty($userContent)) {
                jsonResponse(['error' => 'Message content cannot be empty'], 400);
            }

            // 1. Save user message
            $messageService->saveMessage($charId, 'user', $userContent);

            // 2. Get character info for context
            $character = $characterService->getById($charId);
            if (!$character) jsonResponse(['error' => 'Character not found'], 404);

            // 3. Get conversation history for Ollama
            $history = $messageService->getMessagesByCharacterId($charId);
            $ollamaMessages = [];
            foreach ($history as $msg) {
                $ollamaMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }

            // 4. Prepare system prompt
            $systemPrompt = "You are {$character['name']}.\n\n" .
                            "Character description:\n{$character['description']}\n\n" .
                            "Stay consistent with this character throughout the conversation.";

            // 5. Call Ollama
            try {
                $aiResponse = $ollamaClient->chat($ollamaMessages, $systemPrompt);
                // 6. Save AI response
                $messageService->saveMessage($charId, 'assistant', $aiResponse['content']);
                jsonResponse($aiResponse);
            } catch (\Exception $e) {
                // If Ollama fails, we still return the user message but with an error
                // Actually, let's just throw the error so the frontend knows.
                throw $e;
            }
            break;

        default:
            jsonResponse(['error' => 'Invalid action'], 400);
            break;
    }
} catch (\Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
