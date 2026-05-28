<?php
if (($_POST['check'] ?? null) !== '1') { http_response_code(403); exit; }

echo '
<div style="text-align:center; padding:40px; max-width:400px; margin:30px auto; background:#2d2d2d; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,0.3); border:1px solid #444; color:#fff;">
  <img src="../builder/images/logo.png" style="height:40px; margin-bottom:20px;">
  <h3 style="font-size:1.3em; color:#ff6b00; margin-bottom:16px; display:flex; align-items:center; justify-content:center; gap:8px;">
    <i class="fa fa-check-circle"></i> Codychat 8.0 Installed!
  </h3>
  <p style="margin-bottom:10px;color:#fff; font-weight:500;">Congratulations! Codychat has been successfully installed.</p>
  <p style="margin-bottom:20px;color:#ff6b00; font-size:0.9em; font-weight:bold;">
    NULLED BY FXNTXM & BLACKHUNTER
  </p>
  <div style="background:#1a1a1a; padding:15px; border-radius:6px; margin-bottom:20px; border:1px solid #444;">
    <p style="margin:0; color:#00ff00; font-size:0.9em;">
      <i class="fa fa-unlock"></i> Full Features Unlocked
    </p>
    <p style="margin:5px 0 0; color:#ccc; font-size:0.8em;">
      No license required • Premium access activated
    </p>
  </div>
  <button onclick="endInstall()" style="background:#ff6b00;color:#000;padding:12px 24px;border:none;border-radius:6px;cursor:pointer;font-weight:bold;transition:background .2s;">
    <i class="fa fa-comment"></i> Launch Codychat
  </button>
  <div style="margin-top:20px; padding-top:15px; border-top:1px solid #444;">
    <p style="color:#888; font-size:0.7em; margin:0;">
      Nulled version • Use at your own risk
    </p>
  </div>
</div>';
?>