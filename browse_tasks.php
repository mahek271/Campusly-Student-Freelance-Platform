<?php
session_start();
include 'db.php';

if(!isset($_SESSION["user_id"])){ header("Location: login.php?next=browse_tasks.php"); exit(); }


$cat    = $_GET['cat']     ?? '';
$search = trim($_GET['q']  ?? '');
$sort   = $_GET['sort']    ?? 'newest';
$min    = intval($_GET['min'] ?? 0);
$max    = intval($_GET['max'] ?? 0);
$urg    = $_GET['urgency'] ?? '';

// ── Build WHERE clauses safely ─────────────────────────────
$where  = ["t.status = 'open'"];
$params = [];
$types  = '';

if ($search !== '') {
    $s = '%' . $search . '%';
    $where[]  = "(t.title LIKE ? OR t.description LIKE ? OR t.skills LIKE ? OR t.category LIKE ?)";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
    $types   .= 'ssss';
}
if ($cat !== '') {
    $where[]  = "t.category = ?";
    $params[] = $cat;
    $types   .= 's';
}
if ($urg !== '') {
    $where[]  = "t.urgency = ?";
    $params[] = $urg;
    $types   .= 's';
}
if ($min > 0) {
    $where[]  = "t.price >= ?";
    $params[] = $min;
    $types   .= 'd';
}
if ($max > 0) {
    $where[]  = "t.price <= ?";
    $params[] = $max;
    $types   .= 'd';
}

$order_map = [
    'budget_high' => 't.price DESC',
    'budget_low'  => 't.price ASC',
    'deadline'    => 't.deadline ASC',
    'newest'      => 't.created_at DESC',
];
$order = $order_map[$sort] ?? 't.created_at DESC';

$sql = "SELECT t.*, u.name AS employer_name, u.company_name,
          (SELECT COUNT(*) FROM applications WHERE task_id = t.id) AS app_count
        FROM tasks t
        JOIN users u ON t.employer_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $order";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Query prepare error: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tasks = $stmt->get_result();

// Pull categories dynamically from DB
$cat_res    = mysqli_query($conn, "SELECT DISTINCT category FROM tasks WHERE status='open' AND category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = [];
while ($cr = mysqli_fetch_row($cat_res)) $categories[] = $cr[0];

$result_count = $tasks->num_rows;
$is_filtered  = ($search !== '' || $cat !== '' || $urg !== '' || $min > 0 || $max > 0);

$page_title = 'Browse Tasks';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div class="page-hero" style="padding-bottom:30px">
    <div class="tag <?= $result_count > 0 ? 'tag-g' : 'tag-r' ?>" style="margin-bottom:12px">
      🔎 <?= $result_count ?> <?= $is_filtered ? 'matching ' : '' ?>task<?= $result_count !== 1 ? 's' : '' ?> <?= $is_filtered ? 'found' : 'available' ?>
    </div>
    <h1>Browse <span style="background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent">Open Tasks</span></h1>
    <p>Find your next opportunity — bid, deliver, get paid.</p>
  </div>

  <div class="ctr" style="padding-bottom:60px">

    <!-- FILTERS -->
    <div class="glass" style="padding:20px;margin-bottom:28px">
      <form method="GET" id="filterForm" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">

        <div style="flex:1;min-width:200px">
          <label class="flabel">Search</label>
          <div class="fwrap"><span class="ficon">🔍</span>
            <input type="text" name="q" class="finput" placeholder="Title, skills, keywords…"
              value="<?= htmlspecialchars($search) ?>" id="searchInput">
          </div>
        </div>

        <div style="min-width:150px">
          <label class="flabel">Category</label>
          <select name="cat" class="finput">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $cat === $c ? 'selected' : '' ?>>
                <?= htmlspecialchars($c) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="min-width:130px">
          <label class="flabel">Urgency</label>
          <select name="urgency" class="finput">
            <option value="">Any</option>
            <option value="urgent" <?= $urg === 'urgent' ? 'selected' : '' ?>>🔴 Urgent</option>
            <option value="high"   <?= $urg === 'high'   ? 'selected' : '' ?>>🟠 High</option>
            <option value="medium" <?= $urg === 'medium' ? 'selected' : '' ?>>🟡 Medium</option>
            <option value="low"    <?= $urg === 'low'    ? 'selected' : '' ?>>🟢 Low</option>
          </select>
        </div>

        <div style="min-width:120px">
          <label class="flabel">Min Budget (₹)</label>
          <input type="number" name="min" class="finput" placeholder="0"
            value="<?= $min > 0 ? $min : '' ?>" min="0">
        </div>

        <div style="min-width:120px">
          <label class="flabel">Max Budget (₹)</label>
          <input type="number" name="max" class="finput" placeholder="Any"
            value="<?= $max > 0 ? $max : '' ?>" min="0">
        </div>

        <div style="min-width:140px">
          <label class="flabel">Sort By</label>
          <select name="sort" class="finput">
            <option value="newest"      <?= $sort === 'newest'      ? 'selected' : '' ?>>Newest First</option>
            <option value="budget_high" <?= $sort === 'budget_high' ? 'selected' : '' ?>>Budget: High → Low</option>
            <option value="budget_low"  <?= $sort === 'budget_low'  ? 'selected' : '' ?>>Budget: Low → High</option>
            <option value="deadline"    <?= $sort === 'deadline'    ? 'selected' : '' ?>>Deadline Soonest</option>
          </select>
        </div>

        <div style="display:flex;gap:8px;align-items:flex-end">
          <button type="submit" class="btn btn-brand">🔍 Search</button>
          <a href="browse_tasks.php" class="btn btn-ghost">✕ Clear</a>
        </div>
      </form>

      <?php if ($is_filtered): ?>
      <!-- Active filter chips -->
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
        <span style="font-size:12px;color:var(--t3);align-self:center">Active filters:</span>
        <?php if($search): ?>
          <span class="tag tag-v">🔍 "<?= htmlspecialchars($search) ?>"
            <a href="?<?= http_build_query(array_merge($_GET,['q'=>''])) ?>" style="margin-left:5px;color:inherit">×</a>
          </span>
        <?php endif; ?>
        <?php if($cat): ?>
          <span class="tag tag-c">📂 <?= htmlspecialchars($cat) ?>
            <a href="?<?= http_build_query(array_merge($_GET,['cat'=>''])) ?>" style="margin-left:5px;color:inherit">×</a>
          </span>
        <?php endif; ?>
        <?php if($urg): ?>
          <span class="tag tag-a">⚡ <?= ucfirst($urg) ?>
            <a href="?<?= http_build_query(array_merge($_GET,['urgency'=>''])) ?>" style="margin-left:5px;color:inherit">×</a>
          </span>
        <?php endif; ?>
        <?php if($min>0): ?>
          <span class="tag tag-g">₹ Min: <?= number_format($min) ?>
            <a href="?<?= http_build_query(array_merge($_GET,['min'=>''])) ?>" style="margin-left:5px;color:inherit">×</a>
          </span>
        <?php endif; ?>
        <?php if($max>0): ?>
          <span class="tag tag-g">₹ Max: <?= number_format($max) ?>
            <a href="?<?= http_build_query(array_merge($_GET,['max'=>''])) ?>" style="margin-left:5px;color:inherit">×</a>
          </span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- TASK GRID / EMPTY STATE -->
    <?php if ($result_count === 0): ?>
      <div style="text-align:center;padding:80px 20px;color:var(--t3)">
        <div style="font-size:4rem;margin-bottom:16px">🔍</div>
        <h3 style="font-family:var(--fh);margin-bottom:8px;color:var(--t1)">No tasks found</h3>
        <?php if ($is_filtered): ?>
          <p style="margin-bottom:6px;max-width:400px;margin-left:auto;margin-right:auto">
            No open tasks match your current filters. Try broadening your search.
          </p>
          <a href="browse_tasks.php" class="btn btn-brand" style="margin-top:18px">Clear All Filters</a>
        <?php else: ?>
          <p>No tasks are available right now. Check back soon!</p>
        <?php endif; ?>
      </div>

    <?php else: ?>
      <div class="grid-3">
        <?php while ($t = $tasks->fetch_assoc()):
          $skills      = $t['skills'] ? array_slice(array_map('trim', explode(',', $t['skills'])), 0, 4) : [];
          $urg_cls     = ['urgent'=>'tag-r','high'=>'tag-a','medium'=>'tag-v','low'=>'tag-g'][$t['urgency'] ?? 'medium'] ?? 'tag-v';
          $dl_str      = $t['deadline'] ? date('d M Y', strtotime($t['deadline'])) : 'Flexible';
          $days_left   = $t['deadline'] ? ceil((strtotime($t['deadline']) - time()) / 86400) : null;
          $company     = htmlspecialchars($t['company_name'] ?: $t['employer_name']);
        ?>
        <a href="task_view.php?id=<?= $t['id'] ?>" class="job-card" style="display:block;text-decoration:none">
          <div class="job-card-header">
            <div style="flex:1;min-width:0">
              <div class="job-title"><?= htmlspecialchars($t['title']) ?></div>
              <div class="job-company">🏢 <?= $company ?></div>
            </div>
            <div style="text-align:right;flex-shrink:0">
              <div class="job-budget">₹<?= number_format($t['price']) ?></div>
              <?php if ($days_left !== null): ?>
                <div style="font-size:11px;margin-top:2px;color:<?= $days_left <= 3 ? 'var(--rose)' : ($days_left <= 7 ? 'var(--amber)' : 'var(--t3)') ?>">
                  ⏱ <?= $days_left > 0 ? "$days_left days left" : 'Deadline today!' ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <p style="color:var(--t2);font-size:13px;margin-bottom:12px;line-height:1.6">
            <?= htmlspecialchars(mb_substr($t['description'], 0, 130)) ?>…
          </p>

          <div class="job-tags">
            <span class="tag <?= $urg_cls ?>"><?= ucfirst($t['urgency'] ?? 'medium') ?></span>
            <span class="tag tag-c"><?= htmlspecialchars($t['category']) ?></span>
            <?php foreach ($skills as $sk): if (trim($sk) !== ''): ?>
              <span class="tag tag-v"><?= htmlspecialchars($sk) ?></span>
            <?php endif; endforeach; ?>
          </div>

          <div class="job-meta">
            <span>📋 <?= $t['app_count'] ?> bid<?= $t['app_count'] != 1 ? 's' : '' ?></span>
            <span>📅 <?= $dl_str ?></span>
            <span>📍 <?= htmlspecialchars($t['location']) ?></span>
          </div>
        </a>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
