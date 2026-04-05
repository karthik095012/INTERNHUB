<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Suppress warnings/notices from polluting JSON
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
  // Get student applications
  if (isset($_GET['student_id'])) {
    $student_id = intval(preg_replace('/[^0-9]/', '', $_GET['student_id']));
    if ($student_id <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid student_id']);
      exit;
    }
    
    $sql = "SELECT 
              a.id, 
              a.student_id, 
              a.job_id, 
              a.status, 
              a.cover_note, 
              a.applied_at,
              j.title, 
              j.company, 
              j.stipend 
            FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            WHERE a.student_id = $student_id 
            ORDER BY a.applied_at DESC";
    $result = $conn->query($sql);
    $apps = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $apps[] = $row;
      }
    }
    echo json_encode($apps);
    exit;
  }
  // Get applications for a recruiter's jobs
  elseif (isset($_GET['recruiter_id'])) {
    $recruiter_id = intval(preg_replace('/[^0-9]/', '', $_GET['recruiter_id']));
    if ($recruiter_id <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid recruiter_id']);
      exit;
    }
    
    $sql = "SELECT 
              a.id, 
              a.student_id, 
              a.job_id, 
              a.status, 
              a.cover_note, 
              a.applied_at,
              j.title, 
              j.company, 
              u.name, 
              u.email 
            FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            JOIN users u ON a.student_id = u.id
            WHERE j.recruiter_id = $recruiter_id 
            ORDER BY a.applied_at DESC";
    $result = $conn->query($sql);
    $apps = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $apps[] = $row;
      }
    }
    echo json_encode($apps);
    exit;
  } else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing student_id or recruiter_id']);
    exit;
  }
}

elseif ($method === 'POST' && isset($input['action'])) {
  
  if ($input['action'] === 'apply') {
    $student_id = intval(preg_replace('/[^0-9]/', '', $input['student_id'] ?? 0));
    $job_id = intval($input['job_id'] ?? 0);
    $cover_note = $conn->real_escape_string($input['cover_note'] ?? '');
    
    if ($student_id <= 0 || $job_id <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid student_id or job_id']);
      exit;
    }

    $sql = "INSERT INTO applications (student_id, job_id, cover_note) VALUES ($student_id, $job_id, '$cover_note')";
    if ($conn->query($sql)) {
      echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
  }
  elseif ($input['action'] === 'updatestatus') {
    $app_id = intval($input['id'] ?? 0);
    $status = $conn->real_escape_string($input['status'] ?? '');
    
    if ($app_id <= 0 || !$status) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid app_id or status']);
      exit;
    }
    
    $sql = "UPDATE applications SET status='$status' WHERE id=$app_id";
    if ($conn->query($sql)) {
      echo json_encode(['success' => true]);
    } else {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
  }
} else {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

$conn->close();
?>

    $sql = "INSERT INTO applications (student_id, job_id, cover_note, status) 
            VALUES ($student_id, $job_id, '$cover_note', 'Applied')";

    if ($conn->query($sql)) {
      echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
      echo json_encode(['success' => false, 'error' => $conn->error]);
    }
  }
}

elseif ($method === 'PUT' && isset($input['id'])) {
  $id = $input['id'];
  $status = $conn->real_escape_string($input['status']);
  
  $sql = "UPDATE applications SET status = '$status' WHERE id = $id";
  
  if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
  }
}

$conn->close();
?>
