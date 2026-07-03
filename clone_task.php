<?php
session_start();
include 'db.php';
requireRole('employer');

$uid = (int)$_SESSION['user_id'];
$tid = intval($_GET['id'] ?? 0);
if(!$tid){ header("Location: manage_tasks.php"); exit(); }

$task = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT * FROM tasks WHERE id=$tid AND employer_id=$uid LIMIT 1"));
if(!$task){ $_SESSION['flash_error']='Task not found.'; header("Location: manage_tasks.php"); exit(); }

// Clone the task: copy all fields, reset status to open, set new deadline
$stmt = $conn->prepare(
  "INSERT INTO tasks (employer_id,title,description,price,category,location,skills,deadline,duration,urgency,status,created_at)
   VALUES (?,?,?,?,?,?,?,?,?,?,'open',NOW())");

$new_deadline = date('Y-m-d', strtotime($task['deadline'] ?? '+14 days'));
// Ensure cloned deadline is in the future
if($new_deadline < date('Y-m-d', strtotime('+2 days'))){
  $new_deadline = date('Y-m-d', strtotime('+14 days'));
}
$new_title = 'Copy of ' . $task['title'];

$stmt->bind_param("issdssssss",
  $uid, $new_title, $task['description'], $task['price'],
  $task['category'], $task['location'], $task['skills'],
  $new_deadline, $task['duration'], $task['urgency']);

if($stmt->execute()){
  $new_id = $conn->insert_id;
  $_SESSION['flash_success']='Task cloned! Edit it before publishing. ✅';
  header("Location: edit_task.php?id=$new_id"); exit();
} else {
  $_SESSION['flash_error']='Clone failed: '.$conn->error;
  header("Location: manage_tasks.php"); exit();
}
?>
