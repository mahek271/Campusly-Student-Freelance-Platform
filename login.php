<?php
session_start();
include 'db.php';
if(isset($_SESSION['user_id'])){ header("Location: index.php"); exit(); }

$error = '';
$next  = $_GET['next'] ?? '';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $email    = trim($_POST['email']    ?? '');
  $password =      $_POST['password'] ?? '';
  if(!$email || !$password){
    $error = 'Please enter both email and password.';
  } else {
    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if(!$user){
      $error = 'No account found with that email address.';
    } elseif(!empty($user['is_banned'])){
      $error = 'Your account has been suspended. Please contact support.';
    } elseif(!password_verify($password, $user['password'])){
      $error = 'Incorrect password. Please try again.';
    } else {
      session_regenerate_id(true);
      $_SESSION['user_id']   = $user['id'];
      $_SESSION['user_name'] = $user['name'];
      $_SESSION['user_role'] = $user['role'];
      $_SESSION['flash_success'] = "Welcome back, {$user['name']}! 👋";
      $dest = $next ?: match($user['role']){
        'admin'    => 'admin_dashboard.php',
        'employer' => 'employer_dashboard.php',
        default    => 'candidate_dashboard.php',
      };
      header("Location: $dest");
      exit();
    }
  }
}

$page_title = 'Login';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div class="auth-wrap">
    <div class="auth-card" style="max-width:440px">

      <!-- Logo -->
      <div class="auth-logo">
        <div class="brand-mark" style="margin-right:10px">🎯</div>
        <span class="brand-name">Campusly</span>
      </div>
      <h1 class="auth-title">Welcome back</h1>
      <p class="auth-sub">Sign in to your Campusly account</p>

      <?php if($error): ?>
        <div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" autocomplete="off">
        <div class="fg">
          <label class="flabel">Email Address</label>
          <div class="fwrap">
            <span class="ficon">✉️</span>
            <input type="email" name="email" class="finput"
              placeholder="you@email.com"
              required autofocus autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
          </div>
        </div>

        <div class="fg">
          <label class="flabel">Password</label>
          <div style="position:relative">
            <input type="password" name="password" id="pwdField" class="finput"
              placeholder="Your password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')">
            <span onclick="togglePwd()" title="Show/hide"
              style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--t3);font-size:17px;user-select:none"
              id="eye-icon">👁</span>
          </div>
        </div>

        <div style="text-align:right;margin-top:-8px;margin-bottom:20px">
          <a href="forgot_password.php" style="color:var(--violet2);font-size:13px">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-brand btn-full btn-lg">Sign In →</button>
      </form>

      <p style="text-align:center;margin-top:20px;color:var(--t3);font-size:13.5px">
        Don't have an account?
        <a href="register.php" style="color:var(--violet2);font-weight:600">Sign up free →</a>
      </p>
    </div>
  </div>
</div>

<script>
function togglePwd(){
  const f = document.getElementById('pwdField');
  const e = document.getElementById('eye-icon');
  f.type = f.type === 'password' ? 'text' : 'password';
  e.textContent = f.type === 'password' ? '👁' : '🙈';
}
</script>
<?php include 'includes/footer.php'; ?>
