<?php
session_start();
include 'db.php';
requireRole('employer');

$uid    = (int)$_SESSION['user_id'];
$tid    = intval($_GET['id'] ?? 0);
if(!$tid){ header("Location: manage_tasks.php"); exit(); }

$task = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT * FROM tasks WHERE id=$tid AND employer_id=$uid LIMIT 1"));
if(!$task){ $_SESSION['flash_error']='Task not found.'; header("Location: manage_tasks.php"); exit(); }
if($task['status'] !== 'open'){
  $_SESSION['flash_error']='Only open tasks can be edited. Assigned or completed tasks cannot be modified.';
  header("Location: manage_tasks.php"); exit();
}

$error = '';
$categories = ['Design','Development','Content Writing','Marketing','Research','Video & Animation','Data Analysis','Other'];

if($_SERVER['REQUEST_METHOD']==='POST'){
  $title    = trim($_POST['title']       ?? '');
  $desc     = trim($_POST['description'] ?? '');
  $price    = floatval($_POST['price']   ?? 0);
  $category = $_POST['category']         ?? '';
  $location = trim($_POST['location']    ?? 'Remote');
  $skills   = trim($_POST['skills']      ?? '');
  $deadline = $_POST['deadline']         ?? '';
  $duration = trim($_POST['duration']    ?? '');
  $urgency  = $_POST['urgency']          ?? 'medium';

  // Check if bids exist — restrict price reduction
  $bid_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM applications WHERE task_id=$tid"))['c'];

  if(!$title || !$desc || !$price || !$category)       { $error='All required fields must be filled.'; }
  elseif(strlen($title)<10)                             { $error='Title must be at least 10 characters.'; }
  elseif(strlen($desc)<50)                             { $error='Description must be at least 50 characters.'; }
  elseif($price < 100)                                  { $error='Minimum budget is ₹100.'; }
  elseif($price > 500000)                               { $error='Maximum budget is ₹5,00,000.'; }
  elseif($bid_count > 0 && $price < $task['price'])    { $error='Cannot reduce budget after bids have been placed. '.$bid_count.' bid(s) already received.'; }
  elseif(!$deadline)                                    { $error='Deadline is required.'; }
  elseif(strtotime($deadline) < strtotime('+1 day'))   { $error='Deadline must be at least 1 day from today.'; }
  else {
    $stmt = $conn->prepare("UPDATE tasks SET title=?,description=?,price=?,category=?,location=?,skills=?,deadline=?,duration=?,urgency=? WHERE id=? AND employer_id=?");
    $stmt->bind_param("ssdssssssii",$title,$desc,$price,$category,$location,$skills,$deadline,$duration,$urgency,$tid,$uid);
    if($stmt->execute()){
      // Notify existing bidders of changes
      if($bid_count > 0){
        $bidders=mysqli_query($conn,"SELECT DISTINCT candidate_id FROM applications WHERE task_id=$tid AND status='pending'");
        while($b=$bidders->fetch_assoc()){
          $safe=addslashes($title);
          $conn->query("INSERT INTO notifications (user_id,message,link) VALUES ({$b['candidate_id']},'📝 Task you bid on has been updated: $safe','task_view.php?id=$tid')");
        }
      }
      $_SESSION['flash_success']='Task updated successfully! ✅';
      header("Location: manage_tasks.php"); exit();
    } else {
      $error='Update failed: '.$conn->error;
    }
  }
  // Update $task with POST values for re-display
  $task=array_merge($task,compact('title','desc','price','category','location','skills','deadline','duration','urgency'));
  $task['description']=$desc;
}

$bid_count = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM applications WHERE task_id=$tid"))['c'];

$page_title='Edit Task — '.htmlspecialchars($task['title']);
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div style="max-width:740px;margin:0 auto;padding:100px 20px 60px">
    <a href="manage_tasks.php" style="color:var(--t3);font-size:13.5px;display:inline-block;margin-bottom:24px">← Back to Tasks</a>

    <div style="text-align:center;margin-bottom:32px">
      <div class="tag tag-a" style="margin-bottom:12px">✏️ Edit Task</div>
      <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700">Edit Task</h1>
      <?php if($bid_count>0): ?>
        <div class="alert alert-warn" style="margin-top:14px">
          ⚠️ This task has <strong><?= $bid_count ?> bid<?= $bid_count!=1?'s':'' ?></strong> already. Bidders will be notified of any changes. You <strong>cannot reduce</strong> the budget.
        </div>
      <?php endif; ?>
    </div>

    <?php if($error): ?><div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="glass" style="padding:36px;border-radius:var(--rl)">
      <form method="POST">
        <div class="fg">
          <label class="flabel">Task Title <span style="color:var(--rose)">*</span></label>
          <input type="text" name="title" class="finput" required
            value="<?= htmlspecialchars($task['title']) ?>">
        </div>

        <div class="grid-2">
          <div class="fg">
            <label class="flabel">Category <span style="color:var(--rose)">*</span></label>
            <select name="category" class="finput" required>
              <option value="">Select…</option>
              <?php foreach($categories as $cat): ?>
                <option value="<?= $cat ?>" <?= $task['category']===$cat?'selected':'' ?>><?= $cat ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg">
            <label class="flabel">Urgency</label>
            <select name="urgency" class="finput">
              <?php foreach(['low'=>'🟢 Low','medium'=>'🟡 Medium','high'=>'🟠 High','urgent'=>'🔴 Urgent'] as $v=>$l): ?>
                <option value="<?= $v ?>" <?= $task['urgency']===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="fg">
          <label class="flabel">Description <span style="color:var(--rose)">*</span></label>
          <textarea name="description" class="finput" rows="7" required><?= htmlspecialchars($task['description']) ?></textarea>
        </div>

        <div class="grid-2">
          <div class="fg">
            <label class="flabel">Budget (₹) <span style="color:var(--rose)">*</span></label>
            <div class="fwrap"><span class="ficon">💰</span>
              <input type="number" name="price" class="finput" min="100" max="500000" step="100" required
                value="<?= $task['price'] ?>">
            </div>
            <?php if($bid_count>0): ?><div class="fhelp" style="color:var(--amber)">⚠️ Cannot reduce — bids already placed</div><?php endif; ?>
          </div>
          <div class="fg">
            <label class="flabel">Location</label>
            <select name="location" class="finput">
              <option value="Remote"  <?= $task['location']==='Remote'?'selected':'' ?>>🌐 Remote</option>
              <option value="On-site" <?= $task['location']==='On-site'?'selected':'' ?>>🏢 On-site</option>
              <option value="Hybrid"  <?= $task['location']==='Hybrid'?'selected':'' ?>>🔄 Hybrid</option>
            </select>
          </div>
        </div>

        <div class="grid-2">
          <div class="fg">
            <label class="flabel">Deadline <span style="color:var(--rose)">*</span></label>
            <input type="date" name="deadline" class="finput" required
              min="<?= date('Y-m-d',strtotime('+1 day')) ?>"
              value="<?= htmlspecialchars($task['deadline']??'') ?>">
          </div>
          <div class="fg">
            <label class="flabel">Estimated Duration</label>
            <input type="text" name="duration" class="finput" placeholder="e.g. 3–5 days"
              value="<?= htmlspecialchars($task['duration']??'') ?>">
          </div>
        </div>

        <div class="fg">
          <label class="flabel">Required Skills</label>
          <input type="text" name="skills" class="finput" placeholder="React, Figma, Python…"
            value="<?= htmlspecialchars($task['skills']??'') ?>">
        </div>

        <div style="display:flex;gap:12px">
          <button type="submit" class="btn btn-brand" style="flex:1">💾 Save Changes</button>
          <a href="manage_tasks.php" class="btn btn-ghost">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
