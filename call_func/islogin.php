<?php
define('LOGIN_AUTH', true);
/**
 * Create, or retrieve, the session variable.
 */

function session_class($key, $default = null)
{
	global $session_class;
	$value = $session_class->getValue($key);

	if ($value === null || $value === '') {
		return $default;
	}

	return !empty($value) ? trim($value) : $default;
}

## add/change based on need
$s_user_id = session_class('user_id');
$g_user_role = session_class('user_role');
$g_general_id = session_class('general_id');
$g_fullname = session_class('fullname');
$g_name = session_class('shortname');
$g_user_name = session_class('name');
$g_sex = session_class('sex');
$g_position = session_class('position');
$g_photo = session_class('photo','profile_img.png');
$g_token_id = session_class('token_id');
$g_agent_browser = session_class('agent_browser');
$g_browser_fingerprint = session_class('browser_fingerprint');

$targerLink = API_URL;

if (!isset($s_user_id)) { // hindi naka login
	header("Location: " . $targerLink);
	exit();
}

$session_flag = 0;
$default_query = "SELECT token_id,login_flag FROM user_log WHERE login_flag = '1' AND  token_id REGEXP '\"" . escape($db_connect, $g_token_id) . "\"' LIMIT 1";
if ($query = call_mysql_query($default_query)) {
	if ($num = call_mysql_num_rows($query)) {
		$session_flag = 1;
	}
}

if ($session_flag === 0) { // auto logout
	$session_class->end();
	header("Location: " . $targerLink);
	exit();
}

$url = page_url();
if (empty($session_class->getValue('page_path'))) {
	$session_class->setValue('page_path', $url);
}

if (!isset($g_user_role) or empty($g_user_role)) {
	$session_class->end();
	include HTTP_401;
	exit();
}

$global_profile_pic = $session_class->getValue('photo');
if (!isset($global_profile_pic) or empty($global_profile_pic)) {
	$table_pic = "";
	$tb_id = "";
	if ($g_user_role[0] == "ADMIN") {
		$table_pic = "users";
		$tb_id = "user_id";
	}
	$get_path = get_profile_pic($s_user_id, $tb_id, $table_pic);
	//echo $get_path."SS";
	if (empty(trim($get_path))) {
		$get_path = BASE_URL . "images/placeholder.png";
	}
	$session_class->setValue('photo', $get_path);
	$global_profile_pic = $get_path;
}

$global_fy = array();
$global_my_class = array();
