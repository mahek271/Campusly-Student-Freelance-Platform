<?php
session_start();
include 'db.php';
requireLogin();

$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'];
$error = $success = '';

// Handle password change
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])){
  $current = $_POST['current_password'] ?? '';
  $new_pwd  = $_POST['new_password']     ?? '';
  $conf_pwd = $_POST['confirm_password'] ?? '';
  $user_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT password FROM users WHERE id=$uid"));
  if(!password_verify($current, $user_row['password'])){
    $error = 'Current password is incorrect.';
  } elseif(strlen($new_pwd)<8){
    $error = 'New password must be at least 8 characters.';
  } elseif(!preg_match('/[A-Z]/',$new_pwd)||!preg_match('/[0-9]/',$new_pwd)){
    $error = 'Password needs at least 1 uppercase letter & 1 number.';
  } elseif($new_pwd !== $conf_pwd){
    $error = 'Passwords do not match.';
  } else {
    $hashed = password_hash($new_pwd, PASSWORD_DEFAULT);
    $stmt_pwd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt_pwd->bind_param("si", $hashed, $uid);
    $stmt_pwd->execute();
    $_SESSION['flash_success'] = 'Password changed successfully! ✅';
    header("Location: notification_settings.php#password"); exit();
  }
}

// Handle notification settings
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_notifs'])){
  $keys = ['notif_bid_received','notif_bid_selected','notif_bid_rejected',
           'notif_payment','notif_message','notif_review','notif_task_update','notif_invite'];
  $vals = [];
  foreach($keys as $k) $vals[$k] = isset($_POST[$k]) ? 1 : 0;
  $existing = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM user_notification_prefs WHERE user_id=$uid LIMIT 1"));
  $set = implode(',', array_map(fn($k,$v)=>"$k=$v", array_keys($vals), array_values($vals)));
  if($existing){
    $conn->query("UPDATE user_notification_prefs SET $set WHERE user_id=$uid");
  } else {
    $cols = "user_id,".implode(',',array_keys($vals));
    $vs   = "$uid,".implode(',',array_values($vals));
    $conn->query("INSERT INTO user_notification_prefs ($cols) VALUES ($vs)");
  }
  $_SESSION['flash_success'] = 'Notification preferences saved! ✅';
  header("Location: notification_settings.php"); exit();
}

$p = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM user_notification_prefs WHERE user_id=$uid LIMIT 1"));
function pref($p,$k){ return !$p || $p[$k] ?? true; }

$page_title = 'Settings';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">
  <aside class="sidebar">
    <div class="sidebar-card">
      <?php if($role==='candidate'): ?>
        <a href="candidate_dashboard.php" class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
        <a href="browse_tasks.php"         class="sidebar-link"><span class="si">🔍</span>Browse Tasks</a>
        <a href="my_applications.php"      class="sidebar-link"><span class="si">📋</span>My Bids</a>
        <a href="saved_tasks.php"          class="sidebar-link"><span class="si">🔖</span>Saved Tasks</a>
        <a href="my_earnings.php"          class="sidebar-link"><span class="si">💰</span>Earnings</a>
        <a href="portfolio.php"            class="sidebar-link"><span class="si">🖼️</span>Portfolio</a>
        <a href="messages.php"             class="sidebar-link"><span class="si">💬</span>Messages</a>
        <a href="leaderboard.php"          class="sidebar-link"><span class="si">🏆</span>Leaderboard</a>
      <?php else: ?>
        <a href="employer_dashboard.php"    class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
        <a href="post_task.php"             class="sidebar-link"><span class="si">➕</span>Post Task</a>
        <a href="manage_tasks.php"          class="sidebar-link"><span class="si">📋</span>My Tasks</a>
        <a href="task_invite.php"           class="sidebar-link"><span class="si">📨</span>Invite Students</a>
        <a href="task_analytics.php"        class="sidebar-link"><span class="si">📊</span>Analytics</a>
        <a href="messages.php"              class="sidebar-link"><span class="si">💬</span>Messages</a>
        <a href="payment_history.php"       class="sidebar-link"><span class="si">💳</span>Payments</a>
      <?php endif; ?>
      <a href="notifications.php"          class="sidebar-link"><span class="si">🔔</span>Notifications</a>
      <a href="notification_settings.php"  class="sidebar-link active"><span class="si">⚙️</span>Settings</a>
      <a href="profile.php"                class="sidebar-link"><span class="si">👤</span>Profile</a>
      <a href="logout.php"                 class="sidebar-link" style="color:var(--rose)"><span class="si">🚪</span>Logout</a>
    </div>
  </aside>

  <main class="main-content">
    <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:800;margin-bottom:28px">⚙️ Settings</h1>

    <?php if($error): ?><div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- Password Section -->
    <div class="glass-dark" style="padding:32px;border-radius:var(--r);max-width:640px;margin-bottom:24px" id="password">
      <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:6px;font-size:1.1rem">🔒 Change Password</h3>
      <p style="color:var(--t3);font-size:13px;margin-bottom:22px">Update your account password. Must be 8+ characters with 1 uppercase & 1 number.</p>
      <form method="POST">
        <input type="hidden" name="change_password" value="1">
        <div class="fg">
          <label class="flabel">Current Password</label>
          <input type="password" name="current_password" class="finput" placeholder="Enter your current password" required>
        </div>
        <div class="fg">
          <label class="flabel">New Password</label>
          <input type="password" name="new_password" class="finput" placeholder="New password (8+ chars, 1 uppercase, 1 number)" required>
        </div>
        <div class="fg">
          <label class="flabel">Confirm New Password</label>
          <input type="password" name="confirm_password" class="finput" placeholder="Repeat new password" required>
        </div>
        <button type="submit" class="btn btn-brand">🔒 Update Password</button>
      </form>
    </div>

    <!-- Notification Settings -->
    <div class="glass-dark" style="padding:32px;border-radius:var(--r);max-width:640px">
      <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:6px;font-size:1.1rem">🔔 Notification Preferences</h3>
      <p style="color:var(--t3);font-size:13px;margin-bottom:22px">Choose which alerts you receive on Campusly</p>
      <form method="POST">
        <input type="hidden" name="save_notifs" value="1">
        <?php
        $candidate_opts = [
          ['notif_bid_selected', '✅ Bid Selected',     'When an employer selects your bid'],
          ['notif_bid_rejected', '❌ Bid Not Selected', 'When your bid is passed over'],
          ['notif_payment',      '💰 Payment Released', 'When employer releases payment for completed work'],
          ['notif_message',      '💬 New Message',      'When you receive a direct message'],
          ['notif_review',       '⭐ New Review',       'When an employer leaves you a review'],
          ['notif_task_update',  '📝 Task Updated',     'When a task you bid on is modified'],
          ['notif_invite',       '📨 Task Invite',      'When an employer invites you to bid on their task'],
        ];
        $employer_opts = [
          ['notif_bid_received', '📋 New Bid Received', 'When a student bids on your task'],
          ['notif_payment',      '💸 Payment Confirmed','Confirmation after you release payment'],
          ['notif_message',      '💬 New Message',      'When a student sends you a message'],
          ['notif_review',       '⭐ New Review',       'When a student leaves you a review'],
          ['notif_task_update',  '📤 Work Submitted',   'When a candidate submits work on your task'],
        ];
        $opts = $role==='employer' ? $employer_opts : $candidate_opts;
        foreach($opts as [$key,$label,$desc]):
          $on = pref($p,$key);
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 0;border-bottom:1px solid var(--border)">
          <div>
            <div style="font-weight:600;font-size:14px"><?= $label ?></div>
            <div style="color:var(--t3);font-size:13px;margin-top:3px"><?= $desc ?></div>
          </div>
          <label class="toggle-label" style="position:relative;display:inline-block;width:50px;height:28px;flex-shrink:0;cursor:pointer">
            <input type="checkbox" name="<?= $key ?>" <?= $on?'checked':'' ?> style="opacity:0;width:0;height:0;position:absolute" onchange="animToggle(this)">
            <div class="toggle-track" style="position:absolute;inset:0;border-radius:14px;background:<?= $on?'var(--blue)':'var(--ink4)' ?>;border:1px solid var(--border2);transition:.25s"></div>
            <div class="toggle-knob" style="position:absolute;width:22px;height:22px;border-radius:50%;background:#fff;top:2px;left:<?= $on?'24px':'2px' ?>;transition:.25s;box-shadow:0 2px 6px rgba(0,0,0,0.3)"></div>
          </label>
        </div>
        <?php endforeach; ?>
        <div style="margin-top:28px">
          <button type="submit" class="btn btn-brand">💾 Save Notification Settings</button>
        </div>
      </form>
    </div>
  </main>
</div>
</div>
<script>
function animToggle(cb){
  const label  = cb.closest('.toggle-label');
  const track  = label.querySelector('.toggle-track');
  const knob   = label.querySelector('.toggle-knob');
  track.style.background = cb.checked ? 'var(--blue)' : 'var(--ink4)';
  knob.style.left = cb.checked ? '24px' : '2px';
}
// Scroll to password section if hash
if(location.hash==='#password') document.getElementById('password')?.scrollIntoView({behavior:'smooth',block:'start'});
</script>
<?php include 'includes/footer.php'; ?>
