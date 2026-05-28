<?php
require __DIR__ . "/../config.php";
if (isset($_POST["password"], $_POST["username"])) {
    $password = encrypt(escape($_POST["password"]));
    $username = escape($_POST["username"]);
    echo chatLogin($username, $password);
    exit;
}
if (isset($_POST["gusername"], $_POST["ggender"], $_POST["gage"])) {
    echo guestNameLogin();
    exit;
}

function guestNameLogin() {
    global $mysqli;

    if (!allowGuest()) return 0;
    if (!boomCheckRecaptcha()) return 6;
    if (!okGuest(getIp())) return 16;

    $name   = escape($_POST["gusername"]);
    $gender = escape($_POST["ggender"]);
    $age    = escape($_POST["gage"]);

    if (!validName($name)) return 4;
    if (!boomUsername($name)) return 5;

    if (guestForm()) {
        if (!validAge($age)) return 13;
        if (!validGender($gender)) return 14;
    }

    $guest = [
        "name"     => $name,
        "password" => randomPass(),
        "language" => getLanguage(),
        "ip"       => getIp(),
        "rank"     => 0,
        "avatar"   => "default_guest.png",
        "email"    => ""
    ];

    if (guestForm()) {
        $guest["age"]    = $age;
        $guest["gender"] = $gender;
    }

    $user = boomInsertUser($guest);
    return empty($user) ? 2 : 1;
}

function chatLogin($username, $password)
{
    global $mysqli;
    $ip = getIp();
    if (empty($username) || empty($password)) return 3;
    if (isEmail($username)) {
        $sql = "
            SELECT * FROM boom_users
             WHERE user_email    = '{$username}'
               AND user_password = '{$password}'
        ";
    } else {
        $sql = "
            SELECT * FROM boom_users
             WHERE user_name     = '{$username}'
               AND user_password = '{$password}'
        ";
    }
    $res = $mysqli->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $sid = $row["session_id"] + 1;
        $id  = $row["user_id"];
        $mysqli->query("
            UPDATE boom_users
               SET user_ip     = '{$ip}',
                   user_roomid = '1',
				   user_move = '". time() ."'
                   session_id  = '{$sid}'
             WHERE user_id    = '{$id}'
        ");
        $user = userDetails($id);
        if (!empty($user)) {
            setBoomCookie($user);
            return 3;
        }
        return 2;
    }
    return 2;
}
