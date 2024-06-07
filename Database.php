<?php
namespace BudgetHandler;

use mysqli;
use mysqli_sql_exception;

class Database {
    private string $servername = '127.0.0.1';
    private string $username = 'root';
    private string $password = 'admin@2016';
    private string $dbname = 'client_accounts';

    public function connect() {
        try {
            $conn = new mysqli($this->servername, $this->username, $this->password, $this->dbname);
            if ($conn->connect_error) {
                throw new mysqli_sql_exception("Connection failed: " . $conn->connect_error);
            }
        } catch (mysqli_sql_exception $e) {
            die("Connection failed: " . $e->getMessage());
        }
        return $conn;
    }
}
