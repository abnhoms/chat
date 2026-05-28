<?php
require __DIR__ . "/../config_session.php";

if (isset($_POST["activate_addons"]) && isset($_POST["addons"]) && canManageAddons()) {
    $this_addons = escape($_POST["addons"]);
    echo boomActivateAddons($this_addons);
    exit;
}
if (isset($_POST["remove_addons"]) && isset($_POST["addons"]) && canManageAddons()) {
    $this_addons = escape($_POST["addons"]);
    echo boomRemoveAddons($this_addons);
    exit;
}

function boomActivateAddons($this_addons)
{
    global $mysqli;
    global $data;
    global $setting;
    global $lang;
    $checkaddons = $mysqli->query("SELECT * FROM boom_addons WHERE addons = '" . $this_addons . "'");
    if (0 < $checkaddons->num_rows) {
        return boomCode(0, ["error" => "This addons is already installed in your system"]);
    }
    require BOOM_PATH . "/addons/" . $this_addons . "/system/install.php";
    if (!isset($ad["name"])) {
        return false;
    }
    $def = ["access" => 0, "max" => 11, "bot_name" => "", "bot_id" => 0, "custom1" => "", "custom2" => "", "custom3" => "", "custom4" => "", "custom5" => "", "custom6" => "", "custom7" => "", "custom8" => "", "custom9" => "", "custom10" => ""];
    $a = array_merge($def, $ad);
    $mysqli->query(" INSERT INTO boom_addons (addons, addons_access, bot_id, custom1, custom2,custom3, custom4, custom5, custom6, custom7, custom8, custom9, custom10)  VALUES  ('" . $a["name"] . "', '" . $a["access"] . "', '" . $a["bot_id"] . "', '" . $a["custom1"] . "','" . $a["custom2"] . "', '" . $a["custom3"] . "', '" . $a["custom4"] . "','" . $a["custom5"] . "', '" . $a["custom6"] . "', '" . $a["custom7"] . "', '" . $a["custom8"] . "', '" . $a["custom9"] . "', '" . $a["custom10"] . "') \r\n\t");
    $last_addons = $mysqli->insert_id;
    $addons_key = sha1(str_rot13($a["name"] . $setting["boom"] . $last_addons));
    $mysqli->query("UPDATE boom_addons SET addons_key = '" . $addons_key . "' WHERE addons = '" . $a["name"] . "'");
    if (isset($a["bot_name"]) && isset($a["bot_type"])) {
        usleep(500000);
        $mysqli->query(" INSERT INTO `boom_users`  ( user_name, user_rank, user_password, user_email, user_join, user_ip, user_verify, user_bot, user_tumb) VALUES  ('" . $a["bot_name"] . "', '88', '" . randomPass() . "', '', '" . time() . "', '0.0.0.0', '1', '" . $a["bot_type"] . "', 'default_bot.png') ");
        $last_id = $mysqli->insert_id;
        $mysqli->query("UPDATE boom_addons SET bot_name = '" . $a["bot_name"] . "', bot_id = '" . $last_id . "' WHERE addons = '" . $a["name"] . "'");
    }
    boomConsole("addons_install", ["custom" => $this_addons]);
    return boomCode(1);
}

function boomRemoveAddons($this_addons)
{
    global $mysqli;
    global $data;
    global $cody;
    global $lang;
    $add = addonsDetails($this_addons);
    require BOOM_PATH . "/addons/" . $this_addons . "/system/uninstall.php";
    if (0 < $add["bot_id"]) {
        $user = userDetails($add["bot_id"]);
        clearUserData($user);
    }
    $mysqli->query("DELETE FROM boom_addons WHERE addons = '" . $this_addons . "'");
    boomConsole("addons_uninstall", ["custom" => $this_addons]);
    return 1;
}

?>