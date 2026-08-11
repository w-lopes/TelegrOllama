<?php

namespace App;

class ApiController {
    private CharacterService $characterService;
    private MessageService $messageService;
    private OllamaClient $ollamaClient;

    public function __construct(CharacterService $cs, MessageService $ms, OllamaClient $oc) {
        $this->characterService = $cs;
        $this->messageService = $ms;
        $this->ollamaClient = $oc;
    }

    public function handleRequest(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $action = $_GET['action'] ?? '';

        header('Content-Type: application/json');

        try {
            if ($path === '/api/characters') {
                if ($method === 'GET') $this->listCharacters();
                elseif ($method === 'POST') $this->createCharacter();
            } elseif ($path === '/api/characters/' . $action && strpos($path, '/api/characters/') !== false) {
                // This is a bit tricky with vanilla PHP and no router. 
                // Let's use a simpler approach: /api.php?action=...&id=...
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // Let's rethink the routing to be simpler for vanilla PHP.
    // I will use a single api.php that handles everything based on $_GET['action'].
}
?>
