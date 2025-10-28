<?php
// src/Service/DatabaseService.php
namespace App\Service;

use PDO;

class DatabaseService
{
    private PDO $pdo;

    public function __construct(string $projectDir)
    {
        $dataDir = $projectDir . '/data';
        if (!is_dir($dataDir))
            mkdir($dataDir, 0755, true);
        $path = $dataDir . '/app.sqlite';
        $this->pdo = new PDO('sqlite:' . $path);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->migrate();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    private function migrate()
    {
        // users
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT UNIQUE,
            password TEXT
        )");

        // tickets
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            status TEXT NOT NULL,
            priority TEXT,
            created_at TEXT,
            updated_at TEXT,
            reporter_id INTEGER
        )");

        // seed demo user
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        if ($stmt->fetchColumn() == 0) {
            $hash = password_hash('password', PASSWORD_DEFAULT);
            $ins = $this->pdo->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)');
            $ins->execute(['Demo User', 'user@example.com', $hash]);
        }
        // seed ticket
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM tickets");
        if ($stmt->fetchColumn() == 0) {
            $now = date(DATE_ATOM);
            $this->pdo->prepare('INSERT INTO tickets (title,description,status,priority,created_at,updated_at,reporter_id) VALUES (?,?,?,?,?,?,?)')
                ->execute(['Welcome ticket', 'Seed ticket', 'open', 'low', $now, $now, 1]);
        }
    }
}
