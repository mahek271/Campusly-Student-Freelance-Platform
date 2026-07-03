<?php
/**
 * task_timer.php — Live Deadline Countdown
 * Shows a live countdown for active tasks.
 * Accessible to both the selected candidate and the employer.
 */
session_start();
include 'db.php';
requireLogin();

$uid    = (int)$_SESSION['user_id'];
$app_id = intval($_GET['app_id'] ?? 0);
if(!$app_id){ header("Location: index.php"); exit(); }

$app = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT a.*, t.title, t.deadline, t.price, t.description,
    u_c.name AS cand_name, u_e.name AS emp_name, t.employer_id, t.id as task_id
   FROM applications a
   JOIN tasks t ON t.id=a.task_id
   JOIN users u_c ON u_c.id=a.candidate_id
   JOIN users u_e ON u_e.id=t.employer_id
   WHERE a.id=$app_id AND a.status IN ('selected','completed')
   LIMIT 1"));

if(!$app){ header("Location: index.php"); exit(); }
$is_involved = ($uid===$app['candidate_id'] || $uid===(int)$app['employer_id']);
if(!$is_involved){ header("Location: index.php"); exit(); }

$deadline_ts = $app['deadline'] ? strtotime($app['deadline'].' 23:59:59') : null;
$now_ts      = time();
$is_mine     = ($uid===$app['candidate_id']);

$page_title='Task Timer — '.$app['title'];
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div style="max-width:680px;margin:0 auto;padding:100px 20px 60px;text-align:center">
    <div class="tag tag-v" style="margin-bottom:16px">⏱ Task Timer</div>
    <h1 style="font-family:var(--fh);font-size:1.6rem;font-weight:700;margin-bottom:6px"><?= htmlspecialchars($app['title']) ?></h1>
    <p style="color:var(--t3);font-size:14px;margin-bottom:36px">
      <?= $is_mine ? 'Your delivery commitment' : "Candidate: <strong style='color:var(--t1)'>{$app['cand_name']}</strong>" ?>
      &nbsp;·&nbsp; Bid: <strong style="color:var(--emerald)">₹<?= number_format($app['bid_amount']) ?></strong>
    </p>

    <?php if($deadline_ts): ?>
      <!-- Countdown -->
      <div id="countdown-wrap" style="background:var(--ink3);border:1px solid var(--border);border-radius:24px;padding:44px 36px;margin-bottom:32px">
        <div id="countdown-label" style="font-size:13px;font-weight:600;color:var(--t3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:24px">Time Remaining</div>
        <div style="display:flex;justify-content:center;gap:20px;margin-bottom:24px" id="countdown-grid">
          <?php foreach(['days','hours','minutes','seconds'] as $unit): ?>
          <div style="text-align:center">
            <div id="c-<?= $unit ?>" style="font-family:var(--fh);font-size:clamp(2.5rem,6vw,4rem);font-weight:800;color:var(--t1);line-height:1;min-width:80px">--</div>
            <div style="font-size:11px;color:var(--t3);text-transform:uppercase;letter-spacing:.6px;margin-top:6px"><?= $unit ?></div>
          </div>
          <?php if($unit!=='seconds'): ?>
          <div style="font-family:var(--fh);font-size:3rem;color:var(--t4);align-self:center;margin-top:-10px">:</div>
          <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <div id="progress-wrap">
          <?php
            $app_date  = strtotime($app['applied_at']);
            $total_dur = $deadline_ts - $app_date;
            $elapsed   = $now_ts - $app_date;
            $pct_done  = $total_dur > 0 ? min(100, round($elapsed/$total_dur*100)) : 100;
          ?>
          <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--t3);margin-bottom:6px">
            <span>Started: <?= date('d M',strtotime($app['applied_at'])) ?></span>
            <span><?= $pct_done ?>% elapsed</span>
            <span>Due: <?= date('d M',strtotime($app['deadline'])) ?></span>
          </div>
          <div class="progress">
            <div class="progress-bar" id="time-bar" style="width:<?= $pct_done ?>%;background:<?= $pct_done>=90?'var(--rose)':($pct_done>=70?'var(--amber)':'var(--emerald)') ?>"></div>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="glass" style="padding:40px;border-radius:var(--r);margin-bottom:28px">
        <div style="font-size:3rem;margin-bottom:12px">⏳</div>
        <h3 style="font-family:var(--fh);font-weight:700">No deadline set</h3>
        <p style="color:var(--t3);margin-top:8px">This task has a flexible timeline</p>
      </div>
    <?php endif; ?>

    <!-- Status & Actions -->
    <div class="glass-dark" style="padding:22px;border-radius:var(--r);margin-bottom:20px">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div style="text-align:left">
          <div style="font-size:12px;color:var(--t3);margin-bottom:4px">Status</div>
          <span class="tag <?= $app['status']==='completed'?'tag-c':'tag-g' ?>"><?= ucfirst($app['status']) ?></span>
        </div>
        <div style="text-align:left">
          <div style="font-size:12px;color:var(--t3);margin-bottom:4px">Delivery Days</div>
          <strong><?= $app['delivery_days'] ?> days</strong>
        </div>
        <div style="text-align:left">
          <div style="font-size:12px;color:var(--t3);margin-bottom:4px">Bid Amount</div>
          <strong style="color:var(--emerald)">₹<?= number_format($app['bid_amount']) ?></strong>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
      <?php if($is_mine && $app['status']==='selected'): ?>
        <a href="submit_task.php?app_id=<?= $app_id ?>" class="btn btn-brand btn-lg">📤 Submit Work</a>
      <?php endif; ?>
      <a href="messages.php?thread=<?= $is_mine?$app['employer_id']:$app['candidate_id'] ?>&task=<?= $app['task_id'] ?>" class="btn btn-glass btn-lg">💬 Message</a>
      <a href="task_view.php?id=<?= $app['task_id'] ?>" class="btn btn-ghost">View Task</a>
    </div>
  </div>
</div>

<script>
(function(){
  const deadline = <?= $deadline_ts ? $deadline_ts * 1000 : 'null' ?>;
  if(!deadline) return;

  function pad(n){ return String(n).padStart(2,'0'); }

  function update(){
    const now  = Date.now();
    const diff = Math.max(0, Math.floor((deadline - now)/1000));

    if(diff <= 0){
      document.getElementById('countdown-label').textContent = '⚠️ Deadline Passed!';
      document.getElementById('countdown-label').style.color = 'var(--rose)';
      ['days','hours','minutes','seconds'].forEach(u => document.getElementById('c-'+u).textContent='00');
      return;
    }

    const days    = Math.floor(diff / 86400);
    const hours   = Math.floor((diff % 86400) / 3600);
    const minutes = Math.floor((diff % 3600) / 60);
    const seconds = diff % 60;

    document.getElementById('c-days').textContent    = pad(days);
    document.getElementById('c-hours').textContent   = pad(hours);
    document.getElementById('c-minutes').textContent = pad(minutes);
    document.getElementById('c-seconds').textContent = pad(seconds);

    // Color change as deadline approaches
    const urgentColor = days < 1 ? 'var(--rose)' : days < 3 ? 'var(--amber)' : 'var(--t1)';
    document.querySelectorAll('#countdown-grid [id^="c-"]').forEach(el => el.style.color = urgentColor);
  }

  update();
  setInterval(update, 1000);
})();
</script>
<?php include 'includes/footer.php'; ?>
