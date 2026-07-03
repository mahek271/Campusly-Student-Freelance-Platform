<?php
session_start();
include 'db.php';
requireRole('employer');
$uid = (int)$_SESSION['user_id'];

$error = '';
$categories = ['Design','Development','Content Writing','Marketing','Research','Video & Animation','Data Analysis','Other'];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $title    = trim($_POST['title']    ?? '');
  $desc     = trim($_POST['description'] ?? '');
  $price    = floatval($_POST['price'] ?? 0);
  $category = $_POST['category'] ?? '';
  $location = trim($_POST['location'] ?? 'Remote');
  $skills   = trim($_POST['skills']   ?? '');
  $deadline = $_POST['deadline'] ?? '';
  $duration = trim($_POST['duration'] ?? '');
  $urgency  = $_POST['urgency'] ?? 'medium';

  // Validations
  if(!$title || !$desc || !$price || !$category){
    $error='Please fill all required fields.';
  } elseif(strlen($title) < 10){
    $error='Title must be at least 10 characters.';
  } elseif(strlen($desc) < 50){
    $error='Description must be at least 50 characters. Be specific about requirements!';
  } elseif($price < 100){
    $error='Minimum task budget is ₹100.';
  } elseif($price > 500000){
    $error='Maximum task budget is ₹5,00,000.';
  } elseif(!$deadline){
    $error='Deadline is required. Please set a deadline for the task.';
  } elseif(strtotime($deadline) < strtotime('+2 days')){
    $error='Deadline must be at least 2 days from today.';
  } elseif(!in_array($urgency,['urgent','high','medium','low'])){
    $error='Invalid urgency level.';
  } else {
    $stmt=$conn->prepare("INSERT INTO tasks (employer_id,title,description,price,category,location,skills,deadline,duration,urgency,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,'open',NOW())");
    $stmt->bind_param("issdssssss",$uid,$title,$desc,$price,$category,$location,$skills,$deadline,$duration,$urgency);
    if($stmt->execute()){
      $_SESSION['flash_success']='Task posted successfully! 🎉 Students can now bid.';
      header("Location: employer_dashboard.php"); exit();
    } else {
      $error='Failed to post task. Please try again.';
    }
  }
}

$page_title='Post a Task';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div style="max-width:740px;margin:0 auto;padding:100px 20px 60px">
    <div style="text-align:center;margin-bottom:36px">
      <div class="tag tag-g" style="margin-bottom:12px">🚀 New Task</div>
      <h1 style="font-family:var(--fh);font-size:2rem;font-weight:700">Post a Task</h1>
      <p style="color:var(--t3);margin-top:8px">Connect with talented students. Be specific to get the best bids.</p>
    </div>

    <?php if($error): ?>
      <div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="glass" style="padding:36px;border-radius:var(--rl)">
      <form method="POST">
        <div class="fg">
          <label class="flabel">Task Title <span style="color:var(--rose)">*</span></label>
          <input type="text" name="title" class="finput" placeholder="e.g. Design a landing page for our SaaS product" required value="<?= htmlspecialchars($_POST['title']??'') ?>">
          <div class="fhelp">Be specific and descriptive (min 10 characters)</div>
        </div>

        <div class="grid-2">
          <div class="fg">
            <label class="flabel">Category <span style="color:var(--rose)">*</span></label>
            <select name="category" class="finput" required>
              <option value="">Select category…</option>
              <?php foreach($categories as $cat): ?>
                <option value="<?= $cat ?>" <?= ($_POST['category']??'')===$cat?'selected':'' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg">
            <label class="flabel">Urgency Level</label>
            <select name="urgency" class="finput">
              <option value="low"    <?= ($_POST['urgency']??'')==='low'?'selected':'' ?>>🟢 Low</option>
              <option value="medium" <?= ($_POST['urgency']??'medium')==='medium'?'selected':'' ?>>🟡 Medium</option>
              <option value="high"   <?= ($_POST['urgency']??'')==='high'?'selected':'' ?>>🟠 High</option>
              <option value="urgent" <?= ($_POST['urgency']??'')==='urgent'?'selected':'' ?>>🔴 Urgent</option>
            </select>
          </div>
        </div>

        <div class="fg">
          <label class="flabel">Task Description <span style="color:var(--rose)">*</span></label>
          <textarea name="description" id="desc" class="finput" rows="6" placeholder="Describe the task in detail: what you need, deliverables, any specific requirements, reference links…" data-maxlen="3000" required><?= htmlspecialchars($_POST['description']??'') ?></textarea>
          <div style="display:flex;justify-content:space-between">
            <div class="fhelp">Min 50 characters. More detail = better proposals.</div>
            <div class="fhelp" data-counter="desc">0/3000</div>
          </div>
        </div>

        <div class="grid-2">
          <div class="fg">
            <label class="flabel">Budget (₹) <span style="color:var(--rose)">*</span></label>
            <div class="fwrap"><span class="ficon">💰</span>
              <input type="number" name="price" class="finput" placeholder="e.g. 5000" min="100" max="500000" step="100" required value="<?= htmlspecialchars($_POST['price']??'') ?>">
            </div>
            <div class="fhelp">Min ₹100 · Max ₹5,00,000. Students bid up to 150% of this.</div>
          </div>
          <div class="fg">
            <label class="flabel">Location</label>
            <select name="location" class="finput">
              <option value="Remote" <?= ($_POST['location']??'Remote')==='Remote'?'selected':'' ?>>🌐 Remote</option>
              <option value="On-site" <?= ($_POST['location']??'')==='On-site'?'selected':'' ?>>🏢 On-site</option>
              <option value="Hybrid"  <?= ($_POST['location']??'')==='Hybrid'?'selected':'' ?>>🔄 Hybrid</option>
            </select>
          </div>
        </div>

        <div class="grid-2">
          <div class="fg">
            <label class="flabel">Deadline <span style="color:var(--rose)">*</span> <span style="color:var(--rose);font-size:10px">(Required)</span></label>
            <input type="date" name="deadline" class="finput" required
              min="<?= date('Y-m-d', strtotime('+2 days')) ?>"
              value="<?= htmlspecialchars($_POST['deadline']??'') ?>"
              oninvalid="this.setCustomValidity('Deadline is required — please set a date at least 2 days from today.')"
              oninput="this.setCustomValidity('')">
            <div class="fhelp" style="color:var(--rose)">⚠️ Required · Must be at least 2 days from today</div>
          </div>
          <div class="fg">
            <label class="flabel">Estimated Duration</label>
            <input type="text" name="duration" class="finput" placeholder="e.g. 3–5 days" value="<?= htmlspecialchars($_POST['duration']??'') ?>">
          </div>
        </div>

        <div class="fg">
          <label class="flabel">Required Skills</label>
          <input type="text" name="skills" class="finput" placeholder="e.g. Figma, Photoshop, UI Design (comma separated)" value="<?= htmlspecialchars($_POST['skills']??'') ?>">
        </div>

        <!-- Budget preview -->
        <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:12px;padding:16px;margin-bottom:24px">
          <div style="font-size:12px;font-weight:700;color:var(--emerald);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">💡 Bidding Rules (Students will see this)</div>
          <ul style="list-style:none;display:flex;flex-direction:column;gap:6px;font-size:13.5px;color:var(--t2)">
            <li>✅ Minimum bid: <strong>₹100</strong></li>
            <li>✅ Maximum bid: <strong>150% of your budget</strong></li>
            <li>✅ Minimum proposal length: <strong>50 characters</strong></li>
            <li>✅ Delivery: <strong>1–90 days</strong></li>
            <li>✅ Payment released only after your approval</li>
          </ul>
        </div>

        <button type="submit" class="btn btn-brand btn-full btn-xl">🚀 Post Task</button>
      </form>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
