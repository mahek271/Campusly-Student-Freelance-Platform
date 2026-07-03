<?php
session_start();
include 'db.php';
requireRole('employer');

$uid     = (int)$_SESSION['user_id'];
$task_id = intval($_GET['id'] ?? 0);
if(!$task_id){ header("Location: employer_dashboard.php"); exit(); }

$task = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT * FROM tasks WHERE id=$task_id AND employer_id=$uid"));
if(!$task){ header("Location: employer_dashboard.php"); exit(); }

// Handle assign
if(isset($_GET['assign'])){
  $app_id = intval($_GET['assign']);
  $app    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM applications WHERE id=$app_id AND task_id=$task_id"));
  if($app){
    $conn->query("UPDATE applications SET status='selected' WHERE id=$app_id");
    $conn->query("UPDATE applications SET status='rejected' WHERE task_id=$task_id AND id!=$app_id AND status='pending'");
    $conn->query("UPDATE tasks SET status='assigned' WHERE id=$task_id");
    // Notify winner
    $msg = "🎉 You were selected for the task: {$task['title']}!";
    $conn->query("INSERT INTO notifications (user_id,message,link) VALUES ({$app['candidate_id']},'$msg','my_applications.php')");
    // Notify rejected
    $rejected = mysqli_query($conn,"SELECT candidate_id FROM applications WHERE task_id=$task_id AND status='rejected'");
    while($r=$rejected->fetch_assoc()){
      $conn->query("INSERT INTO notifications (user_id,message,link) VALUES ({$r['candidate_id']},'Your bid on \"{$task['title']}\" was not selected this time.','browse_tasks.php')");
    }
    $_SESSION['flash_success']='Candidate selected! They have been notified. ✅';
  }
  header("Location: view_applicants.php?id=$task_id"); exit();
}

// Handle release payment
if(isset($_GET['release'])){
  $app_id = intval($_GET['release']);
  $app    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM applications WHERE id=$app_id AND task_id=$task_id AND status='selected'"));
  $already_paid = $app ? mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM payments WHERE application_id=$app_id AND status='released' LIMIT 1")) : null;
  if($app && !$already_paid){
    // Insert payment record
    $conn->query("INSERT INTO payments (task_id,application_id,employer_id,candidate_id,amount,status,created_at)
      VALUES ($task_id,$app_id,$uid,{$app['candidate_id']},{$app['bid_amount']},'released',NOW())");
    $conn->query("UPDATE applications SET status='completed' WHERE id=$app_id");
    $conn->query("UPDATE tasks SET status='completed' WHERE id=$task_id");
    $msg="💰 ₹".number_format($app['bid_amount'])." payment released for task: {$task['title']}!";
    $conn->query("INSERT INTO notifications (user_id,message,link) VALUES ({$app['candidate_id']},'$msg','my_earnings.php')");
    $_SESSION['flash_success']='Payment of ₹'.number_format($app['bid_amount']).' released! 💸';
  }
  header("Location: view_applicants.php?id=$task_id"); exit();
}

// Get all applicants
$apps = mysqli_query($conn,
  "SELECT a.*, u.name, u.university, u.skills as student_skills, u.email,
    COALESCE(AVG(r.rating),0) as avg_rating,
    COUNT(DISTINCT r.id) as review_count,
    COUNT(DISTINCT prev.id) as tasks_done
   FROM applications a
   JOIN users u ON a.candidate_id=u.id
   LEFT JOIN reviews r ON r.reviewee_id=u.id
   LEFT JOIN applications prev ON prev.candidate_id=u.id AND prev.status='completed'
   WHERE a.task_id=$task_id
   GROUP BY a.id
   ORDER BY a.bid_amount ASC");

$page_title = 'Applicants — '.$task['title'];
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-card">
      <a href="employer_dashboard.php" class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
      <a href="post_task.php" class="sidebar-link"><span class="si">➕</span>Post Task</a>
      <a href="manage_tasks.php" class="sidebar-link active"><span class="si">📋</span>My Tasks</a>
      <a href="task_invite.php" class="sidebar-link"><span class="si">📨</span>Invite Students</a>
      <a href="task_analytics.php" class="sidebar-link"><span class="si">📊</span>Analytics</a>
      <a href="messages.php" class="sidebar-link"><span class="si">💬</span>Messages</a>
      <a href="payment_history.php" class="sidebar-link"><span class="si">💳</span>Payments</a>
      <a href="notifications.php" class="sidebar-link"><span class="si">🔔</span>Notifications</a>
      <a href="notification_settings.php" class="sidebar-link"><span class="si">⚙️</span>Settings</a>
      <a href="profile.php" class="sidebar-link"><span class="si">👤</span>Profile</a>
      <a href="logout.php" class="sidebar-link" style="color:var(--rose)"><span class="si">🚪</span>Logout</a>
    </div>
  </aside>

  <main class="main-content">
    <div style="margin-bottom:24px">
      <a href="manage_tasks.php" style="color:var(--t3);font-size:13.5px">← My Tasks</a>
    </div>

    <!-- Task summary card -->
    <div class="glass-dark" style="padding:24px;border-radius:var(--r);margin-bottom:28px">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px">
        <div>
          <h1 style="font-family:var(--fh);font-size:1.4rem;font-weight:700;margin-bottom:6px"><?= htmlspecialchars($task['title']) ?></h1>
          <div style="display:flex;gap:10px;flex-wrap:wrap">
            <span class="tag tag-c"><?= htmlspecialchars($task['category']) ?></span>
            <span class="tag <?= $task['status']==='open'?'tag-g':($task['status']==='assigned'?'tag-a':'tag-c') ?>"><?= ucfirst($task['status']) ?></span>
          </div>
        </div>
        <div style="text-align:right">
          <div style="font-size:1.8rem;font-weight:800;color:var(--emerald);font-family:var(--fh)">₹<?= number_format($task['price']) ?></div>
          <div style="color:var(--t3);font-size:13px">Budget</div>
        </div>
      </div>
    </div>

    <h2 style="font-family:var(--fh);font-size:1.2rem;font-weight:700;margin-bottom:20px">
      👥 <?= mysqli_num_rows($apps) ?> Applicants
    </h2>

    <?php if(mysqli_num_rows($apps)===0): ?>
      <div style="text-align:center;padding:60px;color:var(--t3)">
        <div style="font-size:3rem;margin-bottom:12px">🕐</div>
        <div style="font-weight:600">No bids yet</div>
        <p style="margin-top:8px">Share your task link to attract more applicants</p>
      </div>
    <?php else: ?>
      <?php while($a=$apps->fetch_assoc()):
        $status_cls=['pending'=>'tag-a','selected'=>'tag-g','rejected'=>'tag-r','completed'=>'tag-c'][$a['status']]??'tag-v';
      ?>
      <div class="glass-dark" style="padding:24px;border-radius:var(--r);margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:16px;margin-bottom:16px">
          <div style="display:flex;gap:14px;align-items:center">
            <div class="avatar avatar-lg"><?= strtoupper(substr($a['name'],0,1)) ?></div>
            <div>
              <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($a['name']) ?></div>
              <div style="color:var(--t3);font-size:13px"><?= htmlspecialchars($a['university']??'') ?></div>
              <div style="display:flex;gap:6px;margin-top:4px;align-items:center">
                <?php for($i=1;$i<=5;$i++): ?>
                  <span class="star <?= $i<=$a['avg_rating']?'on':'off' ?>">★</span>
                <?php endfor; ?>
                <span style="font-size:12px;color:var(--t3)">(<?= $a['review_count'] ?> reviews · <?= $a['tasks_done'] ?> tasks done)</span>
              </div>
              <?php
              // Show skill badges for this candidate
              $cand_badges = mysqli_query($conn,"SELECT skill_name,score FROM skill_badges WHERE user_id={$a['candidate_id']} ORDER BY earned_at DESC LIMIT 5");
              if(mysqli_num_rows($cand_badges)>0):
              ?>
              <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px">
                <?php while($sb=$cand_badges->fetch_assoc()): ?>
                  <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.25);font-size:11px;font-weight:600;color:var(--amber)">
                    🏅 <?= htmlspecialchars($sb['skill_name']) ?> <?= $sb['score'] ?>%
                  </span>
                <?php endwhile; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <div style="text-align:right">
            <div style="font-size:1.4rem;font-weight:800;color:var(--emerald);font-family:var(--fh)">₹<?= number_format($a['bid_amount']) ?></div>
            <div style="color:var(--t3);font-size:13px">in <?= $a['delivery_days'] ?> days</div>
            <div style="margin-top:6px"><span class="tag <?= $status_cls ?>"><?= ucfirst($a['status']) ?></span></div>
          </div>
        </div>

        <!-- Proposal -->
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px;margin-bottom:16px">
          <div style="font-size:11px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">📝 Proposal</div>
          <p style="color:var(--t2);font-size:14px;line-height:1.7"><?= nl2br(htmlspecialchars($a['proposal_text'])) ?></p>
        </div>

        <!-- Submission if done -->
        <?php if($a['submission_link']): ?>
        <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:10px;padding:14px;margin-bottom:16px">
          <div style="font-size:11px;font-weight:700;color:var(--emerald);margin-bottom:6px">📤 Work Submitted</div>
          <a href="<?= htmlspecialchars($a['submission_link']) ?>" target="_blank" style="color:var(--cyan);font-size:14px;word-break:break-all"><?= htmlspecialchars($a['submission_link']) ?></a>
          <?php if($a['submission_note']): ?>
            <p style="color:var(--t2);font-size:13px;margin-top:8px"><?= nl2br(htmlspecialchars($a['submission_note'])) ?></p>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <a href="view_student_profile.php?id=<?= $a['candidate_id'] ?>" class="btn btn-ghost btn-sm">👤 View Profile</a>
          <?php if(in_array($a['status'],['selected','completed'])): ?>
            <a href="messages.php?thread=<?= $a['candidate_id'] ?>&task=<?= $task_id ?>" class="btn btn-outline btn-sm">💬 Message</a>
          <?php endif; ?>
          <?php if($a['status']==='pending' && $task['status']==='open'): ?>
            <a href="view_applicants.php?id=<?= $task_id ?>&assign=<?= $a['id'] ?>" class="btn btn-success btn-sm"
               onclick="return confirm('Select <?= htmlspecialchars($a['name']) ?> for this task?')">
              ✅ Select Candidate
            </a>
          <?php endif; ?>
          <?php if($a['status']==='selected' && $a['submission_link']): ?>
            <a href="view_applicants.php?id=<?= $task_id ?>&release=<?= $a['id'] ?>" class="btn btn-brand btn-sm"
               onclick="return confirm('Release ₹<?= number_format($a['bid_amount']) ?> payment to <?= htmlspecialchars($a['name']) ?>?')">
              💸 Release Payment
            </a>
            <a href="review_task.php?app_id=<?= $a['id'] ?>" class="btn btn-outline btn-sm">⭐ Leave Review</a>
          <?php endif; ?>
          <?php if($a['status']==='completed'): ?>
            <span class="btn btn-success btn-sm" style="cursor:default">✅ Completed & Paid</span>
            <?php if(!mysqli_num_rows(mysqli_query($conn,"SELECT id FROM reviews WHERE task_id=$task_id AND reviewer_id=$uid LIMIT 1"))): ?>
              <a href="review_task.php?app_id=<?= $a['id'] ?>" class="btn btn-outline btn-sm">⭐ Leave Review</a>
            <?php endif; ?>
            <a href="dispute.php?task_id=<?= $task_id ?>" class="btn btn-ghost btn-sm" style="color:var(--rose)">⚠️ Dispute</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </main>
</div>
</div>
<?php include 'includes/footer.php'; ?>
