<?php
session_start();
include 'db.php';
requireRole('employer');
$uid = (int)$_SESSION['user_id'];

// Close task
if(isset($_GET['close'])){
  $tid=(int)$_GET['close'];
  $conn->query("UPDATE tasks SET status='cancelled' WHERE id=$tid AND employer_id=$uid");
  $_SESSION['flash_success']='Task closed.'; header("Location: manage_tasks.php"); exit();
}

$tasks = mysqli_query($conn,
  "SELECT t.*, (SELECT COUNT(*) FROM applications WHERE task_id=t.id) as bid_count,
    (SELECT COUNT(*) FROM applications WHERE task_id=t.id AND status='selected') as selected_count
   FROM tasks t WHERE t.employer_id=$uid ORDER BY t.created_at DESC");

$page_title='Manage Tasks';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">
  <aside class="sidebar">
    <div class="sidebar-card">
      <a href="employer_dashboard.php" class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
      <a href="post_task.php" class="sidebar-link"><span class="si">➕</span>Post Task</a>
      <a href="manage_tasks.php" class="sidebar-link active"><span class="si">📋</span>My Tasks</a>
      <a href="task_invite.php" class="sidebar-link"><span class="si">📨</span>Invite Students</a>
      <a href="task_analytics.php" class="sidebar-link"><span class="si">📊</span>Analytics</a>
      <a href="messages.php" class="sidebar-link"><span class="si">💬</span>Messages</a>
      <a href="payment_history.php" class="sidebar-link"><span class="si">💳</span>Payments</a>
      <a href="notifications.php" class="sidebar-link"><span class="si">🔔</span>Notifications</a>
      <a href="notification_settings.php" class="sidebar-link"><span class="si">⚙️</span>Settings</a>
      <a href="profile.php" class="sidebar-link"><span class="si">👤</span>Profile</a>
      <a href="logout.php" class="sidebar-link" style="color:var(--rose)"><span class="si">🚪</span>Logout</a>
    </div>
    <a href="post_task.php" class="btn btn-brand btn-full">+ Post Task</a>
  </aside>
  <main class="main-content">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
      <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700">📋 My Tasks</h1>
      <a href="post_task.php" class="btn btn-brand">+ New Task</a>
    </div>

    <?php if(mysqli_num_rows($tasks)===0): ?>
      <div style="text-align:center;padding:70px;color:var(--t3)">
        <div style="font-size:3rem;margin-bottom:14px">📋</div>
        <div style="font-weight:600;margin-bottom:10px">No tasks posted yet</div>
        <a href="post_task.php" class="btn btn-brand">Post Your First Task</a>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:16px">
        <?php while($t=$tasks->fetch_assoc()):
          $s_cls=['open'=>'tag-g','assigned'=>'tag-a','completed'=>'tag-c','cancelled'=>'tag-r'][$t['status']]??'tag-v';
          $deadline_str=$t['deadline']?date('d M Y',strtotime($t['deadline'])):'Flexible';
          $days_left=$t['deadline']?ceil((strtotime($t['deadline'])-time())/86400):null;
        ?>
        <div class="glass-dark" style="padding:22px;border-radius:var(--r)">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;margin-bottom:14px">
            <div>
              <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;align-items:center">
                <span class="tag <?= $s_cls ?>"><?= ucfirst($t['status']) ?></span>
                <span class="tag tag-c"><?= htmlspecialchars($t['category']) ?></span>
                <?php if($days_left!==null && $t['status']==='open'): ?>
                  <span style="font-size:12px;color:<?= $days_left<=3?'var(--rose)':($days_left<=7?'var(--amber)':'var(--t3)') ?>">⏱ <?= $days_left ?> days left</span>
                <?php endif; ?>
              </div>
              <h3 style="font-weight:700;font-size:1rem;margin-bottom:4px"><?= htmlspecialchars($t['title']) ?></h3>
              <div style="color:var(--t3);font-size:13px">Deadline: <?= $deadline_str ?></div>
            </div>
            <div style="text-align:right">
              <div style="font-weight:800;font-size:1.2rem;color:var(--emerald)">₹<?= number_format($t['price']) ?></div>
              <div style="color:var(--t3);font-size:13px"><?= $t['bid_count'] ?> bids</div>
            </div>
          </div>
          <p style="color:var(--t2);font-size:13.5px;margin-bottom:16px;line-height:1.6"><?= htmlspecialchars(substr($t['description'],0,180)) ?>…</p>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="view_applicants.php?id=<?= $t['id'] ?>" class="btn btn-outline btn-sm">👥 View Bids (<?= $t['bid_count'] ?>)</a>
            <a href="task_view.php?id=<?= $t['id'] ?>" class="btn btn-ghost btn-sm">🔍 View</a>
            <?php if($t['status']==='open'): ?>
              <a href="edit_task.php?id=<?= $t['id'] ?>"  class="btn btn-outline btn-sm">✏️ Edit</a>
              <a href="clone_task.php?id=<?= $t['id'] ?>" class="btn btn-glass btn-sm" onclick="return confirm('Clone this task?')">📋 Clone</a>
              <a href="manage_tasks.php?close=<?= $t['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Close this task? No more bids will be accepted.')">🔒 Close</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
</div>
<?php include 'includes/footer.php'; ?>
