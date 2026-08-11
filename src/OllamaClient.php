<?php

namespace App;

class OllamaClient {
    private string $host;
    private string $model;

    public function __construct(string $host, string $model) {
        $this->host = rtrim($host, '/');
        $this->model = $model;
    }

    public function chat(array $messages, string $systemPrompt): array {
        $url = "{$this->host}/api/chat";
        
        // Prepare the payload for Ollama's chat API
        $payload = [
            'model' => $this->model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
            'stream' => false,
            'think' => false
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Increase timeout for AI generation

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Ollama connection error: $error");
        }

        if ($httpCode !== 200) {
            $errorMsg = json_decode($response, true)['error'] ?? 'Unknown Ollama error';
            throw new \Exception("Ollama API error (HTTP $httpCode): $errorMsg");
        }

        $data = json_decode($response, true);
        if (!isset($data['message']['content'])) {
            throw new \Exception("Malformed response from Ollama.");
        }

        return [
            'role' => 'assistant',
            'content' => $data['message']['content']
        ];
    }
}
?>
