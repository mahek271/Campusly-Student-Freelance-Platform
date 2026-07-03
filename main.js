// ── NAV STICKY ──
window.addEventListener('scroll',()=>{
  const nav=document.querySelector('.nav');
  if(nav) nav.classList.toggle('sticky',scrollY>50);
});

// ── TOAST ──
function toast(msg,type='info'){
  const icons={success:'✅',error:'❌',info:'ℹ️',warning:'⚠️'};
  const cls={success:'t-s',error:'t-e',info:'t-i',warning:'t-w'};
  const area=document.getElementById('toasts');
  if(!area) return;
  const el=document.createElement('div');
  el.className=`toast ${cls[type]||'t-i'}`;
  el.innerHTML=`<span style="font-size:18px">${icons[type]}</span><span>${msg}</span>`;
  area.appendChild(el);
  setTimeout(()=>{el.classList.add('out');setTimeout(()=>el.remove(),300);},3500);
}

// ── MODAL ──
function openModal(id){const m=document.getElementById(id);if(m){m.classList.add('show');document.body.style.overflow='hidden';}}
function closeModal(id){const m=document.getElementById(id);if(m){m.classList.remove('show');document.body.style.overflow='';}}
document.addEventListener('click',e=>{if(e.target.classList.contains('overlay'))e.target.classList.remove('show')&&(document.body.style.overflow='');});

// ── CONFIRM DELETE ──
function confirmAction(msg, url){
  if(confirm(msg)) window.location.href = url;
}

// ── STARS RATING ──
function initStars(container){
  const stars=container.querySelectorAll('.star-btn');
  const input=container.querySelector('input[name="rating"]');
  stars.forEach((s,i)=>{
    s.addEventListener('click',()=>{
      if(input) input.value=i+1;
      stars.forEach((x,j)=>x.classList.toggle('on',j<=i));
    });
    s.addEventListener('mouseover',()=>stars.forEach((x,j)=>x.classList.toggle('on',j<=i)));
    s.addEventListener('mouseout',()=>{
      const v=input?parseInt(input.value||0):0;
      stars.forEach((x,j)=>x.classList.toggle('on',j<v));
    });
  });
}
document.querySelectorAll('.star-rating').forEach(initStars);

// ── CHAR COUNTER ──
document.querySelectorAll('[data-maxlen]').forEach(el=>{
  const max=parseInt(el.dataset.maxlen);
  const counter=document.querySelector(`[data-counter="${el.id}"]`);
  el.addEventListener('input',()=>{
    if(counter) counter.textContent=`${el.value.length}/${max}`;
    if(el.value.length>max) el.value=el.value.slice(0,max);
  });
});

// ── BID VALIDATION (live) ──
const bidInput=document.getElementById('bid_amount');
const taskBudget=document.getElementById('task_budget_val');
if(bidInput && taskBudget){
  bidInput.addEventListener('input',()=>{
    const budget=parseFloat(taskBudget.value||0);
    const bid=parseFloat(bidInput.value||0);
    const msg=document.getElementById('bid-msg');
    if(!msg) return;
    if(bid<100){ msg.className='ferr'; msg.textContent='Minimum bid is ₹100.'; }
    else if(bid>budget*1.5){ msg.className='ferr'; msg.textContent=`Bid too high. Max allowed: ₹${(budget*1.5).toFixed(0)}`; }
    else if(bid>budget){ msg.className='fhelp'; msg.textContent='Your bid is above the task budget. Employer may negotiate.'; }
    else{ msg.className='fhelp'; msg.textContent='✅ Bid looks good!'; }
  });
}

// ── FILE PREVIEW ──
document.querySelectorAll('input[type="file"]').forEach(inp=>{
  inp.addEventListener('change',()=>{
    const preview=document.getElementById(inp.dataset.preview);
    if(!preview) return;
    const file=inp.files[0];
    if(!file) return;
    if(file.type.startsWith('image/')){
      const reader=new FileReader();
      reader.onload=e=>{preview.src=e.target.result;preview.style.display='block';};
      reader.readAsDataURL(file);
    } else {
      preview.textContent=`📎 ${file.name}`;
    }
  });
});

// ── SEARCH FILTER ──
const searchInput = document.getElementById('live-search');
if(searchInput){
  searchInput.addEventListener('input',()=>{
    const q=searchInput.value.toLowerCase();
    document.querySelectorAll('.searchable').forEach(el=>{
      el.style.display=el.textContent.toLowerCase().includes(q)?'':'none';
    });
  });
}
