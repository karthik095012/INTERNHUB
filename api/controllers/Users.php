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

if ($method === 'DELETE') {
  $user_id = intval($input['id'] ?? 0);
  
  if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user_id']);
    exit;
  }
  
  // Get user role to delete related data
  $user_sql = "SELECT role FROM users WHERE id = $user_id";
  $user_result = $conn->query($user_sql);
  
  if (!$user_result || $user_result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
  }
  
  $user = $user_result->fetch_assoc();
  $role = $user['role'];
  
  // Start transaction
  $conn->begin_transaction();
  
  try {
    if ($role === 'student') {
      // Delete student-related data
      $conn->query("DELETE FROM saved_jobs WHERE student_id = $user_id");
      $conn->query("DELETE FROM applications WHERE student_id = $user_id");
      $conn->query("DELETE FROM interviews WHERE student_id = $user_id");
      $conn->query("DELETE FROM offers WHERE student_id = $user_id");
      $conn->query("DELETE FROM resumes WHERE student_id = $user_id");
    } elseif ($role === 'recruiter') {
      // Delete recruiter-related data
      $conn->query("DELETE FROM interviews WHERE recruiter_id = $user_id");
      $conn->query("DELETE FROM offers WHERE recruiter_id = $user_id");
      $conn->query("DELETE FROM applications WHERE job_id IN (SELECT id FROM jobs WHERE recruiter_id = $user_id)");
      $conn->query("DELETE FROM jobs WHERE recruiter_id = $user_id");
    }
    
    // Delete the user account
    $delete_sql = "DELETE FROM users WHERE id = $user_id";
    $conn->query($delete_sql);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Account deleted successfully']);
    exit;
  } catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
  }
} else {
  http_response_code(405);
  echo json_encode(['success' => false, 'error' => 'Method not allowed']);
  exit;
}

$conn->close();
?>
