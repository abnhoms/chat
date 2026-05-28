<?php
if ($chat_install != 2) { 
    die; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Codychat 8.0 Installer</title>
    <link rel="shortcut icon" href="default_images/icon.png">
    <link rel="stylesheet" href="css/fontawesome/css/all.css">
    <script src="js/jquery-1.11.2.min.js"></script>
    <script src="js/jqueryui/jquery-ui.min.js"></script>
    <script src="js/global.min.js"></script>
    <script src="builder/install.js?v=11"></script>
    <style>
        :root {
            --primary: #ff6b00;
            --light: #1a1a1a;
            --gray-light: rgba(255,255,255,0.05);
            --radius: 8px;
        }
        * { box-sizing: border-box; }
        body { 
            margin:0; 
            padding:0; 
            font-family:"Segoe UI",sans-serif; 
            background:var(--light); 
            color: #fff;
        }
        .popup {
            position: fixed;
            top:50%;
            left:50%;
            transform: translate(-50%,-50%) scale(0);
            background:#2d2d2d;
            padding:20px;
            border-radius:8px;
            box-shadow:0 4px 20px rgba(0,0,0,0.3);
            display:flex;
            gap:10px;
            transition:transform .2s;
            z-index:1000;
            color: #fff;
        }
        .popup.show { transform: translate(-50%,-50%) scale(1); }
        .popup i { font-size:1.4em; }
        .popup .msg { font-size:1em; }
        .card {
            width:90%;
            max-width:360px;
            margin:40px auto;
            padding:24px;
            background:#2d2d2d;
            border-radius:var(--radius);
            box-shadow:0 3px 15px rgba(0,0,0,0.3);
            text-align:center;
            border: 1px solid #444;
        }
        .card img { height:32px; margin-bottom:12px; }
        .card h2 { font-size:1.6em; margin-bottom:6px; color:#fff; }
        .card h3 {
            font-size:1em;
            color:#ff6b00;
            margin-bottom:12px;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .card h3 .fa { margin-right:6px; }
        .card .terms-title {
            font-weight:600;
            color:#fff;
            margin-bottom:8px;
            text-align:left;
            font-size:.95em;
        }
        .card ul {
            list-style:none;
            padding:0;
            margin:0 0 12px;
            text-align:left;
        }
        .card ul li {
            margin:4px 0;
            font-size:.9em;
            display:flex;
            align-items:center;
        }
        .card ul li .fa {
            margin-right:8px;
            color:var(--primary);
        }
        .card .dmca-text {
            font-size:.8em;
            color:#ff6b00;
            margin:8px 0 16px;
            text-align:center;
            min-height:36px;
            font-weight: bold;
        }
        .card .credits {
            font-size:.7em;
            color:#888;
            margin:8px 0;
            text-align:center;
        }
        .card .rules {
            display:flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            margin-bottom:16px;
        }
        .card .rules .fa {
            margin-right:6px;
            font-size:1.1em;
            color:var(--primary);
        }
        .card .btn {
            width:100%;
            background:var(--primary);
            color:#000;
            padding:10px;
            border:none;
            border-radius:6px;
            font-size:.9em;
            font-weight:600;
            cursor:pointer;
            transition:background .2s,transform .1s;
        }
        .card .btn:hover { background:#ff8c33; transform:translateY(-1px); }
        .card .btn:disabled {
            background:#555;
            color:#888;
            cursor:not-allowed;
            transform:none;
        }
        @media (max-width:480px) {
            .card { margin:20px; padding:16px; }
            .card h2 { font-size:1.6em; }
            .card h3 { font-size:.9em; }
            .card ul li { font-size:.8em; }
        }
    </style>
</head>
<body>
    <div id="install_content">
        <div class="card">
            <img src="default_images/logo.png?v=11" alt="Codychat Logo">
            <h2><span style="color:var(--primary)">Codychat</span> 8.0 Installer</h2>
            <h3><i class="fa fa-file-contract"></i>NULLED VERSION</h3>
             <h3> <a target="_blank"  style="color:var(--primary)" href="https://doniaweb.com">Exclusively on DoniaWeB</a></h3>
            <p class="terms-title">Nulled Version Terms</p>
            <ul>
                <li><i class="fa fa-check-circle"></i>Full features unlocked</li>
                <li><i class="fa fa-check-circle"></i>No license required</li>
                <li><i class="fa fa-check-circle"></i>Free to use and modify</li>
                <li><i class="fa fa-times-circle"></i>No official support</li>
                <li><i class="fa fa-times-circle"></i>Use at your own risk</li>
                <li><i class="fa fa-times-circle"></i>Not for commercial resale</li>
            </ul>

            <p class="dmca-text">
                NULLED BY FXNTXM & BLACKHUNTER
            </p>
            <p class="credits">
                Cracked & Distributed by FXNTXM | Patched by BlackHunter
            </p>

            <div class="rules">
                <i class="fa fa-circle accept_install" value="0"></i>
                <span>I understand this is a nulled version</span>
            </div>

            <button id="start_install" class="btn" disabled>Begin Installation</button>
        </div>
    </div>

    <div class="popup" id="ui_popup">
        <i class="fa"></i>
        <div class="msg"></div>
    </div>
</body>
</html>