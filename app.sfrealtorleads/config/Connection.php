<?php
   class Database {
      private static $instance = null;
      private $conn;

      private string $host = "127.0.0.1";
      private string $user = "root";
      private string $pass = "";
      private string $name = "realtors_lead_system";
      private string $charset = 'utf8mb4';

      private function __construct() {
         $dsn = "mysql:host={$this->host};dbname={$this->name};charset={$this->charset}";
         $options = [
               PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Handle error  | Manejo de errores
               PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      // Getting way    | Modo de obtención
               PDO::ATTR_EMULATE_PREPARES   => false,                 // Prepared statements reals 
         ];

         try {
               $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
         } catch (PDOException $e) {
               throw new PDOException($e->getMessage(), (int)$e->getCode());
         }
      }

      // Static method to get unique instance
      public static function getInstance() {
         if (!self::$instance) {
               self::$instance = new Database();
         }
         return self::$instance;
      }

      // Method to get PDO connection
      public function getConnection() {
         return $this->conn;
      }
   }
?>