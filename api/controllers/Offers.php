<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
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
    
    $sql = "SELECT 
              o.id,
              o.student_id,
              o.job_id,
              o.recruiter_id,
              o.offer_text,
              o.status,
              o.created_at,
              j.title,
              j.company
            FROM offers o 
            JOIN jobs j ON o.job_id = j.id 
            WHERE o.student_id = $student_id 
            ORDER BY o.created_at DESC";
    $result = $conn->query($sql);
    $offers = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $offers[] = $row;
      }
    }
    echo json_encode($offers);
    exit;
  }
  elseif (isset($_GET['recruiter_id'])) {
    $recruiter_id = intval(preg_replace('/[^0-9]/', '', $_GET['recruiter_id']));
    if ($recruiter_id <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid recruiter_id']);
      exit;
    }
    
    $sql = "SELECT 
              o.id,
              o.student_id,
              o.job_id,
              o.recruiter_id,
              o.offer_text,
              o.status,
              o.created_at,
              j.title,
              j.company,
              u.name,
              u.email
            FROM offers o 
            JOIN jobs j ON o.job_id = j.id 
            JOIN users u ON o.student_id = u.id
            WHERE o.recruiter_id = $recruiter_id 
            ORDER BY o.created_at DESC";
    $result = $conn->query($sql);
    $offers = [];
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $offers[] = $row;
      }
    }
    echo json_encode($offers);
    exit;
  } else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing student_id or recruiter_id']);
    exit;
  }
}

elseif ($method === 'POST' && isset($input['action'])) {
  
  if ($input['action'] === 'send') {
    $student_id = intval(preg_replace('/[^0-9]/', '', $input['student_id'] ?? 0));
    $job_id = intval($input['job_id'] ?? 0);
    $recruiter_id = intval(preg_replace('/[^0-9]/', '', $input['recruiter_id'] ?? 0));
    $offer_text = $conn->real_escape_string($input['offer_text'] ?? '');
    
    if ($student_id <= 0 || $job_id <= 0 || $recruiter_id <= 0) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid IDs']);
      exit;
    }
    
    $sql = "INSERT INTO offers (student_id, job_id, recruiter_id, offer_text, status) 
            VALUES ($student_id, $job_id, $recruiter_id, '$offer_text', 'Sent')";
    
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
    $offer_id = intval($input['id'] ?? 0);
    $status = $conn->real_escape_string($input['status'] ?? '');
    
    if ($offer_id <= 0 || !$status) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid offer_id or status']);
      exit;
    }
    
    $sql = "UPDATE offers SET status='$status' WHERE id=$offer_id";
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
  exit;
}

$conn->close();
?>
    $sql = "INSERT INTO offers (student_id, job_id, recruiter_id, offer_text, status) 
            VALUES ($student_id, $job_id, $recruiter_id, '$offer_text', 'Sent')";

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
  
  $sql = "UPDATE offers SET status = '$status' WHERE id = $id";
  
  if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
  } else {
    echo json_encode(['success' => false, 'error' => $conn->error]);
  }
}

$conn->close();
?>
