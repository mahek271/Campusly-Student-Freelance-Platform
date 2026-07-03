<?php
/**
 * portfolio.php — Student Portfolio Builder
 * Students can add portfolio items (past projects) with title,
 * description, URL, image URL, and tags.
 * Displayed on public profile for employers to browse.
 */
session_start();
include 'db.php';
requireRole('candidate');
$uid = (int)$_SESSION['user_id'];

$error=$success='';

// Handle add/delete
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(isset($_POST['add_item'])){
    $title = trim($_POST['title']       ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $url   = trim($_POST['url']         ?? '');
    $img   = trim($_POST['image_url']   ?? '');
    $tags  = trim($_POST['tags']        ?? '');
    if(!$title)                 { $error='Project title is required.'; }
    elseif(strlen($desc)<20)    { $error='Please describe the project (min 20 chars).'; }
    else {
      $stmt=$conn->prepare("INSERT INTO portfolio_items (user_id,title,description,url,image_url,tags,created_at) VALUES (?,?,?,?,?,?,NOW())");
      $stmt->bind_param("isssss",$uid,$title,$desc,$url,$img,$tags);
      if($stmt->execute()) $success='Portfolio item added! ✅';
      else $error='Failed: '.$conn->error;
    }
  }
  if(isset($_POST['delete_item'])){
    $item_id=intval($_POST['item_id']);
    $conn->query("DELETE FROM portfolio_items WHERE id=$item_id AND user_id=$uid");
    $success='Item removed.';
  }
}

// Load items
$items=mysqli_query($conn,"SELECT * FROM portfolio_items WHERE user_id=$uid ORDER BY created_at DESC");
$count=mysqli_num_rows($items);

$page_title='My Portfolio';
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
        <a href="portfolio.php" class="sidebar-link active"><span class="si">🖼️</span>Portfolio</a>
        <a href="messages.php" class="sidebar-link"><span class="si">💬</span>Messages</a>
        <a href="leaderboard.php" class="sidebar-link"><span class="si">🏆</span>Leaderboard</a>
        <a href="notifications.php" class="sidebar-link"><span class="si">🔔</span>Notifications</a>
        <a href="notification_settings.php" class="sidebar-link"><span class="si">⚙️</span>Settings</a>
        <a href="profile.php" class="sidebar-link"><span class="si">👤</span>Profile</a>
        <a href="logout.php" class="sidebar-link" style="color:var(--rose)"><span class="si">🚪</span>Logout</a>
      </div>
  </aside>

  <main class="main-content">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
      <div>
        <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700;margin-bottom:4px">🖼️ My Portfolio</h1>
        <p style="color:var(--t3)"><?= $count ?> project<?= $count!=1?'s':'' ?> showcased — visible to employers on your profile</p>
      </div>
      <button onclick="openModal('addModal')" class="btn btn-brand">+ Add Project</button>
    </div>

    <?php if($error):  ?><div class="alert alert-err">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if($success):?><div class="alert alert-ok">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if($count===0): ?>
      <div style="text-align:center;padding:80px;color:var(--t3)">
        <div style="font-size:4rem;margin-bottom:16px">🖼️</div>
        <h3 style="font-family:var(--fh);margin-bottom:8px">No portfolio items yet</h3>
        <p style="margin-bottom:24px">Showcase your best work to attract employers</p>
        <button onclick="openModal('addModal')" class="btn btn-brand">Add Your First Project</button>
      </div>
    <?php else: ?>
      <div class="grid-2" style="gap:20px">
        <?php while($item=$items->fetch_assoc()):
          $tags_arr = $item['tags'] ? array_map('trim',explode(',',$item['tags'])) : [];
        ?>
        <div class="card">
          <?php if($item['image_url']): ?>
            <div style="height:160px;background:var(--ink4);border-radius:10px;margin-bottom:16px;overflow:hidden">
              <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>"
                style="width:100%;height:100%;object-fit:cover" onerror="this.parentElement.innerHTML='<div style=\'padding:60px 0;text-align:center;font-size:2rem\'>🖼️</div>'">
            </div>
          <?php else: ?>
            <div style="height:80px;background:linear-gradient(135deg,rgba(124,92,252,0.15),rgba(0,212,255,0.1));border-radius:10px;margin-bottom:16px;display:flex;align-items:center;justify-content:center;font-size:2rem">
              💡
            </div>
          <?php endif; ?>

          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
            <h3 style="font-family:var(--fh);font-weight:700;font-size:1rem"><?= htmlspecialchars($item['title']) ?></h3>
            <form method="POST" style="margin:0">
              <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
              <button type="submit" name="delete_item" onclick="return confirm('Remove this project?')"
                class="btn btn-danger btn-sm" style="padding:4px 10px">🗑</button>
            </form>
          </div>

          <p style="color:var(--t2);font-size:13.5px;line-height:1.6;margin-bottom:12px"><?= htmlspecialchars(substr($item['description'],0,150)) ?><?= strlen($item['description'])>150?'…':'' ?></p>

          <?php if($tags_arr): ?>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
              <?php foreach($tags_arr as $tag): if(trim($tag)): ?><span class="tag tag-v" style="font-size:11px"><?= htmlspecialchars($tag) ?></span><?php endif; endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if($item['url']): ?>
            <a href="<?= htmlspecialchars($item['url']) ?>" target="_blank" class="btn btn-glass btn-sm">🔗 View Project</a>
          <?php endif; ?>

          <div style="color:var(--t4);font-size:11px;margin-top:10px"><?= date('d M Y',strtotime($item['created_at'])) ?></div>
        </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
</div>

<!-- Add Portfolio Modal -->
<div id="addModal" class="overlay">
  <div class="mbox mbox-wide" style="max-width:560px">
    <button class="mclose" onclick="closeModal('addModal')">✕</button>
    <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:20px">➕ Add Portfolio Project</h3>
    <form method="POST">
      <div class="fg">
        <label class="flabel">Project Title <span style="color:var(--rose)">*</span></label>
        <input type="text" name="title" class="finput" placeholder="e.g. E-commerce App in Flutter" required>
      </div>
      <div class="fg">
        <label class="flabel">Description <span style="color:var(--rose)">*</span></label>
        <textarea name="description" class="finput" rows="4"
          placeholder="What did you build? What tech did you use? What was the impact? (min 20 chars)"></textarea>
      </div>
      <div class="fg">
        <label class="flabel">Project URL / Link</label>
        <div class="fwrap"><span class="ficon">🔗</span>
          <input type="url" name="url" class="finput" placeholder="https://github.com/… or https://dribbble.com/…">
        </div>
      </div>
      <div class="fg">
        <label class="flabel">Preview Image URL <span style="color:var(--t3)">(optional)</span></label>
        <div class="fwrap"><span class="ficon">🖼️</span>
          <input type="url" name="image_url" class="finput" placeholder="https://i.imgur.com/…">
        </div>
        <div class="fhelp">Paste a direct image URL (Imgur, Cloudinary, Google Drive, etc.)</div>
      </div>
      <div class="fg">
        <label class="flabel">Tech Tags <span style="color:var(--t3)">(comma-separated)</span></label>
        <input type="text" name="tags" class="finput" placeholder="React, Node.js, Firebase, Figma…">
      </div>
      <div style="display:flex;gap:12px">
        <button type="submit" name="add_item" class="btn btn-brand" style="flex:1">✅ Add to Portfolio</button>
        <button type="button" onclick="closeModal('addModal')" class="btn btn-ghost">Cancel</button>
      </div>
    </form>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
