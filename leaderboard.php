<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    $_SESSION['flash_error'] = 'Please log in to view the leaderboard.';
    header("Location: login.php?next=leaderboard.php");
    exit();
}

$students = mysqli_query($conn,
  "SELECT u.id, u.name, u.university, u.skills, u.bio,
    COUNT(DISTINCT a.task_id) as tasks_done,
    COALESCE(AVG(r.rating),0) as avg_rating,
    COUNT(DISTINCT r.id) as review_count
   FROM users u
   LEFT JOIN applications a ON a.candidate_id=u.id AND a.status='completed'
   LEFT JOIN reviews r ON r.reviewee_id=u.id
   WHERE u.role='candidate' AND (u.is_banned IS NULL OR u.is_banned=0)
   GROUP BY u.id
   HAVING tasks_done > 0 OR review_count > 0
   ORDER BY tasks_done DESC, avg_rating DESC
   LIMIT 50");

$page_title='Leaderboard';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
  <div class="page-hero">
    <div class="tag tag-a" style="margin-bottom:14px">🏆 Hall of Fame</div>
    <h1>Campus <span style="background:var(--grad-brand);-webkit-background-clip:text;-webkit-text-fill-color:transparent">Leaderboard</span></h1>
    <p>Top performing students on Campusly — ranked by tasks completed &amp; rating</p>
  </div>

  <div class="ctr" style="padding-bottom:70px">
    <?php
    $all = [];
    while($s=$students->fetch_assoc()) $all[] = $s;
    $total = count($all);
    ?>

    <?php if($total===0): ?>
      <div style="text-align:center;padding:80px;color:var(--t3)">
        <div style="font-size:4rem;margin-bottom:16px">🏆</div>
        <h3 style="font-family:var(--fh);margin-bottom:8px">Leaderboard starts here!</h3>
        <p>Be the first to complete tasks and earn your spot</p>
        <a href="browse_tasks.php" class="btn btn-brand" style="margin-top:20px">Browse Tasks →</a>
      </div>
    <?php else: ?>

    <!-- TOP 3 PODIUM -->
    <?php if($total>=1): ?>
    <div style="display:flex;justify-content:center;align-items:flex-end;gap:16px;margin-bottom:60px;flex-wrap:wrap">
      <?php
      $podium_order = [];
      if($total>=2) $podium_order[] = [1,$all[1],'silver','🥈','160px'];
      $podium_order[] = [0,$all[0],'gold','🥇','200px'];
      if($total>=3) $podium_order[] = [2,$all[2],'bronze','🥉','140px'];
      foreach($podium_order as [$idx,$s,$rank_cls,$medal,$h]): ?>
      <div style="text-align:center;width:200px">
        <div style="margin-bottom:12px">
          <div class="avatar" style="width:70px;height:70px;font-size:28px;margin:0 auto;border:3px solid <?= $rank_cls==='gold'?'var(--amber)':($rank_cls==='silver'?'#94a3b8':'#cd7f32') ?>">
            <?= strtoupper(substr($s['name'],0,1)) ?>
          </div>
          <div style="font-size:2rem;margin-top:-10px"><?= $medal ?></div>
        </div>
        <div style="background:var(--ink3);border:1px solid var(--border);border-radius:14px 14px 0 0;padding:16px 12px;height:<?= $h ?>;display:flex;flex-direction:column;justify-content:center;align-items:center">
          <div style="font-weight:700;font-size:.95rem;margin-bottom:4px"><?= htmlspecialchars(explode(' ',$s['name'])[0]) ?></div>
          <div style="color:var(--t3);font-size:11px;margin-bottom:8px"><?= htmlspecialchars(substr($s['university']??'',0,25)) ?></div>
          <div style="color:var(--blue2);font-weight:800;font-size:1.1rem"><?= $s['tasks_done'] ?> tasks</div>
          <div style="color:var(--t3);font-size:11px"><?= number_format($s['avg_rating'],1) ?>⭐ rating</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- FULL TABLE -->
    <div class="glass-dark" style="padding:24px;border-radius:var(--r)">
      <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:20px;font-size:1.1rem">📊 Full Rankings</h3>
      <div style="overflow-x:auto">
        <table class="tbl">
          <thead>
            <tr>
              <th style="width:50px">Rank</th>
              <th>Student</th>
              <th>University</th>
              <th>Tasks Done</th>
              <th>Rating</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($all as $i=>$s):
              $rank = $i+1;
              $medal = $rank===1?'🥇':($rank===2?'🥈':($rank===3?'🥉':'#'.$rank));
            ?>
            <tr>
              <td style="font-weight:700;font-size:1.1rem;text-align:center"><?= $medal ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div class="avatar"><?= strtoupper(substr($s['name'],0,1)) ?></div>
                  <div>
                    <div style="font-weight:600"><?= htmlspecialchars($s['name']) ?></div>
                    <?php if($s['skills']): ?>
                      <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:3px">
                        <?php foreach(array_slice(explode(',',$s['skills']),0,3) as $sk): ?>
                          <span class="tag tag-v" style="font-size:10px;padding:2px 7px"><?= htmlspecialchars(trim($sk)) ?></span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td style="color:var(--t3);font-size:13px"><?= htmlspecialchars($s['university']??'—') ?></td>
              <td style="font-weight:700;color:var(--blue2)"><?= $s['tasks_done'] ?></td>
              <td>
                <div style="display:flex;align-items:center;gap:6px">
                  <div class="stars">
                    <?php for($j=1;$j<=5;$j++): ?>
                      <span class="star <?= $j<=$s['avg_rating']?'on':'off' ?>">★</span>
                    <?php endfor; ?>
                  </div>
                  <span style="color:var(--t3);font-size:12px">(<?= $s['review_count'] ?>)</span>
                </div>
              </td>
              <td><a href="view_student_profile.php?id=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">Profile</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
