/* ============================================================
     STATE
  ============================================================ */
const API = '/api';
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const MC = {
    GET: '#28a745',
    POST: '#007bff',
    PUT: '#fd7e14',
    PATCH: '#6f42c1',
    DELETE: '#dc3545',
    HEAD: '#6c757d',
    OPTIONS: '#20c997'
};
const ALLPERMS = ['read', 'write', 'run', 'ai', 'manage_users'];
const S = {
    user: null,
    collections: [],
    activeId: null,
    activeData: null,
    ollamaOnline: false,
    ollamaModel: 'llama3',
    availableModels: [],
    generatingIds: new Set(),
    baseUrl: localStorage.getItem('apidocs_base_url') ?? '',
    globalBearer: localStorage.getItem('apidocs_bearer') ?? '',
    cloudProvider: localStorage.getItem('apidocs_cprov') ?? null,
    cloudModel: localStorage.getItem('apidocs_cmodel') ?? null,
    cloudLabel: localStorage.getItem('apidocs_clabel') ?? null,
    cloudKeys: {
        openai: localStorage.getItem('apidocs_key_openai') ?? '',
        anthropic: localStorage.getItem('apidocs_key_anthropic') ?? '',
        google: localStorage.getItem('apidocs_key_google') ?? '',
        groq: localStorage.getItem('apidocs_key_groq') ?? '',
    },
    lastResp: null,
    theme: localStorage.getItem('apidocs_theme') ?? 'dark',
};

/* ============================================================
   INIT
============================================================ */
document.addEventListener('DOMContentLoaded', async () => {
    ['loginEmail', 'loginPassword'].forEach(id => {
        document.getElementById(id)?.addEventListener('keydown', e => {
            if (e.key === 'Enter') doLogin();
        });
    });
    document.getElementById('baseUrlInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') saveBaseUrl();
    });
    document.getElementById('globalBearer').addEventListener('keydown', e => {
        if (e.key === 'Enter') saveGlobalBearer();
    });
    if (S.baseUrl) document.getElementById('baseUrlInput').value = S.baseUrl;
    if (S.globalBearer) document.getElementById('globalBearer').value = S.globalBearer;
    initTheme();
    // emoji picker build + global click handler to close it when clicking outside
    buildEmojiPicker();
    document.addEventListener('click', (ev) => {
        const p = document.getElementById('chatEmojiPicker');
        const btn = document.getElementById('chatEmojiBtn');
        if (!p) return;
        if (p.style.display === 'grid' && !p.contains(ev.target) && ev.target !== btn) {
            p.style.display = 'none';
        }
    });
    document.getElementById('chatEmojiBtn')?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleEmojiPicker();
    });
    // audio init + mute button
    initChatAudio();
    document.getElementById('chatMuteBtn')?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleChatMute();
    });
    try {
        const r = await fetch(`${API}/auth/me`, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            }
        });
        if (r.ok) {
            const d = await r.json();
            await bootApp(d.user);
        } else showLogin();
    } catch {
        showLogin();
    }
});

/* ============================================================
   THEME
============================================================ */
function applyTheme(theme) {
    const actual = theme === 'auto'
        ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
        : theme;
    document.body.setAttribute('data-theme', actual);
    const btn = document.getElementById('themeToggleBtn');
    if (btn) {
        btn.innerHTML = actual === 'dark'
            ? '<i class="bi bi-brightness-high-fill"></i>'
            : '<i class="bi bi-moon-stars-fill"></i>';
        btn.title = actual === 'dark' ? 'Switch to day mode' : 'Switch to night mode';
    }
}

function initTheme() {
    const theme = S.theme ?? 'dark';
    applyTheme(theme);
}

function toggleTheme() {
    const current = document.body.getAttribute('data-theme') ?? 'dark';
    const next = current === 'dark' ? 'light' : 'dark';
    S.theme = next;
    localStorage.setItem('apidocs_theme', next);
    applyTheme(next);
}

/* ============================================================
   AUTH
============================================================ */
function showLogin() {
    document.getElementById('loginPage').style.display = 'flex';
    document.getElementById('appShell').style.display = 'none';
}

function showApp() {
    document.getElementById('loginPage').style.display = 'none';
    document.getElementById('appShell').style.display = 'block';
}


async function doLogin() {
    const email = document.getElementById('loginEmail').value.trim();
    const pass = document.getElementById('loginPassword').value;
    const btn = document.getElementById('loginBtn');
    const err = document.getElementById('loginError');
    err.style.display = 'none';
    if (!email || !pass) {
        err.textContent = 'Enter your email and password.';
        err.style.display = 'block';
        return;
    }
    btn.disabled = true;
    btn.textContent = 'Signing in…';
    try {
        const r = await fetch(`${API}/auth/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({
                email,
                password: pass
            })
        });
        const d = await r.json();
        if (!r.ok) {
            err.textContent = d.error ?? 'Login failed.';
            err.style.display = 'block';
            return;
        }
        await bootApp(d.user);
    } catch (e) {
        err.textContent = 'Network error. Is the server running?';
        err.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Sign In';
    }
}

async function doLogout() {
    await fetch(`${API}/auth/logout`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf
        }
    });
    S.user = null;
    S.activeId = null;
    S.activeData = null;
    bootstrap.Modal.getInstance(document.getElementById('profileModal'))?.hide();
    showLogin();
}

async function bootApp(user) {
    S.user = user;
    showApp();
    applyRoleUI();
    renderUserPill();
    if (S.baseUrl) document.getElementById('baseUrlInput').value = S.baseUrl;
    if (S.globalBearer) document.getElementById('globalBearer').value = S.globalBearer;
    await checkOllama();
    await loadCollections();
    bindSearch();
    bindScroll();
    initMobileLayout();
    setInterval(checkOllama, 30000);
    syncCloudUI();
    // Start background chat polling for unread badge
    startBgChatPoll();
}

/* ============================================================
   ROLE/PERMISSION UI
============================================================ */
function can(perm) {
    const u = S.user;
    if (!u) return false;
    if (u.role === 'admin') return true;
    return (u.permissions ?? []).includes(perm);
}

function applyRoleUI() {
    const hasWrite = can('write');
    const hasAI = can('ai');
    const hasUsers = can('manage_users');
    const hasRun = can('run');

    // Nav buttons
    document.getElementById('btnImport').style.display = hasWrite ? '' : 'none';
    document.getElementById('btnAddEp').style.display = hasWrite ? '' : 'none';
    document.getElementById('btnAiAll').style.display = hasAI ? '' : 'none';
    document.getElementById('btnUsers').style.display = hasUsers ? '' : 'none';
    document.getElementById('sidebarImportBtn').style.display = hasWrite ? '' : 'none';

    const isViewer = S.user?.role === 'viewer';
    const baseUrlInput = document.getElementById('baseUrlInput');
    const globalBearerInput = document.getElementById('globalBearer');
    const baseUrlSave = document.querySelector('.base-url-pill .nav-pill-save');
    const bearerSave = document.querySelector('.bearer-pill .nav-pill-save');

    if (baseUrlInput) baseUrlInput.disabled = isViewer;
    if (globalBearerInput) globalBearerInput.disabled = isViewer;
    if (baseUrlSave) baseUrlSave.disabled = isViewer;
    if (bearerSave) bearerSave.disabled = isViewer;

    // Hero "Regen All" button — shown only if user can use AI
    const regenAllBtn = document.getElementById('regenAllBtn');
    if (regenAllBtn) regenAllBtn.style.display = hasAI ? '' : 'none';
}

function renderUserPill() {
    const u = S.user;
    if (!u) return;
    const colors = {
        admin: '#dc3545',
        editor: '#007bff',
        viewer: '#6c757d'
    };
    const av = document.getElementById('navAvatar');
    av.textContent = u.name.charAt(0).toUpperCase();
    av.style.background = colors[u.role] ?? '#6c757d';
    document.getElementById('navUserName').textContent = u.name;
    const rc = document.getElementById('navRoleChip');
    rc.textContent = u.role;
    rc.className = 'role-chip role-' + u.role;
}

/* ============================================================
   OLLAMA / AI STATUS
============================================================ */
async function checkOllama() {
    try {
        const r = await apiFetch('/ai/status');
        S.ollamaOnline = r.online;
        if (!S.cloudProvider) S.ollamaModel = r.default_model ?? S.ollamaModel;
        document.getElementById('statusDot').className = 'status-dot ' + (r.online ? 'online' : 'offline');
        document.getElementById('statusLabel').textContent = r.online ? 'Ollama' : 'Offline';
        document.getElementById('modelBadge').textContent = S.cloudLabel ?? S.ollamaModel;
        if (r.online) loadModels();
    } catch {
        document.getElementById('statusDot').className = 'status-dot offline';
        document.getElementById('statusLabel').textContent = 'Offline';
    }
}
async function loadModels() {
    try {
        const r = await apiFetch('/ai/models');
        S.availableModels = r.data ?? [];
    } catch { }
}

/* ============================================================
   MODEL MODAL
============================================================ */
function openModelModal() {
    const ll = document.getElementById('localModelList');
    ll.innerHTML = '';
    if (!S.availableModels.length) {
        ll.innerHTML = '<p style="color:var(--muted);font-size:.77rem">No local models found.</p>';
    } else {
        S.availableModels.forEach(m => {
            const active = !S.cloudProvider && m.name === S.ollamaModel;
            const c = document.createElement('div');
            c.className = 'model-card' + (active ? ' active' : '');
            c.innerHTML = `<div class="model-provider" style="background:var(--bg4);color:var(--ai);font-size:.63rem;font-family:'JetBrains Mono',monospace;">AI</div>
        <div class="model-info"><div class="model-name">${esc(m.name)}</div><div class="model-desc">${m.parameters ?? ''}</div></div>
        ${active ? '<span style="color:var(--green)"><i class="bi bi-check2-circle"></i></span>' : ''}`;
            c.onclick = () => {
                S.cloudProvider = S.cloudModel = S.cloudLabel = null;
                ['apidocs_cprov', 'apidocs_cmodel', 'apidocs_clabel'].forEach(k => localStorage
                    .removeItem(k));
                S.ollamaModel = m.name;
                document.getElementById('modelBadge').textContent = m.name;
                bootstrap.Modal.getInstance(document.getElementById('modelModal'))?.hide();
                syncCloudUI();
                toast('Model: ' + m.name, 'ai');
            };
            ll.appendChild(c);
        });
    }
    syncCloudUI();
    new bootstrap.Modal(document.getElementById('modelModal')).show();
}

function selectCloudModel(p, m, l) {
    ['openai', 'anthropic', 'google', 'groq'].forEach(x => {
        const r = document.getElementById(x + '-key-row');
        if (r) r.style.display = x === p ? 'flex' : 'none';
    });
    document.querySelectorAll('#cloudModelList .model-card').forEach(c => c.classList.remove('active'));
    document.getElementById('card-' + p + '-' + m)?.classList.add('active');
    if (S.cloudKeys[p]) activateCloud(p, m, l);
    else toast('Enter your API key below', 'ai');
}

function saveCloudKey(p) {
    const ids = {
        openai: 'openaiKey',
        anthropic: 'anthropicKey',
        google: 'googleKey',
        groq: 'groqKey'
    };
    const key = document.getElementById(ids[p])?.value.trim();
    if (!key) {
        toast('Enter an API key', 'error');
        return;
    }
    S.cloudKeys[p] = key;
    localStorage.setItem('apidocs_key_' + p, key);
    const ac = [...document.querySelectorAll(`[id^="card-${p}-"]`)].find(c => c.classList.contains('active'));
    if (ac) {
        const mdl = ac.id.replace('card-' + p + '-', '');
        const lbl = ac.querySelector('.model-name')?.textContent ?? p;
        activateCloud(p, mdl, lbl);
    } else toast('Key saved — now select a model above', 'success');
}

function activateCloud(p, m, l) {
    S.cloudProvider = p;
    S.cloudModel = m;
    S.cloudLabel = l;
    localStorage.setItem('apidocs_cprov', p);
    localStorage.setItem('apidocs_cmodel', m);
    localStorage.setItem('apidocs_clabel', l);
    document.getElementById('modelBadge').textContent = l;
    document.getElementById('statusDot').className = 'status-dot online';
    document.getElementById('statusLabel').textContent = p.charAt(0).toUpperCase() + p.slice(1);
    bootstrap.Modal.getInstance(document.getElementById('modelModal'))?.hide();
    syncCloudUI();
    toast('Cloud model: ' + l, 'ai');
}

function syncCloudUI() {
    document.querySelectorAll('[id^="chk-"]').forEach(e => e.style.display = 'none');
    if (S.cloudProvider && S.cloudModel) {
        const c = document.getElementById('chk-' + S.cloudProvider + '-' + S.cloudModel);
        if (c) c.style.display = 'inline';
    }
}

/* ============================================================
   COLLECTIONS — with persistence & per-collection bearer
============================================================ */
async function loadCollections() {
    try {
        const r = await apiFetch('/collections');
        S.collections = r.data ?? [];
        renderCollectionSelect();
        // Restore last active collection, respecting default_collection_id for non-admin
        let targetId = localStorage.getItem('apidocs_active_col');
        if (S.user?.default_collection_id && !can('manage_users')) {
            targetId = S.user.default_collection_id;
        }
        const found = S.collections.find(c => c.id === targetId);
        if (found) await loadCollection(found.id);
        else if (S.collections.length) await loadCollection(S.collections[0].id);
    } catch {
        toast('Cannot connect to server', 'error');
    }
}

function renderCollectionSelect() {
    const sel = document.getElementById('collectionSelect');
    const prev = sel.value;
    sel.innerHTML = '<option value="">— Select —</option>';

    const assignedId = S.user?.default_collection_id;
    const visibleCollections = can('manage_users') || !assignedId
        ? S.collections
        : S.collections.filter(c => c.id === assignedId);

    visibleCollections.forEach(c => {
        const o = document.createElement('option');
        o.value = c.id;
        o.textContent = c.name + ' (' + c.total + ')';
        if (c.id === S.activeId) o.selected = true;
        sel.appendChild(o);
    });

    if (!S.activeId && prev) sel.value = prev;
}

function onCollectionChange() {
    const id = document.getElementById('collectionSelect').value;
    if (id) loadCollection(id);
}

async function loadCollection(id) {
    try {
        const r = await apiFetch('/collections/' + id);
        S.activeId = id;
        S.activeData = r.data;
        localStorage.setItem('apidocs_active_col', id);
        // Restore per-collection bearer token
        const perBearer = localStorage.getItem('apidocs_bearer_' + id);
        if (perBearer) {
            S.globalBearer = perBearer;
            document.getElementById('globalBearer').value = perBearer;
        }
        // Restore per-collection base URL
        const perBase = localStorage.getItem('apidocs_base_url_' + id);
        if (perBase) {
            S.baseUrl = perBase;
            document.getElementById('baseUrlInput').value = perBase;
        }
        renderView();
        renderCollectionSelect();
        document.getElementById('uploadZone').style.display = 'none';
        document.getElementById('collectionView').style.display = 'block';
    } catch {
        toast('Failed to load collection', 'error');
    }
}

/* ============================================================
   BASE URL + BEARER TOKEN — persistent, per-collection
============================================================ */
function saveBaseUrl() {
    if (S.user?.role === 'viewer') {
        toast('Viewer accounts cannot modify the Base URL', 'error');
        return;
    }

    const v = document.getElementById('baseUrlInput').value.trim().replace(/\/+$/, '');
    S.baseUrl = v;
    localStorage.setItem('apidocs_base_url', v);
    // Also persist per-collection so it restores when switching back
    if (S.activeId) localStorage.setItem('apidocs_base_url_' + S.activeId, v);
    toast(v ? 'Base URL saved' : 'Base URL cleared', 'success');
}

function saveGlobalBearer() {
    if (S.user?.role === 'viewer') {
        toast('Viewer accounts cannot modify the Bearer token', 'error');
        return;
    }

    const v = document.getElementById('globalBearer').value.trim();
    S.globalBearer = v;
    localStorage.setItem('apidocs_bearer', v);
    if (S.activeId) localStorage.setItem('apidocs_bearer_' + S.activeId, v);
    toast(v ? 'Bearer token saved' : 'Bearer cleared', 'success');
}

/* ============================================================
   RENDER VIEW
============================================================ */
function renderView() {
    const col = S.activeData;
    if (!col) return;
    document.getElementById('heroTitle').textContent = col.name;
    document.getElementById('heroDesc').textContent = col.description || (col.endpoints?.length + ' endpoints');
    const eps = col.endpoints ?? [],
        methods = {};
    eps.forEach(e => {
        methods[e.method] = (methods[e.method] || 0) + 1;
    });
    const aiDone = eps.filter(e => e.ai_summary).length;
    let sh =
        `<div class="total-pill"><span class="stat-count">${eps.length}</span><span class="stat-label">Total</span></div>`;
    Object.entries(methods).sort().forEach(([m, c]) => {
        const col = MC[m] ?? '#6c757d';
        sh +=
            `<div class="stat-pill" style="border-color:${col}"><span class="stat-count" style="color:${col}">${c}</span><span class="stat-label">${m}</span></div>`;
    });
    document.getElementById('heroStats').innerHTML = sh;
    const pct = eps.length ? Math.round(aiDone / eps.length * 100) : 0;
    document.getElementById('batchBar').style.width = pct + '%';
    document.getElementById('batchStatusText').textContent = aiDone + '/' + eps.length + ' documented';
    renderGroups(eps);
}

function renderGroups(endpoints) {
    const groups = {};
    endpoints.forEach(ep => {
        const g = ep.group || 'General';
        (groups[g] = groups[g] || []).push(ep);
    });
    const nav = document.getElementById('sidebarNav');
    nav.innerHTML = '';
    Object.keys(groups).forEach(g => {
        const id = 'grp-' + slugify(g),
            li = document.createElement('li');
        li.innerHTML =
            `<a class="group-nav-link" href="#${id}"><i class="bi bi-folder2" style="font-size:.73rem;opacity:.6"></i>${esc(g)}<span class="grp-count">${groups[g].length}</span></a>`;
        li.querySelector('a').addEventListener('click', e => {
            e.preventDefault();
            document.getElementById(id)?.scrollIntoView({
                behavior: 'smooth'
            });
            if (window.matchMedia('(max-width:768px)').matches) toggleSidebar();
        });
        nav.appendChild(li);
    });
    const cont = document.getElementById('endpointGroups');
    cont.innerHTML = '';
    Object.entries(groups).forEach(([g, eps]) => {
        const id = 'grp-' + slugify(g),
            done = eps.filter(e => e.ai_summary).length;
        const sec = document.createElement('section');
        sec.className = 'api-group';
        sec.id = id;
        sec.innerHTML = `<div class="group-header"><h2>${esc(g)}</h2><span class="group-count">${eps.length} ep${eps.length !== 1 ? 's' : ''}</span>
      ${can('ai') ? `<button class="btn-group-ai" onclick="batchGroup('${esc(g)}')"><i class="bi bi-stars"></i> AI Docs ${done}/${eps.length}</button>` : '<span style="font-size:.64rem;color:var(--muted);margin-left:auto;display:flex;align-items:center;gap:3px"><i class="bi bi-stars"></i> ' + done + '/' + eps.length + ' AI docs</span>'}
    </div><div class="ep-list"></div>`;
        eps.forEach(ep => sec.querySelector('.ep-list').appendChild(buildCard(ep)));
        cont.appendChild(sec);
    });
}

/* ============================================================
   ENDPOINT CARD
============================================================ */
function buildCard(ep) {
    const card = document.createElement('div');
    const col = MC[ep.method] ?? '#6c757d';
    card.className = 'endpoint-card' + (ep.ai_summary ? ' has-summary' : '');
    card.id = 'ep-' + ep.id;
    const authB = (ep.auth?.type && ep.auth.type !== 'noauth') ?
        `<span class="auth-badge"><i class="bi bi-shield-lock me-1"></i>${esc(ep.auth.type)}</span>` :
        `<span class="auth-none"><i class="bi bi-unlock me-1"></i>No Auth</span>`;
    const aiB = ep.ai_summary ? `<span class="ai-badge"><i class="bi bi-stars"></i>AI</span>` : '';
    card.innerHTML = `
    <div class="endpoint-header" onclick="toggleCard(this)">
      <span class="method-badge" style="background:${col}">${esc(ep.method)}</span>
      <div style="flex:1;min-width:0">
        <div class="endpoint-name">${esc(ep.name)}</div>
        <div style="margin-top:2px"><code style="background:var(--bg3);border:1px solid var(--border);border-radius:4px;padding:1px 6px;color:#f0883e;font-family:'JetBrains Mono',monospace;font-size:.71rem;word-break:break-all">${esc(ep.url)}</code></div>
        <div class="endpoint-meta">${authB}${aiB}</div>
      </div>
      <div class="card-actions">
        ${can('run') ? `<button class="card-btn run-btn" onclick="event.stopPropagation();toggleRunner('${ep.id}')"><i class="bi bi-play-fill"></i><span class="btn-text"> Run</span></button>` : ''}
        ${can('ai') && !ep.ai_summary ? `<button class="card-btn" style="border-color:rgba(167,139,250,.3);color:var(--ai)" onclick="event.stopPropagation();generateSummary('${ep.id}')" title="Generate AI docs"><i class="bi bi-stars"></i></button>` : ''}
        ${can('write') ? `<button class="card-btn" onclick="event.stopPropagation();editEndpoint('${ep.id}')"><i class="bi bi-pencil"></i></button>` : ''}
        ${can('write') ? `<button class="card-btn danger" onclick="event.stopPropagation();deleteEndpoint('${ep.id}')"><i class="bi bi-trash3"></i></button>` : ''}
        <i class="bi bi-chevron-down collapse-chevron"></i>
      </div>
    </div>
    <div class="endpoint-body" id="body-${ep.id}">${buildBody(ep)}</div>`;
    return card;
}

function buildBody(ep) {
    let h = '';
    if (can('run')) h += buildRunner(ep);
    // ALL users see AI docs (including viewers) — only generation buttons are hidden for non-ai users
    h += buildAIPanel(ep);
    if (ep.description) h += `<div class="desc-block">${mdLite(esc(ep.description))}</div>`;
    const aq = (ep.query ?? []).filter(q => !q.disabled);
    if (aq.length) {
        h += `<div class="section-heading"><i class="bi bi-question-circle"></i> Query Params</div>
    <div class="table-responsive"><table class="table table-sm param-table">
    <thead><tr><th>Key</th><th>Value</th><th>Description</th></tr></thead><tbody>`;
        aq.forEach(q => {
            h +=
                `<tr><td><code>${esc(q.key)}</code></td><td>${esc(q.value)}</td><td>${esc(q.description)}</td></tr>`;
        });
        h += '</tbody></table></div>';
    }
    if ((ep.path_vars ?? []).length) {
        h += `<div class="section-heading"><i class="bi bi-braces"></i> Path Variables</div>
    <div class="table-responsive"><table class="table table-sm param-table">
    <thead><tr><th>Variable</th><th>Example</th></tr></thead><tbody>`;
        ep.path_vars.forEach(p => {
            h += `<tr><td><code>:${esc(p.key)}</code></td><td>${esc(p.value)}</td></tr>`;
        });
        h += '</tbody></table></div>';
    }
    const body = ep.body ?? {},
        mode = body.mode ?? '';
    if (mode) {
        h +=
            `<div class="section-heading"><i class="bi bi-send"></i> Body <span style="font-size:.67rem;opacity:.6">(${mode})</span></div>`;
        if (mode === 'raw' && body.raw) h += `<pre class="raw-body">${esc(body.raw)}</pre>`;
        else {
            const pp = body[mode] ?? body.urlencoded ?? body.formdata ?? [];
            if (pp.length) {
                h += `<div class="table-responsive"><table class="table table-sm param-table">
        <thead><tr><th>Key</th><th>Type</th><th>Value</th></tr></thead><tbody>`;
                pp.filter(p => !p.disabled).forEach(p => {
                    h +=
                        `<tr><td><code>${esc(p.key)}</code></td><td>${esc(p.type ?? 'text')}</td><td>${esc(p.value)}</td></tr>`;
                });
                h += '</tbody></table></div>';
            }
        }
    }
    if ((ep.responses ?? []).length) {
        h += `<div class="section-heading"><i class="bi bi-arrow-return-left"></i> Example Responses</div>`;
        ep.responses.forEach(r => {
            const c = parseInt(r.code ?? 0),
                cls = c >= 500 ? 'danger' : c >= 400 ? 'warning' : 'success';
            let b = r.body ?? '';
            try {
                b = JSON.stringify(JSON.parse(b), null, 2);
            } catch { }
            h += `<div class="resp-block">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px;font-size:.73rem">
          <span class="badge bg-${cls}">${r.code}</span>
          <span style="color:var(--muted)">${esc(r.status)}</span>
          <span style="color:var(--muted);font-size:.68rem">— ${esc(r.name)}</span>
        </div>
        ${b ? `<pre class="resp-body-pre">${esc(b)}</pre>` : ''}
      </div>`;
        });
    }
    return h;
}

function toggleCard(hdr) {
    hdr.classList.toggle('open');
    hdr.nextElementSibling.classList.toggle('open');
}

/* ============================================================
   RUNNER PANEL — proxy-based (no CORS), bearer fixed
============================================================ */
function buildRunner(ep) {
    const col = MC[ep.method] ?? '#6c757d';
    const ru = resolveUrl(ep.url);

    // Pre-fill query params
    let qRows = '';
    (ep.query ?? [])
        .filter(q => !q.disabled).forEach(q => {
            qRows += `<div class="runner-param-row">
      <input type="text" class="r-key" placeholder="key" value="${esc(q.key)}">
      <input type="text" class="r-val" placeholder="value" value="${esc(q.value)}">
      <button class="btn-rm" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>`;
        });

    // Pre-fill headers — inject real bearer value directly
    const bearerVal = S.globalBearer ? 'Bearer ' + S.globalBearer : '';
    let hRows = `<div class="runner-param-row">
    <input type="text" class="r-key" value="Content-Type">
    <input type="text" class="r-val" value="application/json">
    <button class="btn-rm" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
  </div>`;
    if (ep.auth?.type === 'bearer' || S.globalBearer) {
        hRows += `<div class="runner-param-row">
      <input type="text" class="r-key" value="Authorization">
      <input type="text" class="r-val" id="rauth-${ep.id}" value="${esc(bearerVal)}" placeholder="Bearer token">
      <button class="btn-rm" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>`;
    }

    const rawBody = ep.body?.raw ?? '';
    return `<div class="runner-panel" id="runner-${ep.id}" style="display:none">
    <div class="runner-header">
      <div class="runner-title"><i class="bi bi-play-circle-fill"></i> Runner</div>
      <span style="margin-left:auto;font-size:.67rem;color:var(--green);display:flex;align-items:center;gap:4px">
        <i class="bi bi-shield-check"></i> Laravel proxy — no CORS
      </span>
    </div>
    <div class="runner-body-wrap">
      <div class="runner-url-bar">
        <div class="runner-method-pill" style="background:${col}">${esc(ep.method)}</div>
        <input class="runner-url-input" id="rurl-${ep.id}" type="text" value="${esc(ru)}" placeholder="https://…">
        <button class="runner-send-btn" id="rsend-${ep.id}" onclick="runEndpoint('${ep.id}','${esc(ep.method)}')">
          <i class="bi bi-send-fill"></i> Send
        </button>
      </div>
      <div class="runner-tabs">
        <button class="runner-tab active" onclick="switchTab(this,'rtab-q-${ep.id}')">Query</button>
        <button class="runner-tab"        onclick="switchTab(this,'rtab-h-${ep.id}')">Headers</button>
        <button class="runner-tab"        onclick="switchTab(this,'rtab-b-${ep.id}')">Body</button>
        <button class="runner-tab"        onclick="switchTab(this,'rtab-r-${ep.id}')">Response</button>
      </div>
      <div class="runner-tab-pane active" id="rtab-q-${ep.id}">
        <div class="runner-params-list" id="rq-${ep.id}">${qRows}</div>
        <button class="add-row-btn" onclick="addRunnerRow('rq-${ep.id}')"><i class="bi bi-plus"></i> Add param</button>
      </div>
      <div class="runner-tab-pane" id="rtab-h-${ep.id}">
        <div class="runner-params-list" id="rh-${ep.id}">${hRows}</div>
        <button class="add-row-btn" onclick="addRunnerRow('rh-${ep.id}')"><i class="bi bi-plus"></i> Add header</button>
      </div>
      <div class="runner-tab-pane" id="rtab-b-${ep.id}">
        <textarea class="runner-body-ta" id="rb-${ep.id}" placeholder='{"key":"value"}'>${esc(rawBody)}</textarea>
      </div>
      <div class="runner-tab-pane" id="rtab-r-${ep.id}">
        <div id="rresp-${ep.id}" style="color:var(--muted);font-size:.77rem;padding:8px 0">
          Hit <strong>Send</strong> to see the response here.
        </div>
      </div>
    </div>
  </div>`;
}

function toggleRunner(id) {
    const hdr = document.querySelector(`#ep-${id} .endpoint-header`);
    const body = document.getElementById('body-' + id);
    if (!body.classList.contains('open')) {
        hdr.classList.add('open');
        body.classList.add('open');
    }
    const r = document.getElementById('runner-' + id);
    if (r) r.style.display = r.style.display === 'block' ? 'none' : 'block';
}

function switchTab(btn, tid) {
    const w = btn.closest('.runner-body-wrap');
    w.querySelectorAll('.runner-tab').forEach(t => t.classList.remove('active'));
    w.querySelectorAll('.runner-tab-pane').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(tid)?.classList.add('active');
}

function addRunnerRow(cid) {
    const c = document.getElementById(cid),
        r = document.createElement('div');
    r.className = 'runner-param-row';
    r.innerHTML = `<input type="text" class="r-key" placeholder="key">
    <input type="text" class="r-val" placeholder="value">
    <button class="btn-rm" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>`;
    c.appendChild(r);
}

function resolveUrl(raw) {
    const base = S.baseUrl || S.activeData?.base_url || '';
    return raw
        .replace(/\{\{base[_-]?url\}\}/gi, base)
        .replace(/\{\{baseUrl\}\}/g, base)
        .replace(/\{\{url\}\}/gi, base);
}

async function runEndpoint(epId, method) {
    const sendBtn = document.getElementById('rsend-' + epId);
    const respEl = document.getElementById('rresp-' + epId);
    const respTab = document.querySelector(`#runner-${epId} .runner-tab:nth-child(4)`);
    if (respTab) switchTab(respTab, 'rtab-r-' + epId);

    let url = document.getElementById('rurl-' + epId)?.value.trim() ?? '';
    if (!url) {
        toast('Enter a URL', 'error');
        return;
    }
    // Re-resolve in case base URL changed after runner was built
    url = resolveUrl(url);

    // Append query params
    const qp = [...document.querySelectorAll(`#rq-${epId} .runner-param-row`)].map(r => ({
        k: r.querySelector('.r-key')?.value.trim(),
        v: r.querySelector('.r-val')?.value.trim()
    })).filter(p => p.k);
    if (qp.length) {
        const qs = qp.map(p => encodeURIComponent(p.k) + '=' + encodeURIComponent(p.v)).join('&');
        url += (url.includes('?') ? '&' : '?') + qs;
    }

    // Collect headers from runner panel
    const hdrs = {};
    [...document.querySelectorAll(`#rh-${epId} .runner-param-row`)].forEach(r => {
        const k = r.querySelector('.r-key')?.value.trim();
        const v = r.querySelector('.r-val')?.value.trim();
        if (k && v) hdrs[k] = v;
    });

    // ── BEARER FIX: if no Authorization header present, inject global bearer ──
    const hasAuth = Object.keys(hdrs).some(k => k.toLowerCase() === 'authorization');
    if (S.globalBearer && !hasAuth) {
        hdrs['Authorization'] = 'Bearer ' + S.globalBearer;
    }

    const rawBody = document.getElementById('rb-' + epId)?.value.trim() || null;
    const ep = findEp(epId);

    sendBtn.disabled = true;
    sendBtn.innerHTML = '<span class="spinner"></span> Sending…';
    respEl.innerHTML = '<div class="runner-loading"><div class="spinner"></div> Sending via proxy…</div>';

    const t0 = Date.now();
    try {
        const proxyRes = await fetch(`${API}/proxy/run`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                method: method.toUpperCase(),
                url,
                headers: hdrs,
                body: rawBody
            }),
        });
        const elapsed = Date.now() - t0;
        const pd = await proxyRes.json();

        if (!proxyRes.ok || pd.error) {
            const msg = pd.message ?? 'Proxy could not reach the target server.';
            respEl.innerHTML = `<div class="runner-resp-box">
        <div class="runner-resp-meta">
          <span class="resp-err">&#x26A0; ${proxyRes.status === 502 ? 'Connection Failed' : 'Proxy Error'}</span>
          <span class="resp-pill">${elapsed}ms</span>
        </div>
        <pre class="runner-pre is-err">${esc(msg)}\n\nCheck:\n• URL is correct and reachable\n• Your API server is running\n• PHP can reach the host (firewall/DNS)</pre>
      </div>`;
            return;
        }

        // Format response body
        let body = pd.body ?? '',
            fmt = body;
        try {
            fmt = JSON.stringify(JSON.parse(body), null, 2);
        } catch { }
        const sizeKB = (new Blob([body]).size / 1024).toFixed(1);
        const isOk = pd.ok,
            sc = isOk ? 'resp-ok' : 'resp-err';
        const authNote = S.globalBearer && !hasAuth ?
            `<span class="resp-pill" style="color:var(--yellow)"><i class="bi bi-shield-lock"></i> Global Bearer</span>` :
            '';

        // Store for Save button
        S.lastResp = {
            epId,
            collectionId: S.activeId,
            endpointId: epId,
            endpointName: ep?.name ?? '',
            method: method.toUpperCase(),
            url,
            statusCode: pd.status,
            responseBody: fmt,
            requestHeaders: hdrs,
            requestBody: rawBody,
            responseTimeMs: elapsed,
        };

        respEl.innerHTML = `<div class="runner-resp-box">
      <div class="runner-resp-meta">
        <span class="${sc}">${pd.status} ${pd.statusText}</span>
        <span class="resp-pill"><i class="bi bi-clock"></i> ${elapsed}ms</span>
        <span class="resp-pill"><i class="bi bi-database"></i> ${sizeKB}KB</span>
        ${authNote}
      </div>
      <pre class="runner-pre${isOk ? '' : ' is-err'}">${esc(fmt || '(empty response)')}</pre>
      <div class="runner-save-bar">
        <input type="text" class="runner-save-input" id="rlabel-${epId}" placeholder="Label this response (optional)…">
        <button class="runner-save-btn" onclick="saveRunnerResponse('${epId}')">
          <i class="bi bi-bookmark-plus"></i> Save Response
        </button>
      </div>
    </div>`;
    } catch (err) {
        const elapsed = Date.now() - t0;
        respEl.innerHTML = `<div class="runner-resp-box">
      <div class="runner-resp-meta"><span class="resp-err">&#x26A0; Failed</span><span class="resp-pill">${elapsed}ms</span></div>
      <pre class="runner-pre is-err">${esc(err.message)}\n\nMake sure: php artisan serve is running</pre>
    </div>`;
    } finally {
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="bi bi-send-fill"></i> Send';
    }
}

/* ============================================================
   SAVE RESPONSE
============================================================ */
async function saveRunnerResponse(epId) {
    if (!S.lastResp || S.lastResp.epId !== epId) {
        toast('No response to save', 'error');
        return;
    }
    const label = document.getElementById('rlabel-' + epId)?.value.trim() || null;
    try {
        const r = await fetch(`${API}/saved-responses`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ...S.lastResp,
                label
            }),
        });
        const d = await r.json();
        if (!r.ok) throw new Error(d.error ?? 'Save failed');
        toast('Response saved!', 'success');
    } catch (err) {
        toast('Save failed: ' + err.message, 'error');
    }
}

async function openSavedResponsesModal() {
    const el = document.getElementById('savedRespBody');
    el.innerHTML = '<div class="runner-loading"><div class="spinner"></div> Loading…</div>';
    new bootstrap.Modal(document.getElementById('savedRespModal')).show();
    try {
        const r = await apiFetch('/saved-responses' + (S.activeId ? '?collection_id=' + S.activeId : ''));
        const items = r.data ?? [];
        if (!items.length) {
            el.innerHTML = '<p style="color:var(--muted);font-size:.8rem">No saved responses yet.</p>';
            return;
        }
        el.innerHTML = '';
        items.forEach(item => {
            const code = parseInt(item.status_code ?? 0);
            const cls = code >= 500 ? 'danger' : code >= 400 ? 'warning' : 'success';
            const col = MC[item.method] ?? '#6c757d';
            const div = document.createElement('div');
            div.className = 'saved-item';
            div.innerHTML =
                `
        <span class="method-badge" style="background:${col};flex-shrink:0">${esc(item.method)}</span>
        <div style="flex:1;min-width:0">
          <div style="font-size:.8rem;font-weight:600">${esc(item.label || item.endpoint_name)}</div>
          <div class="saved-url">${esc(item.url)}</div>
          <div style="margin-top:3px;display:flex;gap:6px;align-items:center">
            <span class="badge bg-${cls}" style="font-size:.63rem">${item.status_code}</span>
            <span style="font-size:.67rem;color:var(--muted)">${item.response_time_ms}ms</span>
            <span style="font-size:.67rem;color:var(--muted)">${new Date(item.created_at).toLocaleString()}</span>
          </div>
        </div>
        <button class="btn-rm" onclick="deleteSavedResp(${item.id},this)" title="Delete"><i class="bi bi-trash3"></i></button>`;
            el.appendChild(div);
        });
    } catch (err) {
        el.innerHTML = `<p style="color:var(--a2);font-size:.8rem">Error: ${esc(err.message)}</p>`;
    }
}

async function deleteSavedResp(id, btn) {
    if (!confirm('Delete this saved response?')) return;
    try {
        await apiFetch('/saved-responses/' + id, {
            method: 'DELETE'
        });
        btn.closest('.saved-item')?.remove();
        toast('Deleted', 'success');
    } catch (err) {
        toast('Delete failed: ' + err.message, 'error');
    }
}

/* ============================================================
   AI PANEL — with fixed loading/done state
============================================================ */
function buildAIPanel(ep) {
    const canAI = can('ai');
    if (!ep.ai_summary) {
        // No summary yet — only show generate button if user has AI permission
        if (!canAI) return ''; // viewer with no summary: nothing to show
        return `<button class="ai-generate-btn" id="aibtn-${ep.id}" onclick="generateSummary('${ep.id}')">
      <i class="bi bi-stars"></i> Generate AI Documentation
    </button>`;
    }
    // Summary exists — ALL users see it; only AI-capable users see Regenerate button
    const regenBtn = canAI ?
        `<button class="ai-panel-btn" id="aibtn-${ep.id}" onclick="generateSummary('${ep.id}')"><i class="bi bi-arrow-clockwise"></i> Regenerate</button>` :
        `<span style="font-size:.65rem;color:var(--muted);display:flex;align-items:center;gap:4px"><i class="bi bi-lock"></i> View only</span>`;
    return `<div class="ai-panel" id="aipanel-${ep.id}">
    <div class="ai-panel-header">
      <div class="ai-panel-title"><i class="bi bi-stars"></i> AI Documentation</div>
      ${regenBtn}
    </div>
    <div class="ai-summary-text" id="aitext-${ep.id}">${renderMD(ep.ai_summary)}</div>
  </div>`;
}

function renderMD(txt) {
    if (!txt) return '';
    return txt.split('\n').map(l => l.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')).join('<br>');
}

const TYPEWRITER = {};

function startTypewriter(epId, text) {
    stopTypewriter(epId);
    const tx = document.getElementById('aitext-' + epId);
    if (!tx) return;
    tx.classList.add('ai-typewriter');
    tx.textContent = '';
    TYPEWRITER[epId] = { text, idx: 0, timer: null, elem: tx };
    const step = () => {
        const state = TYPEWRITER[epId];
        if (!state) return;
        state.idx = Math.min(state.idx + 1, state.text.length);
        state.elem.textContent = state.text.slice(0, state.idx);
        if (state.idx < state.text.length) {
            state.timer = setTimeout(step, 18);
        } else {
            state.elem.classList.remove('ai-typewriter');
            state.elem.innerHTML = renderMD(state.text);
            delete TYPEWRITER[epId];
        }
    };
    step();
}

function updateTypewriter(epId, text) {
    const state = TYPEWRITER[epId];
    if (!state) {
        startTypewriter(epId, text);
        return;
    }
    state.text = text;
}

function stopTypewriter(epId, renderFinal = false) {
    const state = TYPEWRITER[epId];
    if (!state) return;
    if (state.timer) clearTimeout(state.timer);
    if (renderFinal && state.elem) {
        state.elem.classList.remove('ai-typewriter');
        state.elem.innerHTML = renderMD(state.text);
    }
    delete TYPEWRITER[epId];
}

async function generateSummary(epId) {
    const isCloud = !!S.cloudProvider;
    if (!isCloud && !S.ollamaOnline) {
        toast('Ollama offline — run: ollama serve', 'error');
        return;
    }
    if (isCloud && !S.cloudKeys[S.cloudProvider]) {
        toast('No API key for ' + S.cloudProvider, 'error');
        return;
    }
    if (S.generatingIds.has(epId)) return;
    const ep = findEp(epId);
    if (!ep) return;
    S.generatingIds.add(epId);

    // Build or reset AI panel — show thinking dots
    let panel = document.getElementById('aipanel-' + epId);
    if (!panel) {
        const btn = document.getElementById('aibtn-' + epId);
        const np = document.createElement('div');
        np.className = 'ai-panel';
        np.id = 'aipanel-' + epId;
        np.innerHTML = `<div class="ai-panel-header">
      <div class="ai-panel-title"><i class="bi bi-stars"></i> AI Documentation</div>
      <button class="ai-panel-btn" id="aibtn-${epId}" disabled><i class="bi bi-hourglass-split"></i> Generating…</button>
    </div>
    <div class="ai-thinking" id="aithink-${epId}">
      <i class="bi bi-stars" style="color:var(--ai)"></i> Generating…
      <div class="ai-dots"><span>•</span><span>•</span><span>•</span></div>
    </div>
    <div class="ai-summary-text" id="aitext-${epId}"></div>`;
        btn?.replaceWith(np);
        panel = np;
    } else {
        const tx = document.getElementById('aitext-' + epId);
        if (tx) tx.innerHTML = '';
        if (!document.getElementById('aithink-' + epId)) {
            const d = document.createElement('div');
            d.className = 'ai-thinking';
            d.id = 'aithink-' + epId;
            d.innerHTML =
                `<i class="bi bi-stars" style="color:var(--ai)"></i> Generating…<div class="ai-dots"><span>•</span><span>•</span><span>•</span></div>`;
            document.getElementById('aitext-' + epId)?.parentNode?.insertBefore(d, document.getElementById(
                'aitext-' + epId));
        }
        const b = document.getElementById('aibtn-' + epId);
        if (b) {
            b.disabled = true;
            b.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating…';
        }
    }
    document.getElementById('ep-' + epId)?.classList.add('has-summary');

    let accumulated = '';
    try {
        if (isCloud) {
            accumulated = await callCloudAI(ep);
            const tx = document.getElementById('aitext-' + epId);
            if (tx) startTypewriter(epId, accumulated);
        } else {
            // Ollama SSE stream
            const res = await fetch(`${API}/ai/summarize`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    endpoint: ep,
                    model: S.ollamaModel,
                    collection_id: S.activeId,
                    endpoint_id: ep.id
                }),
            });
            const reader = res.body.getReader(),
                dec = new TextDecoder();
            const tx = document.getElementById('aitext-' + epId);
            let dotsGone = false;
            while (true) {
                const {
                    done,
                    value
                } = await reader.read();
                if (done) break;
                for (const line of dec.decode(value, {
                    stream: true
                }).split('\n')) {
                    if (!line.startsWith('data: ')) continue;
                    try {
                        const d = JSON.parse(line.slice(6));
                        if (d.token) {
                            // ── Remove thinking dots on first real token ──
                            if (!dotsGone) {
                                document.getElementById('aithink-' + epId)?.remove();
                                dotsGone = true;
                            }
                            accumulated += d.token;
                            if (tx) updateTypewriter(epId, accumulated);
                        }
                        if (d.done) {
                            ep.ai_summary = d.summary || accumulated;
                            markBadge(epId);
                            updateProgress();
                        }
                    } catch { }
                }
            }
        }
        if (isCloud && accumulated) {
            ep.ai_summary = accumulated;
            await persistSummary(ep.id, accumulated);
            markBadge(epId);
            updateProgress();
        }
        toast('AI docs ready for "' + ep.name + '"', 'ai');
    } catch (err) {
        toast('AI failed: ' + err.message, 'error');
    } finally {
        // ── Always remove dots and re-enable button ──
        document.getElementById('aithink-' + epId)?.remove();
        S.generatingIds.delete(epId);
        const b = document.getElementById('aibtn-' + epId);
        if (b) {
            b.disabled = false;
            b.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Regenerate';
        }
    }
}

async function callCloudAI(ep) {
    const p = S.cloudProvider,
        m = S.cloudModel,
        k = S.cloudKeys[p];
    const prompt = buildPrompt(ep);
    if (p === 'openai') {
        const r = await fetch('https://api.openai.com/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + k
            },
            body: JSON.stringify({
                model: m,
                messages: [{
                    role: 'user',
                    content: prompt
                }],
                max_tokens: 1024,
                temperature: 0.3
            })
        });
        if (!r.ok) {
            const e = await r.json();
            throw new Error(e.error?.message ?? 'OpenAI error');
        }
        return (await r.json()).choices?.[0]?.message?.content ?? '';
    }
    if (p === 'anthropic') {
        const r = await fetch('https://api.anthropic.com/v1/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'x-api-key': k,
                'anthropic-version': '2023-06-01',
                'anthropic-dangerous-direct-browser-access': 'true'
            },
            body: JSON.stringify({
                model: m,
                max_tokens: 1024,
                messages: [{
                    role: 'user',
                    content: prompt
                }]
            })
        });
        if (!r.ok) {
            const e = await r.json();
            throw new Error(e.error?.message ?? 'Anthropic error');
        }
        return (await r.json()).content?.[0]?.text ?? '';
    }
    if (p === 'google') {
        const r = await fetch(
            `https://generativelanguage.googleapis.com/v1beta/models/${m}:generateContent?key=${k}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                contents: [{
                    parts: [{
                        text: prompt
                    }]
                }],
                generationConfig: {
                    temperature: 0.3,
                    maxOutputTokens: 1024
                }
            })
        });
        if (!r.ok) {
            const e = await r.json();
            throw new Error(e.error?.message ?? 'Google error');
        }
        return (await r.json()).candidates?.[0]?.content?.parts?.[0]?.text ?? '';
    }
    if (p === 'groq') {
        // Groq uses OpenAI-compatible API — supports Llama3, Mixtral, etc.
        const r = await fetch('https://api.groq.com/openai/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + k
            },
            body: JSON.stringify({
                model: m,
                messages: [{
                    role: 'user',
                    content: prompt
                }],
                max_tokens: 1024,
                temperature: 0.3
            })
        });
        if (!r.ok) {
            const e = await r.json();
            throw new Error(e.error?.message ?? 'Groq error');
        }
        return (await r.json()).choices?.[0]?.message?.content ?? '';
    }
    throw new Error('Unknown provider: ' + p);
}

function buildPrompt(ep) {
    const method = ep.method ?? 'GET',
        name = ep.name ?? '',
        url = ep.url ?? '',
        group = ep.group ?? '';
    const auth = ep.auth?.type ?? 'none',
        desc = ep.description ?? '';
    // Query params
    let queryPart = '';
    (ep.query ?? []).filter(q => !q.disabled).forEach(q => {
        queryPart += `  - ${q.key}: ${q.value} — ${q.description ?? ''}\n`;
    });
    if (queryPart) queryPart = 'Query Parameters:\n' + queryPart;
    // Path vars
    let pathPart = '';
    (ep.path_vars ?? []).forEach(pv => {
        pathPart += `  - :${pv.key} = ${pv.value}\n`;
    });
    if (pathPart) pathPart = 'Path Variables:\n' + pathPart;
    // Body
    let bodyPart = '';
    const body = ep.body ?? {},
        mode = body.mode ?? '';
    if (mode === 'raw' && body.raw) {
        const lang = body.options?.raw?.language ?? 'json';
        bodyPart = `Request Body (${mode}):\nRaw body (${lang}):\n${body.raw}`;
    } else if (['formdata', 'urlencoded'].includes(mode)) {
        const params = body[mode] ?? [];
        let lines = '';
        params.filter(p => !p.disabled).forEach(p => {
            lines += `  - ${p.key} (${p.type ?? 'text'}): ${p.value} — ${p.description ?? ''}\n`;
        });
        if (lines) bodyPart = `Request Body (${mode}):\n${lines}`;
    }
    // Example response
    let examplePart = '';
    const resp = (ep.responses ?? [])[0];
    if (resp?.body) {
        let b = resp.body;
        try {
            b = JSON.stringify(JSON.parse(b), null, 2);
        } catch { }
        examplePart = 'Example Response:\n' + b.substring(0, 600) + (b.length > 600 ? '...' : '');
    }
    const descPart = desc ? 'Existing Description:\n' + desc : '';

    return `You are an expert API documentation writer, similar to Postman's AI documentation assistant.

Generate clear, concise, developer-friendly documentation for the API endpoint provided.

Use plain English and base your output ONLY on the supplied endpoint data.

Do not invent:
- fields
- behaviors
- authentication methods
- rate limits
- validation rules
- response structures
- business logic
- implementation details

If information is missing, explicitly say so instead of guessing.

Follow this structure exactly:

1. **Overview** — One sentence explaining what the endpoint does.
2. **Use Case** — Briefly explain when and why a developer would use this endpoint.
3. **Authentication** — Describe the authentication requirement only if explicitly provided. Otherwise state: "No authentication details provided."
4. **Parameters** — List and briefly explain each parameter and its purpose. Skip this section entirely if there are no parameters.
5. **Request Body** — List and briefly explain each request body field and its role. Mention required fields when known. Skip this section entirely if there is no request body.
6. **Response** — Describe the successful response and explain the meaning of important response fields using ONLY the provided example or schema. Skip this section entirely if no response example/schema is available.
7. **Notes** — Include important implementation details, constraints, caveats, or developer tips ONLY if explicitly available. Otherwise omit this section entirely.

Additional Rules:
- Keep the tone technical but approachable.
- Avoid generic filler language.
- Do not include code examples.
- Do not repeat the endpoint URL unless necessary.
- Prefer short paragraphs or bullet points for readability.
- Prioritize describing real field behavior inferred from examples over generic API terminology.
- Be precise and developer-focused.

--------------------------------------------------
API ENDPOINT DETAILS
--------------------------------------------------

Endpoint Name: ${name}
Group / Module: ${group}
HTTP Method: ${method}
URL: ${url}
Auth: ${auth}
${queryPart}
${pathPart}
${bodyPart}
${examplePart}
${descPart}
---

Write the documentation summary now:`;
}

async function persistSummary(endpointId, summary) {
    try {
        await fetch(`${API}/collections/${S.activeId}/endpoints/${endpointId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({
                ai_summary: summary
            }),
        });
    } catch { }
}

/* ============================================================
   BATCH AI
============================================================ */
async function batchSummarizeAll() {
    if (!S.activeId) {
        toast('No collection loaded', 'error');
        return;
    }
    if (!S.cloudProvider && !S.ollamaOnline) {
        toast('No AI available — start Ollama or select a cloud model', 'error');
        return;
    }
    S.cloudProvider ? await batchCloud(S.activeData?.endpoints ?? []) : await batchOllama();
}
async function batchGroup(group) {
    if (!S.activeId) {
        toast('No collection', 'error');
        return;
    }
    const eps = (S.activeData?.endpoints ?? []).filter(e => e.group === group);
    S.cloudProvider ? await batchCloud(eps) : await batchOllama(group);
}

async function batchCloud(endpoints) {
    const todo = endpoints.filter(e => !e.ai_summary);
    if (!todo.length) {
        toast('All endpoints already documented', 'ai');
        return;
    }
    toast('Cloud AI for ' + todo.length + ' endpoints…', 'ai');
    let done = 0;
    for (const ep of todo) {
        try {
            const sum = await callCloudAI(ep);
            ep.ai_summary = sum;
            await persistSummary(ep.id, sum);
            const tx = document.getElementById('aitext-' + ep.id);
            if (tx) {
                tx.innerHTML = renderMD(sum);
            } else if (!document.getElementById('aipanel-' + ep.id)) {
                const gb = document.getElementById('aibtn-' + ep.id);
                if (gb) {
                    const np = document.createElement('div');
                    np.className = 'ai-panel';
                    np.id = 'aipanel-' + ep.id;
                    np.innerHTML = `<div class="ai-panel-header">
            <div class="ai-panel-title"><i class="bi bi-stars"></i> AI Documentation</div>
            <button class="ai-panel-btn" onclick="generateSummary('${ep.id}')"><i class="bi bi-arrow-clockwise"></i> Regenerate</button>
          </div><div class="ai-summary-text" id="aitext-${ep.id}">${renderMD(sum)}</div>`;
                    gb.replaceWith(np);
                }
            }
            markBadge(ep.id);
            done++;
            document.getElementById('batchBar').style.width = Math.round(done / todo.length * 100) + '%';
            document.getElementById('batchStatusText').textContent = done + '/' + todo.length + ' documented';
        } catch (err) {
            toast('Failed "' + ep.name + '": ' + err.message, 'error');
        }
    }
    toast('Cloud AI done! ' + done + '/' + todo.length, 'ai');
}

async function batchOllama() {
    const bar = document.getElementById('batchBar'),
        st = document.getElementById('batchStatusText');
    try {
        const res = await fetch(`${API}/ai/summarize-collection`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf
            },
            body: JSON.stringify({
                collection_id: S.activeId,
                model: S.ollamaModel,
                force: false
            })
        });
        const reader = res.body.getReader(),
            dec = new TextDecoder();
        let total = 0,
            done = 0;
        while (true) {
            const {
                done: rd,
                value
            } = await reader.read();
            if (rd) break;
            for (const line of dec.decode(value, {
                stream: true
            }).split('\n')) {
                if (!line.startsWith('data: ')) continue;
                try {
                    const d = JSON.parse(line.slice(6));
                    if (d.type === 'start') {
                        total = d.total;
                        toast('Ollama: ' + total + ' endpoints…', 'ai');
                    }
                    if (d.type === 'processing') st.textContent = 'Processing: ' + d.name;
                    if (d.type === 'done' || d.type === 'skip') {
                        done++;
                        const ep = findEp(d.id);
                        if (ep && d.summary) {
                            ep.ai_summary = d.summary;
                            document.getElementById('aithink-' + d.id)?.remove();
                            const tx = document.getElementById('aitext-' + d.id);
                            if (tx) {
                                tx.innerHTML = renderMD(d.summary);
                            } else if (!document.getElementById('aipanel-' + d.id)) {
                                const gb = document.getElementById('aibtn-' + d.id);
                                if (gb) {
                                    const np = document.createElement('div');
                                    np.className = 'ai-panel';
                                    np.id = 'aipanel-' + d.id;
                                    np.innerHTML = `<div class="ai-panel-header">
                    <div class="ai-panel-title"><i class="bi bi-stars"></i> AI Documentation</div>
                    <button class="ai-panel-btn" onclick="generateSummary('${d.id}')"><i class="bi bi-arrow-clockwise"></i> Regenerate</button>
                  </div><div class="ai-summary-text" id="aitext-${d.id}">${renderMD(d.summary)}</div>`;
                                    gb.replaceWith(np);
                                }
                            }
                            markBadge(d.id);
                        }
                        bar.style.width = Math.round(done / total * 100) + '%';
                        st.textContent = done + '/' + total + ' documented';
                    }
                    if (d.type === 'complete') {
                        toast('Ollama done! ' + d.total + ' endpoints', 'ai');
                        await loadCollection(S.activeId);
                    }
                } catch { }
            }
        }
    } catch (err) {
        toast('Batch failed: ' + err.message, 'error');
    }
}

function markBadge(epId) {
    const card = document.getElementById('ep-' + epId);
    if (!card) return;
    card.classList.add('has-summary');
    const meta = card.querySelector('.endpoint-meta');
    if (meta && !meta.querySelector('.ai-badge')) {
        const b = document.createElement('span');
        b.className = 'ai-badge';
        b.innerHTML = '<i class="bi bi-stars"></i>AI';
        meta.appendChild(b);
    }
}

function updateProgress() {
    const eps = S.activeData?.endpoints ?? [],
        done = eps.filter(e => e.ai_summary).length;
    const pct = eps.length ? Math.round(done / eps.length * 100) : 0;
    document.getElementById('batchBar').style.width = pct + '%';
    document.getElementById('batchStatusText').textContent = done + '/' + eps.length + ' documented';
}

/* ============================================================
   UPLOAD
============================================================ */
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
    const f = e.dataTransfer.files[0];
    if (f) {
        document.getElementById('uploadFile').files = e.dataTransfer.files;
        openUploadModal();
    }
}
async function submitUpload() {
    const fi = document.getElementById('uploadFile'),
        name = document.getElementById('uploadName').value;
    if (!fi.files.length) {
        toast('Choose a JSON file', 'error');
        return;
    }
    const fd = new FormData();
    fd.append('file', fi.files[0]);
    if (name) fd.append('name', name);
    try {
        const res = await fetch(`${API}/collections/upload`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf
            },
            body: fd
        });
        const d = await res.json();
        if (!res.ok) throw new Error(d.error ?? 'Upload failed');
        bootstrap.Modal.getInstance(document.getElementById('uploadModal'))?.hide();
        toast('Imported "' + d.data.name + '" — ' + d.data.total + ' endpoints', 'success');
        S.collections.push({
            id: d.data.id,
            name: d.data.name,
            total: d.data.total
        });
        await loadCollection(d.data.id);
    } catch (err) {
        toast('Import failed: ' + err.message, 'error');
    }
}

/* ============================================================
   ENDPOINT CRUD
============================================================ */
function openEndpointModal(ep = null) {
    document.getElementById('epModalTitle').innerHTML = ep ?
        '<i class="bi bi-pencil me-2"></i>Edit Endpoint' :
        '<i class="bi bi-plus-circle me-2"></i>New Endpoint';
    document.getElementById('epEditId').value = ep?.id ?? '';
    document.getElementById('epName').value = ep?.name ?? '';
    document.getElementById('epGroup').value = ep?.group ?? '';
    document.getElementById('epUrl').value = ep?.url ?? '';
    document.getElementById('epDesc').value = ep?.description ?? '';
    document.getElementById('epAuth').value = ep?.auth?.type ?? 'noauth';
    document.getElementById('epBody').value = ep?.body?.raw ?? '';
    document.querySelectorAll('.method-chip').forEach(c => {
        const sel = c.dataset.method === (ep?.method ?? 'GET');
        c.classList.toggle('selected', sel);
        c.style.background = sel ? MC[c.dataset.method] : '';
        c.style.color = sel ? '#fff' : '';
        c.style.borderColor = sel ? MC[c.dataset.method] : '';
    });
    const qc = document.getElementById('queryParams');
    qc.innerHTML = '';
    (ep?.query ?? []).forEach(q => addParam('queryParams', q.key, q.value));
    new bootstrap.Modal(document.getElementById('endpointModal')).show();
}

function selectMethod(chip) {
    document.querySelectorAll('.method-chip').forEach(c => {
        c.classList.remove('selected');
        c.style.background = c.style.color = c.style.borderColor = '';
    });
    chip.classList.add('selected');
    chip.style.background = chip.style.borderColor = MC[chip.dataset.method] ?? '#6c757d';
    chip.style.color = '#fff';
}

function addParam(cId, key = '', value = '') {
    const c = document.getElementById(cId),
        r = document.createElement('div');
    r.className = 'param-row';
    r.innerHTML = `<input type="text" class="form-control param-key" placeholder="key" value="${esc(key)}">
    <input type="text" class="form-control param-val" placeholder="value" value="${esc(value)}">
    <button class="btn-remove" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>`;
    c.appendChild(r);
}
async function saveEndpoint() {
    const editId = document.getElementById('epEditId').value;
    const method = document.querySelector('.method-chip.selected')?.dataset.method ?? 'GET';
    const name = document.getElementById('epName').value.trim();
    const url = document.getElementById('epUrl').value.trim();
    if (!name || !url) {
        toast('Name and URL required', 'error');
        return;
    }
    if (!S.activeId) {
        toast('Load a collection first', 'error');
        return;
    }
    const query = [...document.querySelectorAll('#queryParams .param-row')].map(r => ({
        key: r.querySelector('.param-key').value,
        value: r.querySelector('.param-val').value,
        disabled: false,
        description: '',
    })).filter(p => p.key);
    const rawBody = document.getElementById('epBody').value.trim();
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
        body: rawBody ? {
            mode: 'raw',
            raw: rawBody
        } : {},
    };
    try {
        let res, d;
        if (editId) {
            res = await fetch(`${API}/collections/${S.activeId}/endpoints/${editId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(payload)
            });
            d = await res.json();
            if (!res.ok) throw new Error(d.message ?? 'Update failed');
            toast('Updated "' + name + '"', 'success');
        } else {
            res = await fetch(`${API}/collections/${S.activeId}/endpoints`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify(payload)
            });
            d = await res.json();
            if (!res.ok) throw new Error(d.message ?? 'Create failed');
            toast('Created "' + name + '"', 'success');
        }
        bootstrap.Modal.getInstance(document.getElementById('endpointModal'))?.hide();
        await loadCollection(S.activeId);
    } catch (err) {
        toast('Save failed: ' + err.message, 'error');
    }
}

function editEndpoint(id) {
    const ep = findEp(id);
    if (ep) openEndpointModal(ep);
}
async function deleteEndpoint(id) {
    if (!confirm('Delete this endpoint?')) return;
    try {
        const res = await fetch(`${API}/collections/${S.activeId}/endpoints/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrf
            }
        });
        if (!res.ok) throw new Error('Delete failed');
        toast('Endpoint deleted', 'success');
        document.getElementById('ep-' + id)?.remove();
        if (S.activeData) {
            S.activeData.endpoints = S.activeData.endpoints.filter(e => e.id !== id);
            updateProgress();
        }
    } catch (err) {
        toast('Delete failed: ' + err.message, 'error');
    }
}

/* ============================================================
   USERS MODAL
============================================================ */
async function openUsersModal() {
    if (!can('manage_users')) {
        toast('Admin only', 'error');
        return;
    }
    // Populate collection dropdown
    const sel = document.getElementById('newUserCollection');
    sel.innerHTML = '<option value="">All collections</option>';
    S.collections.forEach(c => {
        const o = document.createElement('option');
        o.value = c.id;
        o.textContent = c.name;
        sel.appendChild(o);
    });
    updatePermChips();
    await loadUsersList();
    new bootstrap.Modal(document.getElementById('usersModal')).show();
}

function updatePermChips() {
    const role = document.getElementById('newUserRole')?.value ?? 'viewer';
    const defaults = {
        admin: ['read', 'write', 'run', 'ai', 'manage_users'],
        editor: ['read', 'write', 'run', 'ai'],
        viewer: ['read']
    };
    const def = defaults[role] ?? ['read'];
    const cont = document.getElementById('permChips');
    cont.innerHTML = '';
    ALLPERMS.forEach(p => {
        const lbl = document.createElement('label');
        lbl.style.cssText =
            'display:flex;align-items:center;gap:5px;cursor:pointer;font-size:.75rem;color:var(--text);background:var(--bg3);border:1px solid var(--border);border-radius:5px;padding:4px 10px;';
        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.value = p;
        cb.checked = def.includes(p);
        cb.addEventListener('change', () => {
            lbl.style.borderColor = cb.checked ? 'rgba(88,166,255,.5)' : 'var(--border)';
            lbl.style.color = cb.checked ? 'var(--accent)' : 'var(--text)';
        });
        if (cb.checked) {
            lbl.style.borderColor = 'rgba(88,166,255,.5)';
            lbl.style.color = 'var(--accent)';
        }
        lbl.appendChild(cb);
        lbl.appendChild(document.createTextNode(' ' + p));
        cont.appendChild(lbl);
    });
}

async function loadUsersList() {
    const el = document.getElementById('usersList');
    el.innerHTML = '<div class="runner-loading"><div class="spinner"></div> Loading…</div>';
    try {
        const r = await apiFetch('/users');
        const users = r.data ?? [];
        el.innerHTML = '';
        if (!users.length) {
            el.innerHTML = '<p style="color:var(--muted);font-size:.8rem">No users yet.</p>';
            return;
        }
        const rc = {
            admin: '#dc3545',
            editor: '#007bff',
            viewer: '#6c757d'
        };
        users.forEach(u => {
            const row = document.createElement('div');
            row.className = 'user-row';
            const perms = (u.permissions ?? []).map(p => `<span class="perm-chip on">${p}</span>`).join(
                '');
            row.innerHTML = `
        <div class="u-avatar-lg" style="background:${rc[u.role] ?? '#6c757d'}">${u.name.charAt(0).toUpperCase()}</div>
        <div style="flex:1;min-width:0">
          <div style="font-size:.81rem;font-weight:600">${esc(u.name)}
            <span class="role-chip role-${u.role}">${u.role}</span>
            ${!u.is_active ? '<span style="color:var(--a2);font-size:.64rem"> inactive</span>' : ''}
          </div>
          <div style="font-size:.71rem;color:var(--muted)">${esc(u.email)}</div>
          ${u.default_collection_id ? `<div style="font-size:.67rem;color:var(--muted)">Collection: ${esc(u.default_collection_id.slice(0, 8))}…</div>` : ''}
          <div style="margin-top:3px">${perms || '<span class="perm-chip">none</span>'}</div>
        </div>
        <div style="display:flex;gap:4px;flex-shrink:0">
          <button class="card-btn" onclick="toggleUserActive(${u.id},${u.is_active})" title="${u.is_active ? 'Deactivate' : 'Activate'}">
            <i class="bi bi-${u.is_active ? 'toggle-on text-success' : 'toggle-off'}"></i>
          </button>
          <button class="card-btn danger" onclick="deleteUser(${u.id},this)">
            <i class="bi bi-trash3"></i>
          </button>
        </div>`;
            el.appendChild(row);
        });
    } catch (err) {
        el.innerHTML = `<p style="color:var(--a2);font-size:.8rem">Error: ${esc(err.message)}</p>`;
    }
}

async function createUser() {
    const name = document.getElementById('newUserName').value.trim();
    const email = document.getElementById('newUserEmail').value.trim();
    const pass = document.getElementById('newUserPassword').value;
    const role = document.getElementById('newUserRole').value;
    const colId = document.getElementById('newUserCollection').value || null;
    const perms = [...document.querySelectorAll('#permChips input:checked')].map(c => c.value);
    if (!name || !email || !pass) {
        toast('Name, email and password required', 'error');
        return;
    }
    try {
        const r = await fetch(`${API}/users`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name,
                email,
                password: pass,
                role,
                permissions: perms,
                default_collection_id: colId
            })
        });
        const d = await r.json();
        if (!r.ok) throw new Error(d.error ?? (d.details ? Object.values(d.details).flat().join(', ') :
            'Create failed'));
        toast('"' + name + '" added', 'success');
        document.getElementById('newUserName').value = '';
        document.getElementById('newUserEmail').value = '';
        document.getElementById('newUserPassword').value = '';
        await loadUsersList();
    } catch (err) {
        toast('Failed: ' + err.message, 'error');
    }
}

async function toggleUserActive(id, current) {
    try {
        await apiFetch('/users/' + id, {
            method: 'PUT',
            body: JSON.stringify({
                is_active: !current
            })
        });
        toast(current ? 'User deactivated' : 'User activated', 'success');
        await loadUsersList();
    } catch (err) {
        toast(err.message, 'error');
    }
}

async function deleteUser(id, btn) {
    if (!confirm('Delete this developer account?')) return;
    try {
        await apiFetch('/users/' + id, {
            method: 'DELETE'
        });
        toast('User deleted', 'success');
        btn.closest('.user-row')?.remove();
    } catch (err) {
        toast(err.message, 'error');
    }
}

/* ============================================================
   PROFILE MODAL
============================================================ */
function openProfileModal() {
    const u = S.user;
    if (!u) return;
    const rc = {
        admin: '#dc3545',
        editor: '#007bff',
        viewer: '#6c757d'
    };
    document.getElementById('profileInfo').innerHTML = `
    <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg3);border:1px solid var(--border);border-radius:8px">
      <div class="u-avatar-lg" style="background:${rc[u.role] ?? '#6c757d'};width:44px;height:44px;font-size:1.1rem">${u.name.charAt(0).toUpperCase()}</div>
      <div>
        <div style="font-size:.95rem;font-weight:700">${esc(u.name)}</div>
        <div style="font-size:.75rem;color:var(--muted)">${esc(u.email)}</div>
        <span class="role-chip role-${u.role}" style="margin-top:4px;display:inline-block">${u.role}</span>
      </div>
    </div>
    <div style="margin-top:10px">
      <div style="font-size:.71rem;color:var(--muted);margin-bottom:5px">Permissions</div>
      <div>${(u.permissions ?? []).map(p => `<span class="perm-chip on">${p}</span>`).join('') || '<span style="color:var(--muted);font-size:.75rem">none assigned</span>'}</div>
    </div>`;
    document.getElementById('curPwd').value = '';
    document.getElementById('newPwd').value = '';
    new bootstrap.Modal(document.getElementById('profileModal')).show();
}

async function changePassword() {
    const cur = document.getElementById('curPwd').value;
    const nw = document.getElementById('newPwd').value;
    if (!cur || !nw) {
        toast('Enter both passwords', 'error');
        return;
    }
    try {
        const r = await fetch(`${API}/users/change-password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                current_password: cur,
                new_password: nw
            })
        });
        const d = await r.json();
        if (!r.ok) throw new Error(d.error ?? 'Failed');
        toast('Password changed', 'success');
        bootstrap.Modal.getInstance(document.getElementById('profileModal'))?.hide();
    } catch (err) {
        toast(err.message, 'error');
    }
}

/* ============================================================
   MOBILE SIDEBAR
============================================================ */
function toggleSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    const cl = document.getElementById('sidebarClose');
    const isOpen = sb.classList.toggle('open');
    ov.classList.toggle('open', isOpen);
    if (cl) cl.style.display = isOpen ? 'block' : 'none';
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

function initMobileLayout() {
    const mq = window.matchMedia('(max-width:768px)');
    const apply = () => {
        const cl = document.getElementById('sidebarClose');
        if (cl) cl.style.display = mq.matches ? 'block' : 'none';
        if (!mq.matches) {
            document.getElementById('sidebar')?.classList.remove('open');
            document.getElementById('sidebarOverlay')?.classList.remove('open');
            document.body.style.overflow = '';
        }
    };
    apply();
    mq.addEventListener('change', apply);
}

/* ============================================================
   SEARCH + SCROLL
============================================================ */
function bindSearch() {
    document.getElementById('searchBox').addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.endpoint-card').forEach(c => {
            c.classList.toggle('search-hidden', !!q && !c.textContent.toLowerCase().includes(q));
        });
        document.querySelectorAll('.api-group').forEach(g => {
            g.style.display = (q && !g.querySelectorAll('.endpoint-card:not(.search-hidden)')
                .length) ? 'none' : '';
        });
    });
}

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


/* ============================================================
   CHAT — Real-time collaboration with polling + email notify
============================================================ */
const CHAT = {
    open: false,
    channel: 'general',
    channelKey: null,
    lastId: 0,
    pollTimer: null,
    pollInterval: 3000, // poll every 3s
    users: [], // mentionable users
    unread: 0,
    mentionQuery: '',
    mentionVisible: false,
    roleColors: {
        admin: '#dc3545',
        editor: '#007bff',
        viewer: '#6c757d'
    },
};

function toggleChat() {
    CHAT.open = !CHAT.open;
    const drawer = document.getElementById('chatDrawer');
    const main = document.getElementById('mainContent');
    const btn = document.getElementById('btnChat');
    drawer.classList.toggle('open', CHAT.open);
    main.classList.toggle('chat-open', CHAT.open);
    btn.classList.toggle('open', CHAT.open);
    if (CHAT.open) {
        clearChatBadge();
        loadChatMessages();
        startChatPoll();
        loadChatUsers();
        // auto-scroll to bottom
        setTimeout(() => scrollChatBottom(), 100);
    } else {
        stopChatPoll();
    }
}

function switchChatChannel(channel, channelKey, btn) {
    CHAT.channel = channel;
    CHAT.channelKey = channelKey ?? (channel === 'collection' ? S.activeId : null);
    CHAT.lastId = 0;
    document.querySelectorAll('.chat-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('chatMessages').innerHTML = '';
    loadChatMessages();
}

async function loadChatMessages(loadMore = false) {
    const el = document.getElementById('chatMessages');
    try {
        let url = `${API}/chat/messages?channel=${CHAT.channel}&limit=50`;
        if (CHAT.channelKey) url += '&channel_key=' + encodeURIComponent(CHAT.channelKey);
        if (loadMore && CHAT.lastId) url += '&before_id=' + CHAT.lastId;

        const res = await apiFetch(url.replace(API, ''));
        const msgs = res.data ?? [];

        if (msgs.length === 0 && !loadMore) {
            el.innerHTML = `<div class="chat-empty">
        <i class="bi bi-chat-dots" style="font-size:2rem;opacity:.3"></i>
        <span>No messages yet.<br>Start the conversation!</span>
      </div>`;
            return;
        }

        // Track first id for load-more
        if (msgs.length && !loadMore) {
            CHAT.lastId = msgs[0].id;
        }

        if (loadMore) {
            // Prepend older messages
            const frag = document.createDocumentFragment();
            if (res.has_more) {
                const lb = document.createElement('div');
                lb.className = 'chat-load-more';
                lb.textContent = 'Load older messages';
                lb.onclick = () => loadChatMessages(true);
                frag.appendChild(lb);
            }
            msgs.forEach(m => frag.appendChild(buildChatMsg(m)));
            el.insertBefore(frag, el.firstChild);
            CHAT.lastId = msgs[0]?.id ?? CHAT.lastId;
        } else {
            el.innerHTML = '';
            if (res.has_more) {
                const lb = document.createElement('div');
                lb.className = 'chat-load-more';
                lb.textContent = 'Load older messages';
                lb.onclick = () => loadChatMessages(true);
                el.appendChild(lb);
            }
            msgs.forEach(m => el.appendChild(buildChatMsg(m)));
            // Set lastPollId to newest message id
            if (msgs.length) CHAT.pollLastId = msgs[msgs.length - 1].id;
            scrollChatBottom();
        }
    } catch (err) {
        console.warn('Chat load failed:', err.message);
    }
}

function buildChatMsg(m) {
    const isOwn = m.user_id === S.user?.id;
    const isSys = m.type === 'system';
    if (isSys) {
        const d = document.createElement('div');
        d.className = 'chat-sys';
        d.textContent = m.message;
        return d;
    }
    const div = document.createElement('div');
    div.className = 'chat-msg' + (isOwn ? ' own' : '');
    div.dataset.msgId = m.id;
    const color = CHAT.roleColors[m.user_role] ?? '#6c757d';
    const initials = m.user_name.charAt(0).toUpperCase();
    // Highlight @mentions
    const msgText = m.message.replace(/@(\w[\w\s]*?)(?=\s|$|@)/g, '<span class="chat-mention">@$1</span>');
    div.innerHTML = `
    <div class="chat-msg-header">
      <div class="chat-msg-avatar" style="background:${color}">${initials}</div>
      <span class="chat-msg-name">${esc(m.user_name)}</span>
      <span class="chat-msg-time">${esc(m.time_ago)}</span>
      ${isOwn ? `<button class="btn-rm" onclick="deleteChatMsg(${m.id},this)" title="Delete" style="font-size:.6rem;padding:1px 5px;margin-left:4px"><i class="bi bi-x"></i></button>` : ''}
    </div>
    <div class="chat-msg-body">${msgText}</div>`;
    return div;
}

/* ── POLL for new messages ────────────────────────────── */
CHAT.pollLastId = 0;

function startChatPoll() {
    stopChatPoll();
    CHAT.pollTimer = setInterval(pollNewMessages, CHAT.pollInterval);
}

function stopChatPoll() {
    if (CHAT.pollTimer) {
        clearInterval(CHAT.pollTimer);
        CHAT.pollTimer = null;
    }
}

async function pollNewMessages() {
    try {
        let url = `/chat/poll?channel=${CHAT.channel}&after_id=${CHAT.pollLastId}`;
        if (CHAT.channelKey) url += '&channel_key=' + encodeURIComponent(CHAT.channelKey);
        const res = await apiFetch(url);
        const msgs = res.data ?? [];
        if (msgs.length) {
            CHAT.pollLastId = res.last_id;
            const el = document.getElementById('chatMessages');
            // Remove empty state
            el.querySelector('.chat-empty')?.remove();
            const playSoundFor = msgs.some(m => m.user_id !== S.user?.id);
            const wasAtBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 60;
            msgs.forEach(m => {
                // Skip if already rendered
                if (!el.querySelector(`[data-msg-id="${m.id}"]`)) {
                    el.appendChild(buildChatMsg(m));
                    // Unread badge if chat is closed or user scrolled up
                    if (!CHAT.open || !wasAtBottom) {
                        CHAT.unread++;
                        showChatBadge(CHAT.unread);
                    }
                }
            });
            if (playSoundFor) playChatSound();
            if (wasAtBottom) scrollChatBottom();
        }
    } catch { }
}

/* ── SEND ─────────────────────────────────────────────── */
async function sendChatMessage() {
    const ta = document.getElementById('chatInput');
    const msg = ta.value.trim();
    if (!msg) return;
    const btn = document.getElementById('chatSendBtn');
    btn.disabled = true;
    try {
        // Extract @mention user ids
        const mentionIds = [];
        const mentionRegex = /@([\w ]+?)(?=\s|$|@)/g;
        let match;
        while ((match = mentionRegex.exec(msg)) !== null) {
            const name = match[1].trim().toLowerCase();
            const u = CHAT.users.find(u => u.name.toLowerCase() === name || u.name.toLowerCase().startsWith(
                name));
            if (u && !mentionIds.includes(u.id)) mentionIds.push(u.id);
        }
        const payload = {
            message: msg,
            channel: CHAT.channel,
            channel_key: CHAT.channelKey,
            collection_id: S.activeId,
            mentions: mentionIds,
        };
        const res = await fetch(`${API}/chat/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const d = await res.json();
        if (!res.ok) throw new Error(d.error ?? 'Send failed');
        ta.value = '';
        ta.style.height = '';
        hideMentionDropdown();
        // Append own message immediately
        const el = document.getElementById('chatMessages');
        el.querySelector('.chat-empty')?.remove();
        el.appendChild(buildChatMsg(d.data));
        CHAT.pollLastId = d.data.id;
        scrollChatBottom();
    } catch (err) {
        toast('Message failed: ' + err.message, 'error');
    } finally {
        btn.disabled = false;
    }
}

/* ── KEYBOARD SHORTCUTS ───────────────────────────────── */
function chatKeyDown(e) {
    if (CHAT.mentionVisible) {
        const items = [...document.querySelectorAll('.chat-mention-item')];
        const active = document.querySelector('.chat-mention-item.active-hint');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const i = items.indexOf(active);
            items[(i + 1) % items.length]?.classList.add('active-hint');
            active?.classList.remove('active-hint');
            return;
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            const i = items.indexOf(active);
            items[(i - 1 + items.length) % items.length]?.classList.add('active-hint');
            active?.classList.remove('active-hint');
            return;
        }
        if (e.key === 'Tab' || e.key === 'Enter') {
            e.preventDefault();
            const sel = active ?? items[0];
            if (sel) sel.click();
            return;
        }
        if (e.key === 'Escape') {
            hideMentionDropdown();
            return;
        }
    }
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendChatMessage();
    }
}

function chatInputChange(ta) {
    // Auto-resize
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 90) + 'px';
    // @mention detection
    const val = ta.value,
        cursor = ta.selectionStart;
    const before = val.substring(0, cursor);
    const match = before.match(/@([\w ]*)$/);
    if (match) {
        CHAT.mentionQuery = match[1];
        showMentionDropdown(match[1]);
    } else {
        hideMentionDropdown();
    }
}

function showMentionDropdown(query) {
    const dd = document.getElementById('chatMentionDropdown');
    const filtered = CHAT.users.filter(u => u.name.toLowerCase().includes(query.toLowerCase())).slice(0, 6);
    if (!filtered.length) {
        hideMentionDropdown();
        return;
    }
    dd.innerHTML = '';
    filtered.forEach(u => {
        const item = document.createElement('div');
        item.className = 'chat-mention-item';
        const color = CHAT.roleColors[u.role] ?? '#6c757d';
        item.innerHTML = `<div class="chat-msg-avatar" style="background:${color};width:18px;height:18px;font-size:.55rem">${u.name.charAt(0).toUpperCase()}</div>
      <span style="font-size:.74rem;font-weight:600">${esc(u.name)}</span>
      <span style="font-size:.67rem;color:var(--muted)">${u.role}</span>`;
        item.onclick = () => insertMention(u.name);
        dd.appendChild(item);
    });
    dd.style.display = 'block';
    CHAT.mentionVisible = true;
    dd.querySelector('.chat-mention-item')?.classList.add('active-hint');
}

function hideMentionDropdown() {
    document.getElementById('chatMentionDropdown').style.display = 'none';
    CHAT.mentionVisible = false;
}

function insertMention(name) {
    const ta = document.getElementById('chatInput');
    const val = ta.value,
        cursor = ta.selectionStart;
    const before = val.substring(0, cursor).replace(/@[\w ]*$/, '@' + name + ' ');
    ta.value = before + val.substring(cursor);
    ta.focus();
    ta.selectionStart = ta.selectionEnd = before.length;
    hideMentionDropdown();
}

async function loadChatUsers() {
    try {
        const r = await apiFetch('/chat/users');
        CHAT.users = r.data ?? [];
        updateChatPresenceUI();
    } catch { }
}

function updateChatPresenceUI() {
    const dot = document.getElementById('chatOnlineDot');
    const countEl = document.getElementById('chatOnlineCount');
    if (!dot || !countEl) return;
    const users = CHAT.users || [];
    // determine online flag by common keys
    const onlineCount = users.filter(u => u.online === true || u.is_online === true || (u.status && u.status === 'online')).length;
    if (onlineCount > 0) {
        dot.style.display = 'inline-block';
        dot.style.background = 'var(--green)';
        dot.style.boxShadow = '0 0 6px var(--green)';
        countEl.textContent = `${onlineCount} online`;
    } else {
        dot.style.display = 'inline-block';
        dot.style.background = 'var(--muted)';
        dot.style.boxShadow = 'none';
        countEl.textContent = '';
    }
}

// Emoji picker
const EMOJIS = ['😀','😁','😂','🤣','😊','😍','😎','😅','😢','😡','👍','👎','🙏','🔥','🎉','💯','✨','🤝','🤖','📎','🧠','🚀','📣','🛠️','🔒','🔔','📌','📝','⚠️','✅','❌'];

function buildEmojiPicker() {
    let wrap = document.getElementById('chatEmojiPicker');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'chatEmojiPicker';
        wrap.className = 'chat-emoji-picker';
        document.body.appendChild(wrap);
    }
    // Move existing picker into body if not already
    if (wrap.parentElement !== document.body) document.body.appendChild(wrap);
    if (wrap.dataset.built) return;
    // ensure hidden by default
    wrap.style.display = 'none';
    wrap.style.position = 'fixed';
    wrap.style.zIndex = 1400;
    EMOJIS.forEach(e => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = e;
        btn.onclick = () => insertEmoji(e);
        wrap.appendChild(btn);
    });
    wrap.dataset.built = '1';
}

function toggleEmojiPicker() {
    const p = document.getElementById('chatEmojiPicker');
    const btn = document.getElementById('chatEmojiBtn');
    if (!p || !btn) return;
    const comp = window.getComputedStyle(p);
    const isHidden = comp.display === 'none' || p.style.display === 'none' || p.hidden;
    if (!isHidden) {
        p.style.display = 'none';
        return;
    }
    buildEmojiPicker();
    // show first so dimensions are measurable
    p.style.display = 'grid';
    p.style.width = p.style.width || '260px';
    requestAnimationFrame(() => {
        const rect = btn.getBoundingClientRect();
        const pw = p.offsetWidth || 260;
        const ph = p.offsetHeight || 200;
        // Position above the button by default, try to keep within viewport
        let left = rect.left;
        if (left + pw > window.innerWidth - 8) left = window.innerWidth - pw - 8;
        if (left < 8) left = 8;
        let top = rect.top - ph - 8;
        // if not enough space above, place above input area (below)
        if (top < 8) top = rect.bottom + 8;
        p.style.left = left + 'px';
        p.style.top = top + 'px';
    });
}

function insertEmoji(emoji) {
    const ta = document.getElementById('chatInput');
    if (!ta) return;
    const start = ta.selectionStart || 0;
    const end = ta.selectionEnd || 0;
    ta.value = ta.value.substring(0, start) + emoji + ta.value.substring(end);
    ta.focus();
    const pos = start + emoji.length;
    ta.selectionStart = ta.selectionEnd = pos;
}

function initChatAudio() {
    try {
        const audio = document.createElement('audio');
        audio.id = 'chatMsgAudio';
        audio.preload = 'auto';
        audio.style.display = 'none';
        // try common asset names (if present on server)
        const srcs = ['/assets/msg.mp3', '/assets/msg.ogg'];
        srcs.forEach(s => {
            const src = document.createElement('source');
            src.src = s;
            audio.appendChild(src);
        });
        document.body.appendChild(audio);
        CHAT.audioEl = audio;
        CHAT.muted = localStorage.getItem('chatMuted') === '1';
        audio.muted = !!CHAT.muted;
        updateMuteButton();
    } catch (err) {
        CHAT.audioEl = null;
    }
}

function playChatSound() {
    try {
        if (CHAT.muted) return;
        if (CHAT.audioEl) {
            const p = CHAT.audioEl.play();
            if (p && p.catch) p.catch(() => {
                // fallback to WebAudio if play() blocked
                playChatTone();
            });
            return;
        }
        playChatTone();
    } catch (err) { }
}

function playChatTone() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        const ctx = new AudioCtx();
        const o = ctx.createOscillator();
        const g = ctx.createGain();
        o.type = 'sine';
        o.frequency.value = 750;
        g.gain.value = 0.02;
        o.connect(g);
        g.connect(ctx.destination);
        o.start();
        setTimeout(() => { try { o.stop(); ctx.close(); } catch {} }, 120);
    } catch { }
}

function toggleChatMute() {
    CHAT.muted = !CHAT.muted;
    localStorage.setItem('chatMuted', CHAT.muted ? '1' : '0');
    if (CHAT.audioEl) CHAT.audioEl.muted = CHAT.muted;
    updateMuteButton();
}

function updateMuteButton() {
    const b = document.getElementById('chatMuteBtn');
    if (!b) return;
    if (CHAT.muted) b.classList.add('muted');
    else b.classList.remove('muted');
    const ic = b.querySelector('i');
    if (ic) ic.className = CHAT.muted ? 'bi bi-bell-slash' : 'bi bi-bell';
}

async function deleteChatMsg(id, btn) {
    if (!confirm('Delete this message?')) return;
    try {
        await apiFetch('/chat/messages/' + id, {
            method: 'DELETE'
        });
        btn.closest('.chat-msg')?.remove();
    } catch (err) {
        toast('Delete failed: ' + err.message, 'error');
    }
}

function showChatBadge(count) {
    const b = document.getElementById('chatBadge');
    b.textContent = count > 9 ? '9+' : count;
    b.classList.add('show');
}

function clearChatBadge() {
    CHAT.unread = 0;
    const b = document.getElementById('chatBadge');
    b.textContent = '';
    b.classList.remove('show');
}

function scrollChatBottom() {
    const el = document.getElementById('chatMessages');
    if (el) el.scrollTop = el.scrollHeight;
}

// Start background poll even when chat is closed (for badge updates)
// Uses a slower interval when closed
let _bgChatTimer = null;

function startBgChatPoll() {
    _bgChatTimer = setInterval(async () => {
        if (CHAT.open) return; // foreground poll handles it
        try {
            let url = `/chat/poll?channel=general&after_id=${CHAT.pollLastId}`;
            const r = await apiFetch(url);
            const newMsgs = (r.data ?? []);
            if (newMsgs.length) {
                CHAT.pollLastId = r.last_id;
                CHAT.unread += newMsgs.length;
                showChatBadge(CHAT.unread);
                const playSoundFor = newMsgs.some(m => m.user_id !== S.user?.id);
                if (playSoundFor) playChatSound();
            }
        } catch { }
    }, 10000); // every 10s in background
}

/* ============================================================
   UTILITIES
============================================================ */
async function apiFetch(path, opts = {}) {
    const res = await fetch(API + path, {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            ...(opts.headers ?? {})
        },
        ...opts,
    });
    const d = await res.json();
    if (!res.ok) throw new Error(d.message ?? d.error ?? 'HTTP ' + res.status);
    return d;
}

function findEp(id) {
    return (S.activeData?.endpoints ?? []).find(e => e.id === id);
}

function toast(msg, type = 'success') {
    const icons = {
        success: 'bi-check2-circle',
        error: 'bi-exclamation-triangle',
        ai: 'bi-stars'
    };
    const cols = {
        success: 'var(--green)',
        error: 'var(--a2)',
        ai: 'var(--ai)'
    };
    const el = document.createElement('div');
    el.className = 'app-toast ' + type;
    el.innerHTML =
        `<i class="bi ${icons[type] ?? 'bi-info-circle'}" style="color:${cols[type] ?? 'var(--accent)'}"></i>${esc(msg)}`;
    document.getElementById('toastStack').appendChild(el);
    setTimeout(() => el.remove(), 4500);
}

function esc(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g,
        '&quot;');
}

function slugify(s) {
    return String(s).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}

function mdLite(s) {
    return s.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/`(.*?)`/g, '<code>$1</code>').replace(/\n/g,
        '<br>');
}
