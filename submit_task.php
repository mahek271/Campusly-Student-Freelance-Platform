<?php
session_start();
include 'db.php';
requireRole('candidate');

$uid    = (int)$_SESSION['user_id'];
$app_id = intval($_GET['app_id'] ?? 0);
if(!$app_id){ header("Location: candidate_dashboard.php"); exit(); }

$app = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT a.*, t.title, t.price, t.deadline, u.name as employer_name, u.id as eid
   FROM applications a
   JOIN tasks t ON a.task_id=t.id
   JOIN users u ON t.employer_id=u.id
   WHERE a.id=$app_id AND a.candidate_id=$uid AND a.status='selected'"));

if(!$app){ header("Location: candidate_dashboard.php"); exit(); }

$error = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $link = trim($_POST['submission_link'] ?? '');
  $note = trim($_POST['submission_note'] ?? '');

  if(!$link){ $error='Please provide a submission link or URL.'; }
  elseif(!filter_var($link, FILTER_VALIDATE_URL)){ $error='Please provide a valid URL (include https://).'; }
  else {
    $stmt=$conn->prepare("UPDATE applications SET submission_link=?, submission_note=? WHERE id=?");
    $stmt->bind_param("ssi",$link,$note,$app_id);
    if($stmt->execute()){
      // Notify employer
      $msg="📤 Work submitted for task: {$app['title']}. Please review and release payment.";
      $safe_msg = mysqli_real_escape_string($conn,$msg);
      $conn->query("INSERT INTO notifications (user_id,message,link) VALUES ({$app['eid']},'$safe_msg','view_applicants.php?id={$app['task_id']}')");
      $_SESSION['flash_success']='Work submitted! Waiting for employer approval. ✅';
      header("Location: candidate_dashboard.php"); exit();
    } else {
      $error='Submission failed. Please try again.';
    }
  }
}

$page_title='Submit Work — '.$app['title'];
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div style="max-width:620px;margin:0 auto;padding:100px 20px 60px">
    <a href="candidate_dashboard.php" style="color:var(--t3);font-size:13.5px">← Back to Dashboard</a>
    <a href="task_timer.php?app_id=<?= $app_id ?>" class="btn btn-ghost btn-sm" style="margin-left:14px">⏱ View Timer</a>

    <div style="margin:24px 0 32px;text-align:center">
      <div class="tag tag-g" style="margin-bottom:12px">📤 Submit Work</div>
      <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700"><?= htmlspecialchars($app['title']) ?></h1>
      <p style="color:var(--t3);margin-top:8px">by <?= htmlspecialchars($app['employer_name']) ?></p>
    </div>

    <!-- Task Summary -->
    <div class="glass-dark" style="padding:20px;border-radius:var(--r);margin-bottom:24px">
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;text-align:center">
        <div>
          <div style="font-size:11px;color:var(--t3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Your Bid</div>
          <div style="font-weight:700;color:var(--emerald);font-size:1.1rem">₹<?= number_format($app['bid_amount']) ?></div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--t3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Delivery</div>
          <div style="font-weight:700;font-size:1.1rem"><?= $app['delivery_days'] ?> days</div>
        </div>
        <div>
          <div style="font-size:11px;color:var(--t3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Deadline</div>
          <div style="font-weight:700;font-size:1.1rem"><?= $app['deadline']?date('d M',strtotime($app['deadline'])):'Flexible' ?></div>
        </div>
      </div>
    </div>

    <?php if($app['submission_link']): ?>
      <div class="alert alert-ok">
        ✅ You already submitted work!<br>
        Link: <a href="<?= htmlspecialchars($app['submission_link']) ?>" target="_blank" style="color:var(--cyan)"><?= htmlspecialchars($app['submission_link']) ?></a><br>
        <small style="opacity:.7">You can update your submission below.</small>
      </div>
    <?php endif; ?>

    <?php if($error): ?>
      <div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="glass" style="padding:32px;border-radius:var(--r)">
      <form method="POST">
        <div class="fg">
          <label class="flabel">Submission Link / URL <span style="color:var(--rose)">*</span></label>
          <div class="fwrap"><span class="ficon">🔗</span>
            <input type="url" name="submission_link" class="finput" placeholder="https://drive.google.com/… or https://github.com/…" required value="<?= htmlspecialchars($app['submission_link']??'') ?>">
          </div>
          <div class="fhelp">Share a Google Drive, GitHub, Figma, or any public link to your work</div>
        </div>

        <div class="fg">
          <label class="flabel">Notes to Employer (optional)</label>
          <textarea name="submission_note" class="finput" rows="4" placeholder="Describe what you've done, any notes for the employer, passwords if needed…"><?= htmlspecialchars($app['submission_note']??'') ?></textarea>
        </div>

        <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:10px;padding:14px;margin-bottom:20px;font-size:13px;color:var(--amber)">
          ⚠️ <strong>Important:</strong> Make sure your link is publicly accessible before submitting. The employer will review your work and release payment upon approval.
        </div>

        <button type="submit" class="btn btn-brand btn-full btn-lg">📤 Submit Work</button>
      </form>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
