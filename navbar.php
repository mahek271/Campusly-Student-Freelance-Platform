<?php
$current_page = basename($_SERVER['PHP_SELF']);
$logged_in    = isset($_SESSION['user_id']);
$role         = $logged_in ? $_SESSION['user_role'] : '';
$user_name    = $logged_in ? $_SESSION['user_name'] : '';

$notif_count = 0; $msg_count = 0;
$nav_avatar  = null;
if ($logged_in) {
    $uid = (int)$_SESSION['user_id'];
    $nr = mysqli_query($conn,"SELECT COUNT(*) as c FROM notifications WHERE user_id=$uid AND is_read=0");
    if($nr) $notif_count = mysqli_fetch_assoc($nr)['c'];
    $mr = @mysqli_query($conn,"SELECT COUNT(*) as c FROM messages WHERE to_id=$uid AND is_read=0");
    if($mr) $msg_count = mysqli_fetch_assoc($mr)['c'];
    $av = mysqli_fetch_assoc(mysqli_query($conn,"SELECT avatar FROM users WHERE id=$uid LIMIT 1"));
    if($av && !empty($av['avatar']) && file_exists($av['avatar'])) $nav_avatar = $av['avatar'];
}
?>
<!-- SCENE BG -->
<div class="scene">
  <div class="mesh"></div>
  <div class="grid"></div>
  <div class="blob b1"></div>
  <div class="blob b2"></div>
</div>

<!-- NAVBAR -->
<nav class="nav" id="mainNav">
  <div class="ctr">
    <div class="nav-wrap">
      <a href="index.php" class="brand">
        <div class="brand-mark">🎯</div>
        <span class="brand-name">Campusly</span>
      </a>

      <div class="nav-links">
        <?php if($logged_in && $role !== 'admin'): ?>
          <a href="browse_tasks.php" class="navl <?= $current_page=='browse_tasks.php'?'active':'' ?>">Browse Tasks</a>
          <a href="leaderboard.php"  class="navl <?= $current_page=='leaderboard.php'?'active':'' ?>">Leaderboard</a>
        <?php endif; ?>
        <?php if($logged_in && $role==='candidate'): ?>
          <a href="candidate_dashboard.php" class="navl <?= $current_page=='candidate_dashboard.php'?'active':'' ?>">Dashboard</a>
          <a href="saved_tasks.php"         class="navl <?= $current_page=='saved_tasks.php'?'active':'' ?>">🔖 Saved</a>
        <?php endif; ?>
        <?php if($logged_in && $role==='employer'): ?>
          <a href="employer_dashboard.php" class="navl <?= $current_page=='employer_dashboard.php'?'active':'' ?>">Dashboard</a>
          <a href="post_task.php"          class="navl <?= $current_page=='post_task.php'?'active':'' ?>">Post Task</a>
        <?php endif; ?>
        <?php if($logged_in && $role==='admin'): ?>
          <a href="admin_dashboard.php" class="navl <?= $current_page=='admin_dashboard.php'?'active':'' ?>">Admin</a>
        <?php endif; ?>
      </div>

      <div class="nav-ctas">
        <?php if($logged_in): ?>
          <?php if($role !== 'admin'): ?>
          <a href="messages.php" class="btn btn-ghost btn-sm" style="position:relative" title="Messages">
            💬
            <?php if($msg_count>0): ?>
              <span class="badge" style="position:absolute;top:-4px;right:-4px;width:16px;height:16px;font-size:9px"><?= $msg_count ?></span>
            <?php endif; ?>
          </a>
          <a href="notifications.php" class="btn btn-ghost btn-sm" style="position:relative" title="Notifications">
            🔔
            <?php if($notif_count>0): ?>
              <span class="badge" style="position:absolute;top:-4px;right:-4px;width:16px;height:16px;font-size:9px"><?= $notif_count ?></span>
            <?php endif; ?>
          </a>
          <?php endif; ?>
          <a href="profile.php" class="btn btn-glass btn-sm">
            <?php if($nav_avatar): ?>
              <img src="<?= htmlspecialchars($nav_avatar) ?>" style="width:24px;height:24px;border-radius:50%;object-fit:cover;border:2px solid var(--blue)">
            <?php else: ?>
              <div class="avatar" style="width:22px;height:22px;font-size:10px"><?= strtoupper(substr($user_name,0,1)) ?></div>
            <?php endif; ?>
            <?= htmlspecialchars(explode(' ',$user_name)[0]) ?>
          </a>
          <a href="logout.php" class="btn btn-ghost btn-sm">Logout</a>
        <?php else: ?>
          <a href="login.php"    class="btn btn-glass btn-sm">Login</a>
          <a href="register.php" class="btn btn-brand btn-sm">Sign Up Free</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- TOAST CONTAINER -->
<div id="toasts"></div>

<?php if(isset($_SESSION['flash_success'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>toast('<?= addslashes(htmlspecialchars($_SESSION['flash_success'])) ?>','success'));</script>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if(isset($_SESSION['flash_error'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>toast('<?= addslashes(htmlspecialchars($_SESSION['flash_error'])) ?>','error'));</script>
<?php unset($_SESSION['flash_error']); endif; ?>
