<?php
session_start();
include 'db.php';

$total_tasks    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM tasks"))['c'];
$total_students = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM users WHERE role='candidate'"))['c'];
$total_paid     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as c FROM payments WHERE status='released'"))['c'];
$open_tasks     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM tasks WHERE status='open'"))['c'];

$logged_in = isset($_SESSION['user_id']);
$nav_avatar = null;
if($logged_in){
  $uid = (int)$_SESSION['user_id'];
  $av = mysqli_fetch_assoc(mysqli_query($conn,"SELECT avatar FROM users WHERE id=$uid LIMIT 1"));
  if($av && !empty($av['avatar']) && file_exists($av['avatar'])) $nav_avatar = $av['avatar'];

  // Recent tasks only for logged-in users
  $recent_tasks = mysqli_query($conn,"
    SELECT t.*, u.name as employer_name 
    FROM tasks t 
    JOIN users u ON t.employer_id=u.id 
    WHERE t.status='open' 
    ORDER BY t.created_at DESC LIMIT 6");
}

$page_title = 'Where Campus Talent Meets Real Work';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <!-- HERO -->
  <section style="padding:140px 0 80px;text-align:center">
    <div class="ctr">
      <div style="display:inline-flex;align-items:center;gap:8px;padding:8px 18px;border-radius:40px;background:rgba(59,130,246,0.12);border:1px solid rgba(59,130,246,0.25);margin-bottom:28px;font-size:13px;color:var(--blue2)">
        🎓 &nbsp;<strong><?= number_format($total_students) ?>+ students</strong>&nbsp; already earning on Campusly
      </div>
      <h1 style="font-family:var(--fh);font-size:clamp(2.5rem,6vw,4.2rem);font-weight:800;line-height:1.1;margin-bottom:24px;max-width:780px;margin-left:auto;margin-right:auto">
        Where <span style="background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent">Campus Talent</span><br>Meets Real Work
      </h1>

      <?php if($logged_in): ?>
      <!-- Logged-in hero: show profile image + welcome -->
      <div style="display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:32px">
        <?php if($nav_avatar): ?>
          <img src="<?= htmlspecialchars($nav_avatar) ?>" alt="Profile"
            style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid var(--blue);box-shadow:0 0 20px var(--blue-glow)">
        <?php else: ?>
          <div class="avatar avatar-lg" style="border:3px solid var(--blue)"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div>
        <?php endif; ?>
        <div style="text-align:left">
          <div style="font-size:1.1rem;font-weight:700">Welcome back, <?= htmlspecialchars(explode(' ',$_SESSION['user_name'])[0]) ?>! 👋</div>
          <div style="color:var(--t3);font-size:13px">Ready to take on new tasks?</div>
        </div>
      </div>
      <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
        <?php if($_SESSION['user_role']==='candidate'): ?>
          <a href="candidate_dashboard.php" class="btn btn-brand btn-xl">🚀 My Dashboard</a>
          <a href="browse_tasks.php" class="btn btn-glass btn-lg">Browse Tasks →</a>
        <?php elseif($_SESSION['user_role']==='employer'): ?>
          <a href="employer_dashboard.php" class="btn btn-brand btn-xl">📋 My Dashboard</a>
          <a href="post_task.php" class="btn btn-glass btn-lg">Post a Task →</a>
        <?php else: ?>
          <a href="admin_dashboard.php" class="btn btn-brand btn-xl">🔑 Admin Panel</a>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <p style="color:var(--t2);font-size:1.15rem;max-width:560px;margin:0 auto 40px">
        Students bid on real tasks from verified employers. Build your portfolio, earn real money, and launch your career while still in college.
      </p>
      <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
        <a href="register.php?role=candidate" class="btn btn-brand btn-xl">🚀 Start Earning</a>
        <a href="register.php?role=employer"  class="btn btn-glass btn-lg">📋 Post a Task</a>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- STATS -->
  <section style="padding:30px 0 70px">
    <div class="ctr">
      <div class="grid-4">
        <div class="stat-box">
          <div class="stat-num"><?= number_format($total_tasks) ?>+</div>
          <div class="stat-label">Tasks Posted</div>
        </div>
        <div class="stat-box">
          <div class="stat-num"><?= number_format($total_students) ?>+</div>
          <div class="stat-label">Student Freelancers</div>
        </div>
        <div class="stat-box">
          <div class="stat-num">₹<?= number_format($total_paid/1000,0) ?>K+</div>
          <div class="stat-label">Paid to Students</div>
        </div>
        <div class="stat-box">
          <div class="stat-num"><?= number_format($open_tasks) ?></div>
          <div class="stat-label">Open Right Now</div>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS -->
  <section class="section">
    <div class="ctr">
      <div style="text-align:center;margin-bottom:56px">
        <div class="tag tag-v" style="margin-bottom:14px">How it works</div>
        <h2 style="font-family:var(--fh);font-size:2.2rem;font-weight:800">Simple. Transparent. Fair.</h2>
      </div>
      <div class="grid-3">
        <?php $steps=[
          ['🔍','Find Tasks','Browse hundreds of real tasks from verified companies — design, dev, content, research & more.'],
          ['💡','Place Your Bid','Submit your proposal with your bid amount. Show why you\'re the best fit for the task.'],
          ['💸','Get Paid Securely','Employer funds escrow. You deliver. Employer approves. Payment released instantly.'],
        ];
        foreach($steps as $i=>$s): ?>
        <div class="card" style="text-align:center;padding:36px 28px">
          <div style="width:60px;height:60px;border-radius:16px;background:var(--ink4);font-size:28px;display:flex;align-items:center;justify-content:center;margin:0 auto 18px">
            <?= $s[0] ?>
          </div>
          <div style="font-family:var(--fh);font-size:1.1rem;font-weight:700;margin-bottom:10px"><?= $s[1] ?></div>
          <p style="color:var(--t2);font-size:14px"><?= $s[2] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if($logged_in && isset($recent_tasks)): ?>
  <!-- RECENT TASKS — only for logged-in users -->
  <section class="section" style="padding-top:0">
    <div class="ctr">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:32px">
        <div>
          <div class="tag tag-g" style="margin-bottom:10px">Live Opportunities</div>
          <h2 style="font-family:var(--fh);font-size:1.8rem;font-weight:800">Open Tasks</h2>
        </div>
        <a href="browse_tasks.php" class="btn btn-outline">View All →</a>
      </div>
      <div class="grid-3">
        <?php while($t=mysqli_fetch_assoc($recent_tasks)):
          $skills = $t['skills'] ? explode(',',htmlspecialchars($t['skills'])) : [];
          $urgency_cls = ['urgent'=>'tag-r','high'=>'tag-a','medium'=>'tag-v','low'=>'tag-g'][$t['urgency']??'medium'] ?? 'tag-v';
        ?>
        <a href="task_view.php?id=<?= $t['id'] ?>" class="job-card" style="display:block">
          <div class="job-card-header">
            <div>
              <div class="job-title"><?= htmlspecialchars($t['title']) ?></div>
              <div class="job-company">🏢 <?= htmlspecialchars($t['employer_name']) ?></div>
            </div>
            <div class="job-budget">₹<?= number_format($t['price']) ?></div>
          </div>
          <p style="color:var(--t2);font-size:13px;line-height:1.6;margin-bottom:10px"><?= htmlspecialchars(substr($t['description'],0,120)) ?>...</p>
          <div class="job-tags">
            <span class="tag <?= $urgency_cls ?>"><?= ucfirst($t['urgency']??'medium') ?></span>
            <span class="tag tag-c"><?= htmlspecialchars($t['category']) ?></span>
            <?php foreach(array_slice($skills,0,3) as $sk): ?><span class="tag tag-v"><?= trim($sk) ?></span><?php endforeach; ?>
          </div>
          <div class="job-meta">
            <span>📅 <?= $t['deadline'] ?? 'Flexible' ?></span>
            <span>📍 <?= htmlspecialchars($t['location']) ?></span>
          </div>
        </a>
        <?php endwhile; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- CTA -->
  <section class="section">
    <div class="ctr">
      <div class="glass" style="text-align:center;padding:70px 40px;background:linear-gradient(135deg,rgba(29,78,216,0.1),rgba(14,165,233,0.06))">
        <h2 style="font-family:var(--fh);font-size:2.2rem;font-weight:800;margin-bottom:14px">Ready to start?</h2>
        <p style="color:var(--t2);max-width:480px;margin:0 auto 32px">Join thousands of students building real careers on Campusly. No experience needed — just talent and drive.</p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
          <?php if(!$logged_in): ?>
          <a href="register.php?role=candidate" class="btn btn-brand btn-xl">Join as Student</a>
          <a href="register.php?role=employer"  class="btn btn-glass btn-lg">Hire Students</a>
          <?php else: ?>
          <a href="browse_tasks.php" class="btn btn-brand btn-xl">Browse Open Tasks →</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include 'includes/footer.php'; ?>
