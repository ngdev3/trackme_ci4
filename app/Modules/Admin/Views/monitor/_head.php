<link href="<?php echo base_url(); ?>assets/global/css/components-rounded.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>assets/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css" />
<style>
    .mon-scope { color: #18243c; overflow-x: hidden; }
    .mon-shell { max-width: 1480px; margin: 0 auto; min-width: 0; }

    /* Hero + tabs */
    .mon-hero { position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
        padding: 20px 24px; margin-bottom: 14px; border-radius: 14px; color: #fff;
        background: radial-gradient(circle at 88% -30%, rgba(120,170,255,.5), transparent 38%), linear-gradient(125deg, #0f2748, #1d4ed8 58%, #3b1e6e);
        box-shadow: 0 18px 42px rgba(16,32,72,.28); }
    .mon-hero-l { display: flex; align-items: center; gap: 14px; position: relative; z-index: 1; }
    .mon-hero-ic { width: 50px; height: 50px; border-radius: 13px; display: grid; place-items: center; font-size: 21px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.24); }
    .mon-title { margin: 0; font-size: 22px; font-weight: 900; }
    .mon-title small { display: block; font-size: 12px; font-weight: 700; color: rgba(235,242,255,.85); margin-top: 3px; }
    .mon-filter { display: flex; align-items: flex-end; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
    .mon-filter label { display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: rgba(235,242,255,.8); margin-bottom: 4px; }
    .mon-filter input, .mon-filter select { min-height: 38px; border: 1px solid rgba(255,255,255,.3); border-radius: 9px; background: rgba(255,255,255,.14); color: #fff; font-weight: 700; font-size: 12.5px; padding: 0 10px; }
    .mon-filter input::placeholder { color: rgba(255,255,255,.65); }
    .mon-filter select option { color: #18243c; }
    .mon-filter .mon-go { min-height: 38px; border-radius: 9px; background: #fff; color: #1740b5; border: 0; cursor: pointer; padding: 0 16px; font-weight: 800; font-size: 12.5px; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
    .mon-filter .mon-go:hover { background: #eef3ff; }

    .mon-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; }
    .mon-tab { display: inline-flex; align-items: center; gap: 7px; padding: 9px 15px; border-radius: 10px; font-size: 13px; font-weight: 800; color: #475569; background: #fff; border: 1px solid #e3e9f2; text-decoration: none; transition: all .14s ease; }
    .mon-tab:hover { color: #1d4ed8; border-color: #b7cdf2; }
    .mon-tab.on { background: #1d4ed8; color: #fff; border-color: #1d4ed8; box-shadow: 0 8px 20px rgba(29,78,216,.28); }
    .mon-tab .lock { font-size: 10px; opacity: .8; }

    /* KPI grid */
    .mon-kpis { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
    .mon-kpi { display: flex; align-items: center; gap: 11px; padding: 14px 15px; border: 1px solid #e3e9f2; border-radius: 13px; background: #fff; box-shadow: 0 10px 26px rgba(24,36,60,.06); }
    .mon-kpi-ic { width: 40px; height: 40px; border-radius: 11px; display: grid; place-items: center; font-size: 17px; color: #fff; flex: none; }
    .mon-kpi-t span { display: block; font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .03em; color: #7a8aa0; }
    .mon-kpi-t strong { display: block; margin-top: 2px; font-size: 20px; font-weight: 900; color: #18243c; }
    .ic-blue { background: linear-gradient(135deg,#2563eb,#1746a2); } .ic-violet { background: linear-gradient(135deg,#7c3aed,#55208f); }
    .ic-green { background: linear-gradient(135deg,#1f9d70,#0c7048); } .ic-amber { background: linear-gradient(135deg,#e08a12,#9a5b06); }
    .ic-slate { background: linear-gradient(135deg,#47566d,#2a3547); } .ic-red { background: linear-gradient(135deg,#e5484d,#a11722); }
    .ic-cyan { background: linear-gradient(135deg,#0ea5e9,#0369a1); } .ic-pink { background: linear-gradient(135deg,#db2777,#9d174d); }

    .mon-panel { border: 1px solid #e3e9f2; border-radius: 14px; background: #fff; box-shadow: 0 12px 30px rgba(24,36,60,.06); margin-bottom: 16px; }
    .mon-panel-h { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 18px; border-bottom: 1px solid #eef2f7; }
    .mon-panel-h b { font-size: 14px; font-weight: 900; color: #0f172a; }
    .mon-panel-b { padding: 16px 18px; overflow-x: auto; }
    .mon-grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; }
    .mon-grid-2 > .mon-panel { margin-bottom: 0; min-width: 0; }

    /* tables */
    table.mon-tbl { width: 100% !important; }
    table.mon-tbl thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .03em; color: #64748b; font-weight: 800; border-bottom: 2px solid #eef2f7; padding: 9px 8px; }
    table.mon-tbl tbody td { font-size: 13px; vertical-align: middle; border-top: 1px solid #f1f5f9; padding: 9px 8px; }
    .mon-user b { display: block; font-weight: 800; color: #0f172a; } .mon-user small { color: #94a3b8; }
    .mon-url { color: #334155; font-weight: 600; } .mon-url i { color: #94a3b8; margin-right: 4px; }
    .mon-when { font-weight: 700; color: #334155; white-space: nowrap; }
    .mon-ip { font-family: ui-monospace, Menlo, monospace; font-weight: 700; color: #0369a1; background: #eff6ff; padding: 2px 8px; border-radius: 6px; }
    .mon-ua { color: #64748b; font-size: 12px; } .mon-serial { color: #94a3b8; font-weight: 700; }
    .mon-btn { display: inline-flex; align-items: center; gap: 7px; min-height: 38px; padding: 0 15px; border-radius: 9px; font-weight: 800; font-size: 12.5px; border: 1px solid #dce6f2; background: #f5f8ff; color: #1d4ed8; text-decoration: none; cursor: pointer; }
    .mon-btn:hover { background: #e6efff; }

    /* lists */
    .mon-list { list-style: none; margin: 0; padding: 0; }
    .mon-list li { display: flex; align-items: center; gap: 11px; padding: 10px 0; border-top: 1px solid #f1f5f9; }
    .mon-list li:first-child { border-top: 0; }
    .mon-bar { flex: 1; height: 8px; border-radius: 5px; background: #eef2f7; overflow: hidden; }
    .mon-bar i { display: block; height: 100%; background: linear-gradient(90deg,#2563eb,#7c3aed); }
    .mon-badge { font-size: 11px; font-weight: 800; color: #475569; white-space: nowrap; }
    .mon-dot { width: 9px; height: 9px; border-radius: 50%; background: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.18); flex: none; }

    /* timeline */
    .mon-tl { list-style: none; margin: 0; padding: 0 0 0 6px; }
    .mon-tl li { position: relative; padding: 0 0 16px 26px; border-left: 2px solid #eef2f7; }
    .mon-tl li:last-child { border-left-color: transparent; }
    .mon-tl-dot { position: absolute; left: -8px; top: 2px; width: 15px; height: 15px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 1px #e3e9f2; }
    .mon-tl-b { background: #fff; }
    .mon-tl-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .mon-tl-user { font-weight: 800; color: #0f172a; font-size: 13px; }
    .mon-tl-time { color: #94a3b8; font-size: 11px; font-weight: 700; }
    .mon-tl-detail { color: #475569; font-size: 12.5px; margin-top: 2px; }
    .mon-kind { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 1px 7px; border-radius: 999px; }
    .k-visit { background:#eff6ff; color:#1d4ed8; } .k-login { background:#f5f3ff; color:#6d28d9; }
    .k-entry_create { background:#dcfce7; color:#15803d; } .k-entry_update { background:#dbeafe; color:#1d4ed8; } .k-entry_delete { background:#fee2e2; color:#b91c1c; }

    /* anomaly cards */
    .mon-flags { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px,1fr)); gap: 12px; }
    .mon-flag { border: 1px solid #e3e9f2; border-left-width: 4px; border-radius: 12px; background: #fff; padding: 13px 15px; box-shadow: 0 8px 20px rgba(24,36,60,.05); }
    .mon-flag.high { border-left-color: #e5484d; } .mon-flag.med { border-left-color: #e08a12; } .mon-flag.low { border-left-color: #0ea5e9; }
    .mon-flag-t { font-weight: 800; color: #0f172a; font-size: 13.5px; }
    .mon-flag-ty { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; }
    .mon-flag-d { color: #64748b; font-size: 12px; margin-top: 4px; }
    .mon-empty { text-align: center; color: #94a3b8; font-weight: 700; padding: 34px 10px; }

    /* entry-audit reused classes */
    .et-mod { display:block; font-weight:800; color:#0f172a; } .et-slug { display:block; font-size:11px; color:#94a3b8; }
    .et-entry-link { display:inline-flex; align-items:center; gap:5px; font-weight:800; color:#1d4ed8; text-decoration:none; padding:3px 9px; border:1px solid #dbe4f3; border-radius:8px; background:#f5f8ff; }
    .et-entry-link:hover { background:#e6efff; } .et-entry-link i { font-size:11px; opacity:.75; }
    .et-act { display:inline-block; font-size:11px; font-weight:800; padding:2px 9px; border-radius:999px; text-transform:uppercase; }
    .et-a-create { background:#dcfce7; color:#15803d; } .et-a-update { background:#dbeafe; color:#1d4ed8; } .et-a-delete { background:#fee2e2; color:#b91c1c; }
    .et-src { display:inline-block; font-size:11px; font-weight:800; padding:2px 8px; border-radius:6px; }
    .et-src-web { background:#eff6ff; color:#1d4ed8; } .et-src-app { background:#ecfdf5; color:#047857; } .et-src-sys { background:#f5f3ff; color:#6d28d9; }
    .et-ip { font-family: ui-monospace, Menlo, monospace; font-weight:700; color:#334155; }
    .et-loc { font-weight:700; color:#0ea5e9; white-space:nowrap; text-decoration:none; } .et-loc i { margin-right:3px; }
    .et-when { font-weight:700; color:#334155; } .et-user { font-weight:700; color:#334155; }

    #monMap { width: 100%; height: 420px; border: 0; border-radius: 12px; }

    @media (max-width: 1200px) { .mon-kpis { grid-template-columns: repeat(3,1fr); } .mon-grid-2 { grid-template-columns: 1fr; } }
    @media (max-width: 640px)  { .mon-kpis { grid-template-columns: repeat(2,1fr); } }
</style>
