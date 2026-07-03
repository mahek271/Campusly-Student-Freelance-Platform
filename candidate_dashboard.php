<?php
session_start();
include 'db.php';
requireRole('candidate');

$uid = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id=$uid"));

// Stats
$total_apps    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM applications WHERE candidate_id=$uid"))['c'];
$won_apps      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM applications WHERE candidate_id=$uid AND status='selected'"))['c'];
$completed     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM applications WHERE candidate_id=$uid AND status='completed'"))['c'];
$total_earned  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as c FROM payments WHERE candidate_id=$uid AND status='released'"))['c'];
$avg_rating_r  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(AVG(rating),0) as c FROM reviews WHERE reviewee_id=$uid"));
$avg_rating    = round($avg_rating_r['c'],1);

// Recent applications
$my_apps = mysqli_query($conn,
  "SELECT a.*, t.title, t.price, t.status as task_status, u.company_name, u.name as employer_name
   FROM applications a
   JOIN tasks t ON a.task_id=t.id
   JOIN users u ON t.employer_id=u.id
   WHERE a.candidate_id=$uid
   ORDER BY a.applied_at DESC LIMIT 6");

// Pending work (selected but not completed)
$active_work = mysqli_query($conn,
  "SELECT a.*, t.title, t.deadline, u.name as employer_name, u.email as employer_email
   FROM applications a
   JOIN tasks t ON a.task_id=t.id
   JOIN users u ON t.employer_id=u.id
   WHERE a.candidate_id=$uid AND a.status='selected'
   ORDER BY t.deadline ASC");

// Notifications
$notifs = mysqli_query($conn,"SELECT * FROM notifications WHERE user_id=$uid ORDER BY created_at DESC LIMIT 5");
mysqli_query($conn,"UPDATE notifications SET is_read=1 WHERE user_id=$uid");

$page_title='My Dashboard';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div class="dash-wrap">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-card">
        <div style="display:flex;flex-direction:column;align-items:center;gap:10px;padding:10px 0">
          <?php if(!empty($user['avatar']) && file_exists($user['avatar'])): ?>
            <img src="<?= htmlspecialchars($user['avatar']) ?>"
                 style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid var(--violet2);flex-shrink:0"
                 alt="Profile picture">
          <?php else: ?>
            <div class="avatar avatar-xl"><?= strtoupper(substr($user['name'],0,1)) ?></div>
          <?php endif; ?>
          <div style="font-weight:700;font-size:1rem"><?= htmlspecialchars($user['name']) ?></div>
          <div style="color:var(--t3);font-size:12px"><?= htmlspecialchars($user['university']??'') ?></div>
          <div style="display:flex;gap:6px;margin-top:4px">
            <?php for($i=1;$i<=5;$i++): ?>
              <span class="star <?= $i<=$avg_rating?'on':'off' ?>">★</span>
            <?php endfor; ?>
            <span style="font-size:12px;color:var(--t3);margin-left:4px">(<?= $avg_rating ?>)</span>
          </div>
        </div>
      </div>

      <div class="sidebar-card">
        <a href="candidate_dashboard.php" class="sidebar-link active"><span class="si">🏠</span>Dashboard</a>
        <a href="browse_tasks.php" class="sidebar-link"><span class="si">🔍</span>Browse Tasks</a>
        <a href="my_applications.php" class="sidebar-link"><span class="si">📋</span>My Bids</a>
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

      <a href="browse_tasks.php" class="btn btn-brand btn-full">🔍 Find Tasks</a>
    </aside>

    <!-- MAIN -->
    <main class="main-content">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px">
        <div>
          <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700">👋 Hey, <?= htmlspecialchars(explode(' ',$user['name'])[0]) ?>!</h1>
          <p style="color:var(--t3);margin-top:4px">Here's your activity overview</p>
        </div>
        <a href="browse_tasks.php" class="btn btn-brand">+ Find New Task</a>
      </div>

      <!-- Stats -->
      <div class="grid-4" style="margin-bottom:28px">
        <?php foreach([
          ['💰','Total Earned','₹'.number_format($total_earned),'var(--emerald)'],
          ['📋','Bids Placed',$total_apps,'var(--violet2)'],
          ['🏆','Tasks Won',$won_apps,'var(--amber)'],
          ['⭐','Avg Rating',$avg_rating.' / 5','var(--cyan)'],
        ] as [$icon,$label,$val,$color]): ?>
        <div class="stat-box" style="text-align:left">
          <div style="font-size:1.5rem;margin-bottom:8px"><?= $icon ?></div>
          <div style="font-size:1.5rem;font-weight:800;color:<?= $color ?>;font-family:var(--fh)"><?= $val ?></div>
          <div style="color:var(--t3);font-size:13px;margin-top:2px"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Active Work -->
      <?php if(mysqli_num_rows($active_work)>0): ?>
      <div style="margin-bottom:28px">
        <h2 style="font-family:var(--fh);font-size:1.2rem;font-weight:700;margin-bottom:16px">🔨 Active Work</h2>
        <?php while($w=$active_work->fetch_assoc()):
          $days_left = $w['deadline'] ? ceil((strtotime($w['deadline'])-time())/86400) : null;
        ?>
        <div class="glass-dark" style="padding:20px;border-radius:var(--r);margin-bottom:12px">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
            <div>
              <div style="font-weight:700"><?= htmlspecialchars($w['title']) ?></div>
              <div style="color:var(--t3);font-size:13px">by <?= htmlspecialchars($w['employer_name']) ?></div>
              <?php if($days_left!==null): ?>
                <div style="font-size:12px;color:<?= $days_left<=3?'var(--rose)':($days_left<=7?'var(--amber)':'var(--t3)') ?>;margin-top:4px">
                  ⏱ <?= $days_left ?> days remaining
                </div>
              <?php endif; ?>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
              <span class="tag tag-g">Selected</span>
              <a href="submit_task.php?app_id=<?= $w['id'] ?>" class="btn btn-brand btn-sm">📤 Submit Work</a>
            </div>
          </div>
          <?php if($w['submission_link']): ?>
            <div style="margin-top:12px;padding:10px;background:var(--surface);border-radius:8px;font-size:13px;color:var(--cyan)">
              ✅ Submitted: <a href="<?= htmlspecialchars($w['submission_link']) ?>" target="_blank" style="color:var(--cyan)"><?= htmlspecialchars($w['submission_link']) ?></a>
            </div>
          <?php endif; ?>
        </div>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>

      <!-- Recent Applications -->
      <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h2 style="font-family:var(--fh);font-size:1.2rem;font-weight:700">📋 Recent Bids</h2>
        </div>
        <?php if(mysqli_num_rows($my_apps)===0): ?>
          <div style="text-align:center;padding:50px;color:var(--t3)">
            <div style="font-size:3rem;margin-bottom:12px">🎯</div>
            <div style="font-weight:600;margin-bottom:8px">No bids yet</div>
            <a href="browse_tasks.php" class="btn btn-brand" style="margin-top:12px">Browse Tasks →</a>
          </div>
        <?php else: ?>
          <div style="overflow-x:auto">
            <table class="tbl">
              <thead><tr><th>Task</th><th>Your Bid</th><th>Status</th><th>Applied</th><th>Action</th></tr></thead>
              <tbody>
                <?php while($a=$my_apps->fetch_assoc()):
                  $status_cls=['pending'=>'tag-a','selected'=>'tag-g','rejected'=>'tag-r','completed'=>'tag-c'][$a['status']]??'tag-v';
                ?>
                <tr>
                  <td>
                    <div style="font-weight:600"><?= htmlspecialchars(substr($a['title'],0,40)) ?><?= strlen($a['title'])>40?'…':'' ?></div>
                    <div style="color:var(--t3);font-size:12px"><?= htmlspecialchars($a['company_name']?:$a['employer_name']) ?></div>
                  </td>
                  <td style="color:var(--emerald);font-weight:700">₹<?= number_format($a['bid_amount']) ?></td>
                  <td><span class="tag <?= $status_cls ?>"><?= ucfirst($a['status']) ?></span></td>
                  <td style="color:var(--t3);font-size:13px"><?= date('d M',strtotime($a['applied_at'])) ?></td>
                  <td>
                    <a href="task_view.php?id=<?= $a['task_id'] ?>" class="btn btn-ghost btn-sm">View</a>
                    <?php if($a['status']==='selected'): ?>
                      <a href="submit_task.php?app_id=<?= $a['id'] ?>" class="btn btn-brand btn-sm">Submit</a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- Notifications -->
      <?php if(mysqli_num_rows($notifs)>0): ?>
      <div style="margin-top:28px">
        <h2 style="font-family:var(--fh);font-size:1.2rem;font-weight:700;margin-bottom:16px">🔔 Recent Notifications</h2>
        <?php while($n=$notifs->fetch_assoc()): ?>
        <div style="background:var(--ink3);border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:10px;display:flex;align-items:center;justify-content:space-between">
          <span style="font-size:14px;color:var(--t2)"><?= htmlspecialchars($n['message']) ?></span>
          <?php if($n['link']): ?><a href="<?= $n['link'] ?>" class="btn btn-ghost btn-sm">View</a><?php endif; ?>
        </div>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>

      <!-- ── Smart Recommendations ── -->
      <div style="margin-top:28px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h2 style="font-family:var(--fh);font-size:1.2rem;font-weight:700">✨ Recommended for You</h2>
          <a href="browse_tasks.php" style="color:var(--violet2);font-size:13.5px">See all →</a>
        </div>
        <div id="rec-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px">
          <div style="color:var(--t3);font-size:13px;padding:20px 0" id="rec-loading">⏳ Finding tasks that match your skills…</div>
        </div>
      </div>

      <!-- ── Badges Teaser ── -->
      <div style="margin-top:28px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h2 style="font-family:var(--fh);font-size:1.2rem;font-weight:700">🎖️ Your Skill Badges</h2>
          <a href="skill_badges.php" style="color:var(--violet2);font-size:13.5px">View all →</a>
        </div>
        <div class="glass-dark" style="padding:18px 22px;border-radius:var(--r);display:flex;align-items:center;gap:16px;flex-wrap:wrap">
          <div style="font-size:2rem">🎖️</div>
          <div>
            <div style="font-weight:600;font-size:15px">Complete tasks to earn skill badges!</div>
            <div style="color:var(--t3);font-size:13px;margin-top:3px">Bronze → Silver → Gold in 9 skill categories. Show employers your expertise.</div>
          </div>
          <a href="skill_badges.php" class="btn btn-brand btn-sm" style="margin-left:auto">View Badges →</a>
        </div>
      </div>

    </main>
  </div>
</div>

<script>
// Load smart recommendations via AJAX
fetch('task_recommendations.php')
  .then(r=>r.json())
  .then(tasks=>{
    const grid=document.getElementById('rec-grid');
    if(!tasks.length){grid.innerHTML='<div style="color:var(--t3);font-size:13px;padding:20px 0">🔍 <a href="browse_tasks.php" style="color:var(--violet2)">Browse all open tasks →</a></div>';return;}
    const urgColors={'urgent':'tag-r','high':'tag-a','medium':'tag-c','low':'tag-g'};
    grid.innerHTML = tasks.map(t=>`
      <a href="task_view.php?id=${t.id}" class="job-card" style="display:block;text-decoration:none">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
          <span class="tag tag-c" style="font-size:11px">${t.category||'General'}</span>
          <span class="tag ${urgColors[t.urgency]||'tag-c'}" style="font-size:10px">${t.urgency||'medium'}</span>
        </div>
        <div style="font-weight:700;font-size:14px;margin-bottom:5px;color:var(--t1);line-height:1.35">${t.title}</div>
        <div style="font-size:12px;color:var(--t3);margin-bottom:10px">🏢 ${t.employer_name}</div>
        <div style="font-family:var(--fh);font-size:1.1rem;font-weight:800;color:var(--emerald)">₹${Number(t.price).toLocaleString('en-IN')}</div>
      </a>`).join('');
  }).catch(()=>{
    document.getElementById('rec-loading').textContent='';
  });
</script>
<?php include 'includes/footer.php'; ?>
