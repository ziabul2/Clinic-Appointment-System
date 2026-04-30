<?php
/**
 * Database Configuration File
 * Handles database connection with error logging
 */

class Database {
    private $host = "localhost";
    private $db_name = "clinic_management";
    private $username = "root";
    private $password = "";
    public $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 2, // Low timeout for quick failover
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

            // Log successful connection
            // $this->logMessage("DATABASE_CONNECTION", "Database connected successfully", "INFO");

        } catch(PDOException $exception) {
            $error_message = "Connection error: " . $exception->getMessage();
            $this->logMessage("DATABASE_ERROR", $error_message, "ERROR");
        }
        
        return $this->conn;
    }

    public static function isOnline() {
        $instance = new self();
        return $instance->getConnection() instanceof PDO;
    }
    
    private function logMessage($type, $message, $level = "INFO") {
        $log_dir = __DIR__ . "/../logs/";
        $log_file = $log_dir . ($level == "ERROR" ? "errors.log" : "process.log");
        $timestamp = date("Y-m-d H:i:s");
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        
        $log_entry = "[$timestamp] [$level] [$type] $message" . PHP_EOL;
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}
?>