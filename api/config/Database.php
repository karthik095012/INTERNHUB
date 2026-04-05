<?php
class Database {
  private $host = 'localhost';
  private $db_name = 'internhub_db';
  private $user = 'root';
  private $password = 'karthik@123';
  private $conn;

  public function connect() {
    $this->conn = new mysqli(
      $this->host,
      $this->user,
      $this->password,
      $this->db_name
    );

    if ($this->conn->connect_error) {
      error_log('Database connection failed: ' . $this->conn->connect_error);
      return null;
    }

    $this->conn->set_charset("utf8mb4");
    return $this->conn;
  }

  public function close() {
    $this->conn->close();
  }
}
?>
