<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Suppress warnings/notices
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once '../config/Database.php';

$db = new Database();
$conn = $db->connect();

// Check connection
if (!$conn) {
  http_response_code(500);
  die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Check for JSON parse errors
if ($input === null && !empty($rawInput)) {
  http_response_code(400);
  die(json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]));
}

if ($method === 'GET') {
  $sql = "SELECT * FROM jobs WHERE active = 1 ORDER BY created_at DESC";
  $result = $conn->query($sql);
  $jobs = [];
  
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $jobs[] = $row;
    }
  }
  
  echo json_encode($jobs);
  exit;
}

elseif ($method === 'POST' && isset($input['action'])) {
  
  if ($input['action'] === 'create') {
    
    // Validate required fields
    $required = ['recruiter_id', 'title', 'company', 'stipend', 'location', 'duration', 'deadline'];
    $missing = [];
    
    foreach ($required as $field) {
      if (!isset($input[$field]) || trim($input[$field]) === '') {
        $missing[] = $field;
      }
    }
    
    if (!empty($missing)) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Missing fields: ' . implode(', ', $missing)]);
      $conn->close();
      exit;
    }
    
    // Sanitize and prepare inputs
    // Extract numeric ID from string like "r_1711900000000" or "s_1711900000000"
    $recruiter_id_raw = trim($input['recruiter_id']);
    
    // Extract the numeric part (everything after "r_" or "s_")
    $recruiter_id_numeric = intval(preg_replace('/[^0-9]/', '', $recruiter_id_raw));
    
    if ($recruiter_id_numeric <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid recruiter_id format']);
      $conn->close();
      exit;
    }
    
    $title = $conn->real_escape_string(trim($input['title']));
    $company = $conn->real_escape_string(trim($input['company']));
    $description = $conn->real_escape_string(trim($input['description'] ?? ''));
    $stipend = $conn->real_escape_string(trim($input['stipend']));
    $location = $conn->real_escape_string(trim($input['location']));
    $duration = $conn->real_escape_string(trim($input['duration']));
    $deadline = $conn->real_escape_string(trim($input['deadline']));
    $opportunities = intval($input['opportunities'] ?? 1);
    
    if ($opportunities < 1) {
      $opportunities = 1;
    }

    $sql = "INSERT INTO jobs (title, company, description, stipend, location, duration, deadline, recruiter_id, opportunities) 
            VALUES ('$title', '$company', '$description', '$stipend', '$location', '$duration', '$deadline', $recruiter_id_numeric, $opportunities)";

    if ($conn->query($sql)) {
      echo json_encode(['success' => true, 'message' => 'Job posted successfully', 'id' => $conn->insert_id]);
    } else {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error, 'sql' => $sql]);
    }
    exit;
  } else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $input['action']]);
    exit;
  }
} else {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

$conn->close();
?>
