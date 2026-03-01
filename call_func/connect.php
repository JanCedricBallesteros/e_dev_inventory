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

	if ($role_txt == "ADMIN") {
		$role_id = 1;
	} else if ($role_txt == "REGISTRAR") {
		$role_id = 2;
	} else if ($role_txt == "VPAA") {
		$role_id = 3;
	} else if ($role_txt == "OFFICIAL") {
		$role_id = 4;
	} else if ($role_txt == "FACULTY") {
		$role_id = 5;
	} else if ($role_txt == "STUDENT") {
		$role_id = 6;
	}

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



function form_share($form_id, $general_id, $fiscal_year, $form_type, $department)
{ //Use switch case for form types
	global $db_connect;
	global $g_user_role;
	include_once '../config/config.php';
	$form_type_data = $form_type;
	// var_dump($form_type);
	// exit();
	$type = form_type['type'][$form_type];
	$office_data = "";
	$office_array = [];
	$v_s_array = array();
	$o_array = array();
	$d_array = array();
	$dept_array = [];
	$dean_dept_data = "";
	$dean_dept_array = [];
	$array = "";
	//checks form type

	if ($g_user_role == "VPAA") {
		$office_sql = "SELECT * FROM form_settings WHERE flag_default = '1' AND form_type = '" . $form_type_data . "' LIMIT 1";
	} else if ($g_user_role == "REGISTRAR") {
		$office_sql = "SELECT * FROM form_settings WHERE flag_default = '2' AND form_type = '" . $form_type_data . "' LIMIT 1";
	}

	if ($office_query = mysqli_query($db_connect, $office_sql)) {
		if ($office_num_row = mysqli_num_rows($office_query)) {
			if ($office_data = call_mysql_fetch_array($office_query)) {
				$v_s_array = json_decode($office_data["visibility_settings"]);
				// echo $office_array2;
				// exit();
			}
		}
	}

	foreach ($v_s_array as $_office) {

		$dean_dept = "SELECT * FROM office tbl_office LEFT JOIN (SELECT user_id,general_id,CONCAT(UPPER(l_name),', ',f_name,' ',m_name) as name FROM users) AS tbl_users ON tbl_office.assign = tbl_users.user_id  WHERE id = '" . $_office . "' LIMIT 1";
		if ($dean_dept_query = mysqli_query($db_connect, $dean_dept)) {
			if ($dean_dept_num_row = mysqli_num_rows($dean_dept_query)) {
				if ($_data = call_mysql_fetch_array($dean_dept_query)) {
					if ($_data["department"] == 0) {
						$o_array[] = $_data;
					} else {
						if ($_data['department'] == $department) $o_array[] = $_data;
						// $d_array[$_data['department']] = $_data;
					}
				}
			}
		}
	}

	// var_dump($o_array);
	// var_dump($d_array);
	// var_dump($v_s_array);
	// exit();

	$dean_dept = "SELECT * FROM office WHERE department = '" . $department . "' LIMIT 1";
	if ($dean_dept_query = mysqli_query($db_connect, $dean_dept)) {
		if ($dean_dept_num_row = mysqli_num_rows($dean_dept_query)) {
			while ($dean_dept_data = call_mysql_fetch_array($dean_dept_query)) {
				$dean_dept_array[] = $dean_dept_data;
				// echo $office_array2;
				// exit();
			}
		}
	}
	// $filter_array = explode(',', $office_array2);
	// echo json_encode($filter_array);
	// var_dump($type);
	// exit();
	if ($type == "Faculty Clearance") {
		for ($x = 0; $x < count($o_array);) {
			$office = $o_array[$x];
			$insert = "INSERT INTO faculty_form_sign_approval (form_id, clearance_id, office_id, signature_status,general_id, fy) VALUES ('" . escape($db_connect, $form_id) . "','" . escape($db_connect, $form_type_data) . "','" . $office['id'] . "','1','" . escape($db_connect, $general_id) . "','" . escape($db_connect, $fiscal_year) . "') ";
			$x++;
			mysqli_query($db_connect, $insert);
		}
	} else if ($type != "Faculty Clearance") {
		for ($x = 0; $x < count($o_array);) {
			$office = $o_array[$x];
			$insert = "INSERT INTO student_form_sign_approval (form_id, clearance_id, office_id, signature_status, general_id, fy) VALUES ('" . escape($db_connect, $form_id) . "','" . escape($db_connect, $form_type_data) . "','" .  $office['id'] . "','1','" . escape($db_connect, $general_id) . "','" . escape($db_connect, $fiscal_year) . "') ";
			$x++;
			mysqli_query($db_connect, $insert);
		}
	}
	return true;
}

function notification($recepient_id, $content, $type, $sender_id)
{
	global $db_connect;
	$insert = "INSERT INTO notification (recipient_id, sender_id, unread, content, type) VALUES ('" . escape($db_connect, $recepient_id) . "','" . $sender_id . "', 0 , '" . escape($db_connect, $content) . "', '" . escape($db_connect, $type) . "') ";
	if (mysqli_query($db_connect, $insert)) {

		return true;
	}
	return false;
}

function unread_to_read($id)
{
	global $db_connect;
	global $g_general_id;
	$insert = "UPDATE notification SET unread = '1' WHERE recipient_id = '" . escape($db_connect, $g_general_id) . "' AND notif_id = '" . escape($db_connect, $id) . "'";
	if (mysqli_query($db_connect, $insert)) {

		return true;
	}
	return false;
}

function save_settings($offices_check, $offices_settings_array)
{
	global $db_connect;
	global $g_fullname;
	global $g_general_id;
	$insert = "INSERT INTO form_settings (visibility_settings, form_type, created_by, general_id) VALUES (" . json_encode($offices_settings_array) . ",'4','" . escape($db_connect, $g_fullname) . "','" . escape($db_connect, $g_general_id) . "') ";
	// var_dump($insert);
	// exit();
	if (mysqli_query($db_connect, $insert)) {

		return true;
	}
	return false;
}

// function approve_form_faculty($general_id, $form_id, $fiscal_year)
// {
// 	global $db_connect;
// 	global $g_general_id;
// 	$sender_id = $g_general_id;
// 	$recepient_id = $general_id;
// 	$content = "All of the signatures for your faculty form has been approved.";
// 	$type = "faculty_notif";

// 	$delete_query = "UPDATE faculty_form_clearance SET status = '5', date_finished = '" . DATE_NOW . ' ' . TIME_NOW . "', fy = '" . $fiscal_year . "' WHERE general_id = '" . escape($db_connect, $general_id) . "' AND form_id = '" . escape($db_connect, $form_id) . "' LIMIT 1";
// 	if (mysqli_query($db_connect, $delete_query)) {
// 		notification($recepient_id, $content, $type, $sender_id);
// 		return true;
// 	}
// 	return false;
// }

function approved_form_faculty($general_id, $form_id, $fiscal_year)
{
	global $db_connect;

	$delete_query = "UPDATE faculty_form_clearance SET status = '5', date_finished = '" . DATE_TIME . "', fy = '" . $fiscal_year . "' WHERE general_id = '" . escape($db_connect, $general_id) . "' AND form_id = '" . escape($db_connect, $form_id) . "' LIMIT 1";
	if (mysqli_query($db_connect, $delete_query)) {
		return true;
	}
	return false;
}

function approved_form_student($general_id, $form_id, $fiscal_year)
{
	global $db_connect;

	$delete_query = "UPDATE student_form_clearance SET status = '5', date_finished = '" . DATE_TIME . "', fy = '" . $fiscal_year . "' WHERE general_id = '" . escape($db_connect, $general_id) . "' AND form_id = '" . escape($db_connect, $form_id) . "' LIMIT 1";
	if (mysqli_query($db_connect, $delete_query)) {
		return true;
	}
	return false;
}

function duedate($not_finished_count, $form_id, $general_id, $clearance_id, $fy_id)
{
	global $db_connect;
	global $g_general_id;

	$tables = [
		"1" => ["table" => "faculty_form_clearance", "notif_type" => "faculty_notif"],
		"2" => ["table" => "student_form_clearance", "notif_type" => "student_notif"],
		"3" => ["table" => "student_form_clearance", "notif_type" => "student_notif"],
		"4" => ["table" => "student_form_clearance", "notif_type" => "student_notif"],
		"5" => ["table" => "student_form_clearance", "notif_type" => "student_notif"],
	];

	$table = $tables[$clearance_id]['table'];
	$type = $tables[$clearance_id]['notif_type'];
	$status = "6";

	$insert = "UPDATE " . $table . " SET status = '" . $status . "' WHERE general_id = '" . escape($db_connect, $general_id) . "' AND form_id = '" . escape($db_connect, $form_id) . "' AND fy = '" . escape($db_connect, $fy_id) . "' LIMIT 1 ";
	if (mysqli_query($db_connect, $insert)) {
		// $form_type = form_type['type'][$clearance_id];
		// $sender_id = $g_general_id;
		// $recepient_id = $general_id;
		// $content = "You are over due for your " . $form_type . "";
		// notification($recepient_id, $content, $type, $sender_id);
		return true;
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

function get_user_info($user_id = "")
{
	global $db_connect, $session_class;
	$result = array("user_id" => 0, "general_id" => 0);

	$g_user_role = !empty($session_class->getValue("role_id")) ? trim($session_class->getValue("role_id")) : 0;
	$user_id = !empty($session_class->getValue("user_id")) ? trim($session_class->getValue("user_id")) : 0;

	## format_name - Last Name, First Name Suffix M.I.
	## full_name - First Name M.I. Last Name Suffix
	## program_name - BACHELOR
	## program_code - ACRONYM

	if ($g_user_role == "FACULTY") {
		$dept_sql = "SELECT u.*,CASE WHEN u.suffix IS NOT NULL AND u.suffix != '' THEN CONCAT(u.suffix,' ') ELSE '' END as suffix,CONCAT(u.l_name,', ',u.f_name, ' ',suffix,CASE WHEN u.m_name IS NOT NULL AND u.m_name != '' THEN CONCAT(LEFT(u.m_name,1),'.') ELSE '' END) as format_name,CONCAT(u.f_name,' ',CASE WHEN u.m_name IS NOT NULL AND u.m_name != '' THEN CONCAT(LEFT(u.m_name,1),'. ',u.l_name, ' ',suffix) ELSE '' END) as full_name, f.*,d.department as department_name,d.code_name as department_code FROM users u LEFT JOIN faculty_info f ON u.user_id = f.user_id LEFT JOIN departments d ON d.department_id = f.department WHERE u.user_id = '" . escape($db_connect, $user_id) . "' LIMIT 1";
		if ($dept_query = mysqli_query($db_connect, $dept_sql)) {
			if ($num = mysqli_num_rows($dept_query)) {
				if ($result = mysqli_fetch_array($dept_query, MYSQLI_ASSOC)) {
					mysqli_free_result($dept_query);
					return $result;
				}
			}
		}
	} elseif ($g_user_role == "STUDENT") {
		$dept_sql = "SELECT u.*,CASE WHEN u.suffix IS NOT NULL AND u.suffix != '' THEN CONCAT(u.suffix,' ') ELSE '' END as suffix,CONCAT(u.l_name,', ',u.f_name, ' ',suffix,CASE WHEN u.m_name IS NOT NULL AND u.m_name != '' THEN CONCAT(LEFT(u.m_name,1),'.') ELSE '' END) as format_name,CONCAT(u.f_name,' ',CASE WHEN u.m_name IS NOT NULL AND u.m_name != '' THEN CONCAT(LEFT(u.m_name,1),'. ',u.l_name, ' ',suffix) ELSE '' END) as full_name, s.*,d.department as department_name,d.code_name as department_code,p.program as program_name,p.short_name as program_code FROM users u LEFT JOIN student_info s ON u.user_id = s.user_id LEFT JOIN departments d ON d.department_id = s.college_dept LEFT JOIN programs p ON s.program = p.program_id WHERE u.user_id = '" . escape($db_connect, $user_id) . "' LIMIT 1";
		if ($dept_query = mysqli_query($db_connect, $dept_sql)) {
			if ($num = mysqli_num_rows($dept_query)) {
				if ($result = mysqli_fetch_array($dept_query, MYSQLI_ASSOC)) {
					mysqli_free_result($dept_query);
					return $result;
				}
			}
		}
	}

	return $result;
}

function get_admin_info($user_id = "")
{
	global $db_connect, $session_class;
	$to_encode = array("user_id" => 0, "general_id" => 0);
	$user_id = !empty($session_class->getValue("user_id")) ? trim($session_class->getValue("user_id")) : 0;

	$_default_query = "SELECT u.user_id,u.general_id,u.img,u.f_name,u.m_name,u.l_name,u.suffix,u.sex,u.birth_date,u.email_address,u.recovery_email,u.status,u.user_sign,u.parent_sign,u.flag_update,aos.signature as sign_path, o.id as office_id, o.name as office_name, o.code_name as office_code, o.assign FROM users u LEFT JOIN ao_signature aos ON u.user_sign = aos.sign_id LEFT JOIN office o ON u.user_id = o.assign WHERE u.user_id = '" . escape($db_connect, $user_id) . "' LIMIT 1";
	if ($_query = call_mysql_query($_default_query)) {
		if ($_num = call_mysql_num_rows($_query)) {
			if ($_data = call_mysql_fetch_array($_query)) {
				// $_data['sign_path'] = empty($_data['sign_path']) ? "" : USER_SIGN_PATH . $_data['sign_path'];
				// $_data['img_path'] = empty($_data['img']) ? UPLOAD_USER_IMG_PATH . DEFAULT_IMG : UPLOAD_USER_IMG_PATH . $_data['img'];
				$_data['sign_path'] = empty($_data['sign_path']) ? "" : $_data['sign_path'];
				$_data['img_path'] = empty($_data['img']) ? DEFAULT_IMG : $_data['img'];

				## signature flag
				$_data['flag_sign'] = true;
				if ($_data['user_sign']) $_data['flag_sign'] = false;

				## profile flag [false for admins]
				$_data['flag_profile'] = false;

				$_data['status'] = accountStatus[$_data['status']];
				$to_encode = $_data;

				mysqli_free_result($_query);
				return $to_encode;
			}
		}
	}

	return $to_encode;
}

function get_faculty_info($user_id = "")
{
	global $db_connect, $session_class;
	$to_encode = array("user_id" => 0, "general_id" => 0);

	$where = "";
	if (!empty($user_id)) {
		$where = "WHERE u.general_id = '" . escape($db_connect, $user_id) . "'";
	} else {
		$user_id = !empty($session_class->getValue("user_id")) ? trim($session_class->getValue("user_id")) : 0;
		$where = "WHERE u.user_id = '" . escape($db_connect, $user_id) . "'";
	}

	$_default_query = "SELECT u.user_id,u.general_id,u.img,u.f_name,u.m_name,u.l_name,u.suffix,u.sex,u.birth_date,u.email_address,u.recovery_email,u.status,u.user_sign,u.parent_sign,u.flag_update,fi.faculty_id,fi.department,fi.cluster_id,fi.account_status,fi.contact_number,fi.brgy,fi.home_address,d.department as department_name,d.code_name as department_code,pc.cluster_name,pc.cluster_code,aos.signature FROM users u LEFT JOIN faculty_info fi ON u.user_id = fi.user_id LEFT JOIN departments d ON d.department_id = fi.department LEFT JOIN program_cluster pc ON pc.cluster_id = fi.cluster_id LEFT JOIN ao_signature aos ON u.user_sign = aos.sign_id " . $where . " LIMIT 1";
	if ($_query = call_mysql_query($_default_query)) {
		if ($_num = call_mysql_num_rows($_query)) {
			if ($_data = call_mysql_fetch_array($_query)) {
				$_data['proper_name'] = get_proper_name($_data['f_name'], $_data['m_name'], $_data['l_name'], $_data['suffix']);
				$_data['format_name'] = get_format_name($_data['f_name'], $_data['m_name'], $_data['l_name'], $_data['suffix']);

				// $_data['sign_path'] = empty($_data['signature']) ? "" : USER_SIGN_PATH . $_data['signature'];
				// $_data['img_path'] = empty($_data['img']) ? USER_IMG_PATH . DEFAULT_IMG : USER_IMG_PATH . $_data['img'];
				$_data['sign_path'] = empty($_data['signature']) ? "" : $_data['signature'];
				$_data['img_path'] = empty($_data['img']) ? DEFAULT_IMG : $_data['img'];

				## signature flag
				$_data['flag_sign'] = true;
				if ($_data['user_sign']) $_data['flag_sign'] = false;

				## profile flag
				$_data['flag_profile'] = true;
				if ($_data['flag_update']) $_data['flag_profile'] = false;

				$_data['status'] = accountStatus[$_data['status']];
				$to_encode = $_data;

				mysqli_free_result($_query);
				return $to_encode;
			}
		}
	}

	return $to_encode;
}

function get_student_info($user_id = "")
{
	global $db_connect, $session_class;
	$to_encode = array("user_id" => 0, "general_id" => 0);

	$where = "";
	if (!empty($user_id)) {
		$where = "WHERE u.general_id = '" . escape($db_connect, $user_id) . "'";
	} else {
		$user_id = !empty($session_class->getValue("user_id")) ? trim($session_class->getValue("user_id")) : 0;
		$where = "WHERE u.user_id = '" . escape($db_connect, $user_id) . "'";
	}

	$_default_query = "SELECT u.user_id,u.general_id,u.img,u.f_name,u.m_name,u.l_name,u.suffix,u.sex,u.birth_date,u.position,u.email_address,u.recovery_email,u.status,u.user_sign,u.parent_sign,u.flag_update, si.student_id,si.program,si.account_status,si.classification_status,si.major,si.year_level,si.brgy,si.home_address,si.contact_number,si.eme_name,si.eme_contact,si.eme_address,si.graduation_date,si.school_name,si.course,si.school_address,si.gwa,si.credentials,si.date_admitted,si.category,si.degree,si.college_dept,si.so_number,si.so_date,si.nstp_serial,si.date_issued,si.research_title,si.tor_pages,si.documents,d.department as department_name,d.code_name as department_code,p.program as program_name,p.short_name as program_code,ss.signature FROM users u LEFT JOIN student_info si ON u.user_id = si.user_id LEFT JOIN departments d ON d.department_id = si.college_dept LEFT JOIN programs p ON si.program = p.program_id LEFT JOIN student_signature ss ON u.user_sign = ss.sign_id " . $where . " LIMIT 1";
	if ($_query = call_mysql_query($_default_query)) {
		if ($_num = call_mysql_num_rows($_query)) {
			if ($_data = call_mysql_fetch_array($_query)) {

				$_data['proper_name'] = get_proper_name($_data['f_name'], $_data['m_name'], $_data['l_name'], $_data['suffix']);
				$_data['format_name'] = get_format_name($_data['f_name'], $_data['m_name'], $_data['l_name'], $_data['suffix']);

				// $_data['sign_path'] = empty($_data['signature']) ? "" : USER_SIGN_PATH . $_data['signature'];
				// $_data['img_path'] = empty($_data['img']) ? USER_IMG_PATH . DEFAULT_IMG : USER_IMG_PATH . $_data['img'];
				$_data['sign_path'] = empty($_data['signature']) ? "" : $_data['signature'];
				$_data['img_path'] = empty($_data['img']) ? DEFAULT_IMG : $_data['img'];

				$_data['documents'] = !empty($_data['documents']) ? json_decode($_data['documents'], true) : [];
				// $_data['date_admitted'] = date("Y-m", $_data['date_admitted']);
				$_data['classification_status_text'] = isset(classificationStatus[$_data['classification_status']]) ? classificationStatus[$_data['classification_status']] : "";
				## signature flag
				$_data['flag_sign'] = true;
				if ($_data['user_sign']) $_data['flag_sign'] = false;

				## profile flag
				$_data['flag_profile'] = true;
				if ($_data['flag_update']) $_data['flag_profile'] = false;

				$_data['status'] = accountStatus[$_data['status']];
				$to_encode = $_data;

				mysqli_free_result($_query);
				return $to_encode;
			}
		}
	}

	return $to_encode;
}

function get_program_org_info($user_id, $school_year_id)
{
	global $db_connect;

	$_encoded = array();
	$_sql = "SELECT po.*,p.program_name FROM program_org AS po LEFT JOIN (SELECT program_id,program as program_name FROM programs) AS p ON po.program_id = p.program_id WHERE org_assign ='" . escape($db_connect, $user_id) . "' AND school_year_id = '" . escape($db_connect, $school_year_id) . "' LIMIT 1";
	if ($_query = call_mysql_query($_sql)) {
		if ($_num = call_mysql_num_rows($_query)) {
			if ($_data = call_mysql_fetch_array($_query)) {
				$program_assigned_id = $_data['org_id'];
				$org_assigned_id = "org_id_" . $program_assigned_id;
				$_data['org_assigned_id'] = $org_assigned_id;
				$_major = empty($_data['major']) || $_data['major'] == "xxx-ccc-xxx" ? "" : " (" . $_data['major'] . ")";
				$_data['org_assigned_name'] = $_data['flag_role'] . ": " . $_data['program_name'] . $_major;
				$_encoded = $_data;
			}
		}
	}

	return $_encoded;
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
