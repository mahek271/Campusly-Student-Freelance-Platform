<?php
/**
 * task_invite.php — Direct Invite System
 * Employers can invite specific students to bid on their task.
 * Student gets notified and can quick-apply.
 */
session_start();
include 'db.php';
requireRole('employer');

$uid = (int)$_SESSION['user_id'];

// Handle invite submission
if($_SERVER['REQUEST_METHOD']==='POST'){
  $task_id   = intval($_POST['task_id']      ?? 0);
  $student_id= intval($_POST['student_id']   ?? 0);
  $message   = trim($_POST['invite_message'] ?? '');

  $task = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id,title FROM tasks WHERE id=$task_id AND employer_id=$uid AND status='open' LIMIT 1"));
  $student = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id,name FROM users WHERE id=$student_id AND role='candidate' AND is_banned=0 LIMIT 1"));

  if($task && $student){
    $safe_msg = $conn->real_escape_string($message ?: "We think you'd be a great fit for this task!");
    $safe_title = $conn->real_escape_string($task['title']);
    $safe_name  = $conn->real_escape_string($student['name']);
    // Notify student
    $conn->query("INSERT INTO notifications (user_id,message,link)
      VALUES ($student_id,'🎯 You have been invited to bid on: $safe_title','task_view.php?id=$task_id')");
    // Add to invites table
    $conn->query("INSERT IGNORE INTO task_invites (task_id,employer_id,student_id,message,created_at)
      VALUES ($task_id,$uid,$student_id,'$safe_msg',NOW())");
    $_SESSION['flash_success'] = "Invite sent to {$student['name']}! ✅";
  }
  header("Location: task_invite.php?task_id=$task_id"); exit();
}

$task_id = intval($_GET['task_id'] ?? 0);
$task    = $task_id ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM tasks WHERE id=$task_id AND employer_id=$uid AND status='open' LIMIT 1")) : null;

// My open tasks (for task picker)
$my_tasks = mysqli_query($conn,"SELECT id,title FROM tasks WHERE employer_id=$uid AND status='open' ORDER BY created_at DESC");

// Search students
$search = trim($_GET['search'] ?? '');
$skill_filter = trim($_GET['skill'] ?? '');
$students_q = "SELECT u.*, 
  COALESCE(AVG(r.rating),0) as avg_rating,
  COUNT(DISTINCT a.task_id) as tasks_done
  FROM users u
  LEFT JOIN reviews r ON r.reviewee_id=u.id
  LEFT JOIN applications a ON a.candidate_id=u.id AND a.status='completed'
  WHERE u.role='candidate' AND u.is_banned=0";
if($search) $students_q .= " AND (u.name LIKE '%".esc($conn,$search)."%' OR u.university LIKE '%".esc($conn,$search)."%')";
if($skill_filter) $students_q .= " AND u.skills LIKE '%".esc($conn,$skill_filter)."%'";
$students_q .= " GROUP BY u.id ORDER BY tasks_done DESC, avg_rating DESC LIMIT 20";
$students = mysqli_query($conn,$students_q);

// Already invited
$invited_ids = [];
if($task_id){
  $inv=mysqli_query($conn,"SELECT student_id FROM task_invites WHERE task_id=$task_id");
  while($r=$inv->fetch_assoc()) $invited_ids[]=$r['student_id'];
}

$page_title='Invite Students';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">
  <aside class="sidebar">
    <div class="sidebar-card">
      <a href="employer_dashboard.php"  class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
      <a href="post_task.php"           class="sidebar-link"><span class="si">➕</span>Post Task</a>
      <a href="manage_tasks.php"        class="sidebar-link"><span class="si">📋</span>My Tasks</a>
      <a href="task_invite.php"         class="sidebar-link active"><span class="si">📨</span>Invite Students</a>
      <a href="task_analytics.php"      class="sidebar-link"><span class="si">📊</span>Analytics</a>
      <a href="messages.php"            class="sidebar-link"><span class="si">💬</span>Messages</a>
      <a href="payment_history.php"     class="sidebar-link"><span class="si">💳</span>Payments</a>
      <a href="notifications.php"       class="sidebar-link"><span class="si">🔔</span>Notifications</a>
      <a href="profile.php"             class="sidebar-link"><span class="si">👤</span>Profile</a>
      <a href="logout.php"               class="sidebar-link" style="color:var(--rose)"><span class="si">🚪</span>Logout</a>
    </div>
    <a href="post_task.php" class="btn btn-brand btn-full">+ Post Task</a>
  </aside>

  <main class="main-content">
    <div style="margin-bottom:28px">
      <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700;margin-bottom:6px">📨 Invite Students</h1>
      <p style="color:var(--t3)">Find the right talent and send a direct invite to bid on your task</p>
    </div>

    <!-- Task Selector -->
    <div class="glass-dark" style="padding:20px;border-radius:var(--r);margin-bottom:24px">
      <label class="flabel" style="margin-bottom:10px;display:block">Select Task to Invite For</label>
      <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <select name="task_id" class="finput" style="flex:1;min-width:250px" onchange="this.form.submit()">
          <option value="">— Pick a task —</option>
          <?php while($t=mysqli_fetch_assoc($my_tasks)): ?>
            <option value="<?= $t['id'] ?>" <?= $task_id==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['title']) ?></option>
          <?php endwhile; ?>
        </select>
        <?php if($task_id): ?>
          <input type="text" name="search" class="finput" placeholder="Search by name or university…" value="<?= htmlspecialchars($search) ?>" style="flex:1;min-width:200px">
          <input type="text" name="skill"  class="finput" placeholder="Filter by skill e.g. React…" value="<?= htmlspecialchars($skill_filter) ?>" style="min-width:160px">
          <button type="submit" class="btn btn-brand">Search</button>
        <?php endif; ?>
      </form>
    </div>

    <?php if(!$task_id): ?>
      <div style="text-align:center;padding:70px;color:var(--t3)">
        <div style="font-size:3rem;margin-bottom:14px">📨</div>
        <div style="font-weight:600">Select a task above to find and invite students</div>
      </div>
    <?php elseif(!$task): ?>
      <div class="alert alert-err">❌ Task not found or not open.</div>
    <?php else: ?>

    <div style="background:rgba(124,92,252,0.08);border:1px solid rgba(124,92,252,0.2);border-radius:var(--r);padding:14px 18px;margin-bottom:20px">
      <div style="font-size:13px;color:var(--violet2)">
        🎯 Inviting for: <strong><?= htmlspecialchars($task['title']) ?></strong> — ₹<?= number_format($task['price']) ?>
        &nbsp;·&nbsp; <?= count($invited_ids) ?> invite<?= count($invited_ids)!=1?'s':'' ?> sent
      </div>
    </div>

    <?php if(mysqli_num_rows($students)===0): ?>
      <div style="text-align:center;padding:50px;color:var(--t3)">
        <div style="font-size:2.5rem;margin-bottom:12px">👤</div>
        <div>No students found matching your search.</div>
      </div>
    <?php else: ?>
      <div class="grid-2" style="gap:16px">
        <?php while($s=$students->fetch_assoc()):
          $skills_arr = $s['skills'] ? array_slice(array_map('trim',explode(',',$s['skills'])),0,5) : [];
          $already_invited = in_array($s['id'],$invited_ids);
        ?>
        <div class="glass-dark" style="padding:20px;border-radius:var(--r)">
          <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:12px">
            <?php if(!empty($s['avatar']) && file_exists($s['avatar'])): ?>
              <img src="<?= htmlspecialchars($s['avatar']) ?>" style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:2px solid var(--violet);flex-shrink:0">
            <?php else: ?>
              <div class="avatar" style="flex-shrink:0"><?= strtoupper(substr($s['name'],0,1)) ?></div>
            <?php endif; ?>
            <div style="flex:1;min-width:0">
              <div style="font-weight:700"><?= htmlspecialchars($s['name']) ?></div>
              <div style="color:var(--t3);font-size:12px"><?= htmlspecialchars($s['university']??'') ?></div>
              <div style="display:flex;align-items:center;gap:8px;margin-top:5px">
                <div><?php for($i=1;$i<=5;$i++): ?><span class="star <?= $i<=$s['avg_rating']?'on':'off' ?>">★</span><?php endfor; ?></div>
                <span style="color:var(--t3);font-size:12px"><?= $s['tasks_done'] ?> tasks done</span>
              </div>
            </div>
          </div>
          <?php if($skills_arr): ?>
            <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:12px">
              <?php foreach($skills_arr as $sk): ?><span class="tag tag-v" style="font-size:10.5px"><?= htmlspecialchars($sk) ?></span><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if($already_invited): ?>
            <div class="tag tag-g" style="width:100%;justify-content:center;padding:8px">✅ Invite Sent</div>
          <?php else: ?>
            <form method="POST">
              <input type="hidden" name="task_id" value="<?= $task_id ?>">
              <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
              <textarea name="invite_message" class="finput" rows="2" style="margin-bottom:10px"
                placeholder="Optional personal message…"></textarea>
              <button type="submit" class="btn btn-outline btn-full btn-sm">📨 Send Invite</button>
            </form>
          <?php endif; ?>
        </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
    <?php endif; ?>
  </main>
</div>
</div>
<?php include 'includes/footer.php'; ?>
