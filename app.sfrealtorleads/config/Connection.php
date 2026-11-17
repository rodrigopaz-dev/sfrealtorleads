<?php
   require_once __DIR__ . '/../vendor/autoload.php';

   class Database {
      private static $instance = null;
      private $conn;

      private string $host;
      private string $name;
      private string $user;
      private string $pass;
      private string $port;
      private string $charset = 'utf8mb4';

      private function __construct() {
         $dotenv = Dotenv\Dotenv::createImmutable('/home/u279829464/domains/sfleads.rodrigopazz.com');
         $dotenv->load();
   
         $this->host = $_ENV['DB_HOST'];
         $this->name = $_ENV['DB_NAME'];
         $this->user = $_ENV['DB_USER'];
         $this->pass = $_ENV['DB_PASSWORD'];
         $this->port = $_ENV['DB_PORT'];

         $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->name};charset={$this->charset}";
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