<?php
require __DIR__ . "/../config.php";
require BOOM_PATH . "/system/language/" . $data["user_language"] . "/console.php";

if (!boomAllow(100)) {
    echo 0;
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST["reload_console"])) {
    $last = escape($_POST["reload_console"]);
    echo reloadSystemConsole($last);
    exit;
}

if (isset($_POST["more_console"])) {
    $last = escape($_POST["more_console"]);
    echo loadMoreSystemConsole($last);
    exit;
}

if (isset($_POST["search_console"])) {
    $find = escape($_POST["search_console"]);
    if ($find == "") {
        echo reloadSystemConsole(0);
        exit;
    }
    $id = 0;
    $user = nameDetails($find);
    if (!empty($user)) {
        $id = $user["user_id"];
    }
    echo searchSystemConsole($id, $find);
    exit;
}

if (isset($_POST["clear_console"]) && boomAllow(11)) {
    echo clearSystemConsole();
    exit;
}

function consoleUser($console) {
    return "<span onclick=\"getProfile(" . $console["hunter"] . ");\" class=\"bold console_user\">" . $console["chunter"] . "</span>";
}

function consoleTarget($console) {
    return "<span onclick=\"getProfile(" . $console["target"] . ");\" class=\"bold console_user\">" . $console["ctarget"] . "</span>";
}

function consoleText($t) {
    return "<span class=\"bold console_text\">" . $t . "</span>";
}

function renderConsoleText($console) {
    global $data, $clang, $lang;
    $ctext = $clang[$console["ctype"]];
    $ctext = str_replace(
        ["%hunter%", "%target%", "%oldname%", "%room%", "%data%", "%data2%", "%rank%", "%roomrank%", "%delay%"],
        [
            consoleUser($console),
            consoleTarget($console),
            consoleText($console["custom"]),
            consoleText($console["croom"]),
            consoleText($console["custom"]),
            consoleText($console["custom2"]),
            consoleText(rankTitle($console["crank"])),
            consoleText(roomRankTitle($console["crank"])),
            consoleText(boomRenderMinutes($console["delay"]))
        ],
        $ctext
    );
    return $ctext;
}

function reloadSystemConsole($id) {
    global $mysqli, $lang;

    $get_console = $mysqli->query("SELECT *, 
        (SELECT user_name FROM boom_users WHERE user_id = hunter) AS chunter, 
        (SELECT user_name FROM boom_users WHERE user_id = target) AS ctarget, 
        (SELECT room_name FROM boom_rooms WHERE room_id = room) AS croom 
        FROM boom_console WHERE id > '" . $id . "' ORDER BY cdate DESC LIMIT 500");

    if ($get_console && $get_console->num_rows > 0) {
        $list = "";
        while ($console = $get_console->fetch_assoc()) {
            $list .= boomTemplate("element/console_log", $console);
        }
        return $list;
    }

    // Check if table is fully empty
    $check = $mysqli->query("SELECT COUNT(*) AS total FROM boom_console");
    $count = $check ? $check->fetch_assoc()["total"] : 0;

    if ($count == 0) {
        return emptyZone($lang["no_data"]);
    }

    return "";
}

function loadMoreSystemConsole($id) {
    global $mysqli, $lang;

    $get_console = $mysqli->query("SELECT *, 
        (SELECT user_name FROM boom_users WHERE user_id = hunter) AS chunter, 
        (SELECT user_name FROM boom_users WHERE user_id = target) AS ctarget, 
        (SELECT room_name FROM boom_rooms WHERE room_id = room) AS croom 
        FROM boom_console WHERE id < '" . $id . "' ORDER BY cdate DESC LIMIT 500");

    if ($get_console && $get_console->num_rows > 0) {
        $list = "";
        while ($console = $get_console->fetch_assoc()) {
            $list .= boomTemplate("element/console_log", $console);
        }
        return $list;
    }

    // Only show empty zone if truly no rows exist
    $check = $mysqli->query("SELECT COUNT(*) AS total FROM boom_console");
    $count = $check ? $check->fetch_assoc()["total"] : 0;

    if ($count == 0) {
        return emptyZone($lang["no_data"]);
    }

    return "";
}

function searchSystemConsole($id, $find) {
    global $mysqli, $clang, $lang;

    $find_list = [];
    foreach ($clang as $key => $value) {
        if (stripos($value, $find) !== false) {
            $find_list[] = $key;
        }
    }

    $find_list = listWordArray($find_list);
    $get_console = $mysqli->query("SELECT *, 
        (SELECT user_name FROM boom_users WHERE user_id = hunter) AS chunter, 
        (SELECT user_name FROM boom_users WHERE user_id = target) AS ctarget, 
        (SELECT room_name FROM boom_rooms WHERE room_id = room) AS croom 
        FROM boom_console 
        WHERE hunter = '" . $id . "' 
        OR target = '" . $id . "' 
        OR ctype IN (" . $find_list . ") 
        ORDER BY cdate DESC LIMIT 500");

    if ($get_console && $get_console->num_rows > 0) {
        $list = "";
        while ($console = $get_console->fetch_assoc()) {
            $list .= boomTemplate("element/console_log", $console);
        }
        return $list;
    }

    $check = $mysqli->query("SELECT COUNT(*) AS total FROM boom_console");
    $count = $check ? $check->fetch_assoc()["total"] : 0;

    if ($count == 0) {
        return emptyZone($lang["no_data"]);
    }

    return "";
}

function clearSystemConsole() {
    global $mysqli;
    $mysqli->query("TRUNCATE TABLE boom_console");
    boomConsole("clear_console");
    return 1;
}
?>
