<?php
session_start();
include 'db.php';
requireRole('admin');

// Actions
if(isset($_GET['ban'])){
  $bid=(int)$_GET['ban'];
  $conn->query("UPDATE users SET is_banned=1 WHERE id=$bid AND role!='admin'");
  $_SESSION['flash_success']='Account suspended. User has been locked out.'; header("Location: admin_dashboard.php"); exit();
}
if(isset($_GET['unban'])){
  $bid=(int)$_GET['unban'];
  $conn->query("UPDATE users SET is_banned=0 WHERE id=$bid");
  $_SESSION['flash_success']='Account reinstated. User can log in again.'; header("Location: admin_dashboard.php"); exit();
}
if(isset($_GET['delete_task'])){
  $tid=(int)$_GET['delete_task'];
  $conn->query("DELETE FROM tasks WHERE id=$tid");
  $_SESSION['flash_success']='Task deleted.'; header("Location: admin_dashboard.php"); exit();
}

// Stats
$stats=[
  'users'     => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM users WHERE role!='admin'"))['c'],
  'students'  => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM users WHERE role='candidate'"))['c'],
  'employers' => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM users WHERE role='employer'"))['c'],
  'tasks'     => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM tasks"))['c'],
  'open'      => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM tasks WHERE status='open'"))['c'],
  'paid'      => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as c FROM payments WHERE status='released'"))['c'],
  'bids'      => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM applications"))['c'],
  'banned'    => mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM users WHERE is_banned=1"))['c'],
];

// Users list
$page = max(1,intval($_GET['p']??1));
$per  = 20;
$offset=($page-1)*$per;
$search=trim($_GET['q']??'');
$user_where = "role!='admin'";
if($search) $user_where.=" AND (name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR email LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
$users=mysqli_query($conn,"SELECT * FROM users WHERE $user_where ORDER BY created_at DESC LIMIT $per OFFSET $offset");
$total_pages=ceil(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM users WHERE $user_where"))['c']/$per);

// Recent tasks
$tasks=mysqli_query($conn,"SELECT t.*,u.name as en FROM tasks t JOIN users u ON t.employer_id=u.id ORDER BY t.created_at DESC LIMIT 15");

$page_title='Admin Dashboard';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">
  <aside class="sidebar">
    <div class="sidebar-card">
      <div style="font-size:11px;font-weight:700;color:var(--rose);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">🔑 ADMIN PANEL</div>
      <a href="#stats"    class="sidebar-link active"><span class="si">📊</span>Overview</a>
      <a href="#users"    class="sidebar-link"><span class="si">👥</span>Users</a>
      <a href="#tasks"    class="sidebar-link"><span class="si">📋</span>Tasks</a>
    </div>
    <a href="logout.php" class="btn btn-danger btn-full">Logout</a>
  </aside>

  <main class="main-content">
    <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700;margin-bottom:28px" id="stats">🔑 Admin Dashboard</h1>

    <!-- Stats Grid -->
    <div class="grid-4" style="margin-bottom:32px">
      <?php foreach([
        ['👥','Total Users',$stats['users'],'var(--violet2)'],
        ['🎓','Students',$stats['students'],'var(--cyan)'],
        ['🏢','Employers',$stats['employers'],'var(--amber)'],
        ['📋','Total Tasks',$stats['tasks'],'var(--emerald)'],
        ['🟢','Open Tasks',$stats['open'],'var(--emerald)'],
        ['💰','Total Paid','₹'.number_format($stats['paid']),'var(--amber)'],
        ['📝','Total Bids',$stats['bids'],'var(--violet2)'],
        ['🚫','Banned Users',$stats['banned'],'var(--rose)'],
      ] as [$icon,$label,$val,$color]): ?>
      <div class="stat-box" style="text-align:left">
        <div style="font-size:1.3rem;margin-bottom:6px"><?= $icon ?></div>
        <div style="font-size:1.4rem;font-weight:800;color:<?= $color ?>;font-family:var(--fh)"><?= $val ?></div>
        <div style="color:var(--t3);font-size:12px;margin-top:2px"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Users Table -->
    <div class="glass-dark" style="padding:24px;border-radius:var(--r);margin-bottom:28px" id="users">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <h3 style="font-family:var(--fh);font-weight:700">👥 Users</h3>
        <form method="GET" style="display:flex;gap:8px">
          <input type="text" name="q" class="finput" style="width:220px;padding:8px 14px" placeholder="Search users…" value="<?= htmlspecialchars($search) ?>">
          <button type="submit" class="btn btn-brand btn-sm">Search</button>
          <?php if($search): ?><a href="admin_dashboard.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
        </form>
      </div>

      <div style="overflow-x:auto">
        <table class="tbl">
          <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php while($u=$users->fetch_assoc()): ?>
            <tr>
              <td style="color:var(--t3)">#<?= $u['id'] ?></td>
              <td style="font-weight:600"><?= htmlspecialchars($u['name']) ?></td>
              <td style="color:var(--t3);font-size:13px"><?= htmlspecialchars($u['email']) ?></td>
              <td><span class="tag <?= $u['role']==='employer'?'tag-c':'tag-v' ?>"><?= ucfirst($u['role']) ?></span></td>
              <td style="color:var(--t3);font-size:13px"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
              <td><?php if($u['is_banned']): ?><span class="tag tag-r">Banned</span><?php else: ?><span class="tag tag-g">Active</span><?php endif; ?></td>
              <td style="display:flex;gap:6px">
                <a href="view_student_profile.php?id=<?= $u['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                <?php if($u['is_banned']): ?>
                  <a href="admin_dashboard.php?unban=<?= $u['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Reinstate this user? They will regain full access.')">✅ Reinstate</a>
                <?php else: ?>
                  <a href="admin_dashboard.php?ban=<?= $u['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Suspend this account? The user will be locked out.')">🚫 Suspend</a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if($total_pages>1): ?>
      <div style="display:flex;gap:8px;justify-content:center;margin-top:20px">
        <?php for($i=1;$i<=$total_pages;$i++): ?>
          <a href="?p=<?= $i ?><?= $search?"&q=".urlencode($search):'' ?>" class="btn <?= $i===$page?'btn-brand':'btn-ghost' ?> btn-sm"><?= $i ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Tasks Table -->
    <div class="glass-dark" style="padding:24px;border-radius:var(--r)" id="tasks">
      <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:20px">📋 Recent Tasks</h3>
      <div style="overflow-x:auto">
        <table class="tbl">
          <thead><tr><th>Title</th><th>Employer</th><th>Budget</th><th>Category</th><th>Status</th><th>Posted</th><th>Actions</th></tr></thead>
          <tbody>
            <?php while($t=$tasks->fetch_assoc()):
              $s_cls=['open'=>'tag-g','assigned'=>'tag-a','completed'=>'tag-c'][$t['status']]??'tag-v';
            ?>
            <tr>
              <td style="font-weight:600"><?= htmlspecialchars(substr($t['title'],0,40)) ?><?= strlen($t['title'])>40?'…':'' ?></td>
              <td style="color:var(--t3)"><?= htmlspecialchars($t['en']) ?></td>
              <td style="color:var(--emerald);font-weight:700">₹<?= number_format($t['price']) ?></td>
              <td><span class="tag tag-c" style="font-size:11px"><?= htmlspecialchars($t['category']) ?></span></td>
              <td><span class="tag <?= $s_cls ?>"><?= ucfirst($t['status']) ?></span></td>
              <td style="color:var(--t3);font-size:13px"><?= date('d M',strtotime($t['created_at'])) ?></td>
              <td>
                <a href="task_view.php?id=<?= $t['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                <a href="admin_dashboard.php?delete_task=<?= $t['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this task?')">Delete</a>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- DISPUTES SECTION -->
    <?php
    // Handle dispute resolve
    if(isset($_GET['resolve_dispute'])){
      $did=(int)$_GET['resolve_dispute'];
      $note=esc($conn,$_GET['note']??'Resolved by admin.');
      $conn->query("UPDATE disputes SET status='resolved',admin_note='$note',resolved_at=NOW() WHERE id=$did");
      $_SESSION['flash_success']='Dispute resolved.'; header("Location: admin_dashboard.php#disputes"); exit();
    }
    if(isset($_GET['dismiss_dispute'])){
      $did=(int)$_GET['dismiss_dispute'];
      $conn->query("UPDATE disputes SET status='dismissed',resolved_at=NOW() WHERE id=$did");
      $_SESSION['flash_success']='Dispute dismissed.'; header("Location: admin_dashboard.php#disputes"); exit();
    }
    $disputes=mysqli_query($conn,
      "SELECT d.*,t.title as task_title,u.name as raiser_name,u.role as raiser_role
       FROM disputes d JOIN tasks t ON t.id=d.task_id JOIN users u ON u.id=d.raised_by
       ORDER BY FIELD(d.status,'open','reviewing','resolved','dismissed'), d.created_at DESC LIMIT 30");
    $open_disputes=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM disputes WHERE status='open'"))['c'];
    ?>
    <div class="glass-dark" style="padding:24px;border-radius:var(--r);margin-top:28px" id="disputes">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
        <h3 style="font-family:var(--fh);font-weight:700">⚠️ Disputes</h3>
        <?php if($open_disputes>0): ?><span class="badge" style="font-size:12px;width:auto;border-radius:12px;padding:2px 10px"><?= $open_disputes ?> open</span><?php endif; ?>
      </div>
      <?php if(mysqli_num_rows($disputes)===0): ?>
        <p style="color:var(--t3);font-size:14px">No disputes raised yet.</p>
      <?php else: ?>
        <?php while($d=$disputes->fetch_assoc()):
          $sc=['open'=>'tag-r','reviewing'=>'tag-a','resolved'=>'tag-g','dismissed'=>'tag-v'][$d['status']]??'tag-v';
        ?>
        <div style="border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:14px;background:var(--surface)">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:10px">
            <div>
              <div style="font-weight:700;font-size:14px"><?= htmlspecialchars($d['task_title']) ?></div>
              <div style="color:var(--t3);font-size:12px">
                Raised by: <strong><?= htmlspecialchars($d['raiser_name']) ?></strong> (<?= $d['raiser_role'] ?>)
                &nbsp;·&nbsp; <?= date('d M Y',strtotime($d['created_at'])) ?>
              </div>
              <div style="margin-top:5px"><span class="tag tag-a" style="font-size:11px"><?= htmlspecialchars($d['reason']) ?></span></div>
            </div>
            <span class="tag <?= $sc ?>"><?= ucfirst($d['status']) ?></span>
          </div>
          <p style="color:var(--t2);font-size:13.5px;margin-bottom:12px;line-height:1.6"><?= htmlspecialchars(substr($d['description'],0,300)) ?></p>
          <?php if($d['status']==='open' || $d['status']==='reviewing'): ?>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <form method="GET" style="display:flex;gap:8px;flex:1;min-width:250px">
              <input type="hidden" name="resolve_dispute" value="<?= $d['id'] ?>">
              <input type="text" name="note" class="finput" style="flex:1;padding:7px 12px;font-size:12.5px" placeholder="Admin resolution note…">
              <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Mark as resolved?')">✅ Resolve</button>
            </form>
            <a href="admin_dashboard.php?dismiss_dispute=<?= $d['id'] ?>" class="btn btn-ghost btn-sm" onclick="return confirm('Dismiss dispute?')">Dismiss</a>
          </div>
          <?php elseif($d['admin_note']): ?>
            <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:8px;padding:10px;font-size:13px;color:var(--emerald)">
              ✅ Admin note: <?= htmlspecialchars($d['admin_note']) ?>
            </div>
          <?php endif; ?>
        </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>

  </main>
</div>
</div>
<?php include 'includes/footer.php'; ?>
