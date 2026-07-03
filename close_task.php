<?php
session_start();
include 'db.php';
requireRole('employer');
$uid = (int)$_SESSION['user_id'];
$tid = intval($_GET['id'] ?? 0);
if($tid){
  $conn->query("UPDATE tasks SET status='cancelled' WHERE id=$tid AND employer_id=$uid");
  $_SESSION['flash_success']='Task closed successfully.';
}
header("Location: manage_tasks.php");
exit();
