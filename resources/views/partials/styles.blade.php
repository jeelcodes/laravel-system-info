<style>
  * { box-sizing: border-box; }

  :root {
    --bg-page: #0f172a;
    --bg-surface: #1e293b;
    --bg-surface-hover: #1a2535;
    --border-color: #334155;
    
    --text-main: #f1f5f9; /* Increased brightness */
    --text-muted: #94a3b8; /* Increased brightness for readability */
    --text-heading: #ffffff;
    --text-bright: #ffffff;
    
    --accent-violet: #8b5cf6;
    --vendor-bg: #1e1b4b;
    --vendor-text: #a5b4fc;
    --ver-bg: #334155;
    --ver-text: #22d3ee;
    
    --warn-bg: #451a03;
    --warn-border: #92400e;
    --warn-text: #fde68a;

    --btn-hover-bg: #475569;
    --pill-ok-bg: #052e16;
    --pill-ok-border: #166534;
    --pill-err-bg: #450a0a;
    --pill-err-border: #991b1b;
    
    --text-ok: #4ade80;
    --text-warn: #fbbf24;
    --text-err: #f87171;
  }

  [data-theme="light"] {
    --bg-page: #f8fafc;
    --bg-surface: #ffffff;
    --bg-surface-hover: #f1f5f9;
    --border-color: #e2e8f0;
    
    --text-main: #0f172a; /* Darkened for higher contrast */
    --text-muted: #475569; /* Darkened for higher contrast */
    --text-heading: #020617;
    --text-bright: #020617;
    
    --accent-violet: #6d28d9;
    --vendor-bg: #ede9fe;
    --vendor-text: #6d28d9;
    --ver-bg: #e0f2fe;
    --ver-text: #0369a1;
    
    --warn-bg: #fffbeb;
    --warn-border: #fcd34d;
    --warn-text: #92400e;

    --btn-hover-bg: #e2e8f0;
    --pill-ok-bg: #f0fdf4;
    --pill-ok-border: #86efac;
    --pill-err-bg: #fef2f2;
    --pill-err-border: #fca5a5;
    
    --text-ok: #15803d; /* Darkened for visibility on white */
    --text-warn: #b45309; /* Darkened for visibility on white */
    --text-err: #b91c1c; /* Darkened for visibility on white */
  }

  body {
    background: var(--bg-page);
    color: var(--text-main);
    font-family: 'Menlo', 'Monaco', 'Consolas', monospace;
    font-size: 12px;
    min-height: 100vh;
  }

  /* ── macOS-style titlebar ── */
  .titlebar {
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border-color);
    display: flex; align-items: center;
    height: 44px; padding: 0 16px; gap: 0;
  }
  .traffic-lights { display: flex; gap: 6px; margin-right: 16px; }
  .tl { width: 12px; height: 12px; border-radius: 50%; cursor: pointer; }
  .tl-r { background: #ff5f57; }
  .tl-y { background: #febc2e; }
  .tl-g { background: #28c840; }
  .win-title { color: var(--text-muted); font-size: 11px; flex: 1; text-align: center; }
  .tab-list { display: flex; margin-left: auto; }
  .nav-tab {
    display: flex; align-items: center; gap: 6px;
    height: 44px; padding: 0 16px;
    color: var(--text-muted); font-size: 11px;
    cursor: pointer; border: none; background: none;
    border-bottom: 2px solid transparent;
    text-decoration: none; transition: color .15s;
    font-family: inherit;
  }
  .nav-tab:hover { color: var(--text-main); text-decoration: none; }
  .nav-tab.active { color: var(--text-heading); border-bottom-color: var(--accent-violet); }
  .nav-tab .badge-dark {
    background: var(--border-color); color: var(--text-muted);
    font-size: 10px; padding: 1px 6px; border-radius: 8px;
  }
  .nav-tab.active .badge-dark { background: var(--vendor-bg); color: var(--vendor-text); }

  /* ── Prompt/search bar ── */
  .prompt-bar {
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border-color);
    padding: 8px 16px;
    display: none; align-items: center; gap: 10px;
  }
  .prompt-bar.visible { display: flex; }
  .prompt-sigil { color: var(--text-ok); white-space: nowrap; font-size: 11px; }
  .prompt-input {
    flex: 1; background: transparent; border: none; outline: none;
    color: var(--text-bright); font-family: inherit; font-size: 12px;
    caret-color: var(--text-ok);
  }
  .prompt-input::placeholder { color: var(--text-muted); }
  .sort-btn {
    background: var(--border-color); border: none; color: var(--text-muted);
    font-size: 11px; padding: 4px 10px; border-radius: 6px;
    cursor: pointer; font-family: inherit;
    display: flex; align-items: center; gap: 4px;
    transition: all .15s; white-space: nowrap;
  }
  .sort-btn:hover { background: var(--btn-hover-bg); color: var(--text-heading); }
  .sort-btn.active { background: var(--accent-violet); color: #fff; }

  /* ── Content ── */
  .content { padding: 16px; }
  .panel { display: none; }
  .panel.active { display: block; }

  /* ── Stat cards ── */
  .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 8px; margin-bottom: 16px; }
  .stat-card { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 14px; }
  .stat-card-top { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
  .stat-card-label { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; font-family: sans-serif; }
  .stat-card-val { font-size: 18px; font-weight: 600; font-family: sans-serif; color: var(--text-heading); }
  .stat-card-val.ok   { color: var(--text-ok); }
  .stat-card-val.warn { color: var(--text-warn); }
  .stat-card-val.mono { font-family: monospace; font-size: 13px; }

  /* ── Warn strip ── */
  .warn-strip {
    background: var(--warn-bg); border: 1px solid var(--warn-border);
    border-radius: 8px; padding: 10px 14px;
    display: flex; align-items: center; gap: 10px;
    font-family: sans-serif; font-size: 12px; color: var(--warn-text);
    margin-bottom: 16px;
  }

  /* ── Section divider ── */
  .section-divider { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
  .section-divider span { font-size: 10px; text-transform: uppercase; letter-spacing: .08em; color: var(--text-muted); font-family: sans-serif; white-space: nowrap; }
  .section-divider hr { flex: 1; border: none; border-top: 1px solid var(--border-color); margin: 0; }

  /* ── KV grid ── */
  .kv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: var(--border-color); border-radius: 8px; overflow: hidden; margin-bottom: 16px; }
  .kv-item { background: var(--bg-surface); padding: 10px 14px; }
  .kv-key { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 3px; font-family: sans-serif; }
  .kv-val { font-size: 13px; color: var(--text-heading); }

  /* ── Tables ── */
  .tbl-shell { background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 8px; }
  .tbl-shell table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 0; table-layout: fixed; }
  .tbl-shell thead th {
    background: var(--border-color); color: var(--text-muted);
    font-size: 10px; text-transform: uppercase; letter-spacing: .06em;
    padding: 7px 12px; text-align: left;
    font-family: sans-serif; border-bottom: 1px solid var(--bg-page);
    position: sticky; top: 0; z-index: 10;
  }
  .tbl-shell thead th:first-child { border-top-left-radius: 7px; }
  .tbl-shell thead th:last-child { border-top-right-radius: 7px; }
  .tbl-shell tbody td { padding: 7px 12px; border-bottom: 1px solid var(--bg-page); color: var(--text-main); }
  .tbl-shell tbody tr:last-child td { border-bottom: none; }
  .tbl-shell tbody tr:hover td { background: var(--bg-surface-hover); }
  .tbl-shell tfoot td { padding: 7px 12px; background: var(--bg-surface); border-top: 1px solid var(--border-color); color: var(--text-muted); font-size: 11px; font-family: sans-serif; }

  .ver-tag { display: inline-block; background: var(--ver-bg); color: var(--ver-text); font-size: 11px; padding: 1px 8px; border-radius: 4px; font-family: monospace; }
  .vendor-tag { display: inline-block; background: var(--vendor-bg); color: var(--vendor-text); font-size: 10px; padding: 1px 6px; border-radius: 4px; margin-right: 4px; font-family: monospace; }
  .row-num { color: var(--text-muted); font-size: 11px; }
  .pill-ok  { display: inline-block; background: var(--pill-ok-bg); color: var(--text-ok); border: 1px solid var(--pill-ok-border); font-size: 11px; padding: 2px 8px; border-radius: 20px; font-family: sans-serif; }
  .pill-err { display: inline-block; background: var(--pill-err-bg); color: var(--text-err); border: 1px solid var(--pill-err-border); font-size: 11px; padding: 2px 8px; border-radius: 20px; font-family: sans-serif; }

  /* ── Vendor chips ── */
  .chip-row { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px; }
  .vchip {
    background: var(--bg-surface); border: 1px solid var(--border-color);
    color: var(--text-muted); font-size: 11px; padding: 3px 10px;
    border-radius: 20px; cursor: pointer;
    font-family: sans-serif; transition: all .12s;
  }
  .vchip:hover { border-color: var(--accent-violet); color: var(--accent-violet); }
  .vchip.active { background: var(--vendor-bg); border-color: var(--accent-violet); color: var(--vendor-text); }

  /* ── Responsive ── */
  @media (max-width: 768px) {
    .titlebar {
      height: auto;
      flex-wrap: wrap;
      padding: 12px 16px;
      gap: 12px;
    }
    .win-title {
      display: none;
    }
    .traffic-lights {
      margin-right: auto;
    }
    .tab-list {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      padding-bottom: 4px;
    }
    .nav-tab {
      white-space: nowrap;
    }
    .prompt-bar {
      flex-wrap: wrap;
    }
    .prompt-input {
      min-width: 150px;
    }
    .stat-grid {
      grid-template-columns: 1fr 1fr;
    }
    .kv-grid {
      grid-template-columns: 1fr;
    }
    .tbl-shell {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    .tbl-shell table {
      min-width: 850px;
    }
    #vulnModal .modal-dialog {
      margin: 1rem;
      width: auto;
      max-width: none;
    }
    #vulnModalBody {
      max-height: calc(100vh - 140px);
    }
  }
</style>
