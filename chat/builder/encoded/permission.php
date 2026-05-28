<?php
require_once "../config_install.php";

if (($_POST["check"] ?? '') !== "1") {
    http_response_code(403);
    die;
}

$requirements = array(
    "PHP ≥8.1" => PHP_VERSION_ID >= 80100,
    "GD library" => extension_loaded("gd") && function_exists("gd_info"),
    "cURL" => extension_loaded("curl") && function_exists("curl_init"),
    "ZIP" => extension_loaded("zip") && class_exists("ZipArchive"),
    "Mbstring" => extension_loaded("mbstring") && function_exists("mb_strlen"),
    "OpCache" => true
);

$paths = array(
    "system/database.php" => BOOM_PATH . "/system/database.php",
    "avatar folder" => BOOM_PATH . "/avatar",
    "cover folder" => BOOM_PATH . "/cover",
    "upload folder" => BOOM_PATH . "/upload"
);

$allChecks = $requirements;
foreach ($paths as $label => $path) {
    $allChecks[$label] = is_writable($path);
}
?>

<style>
:root{
    --primary:#ff6b00;
    --light:#1a1a1a;
    --gray-light:rgba(255,255,255,0.1);
    --radius:8px;
}
.installer-wrapper{
    display:flex;
    justify-content:center;
    padding:20px;
    background:var(--light);
    font-family:Segoe UI,sans-serif;
    color: #fff;
}
.installer-step{
    background:#2d2d2d;
    border-radius:var(--radius);
    box-shadow:0 4px 12px rgba(0,0,0,0.3);
    padding:24px;
    max-width:500px;
    width:100%;
    border: 1px solid #444;
}
.installer-step h3{
    color:var(--primary);
    margin-bottom:16px;
    font-size:1.2em;
    text-align: center;
}
.installer-header {
    text-align: center;
    margin-bottom: 20px;
    border-bottom: 1px solid #444;
    padding-bottom: 15px;
}
.installer-header h2 {
    color: #fff;
    margin: 0 0 5px 0;
    font-size: 1.4em;
}
.installer-header .version {
    color: var(--primary);
    font-size: 0.9em;
    font-weight: bold;
}
.installer-header .credits {
    color: #888;
    font-size: 0.7em;
    margin-top: 5px;
}
.sub_install{
    list-style:none;
    padding:0;
    margin:0 0 20px;
}
.sub_install li{
    padding:8px 0;
    border-bottom:1px solid var(--gray-light);
    display:flex;
    justify-content:space-between;
    color: #fff;
}
.status.pending{
    color:#ff6b00;
    font-weight:600;
}
.status.success{
    color:#00ff00;
    font-weight:600;
}
.status.error{
    color:#ff4444;
    font-weight:600;
}
.btn{
    background:var(--primary);
    color:#000;
    padding:10px 20px;
    border:none;
    border-radius:var(--radius);
    cursor:pointer;
    font-size:1em;
    font-weight: 600;
    width: 100%;
    transition: background .2s;
}
.btn:hover{
    background:#ff8c33;
}
.btn:disabled{
    background:#555;
    color:#888;
    cursor:not-allowed;
}
.fa-spin {
    color: var(--primary);
}
</style>

<div class="installer-wrapper">
    <div class="installer-step">
        <div class="installer-header">
            <h2>Codychat 8.0 Installer</h2>
            <div class="version">NULLED VERSION</div>
            <div class="credits">Nulled by FXNTXM & BlackHunter</div>
              <h3> <a target="_blank"  style="color:var(--primary)" href="https://doniaweb.com">Exclusively on DoniaWeB</a></h3>
        </div>
        
        <h3>System Requirements Check</h3>
        <ul id="requirements" class="sub_install">
            <?php foreach ($allChecks as $label => $passed): ?>
                <?php $data = $passed ? "1" : "0"; ?>
                <li data-ok="<?php echo $data; ?>">
                    <?php echo $label; ?>
                    <span class="status pending">Pending</span>
                </li>
            <?php endforeach; ?>
        </ul>
        <button id="checkBtn" onclick="checkPermission()" class="btn">Check & Continue</button>
    </div>
</div>

<script src="https://use.fontawesome.com/releases/v5.15.4/js/all.js"></script>
<script>
function checkPermission(){
    const items = document.querySelectorAll("#requirements li");
    let allOk = true;
    document.getElementById("checkBtn").disabled = true;
    
    items.forEach((li, i) => {
        const span = li.querySelector(".status");
        span.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        span.className = "status pending";
        
        setTimeout(() => {
            const ok = li.dataset.ok === "1";
            span.textContent = ok ? "OK" : "FAIL";
            span.className = "status " + (ok ? "success" : "error");
            if (!ok) allOk = false;
            
            if (i === items.length - 1) {
                if (allOk) {
                    getComponent();
                } else {
                    document.getElementById("checkBtn").disabled = false;
                    document.getElementById("checkBtn").textContent = "Fix Errors & Retry";
                }
            }
        }, 300 * (i + 1));
    });
}
</script>