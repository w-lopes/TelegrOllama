<?php

namespace App;

use PDO;
use PDOException;

class Database {
    private ?PDO $pdo = null;
    private string $dbPath;

    public function __construct(string $dbPath) {
        $this->dbPath = $dbPath;
        $this->connect();
        $this->initialize();
    }

    private function connect(): void {
        try {
            $this->pdo = new PDO("sqlite:" . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // Enable foreign keys
            $this->pdo->exec("PRAGMA foreign_keys = ON;");
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function initialize(): void {
        // Create characters table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS characters (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create messages table
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                character_id INTEGER NOT NULL,
                role TEXT NOT NULL, -- 'user' or 'assistant'
                content TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
            )
        ");

        // Update updated_at on character change
        $this->pdo->exec("
            CREATE TRIGGER IF NOT EXISTS update_character_timestamp 
            AFTER UPDATE ON characters
            FOR EACH ROW
            BEGIN
                UPDATE characters SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
            END;
        ");
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }
}
