<?php
/**
 * skill_badges.php — Skill Badge / Certificate System
 * Shows earned badges based on completed tasks per category
 * Linked from candidate_dashboard and profile
 */
session_start();
include 'db.php';
requireRole('candidate');

$uid  = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM users WHERE id=$uid"));

// Count completions per category
$cat_completions = mysqli_query($conn,
  "SELECT t.category, COUNT(*) as cnt, SUM(t.price) as earned
   FROM applications a
   JOIN tasks t ON a.task_id = t.id
   WHERE a.candidate_id=$uid AND a.status='completed' AND t.category IS NOT NULL
   GROUP BY t.category ORDER BY cnt DESC");

$cat_data = [];
while($r = mysqli_fetch_assoc($cat_completions)) $cat_data[$r['category']] = $r;

// Badge definitions: [category, emoji, name, thresholds => [bronze,silver,gold]]
$badge_defs = [
  ['Web Development',    '💻', 'Web Dev',       [1,3,7]],
  ['Mobile Development', '📱', 'App Builder',   [1,3,7]],
  ['Design',             '🎨', 'Designer',      [1,3,7]],
  ['Data & Analytics',   '📊', 'Data Analyst',  [1,3,7]],
  ['AI & ML',            '🤖', 'AI Engineer',   [1,3,7]],
  ['Content & Writing',  '✍️',  'Writer',        [1,3,7]],
  ['Marketing',          '📣', 'Marketer',      [1,3,7]],
  ['Video & Media',      '🎬', 'Media Creator', [1,3,7]],
  ['Engineering',        '⚙️',  'Engineer',      [1,3,7]],
];

$total_completed = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM applications WHERE candidate_id=$uid AND status='completed'"))['c'];

function getBadgeTier($count, $thresholds){
  if($count >= $thresholds[2]) return ['gold','🥇 Gold',  'rgba(245,158,11,0.2)',  '#f59e0b'];
  if($count >= $thresholds[1]) return ['silver','🥈 Silver','rgba(148,163,184,0.15)','#94a3b8'];
  if($count >= $thresholds[0]) return ['bronze','🥉 Bronze','rgba(205,124,50,0.15)', '#cd7c32'];
  return null;
}

$page_title = 'My Badges';
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
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
      <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700">🎖️ Skill Badges</h1>
    </div>
    <p style="color:var(--t3);margin-bottom:28px">Complete tasks in each category to earn Bronze → Silver → Gold badges.</p>

    <!-- Summary -->
    <div class="glass-dark" style="padding:20px 24px;border-radius:var(--r);margin-bottom:28px;display:flex;align-items:center;gap:24px;flex-wrap:wrap">
      <div style="text-align:center">
        <div style="font-family:var(--fh);font-size:2rem;font-weight:800;color:var(--violet2)"><?= $total_completed ?></div>
        <div style="font-size:12px;color:var(--t3);margin-top:2px">Tasks Completed</div>
      </div>
      <div style="width:1px;height:40px;background:var(--border)"></div>
      <?php
      $earned_badges = 0;
      foreach($badge_defs as [$cat,$em,$name,$thr]){
        $cnt = $cat_data[$cat]['cnt'] ?? 0;
        if(getBadgeTier($cnt,$thr)) $earned_badges++;
      }
      ?>
      <div style="text-align:center">
        <div style="font-family:var(--fh);font-size:2rem;font-weight:800;color:var(--amber)"><?= $earned_badges ?></div>
        <div style="font-size:12px;color:var(--t3);margin-top:2px">Badges Earned</div>
      </div>
      <div style="width:1px;height:40px;background:var(--border)"></div>
      <div style="flex:1;min-width:200px">
        <div style="font-size:12.5px;color:var(--t2);margin-bottom:6px">Progress to next badge</div>
        <div style="height:6px;background:var(--surface2);border-radius:3px;overflow:hidden">
          <div style="width:<?= min(100,($total_completed/21)*100) ?>%;height:100%;border-radius:3px;background:linear-gradient(90deg,var(--violet),var(--amber))"></div>
        </div>
        <div style="font-size:11px;color:var(--t4);margin-top:4px">Complete 21 total tasks to max all Gold badges</div>
      </div>
    </div>

    <!-- Badge Grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">
    <?php foreach($badge_defs as [$cat,$em,$name,$thr]): ?>
    <?php
      $cnt  = $cat_data[$cat]['cnt'] ?? 0;
      $earn = $cat_data[$cat]['earned'] ?? 0;
      $tier = getBadgeTier($cnt,$thr);
      $next_thresh = $cnt < $thr[0] ? $thr[0] : ($cnt < $thr[1] ? $thr[1] : ($cnt < $thr[2] ? $thr[2] : null));
      $pct  = $next_thresh ? min(100,round(($cnt/$next_thresh)*100)) : 100;
    ?>
    <div class="glass-dark" style="padding:22px;border-radius:var(--r);border:1px solid <?= $tier ? $tier[3].'40' : 'var(--border)' ?>;position:relative;overflow:hidden">
      <?php if($tier): ?>
      <div style="position:absolute;top:12px;right:12px;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;background:<?= $tier[2] ?>;color:<?= $tier[3] ?>"><?= $tier[1] ?></div>
      <?php endif; ?>
      <div style="font-size:2.8rem;margin-bottom:10px;<?= !$tier?'filter:grayscale(1);opacity:.35':'' ?>"><?= $em ?></div>
      <div style="font-family:var(--fh);font-weight:700;font-size:1rem;margin-bottom:3px"><?= htmlspecialchars($name) ?></div>
      <div style="font-size:11.5px;color:var(--t3);margin-bottom:12px"><?= htmlspecialchars($cat) ?></div>
      <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--t2);margin-bottom:5px">
        <span><?= $cnt ?> task<?= $cnt!=1?'s':'' ?> done</span>
        <?php if($next_thresh): ?><span>Next: <?= $next_thresh ?></span><?php else: ?><span style="color:var(--amber)">🏆 Max!</span><?php endif; ?>
      </div>
      <div style="height:5px;background:var(--surface2);border-radius:3px;overflow:hidden">
        <div style="width:<?= $pct ?>%;height:100%;border-radius:3px;background:<?= $tier?$tier[3]:'var(--border2)' ?>;transition:width .5s ease"></div>
      </div>
      <?php if($cnt>0): ?>
      <div style="font-size:11.5px;color:var(--emerald);margin-top:8px">₹<?= number_format($earn) ?> earned in this category</div>
      <?php else: ?>
      <div style="font-size:11.5px;color:var(--t4);margin-top:8px">Apply to <?= htmlspecialchars($cat) ?> tasks to start!</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Legend -->
    <div class="glass-dark" style="padding:18px 24px;border-radius:var(--r);margin-top:24px">
      <div style="font-size:12px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">How to earn badges</div>
      <div style="display:flex;gap:24px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:8px"><span style="color:#cd7c32;font-weight:700">🥉 Bronze</span><span style="color:var(--t2);font-size:13px">Complete 1 task in category</span></div>
        <div style="display:flex;align-items:center;gap:8px"><span style="color:#94a3b8;font-weight:700">🥈 Silver</span><span style="color:var(--t2);font-size:13px">Complete 3 tasks in category</span></div>
        <div style="display:flex;align-items:center;gap:8px"><span style="color:#f59e0b;font-weight:700">🥇 Gold</span><span style="color:var(--t2);font-size:13px">Complete 7 tasks in category</span></div>
      </div>
    </div>
  </main>
</div>
</div>
<?php include 'includes/footer.php'; ?>
