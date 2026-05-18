<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Sora:wght@300;400;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="/css/app.css">

</head>

<body>
    <div id="initLoader">
        <div class="page-loader">
            <div class="spinner"></div>
            <span>Loading app…</span>
        </div>
    </div>

    <!-- ══ LOGIN PAGE ══════════════════════════════════════════ -->
    <div id="loginPage">
        <div class="login-card">
            <div class="login-logo">
                <div class="spark">ST</div>
                <div>
                    <h1>{{ config('app.name') }}</h1>
                    <p>Dev Doc Platform</p>
                </div>
            </div>
            <div class="login-error" id="loginError"></div>
            <div class="login-field">
                <label>Email address</label>
                <input type="email" id="loginEmail" placeholder="you@company.dev" autocomplete="email">
            </div>
            <div class="login-field login-password-field">
                <label>Password</label>
                <div class="password-input-wrap">
                    <input type="password" id="loginPassword" placeholder="••••••••" autocomplete="current-password">
                    <button type="button" class="password-toggle-btn" id="loginPasswordToggle" aria-label="Show password" onclick="toggleLoginPasswordVisibility()">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <button class="login-btn" id="loginBtn" onclick="doLogin()">Sign In</button>
            <div class="login-demo">
                <strong>Demo account:</strong><br>
                guest@company.dev / Guest1234 (Viewer)
            </div>
            <div class="login-footer">Contact your admin for full access &nbsp;·&nbsp; <span
                    style="color:var(--accent)">{{ config('app.name') }}</span></div>
        </div>
    </div>

    <!-- ══ APP SHELL ══════════════════════════════════════════ -->
    <div id="appShell">

        <!-- TOP NAV -->
        <nav class="top-nav">
            <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
            <a class="nav-logo" href="#"><span class="logo-spark">ST</span> {{ config('app.name') }}</a>
            <span class="nav-sep">|</span>

            <!-- Collection selector — persists across reloads -->
            <div class="nav-pill collection-pill" title="Select collection">
                <span class="nav-pill-label">COLLECTION</span>
                <select id="collectionSelect" onchange="onCollectionChange()">
                    <option value="">— Select —</option>
                </select>
            </div>

            <!-- Base URL — persists in localStorage, replaces { base_url } in all requests -->
            <div class="nav-pill base-url-pill" title="Base URL — replaces { base_url } in all runner requests">
                <span class="nav-pill-label">BASE URL</span>
                <input id="baseUrlInput" type="text" placeholder="https://api.example.com" style="min-width:140px">
                <button class="nav-pill-save" onclick="saveBaseUrl()" title="Save base URL"><i
                        class="bi bi-check-lg"></i></button>
            </div>

            <!-- Global Bearer Token — auto-injected into all runner requests -->
            <div class="nav-pill bearer-pill" title="Global Bearer Token (auto-injected into all requests)">
                <span class="nav-pill-label" style="color:var(--yellow)">BEARER</span>
                <input id="globalBearer" type="password" placeholder="your-token…">
                <button class="nav-pill-save" onclick="saveGlobalBearer()" title="Save token"><i
                        class="bi bi-check-lg"></i></button>
            </div>

            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input id="searchBox" type="text" placeholder="Search endpoints…">
            </div>
            <span class="ms-auto"></span>

            <span class="ollama-status">
                <span class="status-dot" id="statusDot"></span>
                <span id="statusLabel">…</span>
                <span class="model-badge" id="modelBadge" onclick="openModelModal()">—</span>
            </span>

            <button class="nav-btn ai-btn" id="btnAiAll" onclick="batchSummarizeAll()">
                <i class="bi bi-stars"></i><span class="btn-text"> AI All</span>
            </button>
            <button class="nav-btn" id="btnImport" onclick="openUploadModal()">
                <i class="bi bi-upload"></i><span class="btn-text"> Import</span>
            </button>
            <button class="nav-btn" id="btnAddEp" onclick="openEndpointModal()">
                <i class="bi bi-plus"></i><span class="btn-text"> Endpoint</span>
            </button>
            <button class="nav-btn" id="btnUsers" onclick="openUsersModal()" style="display:none">
                <i class="bi bi-people"></i><span class="btn-text"> Users</span>
            </button>
            <!-- Chat toggle -->
            <button class="nav-btn" id="themeToggleBtn" onclick="toggleTheme()" title="Toggle day/night">
                <i class="bi bi-moon-stars-fill"></i>
            </button>
            <div class="user-pill" onclick="openProfileModal()" title="Profile & sign out">
                <div class="u-avatar" id="navAvatar">?</div>
                <span id="navUserName">—</span>
                <span class="role-chip" id="navRoleChip">—</span>
            </div>
        </nav>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 15px 0">
                <div class="sidebar-heading" style="padding:0;margin:0">Folders</div>
                <button id="sidebarClose" onclick="toggleSidebar()"
                    style="display:none;background:transparent;border:none;color:var(--muted);font-size:.95rem;cursor:pointer"><i
                        class="bi bi-x-lg"></i></button>
            </div>
            <ul class="nav flex-column" id="sidebarNav"></ul>
            <div class="sidebar-actions">
                <button class="nav-btn" style="width:100%;justify-content:center;font-size:.73rem"
                    onclick="openSavedResponsesModal()">
                    <i class="bi bi-bookmark"></i> Saved Responses
                </button>
                <button class="nav-btn" id="sidebarImportBtn"
                    style="width:100%;justify-content:center;font-size:.73rem" onclick="openUploadModal()">
                    <i class="bi bi-file-earmark-arrow-up"></i> Import Collection
                </button>
            </div>
            <footer style="font-size:.72rem;color:var(--muted);text-align:center;padding:12px 18px;">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </footer>
        </aside>

        <!-- MAIN -->
        <div class="layout">
            <main class="main-content" id="mainContent">
                <div id="uploadZone" class="upload-zone" onclick="openUploadModal()"
                    ondragover="handleDragOver(event)" ondrop="handleDrop(event)">
                    <i class="bi bi-file-earmark-code upload-icon"></i>
                    <h3>Import a Postman Collection</h3>
                    <p>Drop a <code>.json</code> file here or click to browse.<br>Endpoints are parsed instantly and
                        AI-ready.</p>
                    <div style="margin-top:14px"><span class="nav-btn" style="display:inline-flex"><i
                                class="bi bi-upload"></i> Choose File</span></div>
                </div>
                <div id="collectionView" style="display:none">
                    <div class="api-hero">
                        <div class="hero-title" id="heroTitle">—</div>
                        <div class="hero-desc" id="heroDesc">—</div>
                        <div class="stats-row" id="heroStats"></div>
                        <div class="hero-ai-bar">
                            <span
                                style="font-size:.7rem;color:var(--ai);font-weight:600;display:flex;align-items:center;gap:4px"><i
                                    class="bi bi-stars"></i>AI</span>
                            <div class="batch-progress-wrap">
                                <div class="batch-progress-bar" id="batchBar"></div>
                            </div>
                            <span style="font-size:.7rem;color:var(--muted)" id="batchStatusText">—</span>
                            <button id="regenAllBtn" class="nav-btn ai-btn" onclick="batchSummarizeAll()"
                                style="padding:3px 10px;font-size:.7rem">
                                <i class="bi bi-arrow-clockwise"></i> Regen All
                            </button>
                        </div>
                    </div>
                    <div id="endpointGroups"></div>
                </div>
            </main>
        </div>
    </div><!-- /appShell -->

    <!-- ══ UPLOAD MODAL ══════════════════════════════════════ -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Import Collection</h5>
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
                        <div class="form-text" style="color:var(--muted);font-size:.7rem;margin-top:4px">Postman →
                            Collection → ⋯ → Export → v2.1</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary btn-sm" onclick="submitUpload()"><i
                            class="bi bi-upload me-1"></i>Import</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ ENDPOINT MODAL ═════════════════════════════════════ -->
    <div class="modal fade" id="endpointModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="epModalTitle"><i class="bi bi-plus-circle me-2"></i>New Endpoint</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="epEditId">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" id="epName" placeholder="Get user profile">
                    </div>
                    <div class="row mb-3">
                        <div class="col-5">
                            <label class="form-label">Method *</label>
                            <div class="method-select-row" id="methodChips">
                                <span class="method-chip selected" data-method="GET"
                                    style="background:#28a745;color:#fff" onclick="selectMethod(this)">GET</span>
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
                            placeholder="{ base_url }/api/users/:id">
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
                    <div class="mb-3">
                        <label class="form-label">Path Variables</label>
                        <div class="dynamic-params" id="pathVars"></div>
                        <button class="add-param-btn mt-2" onclick="addParam('pathVars')"><i class="bi bi-plus"></i>
                            Add</button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Query Parameters</label>
                        <div class="dynamic-params" id="queryParams"></div>
                        <button class="add-param-btn mt-2" onclick="addParam('queryParams')"><i
                                class="bi bi-plus"></i> Add</button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Headers</label>
                        <div class="dynamic-params" id="headerParams"></div>
                        <button class="add-param-btn mt-2" onclick="addParam('headerParams')"><i
                                class="bi bi-plus"></i> Add</button>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Body <span style="color:var(--muted);font-size:.7rem">(raw
                                JSON)</span></label>
                        <textarea class="form-control" id="epBody" rows="4" placeholder='{"key":"value"}'></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary btn-sm" onclick="saveEndpoint()"><i
                            class="bi bi-check2 me-1"></i>Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MODEL MODAL ════════════════════════════════════════ -->
    <div class="modal fade" id="modelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cpu me-2" style="color:var(--ai)"></i>AI Model</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height:70vh;overflow-y:auto">
                    <div class="sec-divider"><span>Local — Ollama</span></div>
                    <div id="localModelList" style="margin-bottom:6px"></div>
                    <p style="font-size:.69rem;color:var(--muted)"><i class="bi bi-terminal me-1"></i>Pull: <code
                            style="color:var(--ai)">ollama pull llama3</code></p>
                    <div class="sec-divider" style="margin-top:14px"><span>Cloud Models</span></div>
                    <p style="font-size:.72rem;color:var(--muted);margin-bottom:8px">Keys stored in browser only —
                        never sent to server.</p>
                    <div id="cloudModelList">
                        <!-- OpenAI -->
                        <div class="model-card" id="card-openai-gpt-4o"
                            onclick="selectCloudModel('openai','gpt-4o','GPT-4o')">
                            <div class="model-provider"
                                style="background:linear-gradient(135deg,#10a37f,#1a7f64);color:#fff">O</div>
                            <div class="model-info">
                                <div class="model-name">GPT-4o</div>
                                <div class="model-desc">OpenAI · Best quality</div>
                            </div>
                            <span id="chk-openai-gpt-4o" style="color:var(--green);display:none"><i
                                    class="bi bi-check2-circle"></i></span>
                        </div>
                        <div class="model-card" id="card-openai-gpt-4o-mini"
                            onclick="selectCloudModel('openai','gpt-4o-mini','GPT-4o Mini')">
                            <div class="model-provider"
                                style="background:linear-gradient(135deg,#10a37f,#1a7f64);color:#fff">O</div>
                            <div class="model-info">
                                <div class="model-name">GPT-4o Mini</div>
                                <div class="model-desc">OpenAI · Fast & cheap</div>
                            </div>
                            <span id="chk-openai-gpt-4o-mini" style="color:var(--green);display:none"><i
                                    class="bi bi-check2-circle"></i></span>
                        </div>
                        <div class="api-key-row" id="openai-key-row" style="display:none">
                            <input type="password" class="form-control" id="openaiKey" placeholder="sk-…">
                            <button class="btn btn-primary btn-sm" onclick="saveCloudKey('openai')">Save</button>
                        </div>
                        <!-- Anthropic -->
                        <div class="model-card" id="card-anthropic-claude-sonnet-4-5"
                            onclick="selectCloudModel('anthropic','claude-sonnet-4-5','Claude Sonnet 4.5')">
                            <div class="model-provider"
                                style="background:linear-gradient(135deg,#d97757,#c96442);color:#fff">A</div>
                            <div class="model-info">
                                <div class="model-name">Claude Sonnet 4.5</div>
                                <div class="model-desc">Anthropic · Excellent writer</div>
                            </div>
                            <span id="chk-anthropic-claude-sonnet-4-5" style="color:var(--green);display:none"><i
                                    class="bi bi-check2-circle"></i></span>
                        </div>
                        <div class="model-card" id="card-anthropic-claude-haiku-4-5"
                            onclick="selectCloudModel('anthropic','claude-haiku-4-5','Claude Haiku 4.5')">
                            <div class="model-provider"
                                style="background:linear-gradient(135deg,#d97757,#c96442);color:#fff">A</div>
                            <div class="model-info">
                                <div class="model-name">Claude Haiku 4.5</div>
                                <div class="model-desc">Anthropic · Fast</div>
                            </div>
                            <span id="chk-anthropic-claude-haiku-4-5" style="color:var(--green);display:none"><i
                                    class="bi bi-check2-circle"></i></span>
                        </div>
                        <div class="api-key-row" id="anthropic-key-row" style="display:none">
                            <input type="password" class="form-control" id="anthropicKey" placeholder="sk-ant-…">
                            <button class="btn btn-primary btn-sm" onclick="saveCloudKey('anthropic')">Save</button>
                        </div>
                        <!-- Groq — Llama3 cloud -->
                        <div class="model-card" id="card-groq-llama3-70b-8192"
                            onclick="selectCloudModel('groq','llama3-70b-8192','Llama3-70B (Groq)')">
                            <div class="model-provider"
                                style="background:linear-gradient(135deg,#f55036,#c1220a);color:#fff;font-size:.62rem;font-family:'JetBrains Mono',monospace;font-weight:700">
                                Gr</div>
                            <div class="model-info">
                                <div class="model-name">Llama3-70B (Groq)</div>
                                <div class="model-desc">Meta via Groq · Ultra-fast cloud</div>
                            </div>
                            <span id="chk-groq-llama3-70b-8192" style="color:var(--green);display:none"><i
                                    class="bi bi-check2-circle"></i></span>
                        </div>
                        <div class="model-card" id="card-groq-llama-3.1-8b-instant"
                            onclick="selectCloudModel('groq','llama-3.1-8b-instant','Llama3.1-8B (Groq)')">
                            <div class="model-provider"
                                style="background:linear-gradient(135deg,#f55036,#c1220a);color:#fff;font-size:.62rem;font-family:'JetBrains Mono',monospace;font-weight:700">
                                Gr</div>
                            <div class="model-info">
                                <div class="model-name">Llama3.1-8B (Groq)</div>
                                <div class="model-desc">Meta via Groq · Instant, free tier</div>
                            </div>
                            <span id="chk-groq-llama-3.1-8b-instant" style="color:var(--green);display:none"><i
                                    class="bi bi-check2-circle"></i></span>
                        </div>
                        <div class="api-key-row" id="groq-key-row" style="display:none">
                            <input type="password" class="form-control" id="groqKey"
                                placeholder="gsk_… Groq API key (free at console.groq.com)">
                            <button class="btn btn-primary btn-sm" onclick="saveCloudKey('groq')">Save</button>
                        </div>
                        <!-- Google -->
                        <div class="model-card" id="card-google-gemini-2.0-flash"
                            onclick="selectCloudModel('google','gemini-2.0-flash','Gemini 2.0 Flash')">
                            <div class="model-provider"
                                style="background:linear-gradient(135deg,#4285f4,#0f4c9f);color:#fff">G</div>
                            <div class="model-info">
                                <div class="model-name">Gemini 2.0 Flash</div>
                                <div class="model-desc">Google · Fastest, multimodal</div>
                            </div>
                            <span id="chk-google-gemini-2.0-flash" style="color:var(--green);display:none"><i
                                    class="bi bi-check2-circle"></i></span>
                        </div>
                        <div class="model-card" id="card-google-gemini-2.5-flash"
                            onclick="selectCloudModel('google','gemini-2.5-flash-preview-05-20','Gemini 2.5 Flash')">
                            <div class="model-provider"
                                style="background:linear-gradient(135deg,#4285f4,#0f4c9f);color:#fff">G</div>
                            <div class="model-info">
                                <div class="model-name">Gemini 2.5 Flash</div>
                                <div class="model-desc">Google · Most intelligent</div>
                            </div>
                            <span id="chk-google-gemini-2.5-flash" style="color:var(--green);display:none"><i
                                    class="bi bi-check2-circle"></i></span>
                        </div>
                        <div class="api-key-row" id="google-key-row" style="display:none">
                            <input type="password" class="form-control" id="googleKey"
                                placeholder="AIza… Google AI Studio key">
                            <button class="btn btn-primary btn-sm" onclick="saveCloudKey('google')">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ USERS MODAL ════════════════════════════════════════ -->
    <div class="modal fade" id="usersModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-people me-2"></i>Developer Access &amp; Roles</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="usersList" style="margin-bottom:14px"></div>
                    <div class="sec-divider"><span>Add Developer</span></div>
                    <div id="editingUserLabel" style="display:none;color:var(--accent);font-weight:600;margin:0 0 10px;">Editing user</div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" id="newUserName" placeholder="Jane Dev">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="newUserEmail"
                                placeholder="jane@company.dev">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" id="newUserPassword"
                                placeholder="Min 8 chars">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" id="newUserRole" onchange="updatePermChips()">
                                <option value="viewer">Viewer — read only</option>
                                <option value="editor">Editor — read + write + run + AI</option>
                                <option value="admin">Admin — full access</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Default Collection <span
                                    style="color:var(--muted);font-size:.7rem">(user can only see this
                                    collection)</span></label>
                            <select class="form-select" id="newUserCollection">
                                <option value="">All collections</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Permissions</label>
                            <div style="display:flex;flex-wrap:wrap;gap:6px" id="permChips"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-outline-secondary btn-sm d-none" id="cancelEditBtn" type="button" onclick="cancelEditUser()">Cancel</button>
                    <button class="btn btn-primary btn-sm" id="saveUserBtn" onclick="createUser()"><i
                            class="bi bi-person-plus me-1"></i><span id="saveUserBtnText">Add Developer</span></button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ PROFILE MODAL ══════════════════════════════════════ -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-circle me-2"></i>My Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="profileInfo" style="margin-bottom:14px"></div>
                    <div class="sec-divider"><span>Change Password</span></div>
                    <div class="mb-2">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="curPwd">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">New Password (min 8 chars)</label>
                        <input type="password" class="form-control" id="newPwd">
                    </div>
                </div>
                <div class="modal-footer" style="justify-content:space-between">
                    <button class="btn btn-outline-secondary btn-sm" onclick="doLogout()">
                        <i class="bi bi-box-arrow-right me-1"></i>Sign Out
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="changePassword()">Change Password</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ SAVED RESPONSES MODAL ══════════════════════════════ -->
    <div class="modal fade" id="savedRespModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bookmark-star me-2"></i>Saved Responses</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="savedRespBody">
                    <p style="color:var(--muted);font-size:.81rem">No saved responses yet. Run an endpoint and click
                        Save Response.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ FLOATING CHAT BUTTON ═════════════════════════════ -->
    <button class="floating-chat-btn" id="btnChat" onclick="toggleChat()" title="Team Chat">
        <i class="bi bi-chat-dots" style="color:var(--text)"></i>
        <span class="chat-badge" id="chatBadge"></span>
    </button>

    <!-- ══ CHAT DRAWER ════════════════════════════════════════ -->
    <div class="chat-drawer" id="chatDrawer">
        <!-- Header -->
        <div class="chat-header">
            <span class="chat-online-dot" id="chatOnlineDot" style="display:block"></span>
            <span class="chat-header-title"><i class="bi bi-chat-dots me-1" style="color:var(--accent)"></i>Team
                Chat</span>
            <span id="chatOnlineCount" style="font-size:.66rem;color:var(--muted)"></span>
            <button class="chat-mute-btn" id="chatMuteBtn" title="Toggle sound"><i class="bi bi-bell"
                    style="color:var(--text)"></i></button>
            <button class="chat-close-btn" onclick="toggleChat()" title="Close chat"><i
                    class="bi bi-x-lg"></i></button>
        </div>

        <!-- Channel tabs -->
        <div class="chat-channel-tabs">
            <button class="chat-tab active" id="tab-general" onclick="switchChatChannel('general',null,this)"><i
                    class="bi bi-hash"></i> General</button>
            <button class="chat-tab" id="tab-collection" onclick="switchChatChannel('collection',S.activeId,this)"><i
                    class="bi bi-folder2"></i> Collection</button>
        </div>

        <!-- Messages -->
        <div class="chat-messages" id="chatMessages">
            <div class="chat-empty">
                <i class="bi bi-chat-dots" style="font-size:2rem;opacity:.3"></i>
                <span>No messages yet.<br>Start the conversation!</span>
            </div>
        </div>

        <!-- Typing indicator -->
        <div class="chat-typing" id="chatTyping"></div>

        <!-- Input area -->
        <div class="chat-input-area">
            <div class="chat-mention-dropdown" id="chatMentionDropdown"></div>
            <div class="chat-input-row">
                <textarea class="chat-textarea" id="chatInput" placeholder="Message… use @name to mention" rows="1"
                    onkeydown="chatKeyDown(event)" oninput="chatInputChange(this)"></textarea>
                <button class="chat-emoji-btn" id="chatEmojiBtn" onclick="toggleEmojiPicker()" title="Insert emoji">
                    <i class="bi bi-emoji-smile" style="color:var(--text)"></i>
                </button>
                <div class="chat-emoji-picker" id="chatEmojiPicker" style="display:none"></div>
                <button class="chat-send-btn" id="chatSendBtn" onclick="sendChatMessage()">
                    <i class="bi bi-send-fill" style="color:var(--text)"></i>
                </button>
            </div>
            <div style="font-size:.62rem;color:var(--muted);margin-top:4px">
                <kbd
                    style="background:var(--text); color:var(--bg2); border:1px solid var(--border);border-radius:3px;padding:1px 4px;font-size:.62rem">Enter</kbd>&nbsp;
                send &nbsp;
                <kbd
                    style="background:var(--text); color:var(--bg2); border:1px solid var(--border);border-radius:3px;padding:1px 4px;font-size:.62rem">Shift+Enter</kbd>
                <span class="me" style="margin-right: 5.5rem">new line</span>
                <i class="bi bi-code-slash" style="color:var(--accent)"></i> <span
                    class="text-capitalize">stan</span>

            </div>
        </div>
    </div>

    <div class="toast-stack" id="toastStack"></div>
    <button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="bi bi-chevron-up"
            style="color:var(--text)"></i></button>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    @verbatim
        <script src="/js/app.js"></script>
    @endverbatim

</body>

</html>
