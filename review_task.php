<?php
session_start();
include 'db.php';
requireLogin();

$uid    = (int)$_SESSION['user_id'];
$app_id = intval($_GET['app_id'] ?? 0);

if (!$app_id) {
    $_SESSION['flash_error'] = 'Invalid review link.';
    header("Location: index.php"); exit();
}

// Allow selected OR completed — employer can review after selecting (before/after payment)
$app = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT a.id, a.task_id, a.candidate_id, a.bid_amount, a.status,
          a.submission_link,
          t.title, t.employer_id,
          u_c.name AS candidate_name,
          u_e.name AS employer_name
   FROM applications a
   JOIN tasks t   ON t.id = a.task_id
   JOIN users u_c ON u_c.id = a.candidate_id
   JOIN users u_e ON u_e.id = t.employer_id
   WHERE a.id = $app_id
     AND a.status IN ('selected','completed')
   LIMIT 1"));

if (!$app) {
    $raw = mysqli_fetch_assoc(mysqli_query($conn,
      "SELECT a.status FROM applications a WHERE a.id=$app_id LIMIT 1"));
    $_SESSION['flash_error'] = $raw
        ? 'Review not available yet — status is "' . $raw['status'] . '". Work must be submitted and selected first.'
        : 'Application not found.';
    header("Location: index.php"); exit();
}

$is_employer  = ((int)$uid === (int)$app['employer_id']);
$is_candidate = ((int)$uid === (int)$app['candidate_id']);

if (!$is_employer && !$is_candidate) {
    $_SESSION['flash_error'] = 'You are not part of this task.';
    header("Location: index.php"); exit();
}

$reviewee_id   = $is_employer ? (int)$app['candidate_id'] : (int)$app['employer_id'];
$reviewee_name = $is_employer ? $app['candidate_name']    : $app['employer_name'];
$task_id       = (int)$app['task_id'];
$back_url      = $is_employer ? "view_applicants.php?id=$task_id" : "my_applications.php";

// Already reviewed?
$already = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT id FROM reviews WHERE task_id=$task_id AND reviewer_id=$uid LIMIT 1"));
if ($already) {
    $_SESSION['flash_error'] = 'You have already submitted a review for this task.';
    header("Location: $back_url"); exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = intval($_POST['rating']  ?? 0);
    $comment = trim($_POST['comment']   ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please click a star to select your rating (1–5).';
    } elseif (strlen($comment) < 20) {
        $error = 'Please write at least 20 characters of feedback.';
    } elseif (strlen($comment) > 1000) {
        $error = 'Review cannot exceed 1000 characters.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO reviews (task_id, reviewer_id, reviewee_id, rating, comment) VALUES (?,?,?,?,?)"
        );
        $stmt->bind_param("iiiis", $task_id, $uid, $reviewee_id, $rating, $comment);
        if ($stmt->execute()) {
            $safe_msg = $conn->real_escape_string("⭐ New {$rating}-star review on: {$app['title']}");
            $conn->query("INSERT INTO notifications (user_id,message,link) VALUES ($reviewee_id,'$safe_msg','profile.php')");
            $_SESSION['flash_success'] = 'Review submitted — thank you! ⭐';
            header("Location: $back_url"); exit();
        } else {
            $error = 'DB error: ' . $conn->error;
        }
    }
}

$page_title = 'Leave a Review';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div style="max-width:560px;margin:0 auto;padding:110px 20px 60px">
    <a href="<?= $back_url ?>" style="color:var(--t3);font-size:13.5px;display:inline-block;margin-bottom:28px">← Back</a>

    <div style="text-align:center;margin-bottom:32px">
      <div class="tag tag-a" style="margin-bottom:12px">⭐ Review</div>
      <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700">Leave a Review</h1>
      <p style="color:var(--t3);margin-top:8px">for <strong style="color:var(--t1)"><?= htmlspecialchars($reviewee_name) ?></strong></p>
      <p style="color:var(--t3);font-size:13px;margin-top:4px">Task: <?= htmlspecialchars($app['title']) ?></p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-err" style="margin-bottom:20px">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="glass" style="padding:36px;border-radius:var(--r)">
      <form method="POST" id="reviewForm">

        <div style="text-align:center;margin-bottom:28px">
          <label class="flabel" style="display:block;margin-bottom:16px">
            Your Rating <span style="color:var(--rose)">*</span>
          </label>
          <div id="starRow" style="display:flex;justify-content:center;gap:12px">
            <?php for ($i=1;$i<=5;$i++): ?>
              <button type="button" class="star-pick" data-val="<?= $i ?>"
                style="background:none;border:none;font-size:2.8rem;cursor:pointer;color:var(--t4);padding:0;line-height:1;transition:color .15s,transform .15s">★</button>
            <?php endfor; ?>
          </div>
          <div id="star-label" style="color:var(--t3);font-size:13px;margin-top:12px;min-height:22px">Click a star to rate</div>
          <input type="hidden" name="rating" id="ratingInput" value="<?= intval($_POST['rating']??0) ?>">
        </div>

        <div class="fg">
          <label class="flabel">Written Review <span style="color:var(--rose)">*</span></label>
          <textarea name="comment" id="review_comment" class="finput" rows="5"
            placeholder="Share your experience — quality of work, communication, professionalism (min 20 chars)…"><?= htmlspecialchars($_POST['comment']??'') ?></textarea>
          <div style="display:flex;justify-content:space-between;margin-top:5px">
            <div class="fhelp">Min 20 characters</div>
            <div class="fhelp" id="charCount">0/1000</div>
          </div>
        </div>

        <button type="submit" class="btn btn-brand btn-full btn-lg" style="margin-top:8px">⭐ Submit Review</button>
      </form>
    </div>
  </div>
</div>

<style>
.star-pick:hover { color:var(--amber)!important; transform:scale(1.2); }
</style>

<script>
(function(){
  const stars = document.querySelectorAll('.star-pick');
  const input = document.getElementById('ratingInput');
  const label = document.getElementById('star-label');
  const labs  = ['','Poor 😞','Fair 😐','Good 🙂','Very Good 😊','Excellent! 🤩'];

  function paint(v){
    stars.forEach((s,i)=>{ s.style.color = i<v ? 'var(--amber)' : 'var(--t4)'; });
    label.textContent = v ? v+' star'+(v>1?'s':'')+' — '+labs[v] : 'Click a star to rate';
    label.style.color = v ? 'var(--amber)' : 'var(--t3)';
  }
  const init = parseInt(input.value); if(init) paint(init);

  stars.forEach(btn=>{
    btn.addEventListener('click',()=>{ input.value=btn.dataset.val; paint(+btn.dataset.val); });
    btn.addEventListener('mouseenter',()=>{ const v=+btn.dataset.val; stars.forEach((s,i)=>s.style.color=i<v?'var(--amber)':'var(--t4)'); });
    btn.addEventListener('mouseleave',()=>paint(+input.value||0));
  });

  const ta=document.getElementById('review_comment'), cc=document.getElementById('charCount');
  function cnt(){ const n=ta.value.length; cc.textContent=n+'/1000'; cc.style.color=n>900?'var(--rose)':n>700?'var(--amber)':''; if(n>1000)ta.value=ta.value.slice(0,1000); }
  ta.addEventListener('input',cnt); cnt();

  document.getElementById('reviewForm').addEventListener('submit',e=>{
    if(!+input.value){
      e.preventDefault();
      label.textContent='⚠️ Please select a star rating first!';
      label.style.color='var(--rose)';
      document.getElementById('starRow').scrollIntoView({behavior:'smooth',block:'center'});
    }
  });
})();
</script>
<?php include 'includes/footer.php'; ?>
