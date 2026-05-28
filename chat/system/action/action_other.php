<?php
require __DIR__ . "/../config.php";

function changeUserRank() {
	global $mysqli, $data, $setting;

	if (!isset($_POST['change_rank'], $_POST['target'])) return false;
	if (!boomAllow($setting['can_rank'])) return false;

	$change_rank = (int)$_POST['change_rank'];
	$target = (int)$_POST['target'];
	$user = userDetails($target);

	if (!$user) return false;
	if ($data['user_rank'] <= $change_rank) return false;
	if ($data['user_rank'] <= $user['user_rank']) return false;

	$mysqli->query("UPDATE boom_users SET user_rank = '$change_rank' WHERE user_id = '{$user['user_id']}' LIMIT 1");
	redisUpdateUser($target);
	return true;
}

if (isset($_POST['change_rank'], $_POST['target'])) {
	$result = changeUserRank();
	if ($result) {
		if (function_exists('redisFlushAll')) redisFlushAll();
		if (function_exists('boomCacheUpdate')) boomCacheUpdate();
		if (function_exists('opcache_reset')) opcache_reset();
		echo 1;
	} else {
		echo 0;
	}
}

function chatDeletePost(): bool {
	global $mysqli, $data;
	$postId = escape($_POST['del_post'], true);
	$log = logDetails($postId);
	if (empty($log)) {
		return false;
	}
	if (!canDeleteSelfLog($log)) {
		return false;
	}
	return executeDeleteLog($log, $postId);
}

function chatDeleteOtherPost(): bool {
	global $mysqli, $data;
	$postId = escape($_POST['del_post'], true);
	$log = logDetails($postId);
	if (empty($log)) {
		return false;
	}
	if ($log['user_id'] == $data['user_id']  || !canDeleteContent()) {
		return false;
	}
	return executeDeleteLog($log, $postId);
}

function executeDeleteLog($log, $postId): bool {
	global $mysqli, $data;
	$room = roomDetails($data["user_roomid"]);
	if (empty($room)) {
		return false;
	}
	$mysqli->query("
		DELETE FROM boom_chat
		WHERE post_id = '{$postId}'
		  AND post_roomid = '{$data['user_roomid']}'
	");
	$deleted1 = $mysqli->affected_rows;

	$mysqli->query("
		DELETE FROM boom_report
		WHERE report_post = '{$postId}'
		  AND report_type = '1'
		  AND report_room = '{$data['user_roomid']}'
	");
	$deleted2 = $mysqli->affected_rows;

	if ($deleted1 + $deleted2 > 0) {
		updateStaffNotify();
		$now = time();
		if (!delExpired($room["rltime"])) {
			$mysqli->query("
				UPDATE boom_rooms
				SET rldelete = CONCAT(rldelete, ',{$postId}'), rltime = '{$now}'
				WHERE room_id = '{$data['user_roomid']}'
			");
		} else {
			$mysqli->query("
				UPDATE boom_rooms
				SET rldelete = '{$postId}', rltime = '{$now}'
				WHERE room_id = '{$data['user_roomid']}'
			");
		}
		boomConsole("delete_log", [
			"target" => $log["user_id"],
			"room" => $data["user_roomid"],
			"reason" => strip_tags($log["post_message"])
		]);
		removeRelatedFile($postId, "chat");
	if (function_exists('redisFlushAll')) redisFlushAll();
	if (function_exists('opcache_reset')) opcache_reset();
		return true;
	}

	return false;
}

if (isset($_POST['del_post'])) {
	$postId = escape($_POST['del_post'], true);
	$log = logDetails($postId);
	if (empty($log)) {
		echo json_encode(['success' => false]);
		exit;
	}

	$success = false;
	if ($log['user_id'] == $data['user_id']) {
		$success = chatDeletePost();
	} else {
		$success = chatDeleteOtherPost();
	}
	if (function_exists('redisFlushAll')) redisFlushAll();
	if (function_exists('opcache_reset')) opcache_reset();
	echo json_encode(['success' => $success]);
	exit;
}
if(isset($_POST['edit_username'], $_POST['new_name'])){
	$new_name = escape($_POST['new_name']);
	if(!canName()){
		die();
	}
	if($new_name == $data['user_name']){
		echo 1;
		die();
	}
	if(!validName($new_name)){
		echo 2;
		die();
	}
	if(!boomSame($new_name, $data['user_name'])){
		if(!boomUsername($new_name)){
			echo 3;
			die();
		}
	}
	$mysqli->query("UPDATE boom_users SET user_name = '$new_name' WHERE user_id = '{$data['user_id']}'");
	boomConsole('change_name', array('custom'=>$data['user_name']));
	changeNameLog($data, $new_name);
	redisUpdateUser($target);
	if (function_exists('redisFlushAll')) redisFlushAll();
	if (function_exists('opcache_reset')) opcache_reset();
	echo 1;
	die();
}
