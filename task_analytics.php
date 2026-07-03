<?php
session_start();
include 'db.php';
requireRole('employer');

$uid  = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$uid"));

// ── Overview stats ──────────────────────────────────────────────────────────
$total_tasks     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE employer_id=$uid"))['c'];
$open_tasks      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE employer_id=$uid AND status='open'"))['c'];
$completed_tasks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE employer_id=$uid AND status='completed'"))['c'];
$cancelled_tasks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE employer_id=$uid AND status='cancelled'"))['c'];
$assigned_tasks  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM tasks WHERE employer_id=$uid AND status='assigned'"))['c'];

$total_apps    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM applications a JOIN tasks t ON a.task_id=t.id WHERE t.employer_id=$uid"))['c'];
$total_spent   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) as c FROM payments WHERE employer_id=$uid AND status='released'"))['c'];
$avg_bids      = $total_tasks > 0 ? round($total_apps / $total_tasks, 1) : 0;
$completion_rate = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

// ── Category breakdown ──────────────────────────────────────────────────────
$cat_res = mysqli_query($conn,
  "SELECT category, COUNT(*) as cnt, COALESCE(SUM(price),0) as total_budget
   FROM tasks WHERE employer_id=$uid
   GROUP BY category ORDER BY cnt DESC LIMIT 8");
$categories = [];
while ($r = $cat_res->fetch_assoc()) $categories[] = $r;

// ── Monthly task posts (last 6 months) ────────────────────────────────────
$monthly_res = mysqli_query($conn,
  "SELECT DATE_FORMAT(created_at,'%b %Y') as month,
          DATE_FORMAT(created_at,'%Y-%m') as sort_key,
          COUNT(*) as cnt
   FROM tasks WHERE employer_id=$uid
     AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
   GROUP BY month, sort_key ORDER BY sort_key ASC");
$monthly = [];
while ($r = $monthly_res->fetch_assoc()) $monthly[] = $r;

// ── Monthly spend (last 6 months) ─────────────────────────────────────────
$spend_res = mysqli_query($conn,
  "SELECT DATE_FORMAT(created_at,'%b %Y') as month,
          DATE_FORMAT(created_at,'%Y-%m') as sort_key,
          COALESCE(SUM(amount),0) as total
   FROM payments WHERE employer_id=$uid AND status='released'
     AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
   GROUP BY month, sort_key ORDER BY sort_key ASC");
$monthly_spend = [];
while ($r = $spend_res->fetch_assoc()) $monthly_spend[] = $r;

// ── Top candidates hired ───────────────────────────────────────────────────
$top_candidates = mysqli_query($conn,
  "SELECT u.name, u.university,
          COUNT(a.id) as tasks_done,
          COALESCE(SUM(p.amount),0) as total_paid
   FROM applications a
   JOIN tasks t ON t.id=a.task_id
   JOIN users u ON u.id=a.candidate_id
   LEFT JOIN payments p ON p.application_id=a.id AND p.status='released'
   WHERE t.employer_id=$uid AND a.status='completed'
   GROUP BY a.candidate_id, u.name, u.university
   ORDER BY tasks_done DESC, total_paid DESC LIMIT 5");

// ── Recent activity ────────────────────────────────────────────────────────
$recent = mysqli_query($conn,
  "SELECT t.title, t.status, t.created_at, t.price,
          (SELECT COUNT(*) FROM applications WHERE task_id=t.id) as bids
   FROM tasks t WHERE t.employer_id=$uid
   ORDER BY t.created_at DESC LIMIT 5");

// ── Build chart data ───────────────────────────────────────────────────────
$chart_labels = array_column($monthly, 'month');
$chart_counts = array_column($monthly, 'cnt');
$spend_labels = array_column($monthly_spend, 'month');
$spend_totals = array_column($monthly_spend, 'total');

// Category chart data
$cat_labels = array_column($categories, 'category');
$cat_counts = array_column($categories, 'cnt');

$page_title = 'Analytics';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div class="dash-wrap">
    <aside class="sidebar">
      <div class="sidebar-card">
        <div style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:8px 0">
          <div class="avatar avatar-xl"><?= strtoupper(substr($user['name'],0,1)) ?></div>
          <div style="font-weight:700"><?= htmlspecialchars($user['name']) ?></div>
          <div style="color:var(--t3);font-size:12px"><?= htmlspecialchars($user['company_name']??'') ?></div>
        </div>
      </div>
      <div class="sidebar-card">
        <a href="employer_dashboard.php"    class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
        <a href="post_task.php"             class="sidebar-link"><span class="si">➕</span>Post Task</a>
        <a href="manage_tasks.php"          class="sidebar-link"><span class="si">📋</span>My Tasks</a>
        <a href="task_invite.php"           class="sidebar-link"><span class="si">📨</span>Invite Students</a>
        <a href="task_analytics.php"        class="sidebar-link active"><span class="si">📊</span>Analytics</a>
        <a href="messages.php"              class="sidebar-link"><span class="si">💬</span>Messages</a>
        <a href="payment_history.php"       class="sidebar-link"><span class="si">💳</span>Payments</a>
        <a href="notifications.php"         class="sidebar-link"><span class="si">🔔</span>Notifications</a>
        <a href="notification_settings.php" class="sidebar-link"><span class="si">⚙️</span>Settings</a>
        <a href="profile.php"               class="sidebar-link"><span class="si">👤</span>Profile</a>
      <a href="logout.php"                class="sidebar-link" style="color:var(--rose)"><span class="si">🚪</span>Logout</a>
      </div>
      <a href="post_task.php" class="btn btn-brand btn-full">+ Post New Task</a>
    </aside>

    <main class="main-content">
      <!-- Header -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
        <div>
          <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700">📊 Analytics</h1>
          <p style="color:var(--t3)">Track your hiring performance and spend</p>
        </div>
        <a href="post_task.php" class="btn btn-brand">+ Post Task</a>
      </div>

      <!-- KPI Cards -->
      <div class="grid-4" style="margin-bottom:28px">
        <?php foreach([
          ['📋','Tasks Posted',$total_tasks,'var(--violet2)'],
          ['✅','Completed',$completed_tasks,'var(--emerald)'],
          ['📝','Total Bids',$total_apps,'var(--cyan)'],
          ['💰','Total Spent','₹'.number_format($total_spent),'var(--amber)'],
        ] as [$icon,$label,$val,$color]): ?>
        <div class="stat-box" style="text-align:left">
          <div style="font-size:1.5rem;margin-bottom:8px"><?= $icon ?></div>
          <div style="font-size:1.5rem;font-weight:800;color:<?= $color ?>;font-family:var(--fh)"><?= $val ?></div>
          <div style="color:var(--t3);font-size:13px;margin-top:2px"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Secondary KPIs -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px">
        <div class="card" style="padding:20px;text-align:center">
          <div style="font-size:2rem;font-weight:800;color:var(--violet2);font-family:var(--fh)"><?= $avg_bids ?></div>
          <div style="color:var(--t3);font-size:13px;margin-top:4px">Avg Bids / Task</div>
        </div>
        <div class="card" style="padding:20px;text-align:center">
          <div style="font-size:2rem;font-weight:800;color:var(--emerald);font-family:var(--fh)"><?= $completion_rate ?>%</div>
          <div style="color:var(--t3);font-size:13px;margin-top:4px">Completion Rate</div>
        </div>
        <div class="card" style="padding:20px;text-align:center">
          <div style="font-size:2rem;font-weight:800;color:var(--cyan);font-family:var(--fh)"><?= $assigned_tasks ?></div>
          <div style="color:var(--t3);font-size:13px;margin-top:4px">In Progress</div>
        </div>
      </div>

      <!-- Charts Row -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px">

        <!-- Tasks Posted Monthly -->
        <div class="card" style="padding:24px">
          <h3 style="font-family:var(--fh);font-size:1rem;font-weight:700;margin-bottom:20px">📅 Tasks Posted (Last 6 Months)</h3>
          <?php if(empty($monthly)): ?>
            <div style="text-align:center;padding:40px;color:var(--t3)">No data yet</div>
          <?php else: ?>
            <canvas id="taskChart" height="200"></canvas>
          <?php endif; ?>
        </div>

        <!-- Monthly Spend -->
        <div class="card" style="padding:24px">
          <h3 style="font-family:var(--fh);font-size:1rem;font-weight:700;margin-bottom:20px">💸 Monthly Spend (Last 6 Months)</h3>
          <?php if(empty($monthly_spend)): ?>
            <div style="text-align:center;padding:40px;color:var(--t3)">No payments released yet</div>
          <?php else: ?>
            <canvas id="spendChart" height="200"></canvas>
          <?php endif; ?>
        </div>
      </div>

      <!-- Category Breakdown + Top Candidates -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px">

        <!-- Category Breakdown -->
        <div class="card" style="padding:24px">
          <h3 style="font-family:var(--fh);font-size:1rem;font-weight:700;margin-bottom:20px">🏷️ Tasks by Category</h3>
          <?php if(empty($categories)): ?>
            <div style="text-align:center;padding:40px;color:var(--t3)">No tasks yet</div>
          <?php else: ?>
            <?php
            $max_cnt = max(array_column($categories,'cnt'));
            $colors = ['var(--violet2)','var(--cyan)','var(--emerald)','var(--amber)','#f43f7a','#8b5cf6','#06b6d4','#f59e0b'];
            foreach($categories as $i=>$cat):
              $pct = $max_cnt > 0 ? round(($cat['cnt']/$max_cnt)*100) : 0;
              $col = $colors[$i % count($colors)];
            ?>
            <div style="margin-bottom:14px">
              <div style="display:flex;justify-content:space-between;margin-bottom:5px">
                <span style="font-size:13px;font-weight:600"><?= htmlspecialchars($cat['category']) ?></span>
                <span style="font-size:13px;color:var(--t3)"><?= $cat['cnt'] ?> task<?= $cat['cnt']!=1?'s':'' ?></span>
              </div>
              <div style="background:rgba(255,255,255,0.06);border-radius:6px;height:8px;overflow:hidden">
                <div style="width:<?= $pct ?>%;height:100%;background:<?= $col ?>;border-radius:6px;transition:.4s"></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Top Candidates -->
        <div class="card" style="padding:24px">
          <h3 style="font-family:var(--fh);font-size:1rem;font-weight:700;margin-bottom:20px">🏅 Top Candidates Hired</h3>
          <?php
          $rows = [];
          while($r = $top_candidates->fetch_assoc()) $rows[] = $r;
          if(empty($rows)):
          ?>
            <div style="text-align:center;padding:40px;color:var(--t3)">
              <div style="font-size:2.5rem;margin-bottom:10px">🎓</div>
              <div>No completed tasks yet</div>
            </div>
          <?php else: ?>
            <?php foreach($rows as $i=>$c): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 0;<?= $i>0?'border-top:1px solid rgba(255,255,255,0.06)':'' ?>">
              <div class="avatar" style="width:36px;height:36px;font-size:14px;flex-shrink:0"><?= strtoupper(substr($c['name'],0,1)) ?></div>
              <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($c['name']) ?></div>
                <div style="color:var(--t3);font-size:12px"><?= htmlspecialchars($c['university']??'') ?></div>
              </div>
              <div style="text-align:right;flex-shrink:0">
                <div style="font-weight:700;color:var(--emerald);font-size:14px">₹<?= number_format($c['total_paid']) ?></div>
                <div style="color:var(--t3);font-size:12px"><?= $c['tasks_done'] ?> task<?= $c['tasks_done']!=1?'s':'' ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Task Status Summary -->
      <div class="card" style="padding:24px;margin-bottom:28px">
        <h3 style="font-family:var(--fh);font-size:1rem;font-weight:700;margin-bottom:20px">📊 Task Status Overview</h3>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
          <?php foreach([
            ['🟢','Open',$open_tasks,'var(--emerald)'],
            ['🔵','Assigned',$assigned_tasks,'var(--cyan)'],
            ['✅','Completed',$completed_tasks,'var(--violet2)'],
            ['❌','Cancelled',$cancelled_tasks,'#f43f7a'],
          ] as [$icon,$label,$cnt,$col]):
            $pct2 = $total_tasks > 0 ? round(($cnt/$total_tasks)*100) : 0;
          ?>
          <div style="text-align:center;padding:16px;background:rgba(255,255,255,0.03);border-radius:12px;border:1px solid rgba(255,255,255,0.06)">
            <div style="font-size:1.6rem;margin-bottom:6px"><?= $icon ?></div>
            <div style="font-size:1.4rem;font-weight:800;color:<?= $col ?>;font-family:var(--fh)"><?= $cnt ?></div>
            <div style="color:var(--t3);font-size:12px;margin-top:2px"><?= $label ?></div>
            <div style="font-size:11px;color:<?= $col ?>;margin-top:4px;font-weight:600"><?= $pct2 ?>%</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Recent Tasks -->
      <div class="card" style="padding:24px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h3 style="font-family:var(--fh);font-size:1rem;font-weight:700">📋 Recent Tasks</h3>
          <a href="manage_tasks.php" style="color:var(--violet2);font-size:13px">View all →</a>
        </div>
        <?php if(!$recent || mysqli_num_rows($recent)===0): ?>
          <div style="text-align:center;padding:40px;color:var(--t3)">No tasks yet</div>
        <?php else: ?>
        <div style="overflow-x:auto">
          <table class="tbl">
            <thead><tr><th>Task</th><th>Budget</th><th>Bids</th><th>Status</th><th>Posted</th></tr></thead>
            <tbody>
              <?php while($t=$recent->fetch_assoc()):
                $scls=['open'=>'tag-g','assigned'=>'tag-a','completed'=>'tag-c','cancelled'=>'tag-r'][$t['status']]??'tag-v';
              ?>
              <tr>
                <td style="font-weight:600"><?= htmlspecialchars(substr($t['title'],0,50)) ?><?= strlen($t['title'])>50?'…':'' ?></td>
                <td style="color:var(--emerald);font-weight:700">₹<?= number_format($t['price']) ?></td>
                <td style="font-weight:600"><?= $t['bids'] ?></td>
                <td><span class="tag <?= $scls ?>"><?= ucfirst($t['status']) ?></span></td>
                <td style="color:var(--t3);font-size:13px"><?= date('d M Y',strtotime($t['created_at'])) ?></td>
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

<!-- Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#8b8aab';
Chart.defaults.font.family = 'Inter, sans-serif';

const gridColor = 'rgba(255,255,255,0.06)';

<?php if(!empty($monthly)): ?>
new Chart(document.getElementById('taskChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      label: 'Tasks Posted',
      data: <?= json_encode($chart_counts) ?>,
      backgroundColor: 'rgba(124,92,252,0.7)',
      borderColor: '#7c5cfc',
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: gridColor } },
      y: { grid: { color: gridColor }, ticks: { stepSize: 1, precision: 0 }, beginAtZero: true }
    }
  }
});
<?php endif; ?>

<?php if(!empty($monthly_spend)): ?>
new Chart(document.getElementById('spendChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($spend_labels) ?>,
    datasets: [{
      label: 'Amount Spent (₹)',
      data: <?= json_encode($spend_totals) ?>,
      borderColor: '#10b981',
      backgroundColor: 'rgba(16,185,129,0.12)',
      borderWidth: 2.5,
      pointBackgroundColor: '#10b981',
      pointRadius: 5,
      tension: 0.35,
      fill: true,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: gridColor } },
      y: {
        grid: { color: gridColor },
        beginAtZero: true,
        ticks: {
          callback: v => '₹' + v.toLocaleString('en-IN')
        }
      }
    }
  }
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
