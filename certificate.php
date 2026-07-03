<?php
session_start();
include 'db.php';
requireLogin();

$uid    = (int)$_SESSION['user_id'];
$app_id = intval($_GET['app_id'] ?? 0);
if(!$app_id){ header("Location: my_earnings.php"); exit(); }

$app = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT a.*, t.title, t.category, t.price,
    u_c.name AS candidate_name, u_c.university,
    u_e.name AS employer_name, u_e.company_name,
    p.created_at AS paid_on
   FROM applications a
   JOIN tasks t         ON t.id = a.task_id
   JOIN users u_c       ON u_c.id = a.candidate_id
   JOIN users u_e       ON u_e.id = t.employer_id
   LEFT JOIN payments p ON p.application_id = a.id AND p.status='released'
   WHERE a.id=$app_id AND a.candidate_id=$uid AND a.status='completed'
   LIMIT 1"));

if(!$app){ $_SESSION['flash_error']='Certificate not available for this task.'; header("Location: my_earnings.php"); exit(); }

$cert_id   = 'CPL-'.strtoupper(substr(md5($app_id . 'campusly_cert_salt'), 0, 10));
$issue_date= $app['paid_on'] ? date('d F Y', strtotime($app['paid_on'])) : date('d F Y');
$company   = $app['company_name'] ?: $app['employer_name'];

// Escape for JS strings
$js_name     = addslashes(htmlspecialchars($app['candidate_name']));
$js_univ     = addslashes(htmlspecialchars($app['university'] ?? ''));
$js_title    = addslashes(htmlspecialchars($app['title']));
$js_company  = addslashes(htmlspecialchars($company));
$js_category = addslashes(htmlspecialchars($app['category']));
$js_amount   = '₹' . number_format($app['bid_amount']);
$js_date     = $issue_date;
$js_certid   = $cert_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Certificate — <?= htmlspecialchars($app['candidate_name']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;800&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{background:#0a0818;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:30px;font-family:'Plus Jakarta Sans',sans-serif}
    canvas#certCanvas{border-radius:20px;max-width:100%;box-shadow:0 0 0 2px rgba(124,92,252,0.5),0 30px 80px rgba(0,0,0,0.6)}
    .actions{display:flex;gap:12px;margin-top:24px;justify-content:center;flex-wrap:wrap}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:.2s}
    .btn-download{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
    .btn-print{background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff}
    .btn-download:hover,.btn-print:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.4)}
    .btn-back{background:rgba(255,255,255,0.06);color:#b8b5d0;border:1px solid rgba(255,255,255,0.1)}
    #loadingMsg{color:#9d7ffe;font-size:14px;margin-bottom:16px}
    @media print{
      body{background:#0a0818 !important;-webkit-print-color-adjust:exact;print-color-adjust:exact}
      .actions,#loadingMsg{display:none!important}
      canvas#certCanvas{box-shadow:none;border-radius:12px}
    }
  </style>
</head>
<body>
  <div id="loadingMsg">⏳ Rendering certificate…</div>
  <canvas id="certCanvas"></canvas>
  <div class="actions">
    <button onclick="downloadCert()" class="btn btn-download" id="dlBtn">⬇️ Download Certificate</button>
    <button onclick="window.print()" class="btn btn-print">🖨️ Print</button>
    <a href="my_earnings.php" class="btn btn-back">← Back to Earnings</a>
  </div>

<script>
// ── Certificate data from PHP ──────────────────────────────────────
const DATA = {
  name:     "<?= $js_name ?>",
  univ:     "<?= $js_univ ?>",
  title:    "<?= $js_title ?>",
  company:  "<?= $js_company ?>",
  category: "<?= $js_category ?>",
  amount:   "<?= $js_amount ?>",
  date:     "<?= $js_date ?>",
  certId:   "<?= $js_certid ?>"
};

const W = 1200, H = 780;
const canvas = document.getElementById('certCanvas');
canvas.width  = W;
canvas.height = H;
const ctx = canvas.getContext('2d');

// ── Helpers ────────────────────────────────────────────────────────
function roundRect(x,y,w,h,r){
  ctx.beginPath();
  ctx.moveTo(x+r,y);
  ctx.lineTo(x+w-r,y);
  ctx.quadraticCurveTo(x+w,y,x+w,y+r);
  ctx.lineTo(x+w,y+h-r);
  ctx.quadraticCurveTo(x+w,y+h,x+w-r,y+h);
  ctx.lineTo(x+r,y+h);
  ctx.quadraticCurveTo(x,y+h,x,y+h-r);
  ctx.lineTo(x,y+r);
  ctx.quadraticCurveTo(x,y,x+r,y);
  ctx.closePath();
}

function wrapText(text, x, y, maxW, lineH){
  const words = text.split(' ');
  let line = '';
  let lines = [];
  for(let w of words){
    const test = line ? line+' '+w : w;
    if(ctx.measureText(test).width > maxW && line){
      lines.push(line);
      line = w;
    } else { line = test; }
  }
  lines.push(line);
  lines.forEach((l,i)=>ctx.fillText(l, x, y+i*lineH));
  return lines.length;
}

// ── Draw everything ────────────────────────────────────────────────
async function drawCert(){
  await document.fonts.ready;

  // ── Background
  const bg = ctx.createLinearGradient(0,0,W,H);
  bg.addColorStop(0,   '#0f0d1e');
  bg.addColorStop(0.4, '#13102a');
  bg.addColorStop(1,   '#0f0d1e');
  roundRect(0,0,W,H,28);
  ctx.fillStyle = bg;
  ctx.fill();

  // ── Gradient border (stroke instead of ::before pseudo-element)
  const borderGrad = ctx.createLinearGradient(0,0,W,H);
  borderGrad.addColorStop(0,   '#7c5cfc');
  borderGrad.addColorStop(0.35,'#f43f7a');
  borderGrad.addColorStop(0.7, '#f59e0b');
  borderGrad.addColorStop(1,   '#7c5cfc');
  roundRect(1,1,W-2,H-2,27);
  ctx.strokeStyle = borderGrad;
  ctx.lineWidth   = 2.5;
  ctx.stroke();

  // ── Subtle violet glow (top-left)
  const glow = ctx.createRadialGradient(W*0.15, H*0.3, 0, W*0.15, H*0.3, W*0.45);
  glow.addColorStop(0,   'rgba(124,92,252,0.10)');
  glow.addColorStop(1,   'rgba(124,92,252,0)');
  roundRect(0,0,W,H,28);
  ctx.fillStyle = glow;
  ctx.fill();

  const PL = 72, PT = 58; // padding left / top

  // ── Logo mark (rounded square)
  const lmSize=44, lmR=12;
  const lmGrad = ctx.createLinearGradient(PL, PT, PL+lmSize, PT+lmSize);
  lmGrad.addColorStop(0,'#7c5cfc');
  lmGrad.addColorStop(1,'#f43f7a');
  roundRect(PL, PT, lmSize, lmSize, lmR);
  ctx.fillStyle = lmGrad;
  ctx.fill();
  ctx.font = '22px sans-serif';
  ctx.textBaseline = 'middle';
  ctx.textAlign    = 'center';
  ctx.fillText('🎯', PL+lmSize/2, PT+lmSize/2);

  // ── "Campusly" logo text
  const ltGrad = ctx.createLinearGradient(PL+54, PT, PL+160, PT+44);
  ltGrad.addColorStop(0,'#7c5cfc');
  ltGrad.addColorStop(1,'#f43f7a');
  ctx.font         = '700 22px "Plus Jakarta Sans", sans-serif';
  ctx.textAlign    = 'left';
  ctx.textBaseline = 'middle';
  ctx.fillStyle    = ltGrad;
  ctx.fillText('Campusly', PL+56, PT+lmSize/2);

  // ── Badge pill "🏆 Certificate of Completion"
  const badgeY = PT+68, badgeH=36, badgeTxt='🏆  Certificate of Completion';
  ctx.font='600 13px "Plus Jakarta Sans",sans-serif';
  const badgeW = ctx.measureText(badgeTxt).width + 36;
  roundRect(PL, badgeY, badgeW, badgeH, 18);
  ctx.fillStyle = 'rgba(124,92,252,0.14)';
  ctx.fill();
  roundRect(PL, badgeY, badgeW, badgeH, 18);
  ctx.strokeStyle='rgba(124,92,252,0.35)';
  ctx.lineWidth=1;
  ctx.stroke();
  ctx.fillStyle='#9d7ffe';
  ctx.textBaseline='middle';
  ctx.fillText(badgeTxt, PL+18, badgeY+badgeH/2);

  // ── "This certificate is proudly presented to"
  const presY = badgeY+badgeH+32;
  ctx.font      = '500 13px "Plus Jakarta Sans",sans-serif';
  ctx.fillStyle = '#6e6b8a';
  ctx.textBaseline='top';
  ctx.fillText('THIS CERTIFICATE IS PROUDLY PRESENTED TO', PL, presY);

  // ── Candidate name
  const nameY = presY+24;
  ctx.font      = '800 54px "Playfair Display",serif';
  ctx.fillStyle = '#ffffff';
  ctx.fillText(DATA.name, PL, nameY);

  // ── University
  let univY = nameY + 66;
  if(DATA.univ){
    ctx.font      = '500 16px "Plus Jakarta Sans",sans-serif';
    ctx.fillStyle = '#b8b5d0';
    ctx.fillText('🎓  '+DATA.univ, PL, univY);
    univY += 28;
  }

  // ── "for successfully completing the task"
  ctx.font      = '500 16px "Plus Jakarta Sans",sans-serif';
  ctx.fillStyle = '#b8b5d0';
  ctx.fillText('for successfully completing the task', PL, univY);

  // ── Task title
  const taskY = univY + 30;
  ctx.font      = '700 26px "Playfair Display",serif';
  ctx.fillStyle = '#a78bfa';
  const titleText = '\u201C'+DATA.title+'\u201D';
  const titleLines = wrapText(titleText, PL, taskY, W - PL - 200, 36);

  // ── "assigned by Company"
  const assignY = taskY + titleLines*36 + 8;
  ctx.font='500 15px "Plus Jakarta Sans",sans-serif';
  ctx.fillStyle='#b8b5d0';
  ctx.fillText('assigned by ', PL, assignY);
  const byW = ctx.measureText('assigned by ').width;
  ctx.font='600 15px "Plus Jakarta Sans",sans-serif';
  ctx.fillStyle='#e0dff5';
  ctx.fillText(DATA.company, PL+byW, assignY);

  // ── Divider
  const divY = assignY+34;
  const divGrad = ctx.createLinearGradient(PL, divY, W-PL, divY);
  divGrad.addColorStop(0,   'transparent');
  divGrad.addColorStop(0.3, 'rgba(124,92,252,0.5)');
  divGrad.addColorStop(0.7, 'rgba(244,63,122,0.35)');
  divGrad.addColorStop(1,   'transparent');
  ctx.beginPath();
  ctx.moveTo(PL, divY);
  ctx.lineTo(W-PL, divY);
  ctx.strokeStyle = divGrad;
  ctx.lineWidth   = 1;
  ctx.stroke();

  // ── Meta grid (3 columns)
  const metaY  = divY+28;
  const colW   = (W - PL*2 - 160) / 3;
  const metas  = [
    {label:'CATEGORY',     val:DATA.category,          color:'#ffffff'},
    {label:'COMPENSATION', val:DATA.amount,             color:'#10b981'},
    {label:'DATE ISSUED',  val:DATA.date,               color:'#ffffff'},
  ];
  metas.forEach((m,i)=>{
    const mx = PL + i*colW;
    ctx.font='600 11px "Plus Jakarta Sans",sans-serif';
    ctx.fillStyle='#6e6b8a';
    ctx.textBaseline='top';
    ctx.fillText(m.label, mx, metaY);
    ctx.font='600 15px "Plus Jakarta Sans",sans-serif';
    ctx.fillStyle=m.color;
    ctx.fillText(m.val, mx, metaY+18);
  });

  // ── Seal (bottom right)
  const sealX=W-130, sealY=H-160, sealR=46;
  // Outer circle
  ctx.beginPath();
  ctx.arc(sealX, sealY, sealR, 0, Math.PI*2);
  ctx.strokeStyle='rgba(124,92,252,0.45)';
  ctx.lineWidth=2;
  ctx.stroke();
  ctx.fillStyle='rgba(124,92,252,0.10)';
  ctx.fill();
  // Checkmark emoji
  ctx.font='36px sans-serif';
  ctx.textAlign='center';
  ctx.textBaseline='middle';
  ctx.fillText('✅', sealX, sealY);
  // "VERIFIED" label
  ctx.font='600 11px "Plus Jakarta Sans",sans-serif';
  ctx.fillStyle='#6e6b8a';
  ctx.textBaseline='top';
  ctx.fillText('VERIFIED', sealX, sealY+sealR+10);

  // ── Certificate ID (bottom left)
  ctx.font='11px monospace';
  ctx.fillStyle='#3d3b55';
  ctx.textAlign='left';
  ctx.textBaseline='bottom';
  ctx.fillText('ID: '+DATA.certId, PL, H-22);

  document.getElementById('loadingMsg').textContent = '';
}

// ── Download ───────────────────────────────────────────────────────
function downloadCert(){
  const btn = document.getElementById('dlBtn');
  btn.innerHTML = '⏳ Downloading…';
  btn.disabled  = true;

  // Use jsPDF to produce a proper PDF from the canvas
  const script = document.createElement('script');
  script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
  script.onload = () => {
    try {
      const { jsPDF } = window.jspdf;
      const imgData = canvas.toDataURL('image/png', 1.0);
      const pdf = new jsPDF({ orientation:'landscape', unit:'px', format:[W,H] });
      pdf.addImage(imgData,'PNG',0,0,W,H);
      pdf.save('campusly_certificate_<?= $cert_id ?>.pdf');
    } catch(e){
      // Fallback: plain PNG download
      const a = document.createElement('a');
      a.href     = canvas.toDataURL('image/png',1.0);
      a.download = 'campusly_certificate_<?= $cert_id ?>.png';
      a.click();
    }
    btn.innerHTML = '⬇️ Download Certificate';
    btn.disabled  = false;
  };
  script.onerror = () => {
    // jsPDF CDN failed — fallback to PNG
    const a = document.createElement('a');
    a.href     = canvas.toDataURL('image/png',1.0);
    a.download = 'campusly_certificate_<?= $cert_id ?>.png';
    a.click();
    btn.innerHTML = '⬇️ Download Certificate';
    btn.disabled  = false;
  };
  document.head.appendChild(script);
}

// ── Boot ───────────────────────────────────────────────────────────
drawCert();
</script>
</body>
</html>
