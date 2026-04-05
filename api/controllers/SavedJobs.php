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
    
    $sql = "SELECT j.* FROM jobs j 
            JOIN saved_jobs s ON j.id = s.job_id 
            WHERE s.student_id = $student_id AND j.active = 1 
            ORDER BY s.saved_at DESC";
    $result = $conn->query($sql);
    $jobs = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
      }
    }
    echo json_encode($jobs);
    exit;
  } else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing student_id']);
    exit;
  }
}

elseif ($method === 'POST' && isset($input['action'])) {
  
  if ($input['action'] === 'save') {
    $student_id = intval(preg_replace('/[^0-9]/', '', $input['student_id'] ?? 0));
    $job_id = intval($input['job_id'] ?? 0);
    
    if ($student_id <= 0 || $job_id <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid student_id or job_id']);
      exit;
    }
    
    // Check if already saved
    $check_sql = "SELECT id FROM saved_jobs WHERE student_id = $student_id AND job_id = $job_id";
    $check = $conn->query($check_sql);
    
    if ($check && $check->num_rows > 0) {
      echo json_encode(['success' => false, 'message' => 'Already saved']);
      exit;
    }

    $sql = "INSERT INTO saved_jobs (student_id, job_id) VALUES ($student_id, $job_id)";
    if ($conn->query($sql)) {
      echo json_encode(['success' => true]);
    } else {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
  }
}

elseif ($method === 'DELETE') {
  $student_id = intval(preg_replace('/[^0-9]/', '', $input['student_id'] ?? 0));
  $job_id = intval($input['job_id'] ?? 0);
  
  if ($student_id <= 0 || $job_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid student_id or job_id']);
    exit;
  }
  
  $sql = "DELETE FROM saved_jobs WHERE student_id = $student_id AND job_id = $job_id";
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
}

$conn->close();
?>
      echo json_encode(['success' => false, 'error' => $conn->error]);
    }
  }
}

elseif ($method === 'DELETE' && isset($input['student_id']) && isset($input['job_id'])) {
  $student_id = $input['student_id'];
  $job_id = $input['job_id'];
  
  $sql = "DELETE FROM saved_jobs WHERE student_id = $student_id AND job_id = $job_id";
  
  if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
  }
}

$conn->close();
?>
