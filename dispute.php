<?php
/**
 * dispute.php — Task Dispute System
 * Candidates or employers can raise a dispute on a task.
 * Admin reviews and resolves disputes from admin panel.
 */
session_start();
include 'db.php';
requireLogin();

$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'];
$tid  = intval($_GET['task_id'] ?? 0);
if(!$tid){ header("Location: index.php"); exit(); }

// Load task
$task = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT t.*, u.name as employer_name, u.id as eid
   FROM tasks t JOIN users u ON t.employer_id=u.id
   WHERE t.id=$tid AND t.status IN ('assigned','completed') LIMIT 1"));
if(!$task){ $_SESSION['flash_error']='Task not found or not eligible for dispute.'; header("Location: index.php"); exit(); }

// Check involvement
$is_employer  = ($uid === (int)$task['eid']);
$is_candidate = false;
if($role==='candidate'){
  $app=mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM applications WHERE task_id=$tid AND candidate_id=$uid AND status IN ('selected','completed') LIMIT 1"));
  $is_candidate = (bool)$app;
}
if(!$is_employer && !$is_candidate){ $_SESSION['flash_error']='You are not part of this task.'; header("Location: index.php"); exit(); }

// Already disputed?
$existing=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM disputes WHERE task_id=$tid AND raised_by=$uid LIMIT 1"));

$error=$success='';

if($_SERVER['REQUEST_METHOD']==='POST' && !$existing){
  $reason      = trim($_POST['reason']      ?? '');
  $description = trim($_POST['description'] ?? '');
  if(strlen($description)<30){ $error='Please describe the issue in at least 30 characters.'; }
  elseif(!$reason){             $error='Please select a reason.'; }
  else {
    $stmt=$conn->prepare("INSERT INTO disputes (task_id,raised_by,reason,description,status,created_at) VALUES (?,?,?,?,'open',NOW())");
    $stmt->bind_param("iiss",$tid,$uid,$reason,$description);
    if($stmt->execute()){
      // Notify admin
      $conn->query("INSERT INTO notifications (user_id,message,link)
        SELECT id,'⚠️ New dispute raised on task: ".addslashes($task['title'])."','admin_dashboard.php#disputes'
        FROM users WHERE role='admin'");
      // Notify other party
      $other = $is_employer ? "SELECT candidate_id as id FROM applications WHERE task_id=$tid AND status IN ('selected','completed') LIMIT 1"
                            : "SELECT employer_id as id FROM tasks WHERE id=$tid LIMIT 1";
      $other_row=mysqli_fetch_assoc(mysqli_query($conn,$other));
      if($other_row){
        $safe=addslashes($task['title']);
        $conn->query("INSERT INTO notifications (user_id,message,link) VALUES ({$other_row['id']},'⚠️ A dispute has been raised on task: $safe','dispute.php?task_id=$tid')");
      }
      $success='Dispute raised successfully. Our admin team will review within 48 hours.';
      $existing=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM disputes WHERE task_id=$tid AND raised_by=$uid LIMIT 1"));
    } else { $error='Failed to submit dispute: '.$conn->error; }
  }
}

$page_title='Raise Dispute — '.$task['title'];
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div style="max-width:640px;margin:0 auto;padding:100px 20px 60px">
    <a href="javascript:history.back()" style="color:var(--t3);font-size:13.5px;display:inline-block;margin-bottom:24px">← Back</a>

    <div style="text-align:center;margin-bottom:32px">
      <div class="tag tag-r" style="margin-bottom:12px">⚠️ Dispute</div>
      <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700">Raise a Task Dispute</h1>
      <p style="color:var(--t3);font-size:14px;margin-top:8px;max-width:440px;margin-left:auto;margin-right:auto">
        If there is a genuine issue with this task — missed deadline, unsatisfactory work, or payment problem — describe it below. Admin will review within 48 hours.
      </p>
    </div>

    <!-- Task Summary -->
    <div class="glass-dark" style="padding:18px 22px;border-radius:var(--r);margin-bottom:24px;display:flex;justify-content:space-between;align-items:center">
      <div>
        <div style="font-weight:700"><?= htmlspecialchars($task['title']) ?></div>
        <div style="color:var(--t3);font-size:13px">by <?= htmlspecialchars($task['employer_name']) ?></div>
      </div>
      <span class="tag <?= $task['status']==='completed'?'tag-c':'tag-a' ?>"><?= ucfirst($task['status']) ?></span>
    </div>

    <?php if($error):  ?><div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if($success):?><div class="alert alert-ok">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if($existing): ?>
      <!-- Show existing dispute status -->
      <div class="glass" style="padding:28px;border-radius:var(--r)">
        <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:16px">Your Dispute</h3>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
          <span class="tag tag-v"><?= htmlspecialchars($existing['reason']) ?></span>
          <span class="tag <?= $existing['status']==='open'?'tag-a':($existing['status']==='resolved'?'tag-g':'tag-r') ?>">
            <?= ucfirst($existing['status']) ?>
          </span>
        </div>
        <p style="color:var(--t2);font-size:14px;line-height:1.7;margin-bottom:16px"><?= nl2br(htmlspecialchars($existing['description'])) ?></p>
        <div style="font-size:12px;color:var(--t3)">Raised on <?= date('d M Y, h:i A',strtotime($existing['created_at'])) ?></div>
        <?php if($existing['admin_note']): ?>
          <div style="margin-top:16px;background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:10px;padding:14px">
            <div style="font-size:11px;font-weight:700;color:var(--emerald);margin-bottom:6px">ADMIN RESPONSE</div>
            <p style="color:var(--t2);font-size:14px"><?= nl2br(htmlspecialchars($existing['admin_note'])) ?></p>
          </div>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="glass" style="padding:32px;border-radius:var(--r)">
        <form method="POST">
          <div class="fg">
            <label class="flabel">Dispute Reason <span style="color:var(--rose)">*</span></label>
            <select name="reason" class="finput" required>
              <option value="">Select a reason…</option>
              <option value="Work not delivered">Work not delivered by deadline</option>
              <option value="Work quality unsatisfactory">Work quality is unsatisfactory</option>
              <option value="Payment not released">Payment not released after delivery</option>
              <option value="Requirements changed">Requirements changed after acceptance</option>
              <option value="Communication breakdown">Communication breakdown</option>
              <option value="Fraudulent activity">Suspected fraudulent activity</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="fg">
            <label class="flabel">Describe the Issue <span style="color:var(--rose)">*</span></label>
            <textarea name="description" class="finput" rows="6" minlength="30"
              placeholder="Be specific — what happened, when, and what outcome you expect… (min 30 characters)"></textarea>
          </div>
          <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.2);border-radius:10px;padding:14px;margin-bottom:20px;font-size:13px;color:var(--amber)">
            ⚠️ <strong>Note:</strong> Raising false disputes may result in account suspension. Only raise a dispute if you have a genuine issue.
          </div>
          <button type="submit" class="btn btn-danger btn-full btn-lg">⚠️ Submit Dispute</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
