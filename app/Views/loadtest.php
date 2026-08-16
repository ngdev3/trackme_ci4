<?php /** @var string $apiBase */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Load Test Console — HissabKitaab</title>
<style>
  :root{--bg:#0b1220;--panel:#131c2e;--line:#233248;--txt:#e8eefc;--dim:#8aa0c6;--cyan:#22d3ee;--grn:#34d399;--yel:#fbbf24;--red:#f87171;--mag:#a78bfa;--acc:#3b82f6}
  *{box-sizing:border-box}
  body{margin:0;background:radial-gradient(circle at 10% -10%,rgba(59,130,246,.15),transparent 40%),var(--bg);color:var(--txt);font:14px/1.5 -apple-system,Segoe UI,Roboto,Arial,sans-serif}
  .wrap{max-width:1200px;margin:0 auto;padding:20px 16px 60px}
  h1{font-size:22px;margin:0 0 2px} .sub{color:var(--dim);font-size:13px;margin:0 0 16px}
  .warn{background:rgba(251,191,36,.12);border:1px solid rgba(251,191,36,.35);color:#fcd34d;padding:10px 12px;border-radius:12px;font-size:12.5px;margin-bottom:16px}
  .panel{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:16px;margin-bottom:16px}
  .grid{display:grid;gap:12px}
  .cfg{grid-template-columns:repeat(auto-fit,minmax(180px,1fr))}
  label{display:block;font-size:11.5px;text-transform:uppercase;letter-spacing:.4px;color:var(--dim);margin:0 0 5px;font-weight:700}
  input,select{width:100%;background:#0e1728;border:1px solid var(--line);border-radius:10px;color:var(--txt);padding:10px 11px;font-size:13.5px;outline:0}
  input:focus,select:focus{border-color:var(--acc)}
  .row{display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-top:12px}
  button{border:0;border-radius:11px;padding:12px 20px;font-weight:800;font-size:14px;cursor:pointer}
  .start{background:linear-gradient(135deg,#3b82f6,#22d3ee);color:#04101f}
  .stop{background:rgba(248,113,113,.15);color:var(--red);border:1px solid rgba(248,113,113,.4)}
  button:disabled{opacity:.5;cursor:not-allowed}
  .cards{grid-template-columns:repeat(auto-fit,minmax(140px,1fr))}
  .card{background:#0e1728;border:1px solid var(--line);border-radius:14px;padding:13px 14px}
  .card .k{font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--dim);font-weight:700}
  .card .v{font-size:24px;font-weight:900;margin-top:4px}
  .card small{color:var(--dim);font-size:11px}
  .charts{grid-template-columns:1fr 1fr}
  @media(max-width:760px){.charts{grid-template-columns:1fr}}
  .ct{font-size:12.5px;font-weight:800;color:var(--dim);text-transform:uppercase;letter-spacing:.4px;margin:0 0 8px}
  canvas{width:100%;height:180px;display:block}
  table{width:100%;border-collapse:collapse;font-size:12.5px}
  th,td{padding:8px 8px;text-align:right;border-bottom:1px solid var(--line)}
  th:first-child,td:first-child{text-align:left}
  th{color:var(--dim);font-size:11px;text-transform:uppercase;letter-spacing:.3px}
  .score{display:flex;align-items:center;gap:18px}
  .grade{width:96px;height:96px;border-radius:50%;display:grid;place-items:center;font-size:42px;font-weight:900;flex:0 0 auto}
  .mono{font-family:ui-monospace,Consolas,monospace}
  .pill{display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:800}
  .ok{color:var(--grn)} .bad{color:var(--red)} .mid{color:var(--yel)}
  textarea{width:100%;height:120px;background:#0e1728;border:1px solid var(--line);border-radius:10px;color:var(--dim);font-family:ui-monospace,Consolas,monospace;font-size:11px;padding:10px}
</style>
</head>
<body>
<div class="wrap">
  <h1>⚡ Load Test Console</h1>
  <p class="sub">Browser-driven concurrent load with live analytics — HissabKitaab / ERP API.</p>
  <div class="warn">⚠️ Runs real requests from <b>your browser</b>. Only point it at your own <b>staging / dev</b> server. A browser caps ~6 sockets per host, so very high VU counts queue client-side (use the k6 / node scripts for 500+). Keep it read-only.</div>

  <!-- CONFIG -->
  <div class="panel">
    <div class="grid cfg">
      <div><label>API base URL</label><input id="base" value="<?= esc($apiBase, 'attr') ?>"></div>
      <div><label>Login (email/mobile)</label><input id="user" value="rajatinvoice@gmail.com"></div>
      <div><label>Password</label><input id="pass" type="password" value=""></div>
      <div><label>Scenario</label>
        <select id="scenario">
          <option value="workflow">Full workflow (me→dash→list→stmt→report→companies)</option>
          <option value="me">/me only (heaviest)</option>
          <option value="dashboard">/dashboard only</option>
          <option value="list">/transactions/list only</option>
          <option value="statement">/transactions/statement only</option>
          <option value="custom">Custom endpoint…</option>
          <option value="loginstorm">Login storm (POST /auth/login)</option>
        </select>
      </div>
      <div><label>Custom path (if custom)</label><input id="custom" placeholder="/companies"></div>
      <div><label>Virtual users (concurrency)</label><input id="vus" type="number" value="10" min="1" max="300"></div>
      <div><label>Duration (seconds)</label><input id="dur" type="number" value="20" min="3" max="300"></div>
      <div><label>Request timeout (ms)</label><input id="to" type="number" value="15000" min="1000"></div>
    </div>
    <div class="row">
      <button class="start" id="startBtn">▶ Start load</button>
      <button class="stop" id="stopBtn" disabled>■ Stop</button>
      <span id="phase" style="color:var(--dim);align-self:center"></span>
    </div>
  </div>

  <!-- LIVE CARDS -->
  <div class="grid cards" id="cards">
    <div class="card"><div class="k">Throughput</div><div class="v" id="c_rps">—</div><small>req / sec</small></div>
    <div class="card"><div class="k">Requests</div><div class="v" id="c_total">0</div><small><span id="c_inflight">0</span> in-flight</small></div>
    <div class="card"><div class="k">Avg latency</div><div class="v" id="c_avg">—</div><small>ms</small></div>
    <div class="card"><div class="k">P95 latency</div><div class="v" id="c_p95">—</div><small>target &lt; 1000ms</small></div>
    <div class="card"><div class="k">Error rate</div><div class="v" id="c_err">—</div><small>target &lt; 1%</small></div>
    <div class="card"><div class="k">Timeouts</div><div class="v" id="c_to">0</div><small>&gt; timeout ms</small></div>
  </div>

  <!-- CHARTS -->
  <div class="grid charts" style="margin-top:16px">
    <div class="panel"><p class="ct">Latency over time (avg ● p95 ●)</p><canvas id="chLat"></canvas></div>
    <div class="panel"><p class="ct">Throughput over time (req/s)</p><canvas id="chRps"></canvas></div>
    <div class="panel"><p class="ct">Latency percentiles</p><canvas id="chPct"></canvas></div>
    <div class="panel"><p class="ct">Status distribution</p><canvas id="chStat"></canvas></div>
  </div>

  <!-- SCORE + TABLE -->
  <div class="panel">
    <p class="ct">Performance score</p>
    <div class="score">
      <div class="grade" id="grade" style="background:#0e1728;color:var(--dim)">—</div>
      <div>
        <div id="verdict" style="font-size:18px;font-weight:800">Run a test to see the score</div>
        <div class="sub" id="verdictNote" style="margin:4px 0 0">P95 &lt; 1s and error rate &lt; 1% = healthy.</div>
      </div>
    </div>
  </div>

  <div class="panel">
    <p class="ct">Per-endpoint breakdown</p>
    <div style="overflow-x:auto"><table id="tbl">
      <thead><tr><th>Endpoint</th><th>count</th><th>rps</th><th>avg</th><th>p50</th><th>p90</th><th>p95</th><th>p99</th><th>max</th><th>err%</th><th>4xx</th><th>5xx</th><th>to</th></tr></thead>
      <tbody></tbody>
    </table></div>
  </div>

  <div class="panel">
    <p class="ct">Export result <span class="sub" style="text-transform:none">— copy this JSON and send it back for analysis</span></p>
    <button class="start" id="exportBtn" style="margin-bottom:10px">⤓ Generate JSON</button>
    <textarea id="export" readonly placeholder="Run a test, then click Generate JSON."></textarea>
  </div>
</div>

<script>
const $=id=>document.getElementById(id);
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const rand=(a,b)=>a+Math.random()*(b-a);
const fmt=(x,d=0)=>Number.isFinite(x)?Number(x).toFixed(d):'—';

// ---- scenarios (read-only GETs) ----
const SCN={
  workflow:[['GET','/me'],['GET','/dashboard'],['GET','/transactions/list'],['GET','/transactions/statement'],['GET','/transactions/report'],['GET','/companies']],
  me:[['GET','/me']], dashboard:[['GET','/dashboard']], list:[['GET','/transactions/list']],
  statement:[['GET','/transactions/statement']],
};
function steps(){
  const s=$('scenario').value;
  if(s==='custom'){const p=$('custom').value.trim()||'/companies';return [['GET',p]];}
  if(s==='loginstorm')return [['POST','/auth/login']];
  return SCN[s]||SCN.workflow;
}

let state=null; // running state

function pct(sorted,p){if(!sorted.length)return 0;const i=Math.min(sorted.length-1,Math.ceil(p/100*sorted.length)-1);return sorted[i];}
function stat(samples,wall){
  const ms=samples.map(s=>s.ms).sort((a,b)=>a-b),n=ms.length;
  const errs=samples.filter(s=>!s.ok).length,c4=samples.filter(s=>s.status>=400&&s.status<500).length,c5=samples.filter(s=>s.status>=500).length,to=samples.filter(s=>s.timeout).length;
  return{n,rps:n/wall,avg:n?ms.reduce((a,b)=>a+b,0)/n:0,p50:pct(ms,50),p90:pct(ms,90),p95:pct(ms,95),p99:pct(ms,99),max:ms[n-1]||0,errPct:n?errs/n*100:0,c4,c5,to};
}

// ---- canvas helpers ----
function prep(c){const dpr=devicePixelRatio||1;const r=c.getBoundingClientRect();c.width=r.width*dpr;c.height=180*dpr;const x=c.getContext('2d');x.setTransform(dpr,0,0,dpr,0,0);x.clearRect(0,0,r.width,180);return[x,r.width,180];}
function axis(x,w,h){x.strokeStyle='#233248';x.lineWidth=1;x.beginPath();x.moveTo(34,8);x.lineTo(34,h-20);x.lineTo(w-6,h-20);x.stroke();x.fillStyle='#8aa0c6';x.font='10px monospace';}
function line(cv,series,color,maxY){const[x,w,h]=prep(cv);axis(x,w,h);if(!series.length)return maxY;const my=maxY||Math.max(1,...series.flat().map(p=>p.y));const X=i=>34+(w-40)*(series[0].length<=1?0.5:i/(series[0].length-1));const Y=v=>h-20-(h-28)*Math.min(1,v/my);x.fillText(fmt(my),2,14);x.fillText('0',24,h-22);series.forEach((s,si)=>{x.strokeStyle=color[si];x.lineWidth=2;x.beginPath();s.forEach((p,i)=>{i?x.lineTo(X(i),Y(p.y)):x.moveTo(X(i),Y(p.y))});x.stroke();});return my;}
function bars(cv,items){const[x,w,h]=prep(cv);const max=Math.max(1,...items.map(i=>i.v));const bw=(w-44)/items.length;items.forEach((it,i)=>{const bh=(h-34)*(it.v/max);x.fillStyle=it.c;x.fillRect(38+i*bw+4,h-20-bh,bw-8,bh);x.fillStyle='#8aa0c6';x.font='10px monospace';x.fillText(it.l,38+i*bw+4,h-6);x.fillStyle='#e8eefc';x.fillText(fmt(it.v),38+i*bw+4,h-24-bh<12?12:h-24-bh);});}
function donut(cv,items){const[x,w,h]=prep(cv);const cx=w*0.32,cy=h/2,r=Math.min(cx,cy)-8;const tot=items.reduce((a,i)=>a+i.v,0)||1;let a=-Math.PI/2;items.forEach(it=>{const ang=it.v/tot*2*Math.PI;x.beginPath();x.moveTo(cx,cy);x.arc(cx,cy,r,a,a+ang);x.closePath();x.fillStyle=it.c;x.fill();a+=ang;});x.fillStyle='#0e1728';x.beginPath();x.arc(cx,cy,r*0.55,0,7);x.fill();let ly=24;items.forEach(it=>{x.fillStyle=it.c;x.fillRect(w*0.62,ly-9,11,11);x.fillStyle='#e8eefc';x.font='12px monospace';x.fillText(it.l+': '+it.v,w*0.62+17,ly);ly+=20;});}

async function login(base){
  if($('scenario').value==='loginstorm')return null; // login is the workload itself
  const r=await fetch(base+'/auth/login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({login:$('user').value,password:$('pass').value})});
  const j=await r.json().catch(()=>({}));
  if(!j.token)throw new Error('login failed ('+r.status+')');
  return j.token;
}

function updateUI(){
  if(!state)return;
  const wall=(performance.now()-state.t0)/1000;
  const s=stat(state.samples,Math.max(0.1,wall));
  $('c_rps').textContent=fmt(s.rps,1);$('c_total').textContent=s.n;$('c_inflight').textContent=state.inflight;
  $('c_avg').textContent=fmt(s.avg);$('c_p95').textContent=fmt(s.p95);
  $('c_err').innerHTML='<span class="'+(s.errPct<1?'ok':s.errPct<5?'mid':'bad')+'">'+fmt(s.errPct,1)+'%</span>';
  $('c_p95').className='v '+(s.p95<1000?'ok':s.p95<2500?'mid':'bad');
  $('c_to').textContent=s.to;
  // time buckets (1s)
  const buckets={};for(const x of state.samples){const b=Math.floor((x.t-state.t0)/1000);(buckets[b]=buckets[b]||[]).push(x);}
  const keys=Object.keys(buckets).map(Number).sort((a,b)=>a-b);
  const avgS=keys.map(k=>({y:buckets[k].reduce((a,x)=>a+x.ms,0)/buckets[k].length}));
  const p95S=keys.map(k=>{const m=buckets[k].map(x=>x.ms).sort((a,b)=>a-b);return{y:pct(m,95)}});
  const rpsS=keys.map(k=>({y:buckets[k].length}));
  line($('chLat'),[avgS,p95S],['#22d3ee','#a78bfa']);
  line($('chRps'),[rpsS],['#34d399']);
  bars($('chPct'),[{l:'p50',v:s.p50,c:'#34d399'},{l:'p90',v:s.p90,c:'#22d3ee'},{l:'p95',v:s.p95,c:'#fbbf24'},{l:'p99',v:s.p99,c:'#f87171'}]);
  const ok2=state.samples.filter(x=>x.status>=200&&x.status<400).length;
  donut($('chStat'),[{l:'2xx/3xx',v:ok2,c:'#34d399'},{l:'4xx',v:s.c4,c:'#fbbf24'},{l:'5xx',v:s.c5,c:'#f87171'},{l:'timeout',v:s.to,c:'#a78bfa'}]);
}

function finalize(){
  const wall=(performance.now()-state.t0)/1000;
  const byName={};for(const x of state.samples){(byName[x.name]=byName[x.name]||[]).push(x);}
  const tb=$('tbl').querySelector('tbody');tb.innerHTML='';
  const rowFor=(name,arr)=>{const s=stat(arr,wall);return `<tr><td>${name}</td><td>${s.n}</td><td>${fmt(s.rps,1)}</td><td>${fmt(s.avg)}</td><td>${fmt(s.p50)}</td><td>${fmt(s.p90)}</td><td class="${s.p95<1000?'ok':s.p95<2500?'mid':'bad'}">${fmt(s.p95)}</td><td>${fmt(s.p99)}</td><td>${fmt(s.max)}</td><td class="${s.errPct<1?'ok':'bad'}">${fmt(s.errPct,1)}%</td><td>${s.c4}</td><td>${s.c5}</td><td>${s.to}</td></tr>`;};
  Object.keys(byName).sort().forEach(n=>tb.insertAdjacentHTML('beforeend',rowFor(n,byName[n])));
  tb.insertAdjacentHTML('beforeend','<tr style="font-weight:800;background:#0e1728">'+rowFor('ALL',state.samples).slice(4));
  // score
  const s=stat(state.samples,wall);
  let sc=100;
  if(s.p95>1000)sc-=Math.min(45,(s.p95-1000)/60);
  if(s.errPct>1)sc-=Math.min(45,(s.errPct-1)*6);
  if(s.to>0)sc-=Math.min(15,s.to);
  sc=Math.max(0,Math.round(sc));
  const gr=sc>=90?'A':sc>=75?'B':sc>=60?'C':sc>=40?'D':'F';
  const col=sc>=75?'#34d399':sc>=50?'#fbbf24':'#f87171';
  $('grade').textContent=gr;$('grade').style.background=col+'22';$('grade').style.color=col;
  const pass=sc>=75&&s.errPct<1;
  $('verdict').innerHTML=(pass?'<span class="ok">PASS</span>':'<span class="bad">NEEDS WORK</span>')+' — grade '+gr+' · '+sc+'/100'+(s.p95>=1000?' <span class="mid">(P95 &gt; 1s)</span>':'');
  $('verdictNote').textContent=`P95 ${fmt(s.p95)}ms · error ${fmt(s.errPct,1)}% · throughput ${fmt(s.rps,1)} req/s · ${s.n} requests over ${fmt(wall,1)}s`;
  state.summary={wall,overall:s,byName:Object.fromEntries(Object.entries(byName).map(([k,v])=>[k,stat(v,wall)])),score:sc,grade:gr};
}

async function run(){
  const base=$('base').value.replace(/\/$/,'');
  const vus=Math.max(1,Math.min(300,+$('vus').value||10));
  const dur=Math.max(3,Math.min(300,+$('dur').value||20));
  const toMs=Math.max(1000,+$('to').value||15000);
  $('startBtn').disabled=true;$('stopBtn').disabled=false;$('phase').textContent='authenticating…';
  let token;try{token=await login(base);}catch(e){$('phase').textContent='';$('startBtn').disabled=false;$('stopBtn').disabled=true;alert(e.message);return;}
  const scn=steps();
  state={samples:[],inflight:0,stop:false,t0:performance.now(),config:{base,vus,dur,toMs,scenario:$('scenario').value}};
  $('phase').textContent=`running ${vus} VUs for ${dur}s…`;
  const deadline=performance.now()+dur*1000;
  const timer=setInterval(updateUI,500);
  async function hit(method,path){
    state.inflight++;const ac=new AbortController();const t=setTimeout(()=>ac.abort(),toMs);const st=performance.now();
    const opt={method,signal:ac.signal,headers:{}};
    if(method==='POST'&&path==='/auth/login'){opt.headers['Content-Type']='application/json';opt.body=JSON.stringify({login:$('user').value,password:$('pass').value});}
    else if(token)opt.headers['Authorization']='Bearer '+token;
    try{const res=await fetch(base+path,opt);await res.arrayBuffer();state.samples.push({t:performance.now(),ms:performance.now()-st,status:res.status,ok:res.ok,timeout:false,name:method+' '+path});}
    catch(e){state.samples.push({t:performance.now(),ms:performance.now()-st,status:0,ok:false,timeout:e.name==='AbortError',name:method+' '+path});}
    finally{clearTimeout(t);state.inflight--;}
  }
  async function worker(){while(!state.stop&&performance.now()<deadline){for(const[m,p]of scn){if(state.stop)break;await hit(m,p);await sleep(rand(120,520));}}}
  await Promise.all(Array.from({length:vus},worker));
  clearInterval(timer);updateUI();finalize();
  $('phase').textContent='done.';$('startBtn').disabled=false;$('stopBtn').disabled=true;
}

$('startBtn').onclick=run;
$('stopBtn').onclick=()=>{if(state){state.stop=true;$('phase').textContent='stopping…';}};
$('exportBtn').onclick=()=>{if(!state||!state.summary){$('export').value='Run a test first.';return;}$('export').value=JSON.stringify({when:new Date().toISOString(),config:state.config,summary:state.summary},null,2);$('export').select();};
</script>
</body>
</html>
