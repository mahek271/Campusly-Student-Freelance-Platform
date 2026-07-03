<?php
/**
 * task_recommendations.php — Smart Task Recommendations
 * Recommends open tasks based on candidate's skills and past activity
 * Called as AJAX: returns JSON array of task ids + titles
 */
session_start();
include 'db.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['user_role']!=='candidate'){
  echo json_encode([]); exit();
}
$uid = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT skills FROM users WHERE id=$uid"));

// Build skill keywords from user profile
$skills_raw = $user['skills'] ?? '';
$keywords = array_filter(array_map('trim', explode(',', $skills_raw)));

// Also pull categories from past applications
$past_cats = mysqli_query($conn,"SELECT DISTINCT t.category FROM applications a JOIN tasks t ON a.task_id=t.id WHERE a.candidate_id=$uid LIMIT 5");
$fav_cats = [];
while($r = mysqli_fetch_assoc($past_cats)) $fav_cats[] = $r['category'];

// Build WHERE for matching
$already_applied = mysqli_query($conn,"SELECT task_id FROM applications WHERE candidate_id=$uid");
$applied_ids = [0];
while($r = mysqli_fetch_assoc($already_applied)) $applied_ids[] = $r['task_id'];
$excl = implode(',',$applied_ids);

$wheres = ["t.status='open'", "t.id NOT IN ($excl)"];

if($keywords){
  $like_parts = [];
  foreach($keywords as $kw){
    $kw = mysqli_real_escape_string($conn, $kw);
    $like_parts[] = "t.skills LIKE '%$kw%' OR t.title LIKE '%$kw%' OR t.description LIKE '%$kw%'";
  }
  $wheres[] = '('.implode(' OR ',$like_parts).')';
} elseif($fav_cats){
  $cat_list = "'".implode("','", array_map(fn($c)=>mysqli_real_escape_string($conn,$c), $fav_cats))."'";
  $wheres[] = "t.category IN ($cat_list)";
}

$where_sql = implode(' AND ', $wheres);

$tasks = mysqli_query($conn,
  "SELECT t.id, t.title, t.category, t.price, t.urgency, u.name as employer_name
   FROM tasks t JOIN users u ON t.employer_id=u.id
   WHERE $where_sql
   ORDER BY RAND() LIMIT 4");

$result = [];
while($t = mysqli_fetch_assoc($tasks)) $result[] = $t;

// If no skill match, just return newest open tasks
if(empty($result)){
  $tasks = mysqli_query($conn,"SELECT t.id,t.title,t.category,t.price,t.urgency,u.name as employer_name FROM tasks t JOIN users u ON t.employer_id=u.id WHERE t.status='open' AND t.id NOT IN ($excl) ORDER BY t.created_at DESC LIMIT 4");
  while($t = mysqli_fetch_assoc($tasks)) $result[] = $t;
}

echo json_encode($result);

?>
