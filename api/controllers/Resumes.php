<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once '../config/Database.php';

$db = new Database();
$conn = $db->connect();

if (!$conn) {
  http_response_code(500);
  die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

$method = $_SERVER['REQUEST_METHOD'];
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if ($input === null && !empty($raw_input)) {
  http_response_code(400);
  die(json_encode(['success' => false, 'error' => 'Invalid JSON']));
}

if ($method === 'GET') {
  if (isset($_GET['student_id'])) {
    $student_id = intval(preg_replace('/[^0-9]/', '', $_GET['student_id']));
    if ($student_id <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid student_id']);
      exit;
    }
    
    $sql = "SELECT id, file_name, uploaded_at FROM resumes WHERE student_id = $student_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
      $resume = $result->fetch_assoc();
      echo json_encode(['success' => true, 'resume' => $resume]);
    } else {
      echo json_encode(['success' => false, 'message' => 'No resume found']);
    }
    exit;
  } else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing student_id']);
    exit;
  }
}

elseif ($method === 'POST') {
  $student_id = intval(preg_replace('/[^0-9]/', '', $_POST['student_id'] ?? 0));
  
  if ($student_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid student_id']);
    exit;
  }
  
  if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $file_name = basename($_FILES['file']['name']);
    $file_data = file_get_contents($_FILES['file']['tmp_name']);
    $file_data_escaped = $conn->real_escape_string($file_data);

    // Delete old resume if exists
    $conn->query("DELETE FROM resumes WHERE student_id = $student_id");

    $sql = "INSERT INTO resumes (student_id, file_name, file_data) 
            VALUES ($student_id, '$file_name', '$file_data_escaped')";

    if ($conn->query($sql)) {
      echo json_encode(['success' => true, 'message' => 'Resume uploaded successfully']);
    } else {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
  } else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File upload failed']);
    exit;
  }
}

elseif ($method === 'DELETE') {
  $student_id = intval(preg_replace('/[^0-9]/', '', $input['student_id'] ?? 0));
  
  if ($student_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid student_id']);
    exit;
  }
  
  $sql = "DELETE FROM resumes WHERE student_id = $student_id";
  if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
  } else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $conn->error]);
  }
  exit;
} else {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

$conn->close();
?>

elseif ($method === 'DELETE') {
  $input = json_decode(file_get_contents('php://input'), true);
  $student_id = $input['student_id'];
  
  $sql = "DELETE FROM resumes WHERE student_id = $student_id";
  
  if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
  }
}

$conn->close();
?>
