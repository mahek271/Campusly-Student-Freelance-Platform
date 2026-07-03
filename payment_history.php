<?php
session_start();
include 'db.php';
requireRole('employer');
$uid = (int)$_SESSION['user_id'];

$payments = mysqli_query($conn,
  "SELECT p.*, t.title, u.name as candidate_name, u.university
   FROM payments p
   JOIN tasks t ON p.task_id=t.id
   JOIN users u ON p.candidate_id=u.id
   WHERE p.employer_id=$uid AND p.status='released'
   ORDER BY p.created_at DESC");

$total_spent = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE employer_id=$uid AND status='released'"))['t'];
$month_spent = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COALESCE(SUM(amount),0) as t FROM payments WHERE employer_id=$uid AND status='released' AND MONTH(created_at)=MONTH(NOW())"))['t'];
$tasks_completed = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COUNT(*) as t FROM payments WHERE employer_id=$uid AND status='released'"))['t'];

$page_title='Payment History';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">
  <aside class="sidebar">
    <div class="sidebar-card">
      <a href="employer_dashboard.php" class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
      <a href="post_task.php" class="sidebar-link"><span class="si">➕</span>Post Task</a>
      <a href="manage_tasks.php" class="sidebar-link"><span class="si">📋</span>My Tasks</a>
      <a href="task_invite.php" class="sidebar-link"><span class="si">📨</span>Invite Students</a>
      <a href="task_analytics.php" class="sidebar-link"><span class="si">📊</span>Analytics</a>
      <a href="messages.php" class="sidebar-link"><span class="si">💬</span>Messages</a>
      <a href="payment_history.php" class="sidebar-link active"><span class="si">💳</span>Payments</a>
      <a href="notifications.php" class="sidebar-link"><span class="si">🔔</span>Notifications</a>
      <a href="notification_settings.php" class="sidebar-link"><span class="si">⚙️</span>Settings</a>
      <a href="profile.php" class="sidebar-link"><span class="si">👤</span>Profile</a>
      <a href="logout.php" class="sidebar-link" style="color:var(--rose)"><span class="si">🚪</span>Logout</a>
    </div>
  </aside>
  <main class="main-content">
    <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700;margin-bottom:24px">💳 Payment History</h1>

    <div class="grid-3" style="margin-bottom:28px">
      <?php foreach([
        ['💰','Total Spent','₹'.number_format($total_spent),'var(--rose)'],
        ['📅','This Month','₹'.number_format($month_spent),'var(--amber)'],
        ['✅','Completed Tasks',$tasks_completed,'var(--emerald)'],
      ] as [$icon,$label,$val,$color]): ?>
      <div class="stat-box" style="text-align:left">
        <div style="font-size:1.5rem;margin-bottom:8px"><?= $icon ?></div>
        <div style="font-size:1.8rem;font-weight:800;color:<?= $color ?>;font-family:var(--fh)"><?= $val ?></div>
        <div style="color:var(--t3);font-size:13px;margin-top:2px"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="glass-dark" style="padding:24px;border-radius:var(--r)">
      <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:20px">All Payments Released</h3>
      <?php if(mysqli_num_rows($payments)===0): ?>
        <div style="text-align:center;padding:50px;color:var(--t3)">
          <div style="font-size:3rem;margin-bottom:12px">💳</div>
          <div style="font-weight:600">No payments yet</div>
          <a href="post_task.php" class="btn btn-brand" style="margin-top:16px">Post a Task →</a>
        </div>
      <?php else: ?>
        <div style="overflow-x:auto">
          <table class="tbl">
            <thead><tr><th>Task</th><th>Paid To</th><th>University</th><th>Amount</th><th>Date</th></tr></thead>
            <tbody>
              <?php while($p=$payments->fetch_assoc()): ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars(substr($p['title'],0,45)) ?><?= strlen($p['title'])>45?'…':'' ?></td>
                <td>
                  <div style="font-weight:600"><?= htmlspecialchars($p['candidate_name']) ?></div>
                </td>
                <td style="color:var(--t3);font-size:13px"><?= htmlspecialchars($p['university']??'—') ?></td>
                <td style="color:var(--rose);font-weight:800;font-size:1rem">₹<?= number_format($p['amount']) ?></td>
                <td style="color:var(--t3);font-size:13px"><?= date('d M Y',strtotime($p['created_at'])) ?></td>
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
