<?php
session_start();
include 'db.php';

$id = intval($_GET['id'] ?? 0);
if(!$id){ header("Location: browse_tasks.php"); exit(); }

$task = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT t.*, u.name as employer_name, u.company_name, u.id as eid
   FROM tasks t JOIN users u ON t.employer_id=u.id WHERE t.id=$id"));
if(!$task){ header("Location: browse_tasks.php"); exit(); }

$skills = $task['skills'] ? array_map('trim', explode(',', $task['skills'])) : [];

// Session checks
$logged_in    = isset($_SESSION['user_id']);
$uid          = $logged_in ? (int)$_SESSION['user_id'] : 0;
$role         = $logged_in ? $_SESSION['user_role'] : '';
$already_applied = false;
$my_bid       = null;
$is_saved     = false;

if($logged_in){
  $chk = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM applications WHERE task_id=$id AND candidate_id=$uid LIMIT 1"));
  if($chk){ $already_applied = true; $my_bid = $chk; }
  if($role==='candidate'){
    $sv = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM saved_tasks WHERE user_id=$uid AND task_id=$id LIMIT 1"));
    $is_saved = (bool)$sv;
    // Handle save/unsave
    if(isset($_GET['save'])){
      if($is_saved) $conn->query("DELETE FROM saved_tasks WHERE user_id=$uid AND task_id=$id");
      else $conn->query("INSERT IGNORE INTO saved_tasks (user_id,task_id) VALUES ($uid,$id)");
      header("Location: task_view.php?id=$id"); exit();
    }
  }
}

// ── BID SUBMIT ──────────────────────────────────────────────
$bid_error = '';
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_bid'])){
  if(!$logged_in){ header("Location: login.php?next=task_view.php?id=$id"); exit(); }
  if($role !== 'candidate')         { $bid_error = 'Only students can place bids.'; }
  elseif($task['status'] !== 'open'){ $bid_error = 'This task is no longer accepting bids.'; }
  elseif($already_applied)          { $bid_error = 'You have already submitted a bid for this task.'; }
  else {
    $bid_amount    = floatval($_POST['bid_amount']   ?? 0);
    $delivery_days = intval($_POST['delivery_days']  ?? 0);
    $proposal      = trim($_POST['proposal']         ?? '');
    $budget        = floatval($task['price']);

    if($bid_amount < 100)
      { $bid_error = '❌ Minimum bid is ₹100.'; }
    elseif($bid_amount > $budget * 1.5)
      { $bid_error = '❌ Bid cannot exceed 150% of budget (₹'.number_format($budget*1.5,0).').'; }
    elseif($delivery_days < 1)
      { $bid_error = '❌ Delivery must be at least 1 day.'; }
    elseif($delivery_days > 90)
      { $bid_error = '❌ Delivery cannot exceed 90 days.'; }
    elseif(strlen($proposal) < 50)
      { $bid_error = '❌ Proposal must be at least 50 characters.'; }
    elseif(strlen($proposal) > 2000)
      { $bid_error = '❌ Proposal cannot exceed 2000 characters.'; }
    else {
      $stmt = $conn->prepare("INSERT INTO applications (task_id,candidate_id,bid_amount,delivery_days,proposal_text,status,applied_at) VALUES (?,?,?,?,?,'pending',NOW())");
      $stmt->bind_param("iidis", $id, $uid, $bid_amount, $delivery_days, $proposal);
      if($stmt->execute()){
        $safe = $conn->real_escape_string("New bid of ₹".number_format($bid_amount)." on your task: {$task['title']}");
        $conn->query("INSERT INTO notifications (user_id,message,link) VALUES ({$task['eid']},'$safe','view_applicants.php?id=$id')");
        $_SESSION['flash_success'] = 'Bid submitted successfully! ✅';
        header("Location: task_view.php?id=$id"); exit();
      } else { $bid_error = 'Submit failed — '.$conn->error; }
    }
  }
}

// Existing bids (anonymised)
$bids_res = mysqli_query($conn,
  "SELECT a.bid_amount, a.delivery_days, a.applied_at,
    u.university,
    CONCAT(LEFT(u.name,1), REPEAT('*', GREATEST(CHAR_LENGTH(u.name)-2,2)), RIGHT(u.name,1)) AS anon_name
   FROM applications a JOIN users u ON a.candidate_id=u.id
   WHERE a.task_id=$id AND a.status != 'rejected'
   ORDER BY a.bid_amount ASC");
$bids_arr = [];
while($b = $bids_res->fetch_assoc()) $bids_arr[] = $b;

$page_title = htmlspecialchars($task['title']);
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap" style="padding-top:90px;padding-bottom:60px">
  <div class="ctr">
    <a href="browse_tasks.php" style="display:inline-block;color:var(--t3);font-size:13.5px;margin-bottom:28px">← Back to Tasks</a>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:28px;align-items:start">

      <!-- ── LEFT: Task Detail ── -->
      <div>
        <div class="glass-dark" style="padding:32px;border-radius:var(--r);margin-bottom:24px">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px">
            <div style="flex:1">
              <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px">
                <?php
                  $urg = $task['urgency'] ?? 'medium';
                  $urg_cls = ['urgent'=>'tag-r','high'=>'tag-a','medium'=>'tag-v','low'=>'tag-g'][$urg] ?? 'tag-v';
                ?>
                <span class="tag <?= $urg_cls ?>"><?= ucfirst($urg) ?> Priority</span>
                <span class="tag tag-c"><?= htmlspecialchars($task['category']) ?></span>
                <span class="tag <?= $task['status']==='open'?'tag-g':'tag-r' ?>"><?= ucfirst($task['status']) ?></span>
              </div>
              <h1 style="font-family:var(--fh);font-size:1.55rem;font-weight:700;margin-bottom:10px"><?= htmlspecialchars($task['title']) ?></h1>
              <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                <span style="color:var(--t3);font-size:13.5px">
                  🏢 <strong style="color:var(--t2)"><?= htmlspecialchars($task['company_name']?:$task['employer_name']) ?></strong>
                  &nbsp;·&nbsp; Posted <?= date('d M Y',strtotime($task['created_at'])) ?>
                </span>
                <?php if($logged_in && $role==='candidate'): ?>
                  <a href="task_view.php?id=<?= $id ?>&save=1" class="btn btn-ghost btn-sm">
                    <?= $is_saved ? '🔖 Saved' : '🔖 Save Task' ?>
                  </a>
                <?php endif; ?>
              </div>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <div style="font-family:var(--fh);font-size:2rem;font-weight:800;color:var(--emerald)">₹<?= number_format($task['price']) ?></div>
              <div style="font-size:12px;color:var(--t3)">Task Budget</div>
              <?php
                $deadline = $task['deadline'];
                if($deadline){
                  $days_left = ceil((strtotime($deadline) - time()) / 86400);
                  $dl_color  = $days_left <= 3 ? 'var(--rose)' : ($days_left <= 7 ? 'var(--amber)' : 'var(--t3)');
                  echo "<div style='font-size:12px;color:$dl_color;margin-top:4px'>⏱ $days_left days left</div>";
                }
              ?>
            </div>
          </div>

          <!-- Description -->
          <div style="color:var(--t2);line-height:1.85;font-size:14.5px;margin-bottom:24px;white-space:pre-line"><?= htmlspecialchars($task['description']) ?></div>

          <!-- Skills -->
          <?php if($skills): ?>
          <div style="margin-bottom:22px">
            <div style="font-size:11.5px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:10px">Skills Required</div>
            <div style="display:flex;flex-wrap:wrap;gap:8px">
              <?php foreach($skills as $sk): if(trim($sk)): ?><span class="tag tag-v"><?= htmlspecialchars($sk) ?></span><?php endif; endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Meta grid -->
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
            <?php foreach([
              ['📅','Deadline', $deadline ? date('d M Y',strtotime($deadline)) : 'Flexible'],
              ['📍','Location', $task['location'] ?: 'Remote'],
              ['⏱','Duration', $task['duration'] ?: 'Flexible'],
            ] as [$icon,$label,$val]): ?>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:14px">
              <div style="font-size:11px;color:var(--t3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px"><?= $icon ?> <?= $label ?></div>
              <div style="font-weight:600;font-size:14px;color:var(--t1)"><?= htmlspecialchars($val) ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Bid summary table -->
        <?php if(count($bids_arr) > 0): ?>
        <div class="glass-dark" style="padding:24px;border-radius:var(--r)">
          <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:16px">📊 <?= count($bids_arr) ?> Bids Placed</h3>
          <div style="overflow-x:auto">
            <table class="tbl">
              <thead><tr><th>Bidder</th><th>Amount</th><th>Delivery</th><th>College</th></tr></thead>
              <tbody>
                <?php foreach($bids_arr as $b): ?>
                <tr>
                  <td><?= htmlspecialchars($b['anon_name']) ?></td>
                  <td style="color:var(--emerald);font-weight:700">₹<?= number_format($b['bid_amount']) ?></td>
                  <td><?= $b['delivery_days'] ?> days</td>
                  <td style="color:var(--t3);font-size:13px"><?= htmlspecialchars($b['university'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php
            $amounts = array_column($bids_arr,'bid_amount');
            $avg = array_sum($amounts)/max(1,count($amounts));
            $low = min($amounts);
          ?>
          <div style="display:flex;gap:12px;margin-top:14px;flex-wrap:wrap">
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 16px;font-size:13px">
              Avg Bid: <strong style="color:var(--violet2)">₹<?= number_format($avg) ?></strong>
            </div>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:10px 16px;font-size:13px">
              Lowest: <strong style="color:var(--emerald)">₹<?= number_format($low) ?></strong>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- ── RIGHT: Bid Panel ── -->
      <div style="position:sticky;top:100px">
        <?php if(!$logged_in): ?>
          <div class="glass" style="padding:28px;text-align:center;border-radius:var(--r)">
            <div style="font-size:3rem;margin-bottom:12px">🔐</div>
            <h3 style="font-family:var(--fh);margin-bottom:8px">Login to Bid</h3>
            <p style="color:var(--t3);font-size:14px;margin-bottom:20px">Create a free account to bid on tasks</p>
            <a href="login.php?next=task_view.php?id=<?= $id ?>" class="btn btn-brand btn-full btn-lg">Login / Sign Up</a>
          </div>
        <?php elseif($role === 'employer'): ?>
          <div class="glass" style="padding:28px;text-align:center;border-radius:var(--r)">
            <div style="font-size:2.5rem;margin-bottom:10px">🏢</div>
            <p style="color:var(--t2);font-size:14px">Employers cannot bid.<br>Switch to a student account.</p>
          </div>
        <?php elseif($task['status'] !== 'open'): ?>
          <div class="glass" style="padding:28px;text-align:center;border-radius:var(--r)">
            <div style="font-size:2.5rem;margin-bottom:10px">🔒</div>
            <p style="color:var(--t2)">This task is no longer accepting bids.</p>
          </div>
        <?php elseif($already_applied): ?>
          <div class="glass" style="padding:28px;border-radius:var(--r)">
            <div class="alert alert-ok">✅ Your bid is submitted!</div>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:16px">
              <div style="font-size:12px;color:var(--t3);margin-bottom:4px">YOUR BID</div>
              <div style="font-size:1.5rem;font-weight:800;color:var(--emerald)">₹<?= number_format($my_bid['bid_amount']) ?></div>
              <div style="color:var(--t3);font-size:13px"><?= $my_bid['delivery_days'] ?> days delivery</div>
              <div style="margin-top:10px">
                <span class="tag <?= ['pending'=>'tag-a','selected'=>'tag-g','rejected'=>'tag-r','completed'=>'tag-c'][$my_bid['status']]??'tag-v' ?>"><?= ucfirst($my_bid['status']) ?></span>
              </div>
            </div>
          </div>
        <?php else: ?>
          <div class="glass" style="padding:28px;border-radius:var(--r)">
            <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:4px">🚀 Place Your Bid</h3>
            <p style="color:var(--t3);font-size:13px;margin-bottom:20px">
              Budget: <strong style="color:var(--emerald)">₹<?= number_format($task['price']) ?></strong>
              &nbsp;·&nbsp; Max bid: <strong style="color:var(--amber)">₹<?= number_format($task['price']*1.5) ?></strong>
            </p>

            <?php if($bid_error): ?>
              <div class="alert alert-err" style="margin-bottom:16px"><?= htmlspecialchars($bid_error) ?></div>
            <?php endif; ?>

            <form method="POST">
              <input type="hidden" id="task_budget_val" value="<?= $task['price'] ?>">

              <div class="fg">
                <label class="flabel">Bid Amount (₹) <span style="color:var(--rose)">*</span></label>
                <div class="fwrap"><span class="ficon">💰</span>
                  <input type="number" id="bid_amount" name="bid_amount" class="finput"
                    placeholder="e.g. <?= $task['price'] ?>" min="100"
                    max="<?= round($task['price']*1.5) ?>" step="50" required
                    value="<?= htmlspecialchars($_POST['bid_amount'] ?? '') ?>">
                </div>
                <div id="bid-msg" class="fhelp">Min ₹100 · Max ₹<?= number_format($task['price']*1.5) ?></div>
              </div>

              <div class="fg">
                <label class="flabel">Delivery Days <span style="color:var(--rose)">*</span></label>
                <input type="number" name="delivery_days" class="finput"
                  placeholder="e.g. 7" min="1" max="90" required
                  value="<?= htmlspecialchars($_POST['delivery_days'] ?? '') ?>">
                <div class="fhelp">Between 1–90 days</div>
              </div>

              <div class="fg">
                <label class="flabel">Proposal <span style="color:var(--rose)">*</span></label>
                <textarea name="proposal" id="proposal_txt" class="finput" rows="5"
                  placeholder="Why are you the best fit? Mention relevant skills, experience, and your approach… (min 50 chars)"
                  required><?= htmlspecialchars($_POST['proposal'] ?? '') ?></textarea>
                <div style="display:flex;justify-content:space-between">
                  <div class="fhelp">Min 50 characters</div>
                  <div class="fhelp" id="prop-count">0/2000</div>
                </div>
              </div>

              <button type="submit" name="submit_bid" class="btn btn-brand btn-full btn-lg">🚀 Submit Bid</button>
              <p style="color:var(--t3);font-size:11.5px;text-align:center;margin-top:10px">Bids are binding once submitted.</p>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
// Live bid validation
const bidInput = document.getElementById('bid_amount');
const budgetEl = document.getElementById('task_budget_val');
if(bidInput && budgetEl){
  bidInput.addEventListener('input',()=>{
    const budget = parseFloat(budgetEl.value);
    const bid    = parseFloat(bidInput.value);
    const msg    = document.getElementById('bid-msg');
    if(!msg) return;
    if(!bid||bid<100){ msg.className='ferr'; msg.textContent='❌ Min bid is ₹100.'; }
    else if(bid>budget*1.5){ msg.className='ferr'; msg.textContent='❌ Exceeds max (₹'+Math.round(budget*1.5).toLocaleString('en-IN')+').'; }
    else if(bid>budget){ msg.className='fhelp'; msg.style.color='var(--amber)'; msg.textContent='⚠️ Above budget — employer may negotiate.'; }
    else { msg.className='fhelp'; msg.style.color='var(--emerald)'; msg.textContent='✅ Bid looks good!'; }
  });
}
// Proposal char counter
const ta = document.getElementById('proposal_txt');
const pc = document.getElementById('prop-count');
if(ta&&pc){
  function upd(){ const n=ta.value.length; pc.textContent=n+'/2000'; pc.style.color=n>1800?'var(--rose)':n>1500?'var(--amber)':''; if(n>2000)ta.value=ta.value.slice(0,2000); }
  ta.addEventListener('input',upd); upd();
}
</script>
<?php include 'includes/footer.php'; ?>
