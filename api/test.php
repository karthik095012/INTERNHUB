<?php
header('Content-Type: application/json');
require_once 'config/Database.php';

try {
  $db = new Database();
  $conn = $db->connect();
  
  if (!$conn) {
    http_response_code(500);
    echo json_encode([
      'status' => 'error',
      'message' => 'Failed to connect to MySQL. Check credentials and if MySQL server is running.'
    ]);
  } else {
    echo json_encode([
      'status' => 'success',
      'message' => 'Connected to MySQL successfully!',
      'database' => 'internhub_db',
      'host' => 'localhost'
    ]);
    $conn->close();
  }
  
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    'status' => 'error',
    'message' => $e->getMessage()
  ]);
}
?>
}
?>
