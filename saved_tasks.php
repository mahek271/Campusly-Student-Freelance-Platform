<?php
session_start();
include 'db.php';
requireRole('candidate');
$uid = (int)$_SESSION['user_id'];

// Toggle save
if(isset($_GET['toggle'])){
  $tid = intval($_GET['toggle']);
  $exists = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM saved_tasks WHERE user_id=$uid AND task_id=$tid"));
  if($exists){ $conn->query("DELETE FROM saved_tasks WHERE user_id=$uid AND task_id=$tid"); }
  else { $conn->query("INSERT INTO saved_tasks (user_id,task_id,saved_at) VALUES ($uid,$tid,NOW())"); }
  $back = $_SERVER['HTTP_REFERER'] ?? 'saved_tasks.php';
  header("Location: $back"); exit();
}

$saved = mysqli_query($conn,
  "SELECT t.*, u.name as employer_name, u.company_name,
    (SELECT COUNT(*) FROM applications WHERE task_id=t.id) as bid_count
   FROM saved_tasks s
   JOIN tasks t ON t.id=s.task_id
   JOIN users u ON u.id=t.employer_id
   WHERE s.user_id=$uid
   ORDER BY s.saved_at DESC");

$page_title='Saved Tasks';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">
  <aside class="sidebar">
    <div class="sidebar-card">
            <div class="sidebar-card">
        <a href="candidate_dashboard.php" class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
        <a href="browse_tasks.php" class="sidebar-link"><span class="si">🔍</span>Browse Tasks</a>
        <a href="my_applications.php" class="sidebar-link"><span class="si">📋</span>My Bids</a>
        <a href="saved_tasks.php" class="sidebar-link active"><span class="si">🔖</span>Saved Tasks</a>
        <a href="my_earnings.php" class="sidebar-link"><span class="si">💰</span>Earnings</a>
        <a href="portfolio.php" class="sidebar-link"><span class="si">🖼️</span>Portfolio</a>
        <a href="messages.php" class="sidebar-link"><span class="si">💬</span>Messages</a>
        <a href="leaderboard.php" class="sidebar-link"><span class="si">🏆</span>Leaderboard</a>
        <a href="notifications.php" class="sidebar-link"><span class="si">🔔</span>Notifications</a>
        <a href="notification_settings.php" class="sidebar-link"><span class="si">⚙️</span>Settings</a>
        <a href="profile.php" class="sidebar-link"><span class="si">👤</span>Profile</a>
        <a href="logout.php" class="sidebar-link" style="color:var(--rose)"><span class="si">🚪</span>Logout</a>
      </div>
  </aside>
  <main class="main-content">
    <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700;margin-bottom:24px">🔖 Saved Tasks</h1>
    <?php if(mysqli_num_rows($saved)===0): ?>
      <div style="text-align:center;padding:80px;color:var(--t3)">
        <div style="font-size:3.5rem;margin-bottom:16px">🔖</div>
        <h3 style="font-family:var(--fh);margin-bottom:8px">No saved tasks</h3>
        <p>Bookmark tasks you're interested in to find them quickly</p>
        <a href="browse_tasks.php" class="btn btn-brand" style="margin-top:20px">Browse Tasks →</a>
      </div>
    <?php else: ?>
      <div class="grid-2">
        <?php while($t=$saved->fetch_assoc()):
          $urg_cls=['urgent'=>'tag-r','high'=>'tag-a','medium'=>'tag-v','low'=>'tag-g'][$t['urgency']??'medium']??'tag-v';
          $skills=array_slice(explode(',',$t['skills']??''),0,3);
        ?>
        <div class="job-card">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
            <a href="task_view.php?id=<?= $t['id'] ?>" style="flex:1">
              <div class="job-title"><?= htmlspecialchars($t['title']) ?></div>
              <div class="job-company">🏢 <?= htmlspecialchars($t['company_name']?:$t['employer_name']) ?></div>
            </a>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
              <div class="job-budget">₹<?= number_format($t['price']) ?></div>
              <a href="saved_tasks.php?toggle=<?= $t['id'] ?>" class="btn btn-danger btn-sm" title="Remove">🗑 Remove</a>
            </div>
          </div>
          <p style="color:var(--t2);font-size:13px;margin-bottom:10px"><?= htmlspecialchars(substr($t['description'],0,110)) ?>…</p>
          <div class="job-tags">
            <span class="tag <?= $urg_cls ?>"><?= ucfirst($t['urgency']??'medium') ?></span>
            <?php foreach($skills as $sk): if(trim($sk)): ?><span class="tag tag-v"><?= htmlspecialchars(trim($sk)) ?></span><?php endif; endforeach; ?>
          </div>
          <div class="job-meta">
            <span>📋 <?= $t['bid_count'] ?> bids</span>
            <span class="tag <?= $t['status']==='open'?'tag-g':'tag-r' ?>"><?= ucfirst($t['status']) ?></span>
          </div>
          <?php if($t['status']==='open'): ?>
            <a href="task_view.php?id=<?= $t['id'] ?>" class="btn btn-brand btn-sm btn-full" style="margin-top:12px">Place Bid →</a>
          <?php endif; ?>
        </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
</div>
<?php include 'includes/footer.php'; ?>
