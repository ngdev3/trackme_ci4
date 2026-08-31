<style>
.rp-wrap { max-width: 1200px; margin: 0 auto; color:#18243c; }
.rp-head { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin:6px 0 14px; }
.rp-title { font-size:21px; font-weight:900; }
.rp-title small { display:block; font-size:12px; font-weight:700; color:#8190a5; }
.rp-tools { display:flex; gap:8px; align-items:end; flex-wrap:wrap; }
.rp-tools label { display:block; font-size:10px; font-weight:900; letter-spacing:.05em; text-transform:uppercase; color:#718096; margin-bottom:4px; }
.rp-tools input[type=date] { min-height:38px; border:1px solid #dce6f2; border-radius:8px; padding:0 10px; font-weight:700; }
.rp-btn { min-height:38px; display:inline-flex; align-items:center; gap:6px; padding:0 14px; border-radius:8px; font-weight:800; border:0; cursor:pointer; color:#fff; background:linear-gradient(135deg,#1769c2,#0c315f); text-decoration:none; }
.rp-btn.ghost { background:#eef3fa; color:#0c315f; border:1px solid #dce6f2; }
.rp-panel { border:1px solid #dce6f2; border-radius:12px; background:#fff; box-shadow:0 12px 30px rgba(24,36,60,.07); overflow:hidden; }
table.rp { width:100%; border-collapse:separate; border-spacing:0; margin:0; }
table.rp thead th { background:linear-gradient(180deg,#eaf3ff,#fff); color:#516174; font-size:11px; font-weight:900; text-transform:uppercase; padding:11px 12px; white-space:nowrap; border:0; }
table.rp tbody td { border-top:1px solid #eef2f7; padding:9px 12px; font-size:13px; vertical-align:middle; }
table.rp tbody tr:hover { background:rgba(23,105,194,.04); }
table.rp .num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
table.rp tfoot td { border-top:2px solid #cfdcec; padding:11px 12px; font-weight:900; background:#f7fafd; }
.rp .dr { color:#c62828; font-weight:800; } .rp .cr { color:#1f7a4d; font-weight:800; } .rp .nil { color:#7a8699; }
.rp-pill { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:800; }
.rp-pill.dr { background:#fdecec; color:#c62828; } .rp-pill.cr { background:#e8f5ec; color:#1f7a4d; } .rp-pill.nil { background:#eef1f5; color:#7a8699; }
.rp-grp { background:#f4f8fd !important; font-weight:900; color:#0c315f; }
.rp-empty { padding:34px; text-align:center; color:#8190a5; font-weight:700; }
.rp-note { font-size:11px; color:#8190a5; margin-top:8px; }
.rp-two { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media (max-width: 900px){ .rp-two { grid-template-columns:1fr; } }
.rp-diff-ok { color:#1f7a4d; } .rp-diff-bad { color:#c62828; }
</style>
