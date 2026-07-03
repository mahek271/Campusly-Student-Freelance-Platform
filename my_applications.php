<?php
session_start();
include 'db.php';
requireRole('candidate');
$uid = (int)$_SESSION['user_id'];

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');

// Build WHERE
$wheres = ["a.candidate_id=$uid"];
if($filter !== 'all') $wheres[] = "a.status='".mysqli_real_escape_string($conn,$filter)."'";
if($search)           $wheres[] = "(t.title LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR t.category LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR u.name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR u.company_name LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
$where_sql = implode(' AND ', $wheres);

$apps = mysqli_query($conn,
  "SELECT a.*, t.title, t.price, t.category, t.deadline, t.status as task_status,
    u.name as employer_name, u.company_name
   FROM applications a
   JOIN tasks t ON a.task_id=t.id
   JOIN users u ON t.employer_id=u.id
   WHERE $where_sql
   ORDER BY a.applied_at DESC");

// Counts for filter tabs
$counts = ['all'=>0,'pending'=>0,'selected'=>0,'rejected'=>0,'completed'=>0];
$all_apps = mysqli_query($conn,"SELECT status FROM applications WHERE candidate_id=$uid");
while($r=$all_apps->fetch_assoc()){
  $counts['all']++;
  if(isset($counts[$r['status']])) $counts[$r['status']]++;
}

$result_count = mysqli_num_rows($apps);

$page_title = 'My Bids';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">
  <aside class="sidebar">
    <div class="sidebar-card">
        <a href="candidate_dashboard.php" class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
        <a href="browse_tasks.php" class="sidebar-link"><span class="si">🔍</span>Browse Tasks</a>
        <a href="my_applications.php" class="sidebar-link active"><span class="si">📋</span>My Bids</a>
        <a href="saved_tasks.php" class="sidebar-link"><span class="si">🔖</span>Saved Tasks</a>
        <a href="my_earnings.php" class="sidebar-link"><span class="si">💰</span>Earnings</a>
        <a href="portfolio.php" class="sidebar-link"><span class="si">🖼️</span>Portfolio</a>
        <a href="messages.php" class="sidebar-link"><span class="si">💬</span>Messages</a>
        <a href="leaderboard.php" class="sidebar-link"><span class="si">🏆</span>Leaderboard</a>
        <a href="notifications.php" class="sidebar-link"><span class="si">🔔</span>Notifications</a>
        <a href="notification_settings.php" class="sidebar-link"><span class="si">⚙️</span>Settings</a>
        <a href="profile.php" class="sidebar-link"><span class="si">👤</span>Profile</a>
        <a href="logout.php" class="sidebar-link" style="color:var(--rose)"><span class="si">🚪</span>Logout</a>
      </div>
  </aside>

  <main class="main-content">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
      <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700">📋 My Bids</h1>
    </div>

    <!-- Search bar -->
    <form method="GET" style="margin-bottom:18px">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <div style="display:flex;gap:10px;align-items:center">
        <div style="flex:1;position:relative">
          <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:15px;pointer-events:none">🔍</span>
          <input type="text" name="q" class="finput" placeholder="Search by task name, category, employer…"
            value="<?= htmlspecialchars($search) ?>"
            style="padding-left:42px">
        </div>
        <button type="submit" class="btn btn-brand">Search</button>
        <?php if($search || $filter !== 'all'): ?>
          <a href="my_applications.php" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- Filter tabs -->
    <div style="display:flex;gap:8px;margin-bottom:22px;flex-wrap:wrap">
      <?php foreach(['all'=>'All','pending'=>'⏳ Pending','selected'=>'✅ Selected','rejected'=>'❌ Rejected','completed'=>'🏆 Completed'] as $k=>$label): ?>
        <a href="my_applications.php?filter=<?= $k ?><?= $search?'&q='.urlencode($search):'' ?>"
           class="btn <?= $filter===$k?'btn-brand':'btn-ghost' ?> btn-sm">
          <?= $label ?>
          <?php if($counts[$k] ?? 0): ?>
            <span style="background:rgba(255,255,255,0.2);border-radius:10px;padding:1px 7px;font-size:11px;margin-left:2px"><?= $counts[$k] ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Result count -->
    <?php if($search || $filter !== 'all'): ?>
    <div style="font-size:13px;color:var(--t3);margin-bottom:14px">
      <?= $result_count ?> result<?= $result_count!==1?'s':'' ?> found
      <?= $search ? " for <strong style='color:var(--t1)'>".htmlspecialchars($search)."</strong>" : '' ?>
    </div>
    <?php endif; ?>

    <!-- Bids List -->
    <?php if($result_count === 0): ?>
      <div style="text-align:center;padding:70px 20px;color:var(--t3);background:rgba(255,255,255,0.02);border:1px solid var(--border);border-radius:var(--r)">
        <div style="font-size:3rem;margin-bottom:14px"><?= $search ? '🔍' : '📭' ?></div>
        <div style="font-weight:600;font-size:16px;margin-bottom:8px">
          <?= $search ? 'No bids match your search' : 'No bids in this category' ?>
        </div>
        <?php if($search): ?>
          <div style="color:var(--t4);font-size:13px;margin-bottom:18px">Try different keywords or clear the filter</div>
          <a href="my_applications.php?filter=<?= htmlspecialchars($filter) ?>" class="btn btn-ghost btn-sm">Clear Search</a>
        <?php else: ?>
          <a href="browse_tasks.php" class="btn btn-brand" style="margin-top:16px">Find Tasks →</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:16px">
        <?php while($a=$apps->fetch_assoc()):
          $status_cls=['pending'=>'tag-a','selected'=>'tag-g','rejected'=>'tag-r','completed'=>'tag-c'][$a['status']]??'tag-v';
          $status_icon=['pending'=>'⏳','selected'=>'✅','rejected'=>'❌','completed'=>'🏆'][$a['status']]??'';
          $days_since=ceil((time()-strtotime($a['applied_at']))/86400);
        ?>
        <div class="glass-dark" style="padding:22px;border-radius:var(--r);transition:var(--tr)" onmouseover="this.style.borderColor='rgba(124,92,252,0.3)'" onmouseout="this.style.borderColor='var(--border)'">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px">
            <div style="flex:1;min-width:0">
              <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;align-items:center">
                <span class="tag <?= $status_cls ?>"><?= $status_icon ?> <?= ucfirst($a['status']) ?></span>
                <?php if($a['category']): ?><span class="tag tag-c"><?= htmlspecialchars($a['category']) ?></span><?php endif; ?>
              </div>
              <h3 style="font-weight:700;margin-bottom:5px;font-size:15px;line-height:1.4"><?= htmlspecialchars($a['title']) ?></h3>
              <div style="color:var(--t3);font-size:13px;display:flex;flex-wrap:wrap;gap:10px">
                <span>🏢 <?= htmlspecialchars($a['company_name']?:$a['employer_name']) ?></span>
                <span>· Applied <?= $days_since===1?'1 day':"$days_since days" ?> ago</span>
                <?php if($a['deadline']): ?>
                  <?php $dl=ceil((strtotime($a['deadline'])-time())/86400); ?>
                  <span style="color:<?= $dl<3?'var(--rose)':($dl<7?'var(--amber)':'var(--t3)') ?>">· Deadline: <?= date('d M',strtotime($a['deadline'])) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <div style="font-weight:800;font-size:1.2rem;color:var(--emerald)">₹<?= number_format($a['bid_amount']) ?></div>
              <div style="color:var(--t3);font-size:12px"><?= $a['delivery_days'] ?> days delivery</div>
            </div>
          </div>

          <!-- Proposal preview -->
          <?php if($a['proposal_text']): ?>
          <div style="margin-top:14px;background:var(--surface);border-radius:8px;padding:12px">
            <div style="font-size:11px;color:var(--t3);margin-bottom:5px;font-weight:700;text-transform:uppercase;letter-spacing:.4px">Your Proposal</div>
            <p style="color:var(--t2);font-size:13.5px;line-height:1.6"><?= htmlspecialchars(substr($a['proposal_text'],0,220)) ?><?= strlen($a['proposal_text'])>220?'…':'' ?></p>
          </div>
          <?php endif; ?>

          <!-- Submission status -->
          <?php if($a['submission_link']): ?>
          <div style="margin-top:10px;background:rgba(16,185,129,0.07);border:1px solid rgba(16,185,129,0.2);border-radius:8px;padding:10px;font-size:13px">
            📤 Submitted: <a href="<?= htmlspecialchars($a['submission_link']) ?>" target="_blank" style="color:var(--cyan)"><?= htmlspecialchars(substr($a['submission_link'],0,70)) ?><?= strlen($a['submission_link'])>70?'…':'' ?></a>
          </div>
          <?php endif; ?>

          <!-- Actions -->
          <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
            <a href="task_view.php?id=<?= $a['task_id'] ?>" class="btn btn-ghost btn-sm">🔍 View Task</a>
            <?php if($a['status']==='pending'): ?>
              <a href="withdraw_bid.php?id=<?= $a['id'] ?>" class="btn btn-danger btn-sm">↩️ Withdraw Bid</a>
            <?php endif; ?>
            <?php if($a['status']==='selected' && !$a['submission_link']): ?>
              <a href="submit_task.php?app_id=<?= $a['id'] ?>"  class="btn btn-brand btn-sm">📤 Submit Work</a>
              <a href="task_timer.php?app_id=<?= $a['id'] ?>"   class="btn btn-glass btn-sm">⏱ Timer</a>
            <?php elseif($a['status']==='selected' && $a['submission_link']): ?>
              <a href="submit_task.php?app_id=<?= $a['id'] ?>"  class="btn btn-outline btn-sm">🔄 Update</a>
              <a href="task_timer.php?app_id=<?= $a['id'] ?>"   class="btn btn-glass btn-sm">⏱ Timer</a>
            <?php endif; ?>
            <?php if($a['status']==='completed'): ?>
              <a href="certificate.php?app_id=<?= $a['id'] ?>"  class="btn btn-success btn-sm">🏆 Certificate</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
</div>
<?php include 'includes/footer.php'; ?>
