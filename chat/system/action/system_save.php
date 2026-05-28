<?php
require "../config.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!boomLogged()) {
    http_response_code(403);
    die('Forbidden: User not logged in.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Invalid request method.');
}

$expected_host = $setting['domain'];

if (!isset($_SERVER['HTTP_ORIGIN']) && !isset($_SERVER['HTTP_REFERER'])) {
    http_response_code(403);
    die('Forbidden: Missing origin.');
}

$origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'];

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    die('Forbidden: Not an AJAX request.');
}

if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $ua = $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/\b(curl|wget|python-requests|libwww-perl|httpclient|nikto|sqlmap)\b/i', $ua)) {
        http_response_code(403);
        exit;
    }
}

if (php_sapi_name() === 'cli') { 
    http_response_code(403); 
    exit; 
}

$max_input_length = 8192;

function _sanitize_value($v) {
    if (is_array($v)) {
        foreach ($v as $k => $item) $v[$k] = _sanitize_value($item);
        return $v;
    }
    $v = (string)$v;
    $v = str_replace("\0", '', $v);
    $v = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $v);
    $v = strip_tags($v);
    $v = preg_replace('/[\x00-\x1F\x7F]+/u', '', $v);
    if (strlen($v) > 8192) $v = substr($v, 0, 8192);
    return trim($v);
}

if (!(isset($_POST['save_page']) && isset($_POST['page_content']))) {
    foreach (array(&$_GET, &$_POST, &$_COOKIE) as &$sg) {
        foreach ($sg as $k => $v) $sg[$k] = _sanitize_value($v);
    }
}

$_REQUEST = array_merge($_GET, $_POST);

function h($s) { 
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); 
}



function rateLimit($key, $limit = 10, $timeout = 60) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $rateKey = "rate_limit_{$key}_{$ip}";
    
    if (!isset($_SESSION[$rateKey])) {
        $_SESSION[$rateKey] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    $data = $_SESSION[$rateKey];
    
    if (time() - $data['time'] > $timeout) {
        $_SESSION[$rateKey] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    if ($data['count'] >= $limit) {
        http_response_code(429);
        die('Rate limit exceeded. Please try again later.');
    }
    
    $_SESSION[$rateKey]['count']++;
    return true;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    if ($origin !== '') {
        $h = parse_url($origin, PHP_URL_HOST);
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($h !== '' && $h !== $host) { 
            http_response_code(403); 
            exit; 
        }
    }
}

require_once __DIR__ . '/../mailer/autoload.php';

if (!boomAllow(100)) {
    http_response_code(403);
    die('Forbidden: Insufficient permissions.');
}

rateLimit('admin_panel', 30, 60);

if (isset($_POST["save_admin_main"])) {
    $required = ["set_index_path", "set_title", "set_timezone", "set_default_language", "set_site_description", "set_site_keyword"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $index = escape($_POST["set_index_path"]);
    $title = escape($_POST["set_title"]);
    $timezone = escape($_POST["set_timezone"]);
    $language = escape($_POST["set_default_language"]);
    $description = escape($_POST["set_site_description"]);
    $keyword = escape($_POST["set_site_keyword"]);

    $query = "UPDATE boom_setting SET domain = '$index', title = '$title', site_description = '$description', site_keyword = '$keyword', timezone = '$timezone', language = '$language' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_app"])) {
    $required = ["set_use_app", "set_app_name", "set_app_color"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $use_app = escape($_POST["set_use_app"]);
    $app_name = escape($_POST["set_app_name"]);
    $app_color = escape($_POST["set_app_color"]);

    $query = "UPDATE boom_setting SET use_app = '$use_app', app_name = '$app_name', app_color = '$app_color' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_maintenance"])) {
    if (!isset($_POST["set_maint_mode"])) {
        echo 2;
        exit;
    }

    $maint_mode = escape($_POST["set_maint_mode"]);
    $query = "UPDATE boom_setting SET maint_mode = '$maint_mode' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_wallet"])) {
    $required = ["set_use_wallet", "set_can_swallet", "set_can_gold", "set_gold_delay", "set_gold_base", "set_can_ruby", "set_ruby_delay", "set_ruby_base"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $use_wallet = escape($_POST["set_use_wallet"]);
    $can_swallet = escape($_POST["set_can_swallet"]);
    $can_gold = escape($_POST["set_can_gold"]);
    $gold_delay = escape($_POST["set_gold_delay"]);
    $gold_base = escape($_POST["set_gold_base"]);
    $can_ruby = escape($_POST["set_can_ruby"]);
    $ruby_delay = escape($_POST["set_ruby_delay"]);
    $ruby_base = escape($_POST["set_ruby_base"]);

    $query = "UPDATE boom_setting SET use_wallet = '$use_wallet', can_swallet = '$can_swallet', can_gold = '$can_gold', gold_delay = '$gold_delay', gold_base = '$gold_base', can_ruby = '$can_ruby', ruby_delay = '$ruby_delay', ruby_base = '$ruby_base' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_gift"])) {
    if (!isset($_POST["set_use_gift"])) {
        echo 2;
        exit;
    }

    $use_gift = escape($_POST["set_use_gift"]);
    $query = "UPDATE boom_setting SET use_gift = '$use_gift' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_level"])) {
    $required = ["set_use_level", "set_level_mode", "set_exp_chat", "set_exp_priv", "set_exp_post", "set_exp_gift"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $use_level = escape($_POST["set_use_level"]);
    $level_mode = escape($_POST["set_level_mode"]);
    $exp_chat = escape($_POST["set_exp_chat"]);
    $exp_priv = escape($_POST["set_exp_priv"]);
    $exp_post = escape($_POST["set_exp_post"]);
    $exp_gift = escape($_POST["set_exp_gift"]);

    $query = "UPDATE boom_setting SET use_level = '$use_level', level_mode = '$level_mode', exp_chat = '$exp_chat', exp_priv = '$exp_priv', exp_post = '$exp_post', exp_gift = '$exp_gift' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_badge"])) {
    $required = ["set_use_badge", "set_bachat", "set_bagift", "set_balike", "set_bafriend", "set_baruby", "set_bagold", "set_babeat"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $use_badge = escape($_POST["set_use_badge"]);
    $bachat = escape($_POST["set_bachat"]);
    $bagift = escape($_POST["set_bagift"]);
    $balike = escape($_POST["set_balike"]);
    $bafriend = escape($_POST["set_bafriend"]);
    $baruby = escape($_POST["set_baruby"]);
    $bagold = escape($_POST["set_bagold"]);
    $babeat = escape($_POST["set_babeat"]);

    $query = "UPDATE boom_setting SET use_badge = '$use_badge', bachat = '$bachat', bagift = '$bagift', balike = '$balike', bafriend = '$bafriend', baruby = '$baruby', bagold = '$bagold', babeat = '$babeat' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_data"])) {
    $required = ["set_max_avatar", "set_max_cover", "set_max_ricon", "set_max_file"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $max_avatar = escape($_POST["set_max_avatar"]);
    $max_cover = escape($_POST["set_max_cover"]);
    $max_ricon = escape($_POST["set_max_ricon"]);
    $max_file = escape($_POST["set_max_file"]);

    $query = "UPDATE boom_setting SET max_avatar = '$max_avatar', max_cover = '$max_cover', max_ricon = '$max_ricon', file_weight = '$max_file' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_player"])) {
    if (!isset($_POST["set_default_player"])) {
        echo 2;
        exit;
    }

    $default_player = escape($_POST["set_default_player"]);
    $query = "UPDATE boom_setting SET player_id = '$default_player' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_email"])) {
    $required = ["set_mail_type", "set_site_email", "set_email_from", "set_smtp_host", "set_smtp_username", "set_smtp_password", "set_smtp_port", "set_smtp_type"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $mail_type = escape($_POST["set_mail_type"]);
    $site_email = escape($_POST["set_site_email"]);
    $email_from = escape($_POST["set_email_from"]);
    $smtp_host = escape($_POST["set_smtp_host"]);
    $smtp_username = escape($_POST["set_smtp_username"]);
    $smtp_password = escape($_POST["set_smtp_password"]);
    $smtp_port = escape($_POST["set_smtp_port"]);
    $smtp_type = escape($_POST["set_smtp_type"]);

    $query = "UPDATE boom_setting SET mail_type = '$mail_type', site_email = '$site_email', email_from = '$email_from', smtp_host = '$smtp_host', smtp_username = '$smtp_username', smtp_password = '$smtp_password', smtp_port = '$smtp_port', smtp_type = '$smtp_type' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_cache"])) {
    if (!isset($_POST["set_redis_status"])) {
        echo 2;
        exit;
    }

    $redis_status = escape($_POST["set_redis_status"]);
    $query = "UPDATE boom_setting SET redis_status = '$redis_status' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_guest"])) {
    $required = ["set_allow_guest", "set_max_greg", "set_guest_form"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $allow_guest = escape($_POST["set_allow_guest"]);
    $max_greg = escape($_POST["set_max_greg"]);
    $guest_form = escape($_POST["set_guest_form"]);

    $query = "UPDATE boom_setting SET allow_guest = '$allow_guest', max_greg = '$max_greg', guest_form = '$guest_form' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_display"])) {
    $required = ["set_main_theme", "set_login_page", "set_use_gender", "set_use_flag"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $main_theme = escape($_POST["set_main_theme"]);
    $login_page = escape($_POST["set_login_page"]);
    $use_gender = escape($_POST["set_use_gender"]);
    $use_flag = escape($_POST["set_use_flag"]);

    $query = "UPDATE boom_setting SET default_theme = '$main_theme', login_page = '$login_page', use_gender = '$use_gender', use_flag = '$use_flag' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_bridge"])) {
    if (!isset($_POST["set_use_bridge"])) {
        echo 2;
        exit;
    }

    $use_bridge = escape($_POST["set_use_bridge"]);
    $query = "UPDATE boom_setting SET use_bridge = '$use_bridge' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_user_permission"])) {
    $fields = [
        "set_allow_avatar", "set_allow_cover", "set_allow_gcover", "set_allow_cupload", "set_allow_pupload",
        "set_allow_wupload", "set_allow_video", "set_allow_audio", "set_allow_zip", "set_allow_main",
        "set_allow_private", "set_allow_quote", "set_allow_pquote", "set_emo_plus", "set_allow_direct",
        "set_allow_room", "set_allow_vroom", "set_allow_theme", "set_allow_history", "set_allow_colors",
        "set_allow_grad", "set_allow_neon", "set_allow_font", "set_allow_name_color", "set_allow_name_grad",
        "set_allow_name_neon", "set_allow_name_font", "set_allow_name", "set_allow_mood", "set_allow_about",
        "set_allow_report", "set_allow_scontent", "set_allow_rnews", "set_word_proof"
    ];

    $update_parts = [];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = escape($_POST[$field]);
            $db_field = str_replace('set_', '', $field);
            $update_parts[] = "$db_field = '$value'";
        }
    }

    if (!empty($update_parts)) {
        $query = "UPDATE boom_setting SET " . implode(', ', $update_parts) . " WHERE id = '1'";
        
        if ($mysqli->query($query)) {
            boomSaveSettings();
            reloadSettings();
            echo 1;
        } else {
            echo 2;
        }
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_staff_permission"])) {
    $fields = [
        "set_can_raction", "set_can_mute", "set_can_warn", "set_can_kick", "set_can_ghost", "set_can_ban",
        "set_can_delete", "set_can_modavat", "set_can_modcover", "set_can_modmood", "set_can_modabout",
        "set_can_modcolor", "set_can_modname", "set_can_auth", "set_can_modemail", "set_can_modpass",
        "set_can_modblock", "set_can_modvpn", "set_can_verify", "set_can_note", "set_can_vip", "set_can_vemail",
        "set_can_vghost", "set_can_vother", "set_can_vname", "set_can_vhistory", "set_can_vwallet",
        "set_can_news", "set_can_rank", "set_can_inv", "set_can_cuser", "set_can_content", "set_can_clear",
        "set_can_rpass", "set_can_bpriv", "set_can_topic", "set_can_maddons", "set_can_mroom", "set_can_mfilter",
        "set_can_dj", "set_can_mip", "set_can_mlogs", "set_can_mplay", "set_can_mcontact"
    ];

    $update_parts = [];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = escape($_POST[$field]);
            $db_field = str_replace('set_', '', $field);
            $update_parts[] = "$db_field = '$value'";
        }
    }

    if (!empty($update_parts)) {
        $query = "UPDATE boom_setting SET " . implode(', ', $update_parts) . " WHERE id = '1'";
        
        if ($mysqli->query($query)) {
            boomSaveSettings();
            reloadSettings();
            echo 1;
        } else {
            echo 2;
        }
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_room_permission"])) {
    $required = ["set_can_rlogs", "set_can_rclear"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $can_rlogs = escape($_POST["set_can_rlogs"]);
    $can_rclear = escape($_POST["set_can_rclear"]);

    $query = "UPDATE boom_setting SET can_rlogs = '$can_rlogs', can_rclear = '$can_rclear' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_delays"])) {
    $required = ["set_act_delay", "set_chat_delete", "set_private_delete", "set_wall_delete", "set_member_delete", "set_room_delete", "set_ignore_delete"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $act_delay = escape($_POST["set_act_delay"]);
    $chat_delete = escape($_POST["set_chat_delete"]);
    $private_delete = escape($_POST["set_private_delete"]);
    $wall_delete = escape($_POST["set_wall_delete"]);
    $member_delete = escape($_POST["set_member_delete"]);
    $room_delete = escape($_POST["set_room_delete"]);
    $ignore_delete = escape($_POST["set_ignore_delete"]);

    $query = "UPDATE boom_setting SET act_delay = '$act_delay', chat_delete = '$chat_delete', private_delete = '$private_delete', wall_delete = '$wall_delete', member_delete = '$member_delete', room_delete = '$room_delete', ignore_delete = '$ignore_delete' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_call"])) {
    $required = ["set_use_call", "set_can_vcall", "set_can_acall", "set_call_appid", "set_call_secret", "set_live_url", "set_live_appid", "set_live_secret", "set_call_max", "set_call_method", "set_call_cost", "set_can_cgcall", "set_can_gcall", "set_can_mgcall", "set_max_gcall"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $use_call = escape($_POST["set_use_call"]);
    $can_vcall = escape($_POST["set_can_vcall"]);
    $can_acall = escape($_POST["set_can_acall"]);
    $call_appid = escape($_POST["set_call_appid"]);
    $call_secret = escape($_POST["set_call_secret"]);
    $live_url = escape($_POST["set_live_url"]);
    $live_appid = escape($_POST["set_live_appid"]);
    $live_secret = escape($_POST["set_live_secret"]);
    $call_max = escape($_POST["set_call_max"]);
    $call_method = escape($_POST["set_call_method"]);
    $call_cost = escape($_POST["set_call_cost"]);
    $can_cgcall = escape($_POST["set_can_cgcall"]);
    $can_gcall = escape($_POST["set_can_gcall"]);
    $can_mgcall = escape($_POST["set_can_mgcall"]);
    $max_gcall = escape($_POST["set_max_gcall"]);

    $query = "UPDATE boom_setting SET use_call = '$use_call', can_vcall = '$can_vcall', can_acall = '$can_acall', call_appid = '$call_appid', call_secret = '$call_secret', live_url = '$live_url', live_appid = '$live_appid', live_secret = '$live_secret', call_max = '$call_max', call_method = '$call_method', call_cost = '$call_cost', can_cgcall = '$can_cgcall', can_gcall = '$can_gcall', can_mgcall = '$can_mgcall', max_gcall = '$max_gcall' WHERE id = '1'";

    $result = $mysqli->query($query);
    error_log("save_admin_call SQL: " . $query);
    if (!$result) {
        error_log("MySQL Error: " . $mysqli->error);
        echo 2;
        exit;
    }

    boomSaveSettings();
    reloadSettings();
    echo 1;
}


elseif (isset($_POST["save_admin_modules"])) {
    $required = ["set_use_like", "set_use_wall", "set_use_lobby", "set_cookie_law", "set_use_geo"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $use_like = escape($_POST["set_use_like"]);
    $use_wall = escape($_POST["set_use_wall"]);
    $use_lobby = escape($_POST["set_use_lobby"]);
    $cookie_law = escape($_POST["set_cookie_law"]);
    $use_geo = escape($_POST["set_use_geo"]);

    $query = "UPDATE boom_setting SET use_like = '$use_like', use_wall = '$use_wall', use_lobby = '$use_lobby', cookie_law = '$cookie_law', use_geo = '$use_geo' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_chat"])) {
    $required = ["set_max_main", "set_max_private", "set_max_offcount", "set_privload", "set_speed", "set_max_emo", "set_max_room", "set_log_join", "set_log_name", "set_log_action"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $max_main = escape($_POST["set_max_main"]);
    $max_private = escape($_POST["set_max_private"]);
    $max_offcount = escape($_POST["set_max_offcount"]);
    $privload = escape($_POST["set_privload"]);
    $speed = escape($_POST["set_speed"]);
    $max_emo = escape($_POST["set_max_emo"]);
    $max_room = escape($_POST["set_max_room"]);
    $log_join = escape($_POST["set_log_join"]);
    $log_name = escape($_POST["set_log_name"]);
    $log_action = escape($_POST["set_log_action"]);

    $use_logs = '0';
    if ($log_join == '1') $use_logs .= '1';
    if ($log_name == '1') $use_logs .= '2';
    if ($log_action == '1') $use_logs .= '3';

    $query = "UPDATE boom_setting SET max_main = '$max_main', max_private = '$max_private', max_offcount = '$max_offcount', privload = '$privload', speed = '$speed', max_emo = '$max_emo', max_room = '$max_room', use_logs = '$use_logs' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_security"]) && $_POST["save_admin_security"] === "security_registration") {
    $required = ["set_use_recapt", "set_recapt_key", "set_recapt_secret", "set_use_vpn", "set_vpn_key", "set_vpn_delay", "set_flood_action", "set_flood_delay", "set_max_flood", "set_use_rate", "set_rate_limit"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $use_recapt = escape($_POST["set_use_recapt"]);
    $recapt_key = escape($_POST["set_recapt_key"]);
    $recapt_secret = escape($_POST["set_recapt_secret"]);
    $use_vpn = escape($_POST["set_use_vpn"]);
    $vpn_key = escape($_POST["set_vpn_key"]);
    $vpn_delay = escape($_POST["set_vpn_delay"]);
    $flood_action = escape($_POST["set_flood_action"]);
    $flood_delay = escape($_POST["set_flood_delay"]);
    $max_flood = escape($_POST["set_max_flood"]);
    $use_rate = escape($_POST["set_use_rate"]);
    $rate_limit = escape($_POST["set_rate_limit"]);

    $query = "UPDATE boom_setting SET use_recapt = '$use_recapt', recapt_key = '$recapt_key', recapt_secret = '$recapt_secret', use_vpn = '$use_vpn', vpn_key = '$vpn_key', vpn_delay = '$vpn_delay', flood_action = '$flood_action', flood_delay = '$flood_delay', max_flood = '$max_flood', use_rate = '$use_rate', rate_limit = '$rate_limit' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_ai"]) && $_POST["save_admin_ai"] == 1) {
    $required = ["set_openai_key", "set_img_mod"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $openai_key = escape($_POST["set_openai_key"]);
    $img_mod = escape($_POST["set_img_mod"]);

    $query = "UPDATE boom_setting SET openai_key = '$openai_key', img_mod = '$img_mod' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        boomSaveSettings();
        reloadSettings();
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["test_mail"]) && $_POST["test_mail"] == 1) {
    if (!isset($_POST["test_email"])) {
        echo 2;
        exit;
    }

    $test_email = escape($_POST["test_email"]);
    $query = "SELECT mail_type, site_email, email_from, smtp_host, smtp_username, smtp_password, smtp_port, smtp_type FROM boom_setting WHERE id = '1'";
    $result = $mysqli->query($query);

    if ($result->num_rows > 0) {
        $settings = $result->fetch_assoc();

        $mail_type = $settings["mail_type"];
        $site_email = $settings["site_email"];
        $email_from = $settings["email_from"];
        $smtp_host = $settings["smtp_host"];
        $smtp_username = $settings["smtp_username"];
        $smtp_password = $settings["smtp_password"];
        $smtp_port = $settings["smtp_port"];
        $smtp_type = strtolower($settings["smtp_type"]);

        $subject = "Test Mail from CodyChat";
        $message = "This is a test email to verify your email settings.";

        if ($mail_type === "smtp") {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_username;
            $mail->Password = $smtp_password;
            $mail->Port = $smtp_port;
            $mail->SMTPDebug = 0;

            if ($smtp_type === "ssl") {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom($site_email, "CodyChat");
            $mail->addAddress($test_email);
            $mail->addReplyTo($site_email, "Support");

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = "<b>$message</b>";
            $mail->AltBody = $message;

            if ($mail->send()) {
                echo 1;
            } else {
                echo 2;
            }
        } else {
            $headers = "From: $site_email\r\n";
            $headers .= "Reply-To: $site_email\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            if (mail($test_email, $subject, $message, $headers)) {
                echo 1;
            } else {
                echo 2;
            }
        }
    } else {
        echo 2;
    }
}

elseif (isset($_POST["flush_cache"]) && $_POST["flush_cache"] == 1) {
    if (function_exists('opcache_reset')) opcache_reset();
    if (function_exists('redisFlushAll')) redisFlushAll();
    if (function_exists('boomCacheUpdate')) boomCacheUpdate();
    echo 1;
}

elseif (isset($_POST["save_admin_modcat"]) && $_POST["save_admin_modcat"] == 1) {
    $mod_cat = '';

    if (isset($_POST["set_mod_sexual"]) && $_POST["set_mod_sexual"] == 1) {
        $mod_cat .= '1';
    }
    if (isset($_POST["set_mod_hate"]) && $_POST["set_mod_hate"] == 1) {
        $mod_cat .= '2';
    }
    if (isset($_POST["set_mod_harassment"]) && $_POST["set_mod_harassment"] == 1) {
        $mod_cat .= '3';
    }
    if (isset($_POST["set_mod_illicit"]) && $_POST["set_mod_illicit"] == 1) {
        $mod_cat .= '4';
    }
    if (isset($_POST["set_mod_violence"]) && $_POST["set_mod_violence"] == 1) {
        $mod_cat .= '5';
    }

    $query = "UPDATE boom_setting SET mod_cat = '$mod_cat' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_registration"]) && $_POST["save_admin_registration"] == 1) {
    $required = ["set_registration", "set_max_reg", "set_activation", "set_max_username", "set_min_age", "set_coppa"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $registration = escape($_POST["set_registration"]);
    $max_reg = escape($_POST["set_max_reg"]);
    $activation = escape($_POST["set_activation"]);
    $max_username = escape($_POST["set_max_username"]);
    $min_age = escape($_POST["set_min_age"]);
    $coppa = escape($_POST["set_coppa"]);

    $query = "UPDATE boom_setting SET registration = '$registration', max_reg = '$max_reg', activation = '$activation', max_username = '$max_username', min_age = '$min_age', coppa = '$coppa' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_POST["save_admin_registration_act"]) && $_POST["save_admin_registration_act"] == 1) {
    $required = ["set_reg_act", "set_reg_delay"];
    foreach ($required as $field) {
        if (!isset($_POST[$field])) {
            echo 2;
            exit;
        }
    }

    $reg_act = escape($_POST["set_reg_act"]);
    $reg_delay = escape($_POST["set_reg_delay"]);

    $query = "UPDATE boom_setting SET reg_act = '$reg_act', reg_delay = '$reg_delay' WHERE id = '1'";
    
    if ($mysqli->query($query)) {
        echo 1;
    } else {
        echo 2;
    }
}

elseif (isset($_FILES['image']) && isset($_POST['name'])) {
    $uploadDir = BOOM_PATH . '/default_images/';
    $fileName = basename($_POST['name']);
    $targetFile = $uploadDir . $fileName;
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileType = $_FILES['image']['type'];
    
    if (!in_array($fileType, $allowedTypes)) {
        echo 0;
        exit();
    }
    
    if (file_exists($targetFile)) {
        unlink($targetFile);
    }
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        boomCacheUpdate();
        opcache_reset();
        redisFlushAll();
        echo 1;
    } else {
        echo 0;
    }
}

elseif (isset($_POST["save_page"], $_POST["page_target"], $_POST["page_content"])) {
    $page_target = escape($_POST["page_target"]);
    $page_content = escape($_POST["page_content"]);
    
    $query = "UPDATE boom_page SET page_content='$page_content' WHERE page_name='$page_target'";
    
    if ($mysqli->query($query)) {
        echo 1;
        if (function_exists("opcache_reset")) opcache_reset();
        if (function_exists("redisFlushAll")) redisFlushAll();
        if (function_exists("boomCacheUpdate")) boomCacheUpdate();
    } else {
        echo 0;
    }
}

elseif (isset($_POST['activate_addons'], $_POST['addons'])) {
    $nameaddon = escape($_POST['addons']);
    
    if (strpos($nameaddon, 'BLK_') !== false || strpos($nameaddon, 'aps_') !== false) {
        echo boomCode(0);
        die();
    }

    if ($mysqli->query("SELECT addons FROM boom_addons WHERE addons = '$nameaddon'")->num_rows > 0) {
        echo boomCode(0);
        die();
    }

    $install_file = BOOM_PATH . "/addons/$nameaddon/system/install.php";
    if (!file_exists($install_file)) {
        echo boomCode(0);
        die();
    }

    require($install_file);
    if (!isset($ad['name'])) {
        echo boomCode(0);
        die();
    }

    $load = isset($ad['load']) ? escape($ad['load']) : 0;
    $custom1 = escape($ad['custom1'] ?? '');
    $custom2 = escape($ad['custom2'] ?? '');
    $custom3 = escape($ad['custom3'] ?? '');
    $custom4 = escape($ad['custom4'] ?? '');
    $custom5 = escape($ad['custom5'] ?? '');
    $custom6 = escape($ad['custom6'] ?? '');
    $custom7 = escape($ad['custom7'] ?? '');
    $custom8 = escape($ad['custom8'] ?? '');
    $custom9 = escape($ad['custom9'] ?? '');
    $custom10 = escape($ad['custom10'] ?? '');

    $res2 = $mysqli->query("SELECT MAX(addons_id) AS maxaid FROM boom_addons");
    $row2 = $res2->fetch_assoc();
    $next_addons = $row2['maxaid'] + 1;

    if (isset($ad['bot_name'], $ad['bot_type'])) {
        $bot_name = escape($ad['bot_name']);
        $bot_type = (int)$ad['bot_type'];

        $res = $mysqli->query("SELECT MAX(user_id) AS maxid FROM boom_users");
        $row = $res->fetch_assoc();
        $next_id = $row['maxid'] + 1;

        $pass = randomPass();
        $time = time();

        $mysqli->query("INSERT INTO boom_users (user_id, user_name, user_ip, user_join, user_password, user_rank, user_tumb, user_bot) 
                        VALUES ($next_id, '$bot_name', '0.0.0.0', '$time', '$pass', '69', 'default_system.png', '$bot_type')");

        $sql = "INSERT INTO boom_addons (addons_id, addons, addons_load, addons_key, addons_access, bot_name, bot_id,
                custom1, custom2, custom3, custom4, custom5, custom6, custom7, custom8, custom9, custom10)
                VALUES ($next_addons, '$nameaddon', '$load', '', '0', '$bot_name', '$next_id',
                '$custom1', '$custom2', '$custom3', '$custom4', '$custom5', '$custom6', '$custom7', '$custom8', '$custom9', '$custom10')";
    } 
    else {
        if (!isset($ad['access'])) {
            echo boomCode(0);
            die();
        }

        $access = escape($ad['access']);
        $sql = "INSERT INTO boom_addons (addons_id, addons, addons_load, addons_key, addons_access,
                custom1, custom2, custom3, custom4, custom5, custom6, custom7, custom8, custom9, custom10)
                VALUES ($next_addons, '$nameaddon', '$load', '', '$access',
                '$custom1', '$custom2', '$custom3', '$custom4', '$custom5', '$custom6', '$custom7', '$custom8', '$custom9', '$custom10')";
    }

    echo boomCode($mysqli->query($sql) ? 1 : 0);
    die();
}

$mysqli->close();
?>