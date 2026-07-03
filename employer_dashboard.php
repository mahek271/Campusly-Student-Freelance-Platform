<?php
session_start();
include 'db.php';
requireRole('employer');

$uid  = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id=$uid"));

$total_tasks   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM tasks WHERE employer_id=$uid"))['c'];
$open_tasks    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM tasks WHERE employer_id=$uid AND status='open'"))['c'];
$total_apps    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM applications a JOIN tasks t ON a.task_id=t.id WHERE t.employer_id=$uid"))['c'];
$total_spent   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as c FROM payments WHERE employer_id=$uid AND status='released'"))['c'];

$my_tasks = mysqli_query($conn,
  "SELECT t.*, (SELECT COUNT(*) FROM applications WHERE task_id=t.id) as bid_count
   FROM tasks t WHERE t.employer_id=$uid ORDER BY t.created_at DESC");

$page_title='Employer Dashboard';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div class="dash-wrap">
    <aside class="sidebar">
      <div class="sidebar-card">
        <div style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:8px 0">
          <?php if(!empty($user['avatar']) && file_exists($user['avatar'])): ?>
            <img src="<?= htmlspecialchars($user['avatar']) ?>"
                 style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid var(--violet2);flex-shrink:0"
                 alt="Profile picture">
          <?php else: ?>
            <div class="avatar avatar-xl"><?= strtoupper(substr($user['name'],0,1)) ?></div>
          <?php endif; ?>
          <div style="font-weight:700"><?= htmlspecialchars($user['name']) ?></div>
          <div style="color:var(--t3);font-size:12px"><?= htmlspecialchars($user['company_name']??'') ?></div>
        </div>
      </div>
      <div class="sidebar-card">
        <a href="employer_dashboard.php"    class="sidebar-link active"><span class="si">🏠</span>Dashboard</a>
        <a href="post_task.php"             class="sidebar-link"><span class="si">➕</span>Post Task</a>
        <a href="manage_tasks.php"          class="sidebar-link"><span class="si">📋</span>My Tasks</a>
        <a href="task_invite.php"           class="sidebar-link"><span class="si">📨</span>Invite Students</a>
        <a href="task_analytics.php"        class="sidebar-link"><span class="si">📊</span>Analytics</a>
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
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
        <div>
          <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700">🏢 Employer Dashboard</h1>
          <p style="color:var(--t3)">Manage your tasks and talent</p>
        </div>
        <a href="post_task.php" class="btn btn-brand">+ Post Task</a>
      </div>

      <!-- Stats -->
      <div class="grid-4" style="margin-bottom:28px">
        <?php foreach([
          ['📋','Tasks Posted',$total_tasks,'var(--violet2)'],
          ['🟢','Open Tasks',$open_tasks,'var(--emerald)'],
          ['📝','Total Bids',$total_apps,'var(--cyan)'],
          ['💰','Amount Spent','₹'.number_format($total_spent),'var(--amber)'],
        ] as [$icon,$label,$val,$color]): ?>
        <div class="stat-box" style="text-align:left">
          <div style="font-size:1.5rem;margin-bottom:8px"><?= $icon ?></div>
          <div style="font-size:1.5rem;font-weight:800;color:<?= $color ?>;font-family:var(--fh)"><?= $val ?></div>
          <div style="color:var(--t3);font-size:13px;margin-top:2px"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Tasks List -->
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h2 style="font-family:var(--fh);font-size:1.2rem;font-weight:700">📋 My Tasks</h2>
          <a href="manage_tasks.php" style="color:var(--violet2);font-size:13.5px">Manage all →</a>
        </div>
        <?php if(mysqli_num_rows($my_tasks)===0): ?>
          <div style="text-align:center;padding:60px;color:var(--t3)">
            <div style="font-size:3rem;margin-bottom:14px">📋</div>
            <div style="font-weight:600;margin-bottom:10px">No tasks posted yet</div>
            <a href="post_task.php" class="btn btn-brand">Post Your First Task</a>
          </div>
        <?php else: ?>
          <div style="overflow-x:auto">
            <table class="tbl">
              <thead><tr><th>Task</th><th>Budget</th><th>Bids</th><th>Status</th><th>Posted</th><th>Actions</th></tr></thead>
              <tbody>
                <?php while($t=$my_tasks->fetch_assoc()):
                  $status_cls=['open'=>'tag-g','assigned'=>'tag-a','completed'=>'tag-c','cancelled'=>'tag-r'][$t['status']]??'tag-v';
                ?>
                <tr>
                  <td>
                    <div style="font-weight:600"><?= htmlspecialchars(substr($t['title'],0,45)) ?><?= strlen($t['title'])>45?'…':'' ?></div>
                    <div style="color:var(--t3);font-size:12px"><?= htmlspecialchars($t['category']) ?></div>
                  </td>
                  <td style="color:var(--emerald);font-weight:700">₹<?= number_format($t['price']) ?></td>
                  <td style="font-weight:600"><?= $t['bid_count'] ?></td>
                  <td><span class="tag <?= $status_cls ?>"><?= ucfirst($t['status']) ?></span></td>
                  <td style="color:var(--t3);font-size:13px"><?= date('d M Y',strtotime($t['created_at'])) ?></td>
                  <td style="display:flex;gap:6px">
                    <a href="view_applicants.php?id=<?= $t['id'] ?>" class="btn btn-outline btn-sm">👥 Bids (<?= $t['bid_count'] ?>)</a>
                    <?php if($t['status']==='open'): ?>
                      <a href="javascript:confirmAction('Close this task?','close_task.php?id=<?= $t['id'] ?>')" class="btn btn-danger btn-sm">Close</a>
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
