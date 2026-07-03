<?php
session_start();
include 'db.php';
requireRole('candidate');
$uid = (int)$_SESSION['user_id'];

$payments = mysqli_query($conn,
  "SELECT p.*, t.title, u.name as employer_name, u.company_name,
    a.id as app_id
   FROM payments p
   JOIN tasks t        ON p.task_id=t.id
   JOIN users u        ON p.employer_id=u.id
   LEFT JOIN applications a ON a.id=p.application_id
   WHERE p.candidate_id=$uid AND p.status='released'
   ORDER BY p.created_at DESC");

$total_earned = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE candidate_id=$uid AND status='released'"))['t'];
$month_earned = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE candidate_id=$uid AND status='released' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"))['t'];
$total_tasks  = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COUNT(*) as t FROM payments WHERE candidate_id=$uid AND status='released'"))['t'];

$page_title='My Earnings';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">
  <aside class="sidebar">
    <div class="sidebar-card">
            <div class="sidebar-card">
        <a href="candidate_dashboard.php" class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
        <a href="browse_tasks.php" class="sidebar-link"><span class="si">🔍</span>Browse Tasks</a>
        <a href="my_applications.php" class="sidebar-link"><span class="si">📋</span>My Bids</a>
        <a href="saved_tasks.php" class="sidebar-link"><span class="si">🔖</span>Saved Tasks</a>
        <a href="my_earnings.php" class="sidebar-link active"><span class="si">💰</span>Earnings</a>
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
    <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700;margin-bottom:24px">💰 My Earnings</h1>

    <!-- Stats -->
    <div class="grid-3" style="margin-bottom:32px">
      <div class="stat-box" style="text-align:left">
        <div style="font-size:1.5rem;margin-bottom:8px">💰</div>
        <div style="font-size:1.8rem;font-weight:800;color:var(--emerald);font-family:var(--fh)">₹<?= number_format($total_earned) ?></div>
        <div style="color:var(--t3);font-size:13px">Total Earned</div>
      </div>
      <div class="stat-box" style="text-align:left">
        <div style="font-size:1.5rem;margin-bottom:8px">📅</div>
        <div style="font-size:1.8rem;font-weight:800;color:var(--violet2);font-family:var(--fh)">₹<?= number_format($month_earned) ?></div>
        <div style="color:var(--t3);font-size:13px">This Month</div>
      </div>
      <div class="stat-box" style="text-align:left">
        <div style="font-size:1.5rem;margin-bottom:8px">🏆</div>
        <div style="font-size:1.8rem;font-weight:800;color:var(--amber);font-family:var(--fh)"><?= $total_tasks ?></div>
        <div style="color:var(--t3);font-size:13px">Tasks Paid</div>
      </div>
    </div>

    <!-- Payment History -->
    <div class="glass-dark" style="padding:24px;border-radius:var(--r)">
      <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:20px">💳 Payment History</h3>
      <?php if(mysqli_num_rows($payments)===0): ?>
        <div style="text-align:center;padding:50px;color:var(--t3)">
          <div style="font-size:3rem;margin-bottom:12px">💸</div>
          <div style="font-weight:600;margin-bottom:8px">No payments yet</div>
          <a href="browse_tasks.php" class="btn btn-brand">Start Earning →</a>
        </div>
      <?php else: ?>
        <div style="overflow-x:auto">
          <table class="tbl">
            <thead><tr><th>Task</th><th>Employer</th><th>Amount</th><th>Date</th><th></th></tr></thead>
            <tbody>
              <?php while($p=$payments->fetch_assoc()): ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars(substr($p['title'],0,45)) ?><?= strlen($p['title'])>45?'…':'' ?></td>
                <td style="color:var(--t3)"><?= htmlspecialchars($p['company_name']?:$p['employer_name']) ?></td>
                <td style="color:var(--emerald);font-weight:800;font-size:1rem">₹<?= number_format($p['amount']) ?></td>
                <td style="color:var(--t3);font-size:13px"><?= date('d M Y',strtotime($p['created_at'])) ?></td>
                <td>
                  <?php if($p['app_id']): ?>
                    <a href="certificate.php?app_id=<?= $p['app_id'] ?>" class="btn btn-success btn-sm" target="_blank">🏆 Certificate</a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
</div>
<?php include 'includes/footer.php'; ?>
