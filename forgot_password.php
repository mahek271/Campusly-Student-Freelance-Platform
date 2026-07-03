<?php
session_start();
include 'db.php';

$error = $success = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $email = trim($_POST['email'] ?? '');
  if(!$email){ $error='Please enter your email.'; }
  else {
    $user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id,name FROM users WHERE email='".esc($conn,$email)."' LIMIT 1"));
    if(!$user){
      $error='No account with that email exists.';
    } else {
      $code    = rand(100000,999999);
      $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
      $safe_email = esc($conn,$email);
      $conn->query("DELETE FROM password_resets WHERE email='$safe_email'");
      $conn->query("INSERT INTO password_resets (email,code,expires_at) VALUES ('$safe_email','$code','$expires')");
      // In a real app, you'd email this code. Here we show it for demo.
      $_SESSION['reset_email']=$email;
      $_SESSION['demo_code']=$code;
      $success = "In a real app, a code would be emailed. For demo purposes, your code is: <strong style='font-size:1.4rem;color:var(--violet2)'>$code</strong>";
    }
  }
}

$page_title='Forgot Password';
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
      <h1 class="auth-title">Reset Password</h1>
      <p class="auth-sub">Enter your email to receive a reset code</p>

      <?php if($error):  ?><div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if($success): ?>
        <div class="alert alert-ok">✅ <?= $success ?></div>
        <a href="reset_password.php" class="btn btn-brand btn-full btn-lg" style="margin-top:8px">Enter Reset Code →</a>
        <p style="text-align:center;margin-top:16px"><a href="login.php" style="color:var(--t3);font-size:13px">← Back to login</a></p>
      <?php else: ?>
        <form method="POST">
          <div class="fg">
            <label class="flabel">Email Address</label>
            <div class="fwrap"><span class="ficon">✉️</span>
              <input type="email" name="email" class="finput" placeholder="you@email.com" required>
            </div>
          </div>
          <button type="submit" class="btn btn-brand btn-full btn-lg">Send Reset Code</button>
        </form>
        <p style="text-align:center;margin-top:20px;font-size:13.5px;color:var(--t3)">
          <a href="login.php" style="color:var(--violet2)">← Back to login</a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
