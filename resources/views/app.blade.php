<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>API DocsBuilder — Powered by Stanitech</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Sora:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <style>
        /* ── DESIGN TOKENS ───────────────────────────────────────────────────── */
        :root {
            --sidebar-w: 280px;
            --bg: #0d1117;
            --bg2: #161b22;
            --bg3: #21262d;
            --bg4: #2d333b;
            --border: #30363d;
            --text: #e6edf3;
            --muted: #8b949e;
            --accent: #58a6ff;
            --accent2: #f78166;
            --green: #56d364;
            --yellow: #f8c471;
            --purple: #bc8cff;
            --radius: 8px;
            --ai-color: #a78bfa;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            min-height: 100vh;
        }

        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        /* ── TOP NAV ─────────────────────────────────────────────────────────── */
        .top-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(13, 17, 23, .95);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
            height: 60px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            gap: 12px;
        }

        .nav-logo {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .logo-spark {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            background: linear-gradient(135deg, #58a6ff, #a78bfa);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .85rem;
            color: #fff;
            font-weight: 800;
        }

        .nav-sep {
            color: var(--border);
            margin: 0 2px;
        }

        #collectionSelect {
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 6px;
            padding: 5px 10px;
            font-size: .8rem;
            max-width: 200px;
        }

        #collectionSelect:focus {
            outline: none;
            border-color: var(--accent);
        }

        #searchBox {
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 6px;
            padding: 6px 14px 6px 36px;
            font-size: .82rem;
            width: 220px;
        }

        #searchBox:focus {
            outline: none;
            border-color: var(--accent);
        }

        .search-wrap {
            position: relative;
        }

        .search-wrap .bi-search {
            position: absolute;
            left: 11px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .8rem;
        }

        .nav-btn {
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 6px;
            padding: 6px 14px;
            font-size: .8rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all .15s;
            white-space: nowrap;
        }

        .nav-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .nav-btn.ai-btn {
            background: linear-gradient(135deg, rgba(167, 139, 250, .18), rgba(88, 166, 255, .1));
            border-color: rgba(167, 139, 250, .4);
            color: var(--ai-color);
        }

        .nav-btn.ai-btn:hover {
            border-color: var(--ai-color);
            background: rgba(167, 139, 250, .25);
        }

        .ollama-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: .72rem;
            color: var(--muted);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--muted);
            transition: background .3s;
        }

        .status-dot.online {
            background: var(--green);
            box-shadow: 0 0 6px var(--green);
        }

        .status-dot.offline {
            background: var(--accent2);
        }

        .ms-auto {
            margin-left: auto;
        }

        /* ── LAYOUT ──────────────────────────────────────────────────────────── */
        .layout {
            display: flex;
            padding-top: 60px;
            min-height: 100vh;
        }

        /* ── SIDEBAR ─────────────────────────────────────────────────────────── */
        .sidebar {
            width: var(--sidebar-w);
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            background: var(--bg2);
            border-right: 1px solid var(--border);
            padding: 16px 0;
            transition: transform .25s;
        }

        .sidebar-heading {
            font-size: .62rem;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 10px 18px 4px;
        }

        .group-nav-item {
            padding: 0;
        }

        .group-nav-link {
            display: flex;
            align-items: center;
            color: var(--muted);
            font-size: .8rem;
            padding: 7px 18px;
            cursor: pointer;
            transition: all .15s;
            gap: 8px;
            border-left: 2px solid transparent;
            text-decoration: none;
        }

        .group-nav-link:hover {
            color: var(--text);
            background: rgba(255, 255, 255, .04);
            border-left-color: var(--border);
        }

        .group-nav-link.active {
            color: var(--accent);
            background: rgba(88, 166, 255, .08);
            border-left-color: var(--accent);
        }

        .group-nav-link .grp-count {
            margin-left: auto;
            font-size: .65rem;
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1px 7px;
            color: var(--muted);
        }

        .sidebar-actions {
            padding: 12px 14px;
            border-top: 1px solid var(--border);
            margin-top: 12px;
        }

        .sidebar-actions .nav-btn {
            width: 100%;
            justify-content: center;
        }

        /* ── MAIN CONTENT ────────────────────────────────────────────────────── */
        .main-content {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 32px 36px 80px;
            max-width: calc(100vw - var(--sidebar-w));
        }

        /* ── EMPTY / UPLOAD STATE ────────────────────────────────────────────── */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: 14px;
            padding: 60px 40px;
            text-align: center;
            transition: border-color .2s, background .2s;
            cursor: pointer;
            margin-top: 40px;
        }

        .upload-zone:hover,
        .upload-zone.drag-over {
            border-color: var(--accent);
            background: rgba(88, 166, 255, .05);
        }

        .upload-zone .upload-icon {
            font-size: 3rem;
            color: var(--muted);
            margin-bottom: 16px;
            display: block;
        }

        .upload-zone h3 {
            font-size: 1.15rem;
            margin-bottom: 8px;
        }

        .upload-zone p {
            color: var(--muted);
            font-size: .85rem;
        }

        /* ── HERO ────────────────────────────────────────────────────────────── */
        .api-hero {
            background: linear-gradient(135deg, #161b22 0%, #0d1117 60%, #161b2240 100%);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 30px 36px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .api-hero::before {
            content: '';
            position: absolute;
            top: -80px;
            right: -80px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(88, 166, 255, .1) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-title {
            font-size: 1.55rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .hero-desc {
            color: var(--muted);
            font-size: .85rem;
            margin-bottom: 18px;
        }

        .stats-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .stat-pill {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: var(--bg3);
            border: 1px solid;
            border-radius: 8px;
            padding: 8px 16px;
            min-width: 64px;
        }

        .stat-count {
            font-size: 1.25rem;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            line-height: 1;
        }

        .stat-label {
            font-size: .62rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            margin-top: 2px;
        }

        .total-pill {
            background: linear-gradient(135deg, rgba(88, 166, 255, .18), rgba(88, 166, 255, .06));
            border: 1px solid rgba(88, 166, 255, .35);
            border-radius: 8px;
            padding: 8px 16px;
            min-width: 64px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .total-pill .stat-count {
            color: var(--accent);
        }

        .hero-ai-bar {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .hero-ai-bar .ai-label {
            font-size: .75rem;
            color: var(--ai-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .batch-progress-wrap {
            flex: 1;
            min-width: 200px;
            background: var(--bg3);
            border-radius: 20px;
            height: 6px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .batch-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--ai-color), var(--accent));
            border-radius: 20px;
            transition: width .4s ease;
            width: 0%;
        }

        .batch-status-text {
            font-size: .75rem;
            color: var(--muted);
        }

        /* ── GROUP SECTION ───────────────────────────────────────────────────── */
        .api-group {
            margin-bottom: 40px;
        }

        .group-header {
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .group-header h2 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
        }

        .group-count {
            font-size: .7rem;
            color: var(--muted);
            background: var(--bg3);
            border: 1px solid var(--border);
            padding: 1px 9px;
            border-radius: 20px;
        }

        .group-header .btn-group-ai {
            margin-left: auto;
            background: transparent;
            border: 1px solid rgba(167, 139, 250, .3);
            color: var(--ai-color);
            border-radius: 5px;
            padding: 3px 10px;
            font-size: .72rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all .15s;
        }

        .group-header .btn-group-ai:hover {
            background: rgba(167, 139, 250, .12);
            border-color: var(--ai-color);
        }

        /* ── ENDPOINT CARD ───────────────────────────────────────────────────── */
        .endpoint-card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 8px;
            overflow: hidden;
            transition: border-color .2s;
        }

        .endpoint-card:hover {
            border-color: #444c56;
        }

        .endpoint-card.has-summary {
            border-left: 2px solid rgba(167, 139, 250, .5);
        }

        .endpoint-header {
            padding: 13px 16px;
            cursor: pointer;
            transition: background .15s;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .endpoint-header:hover {
            background: rgba(255, 255, 255, .025);
        }

        .endpoint-name {
            font-weight: 600;
            font-size: .87rem;
            color: var(--text);
        }

        .endpoint-url {
            margin-top: 3px;
        }

        .endpoint-url code {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 1px 7px;
            color: #f0883e;
            font-family: 'JetBrains Mono', monospace;
            font-size: .75rem;
        }

        .endpoint-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .method-badge {
            color: #fff;
            font-weight: 700;
            font-size: .66rem;
            padding: 3px 9px;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: .04em;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .auth-badge {
            background: rgba(248, 196, 113, .12);
            color: var(--yellow);
            border: 1px solid rgba(248, 196, 113, .3);
            border-radius: 4px;
            padding: 1px 7px;
            font-size: .68rem;
        }

        .auth-none {
            background: rgba(139, 148, 158, .08);
            color: var(--muted);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 1px 7px;
            font-size: .68rem;
        }

        .ai-summary-badge {
            background: rgba(167, 139, 250, .12);
            color: var(--ai-color);
            border: 1px solid rgba(167, 139, 250, .3);
            border-radius: 4px;
            padding: 1px 7px;
            font-size: .67rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .collapse-chevron {
            color: var(--muted);
            transition: transform .25s;
            font-size: .8rem;
            flex-shrink: 0;
        }

        .endpoint-header.open .collapse-chevron {
            transform: rotate(180deg);
        }

        /* ── ENDPOINT BODY ───────────────────────────────────────────────────── */
        .endpoint-body {
            border-top: 1px solid var(--border);
            padding: 18px 16px;
            background: var(--bg);
            display: none;
        }

        .endpoint-body.open {
            display: block;
        }

        /* AI Summary panel */
        .ai-panel {
            background: linear-gradient(135deg, rgba(167, 139, 250, .08), rgba(88, 166, 255, .05));
            border: 1px solid rgba(167, 139, 250, .25);
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 16px;
            position: relative;
        }

        .ai-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .ai-panel-title {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ai-color);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .ai-panel-actions {
            display: flex;
            gap: 6px;
        }

        .ai-panel-btn {
            background: transparent;
            border: 1px solid rgba(167, 139, 250, .3);
            color: var(--ai-color);
            border-radius: 4px;
            padding: 2px 8px;
            font-size: .7rem;
            cursor: pointer;
            transition: all .15s;
        }

        .ai-panel-btn:hover {
            background: rgba(167, 139, 250, .15);
        }

        .ai-panel-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .ai-summary-text {
            font-size: .82rem;
            color: var(--text);
            line-height: 1.75;
            white-space: pre-wrap;
            font-family: 'Sora', sans-serif;
        }

        .ai-summary-text strong {
            color: var(--accent);
        }

        .ai-thinking {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--ai-color);
            font-size: .8rem;
        }

        .ai-thinking .dots span {
            animation: blink 1.2s infinite;
            font-size: 1.2rem;
            line-height: 1;
        }

        .ai-thinking .dots span:nth-child(2) {
            animation-delay: .2s;
        }

        .ai-thinking .dots span:nth-child(3) {
            animation-delay: .4s;
        }

        @keyframes blink {

            0%,
            80%,
            100% {
                opacity: .2;
            }

            40% {
                opacity: 1;
            }
        }

        .ai-generate-btn {
            background: linear-gradient(135deg, rgba(167, 139, 250, .15), rgba(88, 166, 255, .08));
            border: 1px solid rgba(167, 139, 250, .35);
            color: var(--ai-color);
            border-radius: 6px;
            padding: 8px 16px;
            font-size: .8rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: all .2s;
            font-family: 'Sora', sans-serif;
            width: 100%;
            justify-content: center;
            margin-bottom: 16px;
        }

        .ai-generate-btn:hover {
            background: rgba(167, 139, 250, .25);
            border-color: var(--ai-color);
        }

        .ai-generate-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        /* Params & tables */
        .section-heading {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            margin: 14px 0 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .param-table {
            font-size: .78rem;
            margin-bottom: 4px;
        }

        .param-table thead tr {
            background: var(--bg3);
        }

        .param-table th {
            color: var(--muted);
            font-weight: 600;
            border-color: var(--border);
            padding: 6px 10px;
        }

        .param-table td {
            border-color: var(--border);
            padding: 6px 10px;
            vertical-align: top;
            color: var(--accent2);
        }

        .param-table tbody tr {
            background: var(--bg2);
        }

        .param-table tbody tr:hover {
            background: var(--bg3);
        }

        .param-table code {
            background: var(--bg3);
            border-radius: 3px;
            padding: 1px 5px;
            color: #79c0ff;
            font-size: .8em;
        }

        .raw-body {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 14px;
            font-size: .76rem;
            color: #79c0ff;
            white-space: pre-wrap;
            font-family: 'JetBrains Mono', monospace;
            max-height: 220px;
            overflow-y: auto;
        }

        .resp-block {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }

        .resp-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: .78rem;
        }

        .resp-body {
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 5px;
            padding: 8px 12px;
            margin: 0;
            font-size: .74rem;
            max-height: 260px;
            overflow-y: auto;
            font-family: 'JetBrains Mono', monospace;
            white-space: pre-wrap;
            word-break: break-all;
            color: var(--green);
        }

        .desc-block {
            font-size: .8rem;
            color: var(--muted);
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 14px;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .desc-block code {
            background: var(--bg3);
            border-radius: 3px;
            padding: 1px 5px;
            color: #79c0ff;
            font-size: .85em;
        }

        /* ── MODAL (Add/Edit Endpoint) ───────────────────────────────────────── */
        .modal-content {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .modal-header {
            border-bottom: 1px solid var(--border);
        }

        .modal-footer {
            border-top: 1px solid var(--border);
        }

        .modal-title {
            font-size: .95rem;
            font-weight: 600;
        }

        .form-label {
            font-size: .78rem;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .form-control,
        .form-select,
        textarea {
            background: var(--bg3) !important;
            border: 1px solid var(--border) !important;
            color: var(--text) !important;
            border-radius: 6px;
            font-size: .82rem;
        }

        .form-control:focus,
        .form-select:focus,
        textarea:focus {
            box-shadow: 0 0 0 2px rgba(88, 166, 255, .2) !important;
            border-color: var(--accent) !important;
        }

        .form-control::placeholder,
        textarea::placeholder {
            color: var(--muted) !important;
        }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #000;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #79bcff;
            border-color: #79bcff;
        }

        .btn-outline-secondary {
            border-color: var(--border);
            color: var(--muted);
        }

        .btn-outline-secondary:hover {
            background: var(--bg3);
            color: var(--text);
            border-color: var(--muted);
        }

        .method-select-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .method-chip {
            padding: 4px 12px;
            border-radius: 4px;
            border: 1px solid var(--border);
            cursor: pointer;
            font-size: .75rem;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            color: var(--muted);
            background: var(--bg3);
            transition: all .15s;
        }

        .method-chip.selected {
            color: #fff;
            border-color: transparent;
        }

        .dynamic-params {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .param-row {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .param-row .form-control {
            flex: 1;
        }

        .param-row .btn-remove {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--accent2);
            border-radius: 5px;
            padding: 5px 8px;
            cursor: pointer;
            font-size: .8rem;
        }

        .param-row .btn-remove:hover {
            background: rgba(247, 129, 102, .12);
        }

        .add-param-btn {
            background: transparent;
            border: 1px dashed var(--border);
            color: var(--muted);
            border-radius: 5px;
            padding: 5px 12px;
            cursor: pointer;
            font-size: .75rem;
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .add-param-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        /* ── NOTIFICATIONS ───────────────────────────────────────────────────── */
        .toast-stack {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .app-toast {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 16px;
            font-size: .8rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideUp .25s ease;
            max-width: 320px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .4);
        }

        .app-toast.success {
            border-color: rgba(86, 211, 100, .4);
        }

        .app-toast.error {
            border-color: rgba(247, 129, 102, .4);
        }

        .app-toast.ai {
            border-color: rgba(167, 139, 250, .4);
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* ── BACK TO TOP ─────────────────────────────────────────────────────── */
        #backToTop {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 800;
            background: var(--accent);
            color: #000;
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            font-size: 1rem;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(88, 166, 255, .35);
            transition: opacity .2s;
            display: none;
        }

        /* ── SEARCH FILTER ───────────────────────────────────────────────────── */
        .search-hidden {
            display: none !important;
        }

        /* ── MODEL SELECTOR ──────────────────────────────────────────────────── */
        .model-badge {
            font-size: .7rem;
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2px 10px;
            color: var(--muted);
            cursor: pointer;
            transition: all .15s;
        }

        .model-badge:hover {
            border-color: var(--ai-color);
            color: var(--ai-color);
        }

        /* ── RESPONSIVE ──────────────────────────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                padding: 20px 14px 60px;
            }

            .main-content.sidebar-open .sidebar {
                transform: translateX(0);
            }
        }
    </style>
</head>

<body>

    <!-- ── TOP NAV ──────────────────────────────────────────────────────────── -->
    <nav class="top-nav">
        <a class="nav-logo" href="#">
            <span class="logo-spark">A</span>
            API Docs
        </a>
        <span class="nav-sep">|</span>

        <select id="collectionSelect" title="Switch collection">
            <option value="">— Select Collection —</option>
        </select>

        <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input id="searchBox" type="text" placeholder="Search endpoints…">
        </div>

        <span class="ms-auto"></span>

        <span class="ollama-status" id="ollamaStatus" title="Ollama connection status">
            <span class="status-dot" id="statusDot"></span>
            <span id="statusLabel">Checking…</span>
            <span class="model-badge" id="modelBadge" onclick="openModelModal()">—</span>
        </span>

        <button class="nav-btn ai-btn" onclick="batchSummarizeAll()" id="batchBtn"
            title="Generate AI docs for all endpoints">
            <i class="bi bi-stars"></i> Generate All Docs
        </button>

        <button class="nav-btn" onclick="openUploadModal()">
            <i class="bi bi-upload"></i> Import
        </button>

        <button class="nav-btn" onclick="openEndpointModal()">
            <i class="bi bi-plus"></i> Endpoint
        </button>
    </nav>

    <!-- ── SIDEBAR ──────────────────────────────────────────────────────────── -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-heading">Sections</div>
        <ul class="nav flex-column" id="sidebarNav"></ul>
        <div class="sidebar-actions">
            <button class="nav-btn" style="width:100%;justify-content:center;" onclick="openUploadModal()">
                <i class="bi bi-file-earmark-arrow-up"></i> Import Collection
            </button>
        </div>
    </aside>

    <!-- ── MAIN ──────────────────────────────────────────────────────────────── -->
    <div class="layout">
        <main class="main-content" id="mainContent">

            <!-- Upload / empty state -->
            <div id="uploadZone" class="upload-zone" onclick="openUploadModal()" ondragover="handleDragOver(event)"
                ondrop="handleDrop(event)">
                <i class="bi bi-file-earmark-code upload-icon"></i>
                <h3>Import a Postman Collection</h3>
                <p>Drop a <code>.json</code> file here, or click to browse.<br>
                    Your endpoints will be parsed and AI-ready instantly.</p>
                <div style="margin-top:18px;">
                    <span class="nav-btn" style="display:inline-flex;">
                        <i class="bi bi-upload"></i> Choose File
                    </span>
                </div>
            </div>

            <!-- Collection view (hidden until a collection is loaded) -->
            <div id="collectionView" style="display:none;">

                <!-- Hero -->
                <div class="api-hero" id="heroSection">
                    <div class="hero-title" id="heroTitle">Collection Name</div>
                    <div class="hero-desc" id="heroDesc">—</div>
                    <div class="stats-row" id="heroStats"></div>
                    <div class="hero-ai-bar">
                        <div class="ai-label"><i class="bi bi-stars"></i> AI Documentation</div>
                        <div class="batch-progress-wrap">
                            <div class="batch-progress-bar" id="batchBar"></div>
                        </div>
                        <span class="batch-status-text" id="batchStatusText">—</span>
                        <button class="nav-btn ai-btn" onclick="batchSummarizeAll()"
                            style="padding:4px 12px;font-size:.75rem;">
                            <i class="bi bi-arrow-clockwise"></i> Regenerate All
                        </button>
                    </div>
                </div>

                <!-- Endpoint Groups -->
                <div id="endpointGroups"></div>

            </div>

        </main>
    </div>

    <!-- ── UPLOAD MODAL ───────────────────────────────────────────────────────── -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Postman Collection</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Collection Name (optional)</label>
                        <input type="text" class="form-control" id="uploadName" placeholder="My API v2">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Postman Collection JSON</label>
                        <input type="file" class="form-control" id="uploadFile" accept=".json">
                        <div class="form-text" style="color:var(--muted);font-size:.73rem;margin-top:5px;">
                            Export from Postman → Collection → ⋯ → Export → Collection v2.1
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary btn-sm" onclick="submitUpload()">
                        <i class="bi bi-upload me-1"></i>Import & Parse
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ENDPOINT MODAL (Add / Edit) ───────────────────────────────────────── -->
    <div class="modal fade" id="endpointModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="endpointModalTitle"><i class="bi bi-plus-circle me-2"></i>New
                        Endpoint</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="epEditId">

                    <div class="mb-3">
                        <label class="form-label">Endpoint Name *</label>
                        <input type="text" class="form-control" id="epName" placeholder="Get user profile">
                    </div>

                    <div class="row mb-3">
                        <div class="col-5">
                            <label class="form-label">Method *</label>
                            <div class="method-select-row" id="methodChips">
                                <span class="method-chip selected" data-method="GET" style="background:#28a745"
                                    onclick="selectMethod(this)">GET</span>
                                <span class="method-chip" data-method="POST" onclick="selectMethod(this)">POST</span>
                                <span class="method-chip" data-method="PUT" onclick="selectMethod(this)">PUT</span>
                                <span class="method-chip" data-method="PATCH"
                                    onclick="selectMethod(this)">PATCH</span>
                                <span class="method-chip" data-method="DELETE"
                                    onclick="selectMethod(this)">DELETE</span>
                            </div>
                        </div>
                        <div class="col-7">
                            <label class="form-label">Group / Folder</label>
                            <input type="text" class="form-control" id="epGroup" placeholder="Authentication">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">URL *</label>
                        <input type="text" class="form-control" id="epUrl"
                            placeholder="{base_url}/api/users/:id">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Auth Type</label>
                        <select class="form-select" id="epAuth">
                            <option value="noauth">No Auth</option>
                            <option value="bearer">Bearer Token</option>
                            <option value="basic">Basic Auth</option>
                            <option value="apikey">API Key</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="epDesc" rows="3" placeholder="What does this endpoint do?"></textarea>
                    </div>

                    <!-- Query Params -->
                    <div class="mb-3">
                        <label class="form-label">Query Parameters</label>
                        <div class="dynamic-params" id="queryParams"></div>
                        <button class="add-param-btn mt-2" onclick="addParam('queryParams')">
                            <i class="bi bi-plus"></i> Add Query Param
                        </button>
                    </div>

                    <!-- Body (Raw JSON) -->
                    <div class="mb-3">
                        <label class="form-label">Request Body <span style="color:var(--muted);font-size:.75rem;">(raw
                                JSON)</span></label>
                        <textarea class="form-control" id="epBody" rows="5" placeholder='{"key": "value"}'></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary btn-sm" onclick="saveEndpoint()">
                        <i class="bi bi-check2 me-1"></i>Save Endpoint
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── MODEL MODAL ────────────────────────────────────────────────────────── -->
    <div class="modal fade" id="modelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cpu me-2" style="color:var(--ai-color)"></i>Ollama Model
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-size:.82rem;color:var(--muted);">Select which local Ollama model to use for AI
                        documentation generation.</p>
                    <div id="modelList" style="display:flex;flex-direction:column;gap:6px;"></div>
                    <div style="margin-top:14px;font-size:.75rem;color:var(--muted);">
                        <i class="bi bi-info-circle me-1"></i>
                        To add models run: <code style="color:var(--ai-color);">ollama pull llama3</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TOAST STACK ────────────────────────────────────────────────────────── -->
    <div class="toast-stack" id="toastStack"></div>

    <!-- Back to Top -->
    <button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        <i class="bi bi-chevron-up"></i>
    </button>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        /* ════════════════════════════════════════════════════════════════════════════
       API DOCS — Main Application Script
       Connects to Laravel backend at /api/*  and Ollama via the backend proxy.
    ════════════════════════════════════════════════════════════════════════════ */

        const API = '/api';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        // ── State ────────────────────────────────────────────────────────────────────
        let state = {
            collections: [],
            activeId: null,
            activeData: null, // full collection object
            ollamaOnline: false,
            ollamaModel: 'llama3',
            availableModels: [],
            generatingIds: new Set(), // endpoint IDs currently being generated
        };

        // ── Method colors ─────────────────────────────────────────────────────────────
        const METHOD_COLORS = {
            GET: '#28a745',
            POST: '#007bff',
            PUT: '#fd7e14',
            PATCH: '#6f42c1',
            DELETE: '#dc3545',
            HEAD: '#6c757d',
            OPTIONS: '#20c997',
        };

        // ════════════════════════════════════════════════════════════════════════════
        // INIT
        // ════════════════════════════════════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', async () => {
            await checkOllama();
            await loadCollections();
            bindSearch();
            bindScroll();
            setInterval(checkOllama, 30_000);
        });

        // ════════════════════════════════════════════════════════════════════════════
        // OLLAMA STATUS
        // ════════════════════════════════════════════════════════════════════════════
        async function checkOllama() {
            try {
                const res = await apiFetch('/ai/status');
                state.ollamaOnline = res.online;
                state.ollamaModel = res.default_model ?? state.ollamaModel;
                document.getElementById('statusDot').className = 'status-dot ' + (res.online ? 'online' : 'offline');
                document.getElementById('statusLabel').textContent = res.online ? 'Ollama' : 'Offline';
                document.getElementById('modelBadge').textContent = state.ollamaModel;
                if (res.online) loadModels();
            } catch {
                document.getElementById('statusDot').className = 'status-dot offline';
                document.getElementById('statusLabel').textContent = 'Offline';
            }
        }

        async function loadModels() {
            try {
                const res = await apiFetch('/ai/models');
                state.availableModels = res.data ?? [];
            } catch {}
        }

        function openModelModal() {
            const list = document.getElementById('modelList');
            list.innerHTML = '';
            if (!state.availableModels.length) {
                list.innerHTML =
                    '<p style="color:var(--muted);font-size:.82rem;">No models found. Make sure Ollama is running and you have pulled a model.</p>';
            } else {
                state.availableModels.forEach(m => {
                    const row = document.createElement('div');
                    row.style.cssText =
                        'display:flex;align-items:center;gap:10px;background:var(--bg3);border:1px solid var(--border);border-radius:6px;padding:9px 14px;cursor:pointer;transition:all .15s;';
                    if (m.name === state.ollamaModel) row.style.borderColor = 'var(--ai-color)';
                    row.innerHTML = `
        <i class="bi bi-cpu" style="color:var(--ai-color)"></i>
        <div style="flex:1">
          <div style="font-size:.83rem;font-weight:600;">${esc(m.name)}</div>
          <div style="font-size:.7rem;color:var(--muted);">${m.parameters ?? ''} ${m.family ?? ''}</div>
        </div>
        ${m.name === state.ollamaModel ? '<span style="color:var(--green);font-size:.75rem;"><i class="bi bi-check2-circle"></i> Active</span>' : ''}
      `;
                    row.onclick = () => {
                        state.ollamaModel = m.name;
                        document.getElementById('modelBadge').textContent = m.name;
                        bootstrap.Modal.getInstance(document.getElementById('modelModal'))?.hide();
                        toast(`Model switched to ${m.name}`, 'ai');
                    };
                    list.appendChild(row);
                });
            }
            new bootstrap.Modal(document.getElementById('modelModal')).show();
        }

        // ════════════════════════════════════════════════════════════════════════════
        // COLLECTIONS
        // ════════════════════════════════════════════════════════════════════════════
        async function loadCollections() {
            try {
                const res = await apiFetch('/collections');
                state.collections = res.data ?? [];
                renderCollectionSelect();
                if (state.collections.length) {
                    await loadCollection(state.collections[0].id);
                }
            } catch (e) {
                toast('Could not load collections — is the Laravel server running?', 'error');
            }
        }

        function renderCollectionSelect() {
            const sel = document.getElementById('collectionSelect');
            sel.innerHTML = '<option value="">— Select Collection —</option>';
            state.collections.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name + ` (${c.total})`;
                if (c.id === state.activeId) opt.selected = true;
                sel.appendChild(opt);
            });
            sel.onchange = () => {
                if (sel.value) loadCollection(sel.value);
            };
        }

        async function loadCollection(id) {
            try {
                const res = await apiFetch(`/collections/${id}`);
                state.activeId = id;
                state.activeData = res.data;
                renderCollectionView();
                document.getElementById('uploadZone').style.display = 'none';
                document.getElementById('collectionView').style.display = 'block';
                renderCollectionSelect();
            } catch (e) {
                toast('Failed to load collection.', 'error');
            }
        }

        // ════════════════════════════════════════════════════════════════════════════
        // RENDER COLLECTION
        // ════════════════════════════════════════════════════════════════════════════
        function renderCollectionView() {
            const col = state.activeData;
            if (!col) return;

            // Hero
            document.getElementById('heroTitle').textContent = col.name;
            document.getElementById('heroDesc').textContent = col.description || `${col.endpoints?.length ?? 0} endpoints`;

            // Stats
            const methods = {};
            (col.endpoints ?? []).forEach(e => {
                methods[e.method] = (methods[e.method] || 0) + 1;
            });
            const aiCount = (col.endpoints ?? []).filter(e => e.ai_summary).length;
            const total = (col.endpoints ?? []).length;

            let statsHtml =
                `<div class="total-pill"><span class="stat-count">${total}</span><span class="stat-label">Total</span></div>`;
            Object.entries(methods).sort().forEach(([m, c]) => {
                const col = METHOD_COLORS[m] ?? '#6c757d';
                statsHtml +=
                    `<div class="stat-pill" style="border-color:${col}"><span class="stat-count" style="color:${col}">${c}</span><span class="stat-label">${m}</span></div>`;
            });
            document.getElementById('heroStats').innerHTML = statsHtml;

            // AI progress
            const pct = total ? Math.round((aiCount / total) * 100) : 0;
            document.getElementById('batchBar').style.width = pct + '%';
            document.getElementById('batchStatusText').textContent = `${aiCount}/${total} documented`;

            // Sidebar & groups
            renderGroups(col.endpoints ?? []);
        }

        function renderGroups(endpoints) {
            // Group by 'group' field
            const groups = {};
            endpoints.forEach(ep => {
                const g = ep.group || 'General';
                groups[g] = groups[g] || [];
                groups[g].push(ep);
            });

            // Sidebar
            const nav = document.getElementById('sidebarNav');
            nav.innerHTML = '';
            Object.entries(groups).forEach(([grp, eps]) => {
                const id = 'grp-' + slugify(grp);
                const li = document.createElement('li');
                li.className = 'group-nav-item';
                li.innerHTML = `<a class="group-nav-link" href="#${id}">
      <i class="bi bi-folder2" style="font-size:.8rem;opacity:.6"></i>
      ${esc(grp)}
      <span class="grp-count">${eps.length}</span>
    </a>`;
                li.querySelector('a').addEventListener('click', e => {
                    e.preventDefault();
                    document.getElementById(id)?.scrollIntoView({
                        behavior: 'smooth'
                    });
                });
                nav.appendChild(li);
            });

            // Groups
            const container = document.getElementById('endpointGroups');
            container.innerHTML = '';
            Object.entries(groups).forEach(([grp, eps]) => {
                const id = 'grp-' + slugify(grp);
                const section = document.createElement('section');
                section.className = 'api-group';
                section.id = id;

                const aiDone = eps.filter(e => e.ai_summary).length;
                section.innerHTML = `
      <div class="group-header">
        <h2>${esc(grp)}</h2>
        <span class="group-count">${eps.length} endpoint${eps.length!==1?'s':''}</span>
        <button class="btn-group-ai" onclick="batchSummarizeGroup('${esc(grp)}')">
          <i class="bi bi-stars"></i> AI Docs ${aiDone}/${eps.length}
        </button>
      </div>
      <div class="endpoints-list"></div>`;

                const list = section.querySelector('.endpoints-list');
                eps.forEach(ep => list.appendChild(buildEndpointCard(ep)));
                container.appendChild(section);
            });
        }

        // ════════════════════════════════════════════════════════════════════════════
        // ENDPOINT CARD
        // ════════════════════════════════════════════════════════════════════════════
        function buildEndpointCard(ep) {
            const card = document.createElement('div');
            const color = METHOD_COLORS[ep.method] ?? '#6c757d';
            const hasAI = !!ep.ai_summary;

            card.className = 'endpoint-card' + (hasAI ? ' has-summary' : '');
            card.id = 'ep-' + ep.id;
            card.dataset.epId = ep.id;

            const authBadge = ep.auth?.type && ep.auth.type !== 'noauth' ?
                `<span class="auth-badge"><i class="bi bi-shield-lock me-1"></i>${esc(ep.auth.type)}</span>` :
                `<span class="auth-none"><i class="bi bi-unlock me-1"></i>No Auth</span>`;

            const aiBadge = hasAI ?
                `<span class="ai-summary-badge"><i class="bi bi-stars"></i>AI Docs</span>` :
                '';

            card.innerHTML = `
    <div class="endpoint-header" onclick="toggleCard(this)">
      <span class="method-badge" style="background:${color}">${esc(ep.method)}</span>
      <div style="flex:1;min-width:0;">
        <div class="endpoint-name">${esc(ep.name)}</div>
        <div class="endpoint-url"><code>${esc(ep.url)}</code></div>
        <div class="endpoint-meta">${authBadge}${aiBadge}</div>
      </div>
      <div style="display:flex;gap:6px;align-items:center;flex-shrink:0;">
        <button class="ai-panel-btn" onclick="event.stopPropagation();editEndpoint('${ep.id}')" title="Edit">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="ai-panel-btn" style="color:var(--accent2);border-color:rgba(247,129,102,.3);"
          onclick="event.stopPropagation();deleteEndpoint('${ep.id}')" title="Delete">
          <i class="bi bi-trash3"></i>
        </button>
        <i class="bi bi-chevron-down collapse-chevron"></i>
      </div>
    </div>
    <div class="endpoint-body" id="body-${ep.id}">
      ${buildEndpointBody(ep)}
    </div>`;

            return card;
        }

        function buildEndpointBody(ep) {
            let html = '';

            // AI Summary panel
            html += buildAIPanel(ep);

            // Description
            if (ep.description) {
                html += `<div class="desc-block">${mdLite(esc(ep.description))}</div>`;
            }

            // Query params
            if (ep.query?.length) {
                html +=
                    `<div class="section-heading"><i class="bi bi-question-circle"></i> Query Parameters</div>
    <div class="table-responsive">
    <table class="table table-sm param-table"><thead><tr><th>Key</th><th>Value</th><th>Description</th></tr></thead><tbody>`;
                ep.query.forEach(q => {
                    if (!q.disabled) html +=
                        `<tr><td><code>${esc(q.key)}</code></td><td>${esc(q.value)}</td><td>${esc(q.description)}</td></tr>`;
                });
                html += `</tbody></table></div>`;
            }

            // Path vars
            if (ep.path_vars?.length) {
                html +=
                    `<div class="section-heading"><i class="bi bi-braces"></i> Path Variables</div>
    <div class="table-responsive">
    <table class="table table-sm param-table"><thead><tr><th>Variable</th><th>Example</th><th>Description</th></tr></thead><tbody>`;
                ep.path_vars.forEach(p => {
                    html +=
                        `<tr><td><code>:${esc(p.key)}</code></td><td>${esc(p.value)}</td><td>${esc(p.description)}</td></tr>`;
                });
                html += `</tbody></table></div>`;
            }

            // Body
            const body = ep.body ?? {};
            const mode = body.mode ?? '';
            if (mode) {
                html +=
                    `<div class="section-heading"><i class="bi bi-send"></i> Request Body <span style="font-size:.7rem;opacity:.6;">(${mode})</span></div>`;
                if (mode === 'raw' && body.raw) {
                    html += `<pre class="raw-body">${esc(body.raw)}</pre>`;
                } else {
                    const params = body[mode] ?? body.urlencoded ?? body.formdata ?? [];
                    if (params.length) {
                        html += `<div class="table-responsive"><table class="table table-sm param-table">
          <thead><tr><th>Key</th><th>Type</th><th>Value</th><th>Description</th></tr></thead><tbody>`;
                        params.forEach(p => {
                            if (!p.disabled)
                                html +=
                                `<tr><td><code>${esc(p.key)}</code></td><td>${esc(p.type??'text')}</td><td>${esc(p.value)}</td><td>${esc(p.description)}</td></tr>`;
                        });
                        html += `</tbody></table></div>`;
                    }
                }
            }

            // Responses
            if (ep.responses?.length) {
                html += `<div class="section-heading"><i class="bi bi-arrow-return-left"></i> Example Responses</div>`;
                ep.responses.forEach(r => {
                    const code = parseInt(r.code ?? 0);
                    const cls = code >= 500 ? 'danger' : code >= 400 ? 'warning' : 'success';
                    let body = r.body ?? '';
                    try {
                        body = JSON.stringify(JSON.parse(body), null, 2);
                    } catch {}
                    html += `<div class="resp-block">
        <div class="resp-meta">
          <span class="badge bg-${cls}">${r.code}</span>
          <span style="color:var(--muted)">${esc(r.status)}</span>
          <span style="color:var(--muted);font-size:.72rem;">— ${esc(r.name)}</span>
        </div>
        ${body ? `<pre class="resp-body">${esc(body)}</pre>` : '<p style="color:var(--muted);font-size:.78rem;margin:0;">No response body.</p>'}
      </div>`;
                });
            }

            return html;
        }

        function buildAIPanel(ep) {
            const hasAI = !!ep.ai_summary;
            if (!hasAI) {
                return `<button class="ai-generate-btn" id="aibtn-${ep.id}"
      onclick="generateSummary('${ep.id}')">
      <i class="bi bi-stars"></i>
      Generate AI Documentation
    </button>`;
            }
            return `<div class="ai-panel" id="aipanel-${ep.id}">
    <div class="ai-panel-header">
      <div class="ai-panel-title"><i class="bi bi-stars"></i> AI Documentation</div>
      <div class="ai-panel-actions">
        <button class="ai-panel-btn" id="aibtn-${ep.id}" onclick="generateSummary('${ep.id}')">
          <i class="bi bi-arrow-clockwise"></i> Regenerate
        </button>
      </div>
    </div>
    <div class="ai-summary-text" id="aitext-${ep.id}">${renderAISummary(ep.ai_summary)}</div>
  </div>`;
        }

        function renderAISummary(text) {
            if (!text) return '';
            return text
                .split('\n')
                .map(line => {
                    // Bold numbered headings like "1. **Overview**"
                    line = line.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    return line;
                })
                .join('<br>');
        }

        // ════════════════════════════════════════════════════════════════════════════
        // TOGGLE CARD
        // ════════════════════════════════════════════════════════════════════════════
        function toggleCard(header) {
            header.classList.toggle('open');
            const body = header.nextElementSibling;
            body.classList.toggle('open');
        }

        // ════════════════════════════════════════════════════════════════════════════
        // AI SUMMARY — Single Endpoint (SSE streaming)
        // ════════════════════════════════════════════════════════════════════════════
        async function generateSummary(epId) {
            if (!state.ollamaOnline) {
                toast('Ollama is offline. Start it with: ollama serve', 'error');
                return;
            }
            if (state.generatingIds.has(epId)) return;

            const ep = findEndpoint(epId);
            if (!ep) return;

            state.generatingIds.add(epId);

            // Show thinking state in panel
            let panel = document.getElementById('aipanel-' + epId);
            const btn = document.getElementById('aibtn-' + epId);

            if (!panel) {
                // Replace the generate button with a panel
                const generateBtn = document.getElementById('aibtn-' + epId);
                if (generateBtn) {
                    const newPanel = document.createElement('div');
                    newPanel.className = 'ai-panel';
                    newPanel.id = 'aipanel-' + epId;
                    newPanel.innerHTML = `
        <div class="ai-panel-header">
          <div class="ai-panel-title"><i class="bi bi-stars"></i> AI Documentation</div>
          <div class="ai-panel-actions">
            <button class="ai-panel-btn" id="aibtn-${epId}" disabled><i class="bi bi-hourglass-split"></i> Generating…</button>
          </div>
        </div>
        <div class="ai-thinking"><i class="bi bi-stars" style="color:var(--ai-color)"></i> Generating… <div class="dots"><span>•</span><span>•</span><span>•</span></div></div>
        <div class="ai-summary-text" id="aitext-${epId}" style="margin-top:8px;"></div>`;
                    generateBtn.replaceWith(newPanel);
                    panel = newPanel;
                }
            } else {
                const textEl = document.getElementById('aitext-' + epId);
                if (textEl) textEl.innerHTML =
                    `<div class="ai-thinking"><i class="bi bi-stars"></i> Generating…<div class="dots"><span>•</span><span>•</span><span>•</span></div></div>`;
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating…';
                }
            }

            // Mark card as having AI
            const card = document.getElementById('ep-' + epId);
            if (card) card.classList.add('has-summary');

            let accumulated = '';

            try {
                const response = await fetch(`${API}/ai/summarize`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'text/event-stream'
                    },
                    body: JSON.stringify({
                        endpoint: ep,
                        model: state.ollamaModel,
                        collection_id: state.activeId,
                        endpoint_id: ep.id,
                    }),
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                const textEl = document.getElementById('aitext-' + epId);

                // Remove thinking dots now that stream starts
                if (textEl) textEl.innerHTML = '';

                while (true) {
                    const {
                        done,
                        value
                    } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value, {
                        stream: true
                    });
                    for (const line of chunk.split('\n')) {
                        if (!line.startsWith('data: ')) continue;
                        try {
                            const data = JSON.parse(line.slice(6));
                            if (data.token) {
                                accumulated += data.token;
                                if (textEl) textEl.innerHTML = renderAISummary(accumulated);
                            }
                            if (data.done) {
                                // Update local state
                                ep.ai_summary = data.summary || accumulated;
                                updateEndpointBadge(epId, true);
                                updateBatchProgress();
                            }
                        } catch {}
                    }
                }

                toast(`AI docs generated for "${ep.name}"`, 'ai');
            } catch (err) {
                toast('AI generation failed: ' + err.message, 'error');
            } finally {
                state.generatingIds.delete(epId);
                const b = document.getElementById('aibtn-' + epId);
                if (b) {
                    b.disabled = false;
                    b.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Regenerate';
                }
            }
        }

        // ════════════════════════════════════════════════════════════════════════════
        // AI SUMMARY — Batch (SSE streaming with progress)
        // ════════════════════════════════════════════════════════════════════════════
        async function batchSummarizeAll() {
            if (!state.activeId) {
                toast('No collection loaded.', 'error');
                return;
            }
            if (!state.ollamaOnline) {
                toast('Ollama is offline.', 'error');
                return;
            }
            batchSummarize(null);
        }

        async function batchSummarizeGroup(group) {
            if (!state.activeId) {
                toast('No collection loaded.', 'error');
                return;
            }
            if (!state.ollamaOnline) {
                toast('Ollama is offline.', 'error');
                return;
            }
            batchSummarize(group);
        }

        async function batchSummarize(groupFilter) {
            const statusText = document.getElementById('batchStatusText');
            const bar = document.getElementById('batchBar');

            try {
                const response = await fetch(`${API}/ai/summarize-collection`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        collection_id: state.activeId,
                        model: state.ollamaModel,
                        force: false,
                    }),
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let total = 0;
                let processed = 0;

                while (true) {
                    const {
                        done,
                        value
                    } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value, {
                        stream: true
                    });
                    for (const line of chunk.split('\n')) {
                        if (!line.startsWith('data: ')) continue;
                        try {
                            const data = JSON.parse(line.slice(6));

                            if (data.type === 'start') {
                                total = data.total;
                                statusText.textContent = `0/${total} done`;
                                toast(`Starting AI docs for ${total} endpoints…`, 'ai');
                            }

                            if (data.type === 'processing') {
                                statusText.textContent = `Processing: ${data.name}`;
                            }

                            if (data.type === 'done' || data.type === 'skip') {
                                processed++;
                                const ep = findEndpoint(data.id);
                                if (ep && data.summary) {
                                    ep.ai_summary = data.summary;
                                    // Update the card in-place
                                    const textEl = document.getElementById('aitext-' + data.id);
                                    if (textEl) textEl.innerHTML = renderAISummary(data.summary);
                                    const panelEl = document.getElementById('aipanel-' + data.id);
                                    if (!panelEl) {
                                        const btn = document.getElementById('aibtn-' + data.id);
                                        if (btn) {
                                            const panel = document.createElement('div');
                                            panel.className = 'ai-panel';
                                            panel.id = 'aipanel-' + data.id;
                                            panel.innerHTML = `
                    <div class="ai-panel-header">
                      <div class="ai-panel-title"><i class="bi bi-stars"></i> AI Documentation</div>
                      <div class="ai-panel-actions">
                        <button class="ai-panel-btn" onclick="generateSummary('${data.id}')"><i class="bi bi-arrow-clockwise"></i> Regenerate</button>
                      </div>
                    </div>
                    <div class="ai-summary-text" id="aitext-${data.id}">${renderAISummary(data.summary)}</div>`;
                                            btn.replaceWith(panel);
                                        }
                                    }
                                    updateEndpointBadge(data.id, true);
                                }
                                const pct = total ? Math.round((processed / total) * 100) : 0;
                                bar.style.width = pct + '%';
                                statusText.textContent = `${processed}/${total} documented`;
                            }

                            if (data.type === 'complete') {
                                toast(`AI docs complete! ${data.total} endpoints documented.`, 'ai');
                                // Reload full collection to sync state
                                await loadCollection(state.activeId);
                            }
                        } catch {}
                    }
                }
            } catch (err) {
                toast('Batch generation failed: ' + err.message, 'error');
            }
        }

        function updateEndpointBadge(epId, hasAI) {
            const card = document.getElementById('ep-' + epId);
            if (!card) return;
            if (hasAI) {
                card.classList.add('has-summary');
                const meta = card.querySelector('.endpoint-meta');
                if (meta && !meta.querySelector('.ai-summary-badge')) {
                    const badge = document.createElement('span');
                    badge.className = 'ai-summary-badge';
                    badge.innerHTML = '<i class="bi bi-stars"></i>AI Docs';
                    meta.appendChild(badge);
                }
            }
        }

        function updateBatchProgress() {
            const eps = state.activeData?.endpoints ?? [];
            const aiDone = eps.filter(e => e.ai_summary).length;
            const total = eps.length;
            const pct = total ? Math.round((aiDone / total) * 100) : 0;
            document.getElementById('batchBar').style.width = pct + '%';
            document.getElementById('batchStatusText').textContent = `${aiDone}/${total} documented`;
        }

        // ════════════════════════════════════════════════════════════════════════════
        // UPLOAD
        // ════════════════════════════════════════════════════════════════════════════
        function openUploadModal() {
            new bootstrap.Modal(document.getElementById('uploadModal')).show();
        }

        function handleDragOver(e) {
            e.preventDefault();
            document.getElementById('uploadZone').classList.add('drag-over');
        }

        function handleDrop(e) {
            e.preventDefault();
            document.getElementById('uploadZone').classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file) {
                document.getElementById('uploadFile').files = e.dataTransfer.files;
                openUploadModal();
            }
        }

        async function submitUpload() {
            const fileInput = document.getElementById('uploadFile');
            const name = document.getElementById('uploadName').value;

            if (!fileInput.files.length) {
                toast('Please choose a JSON file.', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            if (name) formData.append('name', name);

            try {
                const res = await fetch(`${API}/collections/upload`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf
                    },
                    body: formData,
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error ?? 'Upload failed');

                bootstrap.Modal.getInstance(document.getElementById('uploadModal'))?.hide();
                toast(`Imported "${data.data.name}" — ${data.data.total} endpoints`, 'success');
                state.collections.push({
                    id: data.data.id,
                    name: data.data.name,
                    total: data.data.total
                });
                await loadCollection(data.data.id);
            } catch (err) {
                toast('Import failed: ' + err.message, 'error');
            }
        }

        // ════════════════════════════════════════════════════════════════════════════
        // ENDPOINT CRUD
        // ════════════════════════════════════════════════════════════════════════════
        function openEndpointModal(ep = null) {
            document.getElementById('endpointModalTitle').innerHTML =
                ep ? '<i class="bi bi-pencil me-2"></i>Edit Endpoint' :
                '<i class="bi bi-plus-circle me-2"></i>New Endpoint';
            document.getElementById('epEditId').value = ep?.id ?? '';
            document.getElementById('epName').value = ep?.name ?? '';
            document.getElementById('epGroup').value = ep?.group ?? '';
            document.getElementById('epUrl').value = ep?.url ?? '';
            document.getElementById('epDesc').value = ep?.description ?? '';
            document.getElementById('epAuth').value = ep?.auth?.type ?? 'noauth';
            document.getElementById('epBody').value = ep?.body?.raw ?? '';

            // Method chips
            document.querySelectorAll('.method-chip').forEach(c => {
                const m = c.dataset.method;
                c.classList.toggle('selected', m === (ep?.method ?? 'GET'));
                c.style.background = m === (ep?.method ?? 'GET') ? METHOD_COLORS[m] : '';
                c.style.color = m === (ep?.method ?? 'GET') ? '#fff' : '';
                c.style.borderColor = m === (ep?.method ?? 'GET') ? METHOD_COLORS[m] : '';
            });

            // Query params
            const qContainer = document.getElementById('queryParams');
            qContainer.innerHTML = '';
            (ep?.query ?? []).forEach(q => addParam('queryParams', q.key, q.value));

            new bootstrap.Modal(document.getElementById('endpointModal')).show();
        }

        function selectMethod(chip) {
            document.querySelectorAll('.method-chip').forEach(c => {
                c.classList.remove('selected');
                c.style.background = '';
                c.style.color = '';
                c.style.borderColor = '';
            });
            chip.classList.add('selected');
            chip.style.background = METHOD_COLORS[chip.dataset.method] ?? '#6c757d';
            chip.style.color = '#fff';
            chip.style.borderColor = METHOD_COLORS[chip.dataset.method] ?? '#6c757d';
        }

        function addParam(containerId, key = '', value = '') {
            const container = document.getElementById(containerId);
            const row = document.createElement('div');
            row.className = 'param-row';
            row.innerHTML = `
    <input type="text" class="form-control param-key" placeholder="key" value="${esc(key)}">
    <input type="text" class="form-control param-val" placeholder="value" value="${esc(value)}">
    <button class="btn-remove" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>`;
            container.appendChild(row);
        }

        async function saveEndpoint() {
            const editId = document.getElementById('epEditId').value;
            const method = document.querySelector('.method-chip.selected')?.dataset.method ?? 'GET';
            const name = document.getElementById('epName').value.trim();
            const url = document.getElementById('epUrl').value.trim();

            if (!name || !url) {
                toast('Name and URL are required.', 'error');
                return;
            }
            if (!state.activeId) {
                toast('Load a collection first.', 'error');
                return;
            }

            const query = [...document.querySelectorAll('#queryParams .param-row')].map(row => ({
                key: row.querySelector('.param-key').value,
                value: row.querySelector('.param-val').value,
                disabled: false,
                description: '',
            })).filter(p => p.key);

            const rawBody = document.getElementById('epBody').value.trim();
            const body = rawBody ? {
                mode: 'raw',
                raw: rawBody
            } : {};

            const payload = {
                name,
                method,
                url,
                group: document.getElementById('epGroup').value.trim() || 'General',
                description: document.getElementById('epDesc').value.trim(),
                auth: {
                    type: document.getElementById('epAuth').value
                },
                query,
                body,
            };

            try {
                let res, data;
                if (editId) {
                    res = await fetch(`${API}/collections/${state.activeId}/endpoints/${editId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify(payload),
                    });
                    data = await res.json();
                    if (!res.ok) throw new Error(data.message ?? 'Update failed');
                    toast(`Updated "${name}"`, 'success');
                } else {
                    res = await fetch(`${API}/collections/${state.activeId}/endpoints`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify(payload),
                    });
                    data = await res.json();
                    if (!res.ok) throw new Error(data.message ?? 'Create failed');
                    toast(`Created "${name}"`, 'success');
                }

                bootstrap.Modal.getInstance(document.getElementById('endpointModal'))?.hide();
                await loadCollection(state.activeId);
            } catch (err) {
                toast('Save failed: ' + err.message, 'error');
            }
        }

        function editEndpoint(epId) {
            const ep = findEndpoint(epId);
            if (ep) openEndpointModal(ep);
        }

        async function deleteEndpoint(epId) {
            if (!confirm('Delete this endpoint?')) return;
            try {
                const res = await fetch(`${API}/collections/${state.activeId}/endpoints/${epId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf
                    },
                });
                if (!res.ok) throw new Error('Delete failed');
                toast('Endpoint deleted.', 'success');
                document.getElementById('ep-' + epId)?.remove();
                // Remove from local state
                if (state.activeData) {
                    state.activeData.endpoints = state.activeData.endpoints.filter(e => e.id !== epId);
                    updateBatchProgress();
                }
            } catch (err) {
                toast('Delete failed: ' + err.message, 'error');
            }
        }

        // ════════════════════════════════════════════════════════════════════════════
        // SEARCH
        // ════════════════════════════════════════════════════════════════════════════
        function bindSearch() {
            document.getElementById('searchBox').addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                document.querySelectorAll('.endpoint-card').forEach(card => {
                    const text = card.textContent.toLowerCase();
                    card.classList.toggle('search-hidden', !!q && !text.includes(q));
                });
                document.querySelectorAll('.api-group').forEach(grp => {
                    const visible = grp.querySelectorAll('.endpoint-card:not(.search-hidden)').length;
                    grp.style.display = (q && !visible) ? 'none' : '';
                });
            });
        }

        // ════════════════════════════════════════════════════════════════════════════
        // SCROLL / SIDEBAR HIGHLIGHT
        // ════════════════════════════════════════════════════════════════════════════
        function bindScroll() {
            window.addEventListener('scroll', () => {
                const y = window.scrollY;
                document.getElementById('backToTop').style.display = y > 400 ? 'flex' : 'none';

                let cur = '';
                document.querySelectorAll('.api-group').forEach(s => {
                    if (y >= s.offsetTop - 100) cur = s.id;
                });
                document.querySelectorAll('.group-nav-link').forEach(l => {
                    l.classList.toggle('active', l.getAttribute('href') === '#' + cur);
                });
            });
        }

        // ════════════════════════════════════════════════════════════════════════════
        // UTILITIES
        // ════════════════════════════════════════════════════════════════════════════
        async function apiFetch(path, opts = {}) {
            const res = await fetch(API + path, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    ...opts.headers
                },
                ...opts,
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message ?? data.error ?? `HTTP ${res.status}`);
            return data;
        }

        function findEndpoint(id) {
            return (state.activeData?.endpoints ?? []).find(e => e.id === id);
        }

        function toast(msg, type = 'success') {
            const icons = {
                success: 'bi-check2-circle',
                error: 'bi-exclamation-triangle',
                ai: 'bi-stars'
            };
            const colors = {
                success: 'var(--green)',
                error: 'var(--accent2)',
                ai: 'var(--ai-color)'
            };
            const el = document.createElement('div');
            el.className = `app-toast ${type}`;
            el.innerHTML =
                `<i class="bi ${icons[type]||'bi-info-circle'}" style="color:${colors[type]||'var(--accent)'}"></i>${esc(msg)}`;
            document.getElementById('toastStack').appendChild(el);
            setTimeout(() => el.remove(), 4000);
        }

        function esc(s) {
            return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
                '&quot;');
        }

        function slugify(s) {
            return String(s).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        }

        function mdLite(s) {
            return s
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/`(.*?)`/g, '<code>$1</code>')
                .replace(/\n/g, '<br>');
        }
    </script>
</body>

</html>
