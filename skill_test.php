<?php
session_start();
include 'db.php';
requireRole('candidate');

$uid  = (int)$_SESSION['user_id'];
$test = $_GET['test'] ?? '';

// All available skill tests with questions
$tests = [
  'Python Basics' => [
    'color'=>'var(--cyan)',
    'icon'=>'🐍',
    'questions'=>[
      ['q'=>'What is the correct way to create a list in Python?','options'=>['list = {}','list = []','list = ()','list = <>'],'answer'=>1],
      ['q'=>'Which keyword is used to define a function in Python?','options'=>['function','define','def','func'],'answer'=>2],
      ['q'=>'What does len([1,2,3]) return?','options'=>['2','3','4','0'],'answer'=>1],
      ['q'=>'How do you start a comment in Python?','options'=>['//','--','#','/*'],'answer'=>2],
      ['q'=>'Which of these is a valid Python dictionary?','options'=>["{'a':1}","['a',1]","('a',1)","<a:1>"],'answer'=>0],
    ],
  ],
  'HTML & CSS' => [
    'color'=>'var(--amber)',
    'icon'=>'🎨',
    'questions'=>[
      ['q'=>'Which HTML tag creates a hyperlink?','options'=>['<link>','<a>','<href>','<url>'],'answer'=>1],
      ['q'=>'Which CSS property controls text size?','options'=>['font-weight','text-size','font-size','size'],'answer'=>2],
      ['q'=>'What does CSS stand for?','options'=>['Creative Style Sheets','Cascading Style Sheets','Computer Style Sheets','Colorful Style Sheets'],'answer'=>1],
      ['q'=>'Which attribute makes an input required in HTML?','options'=>['mandatory','needed','required','must'],'answer'=>2],
      ['q'=>'Which CSS value centres a block element horizontally?','options'=>['margin: auto','align: center','float: center','position: center'],'answer'=>0],
    ],
  ],
  'JavaScript' => [
    'color'=>'var(--amber)',
    'icon'=>'⚡',
    'questions'=>[
      ['q'=>'Which method adds an element to the end of an array?','options'=>['append()','push()','add()','insert()'],'answer'=>1],
      ['q'=>'How do you declare a constant in JS?','options'=>['var','let','const','static'],'answer'=>2],
      ['q'=>'What does === check?','options'=>['Value only','Type only','Value and type','Neither'],'answer'=>2],
      ['q'=>'Which built-in method converts JSON string to object?','options'=>['JSON.parse()','JSON.stringify()','JSON.convert()','JSON.decode()'],'answer'=>0],
      ['q'=>'What is the output of typeof null?','options'=>['null','undefined','object','string'],'answer'=>2],
    ],
  ],
  'SQL Basics' => [
    'color'=>'var(--violet2)',
    'icon'=>'🗄️',
    'questions'=>[
      ['q'=>'Which SQL command retrieves data?','options'=>['GET','FETCH','SELECT','PULL'],'answer'=>2],
      ['q'=>'Which clause filters rows in SQL?','options'=>['FILTER','WHERE','HAVING','LIMIT'],'answer'=>1],
      ['q'=>'What does PRIMARY KEY do?','options'=>['Allows duplicates','Makes column optional','Uniquely identifies each row','Encrypts data'],'answer'=>2],
      ['q'=>'Which join returns all rows from both tables?','options'=>['INNER JOIN','LEFT JOIN','FULL OUTER JOIN','CROSS JOIN'],'answer'=>2],
      ['q'=>'Which aggregate function counts rows?','options'=>['SUM()','AVG()','COUNT()','MAX()'],'answer'=>2],
    ],
  ],
  'UI/UX Design' => [
    'color'=>'var(--rose)',
    'icon'=>'🎭',
    'questions'=>[
      ['q'=>'What does UX stand for?','options'=>['User Excellence','User Experience','Unique Experience','User Experiment'],'answer'=>1],
      ['q'=>'What is a wireframe?','options'=>['Final coloured design','Skeletal layout of a page','Logo mockup','CSS framework'],'answer'=>1],
      ['q'=>'Which principle says important elements should stand out?','options'=>['Proximity','Visual Hierarchy','Alignment','Repetition'],'answer'=>1],
      ['q'=>'What is a "call to action" (CTA)?','options'=>['Navigation menu','Button prompting user to act','Header text','Footer link'],'answer'=>1],
      ['q'=>'What does A/B testing measure?','options'=>['Server speed','Which design performs better','Bug count','User age'],'answer'=>1],
    ],
  ],
  'Digital Marketing' => [
    'color'=>'var(--emerald)',
    'icon'=>'📣',
    'questions'=>[
      ['q'=>'What does SEO stand for?','options'=>['Social Engagement Optimization','Search Engine Optimization','Site Experience Overhaul','System Email Output'],'answer'=>1],
      ['q'=>'What is a "bounce rate"?','options'=>['Emails returned','% users leaving after one page','Conversion rate','Click rate'],'answer'=>1],
      ['q'=>'Which metric measures email effectiveness?','options'=>['Open rate','Bounce rate','CTR','All of the above'],'answer'=>3],
      ['q'=>'What is a "conversion" in digital marketing?','options'=>['Changing website design','Visitor completing a desired action','Switching ad platform','Email unsubscribe'],'answer'=>1],
      ['q'=>'What does CPC stand for?','options'=>['Cost Per Click','Content Per Campaign','Creative Placement Cost','Customer Payment Cycle'],'answer'=>0],
    ],
  ],
];

// Handle test submission
$result = null;
if($_SERVER['REQUEST_METHOD']==='POST' && $test && isset($tests[$test])){
  $q_count   = count($tests[$test]['questions']);
  $correct   = 0;
  for($i=0;$i<$q_count;$i++){
    if(isset($_POST["q$i"]) && intval($_POST["q$i"]) === $tests[$test]['questions'][$i]['answer']){
      $correct++;
    }
  }
  $score   = round($correct / $q_count * 100);
  $passed  = $score >= 80;
  $result  = compact('score','correct','q_count','passed');

  if($passed){
    // Award badge — upsert
    $safe_test = $conn->real_escape_string($test);
    $conn->query("INSERT INTO skill_badges (user_id, skill_name, score, earned_at)
      VALUES ($uid, '$safe_test', $score, NOW())
      ON DUPLICATE KEY UPDATE score=$score, earned_at=NOW()");
    // Notify
    $conn->query("INSERT INTO notifications (user_id,message,link)
      VALUES ($uid,'🏅 You earned a verified badge: $safe_test ($score%)','skill_test.php')");
  }
}

// Get user's existing badges
$badges = mysqli_query($conn,"SELECT * FROM skill_badges WHERE user_id=$uid ORDER BY earned_at DESC");
$badge_names=[];
while($b=$badges->fetch_assoc()) $badge_names[$b['skill_name']]=$b;

$page_title = 'Skill Verification';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap">
<div class="dash-wrap">
  <aside class="sidebar">
    <div class="sidebar-card">
      <a href="candidate_dashboard.php" class="sidebar-link"><span class="si">🏠</span>Dashboard</a>
      <a href="browse_tasks.php"        class="sidebar-link"><span class="si">🔍</span>Browse Tasks</a>
      <a href="my_earnings.php"         class="sidebar-link"><span class="si">💰</span>Earnings</a>
      <a href="portfolio.php"           class="sidebar-link"><span class="si">🖼️</span>Portfolio</a>
      <a href="skill_test.php"          class="sidebar-link active"><span class="si">🏅</span>Skill Tests</a>
      <a href="messages.php"            class="sidebar-link"><span class="si">💬</span>Messages</a>
      <a href="leaderboard.php"         class="sidebar-link"><span class="si">🏆</span>Leaderboard</a>
      <a href="notifications.php"       class="sidebar-link"><span class="si">🔔</span>Notifications</a>
      <a href="profile.php"             class="sidebar-link"><span class="si">👤</span>Profile</a>
    </div>
  </aside>

  <main class="main-content">
    <h1 style="font-family:var(--fh);font-size:1.8rem;font-weight:700;margin-bottom:6px">🏅 Skill Verification Tests</h1>
    <p style="color:var(--t3);margin-bottom:28px">Pass a test (≥80%) to earn a verified badge that shows on your profile and bids — boosting your chances of getting hired</p>

    <?php if($result && $test): ?>
      <!-- Test Result -->
      <div class="glass" style="padding:40px;border-radius:var(--r);text-align:center;margin-bottom:28px;background:<?= $result['passed']?'rgba(16,185,129,0.08)':'rgba(244,63,122,0.08)' ?>;border-color:<?= $result['passed']?'rgba(16,185,129,0.25)':'rgba(244,63,122,0.25)' ?>">
        <div style="font-size:4rem;margin-bottom:12px"><?= $result['passed']?'🏅':'😔' ?></div>
        <h2 style="font-family:var(--fh);font-size:1.6rem;font-weight:700;margin-bottom:8px;color:<?= $result['passed']?'var(--emerald)':'var(--rose)' ?>">
          <?= $result['passed']?'Badge Earned!':'Not Quite!' ?>
        </h2>
        <div style="font-size:3rem;font-weight:800;font-family:var(--fh);color:<?= $result['passed']?'var(--emerald)':'var(--rose)' ?>;margin:12px 0">
          <?= $result['score'] ?>%
        </div>
        <p style="color:var(--t2);margin-bottom:20px">
          <?= $result['correct'] ?> / <?= $result['q_count'] ?> correct
          <?= $result['passed']?"— You've earned the <strong>{$test}</strong> verified badge! 🎉":"— You need 80% to pass. You can retake the test anytime." ?>
        </p>
        <div style="display:flex;gap:12px;justify-content:center">
          <?php if($result['passed']): ?>
            <a href="profile.php" class="btn btn-brand">👤 View My Profile</a>
          <?php else: ?>
            <a href="skill_test.php?test=<?= urlencode($test) ?>" class="btn btn-brand">🔄 Retake Test</a>
          <?php endif; ?>
          <a href="skill_test.php" class="btn btn-ghost">← All Tests</a>
        </div>
      </div>
    <?php elseif($test && isset($tests[$test])): ?>
      <!-- Active Test -->
      <?php $t_data=$tests[$test]; ?>
      <div class="glass-dark" style="padding:24px;border-radius:var(--r);margin-bottom:24px">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:6px">
          <span style="font-size:2rem"><?= $t_data['icon'] ?></span>
          <div>
            <h2 style="font-family:var(--fh);font-weight:700;font-size:1.2rem"><?= htmlspecialchars($test) ?> Test</h2>
            <p style="color:var(--t3);font-size:13px"><?= count($t_data['questions']) ?> questions · Pass = 80% or higher · No time limit</p>
          </div>
        </div>
      </div>

      <form method="POST">
        <?php foreach($t_data['questions'] as $i=>$q): ?>
        <div class="glass-dark" style="padding:24px;border-radius:var(--r);margin-bottom:16px">
          <div style="font-weight:700;font-size:14.5px;margin-bottom:16px;color:var(--t1)">
            <span style="color:var(--t3);font-size:13px">Q<?= $i+1 ?></span> &nbsp;<?= htmlspecialchars($q['q']) ?>
          </div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <?php foreach($q['options'] as $oi=>$opt): ?>
            <label style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;background:var(--surface);border:1px solid var(--border);cursor:pointer;transition:var(--tr)"
              onmouseover="this.style.borderColor='var(--violet)'" onmouseout="this.style.borderColor='var(--border)'">
              <input type="radio" name="q<?= $i ?>" value="<?= $oi ?>" required
                style="accent-color:var(--violet);width:16px;height:16px;flex-shrink:0">
              <span style="font-size:14px;color:var(--t2)"><?= htmlspecialchars($opt) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <div style="display:flex;gap:12px">
          <button type="submit" class="btn btn-brand btn-lg" style="flex:1">📤 Submit Test</button>
          <a href="skill_test.php" class="btn btn-ghost btn-lg">Cancel</a>
        </div>
      </form>

    <?php else: ?>
      <!-- Test List -->
      <?php if(!empty($badge_names)): ?>
      <div class="glass-dark" style="padding:20px 24px;border-radius:var(--r);margin-bottom:24px">
        <div style="font-weight:700;margin-bottom:12px;font-size:14px">🏅 Your Verified Badges</div>
        <div style="display:flex;flex-wrap:wrap;gap:8px">
          <?php foreach($badge_names as $name=>$badge): ?>
          <div style="display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border-radius:20px;background:rgba(124,92,252,0.12);border:1px solid rgba(124,92,252,0.3)">
            <span style="color:var(--amber)">🏅</span>
            <span style="font-size:13px;font-weight:600;color:var(--violet2)"><?= htmlspecialchars($name) ?></span>
            <span style="font-size:11px;color:var(--t3)"><?= $badge['score'] ?>%</span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="grid-3">
        <?php foreach($tests as $name=>$data):
          $earned = isset($badge_names[$name]);
          $score  = $earned ? $badge_names[$name]['score'] : null;
        ?>
        <div class="card" style="text-align:center;padding:28px 20px;<?= $earned?'border-color:rgba(124,92,252,0.35)':'' ?>">
          <div style="font-size:2.8rem;margin-bottom:10px"><?= $data['icon'] ?></div>
          <h3 style="font-family:var(--fh);font-weight:700;margin-bottom:6px"><?= htmlspecialchars($name) ?></h3>
          <div style="color:var(--t3);font-size:13px;margin-bottom:16px"><?= count($data['questions']) ?> questions · Pass = 80%</div>
          <?php if($earned): ?>
            <div style="margin-bottom:14px">
              <span class="tag tag-v">🏅 Verified <?= $score ?>%</span>
            </div>
            <a href="skill_test.php?test=<?= urlencode($name) ?>" class="btn btn-ghost btn-sm btn-full">🔄 Retake</a>
          <?php else: ?>
            <a href="skill_test.php?test=<?= urlencode($name) ?>" class="btn btn-brand btn-full">Start Test →</a>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>
</div>
<?php include 'includes/footer.php'; ?>
