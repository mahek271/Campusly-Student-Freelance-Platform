<?php
session_start();
include 'db.php';

$error = $success = '';
$email = $_SESSION['reset_email'] ?? '';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $code    = trim($_POST['code']     ?? '');
  $newpwd  =      $_POST['password'] ?? '';
  $confirm =      $_POST['confirm']  ?? '';
  $email   = trim($_POST['email']    ?? '');

  if(!$email || !$code || !$newpwd){ $error='All fields required.'; }
  elseif(strlen($newpwd)<8){ $error='Password must be 8+ characters.'; }
  elseif(!preg_match('/[A-Z]/',$newpwd)||!preg_match('/[0-9]/',$newpwd)){ $error='Password needs uppercase and number.'; }
  elseif($newpwd!==$confirm){ $error='Passwords do not match.'; }
  else {
    $safe_email=esc($conn,$email); $safe_code=esc($conn,$code);
    $row=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM password_resets WHERE email='$safe_email' AND code='$safe_code' AND expires_at>NOW() LIMIT 1"));
    if(!$row){ $error='Invalid or expired code. Please request a new one.'; }
    else {
      $hashed=password_hash($newpwd,PASSWORD_DEFAULT);
      $stmt_r=$conn->prepare("UPDATE users SET password=? WHERE email=?");
      $stmt_r->bind_param("ss",$hashed,$email);
      $stmt_r->execute();
      $conn->query("DELETE FROM password_resets WHERE email='$safe_email'");
      unset($_SESSION['reset_email'],$_SESSION['demo_code']);
      $success='Password reset successfully! You can now login.';
    }
  }
}

$page_title='Reset Password';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-logo">
        <div class="brand-mark" style="margin-right:8px">🎯</div>
        <span class="brand-name">Campusly</span>
      </div>
      <h1 class="auth-title">Enter Reset Code</h1>

      <?php if($success): ?>
        <div class="alert alert-ok">✅ <?= htmlspecialchars($success) ?></div>
        <a href="login.php" class="btn btn-brand btn-full btn-lg" style="margin-top:12px">Login Now →</a>
      <?php else: ?>
        <?php if($error): ?><div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
          <div class="fg">
            <label class="flabel">Email</label>
            <input type="email" name="email" class="finput" value="<?= htmlspecialchars($email) ?>" required>
          </div>
          <div class="fg">
            <label class="flabel">Reset Code</label>
            <input type="text" name="code" class="finput" placeholder="6-digit code" maxlength="6" required>
          </div>
          <div class="fg">
            <label class="flabel">New Password</label>
            <input type="password" name="password" class="finput" placeholder="Min 8 chars, uppercase, number" required>
          </div>
          <div class="fg">
            <label class="flabel">Confirm Password</label>
            <input type="password" name="confirm" class="finput" placeholder="Repeat new password" required>
          </div>
          <button type="submit" class="btn btn-brand btn-full btn-lg">Reset Password</button>
        </form>
        <p style="text-align:center;margin-top:16px">
          <a href="forgot_password.php" style="color:var(--t3);font-size:13px">Request new code</a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
