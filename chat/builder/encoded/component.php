<?php
require_once('../config_install.php'); // أو المسار الصحيح عندك
if (!isset($check_install)) {
    $check_install = 0; // افتراضي: لسه ما اتثبتش
}


if (!(isset($_POST["db_host"], $_POST["db_name"], $_POST["db_user"], $_POST["db_pass"], $_POST["username"], $_POST["password"], $_POST["email"], $_POST["repeat"], $_POST["domain"], $_POST["title"], $_POST["language"]))) {
    echo boomCode(0, ["error" => "An error occurred. Please try again or contact us."]);
    exit;
}

if (empty($_POST["db_host"]) || empty($_POST["db_name"]) || empty($_POST["db_user"]) || empty($_POST["username"]) || empty($_POST["password"]) || empty($_POST["email"]) || empty($_POST["repeat"]) || empty($_POST["domain"]) || empty($_POST["title"]) || empty($_POST["language"])) {
    echo boomCode(0, ["error" => "Please fill in all information."]);
    exit;
}

$DB_HOST = $_POST["db_host"];
$DB_NAME = $_POST["db_name"];
$DB_USER = $_POST["db_user"];
$DB_PASS = $_POST["db_pass"];
$HT_DOM  = $_POST["domain"];

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if (mysqli_connect_errno()) {
    echo boomCode(0, ["error" => "Unable to connect to database please check your database information."]);
    exit;
}

echo processinstall();
exit;

function processInstall()
{
    global $mysqli, $HT_LIC;
    require "../../system/template/data_template.php";
    $username = escape($_POST["username"]);
    $email = escape($_POST["email"]);
    $password = escape($_POST["password"]);
    $repeat = escape($_POST["repeat"]);
    $domain = escape($_POST["domain"]);
    $title = escape($_POST["title"]);
    $language = escape($_POST["language"]);
	$parsedUrl = parse_url($domain);
	$host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
	$path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
	$prefixBase = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $host . $path));
	$prefix = trim($prefixBase, '_') . '_';	$__SECURE_HUNTER = ["lic" => $HT_LIC];
    if ($password != $repeat) {
        return boomCode(0, ["error" => "Password are not matching please verify and try again"]);
    }
    if (mb_strlen($username) < 2 || 18 < mb_strlen($username)) {
        return boomCode(0, ["error" => "Invalid username, username must be between 2 and 18 characters long."]);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return boomCode(0, ["error" => "Invalid email please provide a valid email."]);
    }
    if (substr($domain, -1) == "/" || $domain == "" || !preg_match("@https?[\\w_-]*@i", $domain)) {
        return boomCode(0, ["error" => "Invalid domain please make sure domain do not end with a / and try again."]);
    }
    if (!file_exists(BOOM_PATH . "/system/language/" . $language . "/language.php")) {
        $language = "English";
    }
    $time = time();
    $encrypt = str_shuffle("HUNTER---" . md5(rand(1000000, 9999999)));
    $password = boomEncrypt($password, $encrypt);
// مثال: استعلام للتحقق من الاتصال/الإعداد
$q = "SELECT @@sql_mode AS config"; // كمثال، غيّره حسب سطرِك
$result = $mysqli->query($q);

if ($result === false) {
    echo boomCode(0, ['error' => 'Database query failed: ' . $mysqli->error]);
    exit;
}

$row = $result->fetch_assoc();
$config = $row['config'] ?? ''; // استخدم المفتاح الصحيح من نتيجة الاستعلام

$mysqli->query("CREATE TABLE `boom_act` (`act_user` int(11) NOT NULL DEFAULT '0', `act_name` varchar(100) NOT NULL DEFAULT '', `act_time` int(11) NOT NULL DEFAULT '0', KEY `act_name` (`act_name`), KEY `act_user` (`act_user`), KEY `act_time` (`act_time`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_addons` (`addons_id` int(11) NOT NULL AUTO_INCREMENT, `addons` varchar(100) NOT NULL DEFAULT '', `addons_load` int(11) NOT NULL DEFAULT '0', `addons_key` varchar(100) NOT NULL DEFAULT '', `addons_access` int(3) NOT NULL DEFAULT '0', `bot_name` varchar(100) NOT NULL DEFAULT '', `bot_id` int(11) NOT NULL DEFAULT '0', `custom1` varchar(1000) NOT NULL DEFAULT '', `custom2` varchar(1000) NOT NULL DEFAULT '', `custom3` varchar(1000) NOT NULL DEFAULT '', `custom4` varchar(1000) NOT NULL DEFAULT '', `custom5` varchar(1000) NOT NULL DEFAULT '', `custom6` varchar(1000) NOT NULL DEFAULT '', `custom7` varchar(1000) NOT NULL DEFAULT '', `custom8` varchar(1000) NOT NULL DEFAULT '', `custom9` varchar(1000) NOT NULL DEFAULT '', `custom10` varchar(4000) NOT NULL DEFAULT '', PRIMARY KEY (`addons_id`)) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_banned` (`id` int(11) NOT NULL AUTO_INCREMENT, `ip` varchar(100) NOT NULL DEFAULT '', `ban_user` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `ip` (`ip`), KEY `ban_user` (`ban_user`)) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_call` (`call_id` int(11) NOT NULL AUTO_INCREMENT, `call_hunter` int(11) NOT NULL DEFAULT '0', `call_target` int(11) NOT NULL DEFAULT '0', `call_type` int(2) NOT NULL DEFAULT '0', `call_status` int(1) NOT NULL DEFAULT '0', `call_reason` int(1) NOT NULL DEFAULT '0', `call_method` int(1) NOT NULL DEFAULT '1', `call_paid` int(11) NOT NULL DEFAULT '0', `call_time` int(11) NOT NULL DEFAULT '0', `call_last` int(11) NOT NULL DEFAULT '0', `call_active` int(11) NOT NULL DEFAULT '0', `call_room` varchar(100) NOT NULL DEFAULT '', PRIMARY KEY (`call_id`), KEY `call_hunter` (`call_hunter`), KEY `call_target` (`call_target`), KEY `call_status` (`call_status`), KEY `call_time` (`call_time`), KEY `call_active` (`call_active`)) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_call_action` (`id` int(11) NOT NULL AUTO_INCREMENT, `call_room` int(11) NOT NULL DEFAULT '0', `hunter` int(11) NOT NULL DEFAULT '0', `target` int(11) NOT NULL DEFAULT '0', `action_time` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `target` (`target`), KEY `action_time` (`action_time`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_call_user` (`id` int(11) NOT NULL AUTO_INCREMENT, `croom` int(11) NOT NULL DEFAULT '0', `cuser` int(11) NOT NULL DEFAULT '0', `cdate` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `croom` (`croom`), KEY `cuser` (`cuser`), KEY `cdate` (`cdate`)) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_chat` (`post_id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL DEFAULT '0', `post_date` int(11) NOT NULL DEFAULT '0', `post_message` varchar(3000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `post_roomid` int(6) NOT NULL DEFAULT '1', `type` varchar(50) NOT NULL DEFAULT '', `log_rank` int(5) NOT NULL DEFAULT '999', `file` int(11) NOT NULL DEFAULT '0', `quser` int(11) NOT NULL DEFAULT '0', `qpost` int(11) NOT NULL DEFAULT '0', `pghost` int(1) NOT NULL DEFAULT '0', `syslog` int(1) NOT NULL DEFAULT '0', `log_uid` int(11) NOT NULL DEFAULT '0', `tid` int(11) NOT NULL DEFAULT '0', `tname` varchar(60) NOT NULL DEFAULT '', `tcolor` varchar(60) NOT NULL DEFAULT '', `custom` varchar(2000) NOT NULL DEFAULT '', PRIMARY KEY (`post_id`), KEY `post_roomid` (`post_roomid`), KEY `user_id` (`user_id`), KEY `post_date` (`post_date`), KEY `quser` (`quser`), KEY `qpost` (`qpost`), KEY `pghost` (`pghost`)) ENGINE=InnoDB AUTO_INCREMENT=9873 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_clean` (`id` int(11) NOT NULL AUTO_INCREMENT, `last_clean` int(11) NOT NULL DEFAULT '0', `last_expw` int(11) NOT NULL DEFAULT '0', `last_expm` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_console` (`id` int(11) NOT NULL AUTO_INCREMENT, `hunter` int(11) NOT NULL DEFAULT '0', `target` int(11) NOT NULL DEFAULT '0', `room` int(11) NOT NULL DEFAULT '0', `ctype` varchar(200) NOT NULL DEFAULT '', `crank` int(11) NOT NULL DEFAULT '0', `delay` int(11) NOT NULL DEFAULT '0', `reason` varchar(2000) NOT NULL DEFAULT '', `ctext` varchar(400) NOT NULL DEFAULT '', `custom` varchar(2000) NOT NULL DEFAULT '', `custom2` varchar(2000) NOT NULL DEFAULT '', `cdate` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `hunter` (`hunter`), KEY `target` (`target`), KEY `room` (`room`)) ENGINE=InnoDB AUTO_INCREMENT=340 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_contact` (`id` int(11) NOT NULL AUTO_INCREMENT, `cname` varchar(100) NOT NULL DEFAULT '0', `cmessage` varchar(4000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `cemail` varchar(100) NOT NULL DEFAULT '', `cip` varchar(100) NOT NULL DEFAULT '', `cdate` int(11) NOT NULL DEFAULT '0', `cview` int(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `cip` (`cip`), KEY `cview` (`cview`)) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_conversation` (`cid` varchar(30) NOT NULL DEFAULT '', `hunter` int(11) NOT NULL DEFAULT '0', `target` int(11) NOT NULL DEFAULT '0', `unread` int(11) NOT NULL DEFAULT '0', `cdate` int(11) NOT NULL DEFAULT '1', PRIMARY KEY (`cid`), KEY `hunter` (`hunter`), KEY `target` (`target`), KEY `cdate` (`cdate`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_data` (`id` int(11) NOT NULL AUTO_INCREMENT, `data_user` int(11) NOT NULL DEFAULT '0', `data_key` varchar(100) NOT NULL DEFAULT '', `data_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, PRIMARY KEY (`id`), KEY `data_user` (`data_user`), KEY `data_key` (`data_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_exp` (`uid` int(11) NOT NULL AUTO_INCREMENT, `exp_current` int(11) NOT NULL DEFAULT '0', `exp_week` int(11) NOT NULL DEFAULT '0', `exp_month` int(11) NOT NULL DEFAULT '0', `exp_total` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`uid`)) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_filter` (`id` int(11) NOT NULL AUTO_INCREMENT, `word` varchar(100) NOT NULL DEFAULT '', `word_type` varchar(12) NOT NULL DEFAULT 'word', PRIMARY KEY (`id`), KEY `word_type` (`word_type`)) ENGINE=InnoDB AUTO_INCREMENT=162 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_friends` (`id` int(11) NOT NULL AUTO_INCREMENT, `hunter` int(11) NOT NULL DEFAULT '0', `target` int(11) NOT NULL DEFAULT '0', `fstatus` int(1) NOT NULL DEFAULT '1', `viewed` int(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `hunter` (`hunter`), KEY `target` (`target`)) ENGINE=InnoDB AUTO_INCREMENT=457 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_gift` (`id` int(11) NOT NULL AUTO_INCREMENT, `gift_image` varchar(100) NOT NULL DEFAULT '', `gift_title` varchar(300) NOT NULL DEFAULT 'Gift', `gift_method` int(1) NOT NULL DEFAULT '1', `gift_cost` int(11) NOT NULL DEFAULT '0', `gift_rank` int(3) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `gift_rank` (`gift_rank`)) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_group_call` (`call_id` int(11) NOT NULL AUTO_INCREMENT, `call_name` varchar(100) NOT NULL DEFAULT '', `call_creator` int(11) NOT NULL DEFAULT '0', `call_type` int(1) NOT NULL DEFAULT '1', `call_active` int(11) NOT NULL DEFAULT '0', `call_time` int(11) NOT NULL DEFAULT '0', `call_paid` int(11) NOT NULL DEFAULT '0', `call_method` int(1) NOT NULL DEFAULT '0', `call_room` varchar(100) NOT NULL DEFAULT '', `call_password` varchar(40) NOT NULL DEFAULT '', `call_date` int(1) NOT NULL DEFAULT '0', `call_access` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`call_id`), KEY `call_date` (`call_date`)) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_history` (`id` int(11) NOT NULL AUTO_INCREMENT, `hunter` int(11) NOT NULL DEFAULT '0', `target` int(11) NOT NULL DEFAULT '0', `htype` varchar(30) NOT NULL DEFAULT '', `reason` varchar(2000) NOT NULL DEFAULT '', `delay` int(11) NOT NULL DEFAULT '0', `history_date` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `hunter` (`hunter`), KEY `target` (`target`), KEY `htype` (`htype`)) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_ignore` (`ignore_id` int(11) NOT NULL AUTO_INCREMENT, `ignorer` int(11) NOT NULL DEFAULT '0', `ignored` int(11) NOT NULL DEFAULT '0', `ignore_date` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`ignore_id`), KEY `ignorer` (`ignorer`), KEY `ignored` (`ignored`), KEY `ignore_date` (`ignore_date`)) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_login` (`id` int(11) NOT NULL AUTO_INCREMENT, `logip` varchar(50) NOT NULL DEFAULT '', `logdate` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `logip` (`logip`), KEY `logdate` (`logdate`)) ENGINE=InnoDB AUTO_INCREMENT=237 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_mail` (`id` int(11) NOT NULL AUTO_INCREMENT, `mail_user` int(11) NOT NULL DEFAULT '0', `mail_date` int(11) NOT NULL DEFAULT '0', `mail_type` varchar(50) NOT NULL DEFAULT '', PRIMARY KEY (`id`), KEY `mail_user` (`mail_user`), KEY `mail_date` (`mail_date`), KEY `mail_type` (`mail_type`)) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_name` (`id` int(11) NOT NULL AUTO_INCREMENT, `uid` int(11) NOT NULL DEFAULT '0', `uname` varchar(100) NOT NULL DEFAULT '', `udate` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `uid` (`uid`)) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_news` (`id` int(11) NOT NULL AUTO_INCREMENT, `news_comment` int(1) NOT NULL DEFAULT '1', `news_like` int(1) NOT NULL DEFAULT '1', `news_poster` int(11) NOT NULL DEFAULT '0', `news_message` varchar(3000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `news_file` varchar(1000) NOT NULL DEFAULT '', `news_file_type` varchar(20) NOT NULL DEFAULT '', `news_date` int(11) NOT NULL DEFAULT '1', PRIMARY KEY (`id`), KEY `news_date` (`news_date`)) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_news_like` (`id` int(11) NOT NULL AUTO_INCREMENT, `uid` int(11) NOT NULL DEFAULT '0', `liked_uid` int(11) NOT NULL DEFAULT '0', `like_type` int(1) NOT NULL DEFAULT '1', `like_post` int(11) NOT NULL DEFAULT '1', `like_date` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `uid` (`uid`), KEY `liked_uid` (`liked_uid`), KEY `like_date` (`like_date`)) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_news_reply` (`reply_id` int(11) NOT NULL AUTO_INCREMENT, `parent_id` int(11) NOT NULL DEFAULT '0', `reply_user` int(11) NOT NULL DEFAULT '0', `reply_date` int(11) NOT NULL DEFAULT '0', `reply_content` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `reply_uid` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`reply_id`), KEY `parent_id` (`parent_id`), KEY `reply_user` (`reply_user`), KEY `reply_date` (`reply_date`), KEY `reply_uid` (`reply_uid`)) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_notification` (`id` int(11) NOT NULL AUTO_INCREMENT, `notifier` int(11) NOT NULL DEFAULT '0', `notified` int(11) NOT NULL DEFAULT '0', `notify_type` varchar(30) NOT NULL DEFAULT '', `notify_date` int(11) NOT NULL DEFAULT '0', `notify_source` varchar(30) NOT NULL DEFAULT '', `notify_id` int(11) NOT NULL DEFAULT '0', `notify_rank` int(11) NOT NULL DEFAULT '0', `notify_delay` int(11) NOT NULL DEFAULT '0', `notify_reason` varchar(2000) NOT NULL DEFAULT '', `notify_view` int(1) NOT NULL DEFAULT '0', `notify_custom` varchar(2000) NOT NULL DEFAULT '', `notify_custom2` varchar(2000) NOT NULL DEFAULT '', `notify_icon` varchar(30) NOT NULL DEFAULT '', `notify_class` varchar(50) NOT NULL DEFAULT '', `notify_data` varchar(300) NOT NULL DEFAULT '', PRIMARY KEY (`id`), KEY `notifier` (`notifier`), KEY `notified` (`notified`), KEY `notify_date` (`notify_date`), KEY `notify_source` (`notify_source`), KEY `notify_id` (`notify_id`), KEY `notify_view` (`notify_view`)) ENGINE=InnoDB AUTO_INCREMENT=1216 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_page` (`page_id` int(11) NOT NULL AUTO_INCREMENT, `page_name` varchar(100) NOT NULL DEFAULT '', `page_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, PRIMARY KEY (`page_id`)) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_post` (`post_id` int(11) NOT NULL AUTO_INCREMENT, `post_comment` int(1) NOT NULL DEFAULT '1', `post_like` int(1) NOT NULL DEFAULT '1', `post_user` int(11) NOT NULL DEFAULT '0', `post_date` int(11) NOT NULL DEFAULT '0', `post_content` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `post_file` varchar(1000) NOT NULL DEFAULT '', `post_file_type` varchar(20) NOT NULL DEFAULT '', `post_actual` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`post_id`), KEY `post_user` (`post_user`), KEY `post_date` (`post_date`)) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_post_like` (`id` int(11) NOT NULL AUTO_INCREMENT, `uid` int(11) NOT NULL DEFAULT '0', `liked_uid` int(11) NOT NULL DEFAULT '0', `like_type` int(1) NOT NULL DEFAULT '1', `like_post` int(11) NOT NULL DEFAULT '1', `like_date` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `uid` (`uid`), KEY `liked_uid` (`liked_uid`), KEY `like_date` (`like_date`)) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_post_reply` (`reply_id` int(11) NOT NULL AUTO_INCREMENT, `parent_id` int(11) NOT NULL DEFAULT '0', `reply_user` int(11) NOT NULL DEFAULT '0', `reply_date` int(11) NOT NULL DEFAULT '0', `reply_content` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `reply_uid` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`reply_id`), KEY `parent_id` (`parent_id`), KEY `reply_user` (`reply_user`), KEY `reply_date` (`reply_date`), KEY `reply_uid` (`reply_uid`)) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_private` (`id` int(11) NOT NULL AUTO_INCREMENT, `time` int(11) NOT NULL DEFAULT '0', `message` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `hunter` int(11) NOT NULL DEFAULT '0', `target` int(11) NOT NULL DEFAULT '0', `status` int(1) NOT NULL DEFAULT '0', `view` int(1) NOT NULL DEFAULT '0', `file` int(11) NOT NULL DEFAULT '0', `qpost` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `hunter` (`hunter`), KEY `target` (`target`), KEY `time` (`time`), KEY `status` (`status`), KEY `qpost` (`qpost`)) ENGINE=InnoDB AUTO_INCREMENT=8452 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_pro_like` (`id` int(11) NOT NULL AUTO_INCREMENT, `hunter` int(11) NOT NULL DEFAULT '0', `target` int(11) NOT NULL DEFAULT '0', `like_date` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `hunter` (`hunter`), KEY `target` (`target`), KEY `like_date` (`like_date`)) ENGINE=InnoDB AUTO_INCREMENT=657 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_radio_stream` (`id` int(11) NOT NULL AUTO_INCREMENT, `stream_url` varchar(300) NOT NULL DEFAULT '', `stream_alias` varchar(50) NOT NULL DEFAULT '', PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_report` (`report_id` int(11) NOT NULL AUTO_INCREMENT, `report_type` int(2) NOT NULL DEFAULT '0', `report_user` int(11) NOT NULL DEFAULT '0', `report_target` int(11) NOT NULL DEFAULT '0', `report_post` int(11) NOT NULL DEFAULT '0', `report_reason` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `report_room` int(11) NOT NULL DEFAULT '0', `report_date` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`report_id`), KEY `report_user` (`report_user`), KEY `report_target` (`report_target`)) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_room_action` (`id` int(11) NOT NULL AUTO_INCREMENT, `action_room` int(11) NOT NULL DEFAULT '0', `action_user` int(11) NOT NULL DEFAULT '0', `action_muted` int(1) NOT NULL DEFAULT '0', `action_blocked` int(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `action_user` (`action_user`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_room_staff` (`id` int(11) NOT NULL AUTO_INCREMENT, `room_id` int(11) NOT NULL DEFAULT '0', `room_staff` int(11) NOT NULL DEFAULT '0', `room_rank` int(1) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `room_id` (`room_id`), KEY `room_staff` (`room_staff`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_rooms` (`room_id` int(11) NOT NULL AUTO_INCREMENT, `room_name` varchar(40) NOT NULL DEFAULT '', `topic` varchar(1000) NOT NULL DEFAULT '', `access` int(1) NOT NULL DEFAULT '0', `description` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `room_icon` varchar(100) NOT NULL DEFAULT 'default_room.png', `max_user` int(3) NOT NULL DEFAULT '0', `password` varchar(40) NOT NULL DEFAULT '', `room_system` int(1) NOT NULL DEFAULT '1', `room_action` int(11) NOT NULL DEFAULT '0', `room_player_id` int(11) NOT NULL DEFAULT '0', `room_creator` int(11) NOT NULL DEFAULT '0', `rcaction` int(11) NOT NULL DEFAULT '0', `rldelete` varchar(300) NOT NULL DEFAULT '', `rltime` int(11) NOT NULL DEFAULT '0', `pinned` int(1) NOT NULL DEFAULT '0', PRIMARY KEY (`room_id`), KEY `room_system` (`room_system`), KEY `room_action` (`room_action`)) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_setting` (`id` int(11) NOT NULL AUTO_INCREMENT, `title` varchar(200) NOT NULL DEFAULT 'Codychat', `site_description` varchar(600) NOT NULL DEFAULT '', `site_keyword` varchar(600) NOT NULL DEFAULT '', `login_page` varchar(50) NOT NULL DEFAULT 'Default', `dat` varchar(100) NOT NULL DEFAULT '', `system_id` int(11) NOT NULL DEFAULT '0', `registration` int(1) NOT NULL DEFAULT '1', `reg_act` int(1) NOT NULL DEFAULT '0', `reg_delay` int(6) NOT NULL DEFAULT '5', `maint_mode` int(1) NOT NULL DEFAULT '0', `use_bridge` int(1) NOT NULL DEFAULT '0', `use_lobby` int(1) NOT NULL DEFAULT '0', `use_logs` int(5) NOT NULL DEFAULT '12345', `allow_guest` int(1) NOT NULL DEFAULT '0', `guest_form` int(1) NOT NULL DEFAULT '0', `default_theme` varchar(30) NOT NULL DEFAULT 'Lite', `allow_theme` int(3) NOT NULL DEFAULT '100', `max_avatar` int(4) NOT NULL DEFAULT '1', `max_cover` int(4) NOT NULL DEFAULT '1', `max_ricon` int(4) NOT NULL DEFAULT '1', `file_weight` int(5) NOT NULL DEFAULT '2', `domain` varchar(100) NOT NULL DEFAULT '', `allow_avatar` int(3) NOT NULL DEFAULT '1', `allow_cover` int(3) NOT NULL DEFAULT '100', `allow_gcover` int(3) NOT NULL DEFAULT '100', `allow_name_color` int(3) NOT NULL DEFAULT '100', `allow_name_grad` int(3) NOT NULL DEFAULT '100', `allow_name_neon` int(3) NOT NULL DEFAULT '100', `allow_name_font` int(3) NOT NULL DEFAULT '100', `allow_history` int(3) NOT NULL DEFAULT '100', `allow_main` int(3) NOT NULL DEFAULT '0', `allow_private` int(3) NOT NULL DEFAULT '0', `allow_cupload` int(3) NOT NULL DEFAULT '100', `allow_pupload` int(3) NOT NULL DEFAULT '100', `allow_wupload` int(3) NOT NULL DEFAULT '100', `allow_direct` int(3) NOT NULL DEFAULT '0', `allow_room` int(3) NOT NULL DEFAULT '100', `allow_vroom` int(3) NOT NULL DEFAULT '100', `allow_quote` int(3) NOT NULL DEFAULT '100', `allow_pquote` int(3) NOT NULL DEFAULT '100', `allow_video` int(3) NOT NULL DEFAULT '100', `allow_audio` int(3) NOT NULL DEFAULT '100', `allow_zip` int(3) NOT NULL DEFAULT '100', `use_like` int(1) NOT NULL DEFAULT '0', `use_flag` int(1) NOT NULL DEFAULT '0', `use_gender` int(1) NOT NULL DEFAULT '0', `use_geo` int(1) NOT NULL DEFAULT '1', `version` varchar(5) NOT NULL DEFAULT '8.0', `bbfv` varchar(5) NOT NULL DEFAULT '1.0', `language` varchar(20) NOT NULL DEFAULT 'English', `activation` int(1) NOT NULL DEFAULT '0', `use_wall` int(1) NOT NULL DEFAULT '1', `timezone` varchar(60) NOT NULL DEFAULT 'America/Toronto', `boom` varchar(50) NOT NULL DEFAULT '', `min_age` int(2) NOT NULL DEFAULT '14', `allow_colors` int(3) NOT NULL DEFAULT '100', `allow_grad` int(3) NOT NULL DEFAULT '100', `allow_neon` int(3) NOT NULL DEFAULT '100', `allow_font` int(3) NOT NULL DEFAULT '100', `allow_mood` int(3) NOT NULL DEFAULT '100', `allow_scontent` int(3) NOT NULL DEFAULT '100', `allow_rnews` int(3) NOT NULL DEFAULT '100', `allow_about` int(3) NOT NULL DEFAULT '100', `allow_report` int(3) NOT NULL DEFAULT '100', `emo_plus` int(3) NOT NULL DEFAULT '100', `speed` int(4) NOT NULL DEFAULT '3000', `privload` int(4) NOT NULL DEFAULT '2500', `player_id` int(11) NOT NULL DEFAULT '0', `max_main` int(4) NOT NULL DEFAULT '300', `max_private` int(4) NOT NULL DEFAULT '200', `word_action` int(1) NOT NULL DEFAULT '0', `word_delay` int(11) NOT NULL DEFAULT '5', `spam_action` int(1) NOT NULL DEFAULT '0', `spam_delay` int(11) NOT NULL DEFAULT '60', `flood_action` int(1) NOT NULL DEFAULT '1', `flood_delay` int(11) NOT NULL DEFAULT '5', `vpn_delay` int(11) NOT NULL DEFAULT '5', `email_filter` int(1) NOT NULL DEFAULT '0', `max_username` int(2) NOT NULL DEFAULT '18', `chat_delete` int(11) NOT NULL DEFAULT '0', `private_delete` int(11) NOT NULL DEFAULT '0', `wall_delete` int(11) NOT NULL DEFAULT '0', `member_delete` int(11) NOT NULL DEFAULT '0', `room_delete` int(11) NOT NULL DEFAULT '0', `ignore_delete` int(11) NOT NULL DEFAULT '0', `max_offcount` int(3) NOT NULL DEFAULT '0', `site_email` varchar(200) NOT NULL DEFAULT 'yoursiteemail@email.com', `email_from` varchar(100) NOT NULL DEFAULT 'Codychat', `mail_type` varchar(10) NOT NULL DEFAULT 'mail', `smtp_host` varchar(100) NOT NULL DEFAULT '', `smtp_username` varchar(100) NOT NULL DEFAULT '', `smtp_password` varchar(100) NOT NULL DEFAULT '', `smtp_port` varchar(10) NOT NULL DEFAULT '465', `smtp_type` varchar(10) NOT NULL DEFAULT 'tls', `allow_name` int(3) NOT NULL DEFAULT '100', `act_delay` int(4) NOT NULL DEFAULT '0', `cookie_law` int(1) NOT NULL DEFAULT '0', `use_recapt` int(1) NOT NULL DEFAULT '0', `recapt_key` varchar(100) NOT NULL DEFAULT '', `recapt_secret` varchar(100) NOT NULL DEFAULT '', `can_raction` int(3) NOT NULL DEFAULT '100', `can_mute` int(3) NOT NULL DEFAULT '100', `can_warn` int(3) NOT NULL DEFAULT '100', `can_kick` int(3) NOT NULL DEFAULT '100', `can_ghost` int(3) NOT NULL DEFAULT '100', `can_ban` int(3) NOT NULL DEFAULT '100', `can_delete` int(3) NOT NULL DEFAULT '100', `can_modavat` int(3) NOT NULL DEFAULT '100', `can_modcover` int(3) NOT NULL DEFAULT '100', `can_modmood` int(3) NOT NULL DEFAULT '100', `can_modabout` int(3) NOT NULL DEFAULT '100', `can_modcolor` int(3) NOT NULL DEFAULT '100', `can_modname` int(3) NOT NULL DEFAULT '100', `can_modemail` int(3) NOT NULL DEFAULT '100', `can_modpass` int(3) NOT NULL DEFAULT '100', `can_modblock` int(3) NOT NULL DEFAULT '100', `can_modvpn` int(3) NOT NULL DEFAULT '100', `can_verify` int(3) NOT NULL DEFAULT '100', `can_vip` int(3) NOT NULL DEFAULT '100', `can_vemail` int(3) NOT NULL DEFAULT '100', `can_vghost` int(3) NOT NULL DEFAULT '999', `can_vother` int(3) NOT NULL DEFAULT '100', `can_vname` int(3) NOT NULL DEFAULT '100', `can_vhistory` int(3) NOT NULL DEFAULT '100', `can_note` int(3) NOT NULL DEFAULT '100', `can_news` int(3) NOT NULL DEFAULT '100', `can_rank` int(3) NOT NULL DEFAULT '100', `can_auth` int(3) NOT NULL DEFAULT '100', `can_inv` int(3) NOT NULL DEFAULT '100', `can_clear` int(3) NOT NULL DEFAULT '100', `can_bpriv` int(3) NOT NULL DEFAULT '100', `can_rpass` int(3) NOT NULL DEFAULT '100', `can_topic` int(3) NOT NULL DEFAULT '100', `can_content` int(3) NOT NULL DEFAULT '100', `can_maddons` int(3) NOT NULL DEFAULT '100', `can_mroom` int(3) NOT NULL DEFAULT '100', `can_mfilter` int(3) NOT NULL DEFAULT '100', `can_dj` int(3) NOT NULL DEFAULT '100', `can_cuser` int(3) NOT NULL DEFAULT '100', `can_mip` int(3) NOT NULL DEFAULT '100', `can_mlogs` int(3) NOT NULL DEFAULT '100', `can_mplay` int(3) NOT NULL DEFAULT '100', `can_mcontact` int(3) NOT NULL DEFAULT '100', `use_vpn` int(1) NOT NULL DEFAULT '0', `vpn_key` varchar(80) NOT NULL DEFAULT '', `coppa` int(1) NOT NULL DEFAULT '0', `redis_status` int(1) NOT NULL DEFAULT '0', `max_flood` int(2) NOT NULL DEFAULT '6', `max_emo` int(2) NOT NULL DEFAULT '10', `max_room` int(1) NOT NULL DEFAULT '1', `max_reg` int(2) NOT NULL DEFAULT '5', `max_greg` int(2) NOT NULL DEFAULT '25', `curset` int(11) NOT NULL DEFAULT '0', `can_rclear` int(1) NOT NULL DEFAULT '6', `can_rlogs` int(1) NOT NULL DEFAULT '6', `use_level` int(1) NOT NULL DEFAULT '0', `level_mode` int(4) NOT NULL DEFAULT '10', `exp_chat` int(3) NOT NULL DEFAULT '1', `exp_priv` int(3) NOT NULL DEFAULT '1', `exp_gift` int(3) NOT NULL DEFAULT '1', `exp_post` int(3) NOT NULL DEFAULT '1', `use_rate` int(1) NOT NULL DEFAULT '0', `rate_limit` int(3) NOT NULL DEFAULT '50', `word_proof` int(3) NOT NULL DEFAULT '100', `use_badge` int(1) NOT NULL DEFAULT '0', `bachat` int(2) NOT NULL DEFAULT '10', `bagift` int(2) NOT NULL DEFAULT '10', `balike` int(2) NOT NULL DEFAULT '10', `bafriend` int(2) NOT NULL DEFAULT '10', `baruby` int(6) NOT NULL DEFAULT '100', `bagold` int(6) NOT NULL DEFAULT '5000', `babeat` int(6) NOT NULL DEFAULT '1000', `use_gift` int(1) NOT NULL DEFAULT '0', `use_wallet` int(1) NOT NULL DEFAULT '0', `can_vwallet` int(3) NOT NULL DEFAULT '100', `can_swallet` int(3) NOT NULL DEFAULT '100', `can_ruby` int(3) NOT NULL DEFAULT '100', `ruby_delay` int(6) NOT NULL DEFAULT '60', `ruby_base` int(3) NOT NULL DEFAULT '0', `can_gold` int(3) NOT NULL DEFAULT '100', `gold_delay` int(6) NOT NULL DEFAULT '2', `gold_base` int(3) NOT NULL DEFAULT '0', `use_call` int(1) NOT NULL DEFAULT '0', `can_acall` int(3) NOT NULL DEFAULT '100', `can_vcall` int(3) NOT NULL DEFAULT '100', `call_appid` varchar(50) NOT NULL DEFAULT '', `call_secret` varchar(50) NOT NULL DEFAULT '', `call_max` int(5) NOT NULL DEFAULT '60', `call_method` int(1) NOT NULL DEFAULT '1', `call_cost` int(6) NOT NULL DEFAULT '0', `live_url` varchar(60) NOT NULL DEFAULT '', `live_appid` varchar(50) NOT NULL DEFAULT '', `live_secret` varchar(100) NOT NULL DEFAULT '', `use_app` int(1) NOT NULL DEFAULT '0', `app_name` varchar(30) NOT NULL DEFAULT 'Chat', `app_color` varchar(10) NOT NULL DEFAULT '#000000', `openai_key` varchar(200) NOT NULL DEFAULT '', `mod_cat` varchar(200) NOT NULL DEFAULT '', `img_mod` int(1) NOT NULL DEFAULT '0', `can_gcall` int(3) NOT NULL DEFAULT '100', `can_mgcall` int(3) NOT NULL DEFAULT '100', `max_gcall` int(5) NOT NULL DEFAULT '180', `can_agcall` int(3) NOT NULL DEFAULT '100', `can_cgcall` int(3) NOT NULL DEFAULT '100', `can_vgcall` int(3) NOT NULL DEFAULT '100', PRIMARY KEY (`id`)) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_temp` (`id` int(11) NOT NULL AUTO_INCREMENT, `temp_user` int(11) NOT NULL DEFAULT '0', `temp_key` varchar(200) NOT NULL DEFAULT '', `temp_date` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `temp_user` (`temp_user`), KEY `temp_key` (`temp_key`), KEY `temp_date` (`temp_date`)) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_upload` (`id` int(11) NOT NULL AUTO_INCREMENT, `file_name` varchar(300) NOT NULL DEFAULT '', `file_key` varchar(100) NOT NULL DEFAULT '', `date_sent` int(11) NOT NULL DEFAULT '0', `file_user` int(11) NOT NULL DEFAULT '0', `file_zone` varchar(30) NOT NULL DEFAULT '1', `file_type` varchar(30) NOT NULL DEFAULT '', `file_complete` int(1) NOT NULL DEFAULT '1', `relative_post` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `date_sent` (`date_sent`), KEY `file_zone` (`file_zone`), KEY `file_complete` (`file_complete`)) ENGINE=InnoDB AUTO_INCREMENT=694 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_users` (`user_id` int(11) NOT NULL AUTO_INCREMENT, `user_name` varchar(60) NOT NULL DEFAULT '', `user_password` varchar(60) NOT NULL DEFAULT '', `user_email` varchar(80) NOT NULL DEFAULT '', `user_smail` varchar(80) NOT NULL DEFAULT '', `sub_id` varchar(50) NOT NULL DEFAULT '', `user_ip` varchar(50) NOT NULL DEFAULT '', `user_auth` int(1) NOT NULL DEFAULT '0', `user_join` int(11) NOT NULL DEFAULT '0', `user_move` int(11) NOT NULL DEFAULT '0', `last_action` int(11) NOT NULL DEFAULT '0', `user_beat` int(11) NOT NULL DEFAULT '0', `user_language` varchar(30) NOT NULL DEFAULT 'English', `user_timezone` varchar(60) NOT NULL DEFAULT 'America/Toronto', `user_status` int(3) NOT NULL DEFAULT '1', `user_color` varchar(20) NOT NULL DEFAULT 'user', `user_font` varchar(10) NOT NULL DEFAULT '', `bccolor` varchar(10) NOT NULL DEFAULT '', `bcbold` varchar(10) NOT NULL DEFAULT '', `bcfont` varchar(10) NOT NULL DEFAULT '', `user_rank` int(3) NOT NULL DEFAULT '1', `user_level` int(11) NOT NULL DEFAULT '1', `vip_end` int(11) NOT NULL DEFAULT '0', `user_dj` int(1) NOT NULL DEFAULT '0', `user_onair` int(1) NOT NULL DEFAULT '0', `user_roomid` int(11) NOT NULL DEFAULT '1', `user_theme` varchar(30) NOT NULL DEFAULT 'system', `user_sex` int(1) NOT NULL DEFAULT '0', `user_age` int(3) NOT NULL DEFAULT '0', `user_tumb` varchar(200) NOT NULL DEFAULT 'default_avatar.png', `user_cover` varchar(100) NOT NULL DEFAULT '', `user_sound` int(10) NOT NULL DEFAULT '12345', `user_verify` int(1) NOT NULL DEFAULT '0', `valid_key` varchar(64) NOT NULL DEFAULT '', `country` varchar(10) NOT NULL DEFAULT '', `session_id` int(11) NOT NULL DEFAULT '1', `pcount` int(11) NOT NULL DEFAULT '0', `user_news` int(11) NOT NULL DEFAULT '0', `user_ghost` int(11) NOT NULL DEFAULT '0', `user_mute` int(11) NOT NULL DEFAULT '0', `user_rmute` int(11) NOT NULL DEFAULT '0', `user_mmute` int(11) NOT NULL DEFAULT '0', `user_pmute` int(11) NOT NULL DEFAULT '0', `user_banned` int(11) NOT NULL DEFAULT '0', `user_kick` int(11) NOT NULL DEFAULT '0', `kick_msg` varchar(300) NOT NULL DEFAULT '', `warn_msg` varchar(500) NOT NULL DEFAULT '', `ban_msg` varchar(300) NOT NULL DEFAULT '', `user_role` int(1) NOT NULL DEFAULT '0', `user_action` int(11) NOT NULL DEFAULT '0', `room_mute` int(1) NOT NULL DEFAULT '0', `user_mood` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `user_bot` int(1) NOT NULL DEFAULT '0', `naction` int(11) NOT NULL DEFAULT '1', `user_private` int(2) NOT NULL DEFAULT '1', `user_delete` int(11) NOT NULL DEFAULT '0', `user_gold` int(11) NOT NULL DEFAULT '0', `user_sgold` int(11) NOT NULL DEFAULT '0', `last_gold` int(11) NOT NULL DEFAULT '0', `user_ruby` int(11) NOT NULL DEFAULT '0', `user_sruby` int(11) NOT NULL DEFAULT '0', `last_ruby` int(11) NOT NULL DEFAULT '0', `pdel` varchar(300) NOT NULL DEFAULT '', `pdeltime` int(11) NOT NULL DEFAULT '0', `ulogin` int(1) NOT NULL DEFAULT '0', `uvpn` int(1) NOT NULL DEFAULT '1', `bupload` int(1) NOT NULL DEFAULT '0', `bcall` int(1) NOT NULL DEFAULT '0', `bnews` int(1) NOT NULL DEFAULT '0', `ashare` int(1) NOT NULL DEFAULT '1', `sshare` int(1) NOT NULL DEFAULT '1', `lshare` int(1) NOT NULL DEFAULT '1', `fshare` int(1) NOT NULL DEFAULT '1', `gshare` int(1) NOT NULL DEFAULT '1', `ucall` int(11) NOT NULL DEFAULT '0', `user_call` int(1) NOT NULL DEFAULT '1', `ufriend` int(1) NOT NULL DEFAULT '1', `ugcall` int(1) NOT NULL DEFAULT '0', `user_wall` int(11) NOT NULL DEFAULT '0', `user_bubble` int(1) NOT NULL DEFAULT '0', PRIMARY KEY (`user_id`), KEY `user_ip` (`user_ip`), KEY `user_email` (`user_email`), KEY `user_smail` (`user_smail`), KEY `user_roomid` (`user_roomid`), KEY `last_action` (`last_action`), KEY `user_rank` (`user_rank`), KEY `user_bot` (`user_bot`), KEY `user_status` (`user_status`), KEY `user_delete` (`user_delete`), KEY `vip_end` (`vip_end`)) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_users_data` (`uid` int(11) NOT NULL AUTO_INCREMENT, `badge_auth` int(1) NOT NULL DEFAULT '0', `badge_member` int(11) NOT NULL DEFAULT '0', `badge_chat` int(11) NOT NULL DEFAULT '0', `badge_top` int(11) NOT NULL DEFAULT '0', `badge_qtop` int(11) NOT NULL DEFAULT '0', `badge_ruby` int(11) NOT NULL DEFAULT '0', `badge_beat` int(11) NOT NULL DEFAULT '0', `badge_gold` int(11) NOT NULL DEFAULT '0', `badge_like` int(11) NOT NULL DEFAULT '0', `badge_friend` int(11) NOT NULL DEFAULT '0', `badge_gift` int(11) NOT NULL DEFAULT '0', `user_about` varchar(4000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', `user_note` varchar(4000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '', PRIMARY KEY (`uid`)) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_users_gift` (`id` int(11) NOT NULL AUTO_INCREMENT, `target` int(11) NOT NULL DEFAULT '0', `gift` int(11) NOT NULL DEFAULT '0', `gift_count` int(11) NOT NULL DEFAULT '1', `gift_date` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `target` (`target`), KEY `gift` (`gift`), KEY `gift_date` (`gift_date`)) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_vip` (`id` int(11) NOT NULL AUTO_INCREMENT, `userid` int(11) NOT NULL DEFAULT '11', `userp` varchar(50) NOT NULL DEFAULT '', `plan` varchar(20) NOT NULL DEFAULT '', `price` varchar(20) NOT NULL DEFAULT '', `vdate` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `userid` (`userid`)) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
$mysqli->query("CREATE TABLE `boom_vpn` (`id` int(11) NOT NULL AUTO_INCREMENT, `vip` varchar(100) NOT NULL DEFAULT '0', `vtype` int(11) NOT NULL DEFAULT '0', `vdate` int(11) NOT NULL DEFAULT '0', PRIMARY KEY (`id`), KEY `vip` (`vip`), KEY `vtype` (`vtype`), KEY `vdate` (`vdate`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

$database_write = "<?php\r\n" .
"// base system prefix\r\n" .
"define('BOOM_PREFIX', '$prefix');\r\n\r\n" .
"// optional base domain\r\n" .
"define('BOOM_DOMAIN', '$domain/');\r\n\r\n" .
"// default redis configuration\r\n" .
"define('REDIS_IP', '127.0.0.1');\r\n" .
"define('REDIS_PORT', 6379);\r\n" .
"define('REDIS_TIMEOUT', 0.2);\r\n" .
"define('REDIS_PASS', '');\r\n\r\n" .
"// you can edit these lines to configure new setting for your chat\r\n" .
"define('BOOM_DHOST', '" . $_POST["db_host"] . "');\r\n" .
"define('BOOM_DUSER', '" . $_POST["db_user"] . "');\r\n" .
"define('BOOM_DPASS', '" . $_POST["db_pass"] . "');\r\n" .
"define('BOOM_DNAME', '" . $_POST["db_name"] . "');\r\n\r\n" .
"// base system main path do not modify\r\n" .
"define('BOOM_PATH', dirname(__DIR__));\r\n\r\n" .
"// do not modify those variables\r\n" .
"define('BOOM_CRYPT', '" . $encrypt . "');\r\n" .
"define('BOOM_INSTALL', 1);\r\n" .
"define('BOOM', 1);\r\n" .
"?>";

$database_file = fopen(BOOM_PATH . "/system/database.php", "w+");
fwrite($database_file, $database_write);
fclose($database_file);

$settings_write = "<?php\r\n";
$settings_write .= "\$setting['id'] = '1';\r\n";
$settings_write .= "\$setting['title'] = '$title';\r\n";
$settings_write .= "\$setting['site_description'] = '';\r\n";
$settings_write .= "\$setting['site_keyword'] = '';\r\n";
$settings_write .= "\$setting['login_page'] = 'Default';\r\n";
$settings_write .= "\$setting['dat'] = '$password';\r\n";
$settings_write .= "\$setting['system_id'] = '2';\r\n";
$settings_write .= "\$setting['registration'] = '1';\r\n";
$settings_write .= "\$setting['reg_act'] = '0';\r\n";
$settings_write .= "\$setting['reg_delay'] = '5';\r\n";
$settings_write .= "\$setting['maint_mode'] = '0';\r\n";
$settings_write .= "\$setting['use_bridge'] = '0';\r\n";
$settings_write .= "\$setting['use_lobby'] = '0';\r\n";
$settings_write .= "\$setting['use_logs'] = '123';\r\n";
$settings_write .= "\$setting['allow_guest'] = '0';\r\n";
$settings_write .= "\$setting['guest_form'] = '0';\r\n";
$settings_write .= "\$setting['default_theme'] = 'Dark';\r\n";
$settings_write .= "\$setting['allow_theme'] = '50';\r\n";
$settings_write .= "\$setting['max_avatar'] = '4';\r\n";
$settings_write .= "\$setting['max_cover'] = '9';\r\n";
$settings_write .= "\$setting['max_ricon'] = '6';\r\n";
$settings_write .= "\$setting['file_weight'] = '10';\r\n";
$settings_write .= "\$setting['domain'] = '$domain';\r\n";
$settings_write .= "\$setting['allow_avatar'] = '1';\r\n";
$settings_write .= "\$setting['allow_cover'] = '100';\r\n";
$settings_write .= "\$setting['allow_gcover'] = '100';\r\n";
$settings_write .= "\$setting['allow_name_color'] = '50';\r\n";
$settings_write .= "\$setting['allow_name_grad'] = '50';\r\n";
$settings_write .= "\$setting['allow_name_neon'] = '50';\r\n";
$settings_write .= "\$setting['allow_name_font'] = '50';\r\n";
$settings_write .= "\$setting['allow_history'] = '80';\r\n";
$settings_write .= "\$setting['allow_main'] = '0';\r\n";
$settings_write .= "\$setting['allow_private'] = '1';\r\n";
$settings_write .= "\$setting['allow_cupload'] = '100';\r\n";
$settings_write .= "\$setting['allow_pupload'] = '100';\r\n";
$settings_write .= "\$setting['allow_wupload'] = '100';\r\n";
$settings_write .= "\$setting['allow_direct'] = '70';\r\n";
$settings_write .= "\$setting['allow_room'] = '90';\r\n";
$settings_write .= "\$setting['allow_vroom'] = '90';\r\n";
$settings_write .= "\$setting['allow_quote'] = '50';\r\n";
$settings_write .= "\$setting['allow_pquote'] = '50';\r\n";
$settings_write .= "\$setting['allow_video'] = '100';\r\n";
$settings_write .= "\$setting['allow_audio'] = '100';\r\n";
$settings_write .= "\$setting['allow_zip'] = '100';\r\n";
$settings_write .= "\$setting['use_like'] = '1';\r\n";
$settings_write .= "\$setting['use_flag'] = '1';\r\n";
$settings_write .= "\$setting['use_gender'] = '1';\r\n";
$settings_write .= "\$setting['use_geo'] = '1';\r\n";
$settings_write .= "\$setting['version'] = '8.0';\r\n";
$settings_write .= "\$setting['bbfv'] = '1.03';\r\n";
$settings_write .= "\$setting['language'] = '$language';\r\n";
$settings_write .= "\$setting['activation'] = '0';\r\n";
$settings_write .= "\$setting['use_wall'] = '1';\r\n";
$settings_write .= "\$setting['timezone'] = 'America/Toronto';\r\n";
$settings_write .= "\$setting['boom'] = 'nulledbyblackhunterandfxntxm';\r\n";
$settings_write .= "\$setting['min_age'] = '14';\r\n";
$settings_write .= "\$setting['allow_colors'] = '50';\r\n";
$settings_write .= "\$setting['allow_grad'] = '50';\r\n";
$settings_write .= "\$setting['allow_neon'] = '50';\r\n";
$settings_write .= "\$setting['allow_font'] = '50';\r\n";
$settings_write .= "\$setting['allow_mood'] = '100';\r\n";
$settings_write .= "\$setting['allow_scontent'] = '70';\r\n";
$settings_write .= "\$setting['allow_rnews'] = '1';\r\n";
$settings_write .= "\$setting['allow_about'] = '100';\r\n";
$settings_write .= "\$setting['allow_report'] = '1';\r\n";
$settings_write .= "\$setting['emo_plus'] = '50';\r\n";
$settings_write .= "\$setting['speed'] = '3000';\r\n";
$settings_write .= "\$setting['player_id'] = '1';\r\n";
$settings_write .= "\$setting['max_main'] = '600';\r\n";
$settings_write .= "\$setting['max_private'] = '500';\r\n";
$settings_write .= "\$setting['word_action'] = '2';\r\n";
$settings_write .= "\$setting['word_delay'] = '30';\r\n";
$settings_write .= "\$setting['spam_action'] = '0';\r\n";
$settings_write .= "\$setting['spam_delay'] = '60';\r\n";
$settings_write .= "\$setting['flood_action'] = '1';\r\n";
$settings_write .= "\$setting['flood_delay'] = '5';\r\n";
$settings_write .= "\$setting['vpn_delay'] = '5';\r\n";
$settings_write .= "\$setting['email_filter'] = '0';\r\n";
$settings_write .= "\$setting['max_username'] = '18';\r\n";
$settings_write .= "\$setting['chat_delete'] = '0';\r\n";
$settings_write .= "\$setting['private_delete'] = '0';\r\n";
$settings_write .= "\$setting['wall_delete'] = '0';\r\n";
$settings_write .= "\$setting['member_delete'] = '0';\r\n";
$settings_write .= "\$setting['room_delete'] = '0';\r\n";
$settings_write .= "\$setting['ignore_delete'] = '0';\r\n";
$settings_write .= "\$setting['max_offcount'] = '10';\r\n";
$settings_write .= "\$setting['site_email'] = 'yoursiteemail@email.com';\r\n";
$settings_write .= "\$setting['email_from'] = 'Codychat';\r\n";
$settings_write .= "\$setting['mail_type'] = 'mail';\r\n";
$settings_write .= "\$setting['smtp_host'] = '';\r\n";
$settings_write .= "\$setting['smtp_username'] = '';\r\n";
$settings_write .= "\$setting['smtp_password'] = '';\r\n";
$settings_write .= "\$setting['smtp_port'] = '465';\r\n";
$settings_write .= "\$setting['smtp_type'] = 'tls';\r\n";
$settings_write .= "\$setting['allow_name'] = '100';\r\n";
$settings_write .= "\$setting['act_delay'] = '0';\r\n";
$settings_write .= "\$setting['cookie_law'] = '1';\r\n";
$settings_write .= "\$setting['use_recapt'] = '0';\r\n";
$settings_write .= "\$setting['recapt_key'] = '';\r\n";
$settings_write .= "\$setting['recapt_secret'] = '';\r\n";
$settings_write .= "\$setting['can_raction'] = '90';\r\n";
$settings_write .= "\$setting['can_mute'] = '90';\r\n";
$settings_write .= "\$setting['can_warn'] = '90';\r\n";
$settings_write .= "\$setting['can_kick'] = '90';\r\n";
$settings_write .= "\$setting['can_ghost'] = '90';\r\n";
$settings_write .= "\$setting['can_ban'] = '90';\r\n";
$settings_write .= "\$setting['can_delete'] = '90';\r\n";
$settings_write .= "\$setting['can_modavat'] = '90';\r\n";
$settings_write .= "\$setting['can_modcover'] = '90';\r\n";
$settings_write .= "\$setting['can_modmood'] = '90';\r\n";
$settings_write .= "\$setting['can_modabout'] = '90';\r\n";
$settings_write .= "\$setting['can_modcolor'] = '90';\r\n";
$settings_write .= "\$setting['can_modname'] = '90';\r\n";
$settings_write .= "\$setting['can_modemail'] = '90';\r\n";
$settings_write .= "\$setting['can_modpass'] = '90';\r\n";
$settings_write .= "\$setting['can_modblock'] = '90';\r\n";
$settings_write .= "\$setting['can_modvpn'] = '90';\r\n";
$settings_write .= "\$setting['can_verify'] = '90';\r\n";
$settings_write .= "\$setting['can_vip'] = '90';\r\n";
$settings_write .= "\$setting['can_vemail'] = '90';\r\n";
$settings_write .= "\$setting['can_vghost'] = '90';\r\n";
$settings_write .= "\$setting['can_vother'] = '90';\r\n";
$settings_write .= "\$setting['can_vname'] = '90';\r\n";
$settings_write .= "\$setting['can_vhistory'] = '90';\r\n";
$settings_write .= "\$setting['can_note'] = '90';\r\n";
$settings_write .= "\$setting['can_news'] = '90';\r\n";
$settings_write .= "\$setting['can_rank'] = '90';\r\n";
$settings_write .= "\$setting['can_auth'] = '100';\r\n";
$settings_write .= "\$setting['can_inv'] = '100';\r\n";
$settings_write .= "\$setting['can_clear'] = '80';\r\n";
$settings_write .= "\$setting['can_bpriv'] = '90';\r\n";
$settings_write .= "\$setting['can_rpass'] = '80';\r\n";
$settings_write .= "\$setting['can_topic'] = '80';\r\n";
$settings_write .= "\$setting['can_content'] = '90';\r\n";
$settings_write .= "\$setting['can_maddons'] = '90';\r\n";
$settings_write .= "\$setting['can_mroom'] = '90';\r\n";
$settings_write .= "\$setting['can_mfilter'] = '90';\r\n";
$settings_write .= "\$setting['can_dj'] = '90';\r\n";
$settings_write .= "\$setting['can_cuser'] = '90';\r\n";
$settings_write .= "\$setting['can_mip'] = '90';\r\n";
$settings_write .= "\$setting['can_mlogs'] = '90';\r\n";
$settings_write .= "\$setting['can_mplay'] = '90';\r\n";
$settings_write .= "\$setting['can_mcontact'] = '90';\r\n";
$settings_write .= "\$setting['use_vpn'] = '0';\r\n";
$settings_write .= "\$setting['vpn_key'] = '';\r\n";
$settings_write .= "\$setting['coppa'] = '0';\r\n";
$settings_write .= "\$setting['redis_status'] = '0';\r\n";
$settings_write .= "\$setting['max_flood'] = '6';\r\n";
$settings_write .= "\$setting['max_emo'] = '10';\r\n";
$settings_write .= "\$setting['max_room'] = '1';\r\n";
$settings_write .= "\$setting['max_reg'] = '5';\r\n";
$settings_write .= "\$setting['max_greg'] = '25';\r\n";
$settings_write .= "\$setting['curset'] = '46';\r\n";
$settings_write .= "\$setting['privload'] = '1';\r\n";
$settings_write .= "\$setting['can_rclear'] = '9';\r\n";
$settings_write .= "\$setting['can_rlogs'] = '6';\r\n";
$settings_write .= "\$setting['use_level'] = '1';\r\n";
$settings_write .= "\$setting['level_mode'] = '5';\r\n";
$settings_write .= "\$setting['exp_chat'] = '1';\r\n";
$settings_write .= "\$setting['exp_priv'] = '1';\r\n";
$settings_write .= "\$setting['exp_gift'] = '1';\r\n";
$settings_write .= "\$setting['exp_post'] = '1';\r\n";
$settings_write .= "\$setting['use_rate'] = '0';\r\n";
$settings_write .= "\$setting['rate_limit'] = '50';\r\n";
$settings_write .= "\$setting['word_proof'] = '90';\r\n";
$settings_write .= "\$setting['use_badge'] = '1';\r\n";
$settings_write .= "\$setting['bachat'] = '10';\r\n";
$settings_write .= "\$setting['bagift'] = '10';\r\n";
$settings_write .= "\$setting['balike'] = '10';\r\n";
$settings_write .= "\$setting['bafriend'] = '10';\r\n";
$settings_write .= "\$setting['baruby'] = '100';\r\n";
$settings_write .= "\$setting['bagold'] = '5000';\r\n";
$settings_write .= "\$setting['babeat'] = '1000';\r\n";
$settings_write .= "\$setting['use_gift'] = '1';\r\n";
$settings_write .= "\$setting['use_wallet'] = '1';\r\n";
$settings_write .= "\$setting['can_vwallet'] = '70';\r\n";
$settings_write .= "\$setting['can_swallet'] = '1';\r\n";
$settings_write .= "\$setting['can_ruby'] = '1';\r\n";
$settings_write .= "\$setting['ruby_delay'] = '60';\r\n";
$settings_write .= "\$setting['ruby_base'] = '1';\r\n";
$settings_write .= "\$setting['can_gold'] = '1';\r\n";
$settings_write .= "\$setting['gold_delay'] = '2';\r\n";
$settings_write .= "\$setting['gold_base'] = '2';\r\n";
$settings_write .= "\$setting['use_call'] = '2';\r\n";
$settings_write .= "\$setting['can_acall'] = '100';\r\n";
$settings_write .= "\$setting['can_vcall'] = '100';\r\n";
$settings_write .= "\$setting['call_appid'] = '';\r\n";
$settings_write .= "\$setting['call_secret'] = '';\r\n";
$settings_write .= "\$setting['call_max'] = '60';\r\n";
$settings_write .= "\$setting['call_method'] = '1';\r\n";
$settings_write .= "\$setting['call_cost'] = '1';\r\n";
$settings_write .= "\$setting['live_url'] = '';\r\n";
$settings_write .= "\$setting['live_appid'] = '';\r\n";
$settings_write .= "\$setting['live_secret'] = '';\r\n";
$settings_write .= "\$setting['use_app'] = '1';\r\n";
$settings_write .= "\$setting['app_name'] = 'Chat';\r\n";
$settings_write .= "\$setting['app_color'] = '#000000';\r\n";
$settings_write .= "\$setting['openai_key'] = '';\r\n";
$settings_write .= "\$setting['mod_cat'] = '';\r\n";
$settings_write .= "\$setting['img_mod'] = '0';\r\n";
$settings_write .= "\$setting['can_gcall'] = '100';\r\n";
$settings_write .= "\$setting['can_mgcall'] = '100';\r\n";
$settings_write .= "\$setting['max_gcall'] = '180';\r\n";
$settings_write .= "\$setting['can_agcall'] = '100';\r\n";
$settings_write .= "\$setting['can_vgcall'] = '100';\r\n";
$settings_write .= "?>";

$settings_file = fopen(BOOM_PATH . "/system/settings.php", "w+");
fwrite($settings_file, $settings_write);
fclose($settings_file);

	$mysqli->query("INSERT INTO `boom_setting` (id, title, domain, language, default_theme, system_id, boom) VALUES (1, '" . $title . "', '" . $domain . "', '" . $language . "', 'Dark', 2, 'nulledbyblackhunterandfxntxm')");
	$mysqli->query("INSERT INTO `boom_users` (user_id, user_name, user_email, user_join, user_password, user_language, user_rank,  user_verify, user_timezone) VALUES (1, '" . $username . "', '" . $email . "', '" . $time . "', '" . $password . "', '" . $language . "', '100', '1', 'America/Toronto')");
	$mysqli->query("INSERT INTO `boom_users_data` (`uid`, `badge_auth`, `badge_member`, `badge_chat`, `badge_top`, `badge_qtop`, `badge_ruby`, `badge_beat`, `badge_gold`, `badge_like`, `badge_friend`, `badge_gift`, `user_about`, `user_note`) VALUES ('1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '', '')");
    $mysqli->query("INSERT INTO `boom_exp` (`uid`, `exp_current`, `exp_week`, `exp_month`, `exp_total`) VALUES ('1', '0', '0', '0', '0')");
	$mysqli->query("INSERT INTO `boom_users` (user_id, user_name, user_ip, user_join, user_password, user_rank, user_tumb, user_bot) VALUES (2, 'System', '0.0.0.0', '" . $time . "', '" . randomPass() . "', '69', 'default_system.png', '1')");
    $mysqli->query("INSERT INTO boom_rooms ( room_id, room_name, room_system, room_action, room_creator ) VALUES (1, 'Main room', 1, '" . $time . "', '1')");
    $mysqli->query("INSERT INTO `boom_page` (`page_id`, `page_name`, `page_content`) VALUES (1, 'terms_of_use', '" . $term_content . "'), (2, 'privacy_policy', '" . $privacy_content . "'), (3, 'rules', '" . $help_content . "')");
    $mysqli->query("INSERT INTO boom_filter (word, word_type) VALUES\r\n\t('aol','email'),('att','email'),('comcast','email'),('facebook','email'),('gmail','email'),('gmx','email'),('googlemail','email'),('google','email'),('hotmail','email'),('mac','email'),('me','email'),('mail','email'),('msn','email'),('live','email'),('sbcglobal','email'),\r\n\t('verizon','email'),('yahoo','email'),('email','email'),('fastmail','email'),('games','email'),('hush','email'),('hushmail','email'),('icloud','email'),('iname','email'),('inbox','email'),('lavabit','email'),('love','email'),('outlook','email'),('pobox','email'),\r\n\t('protonmail','email'),('rocketmail','email'),('safe-mail','email'),('wow','email'),('ygm','email'),('ymail','email'),('zoho','email'),('yandex','email'),('bellsouth','email'),('charter','email'),('cox','email'),('earthlink','email'),('juno','email'),\r\n\t('btinternet','email'),('virginmedia','email'),('blueyonder','email'),('freeserve','email'),('ntlworld','email'),('o2','email'),('orange','email'),('sky','email'),('talktalk','email'),('tiscali','email'),('virgin','email'),('wanadoo','email'),\r\n\t('bt','email'),('sina','email'),('qq','email'),('naver','email'),('hanmail','email'),('daum','email'),('nate','email'),('laposte','email'),('gmx','email'),('sfr','email'),('neuf','email'),('free','email'),('online','email'),('t-online','email'),('web','email'),\r\n\t('libero','email'),('virgilio','email'),('alice','email'),('tin','email'),('poste','email'),('teletu','email'),('mail','email'),('rambler','email'),('ya','email'),('list','email'),('skynet','email'),('voo','email'),('tvcablenet','email'),('telenet','email'),\r\n\t('fibertel','email'),('speedy','email'),('arnet','email'),('prodigy.mx','email'),('uol','email'),('bol','email'),('terra','email'),('ig','email'),('itelefonica','email'),('r7','email'),('zipmail','email'),('globo','email'),('globomail','email'),('oi','email')\r\n\t");
    $mysqli->query("INSERT INTO `boom_gift` (`id`, `gift_image`, `gift_title`, `gift_method`, `gift_cost`, `gift_rank`) VALUES (1, 'clover.svg', 'Lucky clover', 1, 100, 1),\r\n(2, 'clown.svg', 'Clown face', 1, 100, 1),\r\n(3, 'coffee.svg', 'Hot coffee cup', 1, 100, 1),\r\n(4, 'cool.svg', 'Cool guy face', 1, 100, 1),\r\n(5, 'crown.svg', 'Nice crown', 1, 100, 1),\r\n(6, 'cure.svg', 'Magic potion', 1, 100, 1),\r\n(7, 'diamond.svg', 'Glossy diamond', 1, 100, 1),\r\n(8, 'fishbone.svg', 'Fish bones', 1, 100, 1),\r\n(9, 'flowers.svg', 'Flower bouquet', 1, 100, 1),\r\n(10, 'gift.svg', 'Gift box', 1, 100, 1),\r\n(11, 'goldpot.svg', 'Pot of gold', 1, 100, 1),\r\n(12, 'hot.svg', 'Hot fire flame', 1, 100, 1),\r\n(13, 'icecream.svg', 'Ice cream', 1, 100, 1),\r\n(14, 'karma.svg', 'Karma back', 1, 100, 1),\r\n(15, 'kiss.svg', 'Gentle kiss', 1, 100, 1),\r\n(16, 'like.svg', 'Tumbs up', 1, 100, 1),\r\n(17, 'love.svg', 'Love', 1, 100, 1),\r\n(18, 'lovepotion.svg', 'Love potion', 1, 100, 1),\r\n(19, 'loverepair.svg', 'Broken heart', 1, 100, 1),\r\n(20, 'medal.svg', 'Winner medal', 1, 100, 1),\r\n(21, 'money.svg', 'Pile of cash', 1, 100, 1),\r\n(22, 'pizza.svg', 'Pizza slice', 1, 100, 1),\r\n(23, 'poison.svg', 'Poison potion', 1, 100, 1),\r\n(24, 'power.svg', 'Energy potion', 1, 100, 1),\r\n(25, 'ring.svg', 'Expensive ring', 1, 100, 1),\r\n(26, 'rose.svg', 'Fresh rose', 1, 100, 1),\r\n(27, 'smile.svg', 'Smiley face', 1, 100, 1),\r\n(28, 'star.svg', 'Night star', 1, 100, 1),\r\n(29, 'teddy.svg', 'Teddy bear', 1, 100, 1),\r\n(30, 'trophy.svg', 'Gold trophy', 1, 100, 1),\r\n(31, 'voodoo.svg', 'Voodo doll', 1, 100, 1),\r\n(34, 'energy.svg', 'Power energy', 1, 100, 1)");
	return boomCode(1);
}

?>