<?php
require_once "../config_install.php";

if (($_POST["check"] ?? null) !== "1") {
    http_response_code(403);
    die;
}

echo "<style>
:root {
    --primary: #ff6b00;
    --light: #1a1a1a;
    --gray: #444;
    --gray-light: rgba(255,255,255,0.1);
    --radius: 8px;
}
.installer-wrapper {
    display: flex;
    max-width: 900px;
    margin: 30px auto;
    background: #2d2d2d;
    border-radius: var(--radius);
    box-shadow: 0 4px 25px rgba(0,0,0,0.3);
    overflow: hidden;
    font-family: \"Segoe UI\", sans-serif;
    color: #fff;
    border: 1px solid #444;
}
.installer-sidebar {
    width: 200px;
    background: #252525;
    border-right: 1px solid var(--gray-light);
    display: flex;
    flex-direction: column;
}
.installer-sidebar button {
    padding: 12px;
    border: none;
    background: none;
    text-align: left;
    font-size: 0.95em;
    color: #ccc;
    position: relative;
    cursor: pointer;
    transition: background .2s, color .2s;
    border-bottom: 1px solid #333;
}
.installer-sidebar button .fa {
    float: right;
    opacity: .6;
}
.installer-sidebar button:not(.disabled):hover {
    background: var(--gray-light);
    color: #fff;
}
.installer-sidebar button.active {
    background: var(--primary);
    color: #000;
    font-weight: 600;
}
.installer-sidebar button.active .fa {
    color: #000;
    opacity: .9;
}
.installer-sidebar button.disabled {
    color: #666;
    cursor: not-allowed;
}
.installer-content {
    flex: 1;
    padding: 28px;
    position: relative;
    background: var(--light);
}
.installer-header {
    text-align: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #444;
    padding-bottom: 20px;
}
.installer-header img {
    display: block;
    margin: 0 auto 10px;
    height: 40px;
}
.installer-title {
    color: #fff;
    margin: 0 0 5px 0;
    font-size: 1.4em;
}
.installer-subtitle {
    color: var(--primary);
    font-size: 0.9em;
    font-weight: bold;
    margin: 0 0 5px 0;
}
.installer-credits {
    color: #888;
    font-size: 0.7em;
    margin: 0;
}
.installer-step {
    display: none;
    animation: slideFade .3s ease;
}
.installer-step.active { display: block; }
@keyframes slideFade {
    from { opacity: 0; transform: translateX(20px); }
    to   { opacity: 1; transform: translateX(0); }
}
.installer-step h3 {
    font-size: 1.1em;
    color: var(--primary);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}
.installer-step h3 .fa {
    margin-right: 8px;
    opacity: .8;
}
.form-group { margin-bottom: 18px; }
.form-group label { 
    display: block; 
    margin-bottom: 6px; 
    color: #fff; 
    font-weight: 500;
}
.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--gray);
    border-radius: var(--radius);
    background: #1a1a1a;
    font-size: .9em;
    color: #fff;
    transition: border-color .2s;
}
.form-control:focus {
    border-color: var(--primary);
    outline: none;
}
.form-row { display: flex; gap:12px; }
.form-row .form-group { flex: 1; }
.actions { text-align: right; margin-top: 24px; }
.btn {
    background: var(--primary);
    color: #000;
    padding: 10px 24px;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-weight: 600;
    transition: background .2s, transform .1s;
}
.btn:hover { 
    background: #ff8c33; 
    transform: translateY(-1px); 
}
.btn:disabled { 
    background: #555; 
    color: #888; 
    cursor: not-allowed; 
    transform: none; 
}
.form-text {
    color: #888;
    font-size: 0.8em;
    margin-top: 5px;
}
.nulled-notice {
    background: #ff6b00;
    color: #000;
    padding: 10px;
    border-radius: var(--radius);
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
    font-size: 0.9em;
}
</style>

<div class=\"installer-wrapper\">
    <div class=\"installer-sidebar\">
        <button class=\"step-btn active\" data-target=\"step_user\">1. Owner <i class=\"fa fa-user-circle\"></i></button>
        <button class=\"step-btn disabled\" data-target=\"step_site\">2. Site <i class=\"fa fa-globe\"></i></button>
        <button class=\"step-btn disabled\" data-target=\"step_db\">3. Database <i class=\"fa fa-database\"></i></button>
        <button class=\"step-btn disabled\" data-target=\"step_license\">4. Nulled Version <i class=\"fa fa-key\"></i></button>
    </div>

    <div class=\"installer-content\">
        <div class=\"installer-header\">
            <img src=\"default_images/logo.png\" alt=\"Codychat Logo\">
            <h2 class=\"installer-title\">Codychat 8.0 Installer</h2>
            <div class=\"installer-subtitle\">NULLED VERSION</div>
            <div class=\"installer-credits\">Nulled by FXNTXM & BlackHunter</div>
             <div class=\"installer-subtitle\"><a target=\"_blank\" href=\"https://doniaweb.com\">Exclusively on DoniaWeB</a></div>
        </div>

        <div class=\"nulled-notice\">
            🚀 Full Features Unlocked - No License Required
        </div>

        <div class=\"installer-step active\" id=\"step_user\">
            <h3><i class=\"fa fa-user-circle\"></i>Owner Account</h3>
            <div class=\"form-row\">
                <div class=\"form-group\">
                    <label>Username</label>
                    <input id=\"install_username\" class=\"form-control required\" type=\"text\">
                </div>
                <div class=\"form-group\">
                    <label>Email</label>
                    <input id=\"install_email\" class=\"form-control required\" type=\"email\">
                </div>
            </div>
            <div class=\"form-row\">
                <div class=\"form-group\">
                    <label>Password</label>
                    <input id=\"install_password\" class=\"form-control required\" type=\"password\">
                </div>
                <div class=\"form-group\">
                    <label>Repeat Password</label>
                    <input id=\"install_repeat\" class=\"form-control required\" type=\"password\">
                </div>
            </div>
            <div class=\"actions\">
                <button id=\"next_user\" class=\"btn\">Next</button>
            </div>
        </div>

        <div class=\"installer-step\" id=\"step_site\">
            <h3><i class=\"fa fa-globe\"></i>Site Information</h3>
            <div class=\"form-group\">
                <label>Installation URL</label>
                <input id=\"install_domain\" class=\"form-control required\" type=\"text\">
                <small class=\"form-text\">Do not add a trailing slash \"/\" at the end.</small>
            </div>
            <div class=\"form-row\">
                <div class=\"form-group\">
                    <label>Site Title</label>
                    <input id=\"install_title\" class=\"form-control required\" type=\"text\">
                </div>
                <div class=\"form-group\">
                    <label>Default Language</label>
                    <select id=\"install_language\" class=\"form-control required\">";

echo listLanguage("English", 1);

echo "</select>
                </div>
            </div>
            <div class=\"actions\">
                <button id=\"next_site\" class=\"btn\">Next</button>
            </div>
        </div>

        <div class=\"installer-step\" id=\"step_db\">
            <h3><i class=\"fa fa-database\"></i>Database Information</h3>
            <div class=\"form-row\">
                <div class=\"form-group\">
                    <label>Host</label>
                    <input id=\"install_db_host\" class=\"form-control required\" type=\"text\" value=\"localhost\">
                </div>
                <div class=\"form-group\">
                    <label>Name</label>
                    <input id=\"install_db_name\" class=\"form-control required\" type=\"text\">
                </div>
            </div>
            <div class=\"form-row\">
                <div class=\"form-group\">
                    <label>User</label>
                    <input id=\"install_db_user\" class=\"form-control required\" type=\"text\">
                </div>
                <div class=\"form-group\">
                    <label>Password</label>
                    <input id=\"install_db_password\" class=\"form-control\" type=\"password\">
                </div>
            </div>
            <div class=\"actions\">
                <button id=\"next_db\" class=\"btn\">Next</button>
            </div>
        </div>

        <div class=\"installer-step\" id=\"step_license\">
    <h3><i class=\"fa fa-key\"></i>Nulled Version Ready</h3>
    <div class=\"form-group\">
        <label>License Status</label>
        <input id=\"install_license\" class=\"form-control\" type=\"text\" value=\"NULLED - Full Access\" readonly style=\"background: #00ff00; color: #000; font-weight: bold; text-align: center;\">
    </div>
    <div class=\"form-row\">
        <div class=\"form-group\">
            <label>Cracker</label>
            <input id=\"install_store_user\" class=\"form-control\" type=\"text\" value=\"FXNTXM\" readonly style=\"text-align: center;\">
        </div>
        <div class=\"form-group\">
            <label>Patcher</label>
            <input id=\"install_store_pass\" class=\"form-control\" type=\"text\" value=\"BlackHunter\" readonly style=\"text-align: center;\">
        </div>
        <!-- New Distributor field -->
        <div class=\"form-group\">
            <label>Distributor</label>
            <input id=\"install_distributor\" class=\"form-control\" type=\"text\" value=\"DniaWeB\" readonly style=\"text-align: center; font-weight: bold;\">
        </div>
    </div>

            <div class=\"actions\">
                <button id=\"install_component\" onclick=\"runInstaller()\" class=\"btn\">
                    <i class=\"fa fa-terminal\"></i> Complete Installation
                </button>
                <button id=\"wait_install\" class=\"btn\" style=\"display:none;\">
                    <i class=\"fa fa-spinner fa-spin\"></i> Installing Codychat...
                </button>
            </div>
        </div>
    </div>
</div>";
?>