<?php
/**
 * Some code are snippet from GUMP Class
 * Modify based on needs.
 *
 * GUMP - A fast, extensible PHP input validation class.
 *
 * @author      Sean Nieuwoudt (http://twitter.com/SeanNieuwoudt)
 * @author      Filis Futsarov (http://twitter.com/FilisCode)
 * @copyright   Copyright (c) 2017 wixelhq.com
 *
 * @version     1.5
	The MIT License (MIT)

	Copyright (c) 2013-2017 Wixel

	Permission is hereby granted, free of charge, to any person obtaining a copy of
	this software and associated documentation files (the "Software"), to deal in
	the Software without restriction, including without limitation the rights to
	use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
	the Software, and to permit persons to whom the Software is furnished to do so,
	subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all
	copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
	FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
	COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
	IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
	CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
/* how to use:

$rules = array("field_name" => "rule_name,rule_value|rule_name,rule_value", n...);
$field_label = array("field_name" => "label_name", n...);

$validator = new FormValidator();
$validator->set_field_names($field_label);
$validated = $validator->validate($_POST, $rules);

if($validated === TRUE){	

}else{
	$message['msg'] = '';
	$message['result'] ='error';
	$message['errors'] = $validator->get_readable_errors(true); //print error
}
*/
class FormValidator
{
    // Singleton instance
    protected static $instance = null;

    // Validation rules for execution
    protected $validation_rules = array();

    // Filter rules for execution
    protected $filter_rules = array();

    // Instance attribute containing errors from last run
    protected $errors = array();

    // Contain readable field names that have been set manually
    protected static $fields = array();

    // Custom validation methods
    protected static $validation_methods = array();

    // Custom validation methods error messages and custom ones
    protected static $validation_methods_errors = array();

    // Customer filter methods
    protected static $filter_methods = array();

	//File Uplaod errors
	protected static $phpFileUploadErrors = array(
		0 => 'There is no error, the file uploaded with success.',
		1 => 'The {field} uploaded file size exceeds the allowable limit {param}.',
		2 => 'The {field} uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
		3 => 'The {field} uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Missing a temporary folder in Server',
		7 => 'Failed to write file to disk.',
		8 => 'A PHP extension stopped the file upload.',
	);

    // ** ------------------------- Instance Helper ---------------------------- ** //
    /**
     * Function to create and return previously created instance
     *

     */

    public static function get_instance(){
        if(self::$instance === null)
        {
            self::$instance = new static();
        }
        return self::$instance;
    }


    // ** ------------------------- Validation Data ------------------------------- ** //

    public static $basic_tags = '<br><p><a><strong><b><i><em><img><blockquote><code><dd><dl><hr><h1><h2><h3><h4><h5><h6><label><ul><li><span><sub><sup>';

    public static $en_noise_words = "about,after,all,also,an,and,another,any,are,as,at,be,because,been,before,being,between,both,but,by,came,can,come,could,did,do,each,for,from,get,got,has,had,he,have,her,here,him,himself,his,how,if,in,into,is,it,its,it's,like,make,many,me,might,more,most,much,must,my,never,now,of,on,only,or,other,our,out,over,said,same,see,should,since,some,still,such,take,than,that,the,their,them,then,there,these,they,this,those,through,to,too,under,up,very,was,way,we,well,were,what,where,which,while,who,with,would,you,your,a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,$,1,2,3,4,5,6,7,8,9,0,_";

    // field characters below will be replaced with a space.
    protected $fieldCharsToRemove = array('_', '-');

    protected $lang;


    // ** ------------------------- Validation Helpers ---------------------------- ** //

    public function __construct($lang = 'en')
    {
        if ($lang) {
            $lang_file = __DIR__.DIRECTORY_SEPARATOR.$lang.'.php';

            if (file_exists($lang_file)) {
                $this->lang = $lang;
            } else {
                throw new \Exception('Language with key "'.$lang.'" does not exist');
            }
        }
    }

    /**
     * Shorthand method for inline validation.
     *
     * @param array $data       The data to be validated
     * @param array $validators 
     *
     * @return mixed True(boolean) or the array of error messages
     */
    public static function is_valid(array $data, array $validators)
    {
        $FormValidator = self::get_instance();

        $FormValidator->validation_rules($validators);

        if ($FormValidator->run($data) === false) {
            return $FormValidator->get_readable_errors(false);
        } else {
            return true;
        }
    }

    /**
     * Shorthand method for running only the data filters.
     *
     * @param array $data
     * @param array $filters
     *
     * @return mixed
     */
    public static function filter_input(array $data, array $filters)
    {
        $FormValidator = self::get_instance();

        return $FormValidator->filter($data, $filters);
    }

    /**
     * Magic method to generate the validation error messages.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->get_readable_errors(true);
    }

    /**
     * Perform XSS clean to prevent cross site scripting.
     *
     * @static
     *
     * @param array $data
     *
     * @return array
     */
    public static function xss_clean(array $data)
    {
        foreach ($data as $k => $v) {
            $data[$k] = filter_var($v, FILTER_SANITIZE_STRING);
        }

        return $data;
    }

    /**
     * Adds a custom validation rule using a callback function.
     *
     * @param string   $rule
     * @param callable $callback
     * @param string   $error_message
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function add_validator($rule, $callback, $error_message = null)
    {
        $method = 'validate_'.$rule;

        if (method_exists(__CLASS__, $method) || isset(self::$validation_methods[$rule])) {
            throw new Exception("Validator rule '$rule' already exists.");
        }

        self::$validation_methods[$rule] = $callback;
        if ($error_message) {
            self::$validation_methods_errors[$rule] = $error_message;
        }

        return true;
    }

    /**
     * Adds a custom filter using a callback function.
     *
     * @param string   $rule
     * @param callable $callback
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function add_filter($rule, $callback)
    {
        $method = 'filter_'.$rule;

        if (method_exists(__CLASS__, $method) || isset(self::$filter_methods[$rule])) {
            throw new Exception("Filter rule '$rule' already exists.");
        }

        self::$filter_methods[$rule] = $callback;

        return true;
    }

    /**
     * Helper method to extract an element from an array safely
     *
     * @param mixed $key
     * @param array $array
     * @param mixed $default
     * @return mixed
     */
    public static function field($key, array $array, $default = null)
    {
      if(!is_array($array)) {
        return null;
      }

      if(isset($array[$key])) {
        return $array[$key];
      } else {
        return $default;
      }
    }

    /**
     * Getter/Setter for the validation rules.
     *
     * @param array $rules
     *
     * @return array
     */
    public function validation_rules(array $rules = array())
    {
        if (empty($rules)) {
            return $this->validation_rules;
        }

        $this->validation_rules = $rules;
    }

    /**
     * Getter/Setter for the filter rules.
     *
     * @param array $rules
     *
     * @return array
     */
    public function filter_rules(array $rules = array())
    {
        if (empty($rules)) {
            return $this->filter_rules;
        }

        $this->filter_rules = $rules;
    }

    /**
     * Run the filtering and validation after each other.
     *
     * @param array $data
     * @param bool  $check_fields
     * @param string $rules_delimiter
     * @param string $parameters_delimiters
     *
     * @return array
     *
     * @throws Exception
     */
    public function run(array $data, $check_fields = false, $rules_delimiter='|', $parameters_delimiters=',')
    {
        $data = $this->filter($data, $this->filter_rules(), $rules_delimiter, $parameters_delimiters);

        $validated = $this->validate(
            $data, $this->validation_rules(),
            $rules_delimiter, $parameters_delimiters
        );

        if ($check_fields === true) {
            $this->check_fields($data);
        }

        if ($validated !== true) {
            return false;
        }

        return $data;
    }

    /**
     * Ensure that the field counts match the validation rule counts.
     *
     * @param array $data
     */
    private function check_fields(array $data)
    {
        $ruleset = $this->validation_rules();
        $mismatch = array_diff_key($data, $ruleset);
        $fields = array_keys($mismatch);

        foreach ($fields as $field) {
            $this->errors[] = array(
                'field' => $field,
                'value' => $data[$field],
                'rule' => 'mismatch',
                'param' => null,
            );
        }
    }

    /**
     * Sanitize the input data.
     *
     * @param array $input
     * @param null  $fields
     * @param bool  $utf8_encode
     *
     * @return array
     */
    public function sanitize(array $input, array $fields = array(), $utf8_encode = true)
    {
        $magic_quotes = (bool) get_magic_quotes_gpc();

        if (empty($fields)) {
            $fields = array_keys($input);
        }

        $return = array();

        foreach ($fields as $field) {
            if (!isset($input[$field])) {
                continue;
            } else {
                $value = $input[$field];
                if (is_array($value)) {
                    $value = $this->sanitize($value);
                }
                if (is_string($value)) {
                    if ($magic_quotes === true) {
                        $value = stripslashes($value);
                    }

                    if (strpos($value, "\r") !== false) {
                        $value = trim($value);
                    }

                    if (function_exists('iconv') && function_exists('mb_detect_encoding') && $utf8_encode) {
                        $current_encoding = mb_detect_encoding($value);

                        if ($current_encoding != 'UTF-8' && $current_encoding != 'UTF-16') {
                            $value = iconv($current_encoding, 'UTF-8', $value);
                        }
                    }

                    $value = filter_var($value, FILTER_SANITIZE_STRING);
                }

                $return[$field] = $value;
            }
        }

        return $return;
    }

    /**
     * Return the error array from the last validation run.
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Perform data validation against the provided ruleset.
     * If any rule's parameter contains either '|' or ',', the corresponding default separator can be changed
     *
     * @param mixed $input
     * @param array $ruleset
     * @param string $rules_delimiter
     * @param string $parameters_delimiter
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function validate(array $input, array $ruleset, $rules_delimiter='|', $parameters_delimiter=',')
    {
        $this->errors = array();

        foreach ($ruleset as $field => $rules) {

            $rules = explode($rules_delimiter, $rules);

            $look_for = array('required_file', 'required');

            if (count(array_intersect($look_for, $rules)) > 0 || (isset($input[$field]))) {

                if (isset($input[$field])) {
                    if (is_array($input[$field]) && in_array('required_file', $ruleset)) {
                        $input_array = $input[$field];
                    } else {
                        $input_array = array($input[$field]);
                    }
                } else {
                    $input_array = array('');
                }

                foreach ($input_array as $value) {

                    $input[$field] = trim($value);

                    foreach ($rules as $rule) {
                        $method = null;
                        $param = null;

                        // Check if we have rule parameters
                        if (strstr($rule, $parameters_delimiter) !== false) {
                            $rule   = explode($parameters_delimiter, $rule);
                            $method = 'validate_'.$rule[0];
                            $param  = $rule[1];
                            $rule   = $rule[0];

                            // If there is a reference to a field
                            if (preg_match('/(?:(?:^|;)_([a-z_]+))/', $param, $matches)) {

                                // If provided parameter is a field
                                if (isset($input[$matches[1]])) {
                                    $param = str_replace('_'.$matches[1], $input[$matches[1]], $param);
                                }
                            }
                        } else {
                            $method = 'validate_'.$rule;
                        }

                        //self::$validation_methods[$rule] = $callback;

                        if (is_callable(array($this, $method))) {
                            $result = $this->$method(
                                $field, $input, $param
                            );

                            if (is_array($result)) {
                                if (array_search($result['field'], array_column($this->errors, 'field')) === false) {
                                    $this->errors[] = $result;
                                }
                            }

                        } elseif(isset(self::$validation_methods[$rule])) {
                            $result = call_user_func(self::$validation_methods[$rule], $field, $input, $param);

                            if($result === false) {
                                if (array_search($result['field'], array_column($this->errors, 'field')) === false) {
                                    $this->errors[] = array(
                                        'field' => $field,
                                        'value' => $input[$field],
                                        'rule' => $rule,
                                        'param' => $param,
                                    );
                                }
                            }

                        } else {
                            throw new Exception("Validator method '$method' does not exist.");
                        }
                    }
                }
            }
        }

        return (count($this->errors) > 0) ? $this->errors : true;
    }

    /**
     * Set a readable name for a specified field names.
     *
     * @param string $field
     * @param string $readable_name
     */
    public static function set_field_name($field, $readable_name)
    {
        self::$fields[$field] = $readable_name;
    }

    /**
     * Set readable name for specified fields in an array.
     *
     * Usage:
     *
     * FormValidator::set_field_names(array(
     *  "name" => "My Lovely Name",
     *  "username" => "My Beloved Username",
     * ));
     *
     * @param array $array
     */
    public static function set_field_names(array $array)
    {
        foreach ($array as $field => $readable_name) {
            self::set_field_name($field, $readable_name);
        }
    }

    /**
     * Set a custom error message for a validation rule.
     *
     * @param string $rule
     * @param string $message
     */
    public static function set_error_message($rule, $message)
    {
        $FormValidator = self::get_instance();
        self::$validation_methods_errors[$rule] = $message;
    }

    /**
     * Set custom error messages for validation rules in an array.
     *
     * Usage:
     *
     * FormValidator::set_error_messages(array(
     *  "validate_required"     => "{field} is required",
     *  "validate_valid_email"  => "{field} must be a valid email",
     * ));
     *
     * @param array $array
     */
    public static function set_error_messages(array $array)
    {
        foreach ($array as $rule => $message) {
            self::set_error_message($rule, $message);
        }
    }

    /**
     * Get error messages.
     *
     * @return array
     */
    protected function get_messages()
    {
        $lang_file = __DIR__.DIRECTORY_SEPARATOR.$this->lang.'.php';
        $messages = require $lang_file;

        if ($validation_methods_errors = self::$validation_methods_errors) {
            $messages = array_merge($messages, $validation_methods_errors);
        }
        return $messages;
    }

    /**
     * Process the validation errors and return human readable error messages.
     *
     * @param bool   $convert_to_string = false
     * @param string $field_class
     * @param string $error_class
     *
     * @return array
     * @return string
     */
    public function get_readable_errors($convert_to_string = false, $field_class = 'formvalidator-field', $error_class = 'formvalidator-error-message')
    {
        if (empty($this->errors)) {
            return ($convert_to_string) ? null : array();
        }

        $resp = array();

        // Error messages
        $messages = $this->get_messages();

        foreach ($this->errors as $e) {
            $field = ucwords(str_replace($this->fieldCharsToRemove, chr(32), $e['field']));
            $param = $e['param'];

            // Let's fetch explicitly if the field names exist
            if (array_key_exists($e['field'], self::$fields)) {
                $field = self::$fields[$e['field']];

                // If param is a field (i.e. equalsfield validator)
                if (array_key_exists($param, self::$fields)) {
                    $param = self::$fields[$e['param']];
                }
            }

            // Messages
            if (isset($messages[$e['rule']])) {
                if (is_array($param)) {
                    $param = implode(', ', $param);
                }
				if("validate_valid_filesize"==$e['rule']){
					$param =formatBytes($param);
				}
                $message = str_replace('{param}', $param, str_replace('{field}', '<span class="'.$field_class.'">'.$field.'</span>', $messages[$e['rule']]));
                $resp[] = $message;
            } else {
                throw new \Exception ('Rule "'.$e['rule'].'" does not have an error message');
            }
        }

        if (!$convert_to_string) {
            return $resp;
        } else {
            $buffer = '';
            foreach ($resp as $s) {
                $buffer .= "<span class=\"$error_class\">$s</span>";
            }
            return $buffer;
        }
    }

    /**
     * Process the validation errors and return an array of errors with field names as keys.
     *
     * @param $convert_to_string
     *
     * @return array | null (if empty)
     */
    public function get_errors_array($convert_to_string = null)
    {
        if (empty($this->errors)) {
            return ($convert_to_string) ? null : array();
        }

        $resp = array();

        // Error messages
        $messages = $this->get_messages();

        foreach ($this->errors as $e)
        {
            $field = ucwords(str_replace(array('_', '-'), chr(32), $e['field']));
            $param = $e['param'];

            // Let's fetch explicitly if the field names exist
            if (array_key_exists($e['field'], self::$fields)) {
                $field = self::$fields[$e['field']];

                // If param is a field (i.e. equalsfield validator)
                if (array_key_exists($param, self::$fields)) {
                    $param = self::$fields[$e['param']];
                }
            }

            // Messages
            if (isset($messages[$e['rule']])) {
                // Show first validation error and don't allow to be overwritten
                if (!isset($resp[$e['field']])) {
                    if (is_array($param)) {
                        $param = implode(', ', $param);
                    }
                    $message = str_replace('{param}', $param, str_replace('{field}', $field, $messages[$e['rule']]));
                    $resp[$e['field']] = $message;
                }
            } else {
                throw new \Exception ('Rule "'.$e['rule'].'" does not have an error message');
            }
        }

        return $resp;
    }

    /**
     * Filter the input data according to the specified filter set.
     * If any filter's parameter contains either '|' or ',', the corresponding default separator can be changed
     *
     * @param mixed $input
     * @param array $filterset
     * @param string $filters_delimeter
     * @param string $parameters_delimiter
     *
     * @throws Exception
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function filter(array $input, array $filterset, $filters_delimeter='|', $parameters_delimiter=',')
    {
        foreach ($filterset as $field => $filters) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $filters = explode($filters_delimeter, $filters);

            foreach ($filters as $filter) {
                $params = null;

                if (strstr($filter, $parameters_delimiter) !== false) {
                    $filter = explode($parameters_delimiter, $filter);

                    $params = array_slice($filter, 1, count($filter) - 1);

                    $filter = $filter[0];
                }

                if (is_array($input[$field])) {
                    $input_array = &$input[$field];
                } else {
                    $input_array = array(&$input[$field]);
                }

                foreach ($input_array as &$value) {
                    if (is_callable(array($this, 'filter_'.$filter))) {
                        $method = 'filter_'.$filter;
                        $value = $this->$method($value, $params);
                    } elseif (function_exists($filter)) {
                        $value = $filter($value);
                    } elseif (isset(self::$filter_methods[$filter])) {
                        $value = call_user_func(self::$filter_methods[$filter], $value, $params);
                    } else {
                        throw new Exception("Filter method '$filter' does not exist.");
                    }
                }
            }
        }

        return $input;
    }

    // ** ------------------------- Filters --------------------------------------- ** //

    /**
     * Replace noise words in a string (http://tax.cchgroup.com/help/Avoiding_noise_words_in_your_search.htm).
     *
     * Usage: '<index>' => 'noise_words'
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_noise_words($value, $params = null)
    {
        $value = preg_replace('/\s\s+/u', chr(32), $value);

        $value = " $value ";

        $words = explode(',', self::$en_noise_words);

        foreach ($words as $word) {
            $word = trim($word);

            $word = " $word "; // Normalize

            if (stripos($value, $word) !== false) {
                $value = str_ireplace($word, chr(32), $value);
            }
        }

        return trim($value);
    }

    /**
     * Remove all known punctuation from a string.
     *
     * Usage: '<index>' => 'rmpunctuataion'
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_rmpunctuation($value, $params = null)
    {
        return preg_replace("/(?![.=$'€%-])\p{P}/u", '', $value);
    }

    /**
     * Sanitize the string by removing any script tags.
     *
     * Usage: '<index>' => 'sanitize_string'
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_sanitize_string($value, $params = null)
    {
        return filter_var($value, FILTER_SANITIZE_STRING);
    }

    /**
     * Sanitize the string by urlencoding characters.
     *
     * Usage: '<index>' => 'urlencode'
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_urlencode($value, $params = null)
    {
        return filter_var($value, FILTER_SANITIZE_ENCODED);
    }

    /**
     * Sanitize the string by converting HTML characters to their HTML entities.
     *
     * Usage: '<index>' => 'htmlencode'
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_htmlencode($value, $params = null)
    {
        return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    /**
     * Sanitize the string by removing illegal characters from emails.
     *
     * Usage: '<index>' => 'sanitize_email'
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_sanitize_email($value, $params = null)
    {
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize the string by removing illegal characters from numbers.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_sanitize_numbers($value, $params = null)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize the string by removing illegal characters from float numbers.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_sanitize_decimals($value, $params = null)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Filter out all HTML tags except the defined basic tags.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_basic_tags($value, $params = null)
    {
        return strip_tags($value, self::$basic_tags);
    }

    /**
     * Convert the provided numeric value to a whole number.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_whole_number($value, $params = null)
    {
        return intval($value);
    }

    /**
     * Convert MS Word special characters to web safe characters.
     * [“, ”, ‘, ’, –, …] => [", ", ', ', -, ...]
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_ms_word_characters($value, $params = null)
    {
        $word_open_double  = '“';
        $word_close_double = '”';
        $web_safe_double   = '"';

        $value = str_replace(array($word_open_double, $word_close_double), $web_safe_double, $value);

        $word_open_single  = '‘';
        $word_close_single = '’';
        $web_safe_single   = "'";

        $value = str_replace(array($word_open_single, $word_close_single), $web_safe_single, $value);

        $word_em     = '–';
        $web_safe_em = '-';

        $value = str_replace($word_em, $web_safe_em, $value);

        $word_ellipsis = '…';
        $web_ellipsis  = '...';

        $value = str_replace($word_ellipsis, $web_ellipsis, $value);

        return $value;
    }

    /**
     * Converts to lowercase.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_lower_case($value, $params = null)
    {
        return strtolower($value);
    }

    /**
     * Converts to uppercase.
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_upper_case($value, $params = null)
    {
        return strtoupper($value);
    }

    /**
     * Converts value to url-web-slugs. 
     * 
     * Credit: 
     * https://stackoverflow.com/questions/40641973/php-to-convert-string-to-slug
     * http://cubiq.org/the-perfect-php-clean-url-generator
     *
     * @param string $value
     * @param array  $params
     *
     * @return string
     */
    protected function filter_slug($value, $params = null)
    {
        $delimiter = '-';
        $slug = strtolower(trim(preg_replace('/[\s-]+/', $delimiter, preg_replace('/[^A-Za-z0-9-]+/', $delimiter, preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $str))))), $delimiter));
        return $slug;
    }

    // ** ------------------------- Validators ------------------------------------ ** //


    /**
     * Verify that a value is contained within the pre-defined value set.
     * OUTPUT: will NOT show the list of values.
     *
     * Usage: '<index>' => 'contains_list,value;value;value'
     *
     * @param string $field
     * @param array  $input
     *
     * @return mixed
     */
    protected function validate_contains_list($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $param = trim(strtolower($param));

        $value = trim(strtolower($input[$field]));

        $tmp_param = explode(';', $param);

        // consider: in_array(strtolower($value), array_map('strtolower', $param)

        if (in_array($value, $tmp_param)) { // valid, return nothing
            return;
        } else {
            return array(
                'field' => $field,
                'value' => $value,
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

  
    /**
     * Check if the specified key is present and not empty.
     *
     * Usage: '<index>' => 'required'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_required($field, $input, $param = null)
    {
        if (isset($input[$field]) && ($input[$field] === false || $input[$field] === 0 || $input[$field] === 0.0 || $input[$field] === '0' || !empty($input[$field]))) {
            return;
        }

        return array(
            'field' => $field,
            'value' => null,
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided email is valid.
     *
     * Usage: '<index>' => 'valid_email'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_valid_email($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!filter_var($input[$field], FILTER_VALIDATE_EMAIL)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value length is less or equal to a specific value.
     *
     * Usage: '<index>' => 'max_len,240'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_max_len($field, $input, $param = null)
    {
        if (!isset($input[$field])) {
            return;
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($input[$field]) <= (int) $param) {
                return;
            }
        } else {
            if (strlen($input[$field]) <= (int) $param) {
                return;
            }
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided value length is more or equal to a specific value.
     *
     * Usage: '<index>' => 'min_len,4'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_min_len($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($input[$field]) >= (int) $param) {
                return;
            }
        } else {
            if (strlen($input[$field]) >= (int) $param) {
                return;
            }
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided value length matches a specific value.
     *
     * Usage: '<index>' => 'exact_len,5'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_exact_len($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($input[$field]) == (int) $param) {
                return;
            }
        } else {
            if (strlen($input[$field]) == (int) $param) {
                return;
            }
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided value contains only alpha characters.
     *
     * Usage: '<index>' => 'alpha'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_alpha($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!preg_match('/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i', $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value contains only alpha-numeric characters.
     *
     * Usage: '<index>' => 'alpha_numeric'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_alpha_numeric($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!preg_match('/^([a-z0-9ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ])+$/i', $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value contains only alpha characters with dashed and underscores.
     *
     * Usage: '<index>' => 'alpha_dash'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_alpha_dash($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!preg_match('/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ_-])+$/i', $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }


    /**
     * Determine if the provided value contains only alpha characters with spaces.
     *
     * Usage: '<index>' => 'alpha_space'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_alpha_space($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!preg_match("/^([a-zÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖßÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\s])+$/i", $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a valid number or numeric string.
     *
     * Usage: '<index>' => 'numeric'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_numeric($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!is_digit($input[$field])) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a valid integer.
     *
     * Usage: '<index>' => 'integer'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_integer($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (filter_var($input[$field], FILTER_VALIDATE_INT) === false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a PHP accepted boolean.
     *
     * Usage: '<index>' => 'boolean'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_valid_boolean($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field]) && $input[$field] !== 0) {
            return;
        }

        $booleans = array('1','true',true,1,'0','false',false,0,'yes','no','on','off');
        if (in_array($input[$field], $booleans, true )) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided value is a valid float.
     *
     * Usage: '<index>' => 'float'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_decimal($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (filter_var($input[$field], FILTER_VALIDATE_FLOAT) === false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a valid URL.
     *
     * Usage: '<index>' => 'valid_url'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_valid_url($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!filter_var($input[$field], FILTER_VALIDATE_URL)) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if a URL exists & is accessible.
     *
     * Usage: '<index>' => 'url_exists'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_url_exists($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $url = parse_url(strtolower($input[$field]));

        if (isset($url['host'])) {
            $url = $url['host'];
        }

        if (function_exists('checkdnsrr')  && function_exists('idn_to_ascii')) {
            if (checkdnsrr(idn_to_ascii($url), 'A') === false) {
                return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule' => __FUNCTION__,
                    'param' => $param,
                );
            }
        } else {
            if (gethostbyname($url) == $url) {
                return array(
                    'field' => $field,
                    'value' => $input[$field],
                    'rule' => __FUNCTION__,
                    'param' => $param,
                );
            }
        }
    }

    /**
     * Determine if the provided value is a valid IP address.
     *
     * Usage: '<index>' => 'valid_ip'
     *
     * @param string $field
     * @param array  $input
     *
     * @return mixed
     */
    protected function validate_valid_ip($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!filter_var($input[$field], FILTER_VALIDATE_IP) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }
	
    /**
     * Determine if the input is a valid credit card number.
     *
     * See: http://stackoverflow.com/questions/174730/what-is-the-best-way-to-validate-a-credit-card-in-php
     * Usage: '<index>' => 'valid_cc'
     *
     * @param string $field
     * @param array  $input
     *
     * @return mixed
     */
    protected function validate_valid_credit_card($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $number = preg_replace('/\D/', '', $input[$field]);

        if (function_exists('mb_strlen')) {
            $number_length = mb_strlen($number);
        } else {
            $number_length = strlen($number);
        }


        /**
         * Bail out if $number_length is 0. 
         * This can be the case if a user has entered only alphabets
         * 
         * @since 1.5
         */
        if( $number_length == 0 ) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }


        $parity = $number_length % 2;

        $total = 0;

        for ($i = 0; $i < $number_length; ++$i) {
            $digit = $number[$i];

            if ($i % 2 == $parity) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $total += $digit;
        }

        if ($total % 10 == 0) {
            return; // Valid
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the input is a valid human name [Credits to http://github.com/ben-s].
     *
     * See: https://github.com/Wixel/GUMP/issues/5
     * Usage: '<index>' => 'valid_name'
     *
     * @param string $field
     * @param array  $input
     *
     * @return mixed
     */
    protected function validate_valid_name($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!preg_match("/^([a-z \p{L} '-])+$/i", $input[$field]) !== false) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

  
    /**
     * Determine if the provided input is a valid date (ISO 8601)
     * or specify a custom format.
     *
     * Usage: '<index>' => 'date'
     *
     * @param string $field
     * @param string $input date ('Y-m-d') or datetime ('Y-m-d H:i:s')
     * @param string $param Custom date format
     *
     * @return mixed
     */
    protected function validate_valid_date($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $param = empty($param) ? 'Y-m-d': $param;

         $d = DateTime::createFromFormat($param, $input[$field]);
        if($d && $d->format($format) == $input[$field]){
            return array(
            'field'      => $field,
            'value'      => $input[$field],
            'rule'       => __FUNCTION__,
            'param' => $param,
             );
        }

        // Default

        /*
        if (!$param)
        {

            $cdate1 = date('Y-m-d', strtotime($input[$field]));
            $cdate2 = date('Y-m-d H:i:s', strtotime($input[$field]));

            if ($cdate1 != $input[$field] && $cdate2 != $input[$field])
            {
                return array(
                    'field'      => $field,
                    'value'      => $input[$field],
                    'rule'       => __FUNCTION__,
                    'param' => $param,
                );
            }
        } else {
            $date = \DateTime::createFromFormat($param, $input[$field]);

            if ($date === false || $input[$field] != date($param, $date->getTimestamp()))
            {
                return array(
                    'field'      => $field,
                    'value'      => $input[$field],
                    'rule'       => __FUNCTION__,
                    'param' => $param,
                );
            }
        }
        */
    }

    /**
     * Determine if the provided input meets age requirement (ISO 8601).
     *
     * Usage: '<index>' => 'min_age,13'
     *
     * @param string $field
     * @param string $input date ('Y-m-d') or datetime ('Y-m-d H:i:s')
     * @param string $param int
     *
     * @return mixed
     */
    protected function validate_min_age($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $cdate1 = new DateTime(date('Y-m-d', strtotime($input[$field])));
        $today = new DateTime(date('d-m-Y'));

        $interval = $cdate1->diff($today);
        $age = $interval->y;

        if ($age <= $param) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided numeric value is lower or equal to a specific value.
     *
     * Usage: '<index>' => 'max_numeric,50'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     *
     * @return mixed
     */
    protected function validate_max_numeric($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (is_digit($input[$field]) && is_digit($param) && ($input[$field] <= $param)) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided numeric value is higher or equal to a specific value.
     *
     * Usage: '<index>' => 'min_numeric,1'
     *
     * @param string $field
     * @param array  $input
     * @param null   $param
     * @return mixed
     */
    protected function validate_min_numeric($field, $input, $param = null)
    {
        if (!isset($input[$field]) || $input[$field] === '') {
            return;
        }

        if (is_digit($input[$field]) && is_digit($param) && ($input[$field] >= $param)) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

    /**
     * Determine if the provided value starts with param.
     *
     * Usage: '<index>' => 'starts,Z'
     *
     * @param string $field
     * @param array  $input
     *
     * @return mixed
     */
    protected function validate_starts($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (strpos($input[$field], $param) !== 0) {
            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

      /**
       * Checks if a file was uploaded.
       *
       * Usage: '<index>' => 'required_file'
       *
       * @param  string $field
       * @param  array $input
       *
       * @return mixed
       */
      protected function validate_required_file($field, $input, $param = null)
      {
		
          if (!isset($input[$field])) {
              return;
          }
		  
		  if($input[$field]['error']!== 0 AND $input[$field]['error']!== 4 ){
			  $no = $input[$field]['error'];
			  $this->set_error_message('validate_required_file', self::$phpFileUploadErrors[$no]);
			  
			   return array(
				  'field' => $field,
				  'value' => $input[$field],
				  'rule' => __FUNCTION__,
				  'param' => $param,
				);
		  }
		  
          if (is_array($input[$field]) && $input[$field]['error'] !== 4) {	  
              return;
          }
		  
          return array(
              'field' => $field,
              'value' => $input[$field],
              'rule' => __FUNCTION__,
              'param' => $param,
          );
      }

    /**
     * Check the uploaded file for extension for now
     * checks only the ext should add mime type check.
     *
     * Usage: '<index>' => 'extensions,png;jpg;gif
     *
     * @param string $field
     * @param array  $input
     *
     * @return mixed
     */
    protected function validate_extensions($field, $input, $param = null)
    {
        if (!isset($input[$field])) {
            return;
        }

        if (is_array($input[$field]) && $input[$field]['error'] !== 4) {
            $param = trim(strtolower($param));
            $allowed_extensions = explode(';', $param);

            $path_info = pathinfo($input[$field]['name']);
            $extension = isset($path_info['extension']) ? $path_info['extension'] : false;

            if ($extension && in_array(strtolower($extension), $allowed_extensions)) {
                return;
            }

            return array(
                'field' => $field,
                'value' => $input[$field],
                'rule' => __FUNCTION__,
                'param' => $param,
            );
        }
    }

	 /**
     * Check the uploaded file for file size
     * checks only the ext should add mime type check.
     *
     * Usage: '<index>' => 'valid_filesize,2097152 bytes (2 MB)
     *
     * @param string $field
     * @param array  $input
     * @param array  $param file size limit (,)
     * @return mixed
     */
    protected function validate_valid_filesize($field, $input, $param = null)
    {
        if (!isset($input[$field])) {
            return;
        }

        if (is_array($input[$field]) && $input[$field]['error'] !== 4) {
			
            if($input[$field]['size'] > $param OR $input[$field]['error']== 1){
				return array(
					'field' => $field,
					'value' => $input[$field],
					'rule' => __FUNCTION__,
					'param' => $param,
				);
			}
        }
    }
	
	/**
     * Check the uploaded file for general error
     * checks only the ext should add mime type check.
     *
     * Usage: '<index>' => 'valid_file'
     *
     * @param string $field
     * @param array  $input
     * @param array  $param 
     * @return mixed
     */
    protected function validate_is_valid_file($field, $input, $param = null)
    {
        if (!isset($input[$field])) {
            return;
        }

        if (is_array($input[$field]) AND $input[$field]['error'] !== 4 AND $input[$field]['error'] !== 0) {
			$no =  $input[$field]['error'];
			$this->set_error_message('validate_is_valid_file', self::$phpFileUploadErrors[$no]);
			  
				return array(
					'field' => $field,
					'value' => $input[$field],
					'rule' => __FUNCTION__,
					'param' => $param,
				);
			}
    }
  
	
    /**
     * Determine if the provided field value equals current field value.
     *
     *
     * Usage: '<index>' => 'equalsfield,Z'
     *
     * @param string $field
     * @param string $input
     * @param string $param field to compare with
     *
     * @return mixed
     */
    protected function validate_equalsfield($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if ($input[$field] == $input[$param]) {
            return;
        }

        return array(
            'field' => $field,
            'value' => $input[$field],
            'rule' => __FUNCTION__,
            'param' => $param,
        );
    }

   /**
     * Trims whitespace only when the value is a scalar.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function trimScalar($value)
    {
        if (is_scalar($value)) {
            $value = trim($value);
        }

        return $value;
    }

    /**
     * Determine if the provided value is a valid phone number.
     *
     * Usage: '<index>' => 'phone_number'
     *
     * @param string $field
     * @param array  $input
     *
     * @return mixed
     *
     * Examples:
     *
     *  555-555-5555: valid
     *  555.555.5555: valid
     *  5555425555: valid
     *  555 555 5555: valid
     *  1(519) 555-4444: valid
     *  1 (519) 555-4422: valid
     *  1-555-555-5555: valid
     *  1-(555)-555-5555: valid
	 *	+63906556692: valid
	 *  +(123) 456-7890:valid
	 *  +(123)-456-7890:valid
     */
    protected function validate_phone_number($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

// ^[+]?[\s./0-9]*[(]?[0-9]{1,4}[)]?[-\s./0-9]*$/i - from GUMP class
// ^[\+]?(\d[\s-\.]?)?[\(\[\s-\.]{0,2}?\d{3}[\)\]\s-\.]{0,2}?\d{3}[\s-\.]?\d{4,5}$  - modify

        $regex = '/^[\+]?(\d[\s-\.]?)?[\(\[\s-\.]{0,2}?\d{3}[\)\]\s-\.]{0,2}?\d{3}[\s-\.]?\d{4,5}$/i';
        if (!preg_match($regex, $input[$field])) {
            return array(
              'field' => $field,
              'value' => $input[$field],
              'rule' => __FUNCTION__,
              'param' => $param,
            );
        }
    }

    /**
     * Determine if the provided value is a valid number without leading zero.
     *
     * Usage: '<index>' => 'is_natural_no_zero'
     *
     * @param string $field
     * @param array  $input
     *
     * @return mixed
     */
    protected function validate_is_natural_no_zero($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $regex = '/^[1-9][0-9]*$/i';
        if (!preg_match($regex, $input[$field])) {
            return array(
              'field' => $field,
              'value' => $input[$field],
              'rule' => __FUNCTION__,
              'param' => $param,
            );
        }
    }
	
	/**
     * Determine if the provided value is a positve number.
     *
     * Usage: '<index>' => 'is_natural'
     *
     * @param string $field
     * @param array  $input
     *
     * @return mixed
     */
    protected function validate_is_natural($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $regex = '/^[0-9]+$/i';
        if (!preg_match($regex, $input[$field])) {
            return array(
              'field' => $field,
              'value' => $input[$field],
              'rule' => __FUNCTION__,
              'param' => $param,
            );
        }
    }


	 /**
     * Determine if the provided input is a greater than a given date (param)
     * or specify a custom format.
     *
     * Usage: '<index>' => 'greater_than_date'
     *
     * @param string $field
     * @param string $input date ('Y-m-d') or datetime ('Y-m-d H:i:s')
     * @param string $param another date use to compare - that is less than input date
     *
     * @return mixed
     */
    protected function validate_greater_than_date($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!empty($param)) {
			$input_date1 = date('Y-m-d', strtotime($input[$field]));
            $input_date2 = date('Y-m-d H:i:s', strtotime($input[$field]));
			$cdate1 = date('Y-m-d', strtotime($param));
            $cdate2 = date('Y-m-d H:i:s', strtotime($param));
			
			if ($cdate1 > $input_date1 && $cdate2 > $input_date2)
				{
					return array(
						'field'      => $field,
						'value'      => $input[$field],
						'rule'       => __FUNCTION__,
						'param' => $param,
					);
			}
			
        }
    }


	 /**
     * Determine if the provided input is a less than a given date (param)
     * or specify a custom format.
     *
     * Usage: '<index>' => 'greater_than_date'
     *
     * @param string $field
     * @param string $input date ('Y-m-d') or datetime ('Y-m-d H:i:s')
     * @param string $param another date use to compare - that is greater than input date
     *
     * @return mixed
     */
    protected function validate_less_than_date($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!empty($param)) {
			$input_date1 = date('Y-m-d', strtotime($input[$field]));
            $input_date2 = date('Y-m-d H:i:s', strtotime($input[$field]));
			$cdate1 = date('Y-m-d', strtotime($param));
            $cdate2 = date('Y-m-d H:i:s', strtotime($param));
			
			if ($cdate1 < $input_date1 && $cdate2 < $input_date2)
				{
					return array(
						'field'      => $field,
						'value'      => $input[$field],
						'rule'       => __FUNCTION__,
						'param' => $param,
					);
			}
			
        }
    }

	 /**
     * Determine if the provided input is a greater than or equal a given date (param)
     * or specify a custom format.
     *
     * Usage: '<index>' => 'greater_than_date'
     *
     * @param string $field
     * @param string $input date ('Y-m-d') or datetime ('Y-m-d H:i:s')
     * @param string $param another date use to compare - that is less than or equal input date
     *
     * @return mixed
     */
    protected function validate_greater_than_or_equal_date($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!empty($param)) {
			$input_date1 = date('Y-m-d', strtotime($input[$field]));
            $input_date2 = date('Y-m-d H:i:s', strtotime($input[$field]));
			$cdate1 = date('Y-m-d', strtotime($param));
            $cdate2 = date('Y-m-d H:i:s', strtotime($param));
			
			if ($cdate1 > $input_date1 && $cdate2 > $input_date2)
				{
					return array(
						'field'      => $field,
						'value'      => $input[$field],
						'rule'       => __FUNCTION__,
						'param' => $param,
					);
			}else if ($cdate1 != $input_date1 && $cdate2 != $input_date2)
				{
					return array(
						'field'      => $field,
						'value'      => $input[$field],
						'rule'       => __FUNCTION__,
						'param' => $param,
					);
			}
		}
    }


	 /**
     * Determine if the provided input is a less than a given date (param)
     * or specify a custom format.
     *
     * Usage: '<index>' => 'greater_than_date'
     *
     * @param string $field
     * @param string $input date ('Y-m-d') or datetime ('Y-m-d H:i:s')
     * @param string $param another date use to compare - that is greater than input date
     *
     * @return mixed
     */
    protected function validate_less_than_or_equal_date($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        if (!empty($param)) {
			$input_date1 = date('Y-m-d', strtotime($input[$field]));
            $input_date2 = date('Y-m-d H:i:s', strtotime($input[$field]));
			$cdate1 = date('Y-m-d', strtotime($param));
            $cdate2 = date('Y-m-d H:i:s', strtotime($param));
			
			if ($cdate1 < $input_date1 && $cdate2 < $input_date2)
			{
					return array(
						'field'      => $field,
						'value'      => $input[$field],
						'rule'       => __FUNCTION__,
						'param' => $param,
					);
			}else if ($cdate1 != $input_date1 && $cdate2 != $input_date2)
				{
					return array(
						'field'      => $field,
						'value'      => $input[$field],
						'rule'       => __FUNCTION__,
						'param' => $param,
					);
			}
			
        }
	}
    

    /**
     * Custom regex validator.
     *
     * Usage: '<index>' => 'regex,/your-regex-expression/'
     *
     * @param string $field
     * @param array  $input
     *
     * @return mixed
     */
    protected function validate_regex($field, $input, $param = null)
    {
        if (!isset($input[$field]) || empty($input[$field])) {
            return;
        }

        $regex = $param;
        if (!preg_match($regex, $input[$field])) {
            return array(
              'field' => $field,
              'value' => $input[$field],
              'rule' => __FUNCTION__,
              'param' => $param,
            );
        }
    }

    
}
