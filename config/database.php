<?php
class Database {
  private $host;
  private $dbname;
  private $username;
  private $password;
  private $pdo = null;

  public function __construct() { //is a special function that runs automatically the moment you create a Db object
    $this->host     = $_ENV['DB_HOST'];
    $this->dbname   = $_ENV['DB_NAME'];
    $this->username = $_ENV['DB_USER'];
    $this->password = $_ENV['DB_PASS'];
  }

  public function connect() { //creates the actual connection to MySQL and returns it
    if ($this->pdo === null) {
      try {
        $this->pdo = new PDO(
          "mysql:host={$this->host};dbname={$this->dbname};charset=utf8",
          $this->username,
          $this->password
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'DB Connection Failed: ' . $e->getMessage()]);
        exit;
      }
    }
   return $this->pdo;
  }
}