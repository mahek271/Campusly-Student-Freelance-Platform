<?php
/**
 * quick_apply.php — AJAX Quick Apply handler
 * Called via fetch() from task_view.php or browse_tasks.php
 * Returns JSON so the page doesn't reload
 */
session_start();
include 'db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'candidate'){
  echo json_encode(['ok'=>false,'error'=>'Not logged in as candidate.']); exit();
}

$uid     = (int)$_SESSION['user_id'];
$task_id = intval($_POST['task_id'] ?? 0);
$bid     = floatval($_POST['bid_amount'] ?? 0);
$days    = intval($_POST['delivery_days'] ?? 7);
$proposal= trim($_POST['proposal_text'] ?? '');

if(!$task_id){ echo json_encode(['ok'=>false,'error'=>'Invalid task.']); exit(); }
if(strlen($proposal) < 30){ echo json_encode(['ok'=>false,'error'=>'Proposal must be at least 30 characters.']); exit(); }
if($days < 1 || $days > 90){ echo json_encode(['ok'=>false,'error'=>'Delivery days must be 1-90.']); exit(); }

// Check already applied
$already = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM applications WHERE task_id=$task_id AND candidate_id=$uid"));
if($already){ echo json_encode(['ok'=>false,'error'=>'You have already applied to this task.']); exit(); }

// Check task is still open
$task = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id,employer_id,title FROM tasks WHERE id=$task_id AND status='open'"));
if(!$task){ echo json_encode(['ok'=>false,'error'=>'This task is no longer accepting applications.']); exit(); }

$stmt = $conn->prepare("INSERT INTO applications (task_id,candidate_id,bid_amount,delivery_days,proposal_text) VALUES (?,?,?,?,?)");
$stmt->bind_param("iidis", $task_id, $uid, $bid, $days, $proposal);
if($stmt->execute()){
  // Notify employer
  $cname = $_SESSION['user_name'];
  $conn->query("INSERT INTO notifications (user_id,message,link) VALUES ({$task['employer_id']},'📩 $cname applied to: ".mysqli_real_escape_string($conn,$task['title'])."','view_applicants.php?task_id=$task_id')");
  echo json_encode(['ok'=>true,'message'=>'Application submitted! The employer will review your proposal.']);
} else {
  echo json_encode(['ok'=>false,'error'=>'Could not submit. '.$conn->error]);
}
