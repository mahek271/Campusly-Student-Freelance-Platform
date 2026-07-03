<?php
session_start();
include 'db.php';

// Privacy gate — must be logged in to view any student profile
if(!isset($_SESSION['user_id'])){
    $_SESSION['flash_error'] = 'Please log in to view student profiles.';
    header("Location: login.php?next=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$sid = intval($_GET['id'] ?? 0);
if(!$sid){ header("Location: index.php"); exit(); }

$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Admins can view any user (including banned & employers); others only see active candidates
if($is_admin){
  $student = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM users WHERE id=$sid AND role!='admin'"));
} else {
  $student = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM users WHERE id=$sid AND role='candidate' AND (is_banned IS NULL OR is_banned=0)"));
}

if(!$student){
  $_SESSION['flash_error'] = 'User profile not found.';
  header("Location: " . ($is_admin ? "admin_dashboard.php" : "leaderboard.php"));
  exit();
}

// ── Core stats ──────────────────────────────────────────────────
$stats = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT
    COUNT(*)                                                   AS total_bids,
    SUM(status='completed')                                    AS completed,
    SUM(status='selected')                                     AS active,
    SUM(status='pending')                                      AS pending,
    ROUND(SUM(status='completed')/NULLIF(COUNT(*),0)*100, 0)   AS win_rate
   FROM applications WHERE candidate_id=$sid"));

$avg_r = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COALESCE(AVG(rating),0) AS a, COUNT(*) AS c FROM reviews WHERE reviewee_id=$sid"));

$earned = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COALESCE(SUM(amount),0) AS e FROM payments WHERE candidate_id=$sid AND status='released'"))['e'];

// ── Category breakdown ──────────────────────────────────────────
$cat_stats = mysqli_query($conn,
  "SELECT t.category,
    COUNT(*) AS total,
    SUM(a.status='completed') AS done,
    SUM(t.price)              AS value
   FROM applications a
   JOIN tasks t ON a.task_id=t.id
   WHERE a.candidate_id=$sid AND t.category IS NOT NULL
   GROUP BY t.category
   ORDER BY done DESC, total DESC");

// ── Completed task history ──────────────────────────────────────
$history = mysqli_query($conn,
  "SELECT a.*, t.title, t.category, t.price, t.skills,
    u.name AS employer_name, u.company_name,
    r.rating, r.comment AS review_comment
   FROM applications a
   JOIN tasks t ON a.task_id=t.id
   JOIN users u ON t.employer_id=u.id
   LEFT JOIN reviews r ON r.task_id=t.id AND r.reviewee_id=$sid
   WHERE a.candidate_id=$sid AND a.status='completed'
   ORDER BY a.applied_at DESC");

// ── Reviews ─────────────────────────────────────────────────────
$reviews = mysqli_query($conn,
  "SELECT r.*, u.name AS reviewer_name, u.company_name, t.title AS task_title
   FROM reviews r
   JOIN users u  ON r.reviewer_id=u.id
   JOIN tasks  t ON r.task_id=t.id
   WHERE r.reviewee_id=$sid
   ORDER BY r.created_at DESC LIMIT 6");

// ── Recent bids (for employer context) ─────────────────────────
$recent_bids = mysqli_query($conn,
  "SELECT a.status, t.title, t.category, a.applied_at
   FROM applications a
   JOIN tasks t ON a.task_id=t.id
   WHERE a.candidate_id=$sid
   ORDER BY a.applied_at DESC LIMIT 5");

$skills = $student['skills'] ? array_map('trim', explode(',', $student['skills'])) : [];
$student_badges = mysqli_query($conn,"SELECT skill_name,score FROM skill_badges WHERE user_id=$sid ORDER BY score DESC");

$page_title = htmlspecialchars($student['name'])."'s Profile";
include 'includes/head.php';
include 'includes/navbar.php';
?>

<style>
.prof-grid { display:grid; grid-template-columns:340px 1fr; gap:22px; align-items:start; max-width:1100px; margin:0 auto; padding:100px 20px 60px; }
.stat-pill  { background:rgba(255,255,255,0.04); border:1px solid var(--border); border-radius:12px; padding:16px 20px; text-align:center; }
.hist-card  { background:var(--ink3); border:1px solid var(--border); border-radius:var(--r); padding:18px 20px; margin-bottom:12px; transition:var(--tr); }
.hist-card:hover { border-color:rgba(124,92,252,0.3); }
.bar-fill   { height:100%; border-radius:3px; background:linear-gradient(90deg,var(--violet),var(--violet2)); transition:width .6s ease; }
.section-card { background:rgba(255,255,255,0.025); border:1px solid var(--border); border-radius:var(--r); padding:24px; margin-bottom:18px; }
.section-title { font-family:var(--fh); font-weight:700; font-size:1rem; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
@media(max-width:860px){ .prof-grid{ grid-template-columns:1fr; padding-top:80px; } }
</style>

<div class="prof-grid">

  <!-- ══ LEFT COLUMN ══════════════════════════════════════════ -->
  <div>

    <!-- Profile Card -->
    <div class="section-card" style="text-align:center;padding:28px">
      <?php if(!empty($student['avatar']) && file_exists($student['avatar'])): ?>
        <img src="<?= htmlspecialchars($student['avatar']) ?>" alt="<?= htmlspecialchars($student['name']) ?>"
          style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--violet);margin:0 auto 14px">
      <?php else: ?>
        <div class="avatar" style="width:100px;height:100px;font-size:40px;margin:0 auto 14px"><?= strtoupper(substr($student['name'],0,1)) ?></div>
      <?php endif; ?>

      <h1 style="font-family:var(--fh);font-size:1.35rem;font-weight:700;margin-bottom:4px"><?= htmlspecialchars($student['name']) ?></h1>
      <?php if($student['university']): ?>
        <div style="color:var(--t3);font-size:13px;margin-bottom:10px">🎓 <?= htmlspecialchars($student['university']) ?></div>
      <?php endif; ?>
      <?php if($student['location']): ?>
        <div style="color:var(--t4);font-size:12.5px;margin-bottom:10px">📍 <?= htmlspecialchars($student['location']) ?></div>
      <?php endif; ?>

      <!-- Star rating -->
      <div style="display:flex;align-items:center;justify-content:center;gap:5px;margin-bottom:16px">
        <?php for($i=1;$i<=5;$i++): ?><span class="star <?= $i<=$avg_r['a']?'on':'off' ?>" style="font-size:18px">★</span><?php endfor; ?>
        <span style="color:var(--t2);font-size:13px;margin-left:4px"><?= number_format($avg_r['a'],1) ?> <span style="color:var(--t4)">(<?= $avg_r['c'] ?> reviews)</span></span>
      </div>

      <?php if($student['bio']): ?>
        <p style="color:var(--t2);font-size:13.5px;line-height:1.7;margin-bottom:16px;text-align:left"><?= htmlspecialchars($student['bio']) ?></p>
      <?php endif; ?>

      <!-- Skills -->
      <?php if($skills): ?>
      <div style="text-align:left;margin-bottom:16px">
        <div style="font-size:11px;font-weight:700;color:var(--t4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Skills</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
          <?php foreach($skills as $sk): ?><span class="tag tag-v" style="font-size:11.5px"><?= htmlspecialchars($sk) ?></span><?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <!-- Verified Badges -->
      <?php if(isset($student_badges) && mysqli_num_rows($student_badges)>0): ?>
      <div style="text-align:left;margin-bottom:16px">
        <div style="font-size:11px;font-weight:700;color:var(--t4);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">🏅 Verified Skill Badges</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
          <?php while($sb=$student_badges->fetch_assoc()): ?>
            <span style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:20px;background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.28);font-size:12px;font-weight:600;color:var(--amber)">
              🏅 <?= htmlspecialchars($sb['skill_name']) ?> <span style="opacity:.65;font-size:10px"><?= $sb['score'] ?>%</span>
            </span>
          <?php endwhile; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Social links -->
      <?php if($student['github']||$student['linkedin']||$student['portfolio']): ?>
      <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-bottom:16px">
        <?php if($student['github']):   ?><a href="<?= htmlspecialchars($student['github'])   ?>" target="_blank" class="btn btn-ghost btn-sm">🐙 GitHub</a><?php endif; ?>
        <?php if($student['linkedin']): ?><a href="<?= htmlspecialchars($student['linkedin']) ?>" target="_blank" class="btn btn-ghost btn-sm">💼 LinkedIn</a><?php endif; ?>
        <?php if($student['portfolio']): ?><a href="<?= htmlspecialchars($student['portfolio'])?>" target="_blank" class="btn btn-ghost btn-sm">🌐 Portfolio</a><?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Message button (employers only) -->
      <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role']==='employer'): ?>
      <a href="messages.php?with=<?= $sid ?>" class="btn btn-brand btn-full">💬 Message Student</a>
      <?php endif; ?>
    </div>

    <!-- Quick Stats -->
    <div class="section-card">
      <div class="section-title">📊 Stats at a Glance</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="stat-pill">
          <div style="font-family:var(--fh);font-size:1.6rem;font-weight:800;color:var(--emerald)"><?= $stats['completed'] ?></div>
          <div style="font-size:11.5px;color:var(--t3);margin-top:2px">Completed</div>
        </div>
        <div class="stat-pill">
          <div style="font-family:var(--fh);font-size:1.6rem;font-weight:800;color:var(--violet2)">₹<?= number_format($earned) ?></div>
          <div style="font-size:11.5px;color:var(--t3);margin-top:2px">Total Earned</div>
        </div>
        <div class="stat-pill">
          <div style="font-family:var(--fh);font-size:1.6rem;font-weight:800;color:var(--amber)"><?= $stats['win_rate'] ?? 0 ?>%</div>
          <div style="font-size:11.5px;color:var(--t3);margin-top:2px">Win Rate</div>
        </div>
        <div class="stat-pill">
          <div style="font-family:var(--fh);font-size:1.6rem;font-weight:800;color:var(--cyan)"><?= $stats['total_bids'] ?></div>
          <div style="font-size:11.5px;color:var(--t3);margin-top:2px">Total Bids</div>
        </div>
      </div>
    </div>

    <!-- Category Expertise -->
    <?php
    $cat_rows = [];
    while($c = mysqli_fetch_assoc($cat_stats)) $cat_rows[] = $c;
    $max_done = !empty($cat_rows) ? max(array_merge([1], array_column($cat_rows,'done'))) : 1;
    if($cat_rows):
    ?>
    <div class="section-card">
      <div class="section-title">🎯 Category Expertise</div>
      <?php foreach($cat_rows as $c):
        $pct = round(($c['done'] / $max_done) * 100);
        $cat_emoji = match(true) {
          str_contains($c['category'],'Web') || str_contains($c['category'],'Dev') => '💻',
          str_contains($c['category'],'Design')  => '🎨',
          str_contains($c['category'],'Data')    => '📊',
          str_contains($c['category'],'AI') || str_contains($c['category'],'ML') => '🤖',
          str_contains($c['category'],'Content') || str_contains($c['category'],'Writ') => '✍️',
          str_contains($c['category'],'Market')  => '📣',
          str_contains($c['category'],'Video')   => '🎬',
          str_contains($c['category'],'Mobile')  => '📱',
          default => '⚙️'
        };
      ?>
      <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
          <span style="font-size:13px;font-weight:600"><?= $cat_emoji ?> <?= htmlspecialchars($c['category']) ?></span>
          <span style="font-size:12px;color:var(--t3)"><?= $c['done'] ?> done · <?= $c['total'] ?> bids</span>
        </div>
        <div style="height:6px;background:var(--surface2);border-radius:3px;overflow:hidden">
          <div class="bar-fill" style="width:<?= $pct ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Recent Activity (quick timeline) -->
    <div class="section-card">
      <div class="section-title">🕐 Recent Activity</div>
      <?php
      $act_empty = true;
      mysqli_data_seek($recent_bids, 0);
      while($rb = mysqli_fetch_assoc($recent_bids)):
        $act_empty = false;
        $dot_color = match($rb['status']) {
          'completed' => 'var(--emerald)', 'selected'  => 'var(--violet2)',
          'rejected'  => 'var(--rose)',    default     => 'var(--amber)'
        };
      ?>
      <div style="display:flex;gap:12px;align-items:flex-start;padding:9px 0;border-bottom:1px solid rgba(255,255,255,0.04)">
        <div style="width:8px;height:8px;border-radius:50%;background:<?= $dot_color ?>;flex-shrink:0;margin-top:5px"></div>
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($rb['title']) ?></div>
          <div style="font-size:11.5px;color:var(--t3)"><?= htmlspecialchars($rb['category']) ?> · <?= ucfirst($rb['status']) ?> · <?= date('d M Y',strtotime($rb['applied_at'])) ?></div>
        </div>
      </div>
      <?php endwhile; ?>
      <?php if($act_empty): ?><div style="color:var(--t4);font-size:13px">No activity yet.</div><?php endif; ?>
    </div>

  </div><!-- /left -->

  <!-- ══ RIGHT COLUMN ═════════════════════════════════════════ -->
  <div>

    <!-- Completed Task History -->
    <div class="section-card">
      <div class="section-title">
        🏆 Completed Tasks
        <span style="margin-left:auto;background:rgba(16,185,129,0.12);color:var(--emerald);border:1px solid rgba(16,185,129,0.25);border-radius:20px;padding:2px 10px;font-size:12px;font-weight:600"><?= $stats['completed'] ?> total</span>
      </div>

      <?php
      $history_rows = [];
      while($h = mysqli_fetch_assoc($history)) $history_rows[] = $h;
      ?>

      <?php if(empty($history_rows)): ?>
        <div style="text-align:center;padding:40px 20px;color:var(--t3)">
          <div style="font-size:2.5rem;margin-bottom:10px">🎯</div>
          <div style="font-size:14px">No completed tasks yet.</div>
          <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role']==='employer'): ?>
            <div style="font-size:12.5px;color:var(--t4);margin-top:6px">Be the first to work with this student!</div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <?php foreach($history_rows as $h):
          $task_skills = $h['skills'] ? array_slice(array_map('trim',explode(',',$h['skills'])),0,4) : [];
        ?>
        <div class="hist-card">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:0">
              <div style="display:flex;gap:7px;margin-bottom:6px;flex-wrap:wrap;align-items:center">
                <span class="tag tag-g" style="font-size:11px">🏆 Completed</span>
                <?php if($h['category']): ?><span class="tag tag-c" style="font-size:11px"><?= htmlspecialchars($h['category']) ?></span><?php endif; ?>
              </div>
              <div style="font-weight:700;font-size:14.5px;margin-bottom:4px;line-height:1.35"><?= htmlspecialchars($h['title']) ?></div>
              <div style="color:var(--t3);font-size:12.5px;margin-bottom:8px">
                🏢 <?= htmlspecialchars($h['company_name']?:$h['employer_name']) ?>
                &nbsp;·&nbsp; <?= date('d M Y',strtotime($h['applied_at'])) ?>
              </div>
              <?php if($task_skills): ?>
              <div style="display:flex;flex-wrap:wrap;gap:5px">
                <?php foreach($task_skills as $sk): ?><span class="tag tag-v" style="font-size:10.5px"><?= htmlspecialchars($sk) ?></span><?php endforeach; ?>
              </div>
              <?php endif; ?>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <div style="font-family:var(--fh);font-size:1.15rem;font-weight:800;color:var(--emerald)">₹<?= number_format($h['price']) ?></div>
              <?php if($h['rating']): ?>
              <div style="display:flex;align-items:center;gap:3px;justify-content:flex-end;margin-top:4px">
                <?php for($i=1;$i<=5;$i++): ?><span class="star <?= $i<=$h['rating']?'on':'off' ?>" style="font-size:13px">★</span><?php endfor; ?>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Submission link if available -->
          <?php if($h['submission_link']): ?>
          <div style="margin-top:10px;background:rgba(0,212,255,0.05);border:1px solid rgba(0,212,255,0.15);border-radius:8px;padding:9px 12px;font-size:12.5px">
            🔗 <a href="<?= htmlspecialchars($h['submission_link']) ?>" target="_blank" style="color:var(--cyan)">View Deliverable</a>
          </div>
          <?php endif; ?>

          <!-- Review comment -->
          <?php if($h['review_comment']): ?>
          <div style="margin-top:10px;background:rgba(245,158,11,0.05);border-left:3px solid var(--amber);border-radius:0 8px 8px 0;padding:10px 14px;font-size:13px;color:var(--t2);line-height:1.6">
            <span style="font-size:11px;color:var(--amber);font-weight:700;display:block;margin-bottom:3px">EMPLOYER REVIEW</span>
            "<?= htmlspecialchars($h['review_comment']) ?>"
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Reviews Section -->
    <?php if(mysqli_num_rows($reviews) > 0): ?>
    <div class="section-card">
      <div class="section-title">
        ⭐ Reviews from Employers
        <span style="margin-left:auto;color:var(--amber);font-weight:700"><?= number_format($avg_r['a'],1) ?>/5</span>
      </div>
      <?php while($rev = mysqli_fetch_assoc($reviews)): ?>
      <div style="border-bottom:1px solid var(--border);padding:16px 0">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:8px">
          <div>
            <div style="font-weight:600;font-size:14px"><?= htmlspecialchars($rev['reviewer_name']) ?></div>
            <div style="color:var(--t4);font-size:11.5px;margin-top:2px">
              <?= htmlspecialchars($rev['company_name']??'') ?>
              <?= $rev['company_name']?' · ':'' ?><?= date('d M Y',strtotime($rev['created_at'])) ?>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:3px;flex-shrink:0">
            <?php for($i=1;$i<=5;$i++): ?><span class="star <?= $i<=$rev['rating']?'on':'off' ?>" style="font-size:15px">★</span><?php endfor; ?>
          </div>
        </div>
        <div style="font-size:12px;color:var(--t3);margin-bottom:6px">on: <em><?= htmlspecialchars($rev['task_title']) ?></em></div>
        <?php if($rev['comment']): ?>
          <p style="color:var(--t2);font-size:13.5px;line-height:1.7"><?= htmlspecialchars($rev['comment']) ?></p>
        <?php endif; ?>
      </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Employer CTA box -->
    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role']==='employer'): ?>
    <div style="background:linear-gradient(135deg,rgba(124,92,252,0.12),rgba(0,212,255,0.06));border:1px solid rgba(124,92,252,0.25);border-radius:var(--r);padding:22px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
      <div>
        <div style="font-family:var(--fh);font-weight:700;font-size:15px;margin-bottom:4px">Ready to work with <?= htmlspecialchars(explode(' ',$student['name'])[0]) ?>?</div>
        <div style="color:var(--t2);font-size:13px">Post a task and invite this student directly.</div>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="post_task.php" class="btn btn-brand btn-sm">+ Post Task</a>
        <a href="task_invite.php" class="btn btn-outline btn-sm">📨 Invite to Task</a>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /right -->
</div><!-- /prof-grid -->

<?php include 'includes/footer.php'; ?>
