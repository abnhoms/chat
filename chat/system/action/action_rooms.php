<?php
require "../config_session.php";
header("Content-Type: application/json; charset=utf-8");

if (isset(
    $_POST['admin_set_room_id'],
    $_POST['admin_set_room_name'],
    $_POST['admin_set_room_description'],
    $_POST['admin_set_room_password'],
    $_POST['admin_set_room_access']
)) {
    if (!canEditRoom()) {
        echo 0;
        die;
    }

    $room_id = escape($_POST['admin_set_room_id']);
    $name = escape($_POST['admin_set_room_name']);
    $description = escape($_POST['admin_set_room_description']);
    $password = escape($_POST['admin_set_room_password']);
    $access = (int) escape($_POST['admin_set_room_access']);
    $player_id = 0;

    if (strlen($name) > 100) {
        echo 4;
        die;
    }
    if (strlen($description) > 350) {
        echo 0;
        die;
    }

    $get = $mysqli->query("SELECT * FROM boom_rooms WHERE room_id = '{$room_id}'");
    if ($get->num_rows === 0) {
        echo 0;
        die;
    }

    $room = $get->fetch_assoc();
    if (roomExist($name, $room_id)) {
        echo 2;
        die;
    }

    if ($room_id == 1) {
        $password = '';
    }

    if (isset($_POST['admin_set_room_player'])) {
        $player = (int) escape($_POST['admin_set_room_player']);
        if ($player !== 0 && $player !== $room['room_player_id']) {
            $cp = $mysqli->query("SELECT id FROM boom_radio_stream WHERE id = '{$player}'");
            $player_id = $cp->num_rows > 0 ? $cp->fetch_assoc()['id'] : $room['room_player_id'];
        } else {
            $player_id = $room['room_player_id'];
        }
    }

    $mysqli->query("
        UPDATE boom_rooms
        SET room_name         = '{$name}',
            description       = '{$description}',
            password          = '{$password}',
            access            = '{$access}',
            room_player_id    = '{$player_id}'
        WHERE room_id = '{$room_id}'
    ");

    echo 1;
    die;
}

if (isset($_POST['room'], $_POST['join_room'])) {
    $target = (int) escape($_POST['room']);
    $muted = 0;
    $role = 0;
    $data['user_role'] = 0;

    $sql = "
        SELECT
            r.room_id,
            r.room_name,
            r.room_icon,
            r.topic          AS room_topic,
            r.room_action,
            r.access,
            r.password,
            (SELECT COUNT(id) FROM boom_room_action WHERE action_room=? AND action_user=? AND action_muted=1) AS is_muted,
            (SELECT COUNT(id) FROM boom_room_action WHERE action_room=? AND action_user=? AND action_blocked=1) AS is_blocked,
            (SELECT room_rank FROM boom_room_staff WHERE room_id=? AND room_staff=?) AS room_status
        FROM boom_rooms r
        WHERE r.room_id=?
    ";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        echo json_encode(['code' => 1, 'data' => null]);
        die;
    }

    $stmt->bind_param("iiiiiii", $target, $userId, $target, $userId, $target, $userId, $target);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode(['code' => 1, 'data' => null]);
        die;
    }

    $room = $res->fetch_assoc();
    if ((int) $room['is_muted'] > 0) {
        $muted = 1;
    }
    if ((int) $room['is_blocked'] === 1 || mustVerify()) {
        echo json_encode(['code' => 99, 'data' => null]);
        die;
    }

    if (!is_null($room['room_status'])) {
        $role = (int) $room['room_status'];
        $data['user_role'] = $role;
    }

    if (!boomAllow($room['access'])) {
        echo json_encode(['code' => 2, 'data' => null]);
        die;
    }

    if ($room['password'] !== '') {
        if (!isset($_POST['pass'])) {
            echo json_encode(['code' => 4, 'data' => null]);
            die;
        }
        $pass = escape($_POST['pass']);
        if ($pass !== $room['password'] && !canRoomPassword()) {
            echo json_encode(['code' => 5, 'data' => null]);
            die;
        }
    }

    $now = time();
    $mysqli->query("
        UPDATE boom_users
        SET user_move     = {$now},
            user_roomid   = {$room['room_id']},
            last_action   = {$now},
            user_role     = {$role},
            room_mute     = {$muted}
        WHERE user_id     = {$data['user_id']}
    ");

    $mysqli->query("UPDATE boom_rooms SET room_action = {$now} WHERE room_id = {$room['room_id']}");

    $logs = getChatHistory($room['room_id']);
    $room_icon = $room['room_icon'] === 'default_room.png' 
        ? 'default_images/rooms/default_room.png' 
        : '/room_icon/' . $room['room_icon'];

    $payload = [
        'room_id'       => $room['room_id'],
        'room_name'     => $room['room_name'],
        'room_icon'     => $room_icon,
        'room_action'   => $room['room_action'],
        'room_role'     => $role,
        'room_logs'     => $logs
    ];

    if (empty(trim($room['room_topic']))) {
        $payload['room_topic'] = '';
    } else {
        $payload['room_topic'] = [
            'content' => $room['room_topic'],
            'title'   => $lang['topic'],
            'icon'    => 'default_images/special/topic.svg'
        ];
    }

    redisUpdateRoom($data['user_id']);
    echo json_encode(['code' => 10, 'data' => $payload]);
    die;
}
if (isset($_POST['room'], $_POST['join_room_pass'], $_POST['pass'])) {
    $roomId = (int) escape($_POST['room']);
    $pass   = escape($_POST['pass']);
    $userId = $data['user_id'];
    $muted  = 0;
    $role   = 0;
    $data['user_role'] = 0;

    $sql = "
      SELECT
        r.room_id,
        r.room_name,
        r.room_icon,
        r.topic      AS room_topic,
        r.room_action,
        r.access,
        r.password,
        (SELECT COUNT(id) FROM boom_room_action WHERE action_room=? AND action_user=? AND action_muted=1)   AS is_muted,
        (SELECT COUNT(id) FROM boom_room_action WHERE action_room=? AND action_user=? AND action_blocked=1) AS is_blocked,
        (SELECT room_rank  FROM boom_room_staff  WHERE room_id=?     AND room_staff=?)       AS room_status
      FROM boom_rooms r
      WHERE r.room_id=?
    ";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        echo json_encode(['code'=>1,'data'=>null]); exit;
    }
    $stmt->bind_param('iiiiiii',
        $roomId, $userId,
        $roomId, $userId,
        $roomId, $userId,
        $roomId
    );
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['code'=>1,'data'=>null]); exit;
    }
    $room = $res->fetch_assoc();

    if ((int)$room['is_muted'] > 0)   $muted = 1;
    if ((int)$room['is_blocked']===1 || mustVerify()) {
        echo json_encode(['code'=>99,'data'=>null]); exit;
    }

    if (!is_null($room['room_status'])) {
        $role = (int)$room['room_status'];
        $data['user_role'] = $role;
    }

    if (!boomAllow($room['access'])) {
        echo json_encode(['code'=>2,'data'=>null]); exit;
    }

    if ($room['password'] === '' || ($pass === $room['password'] && canRoomPassword())) {
    } else {
        echo json_encode(['code'=>5,'data'=>null]); exit;
    }

    $now = time();
    $mysqli->query("
      UPDATE boom_users SET
        user_move   = {$now},
        user_roomid = {$room['room_id']},
        last_action = {$now},
        user_role   = {$role},
        room_mute   = {$muted}
      WHERE user_id = {$userId}
    ");
    $mysqli->query("UPDATE boom_rooms SET room_action = {$now} WHERE room_id = {$room['room_id']}");

    $logs     = getChatHistory($room['room_id']);
    $room_icon= $room['room_icon']==='default_room.png'
               ? 'default_images/rooms/default_room.png'
               : '/room_icon/'.$room['room_icon'];

    $payload = [
      'room_id'     => $room['room_id'],
      'room_name'   => $room['room_name'],
      'room_icon'   => $room_icon,
      'room_action' => $room['room_action'],
      'room_role'   => $role,
      'room_logs'   => $logs
    ];
    if (trim($room['room_topic'])!=='') {
      $payload['room_topic'] = [
        'content'=> $room['room_topic'],
        'title'  => $lang['topic'],
        'icon'   => 'default_images/special/topic.svg'
      ];
    } else {
      $payload['room_topic'] = '';
    }

    redisUpdateRoom($data['user_roomid']);
    echo json_encode(['code'=>10,'data'=>$payload]);
    exit;
}
if (isset($_POST['leave_room'])) {
    $mysqli->query("UPDATE boom_users SET user_roomid=0 WHERE user_id={$userId}");
    echo 1;
    die;
}

if (isset($_POST['target'], $_POST['room_staff_rank'])) {
    if (!canEditRoom()) {
        echo json_encode(['code' => 0, 'data' => null]);
        die;
    }

    $target = escape($_POST['target']);
    $rank = escape($_POST['room_staff_rank']);
    $user = userRoomDetails($target);

    if (empty($target) || !canRoomAction($user, 6)) {
        echo json_encode(['code' => 0, 'data' => null]);
        die;
    }

    if ($rank > 0) {
        if (checkMod($user['user_id'])) {
            $mysqli->query("INSERT INTO boom_room_staff(room_id, room_staff, room_rank) VALUES('{$data['user_roomid']}','{$user['user_id']}','{$rank}')");
        } else {
            $mysqli->query("UPDATE boom_room_staff SET room_rank='{$rank}' WHERE room_id='{$data['user_roomid']}' AND room_staff='{$user['user_id']}'");
        }
        $mysqli->query("DELETE FROM boom_room_action WHERE action_user='{$user['user_id']}' AND action_room='{$data['user_roomid']}'");
        $mysqli->query("UPDATE boom_users SET user_role='{$rank}', room_mute=0 WHERE user_id='{$user['user_id']}' AND user_roomid='{$data['user_roomid']}'");
    } else {
        $mysqli->query("DELETE FROM boom_room_staff WHERE room_staff='{$user['user_id']}' AND room_id='{$data['user_roomid']}'");
        $mysqli->query("UPDATE boom_users SET user_role=0 WHERE user_id='{$user['user_id']}' AND user_roomid='{$data['user_roomid']}'");
    }

    boomConsole('change_room_rank', ['target' => $user['user_id'], 'rank' => $rank]);
    echo json_encode(['code' => 1, 'data' => null]);
    die;
}

if (
    isset(
        $_POST['admin_add_room'],
        $_POST['admin_set_name'],
        $_POST['admin_set_pass'],
        $_POST['admin_set_type'],
        $_POST['admin_set_description']
    ) && 
    boomAllow(100) && 
    canRoom()
) {
    $n = escape($_POST['admin_set_name']);
    $p = escape($_POST['admin_set_pass']);
    $t = escape($_POST['admin_set_type']);
    $d = escape($_POST['admin_set_description']);

    if (!validRoomName($n) || strlen($d) > 25 || mb_strlen($p) > 20) {
        echo json_encode(['code' => 0]);
        die;
    }

    $max = $mysqli->query("SELECT 1 FROM boom_rooms WHERE room_creator='{$userId}'");
    if ($max->num_rows >= $cody['max_room'] && !boomAllow(8)) {
        echo json_encode(['code' => 5]);
        die;
    }

    if ($mysqli->query("SELECT 1 FROM boom_rooms WHERE room_name='{$n}'")->num_rows) {
        echo json_encode(['code' => 6]);
        die;
    }

    $sf = boomAllow(100) ? 1 : 0;
    $now = time();
    $mysqli->query("
        INSERT INTO boom_rooms(
            room_name,
            access,
            description,
            password,
            room_system,
            room_creator,
            room_action
        ) VALUES(
            '{$n}',
            '{$t}',
            '{$d}',
            '{$p}',
            '{$sf}',
            '{$data['user_id']}',
            '{$now}'
        )
    ");

    $id = $mysqli->insert_id;
    $mysqli->query("DELETE FROM boom_room_staff WHERE room_id='{$id}'");

    if (!boomAllow(10)) {
        $mysqli->query("
            UPDATE boom_users 
            SET user_roomid='{$id}',
                last_action='{$now}',
                user_role=6 
            WHERE user_id='{$userId}'
        ");
        $mysqli->query("INSERT INTO boom_room_staff(room_id, room_staff, room_rank) VALUES('{$id}','{$userId}',6)");
    } else {
        $mysqli->query("UPDATE boom_users SET user_roomid='{$id}', last_action='{$now}' WHERE user_id='{$userId}'");
    }

    boomConsole('create_room', ['room' => $id]);
    echo json_encode([
        'code' => 10,
        'data' => boomTemplate('element/admin_room', roomDetails($id))
    ]);
    die;
}

if (isset($_POST['pin_room'])) {
    $rid = escape($_POST['pin_room']);
    $mysqli->query("UPDATE boom_rooms SET pinned=IF(pinned=1,0,1) WHERE room_id='{$rid}'");
    echo 1;
    die;
}

if (isset($_POST['delete_room'])) {
    $rid = escape($_POST['delete_room']);
    $mysqli->query("DELETE FROM boom_rooms WHERE room_id='{$rid}'");
    echo 1;
    die;
}

echo json_encode(['code' => 1, 'data' => null]);
die;
?>