<?php

namespace App;

use PDO;

class CharacterService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll(): array {
        $stmt = $this->pdo->query("SELECT * FROM characters ORDER BY updated_at DESC");
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM characters WHERE id = ?");
        $stmt->execute([$id]);
        $character = $stmt->fetch();
        return $character ?: null;
    }

    public function create(string $name, string $description): int {
        if (empty($name)) {
            throw new \Exception("Character name is required.");
        }
        $stmt = $this->pdo->prepare("INSERT INTO characters (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, string $name, string $description): void {
        if (empty($name)) {
            throw new \Exception("Character name is required.");
        }
        $stmt = $this->pdo->prepare("UPDATE characters SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
    }

    public function delete(int $id): void {
        $stmt = $this->pdo->prepare("DELETE FROM characters WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function getCharacterWithLastMessage(int $id): ?array {
        // This is for the sidebar preview
        $sql = "
            SELECT c.*, m.content as last_message, m.created_at as last_message_time
            FROM characters c
            LEFT JOIN (
                SELECT character_id, content, created_at
                FROM messages
                WHERE id IN (SELECT MAX(id) FROM messages GROUP BY character_id)
            ) m ON c.id = m.character_id
            WHERE c.id = ?
        ";
        // Actually, the sidebar needs ALL characters with their last message.
        // Let's just do a simpler query for all in getAll() if we want to be efficient, 
        // but let's refine getAll to include last message.
        return null; // Placeholder
    }

    public function getAllWithLastMessage(): array {
        $sql = "
            SELECT c.*, m.content as last_message, m.created_at as last_message_time
            FROM characters c
            LEFT JOIN (
                SELECT character_id, content, created_at
                FROM messages
                WHERE id IN (SELECT MAX(id) FROM messages GROUP BY character_id)
            ) m ON c.id = m.character_id
            ORDER BY c.updated_at DESC
        ";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
?>
