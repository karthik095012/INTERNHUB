<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/Database.php';

$db = new Database();
$conn = $db->connect();

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action'])) {
  echo json_encode(['success' => false, 'error' => 'No action specified']);
  exit;
}

if ($input['action'] === 'register') {
  $email = $conn->real_escape_string($input['email']);
  $password = password_hash($input['password'], PASSWORD_BCRYPT);
  $name = $conn->real_escape_string($input['name']);
  $role = $conn->real_escape_string($input['role']);
  $company = isset($input['company']) && $input['company'] ? $conn->real_escape_string($input['company']) : null;

  $check_sql = "SELECT id FROM users WHERE email='$email'";
  $check_result = $conn->query($check_sql);
  
  if ($check_result->num_rows > 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email already exists']);
    exit;
  }

  $company_val = $company ? "'$company'" : "NULL";
  $sql = "INSERT INTO users (email, password, name, role, company) 
          VALUES ('$email', '$password', '$name', '$role', $company_val)";

  if ($conn->query($sql)) {
    $user_id = $conn->insert_id;
    echo json_encode(['success' => true, 'message' => 'Registration successful!', 'user' => [
      'id' => $user_id,
      'email' => $email,
      'name' => $name,
      'role' => $role,
      'company' => $company
    ]]);
  } else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $conn->error]);
  }
}

elseif ($input['action'] === 'login') {
  $email = $conn->real_escape_string($input['email']);
  $password = $input['password'];

  $sql = "SELECT id, email, name, role, company, password FROM users WHERE email='$email'";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password'])) {
      unset($user['password']);
      echo json_encode(['success' => true, 'user' => $user]);
    } else {
      echo json_encode(['success' => false, 'error' => 'Invalid password']);
    }
  } else {
    echo json_encode(['success' => false, 'error' => 'User not found']);
  }
}

else {
  echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

$conn->close();
?>
