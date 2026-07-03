<?php
session_start();
include 'db.php';
requireLogin();

$uid  = (int)$_SESSION['user_id'];

// Send message
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['send'])){
  $to_id   = intval($_POST['to_id'] ?? 0);
  $task_id = intval($_POST['task_id'] ?? 0);
  $body    = trim($_POST['body'] ?? '');
  if($to_id && $body && strlen($body)<=2000){
    $safe = $conn->real_escape_string($body);
    $conn->query("INSERT INTO messages (from_id,to_id,task_id,body,sent_at) VALUES ($uid,$to_id,$task_id,'$safe',NOW())");
    $preview = $conn->real_escape_string(substr($body,0,60));
    $sender  = $conn->real_escape_string($_SESSION['user_name']);
    $conn->query("INSERT INTO notifications (user_id,message,link) VALUES ($to_id,'💬 $sender: $preview','messages.php?thread=$uid&task=$task_id')");
  }
  header("Location: messages.php?thread=$to_id&task=$task_id"); exit();
}

// Thread list
$threads = mysqli_query($conn,
  "SELECT
    IF(m.from_id=$uid, m.to_id, m.from_id) AS other_id,
    m.task_id,
    MAX(m.sent_at) AS last_msg,
    u.name AS other_name,
    t.title AS task_title,
    SUM(IF(m.to_id=$uid AND m.is_read=0,1,0)) AS unread
   FROM messages m
   JOIN users u ON u.id = IF(m.from_id=$uid, m.to_id, m.from_id)
   JOIN tasks t ON t.id = m.task_id
   WHERE m.from_id=$uid OR m.to_id=$uid
   GROUP BY other_id, m.task_id
   ORDER BY last_msg DESC");

$thread_uid  = intval($_GET['thread'] ?? 0);
$thread_task = intval($_GET['task']   ?? 0);
$msgs = null; $other_user = null; $task_info = null;

if($thread_uid && $thread_task){
  $conn->query("UPDATE messages SET is_read=1 WHERE from_id=$thread_uid AND to_id=$uid AND task_id=$thread_task");
  $msgs = mysqli_query($conn,
    "SELECT m.*, u.name AS sender_name FROM messages m JOIN users u ON u.id=m.from_id
     WHERE task_id=$thread_task
       AND ((from_id=$uid AND to_id=$thread_uid) OR (from_id=$thread_uid AND to_id=$uid))
     ORDER BY sent_at ASC");
  $other_user = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id,name FROM users WHERE id=$thread_uid LIMIT 1"));
  $task_info  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id,title FROM tasks WHERE id=$thread_task LIMIT 1"));
}

$page_title = 'Messages';
include 'includes/head.php';
include 'includes/navbar.php';
?>
<div class="wrap" style="padding-top:70px;height:calc(100vh - 70px);overflow:hidden">
<div style="display:grid;grid-template-columns:300px 1fr;height:100%;max-width:1200px;margin:0 auto;border-left:1px solid var(--border);border-right:1px solid var(--border);position:relative;z-index:1">

  <!-- LEFT: Threads -->
  <div style="background:var(--ink2);border-right:1px solid var(--border);overflow-y:auto;display:flex;flex-direction:column">
    <div style="padding:20px;border-bottom:1px solid var(--border)">
      <div style="font-family:var(--fh);font-weight:700;font-size:1.1rem">💬 Messages</div>
    </div>
    <?php if(mysqli_num_rows($threads)===0): ?>
      <div style="padding:40px 20px;text-align:center;color:var(--t3)">
        <div style="font-size:2.5rem;margin-bottom:10px">📭</div>
        <p style="font-size:13px">No messages yet.<br>Messages appear when you are selected for a task.</p>
      </div>
    <?php else: ?>
      <?php while($th=$threads->fetch_assoc()):
        $active = ($th['other_id']==$thread_uid && $th['task_id']==$thread_task);
      ?>
      <a href="messages.php?thread=<?= $th['other_id'] ?>&task=<?= $th['task_id'] ?>"
         style="display:block;padding:14px 18px;border-bottom:1px solid var(--border);transition:var(--tr);text-decoration:none;<?= $active?'background:rgba(124,92,252,0.12);border-left:3px solid var(--violet)':'' ?>">
        <div style="display:flex;align-items:center;gap:10px">
          <div class="avatar" style="flex-shrink:0"><?= strtoupper(substr($th['other_name'],0,1)) ?></div>
          <div style="flex:1;min-width:0">
            <div style="display:flex;justify-content:space-between">
              <span style="font-weight:600;font-size:13.5px;color:var(--t1)"><?= htmlspecialchars($th['other_name']) ?></span>
              <?php if($th['unread']>0): ?><span class="badge"><?= $th['unread'] ?></span><?php endif; ?>
            </div>
            <div style="color:var(--violet2);font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(substr($th['task_title'],0,35)) ?></div>
          </div>
        </div>
      </a>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <!-- RIGHT: Chat -->
  <div style="display:flex;flex-direction:column;background:var(--ink);height:100%">
    <?php if($msgs && $other_user): ?>
      <div style="padding:14px 22px;border-bottom:1px solid var(--border);background:var(--ink2);display:flex;align-items:center;gap:12px">
        <div class="avatar"><?= strtoupper(substr($other_user['name'],0,1)) ?></div>
        <div>
          <div style="font-weight:700"><?= htmlspecialchars($other_user['name']) ?></div>
          <div style="color:var(--violet2);font-size:12px">Re: <?= htmlspecialchars($task_info['title']) ?></div>
        </div>
      </div>

      <div id="chat-area" style="flex:1;overflow-y:auto;padding:20px;display:flex;flex-direction:column;gap:10px">
        <?php while($m=$msgs->fetch_assoc()):
          $me = ($m['from_id']==$uid);
        ?>
        <div style="display:flex;justify-content:<?= $me?'flex-end':'flex-start' ?>;gap:8px;align-items:flex-end">
          <?php if(!$me): ?><div class="avatar" style="width:28px;height:28px;font-size:11px"><?= strtoupper(substr($m['sender_name'],0,1)) ?></div><?php endif; ?>
          <div>
            <div style="background:<?= $me?'var(--violet)':'var(--ink3)' ?>;border:1px solid <?= $me?'rgba(124,92,252,0.4)':'var(--border)' ?>;border-radius:<?= $me?'18px 18px 4px 18px':'18px 18px 18px 4px' ?>;padding:11px 15px;font-size:13.5px;line-height:1.6;color:var(--t1);max-width:420px;word-break:break-word">
              <?= nl2br(htmlspecialchars($m['body'])) ?>
            </div>
            <div style="font-size:10.5px;color:var(--t4);margin-top:3px;text-align:<?= $me?'right':'left' ?>"><?= date('d M, h:i A',strtotime($m['sent_at'])) ?></div>
          </div>
          <?php if($me): ?><div class="avatar" style="width:28px;height:28px;font-size:11px"><?= strtoupper(substr($_SESSION['user_name'],0,1)) ?></div><?php endif; ?>
        </div>
        <?php endwhile; ?>
      </div>

      <div style="padding:14px 20px;border-top:1px solid var(--border);background:var(--ink2)">
        <form method="POST" style="display:flex;gap:10px;align-items:flex-end">
          <input type="hidden" name="to_id"   value="<?= $thread_uid ?>">
          <input type="hidden" name="task_id" value="<?= $thread_task ?>">
          <textarea name="body" class="finput" rows="2" placeholder="Type a message…" style="flex:1;resize:none" maxlength="2000" required></textarea>
          <button type="submit" name="send" class="btn btn-brand" style="height:46px">Send →</button>
        </form>
      </div>
    <?php else: ?>
      <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;color:var(--t3);gap:12px">
        <div style="font-size:3.5rem">💬</div>
        <div style="font-weight:600">Select a conversation</div>
        <div style="font-size:13px;color:var(--t4)">Messages are enabled for active tasks</div>
      </div>
    <?php endif; ?>
  </div>
</div>
</div>
<script>const ca=document.getElementById('chat-area');if(ca)ca.scrollTop=ca.scrollHeight;</script>
<?php include 'includes/footer.php'; ?>
