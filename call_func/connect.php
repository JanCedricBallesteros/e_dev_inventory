<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__));
include DOMAIN_PATH . '/config/db_data.php';
$db_connect = mysqli_connect(HOST, DB_USER, DB_PASS, DB_NAME);

if (mysqli_connect_errno()) {
	$error = "Failed to connect to Database: " . mysqli_connect_error();
	error_log($error);
	echo $error;
	exit();
}

function escape($con = "", $str = "")
{
	global $db_connect;
	$string = mysqli_real_escape_string($db_connect, $str);
	return $string;
}

function db_close()
{
	global $db_connect;
	mysqli_close($db_connect);
}

function call_mysql_query($query, $connect = '')
{
	global $db_connect;
	$connect = empty($connect) ? $db_connect : $connect;
	if (empty($query)) {
		return false;
	}
	$r = mysqli_query($connect, $query);
	return $r;
}


function call_mysql_fetch_array($query, $resulttype = MYSQLI_ASSOC, $connect = '')
{
	global $db_connect;
	return mysqli_fetch_array($query, $resulttype);
}

function call_mysql_num_rows($query)
{
	$result = 0;
	if ($query) {
		$result = mysqli_num_rows($query);
	}
	return $result;
}

function call_mysql_affected_rows($connect = '')
{
	global $db_connect;
	$connect = empty($connect) ? $db_connect : $connect;
	return mysqli_affected_rows($connect);
}


function mysqli_query_return($sql_query, $connect = "")
{
	global $db_connect;
	$connect = empty($connect) ? $db_connect : $connect;
	$rdata = array();
	if (empty($sql_query)) {
		return $rdata;
	}

	if ($query = mysqli_query($connect, $sql_query)) {
		if ($num = mysqli_num_rows($query)) {
			while ($data = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
				array_push($rdata, $data);
			}
		}
	}

	return $rdata;
}

function mysqliquery_return($sql_query, $connect = "", $type = MYSQLI_ASSOC)
{
	global $db_connect;

	$connect = (empty($connect)) ? $db_connect : $connect;
	$rdata = array();

	if ($query = mysqli_query($connect, $sql_query)) {
		$num = mysqli_num_rows($query);
		if ($num > 0) {
			while ($data = mysqli_fetch_array($query, $type)) {
				$rdata[] = $data;
			}
		}
	}
	return $rdata;
}

function activity_log_new($action)
{
	global $db_connect, $session_class;
	$date_now = date('Y-m-d H:i:s');
	$user_id = $session_class->getValue('user_id');
	$role_txt = $session_class->getValue('role_id');
	$fingerprint = $session_class->getValue('browser_fingerprint');
	$role_id = 0;

	## user access
	$user_access_role = SYSTEM_ACCESS[GLOBAL_SYSTEM_ACCESS]['role'];
	$role_id = array_search($role_txt, $user_access_role);

	if (!empty($user_id) and trim($action) != "") { // may user
		$insert = "INSERT INTO activity_log (user_id, action, date_log, session_id, user_level) VALUES ( '" . $user_id . "', '" . escape($db_connect, $action) . "','" . $date_now . "','" . $fingerprint . "', " . $role_id . ") ";

		if (mysqli_query($db_connect, $insert)) {
			return true;
		}
	}
	return false;
}


function data_log_new($action, $user_id, $system_access = "", $jwt_data = "")
{
	global $db_connect;
	$date_now = date('Y-m-d H:i:s');

	if (!empty($user_id) && trim($action) != '' && empty($system_access) && empty($jwt_data)) { // may user
		if ($action == 'INSERT_DATA') {
			$insert = "INSERT INTO log (user_id,system_access,data_log,action_flag,date_log) VALUES ('" . escape($db_connect, $user_id) . "','" . escape($db_connect, $system_access) . "','" . escape($db_connect, $jwt_data) . "','0','" . $date_now . "') ";
			if ($query = mysqli_query($db_connect, $insert)) {
				return true;
			}
		} elseif ($action == 'UPDATE_DATA') {
			$update = "UPDATE log set action_flag = '1' WHERE log_id = '" . escape($db_connect, $user_id) . "' ";
			if ($query = mysqli_query($db_connect, $update)) {
				return true;
			}
		}
	}
	return false;
}

function  user_log($data = array())
{
	global $db_connect;

	$input = array_merge(array("ID_USER" => "", "IP" => "", "TOKEN" => "", "ACTION" => "", "AGENTS" => "", "SUMMARY" => "", "USER_ROLE" => ""), $data);
	$token_id = $input['TOKEN'];
	if (empty($input['ID_USER'])) {
		return false;
	}

	$user_login_id = '';
	$duplicate_token = false;
	$select = "SELECT user_log_id,login_date,token_id  FROM user_log WHERE DATE_FORMAT(login_date,'%Y-%m-%d') = '" . DATE_NOW . "' AND user_id = '" . escape($db_connect, $input['ID_USER']) . "'";
	if ($result = mysqli_query($db_connect, $select)) {
		if ($data = mysqli_fetch_assoc($result)) {
			$user_login_id =  $data['user_log_id'];
			#checking duplicate entry token
			$token_array = json_decode($data['token_id']);
			if (in_array($token_id, $token_array)) {
				$duplicate_token = true;
			}
		}
	}

	unset($data);

	$sql_query = '';
	if (empty($user_login_id)) { #not existing
		$input['TOKEN'] =  "JSON_ARRAY('" . $input['TOKEN'] . "')";
		$input['SUMMARY'] = " JSON_ARRAY(JSON_ARRAY('" . DATE_TIME . "','" . $input['ACTION'] . "','" . $input['IP'] . "'))";
		if ($input['ACTION'] == 'LOGIN') { ## fill login time
			$sql_query = "INSERT INTO user_log (login_date,logout_date,action,user_id,session_id,ip_address,device,token_id,login_flag,user_level) VALUES ( '" . DATE_TIME . "','NULL','" . escape($db_connect, $input['ACTION']) . "', '" . escape($db_connect, $input['ID_USER']) . "'," . $input['SUMMARY'] . ",'" . escape($db_connect, $input['IP']) . "','" . escape($db_connect, $input['AGENTS']) . "'," . $input['TOKEN'] . ",'1','" . $input['USER_ROLE'] . "')";
		} else if ($input['ACTION'] == 'LOGOUT') { ## fill logout time
			$sql_query = "INSERT INTO user_log (login_date,logout_date,action,user_id,session_id,ip_address,device,token_id,login_flag,user_level) VALUES ( 'NULL', '" . DATE_TIME . "','" . escape($db_connect, $input['ACTION']) . "', '" . escape($db_connect, $input['ID_USER']) . "'," . $input['SUMMARY'] . ",'" . escape($db_connect, $input['IP']) . "','" . escape($db_connect, $input['AGENTS']) . "','" . escape($db_connect, $input['TOKEN']) . "','1','" . $input['USER_ROLE'] . "')";
		}
	} else { #existing records
		$input['SUMMARY'] = " JSON_ARRAY_APPEND(session_id,'$',JSON_ARRAY('" . DATE_TIME . "','" . $input['ACTION'] . "','" . $input['IP'] . "'))";
		if ($input['ACTION'] == 'LOGIN') { ## fill logout time
			if ($duplicate_token) {
				return 'duplicate';
			}
			$sql_query = "UPDATE user_log SET session_id = " . $input['SUMMARY'] . ", token_id = JSON_ARRAY_APPEND(token_id,'$','" . escape($db_connect, $token_id) . "'), login_flag = '1', user_level = '" . $input['USER_ROLE'] . "' WHERE user_log_id ='" . $user_login_id . "' ";
		} else if ($input['ACTION'] == 'LOGOUT') { ## fill logout time
			$sql_query = "UPDATE user_log SET logout_date = '" . DATE_TIME . "', session_id = " . $input['SUMMARY'] . ", login_flag = '0', user_level = '" . $input['USER_ROLE'] . "' WHERE user_log_id ='" . $user_login_id . "' ";
		}
	}

	if (mysqli_query($db_connect, $sql_query)) { #error log
		return true;
	}

	return false;
}

function get_profile_pic($user_id, $field, $table)
{
	global $db_connect;
	$path = "";
	if (trim($user_id) == "" || trim($field) == "" || trim($table) == "") {
		return "";
	}
	$query = "SELECT location FROM " . $table . " WHERE " . $field . " = '" . $user_id . "' LIMIT 1";
	if ($query = mysqli_query($db_connect, $query)) {
		$num = mysqli_num_rows($query);
		if ($num != 0) {
			if ($data = mysqli_fetch_array($query, MYSQLI_ASSOC)) {
				$path = empty($data['location']) ? "" : BASE_URL . $data['location'];
			}
		}
	}

	return $path;
}

function isduplicate_where($table_name, $select_column, $sql_where)
{
	global $db_connect;

	$default_query = "SELECT " . $select_column . " FROM " . $table_name . " WHERE " . $sql_where . " ";
	if ($query = mysqli_query($db_connect, $default_query)) {
		if ($num = mysqli_num_rows($query)) {
			return true;
		} else {
			return false;
		}
	}

	return false;
}

function get_school_year($year = 0)
{
	global $db_connect, $session_class;
	$school_year = array("school_year_id" => 0, "school_year" => "", "sem" => "", "date_from" => "", "date_to" => "", "flag_used" => "");

	if (is_numeric($year) and $year > 0) {
		$query = "SELECT *,CONCAT('F.Y. : ',school_year,' - ',sem) as fy_sem FROM school_year WHERE school_year_id ='" . escape($db_connect, $year) . "' ORDER BY school_year_id DESC LIMIT 1";
		if ($result = mysqli_query($db_connect, $query)) {
			$school_year = mysqli_fetch_array($result, MYSQLI_ASSOC);
			return $school_year;
		}
	}

	$session_year = $session_class->getValue('global_fy');
	if (!(isset($session_year)) or empty($session_year)) {
		$query = "SELECT *,CONCAT('F.Y. : ',school_year,' - ',sem) as fy_sem FROM school_year WHERE flag_used ='1' ORDER BY school_year_id DESC LIMIT 1";
		if ($result = mysqli_query($db_connect, $query)) {
			if ($num = mysqli_num_rows($result)) {
				$school_year = mysqli_fetch_array($result, MYSQLI_ASSOC);
			}
		}
	} else {
		$school_year = $session_year;
	}

	return $school_year;
}

function get_proper_name($f_name, $m_name, $l_name, $suffix)
{
	$encoding = 'UTF-8';
	$output = "";

	$middle_initial = !empty($m_name) ? mb_strtoupper(mb_substr($m_name, 0, 1, $encoding), $encoding) . "." : "";
	$output = !empty($l_name) ? mb_strtoupper($l_name, $encoding) : "";
	$output .= !empty($f_name) ? ", " . mb_strtoupper($f_name, $encoding) : "";
	if (!empty($suffix)) $output .= " " . mb_strtoupper($suffix, $encoding);
	if (!empty($middle_initial)) $output .= " " . $middle_initial;

	return $output;
}


function get_format_name($f_name, $m_name, $l_name, $suffix)
{
	$encoding = 'UTF-8';
	$output = "";

	$middle_initial = !empty($m_name) ? mb_strtoupper(mb_substr($m_name, 0, 1, $encoding), $encoding) . "." : "";
	$output = !empty($f_name) ? mb_strtoupper($f_name, $encoding) : "";
	if (!empty($middle_initial)) $output .= " " . $middle_initial;
	$output .= !empty($l_name) ? " " . mb_strtoupper($l_name, $encoding) : "";
	if (!empty($suffix)) $output .= " " . mb_strtoupper($suffix, $encoding);

	return $output;
}

function get_full_name($f_name, $m_name, $l_name, $suffix)
{
	$encoding = 'UTF-8';
	$output = "";

	$formatted_fname = !empty($f_name) ? mb_strtoupper($f_name, $encoding) : ""; // Niko
	$formatted_mname = !empty($m_name) ? mb_strtoupper($m_name, $encoding) : ""; // Idao
	$formatted_lname = !empty($l_name) ? mb_strtoupper($l_name, $encoding) : ""; // CAÑAS
	$formatted_suffix = !empty($suffix) ? mb_strtoupper($suffix, $encoding) : ""; // JR., III, etc.

	$output = $formatted_lname . ", " . $formatted_fname;
	if (!empty($formatted_suffix)) $output .= " " . $formatted_suffix;
	if (!empty($formatted_mname)) $output .= " " . $formatted_mname;

	return $output;
}
