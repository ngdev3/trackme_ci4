<style>
   .dashboard-page {
      width: 100%;
      overflow: visible;
   }

   .dashboard-page h5 {
      position: relative;
      margin: 24px 0 14px;
      padding-left: 14px;
      color: var(--tm-ink, #18243c);
      font-weight: 800;
      letter-spacing: 0;
   }

   .dashboard-page>div>h5:before,
   .dashboard-page h5:before {
      content: "";
      position: absolute;
      top: 3px;
      left: 0;
      width: 5px;
      height: 18px;
      border-radius: 999px;
      background: linear-gradient(180deg, var(--tm-brand, #1769c2), var(--tm-accent, #f0a020));
   }

   .dashboard-page hr {
      margin: 22px 0;
      border: 0;
      border-top: 1px dashed var(--tm-line, #dce6f2);
   }

   /* ===================== WELCOME HERO ===================== */
   .dash-hero {
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 22px;
      flex-wrap: wrap;
      margin: 4px 0 6px;
      padding: 22px 26px;
      border-radius: 18px;
      color: #fff;
      background:
         radial-gradient(circle at 88% -20%, color-mix(in srgb, var(--tm-accent, #f0a020) 55%, transparent), transparent 42%),
         linear-gradient(130deg, var(--tm-brand, #1769c2), var(--tm-sidebar-end, #091e39) 120%);
      box-shadow: 0 20px 44px rgba(24, 36, 60, .22);
   }

   .dash-hero:after {
      content: "";
      position: absolute;
      right: -60px;
      top: -80px;
      width: 240px;
      height: 240px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .08);
      pointer-events: none;
   }

   .dash-hero-left { position: relative; z-index: 1; min-width: 0; }
   .dash-hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 9px;
      padding: 5px 12px;
      border-radius: 999px;
      background: rgba(255, 255, 255, .16);
      border: 1px solid rgba(255, 255, 255, .2);
      font-size: 11px;
      font-weight: 800;
      letter-spacing: .08em;
      text-transform: uppercase;
   }
   .dash-hero-eyebrow i { font-size: 13px; }
   .dash-hero h3 {
      margin: 0 0 6px;
      color: #fff;
      font-size: 26px;
      font-weight: 900;
      letter-spacing: -.3px;
   }
   .dash-hero p {
      margin: 0;
      max-width: 520px;
      color: rgba(255, 255, 255, .82);
      font-size: 13.5px;
      font-weight: 600;
      line-height: 1.55;
   }

   .dash-hero-clock {
      position: relative;
      z-index: 1;
      flex: 0 0 auto;
      min-width: 170px;
      padding: 16px 20px;
      border-radius: 14px;
      text-align: center;
      background: rgba(255, 255, 255, .14);
      border: 1px solid rgba(255, 255, 255, .22);
      backdrop-filter: blur(8px);
   }
   .dash-hero-time {
      font-size: 30px;
      font-weight: 900;
      line-height: 1;
      font-variant-numeric: tabular-nums;
   }
   .dash-hero-date {
      margin-top: 7px;
      color: rgba(255, 255, 255, .82);
      font-size: 12px;
      font-weight: 700;
      letter-spacing: .03em;
   }

   /* ----- weather card in hero ----- */
   .dash-hero-weather {
      position: relative;
      z-index: 1;
      flex: 0 0 auto;
      width: 300px;
      max-width: 100%;
      padding: 14px 16px;
      border-radius: 14px;
      color: #fff;
      background: rgba(255, 255, 255, .14);
      border: 1px solid rgba(255, 255, 255, .22);
      backdrop-filter: blur(8px);
   }
   .dhw-main { display: flex; align-items: center; gap: 12px; }
   .dhw-emoji { font-size: 32px; line-height: 1; }
   .dhw-temp { font-size: 26px; font-weight: 900; line-height: 1; font-variant-numeric: tabular-nums; }
   .dhw-cond { margin-top: 3px; font-size: 12px; color: rgba(255, 255, 255, .82); font-weight: 600; }
   .dhw-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-top: 10px;
      padding-top: 9px;
      border-top: 1px solid rgba(255, 255, 255, .16);
      font-size: 11.5px;
      font-weight: 700;
      color: rgba(255, 255, 255, .82);
   }
   .dhw-meta b { color: #fff; }
   .dhw-auto {
      display: flex;
      align-items: center;
      gap: 7px;
      margin: 9px 0 0;
      font-size: 11px;
      font-weight: 700;
      color: rgba(255, 255, 255, .88);
      cursor: pointer;
   }
   .dhw-auto input { width: 14px; height: 14px; margin: 0; accent-color: #fff; cursor: pointer; }

   .dhw-edit {
      margin-top: 8px;
      padding: 0;
      border: 0;
      background: transparent;
      color: rgba(255, 255, 255, .92);
      font-size: 11px;
      font-weight: 700;
      text-decoration: underline;
      cursor: pointer;
   }
   .dhw-editor { margin-top: 9px; }
   .dhw-editor input[type="text"] {
      width: 100%;
      height: 34px;
      padding: 7px 10px;
      border-radius: 9px;
      border: 1px solid rgba(255, 255, 255, .35);
      background: rgba(255, 255, 255, .16);
      color: #fff;
      font-size: 12px;
      outline: none;
   }
   .dhw-editor input[type="text"]::placeholder { color: rgba(255, 255, 255, .7); }
   .dhw-actions { display: flex; gap: 7px; margin-top: 7px; }
   .dhw-actions button {
      flex: 1;
      height: 30px;
      border: 0;
      border-radius: 8px;
      font-size: 11px;
      font-weight: 800;
      cursor: pointer;
   }
   #dhwSearch, #dhwPinBtn { background: #fff; color: #13344a; }
   #dhwAutoLoc { background: rgba(255, 255, 255, .18); color: #fff; border: 1px solid rgba(255, 255, 255, .3); }
   .dhw-result {
      display: block;
      width: 100%;
      margin-top: 5px;
      padding: 7px 9px;
      text-align: left;
      border: 0;
      border-radius: 8px;
      background: rgba(255, 255, 255, .14);
      color: #fff;
      font-size: 11.5px;
      font-weight: 700;
      cursor: pointer;
   }
   .dhw-result:hover { background: rgba(255, 255, 255, .28); }
   .dhw-hint { margin-top: 6px; font-size: 11px; color: rgba(255, 255, 255, .82); font-weight: 600; }

   /* current rain chance */
   .dhw-rain {
      margin-top: 8px;
      font-size: 11.5px;
      font-weight: 700;
      color: rgba(255, 255, 255, .9);
   }
   .dhw-rain b { color: #fff; }

   /* 7-day forecast strip */
   .dhw-forecast {
      display: flex;
      gap: 6px;
      margin-top: 10px;
      padding-top: 10px;
      border-top: 1px solid rgba(255, 255, 255, .16);
      overflow-x: auto;
      scrollbar-width: thin;
      scrollbar-color: rgba(255, 255, 255, .35) transparent;
   }
   .dhw-forecast::-webkit-scrollbar { height: 5px; }
   .dhw-forecast::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, .3); border-radius: 4px; }
   .dhw-day {
      flex: 0 0 auto;
      width: 48px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      padding: 7px 4px;
      border-radius: 11px;
      text-align: center;
      background: rgba(255, 255, 255, .1);
      border: 1px solid rgba(255, 255, 255, .14);
   }
   .dhw-day-name { font-size: 9.5px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: rgba(255, 255, 255, .85); }
   .dhw-day-emoji { font-size: 18px; line-height: 1; }
   .dhw-day-temp { font-size: 10.5px; font-weight: 800; color: #fff; white-space: nowrap; }
   .dhw-day-temp small { color: rgba(255, 255, 255, .62); font-weight: 700; }
   .dhw-day-rain { font-size: 9.5px; font-weight: 700; color: #bfe3ff; white-space: nowrap; }

   /* ===================== HORIZONTAL WEATHER BAR ===================== */
   .dash-weather-bar {
      position: relative;
      display: flex;
      align-items: center;
      gap: 20px;
      flex-wrap: wrap;
      margin: 14px 0 6px;
      padding: 16px 22px;
      border-radius: 18px;
      color: #fff;
      background:
         radial-gradient(circle at 97% -40%, color-mix(in srgb, var(--tm-accent, #f0a020) 45%, transparent), transparent 42%),
         linear-gradient(130deg, var(--tm-brand, #1769c2), var(--tm-sidebar-end, #091e39) 125%);
      box-shadow: 0 18px 40px rgba(24, 36, 60, .18);
   }

   .dash-weather-bar .dwb-now { display: flex; align-items: center; gap: 14px; flex: 0 0 auto; }
   .dash-weather-bar .dhw-emoji { font-size: 44px; line-height: 1; }
   .dash-weather-bar .dhw-temp { font-size: 30px; font-weight: 900; line-height: 1; font-variant-numeric: tabular-nums; }
   .dash-weather-bar .dhw-cond { margin-top: 3px; font-size: 13px; font-weight: 600; color: rgba(255, 255, 255, .85); }
   .dwb-now-meta {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 6px 12px;
      margin-top: 7px;
      font-size: 11.5px;
      font-weight: 700;
      color: rgba(255, 255, 255, .82);
   }
   .dwb-now-meta b { color: #fff; }

   .dwb-divider { width: 1px; align-self: stretch; min-height: 64px; background: rgba(255, 255, 255, .2); }

   /* forecast spreads across the remaining width */
   .dash-weather-bar .dhw-forecast {
      flex: 1 1 380px;
      gap: 8px;
      margin: 0;
      padding: 0;
      border: 0;
      justify-content: space-between;
   }
   .dash-weather-bar .dhw-day { flex: 1 1 0; min-width: 56px; }

   .dwb-tools { position: relative; flex: 0 0 auto; display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
   .dwb-tools .dhw-edit { margin: 0; }
   .dwb-tools .dhw-auto { margin: 0; }

   /* location editor as a popover */
   .dash-weather-bar .dhw-editor {
      position: absolute;
      top: calc(100% + 10px);
      right: 0;
      z-index: 40;
      width: 264px;
      padding: 13px;
      border-radius: 13px;
      background: var(--tm-sidebar-end, #0d2747);
      border: 1px solid rgba(255, 255, 255, .18);
      box-shadow: 0 22px 48px rgba(0, 0, 0, .4);
   }

   @media (max-width: 991px) {
      .dwb-divider { display: none; }
      .dwb-tools { width: 100%; align-items: flex-start; }
      .dash-weather-bar .dhw-editor { right: auto; left: 0; }
   }

   /* ===================== KPI STAT TILES ===================== */
   /* Common stat box */
   .stat-soft {
      position: relative;
      padding: 12px 16px;
      border-radius: 13px;
      font-size: 13px;
      font-weight: 800;
      line-height: 1.3;
      white-space: nowrap;
      color: var(--tm-ink, #18243c);
      background: #fff;
      border: 1px solid var(--tm-line, #dce6f2);
      box-shadow: 0 8px 20px rgba(24, 36, 60, .05);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
   }

   .stat-soft:hover {
      transform: translateY(-3px);
      box-shadow: 0 16px 30px rgba(24, 36, 60, .12);
      z-index: 5;
   }

   /* Soft backgrounds */
   .soft-primary {
      background: var(--tm-brand-soft, #eef5ff);
      border-color: rgba(var(--tm-brand-rgb, 23, 105, 194), .18);
      color: var(--tm-brand-dark, #0c315f);
   }

   .soft-green {
      background: #edf7f1;
      border-color: #cfead9;
      color: #13734f;
   }

   .soft-success {
      background: #edf7f1;
      border-color: #cfead9;
   }

   .soft-warning {
      background: #fff7e6;
      border-color: #ffe2b8;
   }

   .soft-danger {
      background: #fdeeee;
      border-color: #f5c2c7;
   }

   .soft-neutral {
      background: #f4f6f8;
      border-color: #dfe3e7;
   }

   .soft-pendinglot {
      background: #fff5e8;
      border-color: #f7d8a8;
      color: #995300;
   }

   /* Space between cards */
   .stats-row .col-auto {
      margin-right: 10px;
   }

   /* Optional vertical space if wrapping happens */
   .stats-row {
      row-gap: 12px;
      display: flex;
      flex-wrap: wrap;
      align-items: stretch;
   }

   .card-body {
      border: 0;
      border-radius: 16px;
      width: 100%;
      max-width: 100%;
      background: #fff;
      box-shadow: 0 14px 34px rgba(24, 36, 60, .07);
   }

   .dashboard-page .card {
      border: 1px solid var(--tm-line, #dce6f2) !important;
      border-radius: 16px !important;
   }

   .dashboard-lot-panel {
      width: 100%;
      max-width: 100%;
      padding-left: 0;
      padding-right: 0;
      overflow: visible !important;
   }

   .dashboard-page .card {
      width: 100%;
      max-width: 100%;
      overflow: hidden;
   }

   .dashboard-page .row {
      margin-left: -10px;
      margin-right: -10px;
   }

   .dashboard-page [class*="col-"] {
      padding-left: 10px;
      padding-right: 10px;
   }

   .dashboard-page .masonry,
   .dashboard-page .masonry-item,
   .dashboard-page .masonry-item>.row {
      width: 100%;
      max-width: 100%;
   }

   .dashboard-page .layers {
      position: relative;
      min-height: 140px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      overflow: hidden;
      padding: 18px !important;
      border: 0 !important;
      border-radius: 16px !important;
      color: #fff !important;
      box-shadow: 0 14px 30px rgba(24, 36, 60, .16) !important;
      transition: transform .2s ease, box-shadow .2s ease;
   }

   /* glossy top sheen */
   .dashboard-page .layers:before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(255, 255, 255, .22), rgba(255, 255, 255, 0) 42%);
      pointer-events: none;
   }

   /* decorative orb */
   .dashboard-page .layers:after {
      content: "";
      position: absolute;
      right: -34px;
      bottom: -46px;
      width: 124px;
      height: 124px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .14);
      pointer-events: none;
      transition: transform .25s ease;
   }

   .dashboard-page .layers:hover {
      transform: translateY(-5px);
      box-shadow: 0 24px 44px rgba(24, 36, 60, .26) !important;
   }

   .dashboard-page .layers:hover:after {
      transform: scale(1.18);
   }

   .dashboard-page .layers h5,
   .dashboard-page .layers h6 {
      position: relative;
      z-index: 1;
      max-width: 100%;
      margin: 0;
      color: #fff !important;
      font-size: 12px;
      font-weight: 800 !important;
      letter-spacing: .04em !important;
      text-shadow: 0 1px 6px rgba(0, 0, 0, .25);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      text-transform: uppercase;
   }

   .dashboard-page .layers span {
      position: relative;
      z-index: 1;
      max-width: 100%;
      color: #fff !important;
      background: rgba(255, 255, 255, .22) !important;
      border: 1px solid rgba(255, 255, 255, .3);
      border-radius: 12px !important;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, .35);
      backdrop-filter: blur(4px);
      overflow-wrap: anywhere;
      font-size: 21px !important;
      font-weight: 800;
      line-height: 1.2;
      padding: 9px 15px !important;
   }

   .dashboard-section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin: 26px 0 14px;
      padding: 18px 22px;
      border: 1px solid var(--tm-line, #dce6f2);
      border-radius: 16px;
      background:
         linear-gradient(135deg, rgba(255,255,255,.98), rgba(248,251,255,.94)),
         radial-gradient(circle at 96% 8%, color-mix(in srgb, var(--tm-brand, #1769c2) 14%, transparent), transparent 28%);
      box-shadow: 0 14px 32px rgba(24, 36, 60, .08);
   }

   .dashboard-section-head h5 {
      margin: 0;
      padding-left: 14px;
      font-size: 20px;
   }

   .dashboard-section-head p {
      margin: 5px 0 0;
      color: #718096;
      font-size: 13px;
      font-weight: 700;
      line-height: 1.45;
   }

   .dashboard-section-pill {
      flex: 0 0 auto;
      padding: 9px 13px;
      border-radius: 8px;
      background: #edf7f1;
      color: #13734f;
      border: 1px solid #cfead9;
      font-size: 12px;
      font-weight: 900;
      white-space: nowrap;
   }

   .paddy-card-grid {
      margin-bottom: 4px;
   }

   .paddy-dashboard-card {
      position: relative;
      display: block;
      min-height: 154px;
      padding: 18px;
      overflow: hidden;
      border: 1px solid var(--tm-line, #dce6f2);
      border-radius: 16px;
      text-decoration: none !important;
      color: #18243c;
      background:
         linear-gradient(180deg, rgba(255,255,255,.98), rgba(255,255,255,.92)),
         radial-gradient(circle at 88% 12%, color-mix(in srgb, var(--paddy-accent, #1f9d70) 16%, transparent), transparent 34%);
      box-shadow: 0 14px 32px rgba(24, 36, 60, .08);
      transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
   }

   .paddy-dashboard-card:hover,
   .paddy-dashboard-card:focus {
      transform: translateY(-5px);
      border-color: color-mix(in srgb, var(--paddy-accent, #1f9d70) 45%, #fff);
      box-shadow: 0 24px 44px rgba(24, 36, 60, .14);
      color: #18243c;
   }

   .paddy-dashboard-card:before {
      content: "";
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 6px;
      background: var(--paddy-accent, #1f9d70);
   }

   .paddy-dashboard-card:after {
      content: "";
      position: absolute;
      right: -38px;
      bottom: -46px;
      width: 124px;
      height: 124px;
      border-radius: 50%;
      background: color-mix(in srgb, var(--paddy-accent, #1f9d70) 9%, transparent);
      transition: transform .2s ease;
   }

   .paddy-dashboard-card:hover:after {
      transform: scale(1.12);
   }

   .paddy-card-label {
      position: relative;
      z-index: 1;
      display: inline-block;
      margin-bottom: 14px;
      padding: 5px 9px;
      border-radius: 8px;
      background: #edf7f1;
      color: #13734f;
      font-size: 11px;
      font-weight: 900;
      text-transform: uppercase;
   }

   .paddy-card-title {
      position: relative;
      z-index: 1;
      min-height: 38px;
      margin: 0 0 14px;
      color: #18243c;
      font-size: 15px;
      font-weight: 900;
      line-height: 1.25;
      overflow: hidden;
      text-overflow: ellipsis;
   }

   .paddy-card-value {
      position: relative;
      z-index: 1;
      display: inline-block;
      padding: 8px 13px;
      border-radius: 8px;
      background: #fff7e6;
      border: 1px solid #ffe2b8;
      color: #995300;
      font-size: 24px;
      font-weight: 900;
      line-height: 1.1;
   }

   .paddy-card-meta {
      position: relative;
      z-index: 1;
      display: block;
      margin-top: 10px;
      color: #718096;
      font-size: 12px;
      font-weight: 800;
   }

   /* ===================== SALES & PURCHASE ANALYTICS ===================== */
   .sp-analytics { margin: 8px 0 4px; }
   .sp-head { margin: 22px 0 14px; }
   .sp-head h5 { position: relative; margin: 0; padding-left: 14px; font-size: 20px; font-weight: 800; color: var(--tm-ink, #18243c); }
   .sp-head h5:before { content: ""; position: absolute; left: 0; top: 3px; width: 5px; height: 18px; border-radius: 999px; background: linear-gradient(180deg, var(--tm-brand, #1769c2), var(--tm-accent, #f0a020)); }
   .sp-head p { margin: 6px 0 0 14px; color: var(--tm-muted, #718096); font-size: 13px; font-weight: 600; }

   .sp-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-bottom: 14px; }
   .sp-kpi { display: flex; align-items: center; gap: 14px; padding: 18px 20px; border-radius: 16px; color: #fff; box-shadow: 0 16px 34px rgba(24, 36, 60, .16); }
   .sp-kpi-ico { width: 46px; height: 46px; flex: 0 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 13px; background: rgba(255, 255, 255, .2); font-size: 20px; font-weight: 900; }
   .sp-kpi small { display: block; font-size: 11px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; opacity: .92; }
   .sp-kpi b { display: block; margin-top: 3px; font-size: 21px; font-weight: 900; line-height: 1.1; }
   .sp-kpi-sale { background: linear-gradient(135deg, var(--tm-brand, #1769c2), var(--tm-sidebar-end, #0c315f)); }
   .sp-kpi-purchase { background: linear-gradient(135deg, #f0a020, #c9791a); }
   .sp-kpi-opening { background: linear-gradient(135deg, #0f766e, #115e59); }
   .sp-kpi-profit { background: linear-gradient(135deg, #1f9d70, #0f6b4a); }
   .sp-kpi-loss { background: linear-gradient(135deg, #e5484d, #a32635); }
   .sp-kpi-ratio { background: linear-gradient(135deg, #6c5ce7, #3f2db0); }
   .sp-kpi-ratio.is-neg { background: linear-gradient(135deg, #e5484d, #a32635); }
   .sp-kpis-4 { grid-template-columns: repeat(4, 1fr); }
   .sp-kpis-5 { grid-template-columns: repeat(5, 1fr); }

   .sp-charts { display: grid; grid-template-columns: 2fr 1fr; gap: 14px; margin-bottom: 14px; }
   .sp-card { min-width: 0; padding: 16px 18px; border: 1px solid var(--tm-line, #dce6f2); border-radius: 16px; background: #fff; box-shadow: 0 14px 32px rgba(24, 36, 60, .06); }
   .sp-card-head { align-items: center; display: flex; gap: 12px; justify-content: space-between; margin-bottom: 12px; }
   .sp-card-head h6 { margin: 0; font-size: 14px; font-weight: 800; color: var(--tm-ink, #18243c); }
   .sp-chart-filter { min-width: 210px; position: relative; }
   .sp-chart-filter:after { color: var(--tm-brand, #1769c2); content: "\f107"; font-family: FontAwesome; pointer-events: none; position: absolute; right: 12px; top: 10px; }
   .sp-chart-filter select { -webkit-appearance: none; appearance: none; background: #f8fbff; border: 1px solid var(--tm-line, #dce6f2); border-radius: 8px; color: var(--tm-ink, #18243c); cursor: pointer; font-size: 12px; font-weight: 800; height: 36px; outline: 0; padding: 7px 34px 7px 11px; width: 100%; }
   .sp-chart-filter select:hover, .sp-chart-filter select:focus { border-color: var(--tm-brand, #1769c2); box-shadow: 0 0 0 3px rgba(23, 105, 194, .1); }
   .sp-date-filter { align-items: flex-end; display: flex; flex-wrap: wrap; gap: 8px; }
   .sp-date-field { min-width: 150px; }
   .sp-date-field label { display: block; margin-bottom: 5px; font-size: 11px; font-weight: 900; letter-spacing: .03em; color: #718096; text-transform: uppercase; }
   .sp-date-field input { background: #f8fbff; border: 1px solid var(--tm-line, #dce6f2); border-radius: 8px; color: var(--tm-ink, #18243c); font-size: 12px; font-weight: 800; height: 36px; outline: 0; padding: 7px 10px; width: 100%; }
   .sp-date-field input:hover, .sp-date-field input:focus { border-color: var(--tm-brand, #1769c2); box-shadow: 0 0 0 3px rgba(23, 105, 194, .1); }
   .sp-date-actions { display: flex; gap: 8px; }
   .sp-date-actions button, .sp-date-actions a { align-items: center; border: 0; border-radius: 8px; display: inline-flex; font-size: 12px; font-weight: 900; height: 36px; padding: 0 13px; text-decoration: none; }
   .sp-date-actions button { background: var(--tm-brand, #1769c2); color: #fff; }
   .sp-date-actions a { background: #eef3fa; color: var(--tm-ink, #18243c); }
   .sp-canvas-wrap { position: relative; height: 300px; }

   .sp-table-wrap { max-height: 300px; overflow: auto; }
   .sp-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
   .sp-table th, .sp-table td { padding: 9px 8px; text-align: right; border-bottom: 1px solid var(--tm-line, #eef2f8); white-space: nowrap; }
   .sp-table th:first-child, .sp-table td:first-child { text-align: left; }
   .sp-table th { position: sticky; top: 0; background: #fff; color: var(--tm-muted, #718096); font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em; font-weight: 800; }
   .sp-table td { font-weight: 800; color: var(--tm-ink, #24324f); }
   .sp-table tbody tr:hover { background: var(--tm-brand-soft, #f1f6ff); }
   .sp-pos { color: #1f9d70; }
   .sp-neg { color: #e5484d; }

   /* ===================== AGEING (DEBTORS / CREDITORS) ===================== */
   .ageing { margin: 8px 0 4px; }
   .age-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
   .age-card { overflow: hidden; border: 1px solid var(--tm-line, #dce6f2); border-radius: 16px; background: #fff; box-shadow: 0 14px 32px rgba(24, 36, 60, .06); }
   .age-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; color: #fff; }
   .age-deb .age-card-head { background: linear-gradient(135deg, #1f9d70, #0f6b4a); }
   .age-cred .age-card-head { background: linear-gradient(135deg, #e5484d, #a32635); }
   .age-card-head h6 { margin: 0; font-size: 15px; font-weight: 800; }
   .age-card-head small { display: block; margin-top: 3px; font-size: 11px; font-weight: 700; opacity: .9; }
   .age-total { text-align: right; font-size: 22px; font-weight: 900; line-height: 1.1; }
   .age-total small { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; opacity: .9; }
   .age-body { padding: 14px 16px; }
   .age-bar { display: flex; height: 14px; margin-bottom: 12px; border-radius: 8px; overflow: hidden; background: #eef1f6; }
   .age-bar span { display: block; height: 100%; }
   .age-seg0 { background: #1f9d70; }
   .age-seg1 { background: #e0a92a; }
   .age-seg2 { background: #ef7c1a; }
   .age-seg3 { background: #e5484d; }
   .age-chips { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px; }
   .age-chip { padding: 8px 9px; border-radius: 10px; background: #f6f8fc; border: 1px solid var(--tm-line, #eef2f8); }
   .age-chip small { display: block; margin-bottom: 3px; color: var(--tm-muted, #718096); font-size: 10px; font-weight: 800; text-transform: uppercase; }
   .age-chip small i { display: inline-block; width: 8px; height: 8px; margin-right: 5px; border-radius: 50%; }
   .age-chip b { font-size: 13px; font-weight: 900; color: var(--tm-ink, #24324f); }
   .age-table-wrap { max-height: 280px; overflow: auto; }
   .age-table { width: 100%; border-collapse: collapse; font-size: 12px; }
   .age-table th, .age-table td { padding: 8px 7px; text-align: right; border-bottom: 1px solid var(--tm-line, #eef2f8); white-space: nowrap; }
   .age-table th:first-child, .age-table td:first-child { text-align: left; }
   .age-table th { position: sticky; top: 0; background: #fff; color: var(--tm-muted, #718096); font-size: 10px; text-transform: uppercase; letter-spacing: .03em; font-weight: 800; }
   .age-table td { font-weight: 800; color: var(--tm-ink, #24324f); }
   .age-table tbody tr:hover { background: var(--tm-brand-soft, #f1f6ff); }
   .age-table td.age-old { color: #e5484d; }
   .age-empty { padding: 22px; text-align: center; color: #9aa3c4; font-weight: 700; }
   @media (max-width: 991px) { .age-grid { grid-template-columns: 1fr; } }

   @media (max-width: 991px) {
      .sp-kpis { grid-template-columns: 1fr; }
      .sp-charts { grid-template-columns: 1fr; }
      .sp-canvas-wrap { height: 260px; }
   }
   @media (max-width: 560px) {
      .sp-card-head { align-items: stretch; flex-direction: column; }
      .sp-chart-filter { min-width: 0; width: 100%; }
      .sp-date-filter { width: 100%; }
      .sp-date-field, .sp-date-actions { width: 100%; }
      .sp-date-actions button, .sp-date-actions a { justify-content: center; flex: 1; }
   }

   @media (max-width: 767px) {
      .dashboard-page {
         padding-bottom: 70px;
      }

      .dashboard-page h5 {
         font-size: 17px;
         line-height: 1.35;
      }

      .dashboard-lot-panel {
         margin-top: 10px;
      }

      .stats-row {
         display: grid;
         grid-template-columns: 1fr;
         gap: 10px;
         margin-left: 0;
         margin-right: 0;
      }

      .stats-row .col-auto,
      .stats-row [class*="col-"] {
         width: 100%;
         max-width: 100%;
         margin-right: 0;
         padding-left: 0;
         padding-right: 0;
      }

      .stat-soft,
      .stats-row .form-control {
         width: 100%;
         min-height: 44px;
         display: flex;
         align-items: center;
         justify-content: space-between;
         text-align: left;
         white-space: normal;
      }

      .card-body {
         padding: 12px !important;
      }

      .dashboard-page .masonry,
      .dashboard-page .masonry-item {
         position: static !important;
         height: auto !important;
      }

      .dashboard-page .masonry-item>.row,
      .dashboard-page .row.gap-20 {
         display: grid;
         grid-template-columns: 1fr;
         gap: 12px;
         margin-left: 0;
         margin-right: 0;
      }

      .dashboard-page .row.gap-20>[class*="col-"] {
         width: 100%;
         max-width: 100%;
         padding-left: 0;
         padding-right: 0;
      }

      .dashboard-page .layers {
         min-height: 116px;
         padding: 14px !important;
         border-radius: 8px !important;
      }

      .dashboard-page .layers span {
         font-size: 19px !important;
         padding: 7px 12px !important;
      }

      .dashboard-section-head {
         display: block;
         padding: 15px;
      }

      .dashboard-section-pill {
         display: inline-block;
         margin-top: 12px;
      }

      .paddy-dashboard-card {
         min-height: 138px;
      }
   }

   @media (min-width: 768px) and (max-width: 1199px) {
      .dashboard-page .row.gap-20>.col-md-3 {
         width: 50%;
         max-width: 50%;
         flex: 0 0 50%;
      }
   }
</style>

<main class="main-content bgc-grey-100">
   <div id="mainContent" class="dashboard-page">

      <?php if (!empty($apk_stats)): $A = $apk_stats; $AL = $A['latest']; ?>
      <div class="container mt-3" data-sec="apk_widgets" data-sec-label="App Updates">
         <h5>App Updates (APK)</h5>
         <div style="display:grid;gap:12px;grid-template-columns:repeat(6,1fr)">
            <?php
            $apk_cards = array(
               array('Current Version', $AL ? 'v' . esc($AL->version_name) : '—', $AL ? 'Code ' . (int) $AL->version_code : 'No build', '#1769c2'),
               array('Total Downloads', (int) $A['total_downloads'], 'All versions', '#1f9d55'),
               array('Total Versions', (int) $A['total_versions'], 'Builds', '#7b4bd0'),
               array('Latest Upload', $AL && $AL->created_at ? date('d M Y', strtotime($AL->created_at)) : '—', $AL && $AL->created_at ? date('h:i A', strtotime($AL->created_at)) : '', '#c67c11'),
               array('Force Update', $A['force_update'] ? 'ON' : 'OFF', 'Current build', $A['force_update'] ? '#1f9d55' : '#c53030'),
               array('Website Visible', $A['website_visible'] ? 'ON' : 'OFF', 'Homepage', $A['website_visible'] ? '#1f9d55' : '#c53030'),
            );
            foreach ($apk_cards as $c): ?>
               <a href="<?= base_url('admin/app_update/listing') ?>" style="background:#fff;border:1px solid #e2e8f0;border-left:4px solid <?= $c[3] ?>;border-radius:10px;box-shadow:0 6px 18px rgba(24,36,60,.05);display:block;padding:14px;text-decoration:none">
                  <div style="color:#8394a7;font-size:10px;font-weight:800;letter-spacing:.04em;text-transform:uppercase"><?= $c[0] ?></div>
                  <div style="color:#18243c;font-size:19px;font-weight:900;margin-top:5px"><?= $c[1] ?></div>
                  <div style="color:#7a8aa0;font-size:11px;font-weight:700;margin-top:2px"><?= $c[2] ?></div>
               </a>
            <?php endforeach; ?>
         </div>
      </div>
      <?php endif; ?>

      <!-- Dashboard layout toolbar (drag-reorder / show-hide sections; per-user, App Settings). -->
      <div class="dash-layout-toolbar" id="dashLayoutToolbar">
         <button type="button" class="dlt-btn" id="dashArrangeToggle"><i class="ti-layout-grid2"></i> <span>Arrange layout</span></button>
         <span class="dlt-hint" id="dashArrangeHint" style="display:none;">Drag the <i class="ti-move"></i> handles to reorder. Changes save automatically.</span>
         <span class="dlt-spacer"></span>
         <a class="dlt-link" href="<?php echo base_url('admin/app_setting'); ?>"><i class="ti-settings"></i> App Settings</a>
      </div>
      <style>
         .dash-layout-toolbar { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin:0 0 12px; }
         .dash-layout-toolbar .dlt-spacer { flex:1 1 auto; }
         .dlt-btn { border:1px solid #dce6f2; background:#fff; color:#0c315f; font-weight:800; padding:8px 14px; border-radius:8px; cursor:pointer; font-size:13px; }
         .dlt-btn.active { background:linear-gradient(135deg,#1769c2,#0c4a94); color:#fff; border-color:transparent; }
         .dlt-link { color:#516174; font-weight:700; font-size:13px; text-decoration:none; border-bottom:1px dashed #9cc0ff; }
         .dlt-hint { color:#718096; font-size:12px; font-weight:700; }
         /* Widgetized sections */
         .dash-widget { margin-bottom:16px; }
         .dash-widget-head { display:none; align-items:center; gap:10px; padding:7px 12px; margin-bottom:8px; background:#eef4fb; border:1px solid #dce6f2; border-radius:8px; }
         .dash-page-arranging .dash-widget-head { display:flex; }
         .dash-page-arranging .dash-widget { outline:2px dashed #9cc0ff; outline-offset:6px; border-radius:12px; }
         .dash-widget-grip { cursor:grab; display:inline-flex; align-items:center; gap:6px; background:#0c315f; color:#fff; padding:4px 12px; border-radius:6px; font-size:12px; font-weight:800; }
         .dash-widget-grip:active { cursor:grabbing; }
         .dash-widget-title { font-weight:800; color:#0c315f; font-size:13px; }
         .dash-widget.dash-widget-dragging { opacity:.55; }
         .dash-widget.dash-widget-over { outline-color:#1769c2 !important; }
         /* Widget host: full-width sections stack; compact tiles flow in a grid. */
         #dashLayoutHost { display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start; }
         #dashLayoutHost > .dash-widget { flex:1 1 100%; min-width:0; margin-bottom:0; }
         #dashLayoutHost > .dash-widget.dash-widget-tile { flex:1 1 230px; max-width:320px; }
         .dash-metric-card { display:flex; align-items:center; gap:14px; padding:16px 18px; border:1px solid #dce6f2; border-radius:12px; background:#fff; box-shadow:0 10px 26px rgba(24,36,60,.06); text-decoration:none; transition:transform .12s ease, box-shadow .12s ease; }
         .dash-metric-card:hover { transform:translateY(-2px); box-shadow:0 16px 34px rgba(24,36,60,.12); }
         .dash-metric-ico { width:46px; height:46px; flex:0 0 auto; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; background:linear-gradient(135deg,#1769c2,#0c4a94); color:#fff; font-size:20px; }
         .dash-metric-meta { display:flex; flex-direction:column; line-height:1.15; }
         .dash-metric-num { font-size:26px; font-weight:900; color:#0c315f; font-variant-numeric:tabular-nums; }
         .dash-metric-label { font-size:12px; font-weight:800; color:#718096; }
      </style>

      <?php
      $dashUserName = '';
      if (!empty($_SESSION['userinfo']->name)) {
         $dashUserName = $_SESSION['userinfo']->name;
      } elseif (function_exists('currentuserinfo') && !empty(@currentuserinfo()->name)) {
         $dashUserName = @currentuserinfo()->name;
      }
      $dashFirm = !empty($_SESSION['fy']->firm_name) ? $_SESSION['fy']->firm_name : '';
      ?>
      <section class="dash-hero" data-sec="hero" data-sec-label="Welcome &amp; Clock">
         <div class="dash-hero-left">
            <span class="dash-hero-eyebrow"><i class="ti-bar-chart-alt"></i> Operations Dashboard</span>
            <h3 id="dashGreeting">Welcome back<?php echo $dashUserName ? ', ' . htmlspecialchars($dashUserName, ENT_QUOTES, 'UTF-8') : ''; ?>!</h3>
            <p>Here is your live business overview<?php echo $dashFirm ? ' for <b>' . htmlspecialchars($dashFirm, ENT_QUOTES, 'UTF-8') . '</b>' : ''; ?> — lots, stock and sales at a glance.</p>
         </div>
         <div class="dash-hero-clock">
            <div class="dash-hero-time" id="dashHeroTime">--:--</div>
            <div class="dash-hero-date" id="dashHeroDate">--</div>
         </div>
      </section>

      <!-- HORIZONTAL WEATHER BAR -->
      <section class="dash-weather-bar" id="dashWeather" data-sec="weather" data-sec-label="Weather Bar">
         <div class="dwb-now">
            <span class="dhw-emoji" id="dhwEmoji">🛰️</span>
            <div class="dwb-now-main">
               <div class="dhw-temp" id="dhwTemp">--&deg;</div>
               <div class="dhw-cond" id="dhwCond">Fetching weather…</div>
               <div class="dwb-now-meta">
                  <span id="dhwCity">📍 Locating…</span>
                  <span id="dhwHL"></span>
                  <span id="dhwRain"></span>
               </div>
            </div>
         </div>

         <div class="dwb-divider"></div>

         <div class="dhw-forecast" id="dhwForecast"></div>

         <div class="dwb-tools">
            <button type="button" class="dhw-edit" id="dhwEdit">✎ Change location</button>
            <label class="dhw-auto" title="Automatically match the app theme to the current weather">
               <input type="checkbox" id="dhwAuto" checked>
               <span>Match theme to weather</span>
            </label>
            <div class="dhw-editor" id="dhwEditor" style="display:none">
               <input type="text" id="dhwInput" placeholder="Type a city, e.g. Mumbai" autocomplete="off">
               <div class="dhw-actions">
                  <button type="button" id="dhwSearch">Search</button>
                  <button type="button" id="dhwAutoLoc">Use my location</button>
               </div>
               <input type="text" id="dhwPin" placeholder="Or enter PIN code, e.g. 110001" inputmode="numeric" maxlength="6" autocomplete="off" style="margin-top:7px;">
               <div class="dhw-actions">
                  <button type="button" id="dhwPinBtn">Get weather by PIN</button>
               </div>
               <div class="dhw-results" id="dhwResults"></div>
               <div class="dhw-hint" id="dhwHint"></div>
            </div>
         </div>
      </section>

      <!-- Module data tiles (live counts). Each is a show/hide + reorderable widget. -->
      <?php if (!empty($module_tiles) && is_array($module_tiles)): ?>
         <?php foreach ($module_tiles as $t): ?>
            <div class="dash-metric" data-sec="<?php echo esc($t['key']); ?>" data-sec-label="<?php echo esc($t['label']); ?>" data-sec-tile="1">
               <a class="dash-metric-card" href="<?php echo $t['url']; ?>">
                  <span class="dash-metric-ico"><i class="<?php echo esc($t['icon']); ?>"></i></span>
                  <span class="dash-metric-meta">
                     <span class="dash-metric-num"><?php echo (int) $t['count']; ?></span>
                     <span class="dash-metric-label"><?php echo esc($t['label']); ?></span>
                  </span>
               </a>
            </div>
         <?php endforeach; ?>
      <?php endif; ?>


<?php if (!empty($user_login_analytics)) {
      $ula = $user_login_analytics;
      $ula_sum = $ula['summary'];
   ?>
   <style>
      .ula-table-wrap { overflow-x: auto; margin-top: 6px; }
      .ula-table { width: 100%; min-width: 720px; border-collapse: collapse; }
      .ula-table th, .ula-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid rgba(120,135,160,.16); font-size: 13px; }
      .ula-table th { color: #6b7890; font-size: 11px; font-weight: 800; text-transform: uppercase; background: rgba(120,135,160,.06); }
      .ula-table td b { font-weight: 800; }
      .ula-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; }
      .ula-badge.on { color: #15803d; background: #dcfce7; }
      .ula-badge.off { color: #b45309; background: #fef3c7; }
      .ula-badge:before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
      .ula-count { font-variant-numeric: tabular-nums; font-weight: 800; }
   </style>
   <section class="sp-analytics" data-sec="user_login" data-sec-label="User Login Analytics">
      <div class="sp-head">
         <h5>User Login Analytics</h5>
         <p>Who is active, their last login and how many times each user has accessed the system.</p>
      </div>

      <div class="sp-kpis">
         <div class="sp-kpi sp-kpi-sale"><span class="sp-kpi-ico">&#128101;</span><div><small>Total Users</small><b><?php echo (int) $ula_sum['total']; ?></b></div></div>
         <div class="sp-kpi sp-kpi-profit"><span class="sp-kpi-ico">&#10004;</span><div><small>Active</small><b><?php echo (int) $ula_sum['active']; ?></b></div></div>
         <div class="sp-kpi sp-kpi-loss"><span class="sp-kpi-ico">&#10006;</span><div><small>Inactive</small><b><?php echo (int) $ula_sum['inactive']; ?></b></div></div>
         <div class="sp-kpi sp-kpi-purchase"><span class="sp-kpi-ico">&#128336;</span><div><small>Logged In Today</small><b><?php echo (int) $ula_sum['active_today']; ?></b></div></div>
         <div class="sp-kpi sp-kpi-sale"><span class="sp-kpi-ico">&#128274;</span><div><small>Total Logins</small><b><?php echo (int) $ula_sum['total_logins']; ?></b></div></div>
      </div>

      <div class="sp-charts">
         <div class="sp-card">
            <div class="sp-card-head"><h6>Active vs Inactive Users</h6></div>
            <div class="sp-canvas-wrap"><canvas id="ulaStatusChart"></canvas></div>
         </div>
         <div class="sp-card">
            <div class="sp-card-head"><h6>Logins &mdash; Last 14 Days</h6></div>
            <div class="sp-canvas-wrap"><canvas id="ulaTrendChart"></canvas></div>
         </div>
      </div>

      <div class="sp-charts">
         <div class="sp-card">
            <div class="sp-card-head"><h6>Top Users by Access Count</h6></div>
            <div class="sp-canvas-wrap"><canvas id="ulaTopChart"></canvas></div>
         </div>
         <div class="sp-card">
            <div class="sp-card-head"><h6>Recent User Activity</h6></div>
            <div class="ula-table-wrap">
               <table class="ula-table">
                  <thead>
                     <tr><th>User</th><th>Role</th><th>Status</th><th>Last Login</th><th>Access Count</th></tr>
                  </thead>
                  <tbody>
                     <?php foreach ($ula['users'] as $usr):
                        $uname = trim($usr->first_name . ' ' . $usr->last_name);
                        if ($uname === '') { $uname = $usr->email; }
                        $ll = (!empty($usr->last_login) && $usr->last_login !== '0000-00-00 00:00:00') ? date('d M Y, h:i A', strtotime($usr->last_login)) : 'Never';
                     ?>
                        <tr>
                           <td><b><?php echo htmlspecialchars($uname, ENT_QUOTES, 'UTF-8'); ?></b></td>
                           <td>Type <?php echo (int) $usr->user_type; ?></td>
                           <td><span class="ula-badge <?php echo $usr->status === 'Active' ? 'on' : 'off'; ?>"><?php echo htmlspecialchars($usr->status, ENT_QUOTES, 'UTF-8'); ?></span></td>
                           <td><?php echo $ll; ?></td>
                           <td class="ula-count"><?php echo (int) $usr->access_count; ?></td>
                        </tr>
                     <?php endforeach; ?>
                  </tbody>
               </table>
            </div>
         </div>
      </div>
   </section>

   <script>
      window.__ulaAmd = (typeof window.define === 'function' && window.define.amd) ? window.define : null;
      if (window.__ulaAmd) { window.define = undefined; }
   </script>
   <script src="<?= base_url('assets/global/plugins/chartjs/chart.umd.min.js') ?>"></script>
   <script>
      (function () {
         var ChartLib = (window.Chart && window.Chart.defaults && window.Chart.defaults.font) ? window.Chart : null;
         if (window.__ulaAmd) { window.define = window.__ulaAmd; }
         var DATA = <?= json_encode($user_login_analytics) ?>;

         function start() {
            if (!ChartLib || !DATA) { return; }
            var Chart = ChartLib;
            var css = getComputedStyle(document.body);
            function v(n, f) { var x = css.getPropertyValue(n); return (x && x.trim()) ? x.trim() : f; }
            var brand = v('--tm-brand', '#1769c2');
            var accent = v('--tm-accent', '#f0a020');
            var grid = 'rgba(120,135,160,.14)';

            // Active vs Inactive doughnut
            try {
               var sEl = document.getElementById('ulaStatusChart');
               if (sEl) {
                  new Chart(sEl, {
                     type: 'doughnut',
                     data: {
                        labels: ['Active', 'Inactive'],
                        datasets: [{ data: [DATA.summary.active, DATA.summary.inactive], backgroundColor: ['#1f9d70', accent], borderWidth: 0 }]
                     },
                     options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
                        plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { weight: '700' } } } } }
                  });
               }
            } catch (e) {}

            // Logins per day line
            try {
               var tEl = document.getElementById('ulaTrendChart');
               if (tEl) {
                  new Chart(tEl, {
                     type: 'line',
                     data: {
                        labels: DATA.logins_by_day.map(function (d) { return d.date.slice(5); }),
                        datasets: [{ label: 'Logins', data: DATA.logins_by_day.map(function (d) { return d.count; }),
                           borderColor: brand, backgroundColor: 'rgba(23,105,194,.12)', fill: true, tension: .35, pointRadius: 3 }]
                     },
                     options: { responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: grid }, ticks: { precision: 0 } } } }
                  });
               }
            } catch (e) {}

            // Top users by access (horizontal bar)
            try {
               var pEl = document.getElementById('ulaTopChart');
               if (pEl && DATA.top_users && DATA.top_users.length) {
                  new Chart(pEl, {
                     type: 'bar',
                     data: {
                        labels: DATA.top_users.map(function (u) { return (((u.first_name || '') + ' ' + (u.last_name || '')).trim()) || ('User ' + u.user_id); }),
                        datasets: [{ label: 'Logins', data: DATA.top_users.map(function (u) { return +u.c || 0; }), backgroundColor: brand, borderRadius: 6, maxBarThickness: 26 }]
                     },
                     options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true, grid: { color: grid }, ticks: { precision: 0 } }, y: { grid: { display: false } } } }
                  });
               }
            } catch (e) {}
         }

         if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', function () { setTimeout(start, 60); }); }
         else { setTimeout(start, 60); }
      })();
   </script>
<?php } ?>

<?php if (!empty($sp_analytics)) {
         $sp_profit = $sp_analytics['totals']['profit'];
         $sp_is_profit = $sp_profit >= 0;
         // Profit ratio (net margin) = profit / sales * 100
         $sp_sale_total = $sp_analytics['totals']['sale'];
         $sp_ratio = ($sp_sale_total > 0) ? ($sp_profit / $sp_sale_total * 100) : 0;
      ?>
      <section class="sp-analytics" data-sec="sales_purchase" data-sec-label="Sales &amp; Purchase Analytics">
         <div class="sp-head">
            <h5>Sales &amp; Purchase Analytics</h5>
            <p>Profit / loss, profit ratio, monthly trend and commodity-wise purchase &amp; sale rates for the current financial year.</p>
         </div>

         <div class="sp-kpis sp-kpis-4">
            <div class="sp-kpi sp-kpi-sale">
               <span class="sp-kpi-ico">&#8599;</span>
               <div><small>Total Sales</small><b>&#8377;<?php echo number_format($sp_analytics['totals']['sale'], 2); ?></b></div>
            </div>
            <div class="sp-kpi sp-kpi-purchase">
               <span class="sp-kpi-ico">&#8600;</span>
               <div><small>Total Purchase</small><b>&#8377;<?php echo number_format($sp_analytics['totals']['purchase'], 2); ?></b></div>
            </div>
            <div class="sp-kpi <?php echo $sp_is_profit ? 'sp-kpi-profit' : 'sp-kpi-loss'; ?>">
               <span class="sp-kpi-ico"><?php echo $sp_is_profit ? '&#9650;' : '&#9660;'; ?></span>
               <div><small><?php echo $sp_is_profit ? 'Net Profit' : 'Net Loss'; ?></small><b>&#8377;<?php echo number_format(abs($sp_profit), 2); ?></b></div>
            </div>
            <div class="sp-kpi sp-kpi-ratio<?php echo $sp_ratio < 0 ? ' is-neg' : ''; ?>">
               <span class="sp-kpi-ico">&#37;</span>
               <div><small>Profit Ratio</small><b><?php echo number_format($sp_ratio, 1); ?>%</b></div>
            </div>
         </div>

         <div class="sp-charts">
            <div class="sp-card">
               <div class="sp-card-head">
                  <h6>Sales vs Purchase &mdash; Monthly Trend</h6>
                  <div class="sp-chart-filter">
                     <select id="spCommodityFilter" aria-label="Filter monthly chart by commodity">
                        <option value="">All Commodities</option>
                        <?php foreach ($sp_analytics['commodity_monthly'] as $commodity_month): ?>
                           <option value="<?php echo htmlspecialchars($commodity_month['commodity'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($commodity_month['commodity'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                     </select>
                  </div>
               </div>
               <div class="sp-canvas-wrap"><canvas id="spTrendChart"></canvas></div>
            </div>
            <div class="sp-card">
               <div class="sp-card-head"><h6>Sales Share by Commodity</h6></div>
               <div class="sp-canvas-wrap"><canvas id="spShareChart"></canvas></div>
            </div>
         </div>

         <div class="sp-charts">
            <div class="sp-card">
               <div class="sp-card-head"><h6>Commodity Rate &mdash; Purchase vs Sale</h6></div>
               <div class="sp-canvas-wrap"><canvas id="spRateChart"></canvas></div>
            </div>
            <div class="sp-card">
               <div class="sp-card-head"><h6>Profit Margin by Commodity</h6></div>
               <div class="sp-canvas-wrap"><canvas id="spMarginChart"></canvas></div>
            </div>
         </div>

         <div class="sp-charts">
            <div class="sp-card">
               <div class="sp-card-head"><h6>Sales vs Purchase &mdash; Profit Ratio</h6></div>
               <div class="sp-canvas-wrap"><canvas id="spRatioChart"></canvas></div>
            </div>
            <div class="sp-card">
               <div class="sp-card-head"><h6>Commodity Rate Sheet</h6></div>
               <div class="sp-table-wrap">
                  <table class="sp-table">
                     <thead>
                        <tr><th>Commodity</th><th>Buy &#8377;</th><th>Sell &#8377;</th><th>Margin</th></tr>
                     </thead>
                     <tbody>
                        <?php foreach (array_slice($sp_analytics['commodity'], 0, 10) as $c) {
                           $margin = $c['sale_rate'] - $c['purchase_rate'];
                           $has_both = $c['sale_rate'] && $c['purchase_rate'];
                        ?>
                           <tr>
                              <td><?php echo htmlspecialchars($c['commodity'], ENT_QUOTES, 'UTF-8'); ?></td>
                              <td><?php echo $c['purchase_rate'] ? number_format($c['purchase_rate'], 0) : '&mdash;'; ?></td>
                              <td><?php echo $c['sale_rate'] ? number_format($c['sale_rate'], 0) : '&mdash;'; ?></td>
                              <td class="<?php echo $margin >= 0 ? 'sp-pos' : 'sp-neg'; ?>"><?php echo $has_both ? number_format($margin, 0) : '&mdash;'; ?></td>
                           </tr>
                        <?php } ?>
                     </tbody>
                  </table>
               </div>
            </div>
         </div>
      </section>
      <?php } ?>

      <?php if (!empty($tradeparty_position) && (($tradeparty_position['debtor_count'] + $tradeparty_position['creditor_count']) > 0)) {
         $tp = $tradeparty_position;
      ?>
      <section class="tp-pos" data-sec="tradeparty_position" data-sec-label="Trade Party Position (Debtors/Creditors)">
         <div class="sp-head">
            <h5>Trade Party Position</h5>
            <p>Live Debtor / Creditor position across all parties, computed from the cash book. <a href="<?php echo base_url('admin/accounts_report'); ?>">Open Accounting Reports &rarr;</a></p>
         </div>
         <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
            <a href="<?php echo base_url('admin/accounts_report/debtors'); ?>" style="text-decoration:none;border:1px solid #f2c9c9;border-left:5px solid #c62828;border-radius:12px;background:#fff;padding:16px 18px;box-shadow:0 8px 20px rgba(24,36,60,.06);">
               <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.04em;color:#718096;">Total Debtors</div>
               <div style="margin-top:6px;font-size:22px;font-weight:900;color:#c62828;font-variant-numeric:tabular-nums;">&#8377; <?php echo acc_money($tp['total_debtor']); ?></div>
               <div style="margin-top:2px;font-size:11px;color:#94a3b8;font-weight:700;"><?php echo (int) $tp['debtor_count']; ?> parties owe you (Dr)</div>
            </a>
            <a href="<?php echo base_url('admin/accounts_report/creditors'); ?>" style="text-decoration:none;border:1px solid #bfe3c8;border-left:5px solid #1f7a4d;border-radius:12px;background:#fff;padding:16px 18px;box-shadow:0 8px 20px rgba(24,36,60,.06);">
               <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.04em;color:#718096;">Total Creditors</div>
               <div style="margin-top:6px;font-size:22px;font-weight:900;color:#1f7a4d;font-variant-numeric:tabular-nums;">&#8377; <?php echo acc_money($tp['total_creditor']); ?></div>
               <div style="margin-top:2px;font-size:11px;color:#94a3b8;font-weight:700;"><?php echo (int) $tp['creditor_count']; ?> parties you owe (Cr)</div>
            </a>
            <a href="<?php echo base_url('admin/accounts_report/outstanding'); ?>" style="text-decoration:none;border:1px solid #cfdcec;border-left:5px solid #1769c2;border-radius:12px;background:#fff;padding:16px 18px;box-shadow:0 8px 20px rgba(24,36,60,.06);">
               <div style="font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.04em;color:#718096;">Net Position</div>
               <div style="margin-top:6px;font-size:22px;font-weight:900;color:<?php echo $tp['net_side'] === 'Cr' ? '#1f7a4d' : ($tp['net_side'] === 'Dr' ? '#c62828' : '#7a8699'); ?>;font-variant-numeric:tabular-nums;">&#8377; <?php echo acc_money($tp['net']); ?> <?php echo $tp['net_side'] === 'Nil' ? '' : $tp['net_side']; ?></div>
               <div style="margin-top:2px;font-size:11px;color:#94a3b8;font-weight:700;"><?php echo htmlspecialchars($tp['net_status'], ENT_QUOTES, 'UTF-8'); ?> overall</div>
            </a>
         </div>
      </section>
      <?php } ?>

      <?php if (!empty($ageing)) {
         $age_cfg = array(
            array('key' => 'debtors', 'cls' => 'age-deb', 'title' => 'Receivable (Debtors)', 'sub' => 'Parties that owe you', 'empty' => 'receivables'),
            array('key' => 'creditors', 'cls' => 'age-cred', 'title' => 'Payable (Creditors)', 'sub' => 'Parties you owe', 'empty' => 'payables'),
         );
         $age_seg = array('b0', 'b1', 'b2', 'b3');
         $age_names = array('0-30d', '31-60d', '61-90d', '90+ d');
      ?>
      <section class="ageing" data-sec="ageing" data-sec-label="Ageing (Debtors &amp; Creditors)">
         <div class="sp-head">
            <h5>Ageing &mdash; Debtors &amp; Creditors</h5>
            <p>Outstanding receivables &amp; payables as of today, aged FIFO into 0&ndash;30 / 31&ndash;60 / 61&ndash;90 / 90+ day buckets.</p>
         </div>
         <div class="age-grid">
            <?php foreach ($age_cfg as $cfg) {
               $blk = $ageing[$cfg['key']];
               $bk = $blk['buckets'];
               $tot = $bk['total'];
            ?>
               <div class="age-card <?php echo $cfg['cls']; ?>">
                  <div class="age-card-head">
                     <div>
                        <h6><?php echo $cfg['title']; ?></h6>
                        <small><?php echo $cfg['sub']; ?> &middot; <?php echo (int) $blk['count']; ?> parties</small>
                     </div>
                     <div class="age-total"><small>Outstanding</small>&#8377;<?php echo number_format($tot, 0); ?></div>
                  </div>
                  <div class="age-body">
                     <?php if ($tot > 0) { ?>
                        <div class="age-bar">
                           <?php foreach ($age_seg as $si => $sk) {
                              $w = $tot > 0 ? ($bk[$sk] / $tot * 100) : 0;
                              if ($w > 0) { ?>
                                 <span class="age-seg<?php echo $si; ?>" style="width:<?php echo round($w, 2); ?>%"></span>
                           <?php } } ?>
                        </div>
                        <div class="age-chips">
                           <?php foreach ($age_seg as $si => $sk) { ?>
                              <div class="age-chip">
                                 <small><i class="age-seg<?php echo $si; ?>"></i><?php echo $age_names[$si]; ?></small>
                                 <b>&#8377;<?php echo number_format($bk[$sk], 0); ?></b>
                              </div>
                           <?php } ?>
                        </div>
                        <div class="age-table-wrap">
                           <table class="age-table">
                              <thead>
                                 <tr><th>Party</th><th>0-30</th><th>31-60</th><th>61-90</th><th>90+</th><th>Total</th></tr>
                              </thead>
                              <tbody>
                                 <?php foreach ($blk['parties'] as $p) { ?>
                                    <tr>
                                       <td><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                       <td><?php echo $p['b0'] ? number_format($p['b0'], 0) : '&mdash;'; ?></td>
                                       <td><?php echo $p['b1'] ? number_format($p['b1'], 0) : '&mdash;'; ?></td>
                                       <td><?php echo $p['b2'] ? number_format($p['b2'], 0) : '&mdash;'; ?></td>
                                       <td class="<?php echo $p['b3'] ? 'age-old' : ''; ?>"><?php echo $p['b3'] ? number_format($p['b3'], 0) : '&mdash;'; ?></td>
                                       <td>&#8377;<?php echo number_format($p['total'], 0); ?></td>
                                    </tr>
                                 <?php } ?>
                              </tbody>
                           </table>
                        </div>
                     <?php } else { ?>
                        <div class="age-empty">No outstanding <?php echo $cfg['empty']; ?>.</div>
                     <?php } ?>
                  </div>
               </div>
            <?php } ?>
         </div>
      </section>
      <?php } ?>

      <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>

      <?php if (get_logical_data()->status) { ?>
         <div class="container mt-3 dashboard-lot-panel" style="overflow: auto;" data-sec="lot_report" data-sec-label="Lot Wise Report">
            <div class="card border-0 shadow-sm">
               <div class="card-body py-2">

                  <div class="row g-2 stats-row">

                     <div class="col-12 col-sm-6 col-md-auto stat-soft soft-primary">
                        KisanVahi Date
                     </div>

                     <div class="col-12 col-sm-6 col-md-auto">
                        <input type="text" id="datepicker" name="activeKishan" class="form-control form-control-sm"
                           placeholder="Latest KisanVahi">
                     </div>

                     <div class="col-12 col-sm-6 col-md-auto stat-soft soft-primary">
                        <span id="total_quant"></span>
                     </div>

                     <div class="col-6 col-md-auto stat-soft soft-success">
                        Total Kisan Vahi<br>
                        <?php echo number_format(@$totalrealtimeCenterSum, 2); ?>
                     </div>

                     <div class="col-6 col-md-auto stat-soft soft-warning">
                        Pending Lot<br>
                        <?php echo @$status_report['pending_lot']->pending_lot; ?>
                     </div>

                     <div class="col-6 col-md-auto stat-soft soft-neutral">
                        Accepted Lot<br>
                        <?php echo @$status_report['accept_lot']->accept_lot; ?>
                     </div>

                     <div class="col-6 col-md-auto stat-soft soft-danger">
                        Lot On Hold<br>
                        <?php echo @$status_report['hold_lot']->hold_lot; ?>
                     </div>

                     <div class="col-6 col-md-auto stat-soft soft-green">
                        Expected Lot<br>
                        <?php echo number_format(@$totalrealtimeCenterSum / 433, 2); ?>
                     </div>

                     <div class="col-6 col-md-auto stat-soft soft-pendinglot">
                        Left Lot<br>
                        <?php echo number_format((@$totalrealtimeCenterSum / 433) - @$status_report['accept_lot']->accept_lot, 2); ?>
                     </div>

                  </div>

               </div>
            </div>
         </div>

         <?php
         $centerPaddyData = (!empty($RealTimeDataCount) && !empty($RealTimeDataCount['first'])) ? $RealTimeDataCount['first'] : array();
         ?>
         <?php if (!empty($centerPaddyData)) { ?>
            <hr>

            <section class="dashboard-section-head">
               <div>
                  <h5>Center Wise Paddy System</h5>
                  <p>Live paddy quantity received through Kisan Vahi, grouped by center for the selected firm and season.</p>
               </div>
               <div class="dashboard-section-pill">
                  <?php echo count($centerPaddyData); ?> Active Centers
               </div>
            </section>

            <div class="row gap-20 paddy-card-grid">
               <?php
               $paddyAccents = ['#1f9d70', '#1769c2', '#f0a020', '#9b5de5', '#e5484d', '#0aa6b7'];
               foreach ($centerPaddyData as $x => $val) {
                  $centerName = !empty($val->name) ? $val->name : 'Unassigned Center';
                  $totalQuant = isset($val->totalQuant) ? (float) $val->totalQuant : 0;
               ?>
                  <div class="col-md-3 col-sm-6 col-xs-12">
                     <a href="Javascript:void(0)" class="paddy-dashboard-card" style="--paddy-accent: <?php echo $paddyAccents[$x % count($paddyAccents)]; ?>;">
                        <span class="paddy-card-label">Paddy Center</span>
                        <h5 class="paddy-card-title"><?php echo $centerName; ?></h5>
                        <span class="paddy-card-value"><?php echo number_format($totalQuant, 2); ?></span>
                        <span class="paddy-card-meta">Total quantity recorded</span>
                     </a>
                  </div>
               <?php } ?>
            </div>
            <hr>
         <?php } ?>


        

         <?php
         // --- Sale Stock (merged: Bill of Supply / Tax E-Invoice / Tax Unregistered) ---
         // Previously three near-identical stacked cards; consolidated into one card
         // with a type toggle. Only datasets that have data get a tab.
         $stock_colors = array(
            'linear-gradient(135deg,#667eea,#764ba2)',
            'linear-gradient(135deg,#f7971e,#ffd200)',
            'linear-gradient(135deg,#43cea2,#185a9d)',
            'linear-gradient(135deg,#ff512f,#dd2476)',
            'linear-gradient(135deg,#00c6ff,#0072ff)',
            'linear-gradient(135deg,#11998e,#38ef7d)'
         );
         $stock_tabs = array();
         if (!empty($getStockDetails)) { $stock_tabs['bos'] = array('label' => 'Bill of Supply', 'items' => $getStockDetails); }
         if (!empty($getStockDetailsEinvoice)) { $stock_tabs['einvoice'] = array('label' => 'Tax E-Invoice', 'items' => $getStockDetailsEinvoice); }
         if (!empty($getStockDetailsForDashbaordunregistered)) { $stock_tabs['unreg'] = array('label' => 'Tax Unregistered', 'items' => $getStockDetailsForDashbaordunregistered); }

         if (!empty($stock_tabs)) {
            reset($stock_tabs);
            $ss_first = key($stock_tabs);
            $renderStockCards = function ($items) use ($stock_colors) {
               $i = 0;
               foreach ($items as $val) {
                  $bg = $stock_colors[$i % count($stock_colors)];
                  ?>
                  <div class="col-md-3 col-sm-6 col-xs-6 text-center">
                     <a href="Javascript:void(0)" style="text-decoration:none;">
                        <div class="layers p-20" style="background: <?php echo $bg; ?>; border-radius:18px; box-shadow:0 8px 18px rgba(0,0,0,0.12); color:#fff;">
                           <!-- Product + HSN -->
                           <div class="layer w-100 mB-15">
                              <h6 style="margin:0;font-weight:600;letter-spacing:0.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                 <?php echo strtoupper($val->product_name . '_' . $val->hsn_code); ?>
                              </h6>
                           </div>
                           <!-- Quantity -->
                           <div class="layer w-100">
                              <span style="display:inline-block;font-size:24px;font-weight:700;padding:12px 25px;background:rgba(255,255,255,0.25);border-radius:50px;">
                                 <?php echo (!empty($val->product_name)) ? ($val->total_quantity . '<br>' . $val->total_amount) : "0.00"; ?>
                              </span>
                           </div>
                        </div>
                     </a>
                  </div>
                  <?php
                  $i++;
               }
            };
         ?>
         <style>
            .sale-stock-tabs { display:inline-flex; gap:6px; flex-wrap:wrap; }
            .sale-stock-tab { border:1px solid #dce6f2; background:#fff; color:#516174; font-weight:700; padding:7px 14px; border-radius:8px; cursor:pointer; font-size:13px; }
            .sale-stock-tab.active { background:linear-gradient(135deg,#1769c2,#0c4a94); color:#fff; border-color:transparent; }
         </style>
         <div class="sale-stock-block" data-sec="sale_stock" data-sec-label="Sale Stock">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:10px;">
               <h5 style="margin:0;">Sale Stock</h5>
               <div class="sale-stock-tabs">
                  <?php foreach ($stock_tabs as $key => $tab) { ?>
                     <button type="button" class="sale-stock-tab<?php echo $key === $ss_first ? ' active' : ''; ?>" data-target="ss-<?php echo $key; ?>"><?php echo esc($tab['label']); ?></button>
                  <?php } ?>
               </div>
            </div>

            <?php foreach ($stock_tabs as $key => $tab) { ?>
               <div class="sale-stock-panel" id="ss-<?php echo $key; ?>" style="<?php echo $key === $ss_first ? '' : 'display:none;'; ?>">
                  <div class="row gap-20 masonry pos-r">
                     <div class="masonry-sizer col-md-6"></div>
                     <div class="masonry-item w-100">
                        <div class="row gap-20">
                           <?php $renderStockCards($tab['items']); ?>
                        </div>
                     </div>
                  </div>
               </div>
            <?php } ?>
         </div>
         <script>
            (function () {
               var wrap = document.querySelector('.sale-stock-block');
               if (!wrap) { return; }
               var tabs = wrap.querySelectorAll('.sale-stock-tab');
               for (var i = 0; i < tabs.length; i++) {
                  tabs[i].addEventListener('click', function () {
                     var target = this.getAttribute('data-target');
                     var ts = wrap.querySelectorAll('.sale-stock-tab');
                     for (var j = 0; j < ts.length; j++) { ts[j].classList.remove('active'); }
                     this.classList.add('active');
                     var panels = wrap.querySelectorAll('.sale-stock-panel');
                     for (var k = 0; k < panels.length; k++) {
                        panels[k].style.display = (panels[k].id === target) ? '' : 'none';
                     }
                  });
               }
            })();
         </script>
         <hr>
         <?php } ?>


         <div class="row gap-20 masonry pos-r">
            <div class="masonry-sizer col-md-6"></div>
            <div class="masonry-item w-100">
               <div class="row gap-20" id="repeat">

               </div>
            </div>
         </div>

      </div>
   <?php } ?>

</main>
<script>
/* ---- Per-user dashboard widgets: drag-reorder + show/hide (App Settings) ---- */
(function () {
   var layout  = <?php echo json_encode(isset($dashboard_layout) ? $dashboard_layout : array()); ?>;
   var saveUrl = '<?php echo base_url('admin/app_setting/save_dashboard_layout'); ?>';
   var page = document.getElementById('mainContent');
   if (!page) { return; }

   var secs = Array.prototype.slice.call(page.querySelectorAll('[data-sec]'));
   if (!secs.length) { return; }

   function esc(s) { return (s == null ? '' : String(s)).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

   // One host holds every widget so they reorder together regardless of the
   // section's original nesting depth in the page.
   var host = document.createElement('div');
   host.id = 'dashLayoutHost';
   secs[0].parentNode.insertBefore(host, secs[0]);

   // Wrap each tagged section in a uniform widget frame (header + drag grip).
   var widgets = {};
   secs.forEach(function (el) {
      var key = el.getAttribute('data-sec');
      var label = el.getAttribute('data-sec-label') || key;
      var w = document.createElement('div');
      w.className = 'dash-widget' + (el.getAttribute('data-sec-tile') === '1' ? ' dash-widget-tile' : '');
      w.setAttribute('data-sec', key);
      var head = document.createElement('div');
      head.className = 'dash-widget-head';
      head.innerHTML = '<span class="dash-widget-grip" draggable="true" title="Drag to move"><i class="ti-move"></i> Move</span>'
         + '<span class="dash-widget-title">' + esc(label) + '</span>';
      var body = document.createElement('div');
      body.className = 'dash-widget-body';
      body.appendChild(el);
      w.appendChild(head);
      w.appendChild(body);
      widgets[key] = w;
   });

   // Place widgets in saved order (then any not-yet-saved in default order).
   var placed = {};
   (layout || []).forEach(function (item) {
      var w = widgets[item.key];
      if (!w) { return; }
      host.appendChild(w);
      placed[item.key] = true;
      if (item.hidden) { w.setAttribute('data-sec-hidden', '1'); w.style.display = 'none'; }
   });
   secs.forEach(function (el) {
      var k = el.getAttribute('data-sec');
      if (!placed[k] && widgets[k]) { host.appendChild(widgets[k]); }
   });

   function hostWidgets() {
      return Array.prototype.filter.call(host.children, function (el) {
         return el.nodeType === 1 && el.classList.contains('dash-widget');
      });
   }
   function currentLayout() {
      return hostWidgets().map(function (w) {
         return { key: w.getAttribute('data-sec'), hidden: w.getAttribute('data-sec-hidden') === '1' ? 1 : 0 };
      });
   }
   function save() {
      try {
         fetch(saveUrl, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ layout: currentLayout() })
         }).catch(function () {});
      } catch (e) {}
   }

   var dragEl = null;
   function clearOver() {
      Array.prototype.forEach.call(host.querySelectorAll('.dash-widget-over'), function (x) { x.classList.remove('dash-widget-over'); });
   }
   host.addEventListener('dragstart', function (e) {
      var grip = e.target && e.target.closest ? e.target.closest('.dash-widget-grip') : null;
      if (!grip) { return; }
      dragEl = grip.closest('.dash-widget');
      if (dragEl) {
         dragEl.classList.add('dash-widget-dragging');
         e.dataTransfer.effectAllowed = 'move';
         try { e.dataTransfer.setData('text/plain', 'widget'); } catch (_) {}
      }
   });
   host.addEventListener('dragend', function () {
      if (dragEl) { dragEl.classList.remove('dash-widget-dragging'); }
      dragEl = null; clearOver();
   });
   host.addEventListener('dragover', function (e) {
      if (!dragEl) { return; }
      e.preventDefault();
      var over = e.target && e.target.closest ? e.target.closest('.dash-widget') : null;
      if (!over || over === dragEl || over.parentNode !== host) { return; }
      clearOver();
      over.classList.add('dash-widget-over');
      var rect = over.getBoundingClientRect();
      var after = (e.clientY - rect.top) > rect.height / 2;
      host.insertBefore(dragEl, after ? over.nextSibling : over);
   });
   host.addEventListener('drop', function (e) {
      if (dragEl) { e.preventDefault(); save(); }
      clearOver();
   });

   var toggle = document.getElementById('dashArrangeToggle');
   var hint = document.getElementById('dashArrangeHint');
   if (toggle) {
      toggle.addEventListener('click', function () {
         var on = page.classList.toggle('dash-page-arranging');
         toggle.classList.toggle('active', on);
         if (hint) { hint.style.display = on ? '' : 'none'; }
      });
   }
})();
</script>
<script>
   $(document).ready(function () {

      /* ----- hero greeting + live clock ----- */
      (function () {
         var days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
         var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
         function pad(n){ return n < 10 ? '0' + n : n; }
         function tick() {
            var d = new Date();
            var h = d.getHours(), ap = h >= 12 ? 'PM' : 'AM', h12 = h % 12 || 12;
            var t = document.getElementById('dashHeroTime');
            var dt = document.getElementById('dashHeroDate');
            var g = document.getElementById('dashGreeting');
            if (t) t.textContent = h12 + ':' + pad(d.getMinutes()) + ' ' + ap;
            if (dt) dt.textContent = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
            if (g && !g.dataset.done) {
               var hr = d.getHours();
               var greet = hr < 12 ? 'Good morning' : (hr < 17 ? 'Good afternoon' : 'Good evening');
               g.textContent = g.textContent.replace('Welcome back', greet);
               g.dataset.done = '1';
            }
         }
         tick(); setInterval(tick, 20000);
      })();

      /* ----- live weather + auto theme by weather condition ----- */
      (function () {
         var AUTO_KEY = 'trackme-weather-theme';
         var WMO = {
            0:['Clear sky','☀️'], 1:['Mainly clear','🌤️'], 2:['Partly cloudy','⛅'], 3:['Overcast','☁️'],
            45:['Fog','🌫️'], 48:['Rime fog','🌫️'],
            51:['Light drizzle','🌦️'], 53:['Drizzle','🌦️'], 55:['Heavy drizzle','🌧️'],
            56:['Freezing drizzle','🌧️'], 57:['Freezing drizzle','🌧️'],
            61:['Light rain','🌦️'], 63:['Rain','🌧️'], 65:['Heavy rain','🌧️'],
            66:['Freezing rain','🌧️'], 67:['Freezing rain','🌧️'],
            71:['Light snow','🌨️'], 73:['Snow','🌨️'], 75:['Heavy snow','❄️'], 77:['Snow grains','🌨️'],
            80:['Rain showers','🌦️'], 81:['Rain showers','🌧️'], 82:['Heavy showers','⛈️'],
            85:['Snow showers','🌨️'], 86:['Snow showers','❄️'],
            95:['Thunderstorm','⛈️'], 96:['Thunderstorm','⛈️'], 99:['Thunderstorm','⛈️']
         };
         /* preset key -> [primary, font] (matches the header theme presets) */
         var THEME = {
            ocean:['#1769c2','#18243c'], aqua:['#00a7c4','#0c3944'], violet:['#6956d9','#241f45'],
            sunset:['#e85d2c','#3f1e10'], royal:['#8257c9','#2c2447'], crimson:['#e23b3b','#3d1414']
         };
         var THEME_LABEL = {
            ocean:'Ocean Blue', aqua:'Aqua', violet:'Violet', sunset:'Sunset', royal:'Royal', crimson:'Crimson'
         };

         function themeForWeather(code, isDay) {
            if (code >= 95) return 'crimson';                                   // thunderstorm
            if (code >= 71 && code <= 86) return 'aqua';                        // snow
            if ((code >= 51 && code <= 67) || (code >= 80 && code <= 82)) return 'ocean'; // rain
            if (code === 45 || code === 48) return 'violet';                    // fog
            if (code === 2) return 'aqua';                                      // partly cloudy
            if (code === 3) return 'ocean';                                     // overcast
            return isDay ? 'sunset' : 'royal';                                  // clear day / night
         }
         function autoOn() {
            var v = localStorage.getItem(AUTO_KEY);
            return v === null ? true : v === '1';
         }
         function set(id, val) { var el = document.getElementById(id); if (el) el.innerHTML = val; }

         function applyWeatherTheme(key) {
            window.__weatherTheme = key;
            var note = document.querySelector('#dashWeather .dhw-auto span');
            if (note) note.textContent = autoOn() ? ('Theme: ' + (THEME_LABEL[key] || key)) : 'Match theme to weather';
            if (autoOn() && window.TrackmeTheme && THEME[key]) {
               window.TrackmeTheme.setPreset(THEME[key][0], THEME[key][1]);
            }
         }
         var DNAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
         function render(d, city) {
            var cur = d.current || {}, day = d.daily || {};
            var code = cur.weather_code, isDay = cur.is_day === 1;
            var info = WMO[code] || ['Weather', '🌍'];
            set('dhwEmoji', info[1]);
            set('dhwTemp', Math.round(cur.temperature_2m) + '&deg;C');
            set('dhwCond', info[0]);
            set('dhwCity', '📍 ' + (city || 'Your location'));

            if (day.temperature_2m_max) {
               set('dhwHL', 'H:<b>' + Math.round(day.temperature_2m_max[0]) + '&deg;</b> L:<b>' + Math.round(day.temperature_2m_min[0]) + '&deg;</b>');
            }
            var rainArr = day.precipitation_probability_max;
            set('dhwRain', (rainArr && rainArr[0] != null) ? '💧 Rain <b>' + rainArr[0] + '%</b>' : '');

            /* 7-day strip */
            var html = '';
            if (day.time) {
               for (var i = 0; i < day.time.length; i++) {
                  var dt = new Date(day.time[i] + 'T00:00:00');
                  var di = WMO[day.weather_code[i]] || ['', '🌍'];
                  var rp = (rainArr && rainArr[i] != null) ? rainArr[i] : '--';
                  html += '<div class="dhw-day">'
                     + '<span class="dhw-day-name">' + (i === 0 ? 'Today' : DNAMES[dt.getDay()]) + '</span>'
                     + '<span class="dhw-day-emoji">' + di[1] + '</span>'
                     + '<span class="dhw-day-temp">' + Math.round(day.temperature_2m_max[i]) + '&deg;<small>/' + Math.round(day.temperature_2m_min[i]) + '&deg;</small></span>'
                     + '<span class="dhw-day-rain">💧' + rp + '%</span>'
                     + '</div>';
               }
            }
            var fc = document.getElementById('dhwForecast');
            if (fc) fc.innerHTML = html;

            applyWeatherTheme(themeForWeather(code, isDay));
         }
         function getWeather(lat, lon, city) {
            fetch('https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lon + '&current=temperature_2m,weather_code,is_day&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=auto&forecast_days=7')
               .then(function (r) { return r.json(); })
               .then(function (d) { render(d, city); })
               .catch(function () { set('dhwCond', 'Weather unavailable'); set('dhwEmoji', '🌐'); });
         }

         var cb = document.getElementById('dhwAuto');
         if (cb) {
            cb.checked = autoOn();
            cb.addEventListener('change', function () {
               localStorage.setItem(AUTO_KEY, cb.checked ? '1' : '0');
               if (cb.checked) {
                  applyWeatherTheme(window.__weatherTheme || 'ocean');
               } else if (window.TrackmeTheme) {
                  var note = document.querySelector('#dashWeather .dhw-auto span');
                  if (note) note.textContent = 'Match theme to weather';
                  window.TrackmeTheme.reset();
               }
            });
         }

         /* ----- saved location (remembered forever) ----- */
         var LOC_KEY = 'trackme-weather-location';
         function savedLocation() {
            try { return JSON.parse(localStorage.getItem(LOC_KEY)); } catch (e) { return null; }
         }
         function loadByIp() {
            fetch('https://get.geojs.io/v1/ip/geo.json')
               .then(function (r) { return r.json(); })
               .then(function (loc) {
                  var lat = parseFloat(loc.latitude), lon = parseFloat(loc.longitude);
                  var city = loc.city || loc.region || loc.country || '';
                  if (isNaN(lat) || isNaN(lon)) throw new Error('no geo');
                  getWeather(lat, lon, city);
               })
               .catch(function () { getWeather(28.6139, 77.2090, 'New Delhi'); });
         }

         /* Reverse geocode lat/lon -> place name (free, no API key, CORS-enabled). */
         function reverseGeocode(lat, lon, cb) {
            fetch('https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=' + lat + '&longitude=' + lon + '&localityLanguage=en')
               .then(function (r) { return r.json(); })
               .then(function (d) {
                  cb(d.city || d.locality || d.principalSubdivision || d.countryName || '');
               })
               .catch(function () { cb(''); });
         }

         /* Use the browser's precise GPS location (asks permission). Falls back
            to approximate IP-based location if denied or unavailable. */
         function loadByGeolocation() {
            if (!navigator.geolocation) {
               if (hint) hint.textContent = 'Geolocation not supported by this browser — using approximate location.';
               loadByIp();
               return;
            }

            /* Geolocation only works on a secure origin (https) or localhost.
               On a plain http LAN address the browser blocks it with no prompt. */
            if (window.isSecureContext === false) {
               if (hint) hint.innerHTML = 'Location needs a secure connection. Open the panel via <b>https://</b> or <b>http://localhost</b> to allow precise location. Using approximate location for now.';
               loadByIp();
               return;
            }

            var requested = false;
            function requestPosition() {
               if (requested) return;
               requested = true;
               if (hint) hint.textContent = 'Detecting your location… please choose "Allow" in the browser prompt.';
               navigator.geolocation.getCurrentPosition(
                  function (pos) {
                     var lat = pos.coords.latitude, lon = pos.coords.longitude;
                     reverseGeocode(lat, lon, function (city) {
                        var loc = { name: city || 'My location', lat: lat, lon: lon };
                        try { localStorage.setItem(LOC_KEY, JSON.stringify(loc)); } catch (e) {}
                        if (hint) hint.textContent = 'Showing weather for ' + loc.name + '.';
                        getWeather(lat, lon, loc.name);
                     });
                  },
                  function (err) {
                     if (hint) {
                        hint.textContent = (err && err.code === 1)
                           ? 'Location access was blocked. Click the location/lock icon in the address bar, set Location to "Allow", then try again. Using approximate location for now.'
                           : 'Could not get precise location — using approximate.';
                     }
                     loadByIp();
                  },
                  { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
               );
            }

            /* If the permission was previously blocked the browser will NOT show a
               prompt again — tell the user how to re-enable it instead of silently
               falling back. */
            if (navigator.permissions && navigator.permissions.query) {
               navigator.permissions.query({ name: 'geolocation' }).then(function (status) {
                  if (status.state === 'denied') {
                     if (hint) hint.textContent = 'Location is blocked for this site. Click the location/lock icon in the address bar, set Location to "Allow", then click "Use my location" again.';
                     loadByIp();
                     return;
                  }
                  requestPosition();
               }).catch(function () { requestPosition(); });
            } else {
               requestPosition();
            }
         }
         function loadWeather() {
            var saved = savedLocation();
            if (saved && saved.lat != null && saved.lon != null) {
               getWeather(saved.lat, saved.lon, saved.name || '');
            } else {
               loadByIp();
            }
         }

         /* ----- location editor ----- */
         var editBtn = document.getElementById('dhwEdit');
         var editor = document.getElementById('dhwEditor');
         var input = document.getElementById('dhwInput');
         var results = document.getElementById('dhwResults');
         var hint = document.getElementById('dhwHint');

         function openEditor(open) {
            if (!editor) return;
            editor.style.display = open ? 'block' : 'none';
            if (open && input) { input.focus(); }
         }
         function doSearch() {
            var q = (input && input.value || '').trim();
            results.innerHTML = '';
            if (q.length < 2) { hint.textContent = 'Type at least 2 letters.'; return; }
            hint.textContent = 'Searching…';
            fetch('https://geocoding-api.open-meteo.com/v1/search?name=' + encodeURIComponent(q) + '&count=5&language=en&format=json')
               .then(function (r) { return r.json(); })
               .then(function (d) {
                  if (!d.results || !d.results.length) { hint.textContent = 'No matching city found.'; return; }
                  hint.textContent = '';
                  d.results.forEach(function (r) {
                     var parts = [r.name];
                     if (r.admin1) parts.push(r.admin1);
                     if (r.country) parts.push(r.country);
                     var label = parts.join(', ');
                     var btn = document.createElement('button');
                     btn.type = 'button';
                     btn.className = 'dhw-result';
                     btn.textContent = '📍 ' + label;
                     btn.addEventListener('click', function () {
                        var loc = { name: r.name + (r.country ? ', ' + r.country : ''), lat: r.latitude, lon: r.longitude };
                        try { localStorage.setItem(LOC_KEY, JSON.stringify(loc)); } catch (e) {}
                        getWeather(loc.lat, loc.lon, loc.name);
                        openEditor(false);
                     });
                     results.appendChild(btn);
                  });
               })
               .catch(function () { hint.textContent = 'Search failed. Check your connection.'; });
         }

         if (editBtn) editBtn.addEventListener('click', function () { openEditor(editor.style.display === 'none'); });
         var searchBtn = document.getElementById('dhwSearch');
         if (searchBtn) searchBtn.addEventListener('click', doSearch);
         if (input) {
            input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); doSearch(); } });
            var suggestTimer;
            input.addEventListener('input', function () {
               clearTimeout(suggestTimer);
               var q = (input.value || '').trim();
               if (q.length < 2) { results.innerHTML = ''; hint.textContent = ''; return; }
               suggestTimer = setTimeout(doSearch, 350);
            });
         }
         var autoLocBtn = document.getElementById('dhwAutoLoc');
         if (autoLocBtn) autoLocBtn.addEventListener('click', function () {
            try { localStorage.removeItem(LOC_KEY); } catch (e) {}
            if (results) results.innerHTML = '';
            if (input) input.value = '';
            if (hint) hint.textContent = 'Detecting your location…';
            loadByGeolocation();
         });

         /* ----- weather by PIN / postal code ----- */
         function pinSaveShow(lat, lon, name) {
            var loc = { name: name, lat: lat, lon: lon };
            try { localStorage.setItem(LOC_KEY, JSON.stringify(loc)); } catch (e) {}
            if (hint) hint.textContent = 'Showing weather for ' + name + '.';
            getWeather(lat, lon, name);
            openEditor(false);
         }
         function pinGeocode(q, cb) {
            if (!q) { cb(null); return; }
            fetch('https://geocoding-api.open-meteo.com/v1/search?name=' + encodeURIComponent(q) + '&count=1&language=en&format=json')
               .then(function (r) { return r.json(); })
               .then(function (d) { cb(d.results && d.results.length ? { lat: d.results[0].latitude, lon: d.results[0].longitude } : null); })
               .catch(function () { cb(null); });
         }
         /* Fallback for PINs not in Zippopotam: India Post API gives the area,
            then Open-Meteo geocoding resolves it to coordinates. */
         function pinViaIndiaPost(pin) {
            fetch('https://api.postalpincode.in/pincode/' + pin)
               .then(function (r) { return r.json(); })
               .then(function (d) {
                  var rec = d && d[0];
                  if (!rec || rec.Status !== 'Success' || !rec.PostOffice || !rec.PostOffice.length) throw new Error('no post');
                  var po = rec.PostOffice[0];
                  var place = po.Name, district = po.District, state = po.State || '';
                  var label = (district || place) + (state ? ', ' + state : '') + ' (' + pin + ')';
                  pinGeocode(place, function (g) {
                     if (g) { pinSaveShow(g.lat, g.lon, label); return; }
                     pinGeocode(district, function (g2) {
                        if (g2) { pinSaveShow(g2.lat, g2.lon, label); }
                        else if (hint) { hint.textContent = 'Could not locate PIN ' + pin + ' on the map.'; }
                     });
                  });
               })
               .catch(function () { if (hint) hint.textContent = 'Invalid or unknown PIN code.'; });
         }
         function lookupPin() {
            var pinEl = document.getElementById('dhwPin');
            var pin = ((pinEl && pinEl.value) || '').replace(/\D/g, '');
            if (results) results.innerHTML = '';
            if (pin.length !== 6) { if (hint) hint.textContent = 'Enter a valid 6-digit PIN code.'; return; }
            if (hint) hint.textContent = 'Looking up PIN ' + pin + '…';
            fetch('https://api.zippopotam.us/in/' + pin)
               .then(function (r) { if (!r.ok) throw new Error('zippo ' + r.status); return r.json(); })
               .then(function (d) {
                  if (d && d.places && d.places.length) {
                     var p = d.places[0];
                     var lat = parseFloat(p.latitude), lon = parseFloat(p.longitude);
                     if (!isNaN(lat) && !isNaN(lon)) {
                        pinSaveShow(lat, lon, (p['place name'] || ('PIN ' + pin)) + (p.state ? ', ' + p.state : ''));
                        return;
                     }
                  }
                  throw new Error('no zippo data');
               })
               .catch(function () { pinViaIndiaPost(pin); });
         }
         var pinBtn = document.getElementById('dhwPinBtn');
         if (pinBtn) pinBtn.addEventListener('click', lookupPin);
         var pinInput = document.getElementById('dhwPin');
         if (pinInput) pinInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); lookupPin(); } });

         loadWeather();
      })();

      $(function () {
         $("#datepicker").datepicker({

            dateFormat: "dd-mm-yy",
            "setDate": '01-11-2020'
         });
         var today = new Date();
         var dd = String(today.getDate()).padStart(2, '0');
         var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
         var yyyy = today.getFullYear();
         today = dd + '-' + mm + '-' + yyyy;
         $("#datepicker").val(today);

         $('#datepicker').trigger("change");
      });

      $("#datepicker").change(() => {



         $.ajax({
            url: "<?php echo base_url(); ?>admin/dashboard/getmylatestkisanvahi",
            type: "POST",
            data: {
               activeKishan: $('#datepicker').val()
            }, //our data
            dataType: 'json',
            success: function (a) {
               var html = '';
               $('#repeat').html(html)
               var totalquant = 0;
               $.each(a, function (key, value) {
                  //For example
                  console.log(value)
                  html += ` 
                           <div class="col-md-3 text-center">
                               <a href="Javascript:void(0)">
                                    <div class="layers bd bgc-white p-20">
                              
                                    <div class="layer w-100 mB-20">
                                    
                                    <h6 class="lh-1">` + value.name + ` </h6>
                                    <h6 class="lh-1">( ` + $('#datepicker').val() + ` )</h6>
          
                                    </div>
                                    <div class="layer w-100">
                                          <div class="peers ai-sb fxw-nw">
                                          <div class="peer"><span class="d-ib lh-0 va-m fw-600 bdrs-10em pX-15 pY-15 bgc-purple-50 c-purple-500">` + value.quant + `</span></div>
                                          <div class="peer"><span class="d-ib lh-0 va-m fw-600 bdrs-10em pX-15 pY-15 bgc-purple-50 c-purple-500">` + value.totalKisan + `</span></div>
                                          </div>
                                    </div>
                                    </div>
                                 </a>
                              </div>
                           `;

                  totalquant += parseFloat(value.quant);
                  $('#repeat').html(html);
               })
               $('#total_quant').text("Today's  " + totalquant.toFixed(2))
               console.log(totalquant)
            },
            error: function () {
               alert("Error");
            }
         });

      });

      $('#clickToShow').click(
         () => {
            $('#stats').removeClass('hide').addClass('show')
            $('#clickToShow').addClass('hide')
            $('#clickToHide').removeClass('hide').addClass('show')
         }
      );
      $('#clickToHide').click(
         () => {
            $('#stats').removeClass('show').addClass('hide');
            $('#clickToHide').addClass('hide')
            $('#clickToShow').removeClass('hide').addClass('show')
         }
      );
      $("#contactInfo_next").click(function () {
         $(".info-tab-contianer:nth-child(1) p").css("border-bottom", "none");
         $(".info-tab-contianer:nth-child(2) p").css("border-bottom", "2px solid #2196f3");
         $(".alpha_num_a").hide();
         $(".alpha_num_b").show();
      });
      $("#companyInfo_back").click(function () {
         $(".info-tab-contianer:nth-child(2) p").css("border-bottom", "none");
         $(".info-tab-contianer:nth-child(1) p").css("border-bottom", "2px solid #2196f3");
         $(".alpha_num_b").hide();
         $(".alpha_num_a").show();
      });
   });
</script>

<?php if (!empty($sp_analytics)) { ?>
<script>/* keep Chart.js UMD from registering as an AMD module so it sets window.Chart */
   window.__spAmd = (typeof window.define === 'function' && window.define.amd) ? window.define : null;
   if (window.__spAmd) { window.define = undefined; }
</script>
<script src="<?= base_url('assets/global/plugins/chartjs/chart.umd.min.js') ?>"></script>
<script>
   if (window.__spAmd) { window.define = window.__spAmd; }
   (function () {
      var DATA = <?= json_encode($sp_analytics) ?>;
      /* capture Chart.js v4 NOW, before any later-loading library overwrites window.Chart */
      var ChartLib = (window.Chart && window.Chart.defaults && window.Chart.defaults.font) ? window.Chart : null;
      try { console.log('[SP] ChartLib captured:', !!ChartLib, 'version:', window.Chart && window.Chart.version); } catch (e) {}

      function msg(el, text) {
         if (el) { el.innerHTML = '<div style="display:flex;height:100%;align-items:center;justify-content:center;color:#9aa3c4;font-weight:700;text-align:center;padding:12px;line-height:1.4">' + text + '</div>'; }
      }
      function wrapOf(id) { var c = document.getElementById(id); return c ? c.parentNode : null; }

      function start() {
         try { console.log('[SP] analytics data:', DATA); } catch (e) {}

         if (!DATA) { msg(wrapOf('spTrendChart'), 'No analytics data returned.'); return; }
         if (!ChartLib) {
            ['spTrendChart', 'spShareChart', 'spRateChart', 'spMarginChart', 'spRatioChart'].forEach(function (id) { msg(wrapOf(id), 'Chart library could not load.'); });
            return;
         }
         var Chart = ChartLib;

         var css = getComputedStyle(document.body);
         function v(name, fallback) { var x = css.getPropertyValue(name); return (x && x.trim()) ? x.trim() : fallback; }
         var brand = v('--tm-brand', '#1769c2');
         var accent = v('--tm-accent', '#f0a020');
         var grid = 'rgba(120,135,160,.14)';
         var inr = new Intl.NumberFormat('en-IN', { maximumFractionDigits: 0 });
         var palette = [brand, accent, '#1f9d70', '#9b5de5', '#e5484d', '#0aa6b7', '#f15bb5', '#3a86ff', '#8ac926', '#ff924c'];

         try {
            Chart.defaults.font.family = "'Inter','Segoe UI',Arial,sans-serif";
            Chart.defaults.color = '#6b7890';
         } catch (e) {}

         var months = DATA.months || [];
         var commodity = DATA.commodity || [];
         var commodityMonthly = DATA.commodity_monthly || [];

         try {
            var _t = document.getElementById('spTrendChart');
            console.log('[SP] trend canvas wrap size:', _t && _t.parentNode.clientWidth, 'x', _t && _t.parentNode.clientHeight,
               '| months:', months.length, '| commodities:', commodity.length);
         } catch (e) {}

         /* ---- monthly trend (grouped bars) ---- */
         try {
            var trendEl = document.getElementById('spTrendChart');
            var trendHas = months.some(function (m) { return (+m.sale) || (+m.purchase); });
            if (trendEl && months.length && trendHas) {
               var trendChart = new Chart(trendEl, {
                  type: 'bar',
                  data: {
                     labels: months.map(function (m) { return m.label; }),
                     datasets: [
                        { label: 'Sales', data: months.map(function (m) { return +m.sale || 0; }), backgroundColor: brand, borderRadius: 6, maxBarThickness: 26 },
                        { label: 'Purchase', data: months.map(function (m) { return +m.purchase || 0; }), backgroundColor: accent, borderRadius: 6, maxBarThickness: 26 }
                     ]
                  },
                  options: {
                     responsive: true, maintainAspectRatio: false,
                     plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { weight: '700' } } },
                        tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ₹' + inr.format(c.parsed.y); } } }
                     },
                     scales: {
                        x: { grid: { display: false } },
                        y: { grid: { color: grid }, ticks: { callback: function (val) { return '₹' + inr.format(val); } } }
                     }
                  }
               });
               var commodityFilter = document.getElementById('spCommodityFilter');
               if (commodityFilter) {
                  commodityFilter.addEventListener('change', function () {
                     var selected = this.value;
                     var selectedData = null;
                     for (var i = 0; i < commodityMonthly.length; i++) {
                        if (commodityMonthly[i].commodity === selected) {
                           selectedData = commodityMonthly[i];
                           break;
                        }
                     }
                     var saleData = selectedData ? selectedData.sale : months.map(function (m) { return +m.sale || 0; });
                     var purchaseData = selectedData ? selectedData.purchase : months.map(function (m) { return +m.purchase || 0; });
                     trendChart.data.datasets[0].label = selected ? selected + ' Sales' : 'Sales';
                     trendChart.data.datasets[0].data = saleData;
                     trendChart.data.datasets[1].label = selected ? selected + ' Purchase' : 'Purchase';
                     trendChart.data.datasets[1].data = purchaseData;
                     trendChart.update();
                  });
               }
            } else if (trendEl) {
               msg(trendEl.parentNode, 'No dated sales/purchase in the last 6 months.');
            }
         } catch (e) { msg(wrapOf('spTrendChart'), 'Trend chart error: ' + e.message); }

         /* ---- sales share by commodity (doughnut) ---- */
         try {
            var shareEl = document.getElementById('spShareChart');
            if (shareEl) {
               var byShare = commodity.filter(function (c) { return (+c.sale_amount) > 0; })
                  .sort(function (a, b) { return b.sale_amount - a.sale_amount; });
               var top = byShare.slice(0, 6);
               var rest = byShare.slice(6).reduce(function (s, c) { return s + (+c.sale_amount); }, 0);
               var labels = top.map(function (c) { return c.commodity; });
               var values = top.map(function (c) { return +c.sale_amount; });
               if (rest > 0) { labels.push('Others'); values.push(rest); }

               if (values.length) {
                  new Chart(shareEl, {
                     type: 'doughnut',
                     data: { labels: labels, datasets: [{ data: values, backgroundColor: palette, borderColor: '#fff', borderWidth: 2 }] },
                     options: {
                        responsive: true, maintainAspectRatio: false, cutout: '62%',
                        plugins: {
                           legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { weight: '700' } } },
                           tooltip: { callbacks: { label: function (c) { return c.label + ': ₹' + inr.format(c.parsed); } } }
                        }
                     }
                  });
               } else {
                  msg(shareEl.parentNode, 'No sales recorded for this financial year.');
               }
            }
         } catch (e) { msg(wrapOf('spShareChart'), 'Share chart error: ' + e.message); }

         /* ---- commodity rate: purchase vs sale (horizontal bars) ---- */
         try {
            var rateEl = document.getElementById('spRateChart');
            if (rateEl) {
               var rated = commodity.filter(function (c) { return (+c.sale_rate) > 0 || (+c.purchase_rate) > 0; }).slice(0, 7);
               if (rated.length) {
                  new Chart(rateEl, {
                     type: 'bar',
                     data: {
                        labels: rated.map(function (c) { return c.commodity; }),
                        datasets: [
                           { label: 'Purchase Rate', data: rated.map(function (c) { return +c.purchase_rate || 0; }), backgroundColor: accent, borderRadius: 5, maxBarThickness: 16 },
                           { label: 'Sale Rate', data: rated.map(function (c) { return +c.sale_rate || 0; }), backgroundColor: brand, borderRadius: 5, maxBarThickness: 16 }
                        ]
                     },
                     options: {
                        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                        plugins: {
                           legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { weight: '700' } } },
                           tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ₹' + inr.format(c.parsed.x); } } }
                        },
                        scales: {
                           x: { grid: { color: grid }, ticks: { callback: function (val) { return '₹' + inr.format(val); } } },
                           y: { grid: { display: false } }
                        }
                     }
                  });
               } else {
                  msg(rateEl.parentNode, 'No commodity rates available.');
               }
            }
         } catch (e) { msg(wrapOf('spRateChart'), 'Rate chart error: ' + e.message); }

         /* ---- profit margin % by commodity (horizontal bars) ---- */
         try {
            var marginEl = document.getElementById('spMarginChart');
            if (marginEl) {
               var withMargin = commodity.filter(function (c) {
                  return (+c.sale_rate) > 0 && (+c.purchase_rate) > 0;
               }).map(function (c) {
                  var m = (c.sale_rate - c.purchase_rate) / c.purchase_rate * 100;
                  return { commodity: c.commodity, margin: Math.round(m * 10) / 10 };
               }).sort(function (a, b) { return b.margin - a.margin; }).slice(0, 8);

               if (withMargin.length) {
                  new Chart(marginEl, {
                     type: 'bar',
                     data: {
                        labels: withMargin.map(function (c) { return c.commodity; }),
                        datasets: [{
                           label: 'Profit Margin %',
                           data: withMargin.map(function (c) { return c.margin; }),
                           backgroundColor: withMargin.map(function (c) { return c.margin >= 0 ? '#1f9d70' : '#e5484d'; }),
                           borderRadius: 5, maxBarThickness: 18
                        }]
                     },
                     options: {
                        indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                        plugins: {
                           legend: { display: false },
                           tooltip: { callbacks: { label: function (c) { return 'Margin: ' + c.parsed.x + '%'; } } }
                        },
                        scales: {
                           x: { grid: { color: grid }, ticks: { callback: function (val) { return val + '%'; } } },
                           y: { grid: { display: false } }
                        }
                     }
                  });
               } else {
                  msg(marginEl.parentNode, 'Need both purchase &amp; sale rates to compute margin.');
               }
            }
         } catch (e) { msg(wrapOf('spMarginChart'), 'Margin chart error: ' + e.message); }

         /* ---- overall profit ratio: sales = cost + profit (doughnut) ---- */
         try {
            var ratioEl = document.getElementById('spRatioChart');
            if (ratioEl) {
               var totals = DATA.totals || {};
               var sale = +totals.sale || 0;
               var purchase = +totals.purchase || 0;
               var profit = +totals.profit || 0;
               if (sale > 0 || purchase > 0) {
                  var ratio = sale > 0 ? Math.round(profit / sale * 1000) / 10 : 0;
                  var profitPos = profit >= 0;
                  new Chart(ratioEl, {
                     type: 'doughnut',
                     data: {
                        labels: ['Purchase (Cost)', profitPos ? 'Profit' : 'Loss'],
                        datasets: [{
                           data: [purchase, Math.abs(profit)],
                           backgroundColor: [accent, profitPos ? '#1f9d70' : '#e5484d'],
                           borderColor: '#fff', borderWidth: 2
                        }]
                     },
                     options: {
                        responsive: true, maintainAspectRatio: false, cutout: '64%',
                        plugins: {
                           legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { weight: '700' } } },
                           tooltip: { callbacks: { label: function (c) { return c.label + ': ₹' + inr.format(c.parsed); } } },
                           title: { display: true, text: 'Profit Ratio: ' + ratio + '%', position: 'top', font: { size: 14, weight: '800' }, color: profitPos ? '#1f9d70' : '#e5484d' }
                        }
                     }
                  });
               } else {
                  msg(ratioEl.parentNode, 'No sales / purchase recorded for this financial year.');
               }
            }
         } catch (e) { msg(wrapOf('spRatioChart'), 'Ratio chart error: ' + e.message); }
      }

      function boot() { setTimeout(start, 60); }
      if (document.readyState === 'loading') {
         document.addEventListener('DOMContentLoaded', boot);
      } else {
         boot();
      }
   })();
</script>
<?php } ?>

