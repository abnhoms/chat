<?php
$load_addons = 'giphy';
require(__DIR__ . '/../../../system/config_addons.php');

function saveGiphy(){
	global $mysqli, $data;
	$giphy_access = escape($_POST['set_giphy_access'], true);
	$giphy_key = escape($_POST['set_giphy_key']);
	$giphy_gifs = escape($_POST['set_giphy_gifs'], true);
	$giphy_stickers = escape($_POST['set_giphy_stickers'], true);
	if(!is_numeric($giphy_gifs) || !is_numeric($giphy_gifs) || !is_numeric($giphy_gifs)){
		return false;
	}
	$mysqli->query("UPDATE boom_addons SET addons_access = '$giphy_access', custom1 = '$giphy_key', custom2 = '$giphy_gifs', custom3 = '$giphy_stickers' WHERE addons = 'giphy'");
	redisUpdateAddons('giphy');
	return 5;
}

if(isset($_POST['set_giphy_access'], $_POST['set_giphy_key'], $_POST['set_giphy_gifs'], $_POST['set_giphy_stickers']) && canManageAddons()){
	echo saveGiphy();
	die();
}
else {
	die();
}
?>