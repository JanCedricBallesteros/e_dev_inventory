<?php
function isValid_ColorInput($color)
{ //valid HEX and RGBA colors
	if (preg_match('/^(\#[\da-f]{3}|\#[\da-f]{6}|rgba?\(((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*,\s*){2}((\d{1,2}|1\d\d|2([0-4]\d|5[0-5]))\s*)(,\s*(0\.\d+|1))?\)|hsla?\(\s*((\d{1,2}|[1-2]\d{2}|3([0-5]\d|60)))\s*,\s*((\d{1,2}|100)\s*%)\s*,\s*((\d{1,2}|100)\s*%)(,\s*(0\.\d+|1))?\))$/', $color)) {
		return true;
	}
	return false;
}

function del_file($file)
{
	if (file_exists($file)) {
		unlink($file);
	}
}

function deleteDir($dirPath)
{
	if ($dirPath == "" or empty($dirPath)) {
		return false;
	}

	if (!is_dir($dirPath)) {
		throw new InvalidArgumentException($dirPath . " must be a directory");
	}

	if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
		$dirPath .= '/';
	}

	if ($handle = opendir($dirPath)) {
		while (false !== ($sub = readdir($handle))) {
			if ($sub != "." && $sub != ".." && $sub != "Thumb.db") {
				$file = $dirPath . $sub;
				if (is_dir($file)) {
					deleteDir($file);
				} else {
					unlink($file);
				}
			}
		}
		closedir($handle);
	}
	rmdir($dirPath);
}

function get_filesize($filePath)
{

	$size = 0;
	foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($filePath)) as $file) {
		$size += $file->getSize();
	}
	return $size;


	/*$filesize = 0;
	if((is_file($filePath)) AND (file_exists($filePath))){
        $filesize = filesize($filePath); // bytes
		$filesize = round($filesize / 1024 / 1024, 1); // megabytes with 1 digit
    }   
	
	return $filesize;*/
}

function is_base64_string($string)  //check base 64 encode 
{
	// Check if there is no invalid character in string
	if (!preg_match('/^(?:[data]{4}:(text|image|application)\/[a-z]*)/', $string)) {
		return false;
	} else {
		return true;
	}
}


function summernote_image($content)
{
	if ($content == "") return $content;
	if (strpos($content, '.tmp') !== false) {
		if (preg_match_all('/<img[^>]+src=["\']([^=]*)["\'][^>]*>/i', $content, $images)) {
			$images_srcs = $images[1];
			$collect = array();
			foreach ($images_srcs as $image_src) {
				$charlist = '.tmp';
				if ($image_src == "") continue;
				if (strpos($image_src, $charlist) === false) continue;

				$image_path  = DOMAIN_PATH . '/' . str_replace(BASE_URL, '', $image_src);
				$new_src = rtrim($image_src, $charlist);
				$new_path  = DOMAIN_PATH . '/' . str_replace(BASE_URL, '', $new_src);

				if (file_exists($image_path)) {
					rename($image_path, $new_path);
					$collect[$image_src] = $new_src;
				}
			}
			$content = strtr($content, $collect);
		}
	}

	return $content;
}
/*
to take mime type as a parameter and return the equivalent extension for image
*/
function mime2ext($mime)
{
	//png,bmp,gif,svg,jp2,jpeg,pjpeg,jpx,jpm,tiff,ico
	$all_mimes = '{"png":["image\/png","image\/x-png"],"bmp":["image\/bmp","image\/x-bmp",
    "image\/x-bitmap","image\/x-xbitmap","image\/x-win-bitmap","image\/x-windows-bmp",
    "image\/ms-bmp","image\/x-ms-bmp"],"gif":["image\/gif"],"jpeg":["image\/jpeg",
    "image\/pjpeg"],"svg":["image\/svg+xml"],"jp2":["image\/jp2","image\/jpx","image\/jpm"],"tiff":["image\/tiff"],
	"ico":["image\/x-icon","image\/x-ico","image\/vnd.microsoft.icon"]}';
	$all_mimes = json_decode($all_mimes, true);
	foreach ($all_mimes as $key => $value) {
		if (array_search($mime, $value) !== false) return $key;
	}
	return false;
}
// Returns a POST request size limit in bytes based on the PHP post_max_size
function post_max_limit()
{
	$size = parse_size(ini_get('post_max_size'));
	return $size;
}

// Returns a POST request size limit in bytes based on the PHP post_max_size
function upload_max_limit()
{
	$size = parse_size(ini_get('upload_max_filesize'));
	return $size;
}

function request_length()
{
	if (is_array($_POST) and !empty($_POST)) {
		$size = $_SERVER['CONTENT_LENGTH'];
		return $size;
	}
}

$g_cipher = "aes-256-cbc";
$g_key = "nomenclature";
function encrypted_string($unencrypt)
{
	global $g_cipher, $g_key;
	$dirty = array("+", "/", "=");
	$clean = array("_PLUS_", "_SLASH_", "_EQUALS_");
	$plaintext = "$unencrypt";

	$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($g_cipher));
	$encrypted = openssl_encrypt($plaintext, $g_cipher, $g_key, 0, $iv);
	$ciphertext = base64_encode($encrypted . '::' . $iv);
	$encrypted = str_replace($dirty, $clean, $ciphertext);
	return $encrypted;
}

function decrypted_string($encrypted_string)
{
	global $g_cipher, $g_key;
	$dirty = array("+", "/", "=");
	$clean = array("_PLUS_", "_SLASH_", "_EQUALS_");
	$garble = str_replace($clean, $dirty, $encrypted_string);
	list($decoded, $iv) = explode('::', base64_decode($garble), 2);
	$plaintext = openssl_decrypt($decoded, $g_cipher, $g_key, 0, $iv);

	return $plaintext;
}

function custom_encrypted_string($g_key, $unencrypt, $g_cipher = "aes-256-cbc")
{
	$dirty = array("+", "/", "=");
	$clean = array("_PLUS_", "_SLASH_", "_EQUALS_");
	$plaintext = "$unencrypt";

	$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($g_cipher));
	$encrypted = openssl_encrypt($plaintext, $g_cipher, $g_key, 0, $iv);
	$ciphertext = base64_encode($encrypted . '::' . $iv);
	$encrypted = str_replace($dirty, $clean, $ciphertext);
	return $encrypted;
}

function custom_decrypted_string($g_key, $encrypted_string, $g_cipher = "aes-256-cbc")
{
	$dirty = array("+", "/", "=");
	$clean = array("_PLUS_", "_SLASH_", "_EQUALS_");
	$garble = str_replace($clean, $dirty, $encrypted_string);
	list($decoded, $iv) = explode('::', base64_decode($garble), 2);
	$plaintext = openssl_decrypt($decoded, $g_cipher, $g_key, 0, $iv);
	return $plaintext;
}

function encrypted_data($g_key, $unencrypt, $g_cipher = CRYPT_CIPHER, $dirty = CRYPT_DIRTY, $clean = CRYPT_CLEAN)
{
	$plaintext = "$unencrypt";

	$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($g_cipher));
	$encrypted = openssl_encrypt($plaintext, $g_cipher, $g_key, 0, $iv);
	$ciphertext = base64_encode($encrypted . '::' . $iv);
	$encrypted = str_replace($dirty, $clean, $ciphertext);
	return $encrypted;
}

function decrypted_data($g_key, $encrypted_string, $g_cipher = CRYPT_CIPHER, $dirty = CRYPT_DIRTY, $clean = CRYPT_CLEAN)
{
	$garble = str_replace($clean, $dirty, $encrypted_string);
	list($decoded, $iv) = explode('::', base64_decode($garble), 2);
	$plaintext = openssl_decrypt($decoded, $g_cipher, $g_key, 0, $iv);
	return $plaintext;
}

function formatBytes($bytes, $precision = 2)
{
	$units = array('BYTES', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

	$l = 0;
	$n = (int)$bytes or 0;
	while ($n >= 1024) {
		$n = $n / 1024;
		$l += 1;
	}
	return (round($n, $precision) . ' ' . $units[$l]);
}

function convertDataUnit($value = 0, $unit_from = 'BYTES', $unit_to = 'BYTES', $base = 1024)
{
	$units = array('BYTES', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	$result = array("value" => 0, "unit" => "");
	$result['unit'] = $unit_to;

	if (!is_digit($value)) {
		return $result;
	}
	if ($value <= 0) {
		return $result;
	}

	$unit_from_id = array_search($unit_from, $units);
	$unit_to_id =  array_search($unit_to, $units);

	$unit_from_id = ($unit_from_id === FALSE) ? 0 : $unit_from_id;
	$unit_to_id = ($unit_to_id === FALSE) ? 0 : $unit_to_id;

	if ($unit_to_id == $unit_from_id) { //same unit
		$result['value'] = $value;
		$result['unit'] = $unit_to;
		return $result;
	}

	if ($unit_from_id < $unit_to_id) {
		$loop = $unit_to_id - $unit_from_id;
		while ($loop > 0) {
			$value = $value / $base;
			$loop -= 1;
		}
	} else if ($unit_from_id > $unit_to_id) {
		$loop = abs($unit_to_id - $unit_from_id);
		$value = (pow($base, $loop)) * $value;
	}

	$result['value'] = $value;
	$result['unit'] = $unit_to;

	return $result;
}

function html($string)
{
	return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function xml($string)
{
	return htmlspecialchars($string, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function converToTz($time = "", $toTz = '', $fromTz = '', $format = 'Y-m-d H:i:s')
{
	$time = ($time == "") ? date('Y-m-d H:i:s') : $time;
	$date = new DateTime($time, new DateTimeZone($fromTz));
	$date->setTimezone(new DateTimeZone($toTz));
	if ($format == "DateTime::W3C") {
		$time = $date->format(DateTime::W3C);
	} else {
		$time = $date->format($format);
	}

	return $time;
}
function timestamp_precise($time = '')
{ // for unix timestamp 13 with milliseconds digits
	if ($time == '') {
		$time = microtime(true);
	}
	return round($time * 1000);
}
function timestamp_php($time = '')
{ // for unix timestamp 13 with decimal in milliseconds digits
	if ($time == '') {
		$time = timestamp_precise();
	}
	return round($time / 1000, 3);
}
function get_ip()
{
	if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	} elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	} else {
		$ip = $_SERVER["REMOTE_ADDR"];
	}
	return $ip;
}

function array_html($str)
{
	$flags = ENT_QUOTES;
	$encoding = "UTF-8";
	if (!(is_null($str)) and is_array($str)) {
		foreach ($str as $index => $value) {
			$str[$index] = $value;
			if (empty($value)) {
				continue;
			}
			$temp = js_clean($value);
			$str[$index] = htmlspecialchars($temp, $flags, $encoding);
		}
	}
	return $str;
}

function js_clean_array($array)
{
	$return = array();
	if (!(empty($array)) and is_array($array)) {
		foreach ($array as $index => $value) {
			$return[$index] = $value;
			if (empty($value)) {
				continue;
			}
			$return[$index] = js_clean($value);
		}
	}
	return $return;
}

function var_html($str)
{
	$flags = ENT_QUOTES;
	$encoding = "UTF-8";
	$temp = js_clean($str);
	$str = htmlspecialchars($temp, $flags, $encoding);
	return $str;
}
function output($array)
{
	$json = json_encode($array, JSON_NUMERIC_CHECK);
	return $json;
}
function js_clean($data)
{
	//$data = strip_tags($data);
	//Remove Script tags
	//$data = preg_replace('/<script>/', '$1>',$data);

	// Remove any attribute starting with "on" or xmlns
	$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

	// Remove javascript: and vbscript: protocols
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
	$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

	// Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
	$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

	// Remove namespaced elements (we do not need them)
	$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

	do {
		// Remove really unwanted tags
		$old_data = $data;
		$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
	} while ($old_data !== $data);

	// we are done...
	return $data;
}

function validateDate($date, $format = 'Y-m-d H:i:s', $iso = false) /*modiy 8/21/2020*/
{
	if (empty($date)) {
		return false;
	}

	if (isTimestampIsoValid($date)) {
		$iso = true;
		return true;
	} else {
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}
	return false;
}

function formatDate($date, $format = 'Y-m-d H:i:s', $iso = false)/*modify*/
{
	//if(validateDate($date,$format,$iso)){
	date_default_timezone_set(DEFAULT_TIMEZONE);

	return date($format, strtotime($date));
	//}
	//return '';

}
function isTimestampIsoValid($timestamp) /*modify*/
{
	if (preg_match('/^' .
		'(\d{4})-(\d{2})-(\d{2})T' . // YYYY-MM-DDT ex: 2014-01-01T
		'(\d{2}):(\d{2}):(\d{2})' .  // HH-MM-SS  ex: 17:00:00
		'(Z|((-|\+)\d{2}:\d{2}))' .  // Z or +01:00 or -01:00
		'$/', $timestamp, $parts) == true) {
		try {
			new \DateTime($timestamp);
			return true;
		} catch (\Exception $e) {
			return false;
		}
	} else {
		return false;
	}
}


function set_password($text)
{
	if (trim($text) != "") {
		return SHA1($text . SALT); ## please input "SALT" (e.g. SHA1($text.SALT)) , if we have users registration
	}
	return '';
}

/**
 * Check input for existing only of digits (numbers)
 * @author Tim Boormans <info@directwebsolutions.nl>
 * @param $digit
 * @return bool
 */

function is_digit($digit)
{
	if (is_int($digit)) {
		return true;
	} elseif (is_string($digit)) {
		return ctype_digit($digit);
	} else {
		// booleans, floats and others
		return false;
	}
}

function get_array_changes($a1, $a2)
{
	$result = array();
	$old_array = array();
	$array_diff  = array_diff_assoc($a2, $a1);

	foreach ($array_diff as $index => $array_val) {
		$old_array[$index] = isset($a1[$index]) ? $a1[$index] : NULL;
	}

	$result['old_data'] = $old_array;
	$result['new_data'] = $array_diff;

	return $result;
}

function DateDiffInterval($sDate1, $sDate2, $sUnit = 'H')
{
	$nInterval = strtotime($sDate2) - strtotime($sDate1);
	if ($sUnit == 'D') { // days
		$nInterval = $nInterval / 60 / 60 / 24;
	} else if ($sUnit == 'H') { // hours
		$nInterval = $nInterval / 60 / 60;
	} else if ($sUnit == 'M') { // minutes
		$nInterval = $nInterval / 60;
	} else if ($sUnit == 'S') { // seconds
	}
	return $nInterval;
}

function is_shorten_url($Address)
{
	$Address = trim($Address);
	if (empty($Address)) return true;

	$parseUrl = parse_url(($Address));
	$host = trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2)));

	$shorten_links = array('bit.ly', 'tinyurl.com', 't.co', 'ow.ly', 'rb.gy', 'tiny.one', 'rotf.lol', 'to.short.cm', 'cutt.ly', 'bl.ink', 'short.fyi', 'mz.cm', 't.ly', 'is.gd', 'goo.gl', 'ctiny.me', 'www.seebot.run', 'tiny.cc');

	if (in_array($host, $shorten_links))  return true;

	if (strlen($Address) > 30) return false;

	if ($parseUrl["query"] || $parseUrl["fragment"]) return false;

	$path = $parseUrl["path"];
	$pathParts = explode("/", $path);
	if (count($pathParts) > 2) return false;

	$lastPath = array_pop($pathParts);
	if (strlen($lastPath) > 10) return false;

	if (strlen($host) > 10) return false;

	return true;
}

function is_google_drive($Address)
{
	$Address = trim($Address);
	if (empty($Address)) return false;

	$parseUrl = parse_url(($Address));
	$host = trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2)));

	$google_link = array('docs.google.com', 'drive.google.com');

	if (in_array($host, $google_link))  return true;

	return false;
}

function valid_submit_link($Address)
{
	$Address = trim($Address);
	if (empty($Address)) return false;

	$parseUrl = parse_url(($Address));
	$host = trim($parseUrl['host'] ? $parseUrl['host'] : array_shift(explode('/', $parseUrl['path'], 2)));

	$valid_link = array('docs.google.com', 'drive.google.com', 'www.youtube.com', 'youtube.com', 'youtu.be');

	if (in_array($host, $valid_link))  return true;

	return false;
}

function array_search_revision($needle, $haystack, $key_find)
{
	$result = false;
	if (is_array($haystack) and $key_find != "") {
		foreach ($haystack as $key => $sub_array) {
			if (!isset($sub_array[$key_find])) return false;
			if ($sub_array[$key_find] == $needle) return $key;
		}
	}

	return  $result;
}

function serial_number()
{
	$template   = 'X9X9-XX9X-9X9X';
	$k = strlen($template);
	$sernum = '';
	for ($i = 0; $i < $k; $i++) {
		switch ($template[$i]) {
			case 'X':
				$sernum .= chr(rand(65, 90));
				break;
			case '9':
				$sernum .= rand(0, 9);
				break;
			case '-':
				$sernum .= '-';
				break;
		}
	}
	return $sernum;
}

function password_generate()
{
	$data = '0123456789abcdefghijklmnopqrstuvwxyz@#$%&_ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	return substr(str_shuffle($data), 0, 10);
}

function msg_alert($response, $msg)
{
	$msg_response = [
		'msg_success' => [
			'class' => 'alert-success',
			'icon' => 'bi bi-check-circle-fill',
		],
		'msg_error' => [
			'class' => 'alert-danger',
			'icon' => 'bi bi-exclamation-circle-fill',
		],
		'msg_info' => [
			'class' => 'alert-info',
			'icon' => 'bi bi-info-square-fill',
		],
		'msg_warning' => [
			'class' => 'alert-warning',
			'icon' => 'bi bi-exclamation-triangle-fill',
		],
	];
	$output = '<div id="alert-message" class="alert ' . $msg_response[$response]['class'] . ' alert-dismissible fade show font-monospace" role="alert">
					<i class="' . $msg_response[$response]['icon'] . '"></i> ' . $msg . '
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>';

	return $output;
}

function update_summary_logs($summary_file, $filename, $gen_id = "", $username = "")
{
	global $session_class;

	//$name = $session_class->getValue('name');
	date_default_timezone_set(DEFAULT_TIMEZONE);
	$log = date("Y-m-d H:i:s") . "|" . $gen_id . "|" . $username . "|" . $filename . "\r\n";
	file_put_contents($summary_file, $log, FILE_APPEND);
}

function tailCustom($filepath, $lines = 1, $adaptive = true)
{

	// Open file
	$f = @fopen($filepath, "rb");
	if ($f === false) return false;

	// Sets buffer size, according to the number of lines to retrieve.
	// This gives a performance boost when reading a few lines from the file.
	if (!$adaptive) $buffer = 4096;
	else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

	// Jump to last character
	fseek($f, -1, SEEK_END);

	// Read it and adjust line number if necessary
	// (Otherwise the result would be wrong if file doesn't end with a blank line)
	if (fread($f, 1) != "\n") $lines -= 1;

	// Start reading
	$output = '';
	$chunk = '';

	// While we would like more
	while (ftell($f) > 0 && $lines >= 0) {

		// Figure out how far back we should jump
		$seek = min(ftell($f), $buffer);

		// Do the jump (backwards, relative to where we are)
		fseek($f, -$seek, SEEK_CUR);

		// Read a chunk and prepend it to our output
		$output = ($chunk = fread($f, $seek)) . $output;

		// Jump back to where we started reading
		fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

		// Decrease our line counter
		$lines -= substr_count($chunk, "\n");
	}
	// While we have too many lines
	// (Because of buffer size we might have read too many)
	while ($lines++ < 0) {

		// Find first newline and remove all text before that
		$output = substr($output, strpos($output, "\n") + 1);
	}
	// Close file and return
	fclose($f);

	return trim($output);
}
