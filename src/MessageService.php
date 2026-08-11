<?php

namespace App;

use PDO;

class MessageService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getMessagesByCharacterId(int $characterId): array {
        $stmt = $this->pdo->prepare("SELECT * FROM messages WHERE character_id = ? ORDER BY created_at ASC");
        $stmt->execute([$characterId]);
        return $stmt->fetchAll();
    }

    public function saveMessage(int $characterId, string $role, string $content): int {
        $stmt = $this->pdo->prepare("INSERT INTO messages (character_id, role, content) VALUES (?, ?, ?)");
        $stmt->execute([$characterId, $role, $content]);
        return (int)$this->pdo->lastInsertId();
    }

    public function deleteMessagesByCharacterId(int $characterId): void {
        $stmt = $this->pdo->prepare("DELETE FROM messages WHERE character_id = ?");
        $stmt->execute([$characterId]);
    }
}
?>
