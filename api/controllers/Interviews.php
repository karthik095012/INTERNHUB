<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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
  // Get interviews for student
  if (isset($_GET['student_id'])) {
    $student_id = intval(preg_replace('/[^0-9]/', '', $_GET['student_id']));
    if ($student_id <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid student_id']);
      exit;
    }
    
    $sql = "SELECT 
              i.id,
              i.recruiter_id,
              i.student_id,
              i.job_id,
              i.type,
              i.status,
              i.interview_date,
              i.interview_time,
              i.join_link,
              i.created_at,
              j.title as job_title,
              j.company,
              u.name as recruiter_name
            FROM interviews i 
            JOIN jobs j ON i.job_id = j.id 
            JOIN users u ON i.recruiter_id = u.id
            WHERE i.student_id = $student_id 
            ORDER BY i.interview_date DESC";
    $result = $conn->query($sql);
    $interviews = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $interviews[] = $row;
      }
    }
    echo json_encode($interviews);
    exit;
  }
  // Get interviews for recruiter
  elseif (isset($_GET['recruiter_id'])) {
    $recruiter_id = intval(preg_replace('/[^0-9]/', '', $_GET['recruiter_id']));
    if ($recruiter_id <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid recruiter_id']);
      exit;
    }
    
    $sql = "SELECT 
              i.id,
              i.recruiter_id,
              i.student_id,
              i.job_id,
              i.type,
              i.status,
              i.interview_date,
              i.interview_time,
              i.join_link,
              i.created_at,
              j.title,
              j.company
            FROM interviews i 
            JOIN jobs j ON i.job_id = j.id 
            WHERE i.recruiter_id = $recruiter_id 
            ORDER BY i.interview_date DESC";
    $result = $conn->query($sql);
    $interviews = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $interviews[] = $row;
      }
    }
    echo json_encode($interviews);
    exit;
  } else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing student_id or recruiter_id']);
    exit;
  }
}

elseif ($method === 'POST' && isset($input['action'])) {
  
  if ($input['action'] === 'schedule') {
    $recruiter_id = intval(preg_replace('/[^0-9]/', '', $input['recruiter_id'] ?? 0));
    $student_id = intval(preg_replace('/[^0-9]/', '', $input['student_id'] ?? 0));
    $job_id = intval($input['job_id'] ?? 0);
    $type = $conn->real_escape_string($input['type'] ?? '');
    $interview_date = $conn->real_escape_string($input['interview_date'] ?? '');
    $interview_time = $conn->real_escape_string($input['interview_time'] ?? '');
    $join_link = $conn->real_escape_string($input['join_link'] ?? '');
    
    if ($recruiter_id <= 0 || $student_id <= 0 || $job_id <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid IDs']);
      exit;
    }
    
    $sql = "INSERT INTO interviews (recruiter_id, student_id, job_id, type, interview_date, interview_time, join_link) 
            VALUES ($recruiter_id, $student_id, $job_id, '$type', '$interview_date', '$interview_time', '$join_link')";
    
    if ($conn->query($sql)) {
      echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
  }
}

elseif ($method === 'PUT' && isset($input['action'])) {
  if ($input['action'] === 'updatestatus') {
    $interview_id = intval($input['id'] ?? 0);
    $status = $conn->real_escape_string($input['status'] ?? '');
    
    if ($interview_id <= 0 || !$status) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid interview_id or status']);
      exit;
    }
    
    $sql = "UPDATE interviews SET status='$status' WHERE id=$interview_id";
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
  $interview_id = intval($input['id'] ?? 0);
  
  if ($interview_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid interview_id']);
    exit;
  }
  
  $sql = "DELETE FROM interviews WHERE id=$interview_id";
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
    $candidate_name = $conn->real_escape_string($input['candidate_name']);
    $candidate_email = $conn->real_escape_string($input['candidate_email']);
    $type = $conn->real_escape_string($input['type']);
    $interview_date = $input['interview_date'];
    $interview_time = $input['interview_time'];
    $join_link = $conn->real_escape_string($input['join_link'] ?? '');

    $sql = "INSERT INTO interviews (recruiter_id, student_id, job_id, candidate_name, candidate_email, type, interview_date, interview_time, join_link) 
            VALUES ($recruiter_id, $student_id, $job_id, '$candidate_name', '$candidate_email', '$type', '$interview_date', '$interview_time', '$join_link')";

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
  
  $sql = "UPDATE interviews SET status = '$status' WHERE id = $id";
  
  if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
  }
}

elseif ($method === 'DELETE' && isset($input['id'])) {
  $id = $input['id'];
  
  $sql = "DELETE FROM interviews WHERE id = $id";
  
  if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
  }
}

$conn->close();
?>
