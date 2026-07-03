<?php
session_start();
include 'db.php';
requireLogin();
$uid = (int)$_SESSION['user_id'];

// Mark all read
$conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$uid");

$notifs = mysqli_query($conn,"SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 50");

$page_title='Notifications';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div style="max-width:700px;margin:0 auto;padding:100px 20px 60px">
    <h1 style="font-family:var(--fh);font-size:2rem;font-weight:700;margin-bottom:28px">🔔 Notifications</h1>
    <?php if(mysqli_num_rows($notifs)===0): ?>
      <div style="text-align:center;padding:80px;color:var(--t3)">
        <div style="font-size:3.5rem;margin-bottom:16px">🔕</div>
        <h3 style="font-family:var(--fh);margin-bottom:8px">All quiet here</h3>
        <p>You have no notifications yet</p>
      </div>
    <?php else: ?>
      <?php while($n=$notifs->fetch_assoc()): ?>
      <div style="background:var(--ink3);border:1px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:10px;display:flex;align-items:flex-start;justify-content:space-between;gap:14px">
        <div style="flex:1">
          <p style="color:var(--t2);font-size:14px"><?= htmlspecialchars($n['message']) ?></p>
          <div style="color:var(--t3);font-size:12px;margin-top:6px"><?= date('d M Y H:i',strtotime($n['created_at'])) ?></div>
        </div>
        <?php if($n['link']): ?><a href="<?= htmlspecialchars($n['link']) ?>" class="btn btn-ghost btn-sm" style="flex-shrink:0">View →</a><?php endif; ?>
      </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
