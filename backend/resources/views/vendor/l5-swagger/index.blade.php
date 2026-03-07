<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentationTitle }}</title>

    <link rel="stylesheet" type="text/css" href="{{ l5_swagger_asset($documentation, 'swagger-ui.css') }}">
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-32x32.png') }}" sizes="32x32"/>
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-16x16.png') }}" sizes="16x16"/>

    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin:0; background:#fafafa; }

        /* ✅ Mini Postman panel */
        #swagger-login-panel{
            position: fixed;
            top: 70px;
            right: 18px;
            z-index: 9999;
            width: 320px;
            background: #fff;
            border: 1px solid rgba(0,0,0,.12);
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,.12);
            padding: 12px;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
        }
        #swagger-login-panel h4{
            margin:0 0 10px 0;
            font-size: 14px;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }
        #swagger-login-panel .row{ margin-bottom: 8px; }
        #swagger-login-panel input{
            width: 100%;
            padding: 8px 10px;
            border: 1px solid rgba(0,0,0,.18);
            border-radius: 8px;
            outline: none;
        }
        #swagger-login-panel .btns{
            display:flex;
            gap:8px;
            margin-top: 8px;
        }
        #swagger-login-panel button{
            flex:1;
            padding: 9px 10px;
            border: 0;
            border-radius: 8px;
            cursor:pointer;
            font-weight: 600;
        }
        #btnLogin{ background:#16a34a; color:#fff; }
        #btnCopy{ background:#2563eb; color:#fff; }
        #btnClear{ background:#ef4444; color:#fff; }
        #swagger-login-panel .small{
            margin-top: 8px;
            font-size: 12px;
            color: #334155;
            word-break: break-all;
        }
        #swagger-login-panel .hint{
            font-size: 12px;
            color:#64748b;
            margin-top: 6px;
            line-height: 1.35;
        }
        #swagger-login-panel .pill{
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 999px;
            background:#f1f5f9;
            color:#0f172a;
        }
    </style>

    @if(config('l5-swagger.defaults.ui.display.dark_mode'))
        <style>
            body#dark-mode, #dark-mode .scheme-container { background: #1b1b1b; }
        </style>
    @endif
</head>

<body @if(config('l5-swagger.defaults.ui.display.dark_mode')) id="dark-mode" @endif>
<div id="swagger-ui"></div>

<!-- ✅ Mini Postman panel -->
<div id="swagger-login-panel">
    <h4>
        <span>Login (Sanctum)</span>
        <span class="pill" id="tokenState">No token</span>
    </h4>

    <div class="row">
        <input id="loginEmail" type="text" placeholder="email" autocomplete="username">
    </div>
    <div class="row">
        <input id="loginPass" type="password" placeholder="password" autocomplete="current-password">
    </div>

    <div class="btns">
        <button id="btnLogin" type="button">Login</button>
        <button id="btnCopy" type="button">Copy</button>
        <button id="btnClear" type="button">Clear</button>
    </div>

    <div class="small" id="tokenPreview"></div>
    <div class="hint">
        - Login จะยิง <b>/api/v1/auth/login</b><br>
        - Swagger จะแนบ <b>Authorization: Bearer &lt;token&gt;</b> ให้อัตโนมัติทุก request
    </div>
</div>

<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-bundle.js') }}"></script>
<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-standalone-preset.js') }}"></script>

<script>
window.onload = function() {
    const TOKEN_KEY = 'swagger_sanctum_token';

    const elState   = document.getElementById('tokenState');
    const elPrev    = document.getElementById('tokenPreview');
    const elEmail   = document.getElementById('loginEmail');
    const elPass    = document.getElementById('loginPass');
    const btnLogin  = document.getElementById('btnLogin');
    const btnCopy   = document.getElementById('btnCopy');
    const btnClear  = document.getElementById('btnClear');

    function getToken() {
        return localStorage.getItem(TOKEN_KEY) || '';
    }

    function setToken(token) {
        localStorage.setItem(TOKEN_KEY, token);
        renderToken();
        // พยายาม preauthorize ให้ lock icon เปลี่ยน (ถ้า swagger-ui รองรับ)
        try {
            if (window.ui && window.ui.preauthorizeApiKey) {
                window.ui.preauthorizeApiKey('bearerAuth', 'Bearer ' + token);
            }
        } catch (e) {}
    }

    function clearToken() {
        localStorage.removeItem(TOKEN_KEY);
        renderToken();
    }

    function renderToken() {
        const t = getToken();
        if (!t) {
            elState.textContent = 'No token';
            elPrev.textContent = '';
            return;
        }
        elState.textContent = 'Token ready';
        elPrev.textContent = 'Bearer ' + t.substring(0, 24) + '...';
    }

    const urls = [];
    @foreach($urlsToDocs as $title => $url)
        urls.push({name: "{{ $title }}", url: "{{ $url }}"});
    @endforeach

    const ui = SwaggerUIBundle({
        dom_id: '#swagger-ui',
        urls: urls,
        "urls.primaryName": "{{ $documentationTitle }}",

        supportedSubmitMethods: ['get','post','put','patch','delete','options','head'],
        operationsSorter: {!! isset($operationsSorter) ? '"' . $operationsSorter . '"' : 'null' !!},
        configUrl: {!! isset($configUrl) ? '"' . $configUrl . '"' : 'null' !!},
        validatorUrl: {!! isset($validatorUrl) ? '"' . $validatorUrl . '"' : 'null' !!},
        oauth2RedirectUrl: "{{ route('l5-swagger.'.$documentation.'.oauth2_callback', [], $useAbsolutePath) }}",

        // ✅ แนบ Authorization อัตโนมัติทุก request ถ้ามี token
        requestInterceptor: function(request) {
            const t = getToken();
            if (t) {
                request.headers['Authorization'] = 'Bearer ' + t;
            }
            request.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
            return request;
        },

        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],

        plugins: [
            SwaggerUIBundle.plugins.DownloadUrl
        ],

        layout: "StandaloneLayout",
        docExpansion : "{!! config('l5-swagger.defaults.ui.display.doc_expansion', 'none') !!}",
        deepLinking: true,
        filter: {!! config('l5-swagger.defaults.ui.display.filter') ? 'true' : 'false' !!},
        persistAuthorization: true
    });

    window.ui = ui;

    // ✅ Login button
    btnLogin.addEventListener('click', async () => {
        const email = (elEmail.value || '').trim();
        const password = (elPass.value || '').trim();

        if (!email || !password) {
            alert('กรอก email + password ก่อน');
            return;
        }

        btnLogin.disabled = true;
        btnLogin.textContent = 'Logging in...';

        try {
            const res = await fetch(window.location.origin + '/api/v1/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const json = await res.json().catch(() => ({}));

            // โปรเจกต์คุณคืนรูปแบบ { ok, message, data: { token } }
            const token = json?.data?.token || json?.token || '';

            if (!res.ok || !token) {
                console.error(json);
                alert('Login ไม่สำเร็จ: ' + (json?.message || ('HTTP ' + res.status)));
                return;
            }

            setToken(token);
            alert('Login OK: token ถูกใส่ให้ Swagger แล้ว');
        } catch (e) {
            console.error(e);
            alert('Login error: ' + e.message);
        } finally {
            btnLogin.disabled = false;
            btnLogin.textContent = 'Login';
        }
    });

    // ✅ Copy token
    btnCopy.addEventListener('click', async () => {
        const t = getToken();
        if (!t) { alert('ยังไม่มี token'); return; }
        const full = 'Bearer ' + t;
        try {
            await navigator.clipboard.writeText(full);
            alert('Copy แล้ว');
        } catch (e) {
            // fallback
            prompt('Copy token:', full);
        }
    });

    // ✅ Clear token
    btnClear.addEventListener('click', () => {
        clearToken();
        alert('ล้าง token แล้ว');
    });

    renderToken();
};
</script>

</body>
</html>