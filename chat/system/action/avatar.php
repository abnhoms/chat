<?php
require __DIR__ . "/../config_session.php";
if (isset($_FILES["file"]) && isset($_POST["self"])) {
    echo processavatar();
    exit;
}
if (isset($_FILES["file"]) && isset($_POST["target"])) {
    echo processstaffavatar();
    exit;
}
if (isset($_POST["delete_avatar"])) {
    $avatar_link = myAvatar(resetAvatar($data));
    echo boomCode(0, ["data" => $avatar_link]);
    exit;
}
if (isset($_POST["remove_avatar"])) {
    echo staffremoveavatar();
}
echo " ";

function staffRemoveAvatar()
{
    global $mysqli;
    global $data;
    global $cody;
    $target = escape($_POST["remove_avatar"]);

            $user = userDetails($target);
            if (empty($user)) {
                return boomCode(0);
            }
            if (!canModifyAvatar($user)) {
                return boomCode(0);
            }
            $reset = myAvatar(resetAvatar($user));
            return boomCode(1, ["data" => $reset]);

}

function processAvatar()
{
    global $mysqli;
    global $data;
    global $cody;
    ini_set("memory_limit", "128M");
    $info = pathinfo($_FILES["file"]["name"]);
    $extension = $info["extension"];
    if (!canAvatar()) {
        return boomCode(1);
    }
    if (fileError(2)) {
        return boomCode(1);
    }

    $file_tumb = "avatar_user" . $data["user_id"] . "_" . time() . ".jpg";
    $file_avatar = "temporary_avatar_user_" . $data["user_id"] . "." . $extension;
    unlinkAvatar($file_avatar);
    if (isImage($extension)) {
        $info = getimagesize($_FILES["file"]["tmp_name"]);
        if ($info !== false) {
            list($width, $height) = $info;
            $type = $info["mime"];
            boomMoveFile("avatar/" . $file_avatar);
            $filepath = "avatar/" . $file_tumb;
            $filesource = "avatar/" . $file_avatar;
            $create = createTumbnail($filesource, $filepath, $type, $width, $height, 200, 200);
            if (sourceExist($filepath) && sourceExist($filesource)) {
                if (validImageData($filepath)) {
                    unlinkAvatar($data["user_tumb"]);
                    unlinkAvatar($file_avatar);
                    $mysqli->query("UPDATE boom_users SET user_tumb = '" . $file_tumb . "' WHERE user_id = '" . $data["user_id"] . "'");
                    return boomCode(5, ["data" => myAvatar($file_tumb)]);
                }
                unlinkAvatar($file_avatar);
                return boomCode(7);
            }
            unlinkAvatar($file_avatar);
            return boomCode(7);
        }
        return boomCode(7);
    }
    return boomCode(1);
}

function processStaffAvatar()
{
    global $mysqli;
    global $data;
    global $cody;
    $target = escape($_POST["target"]);
    $user = userDetails($target);
    if (empty($user)) {
        return boomCode(1);
    }
    if (!canModifyAvatar($user)) {
        return boomCode(1);
    }
    ini_set("memory_limit", "128M");
    $info = pathinfo($_FILES["file"]["name"]);
    $extension = $info["extension"];
    if (fileError(2)) {
        return boomCode(1);
    }

$file_tumb = "avatar_user" . $data["user_id"] . "_" . time() . "." . $extension;
    $file_avatar = "temporary_avatar_user_" . $user["user_id"] . "." . $extension;
    unlinkAvatar($file_avatar);
    if (isImage($extension)) {
        $info = getimagesize($_FILES["file"]["tmp_name"]);
        if ($info !== false) {
            list($width, $height) = $info;
            $type = $info["mime"];
            boomMoveFile("avatar/" . $file_avatar);
            $filepath = "avatar/" . $file_tumb;
            $filesource = "avatar/" . $file_avatar;
            $create = createTumbnail($filesource, $filepath, $type, $width, $height, 200, 200);
            if (sourceExist($filepath) && sourceExist($filesource)) {
                if (validImageData($filepath)) {
                    unlinkAvatar($user["user_tumb"]);
                    unlinkAvatar($file_avatar);
                    $mysqli->query("UPDATE boom_users SET user_tumb = '" . $file_tumb . "' WHERE user_id = '" . $user["user_id"] . "'");
                    boomConsole("change_avatar", ["target" => $user["user_id"]]);
                    return boomCode(5, ["data" => myAvatar($file_tumb)]);
                }
                unlinkAvatar($file_avatar);
                return boomCode(7);
            }
            unlinkAvatar($file_avatar);
            return boomCode(7);
        }
        return boomCode(7);
    }
    return boomCode(1);
}

?>