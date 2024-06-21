<?php

namespace User\BudgetHandler;

use mysqli;
use mysqli_sql_exception;
use Dotenv\Dotenv;

class Database {
    private string $servername;
    private string $username;
    private string $password;
    private string $dbname;

    public function __construct() {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
        $dotenv->load();

        // Retrieve configuration from environment variables
        $this->servername = $_ENV['DB_SERVERNAME'] ?? 'budgethandler-sqlserver.database.windows.net';
        $this->username = $_ENV['DB_USERNAME'] ?? 'sqladmin';
        $this->password = $_ENV['DB_PASSWORD'] ?? 'Tsaotsao21!';
        $this->dbname = $_ENV['DB_NAME'] ?? 'client_accounts';
    }

    public function connect(): mysqli {
        try {
            $conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
            if ($conn->connect_error) {
                throw new mysqli_sql_exception("Connection failed: " . $conn->connect_error);
            }
        } catch (mysqli_sql_exception $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw $e;
        }
        return $conn;
    }
}
