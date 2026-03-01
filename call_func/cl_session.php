<?php
/**
 * Session - a class that endeavors to make using better sessions easier.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 2.1 of the License only.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package   Asdfdotdev
 * @author    Chris Carlevato <hello@asdf.dev>
 * @copyright 2015-2020 Chris Carlevato
 * @license   http://www.gnu.org/licenses/lgpl-2.1.html
 * @version   0.5.0
 * @link    
 * edited based on need and update for security 
 */

//namespace Asdfdotdev;

/**
 * Class Session
 *
 * @package Asdfdotdev
 */
class Session
{
    /** @var string Key for session value array */
    protected $valuesKey = 'asdfdotdev.session';

    /** @var string Name of the session */
    protected $name;

    /** @var string Path the session cookie is available on  */
    protected $path;

    /** @var string Domain the cookie is available on */
    protected $domain;

    /** @var boolean Only transmit the session cookie over https */
    protected $secure;

    /** @var string Name of hashing algorithm to use for hashed values */
    protected $hash;

    /** @var int Length of Session ID string */
    protected $idLength;

    /** @var int Number of bits in encoded Session ID characters */
    protected $idBits;

    /** @var boolean Generate fake PHPSESSID cookie */
    protected $decoy;

    /** @var int Minimum time in seconds to regenerate session id */
    protected $timeMin;

    /** @var int Maximum time in seconds to regenerate session id */
    protected $timeMax;

    /** @var bool */
    protected $debug;

	protected $gc_probability	= 1;			//Chance (in 100) that old sessions will 
	
	protected $expiration	= 7200;				//The session expires after 2 hours of non-use
	
	protected $session_table	= 'sessions_table';		//If using a DB, what is the table name?
    /**
     * Config settings can include:
     * - name:    Name of the session                         (Default: asdfdotdev)
     * - path:    Server path the cookie is available on      (Default: /)
     * - domain:  Domain the session cookie is available on   (Default: localhost)
     * - secure:  Only transmit the cookie over https         (Default: false)
     * - hash:    Name of algorithm to use for hashed values  (Default: sha256)
     * - decoy:   Generate fake PHPSESSID cookie              (Default: true)
     * - min:     Min time in seconds to regenerate session   (Default: 60)
     * - max:     Max time in seconds to regenerate session   (Default: 600)
     *
     * @param array $config Session Configuration
     *
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $settings = array_merge(
            [
                'name' => 'asdfdotdev',
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'bits' => 4,
                'length' => 32,
                'hash' => 'sha256',
                'decoy' => true,
                'min' => 60,
                'max' => 600,
                'debug' => false,
            ],
            $config
        );

        $this->setName($settings['name']);
        $this->setPath($settings['path']);
        $this->setDomain($settings['domain']);
        $this->setSecure($settings['secure']);
        $this->setIdBits($settings['bits']);
        $this->setIdLength($settings['length']);
        $this->setHash($settings['hash']);
        $this->decoy = $settings['decoy'];
        $this->timeMin = $settings['min'];
        $this->timeMax = $settings['max'];
        $this->debug = $settings['debug'];
		
        $this->verifySettings();
		
        return $this;
    }

    /**
     * Set session name, this is also used as the name of the session cookie.
     *
     * @param string $name Session Name
     */
    protected function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get session name.
     *
     * @return string Session Name
     */
    protected function getName()
    {
        return $this->name;
    }

    /**
     * Set path on the domain where the cookies will work
     * Use a single slash (default) for all paths on the domain.
     *
     * @param  string $path Cookie Path
     * @return void
     */
    protected function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * Get cookie path.
     *
     * @return string Cookie Path
     */
    protected function getPath()
    {
        return $this->path;
    }

    /**
     * Set cookie domain. To make cookie visible on all subdomains prefixed with a dot
     *
     * @param string $domain Cookie Domain
     */
    protected function setDomain(string $domain = '')
    {
        $domain = ($domain == '') ? $_SERVER['SERVER_NAME'] : $domain;
        $this->domain = $domain;
    }

    /**
     * Get session cookie domain.
     *
     * @return string Cookie Domain
     */
    protected function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set cookie secure status. If TRUE cookie will only be sent over secure connections.
     *
     * @param bool $secure Cookie Secure Status
     *
     * @return void
     */
    protected function setSecure(bool $secure = false)
    {
		// Set SSL level 
		$https = isset($secure) ? $secure : isset($_SERVER['HTTPS']);
        $this->secure = $https;
    }

    /**
     * Get cookie secure status.
     *
     * @return bool Cookie Secure Status
     */
    protected function getSecure()
    {
        return $this->secure;
    }

    /**
     * Set cookie id hash method.
     *
     * @param int/string $hash 0 = MD5, 1 = SHA1, or supported hash name (Default: 1)
     *
     * @throws \Exception
     */
    protected function setHash(string $hash = '')
    {
        if (in_array($hash, hash_algos())) {
            $this->hash = $hash;
        } else {
            $this->error(
                'Server does not support selected hash algorithm selected.'
            );
        }
    }

    /**
     * Get session hash setting.
     *
     * @return int Cookie Hash Setting
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set length of session id string.
     *
     * @param int $length
     * @throws \Exception
     *
     * @return void
     */
    protected function setIdLength(int $length)
    {
        if (in_array($length, range(22, 256))) {
            $this->idLength = $length;
        } else {
            $this->error(
                'Session ID length invalid. Length must be between 22 to 256.'
            );
        }
    }

    /**
     * Get session id length.
     *
     * @return int
     */
    protected function getIdLength()
    {
        return $this->idLength;
    }

    /**
     * Set session id bits.
     *
     * @param int $bits
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function setIdBits(int $bits)
    {
        if (in_array($bits, range(4, 6))) {
            $this->idBits = $bits;
        } else {
            $this->error(
                'Session ID bits per character invalid. Options are 4, 5, or 6.'
            );
        }
    }

    /**
     * Get session id bits.
     *
     * @return int
     */
    protected function getIdBits()
    {
        return $this->idBits;
    }

    /**
     * Create decoy cookie if it hasn't been set.
     *
     * This cookie intentionally exhibits signs of a week session cookie so that it
     * looks attractive to would be scoundrels. These vulnerabilities include:
     * - PHPSESSID name
     * - MD5 hash value
     * - not HTTPOnly
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function generateDecoyCookie()
    {
        $has_decoy = isset($_COOKIE['PHPSESSID']);

        if ($this->decoy && !$has_decoy) {
            $this->setValue('decoy_value', md5(mt_rand()));
            setcookie(
                'PHPSESSID',
                $this->getValue('decoy_value'),
                $this->expiration + time(),
                $this->getPath(),
                $this->getDomain(),
                $this->getSecure(),
                0
            );
        }
    }

    /**
     * Generate sha256 fingerprint hash from current settings.
     *
     * @return string
     */
    protected function generateFingerprint()
    {
		/*
		if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
			$remoteIpHeader = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}else if (!empty($_SERVER["HTTP_CLIENT_IP"])){
			$remoteIpHeader = $_SERVER["HTTP_CLIENT_IP"];
		}else{
			$remoteIpHeader = $_SERVER["REMOTE_ADDR"];
		}	*/

        return hash(
            'sha256',
            $_SERVER['HTTP_USER_AGENT'].session_id() 
        );
		
		//$_SERVER['REMOTE_ADDR'] - remove due to issue encounter in some user
    }

    /**
     * Create session fingerprint from user agent, ip and session id in an attempt to discourage session hijacking.
     *
     * @return void
     */
    protected function setFingerprint()
    {
        $this->setValue(
            'fingerprint',
            $this->generateFingerprint()
        );
    }

    /**
     * Compare current user agent, ip and session id against stored session fingerprint
     * If compared value doesn't match stored value session end the session.
     *
     * @throws \Exception
     *
     * @return bool Valid fingerprint
     */
    protected function validateFingerprint()
    {
        $print = $this->getValue('fingerprint');
        $valid = $this->generateFingerprint();

        if (!isset($print)) {
            $this->setFingerprint();
        } elseif ($print != $valid) {
            $this->end();
            return false;
        }

        return true;
    }

    /**
     * Reset session lifespan time using random value between timeMin and timeMax.
     *
     * @return void
     */
    protected function resetLifespan()
    {
        $this->setValue(
            'lifespan',
            date('U') + mt_rand($this->timeMin, $this->timeMax)
        );
    }

    /**
     * Compare session lifespan time to current time
     * If current time is beyond session lifespan regenerate session id.
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function checkLifespan()
    {
        if ($this->getValue('lifespan') == '') {
            $this->resetLifespan();
        } elseif ($this->getValue('lifespan') < date('U')) {
            $this->regenerate();
        }
    }

    /**
     * Start Session.
     *
     * @param bool $restart Force session id regeneration
     *
     * @throws \Exception
     *
     * @return void
     */
    public function start($restart = false)
    {
        if ($restart) {
            $this->regenerateId();
        } else {
            $this->configureSystemSessionSettings();
        }

        $this->prepareSession();

        if ($restart) {
            $this->setFingerprint();
            $this->resetLifespan();
        }

        if ($this->validateFingerprint()) {
            $this->generateDecoyCookie();
            $this->checkLifespan();
            $this->setValue('session_loaded', date('U'));
            $this->setValue(
                'ttl',
                ($this->getValue('lifespan') - $this->getValue('session_loaded'))
            );
        }
    }

    /**
     * Generate generate system session, maybe scaffold value array
     *
     * @return void
     */
    private function prepareSession()
    {
        session_set_cookie_params(
            $this->expiration,
            $this->getPath(),
            $this->getDomain(),
            $this->getSecure(),
            true
        );

        session_name($this->getName());
        session_start();

        if (!isset($_SESSION[$this->valuesKey])) {
            $_SESSION[$this->valuesKey] = [];
        }
    }

    /**
     * Get session variable value.
     *
     * @param string $key Name of the session variable value to retrieve
     *
     * @return mixed Value of the variable requested
     */
    public function getValue($key)
    {
        if (!isset($_SESSION[$this->valuesKey][$key])) {
            return null;
        }

        return $_SESSION[$this->valuesKey][$key];
    }

    /**
     * Create session value if not present, otherwise the value is updated.
     *
     * @param string $key   Name of the session variable to create/update
     * @param mixed  $value Value of the session variable to create/update
     * @param bool   $hash  false = store $value in session array as plain text,
     *                      true = store hash of $value in session array
     *
     * @return void
     */
    public function setValue($key, $value, $hash = false)
    {
        if ($hash) {
            $value = hash($this->getHash(), $value);
        }

        $_SESSION[$this->valuesKey][$key] = $value;
    }

    /**
     * Append to session value.
     * Note: Append behavior varies by current value type:
     *       - Array: passed value added to array (array_merge)
     *       - String: passed value added to the end of the string (concatenation)
     *       - Other: passed value replaces the saved value (replace)
     *
     * @param string $key Name of the session variable to create/update
     * @param string $value String to append to the end of the current value
     *
     * @throws \Exception
     *
     * @return void
     */
    public function appValue($key, $value)
    {
        $currentValue = $this->getValue($key);

        if (isset($currentValue)) {
            if (is_array($currentValue)) {
                $updatedValue = array_merge($currentValue, $value);
            } elseif (is_string($currentValue)) {
                $updatedValue = $currentValue . $value;
            } else {
                $updatedValue = $value;
            }
        } else {
            $updatedValue = $value;
        }

        $this->setValue($key, $updatedValue);
    }

    /**
     * Increment session value.
     *
     * @param string $key Name of the session variable to create/increment
     * @param int $value Amount to add to the current value
     *
     * @throws \Exception
     *
     * @return void
     */
    public function incValue($key, $value)
    {
        if (!is_numeric($value)) {
            $this->error(
                sprintf(
                    'Only numeric values can be passed to %s',
                    __METHOD__
                )
            );
        }

        $currentValue = $this->getValue($key);

        if (isset($currentValue)) {
            $updatedValue = $currentValue + $value;
        } else {
            $updatedValue = $value;
        }

        $this->setValue($key, $updatedValue);
    }
	
	/**add session_write_close for some ajaxx call **/
	public function session_close(){
		if (session_status() == PHP_SESSION_ACTIVE) {
		   session_write_close();
		}
	}

    /**
     * Drop session value.
     *
     * @param string $key Name of the session variable to drop
     *
     * @return void
     */
    public function dropValue($key)
    {
        unset($_SESSION[$this->valuesKey][$key]);
    }

    /**
     * Restart session with reset flag true.
     *
     * @throws \Exception
     *
     * @return void
     */
    public function regenerate()
    {
        $this->start(true);
    }

    /**
     * Regenerate session id.
     */
    private function regenerateId()
    {
        session_regenerate_id(true);
        $new_id = session_id();
        session_write_close();
        session_id($new_id);
    }

    /**
     * Update system session values.
     *
     * @see https://www.php.net/manual/en/session.configuration.php
     */
    private function configureSystemSessionSettings()
    {
        if (function_exists('ini_set')) {
            ini_set('session.sid_length', $this->getIdLength());
            ini_set('session.sid_bits_per_character', $this->getIdBits());
            ini_set('session.cookie_secure', $this->getSecure());
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
			ini_set('session.gc_probability', $this->gc_probability);
			ini_set('session.gc_divisor', 1000);
			ini_set('session.gc_maxlifetime', $this->expiration);
        }
    }

    /**
     * End session.
     *
     * @return void
     */
    public function end()
    {
        session_unset();
        session_destroy();
    }

    /**
     * Dump session contents for debugging or testing.
     *
     * @param int $format 0 = string
     *                    1 = array
     *                    2 = json encoded string
     *
     * @return mixed
     */
    public function dump($format = 1)
    {
        switch ($format) {
            // string
            case 1:
                return print_r($_SESSION, true);
                break;
            // array
            case 2:
                return $_SESSION;
                break;
            // json string
            case 3:
            default:
                return json_encode($_SESSION);
                break;
        }
    }

    /**
     * Prevents incorrect configuration of timeMin/time/Max lifespan values.
     *
     * @throws \Exception
     *
     * @return void
     */
    private function validateSessionLifespan()
    {
        if ($this->timeMin > $this->timeMax) {
            $this->timeMin = $this->timeMin + $this->timeMax;
            $this->timeMax = $this->timeMin - $this->timeMax;
            $this->timeMin -= $this->timeMax;
        }
    }

    /**
     * In the event timezone is unset, set it if possible.
     *
     * @throws \Exception
     *
     * @return void
     */
    private function validateSystemTimezone()
    {
        if (function_exists('ini_get') && ini_get('date.timezone') == '') {
            date_default_timezone_set('UTC');
        }
    }

    /**
     * Confirm session path is writable.
     *
     * @throws \Exception
     *
     * @return void
     */
    private function validateSessionDir()
    {
        if (!is_writable(session_save_path())) {
            $this->error(
                'Session directory is not writable.'
            );
        }
    }

    /**
     * Confirm that request domain matches cookie domain.
     *
     * @throws \Exception
     *
     * @return void
     */
    private function validateSessionDomain()
    {
        if ($_SERVER['HTTP_HOST'] != $this->getDomain()) {
            $this->error(
                sprintf(
                    'Session cookie domain (%s) and request domain (%s) mismatch.',
                    $_SERVER['HTTP_HOST'],
                    $this->getDomain()
                )
            );
        }
    }

    /**
     * Confirm PHP version is at least 7.2.0
     *
     * @throws \Exception
     *
     * @return void
     */
    private function validatePHPVersion()
    {
        if (version_compare(phpversion(), '7.2.0', '<')) {
            $this->error(
                'PHP v7.2.0 or newer is required.'
            );
        }
    }

    /**
     * Throw exception on error.
     *
     * @param string $response Explain to them what they screwed up
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function error($response)
    {
        throw new \Exception($response, null, null);
    }

    /**
     * Validate various requirements
     * @throws \Exception
     */
    protected function verifySettings()
    {
        $this->validateSystemTimezone();
        $this->validateSessionLifespan();

        if ($this->debug) {
            $this->validatePHPVersion();
            $this->validateSessionDir();
            $this->validateSessionDomain();
        }
    }
}

/**

 * php-csrf v1.0.2
 * Modify based on needs
 
 * Single PHP library file for protection over Cross-Site Request Forgery
 * Easily generate and manage CSRF tokens in groups.


 * Usage:
 * 		// Load or Start a new list of tokens
 * 		$csrf_tokens = new CSRF(
 * 			<modifier for the session variable and the form input name>,
 * 			<default time before the token expire, in seconds>
 * 		);
 * 		// Generate an input for a form with a token
 * 		// Tokens on the list are binded on a group so that
 * 		// they can only be matched on that group
 * 		// You can use as a group name the form name
 * 		echo $csrf_tokens->input(<name of the group>);
 */
class CSRF {

	private $name;
	private $hashes;
	private $hashTime2Live;
	private $hashSize;
	private $inputName;
    private $session_class;

	/**
	 * Initialize a CSRF instance
	 * @param string  $session_name  Session name
	 * @param string  $input_name     Form name
	 * @param integer $hashTime2Live Default seconds hash before expiration
	 * @param integer $hashSize      Default hash size in chars
	 */
	function __construct ($session_class,$session_name='csrf-lib', $hashTime2Live=0, $hashSize=64) {
		// Session mods
		$this->name = $session_name;
		// Default time before expire for hashes
		$this->hashTime2Live = $hashTime2Live;
		// Default hash size
		$this->hashSize = $hashSize;
		// global session
		$this->session_class = $session_class;
		// Load hash list
		$this->_load();
	}

	/**
	 * Generate a CSRF_Hash
	 * @param  string  $context    Name of the form
	 * @param  integer $time2Live  Seconds before expiration
	 * @param  integer $max_hashes Clear old context hashes if more than this number
	 * @return CSRF_Hash
	 */
	private function generateHash ($context='', $time2Live=-1, $max_hashes=5) {
		// If no time2live (or invalid) use default
		if ($time2Live < 0) $time2Live = $this->hashTime2Live;
		// Generate new hash
		$hash = new CSRF_Hash($context, $time2Live, $this->hashSize);
		// Save it
		array_push($this->hashes, $hash);
		if ($this->clearHashes($context, $max_hashes) == 0) {
			$this->_save($this->session_class);
		}

		// Return hash info
		return $hash;
	}

	/**
	 * Get the hashes of a context
	 * @param  string  $context    the group to clean
	 * @param  integer $max_hashes max hashes to get
	 * @return array               array of hashes as strings
	 */
	public function getHashes ($context='', $max_hashes=-1) {
		$len = count($this->hashes);
		$hashes = array();
		// Check in the hash list
		for ($i = $len - 1; $i >= 0 && $len > 0; $i--) {
			if ($this->hashes[$i]->inContext($context)) {
				array_push($hashes, $this->hashes[$i]->get());
				$len--;
			}
		}
		return $hashes;
	}

	/**
	 * Clear the hashes of a context
	 * @param  string  $context    the group to clean
	 * @param  integer $max_hashes ignore first x hashes
	 * @return integer             number of deleted hashes
	 */
	public function clearHashes ($context='', $max_hashes=0) {
		$ignore = $max_hashes;
		$deleted = 0;
		// Check in the hash list
		for ($i = count($this->hashes) - 1; $i >= 0; $i--) {
			if ($this->hashes[$i]->inContext($context) && $ignore-- <= 0) {
				array_splice($this->hashes, $i, 1);
				$deleted++;
			}
		}
		if ($deleted > 0) {
			$this->_save($this->session_class);
		}
		return $deleted;
	}

	/**
	 * Generate an input html element
	 * @param  string  $context   Name of the form
	 * @param  integer $time2Live Seconds before expire
	 * @param  integer $max_hashes Clear old context hashes if more than this number
	 * @return integer             html input element code as a string
	 */
	public function input ($context='',$input='key-awesome', $time2Live=-1, $max_hashes=1) {
		// Generate hash
		$hash = $this->generateHash ($context, $time2Live, $max_hashes);
		// Generate html input string
		$this->inputName = $input;
		return '<input type="hidden" name="' . htmlspecialchars($this->inputName) . '"  id="' . htmlspecialchars($this->inputName) . '" value="' . htmlspecialchars($hash->get()) . '"/>';
	}

	/**
	 * Generate a script html element with the hash variable
	 * @param  string  $context    Name of the form
	 * @param  string  $name       The name for the variable
	 * @param  integer $time2Live  Seconds before expire
	 * @param  integer $max_hashes Clear old context hashes if more than this number
	 * @return integer             html script element code as a string
	 */
	public function script ($context='', $name='', $declaration='var', $time2Live=-1, $max_hashes=5) {
		// Generate hash
		$hash = $this->generateHash ($context, $time2Live, $max_hashes);
		// Variable name
		if (strlen($name) == 0) {
			$name = $this->inputName;
		}
		// Generate html input string
		return '<script type="text/javascript">' . $declaration . ' ' . $name . ' = ' . json_encode($hash->get()) . ';</script>';
	}

	/**
	 * Generate a javascript variable with the hash
	 * @param  string  $context    Name of the form
	 * @param  string  $name       The name for the variable
	 * @param  integer $time2Live  Seconds before expire
	 * @param  integer $max_hashes Clear old context hashes if more than this number
	 * @return integer             html script element code as a string
	 */
	public function javascript ($context='', $name='', $declaration='var', $time2Live=-1, $max_hashes=5) {
		// Generate hash
		$hash = $this->generateHash ($context, $time2Live, $max_hashes);
		// Variable name
		if (strlen($name) == 0) {
			$name = $this->inputName;
		}
		// Generate html input string
		return $declaration . ' ' . $name . ' = ' . json_encode($hash->get()) . ';';
	}

	/**
	 * Generate a string hash
	 * @param  string  $context    Name of the form
	 * @param  integer $time2Live  Seconds before expire
	 * @param  integer $max_hashes Clear old context hashes if more than this number
	 * @return integer             hash as a string
	 */
	public function string ($context='', $time2Live=-1, $max_hashes=5) {
		// Generate hash
		$hash = $this->generateHash ($context, $time2Live, $max_hashes);
		// Generate html input string
		return $hash->get();
	}

	/**
	 * Validate by context
	 * @param  string $context Name of the form
	 * @return boolean         Valid or not
	 */
	public function validate ($context='', $hash = null) {
		// If hash was not given, find hash
		if (is_null($hash)) {
			if (isset($_POST[$this->inputName])) {
				$hash = $_POST[$this->inputName];
			}
			else if (isset($_GET[$this->inputName])) {
				$hash = $_GET[$this->inputName];
			}
			else {
				return false;
			}
		}

		// Check in the hash list
		for ($i = count($this->hashes) - 1; $i >= 0; $i--) {
			if ($this->hashes[$i]->verify($hash, $context)) {
				array_splice($this->hashes, $i, 1);
				return true;
			}
		}
		return false;
	}


	/**
	 * Load hash list
	 */
	private function _load () {
		$this->hashes = array();
		// Generate new hash
		// If there are hashes on the session
		$t_session = $this->session_class;
		$csrf_session = $t_session->getValue($this->name);
		if (isset($csrf_session)) {
		//**if (isset($_SESSION[$this->name])) {
			// Load session hashes
			//**$session_hashes = unserialize($_SESSION[$this->name]);
			$session_hashes = unserialize($csrf_session);
			// Ignore expired
			for ($i = count($session_hashes) - 1; $i >= 0; $i--) {
				// If an expired found, the rest will be expired
				if ($session_hashes[$i]->hasExpire()) {
					break;
				}
				array_unshift($this->hashes, $session_hashes[$i]);
			}
			if (count($this->hashes) != count($session_hashes)) {
				$this->_save($this->session_class);
			}
		}
	}

	/**
	 * Save hash list
	 */
	private function _save ($session_class) {
		$session_class->setValue($this->name,serialize($this->hashes));
		//**$_SESSION[$this->name] = serialize($this->hashes);
	}
}

class CSRF_Hash {

	private $hash;
	private $context;
	private $expire;

	/**
	 * [__construct description]
	 * @param string  $context   [description]
	 * @param integer $time2Live Number of seconds before expiration
	 */
	function __construct($context, $time2Live=0, $hashSize=64) {
		// Save context name
		$this->context = $context;

		// Generate hash
		$this->hash = $this->_generateHash($hashSize);

		// Set expiration time
		if ($time2Live > 0) {
			$this->expire = time() + $time2Live;
		}
		else {
			$this->expire = 0;
		}
	}

	/**
	 * The hash function to use
	 * @param  int $n 	Size in bytes
	 * @return string 	The generated hash
	 */
	private function _generateHash ($n) {
		return bin2hex(openssl_random_pseudo_bytes($n/2));
	}

	/**
	 * Check if hash has expired
	 * @return boolean
	 */
	public function hasExpire () {
		if ($this->expire == 0 || $this->expire > time()) {
			return false;
		}
		return true;
	}

	/**
	 * Verify hash
	 * @return boolean
	 */
	public function verify ($hash, $context='') {
		if (strcmp($context, $this->context) == 0 && !$this->hasExpire() && strcmp($hash, $this->hash) == 0) {
			return true;
		}
		return false;
	}

	/**
	 * Check Context
	 * @return boolean
	 */
	public function inContext ($context='') {
		if (strcmp($context, $this->context) == 0) {
			return true;
		}
		return false;
	}

	/**
	 * Get hash
	 * @return string
	 */
	public function get () {
		return $this->hash;
	}
}


$session_class = new Session(SESSION_CONFIG);
$session_class->start();

?>