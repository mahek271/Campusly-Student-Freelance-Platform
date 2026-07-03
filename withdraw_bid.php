<?php
session_start();
include 'db.php';
requireRole('candidate');

$uid    = (int)$_SESSION['user_id'];
$app_id = intval($_GET['id'] ?? 0);
if(!$app_id){ header("Location: my_applications.php"); exit(); }

$app = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT a.*, t.title FROM applications a JOIN tasks t ON t.id=a.task_id
   WHERE a.id=$app_id AND a.candidate_id=$uid AND a.status='pending' LIMIT 1"));

if(!$app){
  $_SESSION['flash_error']='Bid cannot be withdrawn. It may have already been processed.';
  header("Location: my_applications.php"); exit();
}

if(isset($_POST['confirm'])){
  $conn->query("DELETE FROM applications WHERE id=$app_id AND candidate_id=$uid AND status='pending'");
  $_SESSION['flash_success']='Bid withdrawn successfully.';
  header("Location: my_applications.php"); exit();
}

$page_title='Withdraw Bid';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div style="max-width:480px;margin:0 auto;padding:110px 20px 60px;text-align:center">
    <div style="font-size:3.5rem;margin-bottom:16px">⚠️</div>
    <h1 style="font-family:var(--fh);font-size:1.6rem;font-weight:700;margin-bottom:10px">Withdraw Bid?</h1>
    <p style="color:var(--t2);margin-bottom:8px">You are about to withdraw your bid on:</p>
    <div class="glass-dark" style="padding:18px 22px;border-radius:var(--r);margin:16px 0 24px;text-align:left">
      <div style="font-weight:700"><?= htmlspecialchars($app['title']) ?></div>
      <div style="color:var(--emerald);font-weight:700;font-size:1.1rem;margin-top:6px">₹<?= number_format($app['bid_amount']) ?></div>
      <div style="color:var(--t3);font-size:13px;margin-top:4px">Submitted <?= date('d M Y',strtotime($app['applied_at'])) ?></div>
    </div>
    <div class="alert alert-warn" style="margin-bottom:24px;text-align:left">
      ⚠️ This action is permanent. You will not be able to rebid on this task after withdrawing.
    </div>
    <form method="POST" style="display:flex;gap:12px;justify-content:center">
      <button type="submit" name="confirm" class="btn btn-danger btn-lg">Yes, Withdraw Bid</button>
      <a href="my_applications.php" class="btn btn-ghost btn-lg">Cancel</a>
    </form>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
