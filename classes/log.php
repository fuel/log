<?php
/**
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Log;

/**
 * Log core class replacement
 *
 * This class will provide the interface between the Fuel v1.x class API
 * and the Monolog package, in preparation for FuelPHP v2.0
 */
class Log
{
	/**
	 * container for the Monolog instance
	 */
	protected static $monolog = null;

	/**
	 * Copy of the Monolog log levels
	 */
	protected static $levels = array(
		100 => 'DEBUG',
		200 => 'INFO',
		250 => 'NOTICE',
		300 => 'WARNING',
		400 => 'ERROR',
		500 => 'CRITICAL',
		550 => 'ALERT',
		600 => 'EMERGENCY',
	);

	/**
	 * Initialize the class
	 */
	public static function _init()
	{
		// load the file config
		\Config::load('file', true);

		// determine the name and location of the logfile
		$filepath = \Config::get('log_path').date('Y/m').'/';

		if ( ! is_dir($filepath))
		{
			$old = umask(0);
			mkdir($filepath, \Config::get('file.chmod.folders', 0777), true);
			umask($old);
		}

		$filename = $filepath.date('d').'.php';

		if ( ! file_exists($filename))
		{
			file_put_contents($filename, "<"."?php defined('COREPATH') or exit('No direct script access allowed'); ?".">".PHP_EOL.PHP_EOL);
		}

		// create the monolog instance
		static::$monolog = new \Monolog\Logger('fuelphp');

		// create the streamhandler, and activate the handler
		$stream = new \Monolog\Handler\StreamHandler($filename, \Monolog\Logger::DEBUG);
		$formatter = new \Monolog\Formatter\LineFormatter("%level_name% - %datetime% --> %message%".PHP_EOL, "Y-m-d H:i:s");
		$stream->setFormatter($formatter);
		static::$monolog->pushHandler($stream);
	}

	/**
	 * Return the monolog instance
	 */
	public static function instance()
	{
		return static::$monolog;
	}

	/**
	 * Logs a message with the Info Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function info($msg, $method = null)
	{
		return static::write(\Fuel::L_INFO, $msg, $method);
	}

	/**
	 * Logs a message with the Debug Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function debug($msg, $method = null)
	{
		return static::write(\Fuel::L_DEBUG, $msg, $method);
	}

	/**
	 * Logs a message with the Warning Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function warning($msg, $method = null)
	{
		return static::write(\Fuel::L_WARNING, $msg, $method);
	}

	/**
	 * Logs a message with the Error Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 */
	public static function error($msg, $method = null)
	{
		return static::write(\Fuel::L_ERROR, $msg, $method);
	}

	/**
	 * Logs a message with the Info Log Level
	 *
	 * @param   mixed  arg,...    The log messages
	 * @return  bool    If it was successfully logged
	 */
	public static function i()
	{
		return static::write_log(\Fuel::L_INFO, func_get_args(), debug_backtrace());
	}

	/**
	 * Logs a message with the Debug Log Level
	 *
	 * @param   mixed  arg,...    The log messages
	 * @return  bool   If it was successfully logged
	 */
	public static function d()
	{
		return static::writeLog(\Fuel::L_DEBUG, func_get_args(), debug_backtrace());
	}

	/**
	 * Logs a message with the Warning Log Level
	 *
	 * @param   mixed  arg,...    The log messages
	 * @return  bool   If it was successfully logged
	 */
	public static function w()
	{
		return static::writeLog(\Fuel::L_WARNING, func_get_args(), debug_backtrace());
	}

	/**
	 * Logs a message with the Error Log Level
	 *
	 * @param   mixed  arg,...    The log messages
	 * @return  bool   If it was successfully logged
	 */
	public static function e()
	{
		return static::writeLog(\Fuel::L_ERROR, func_get_args(), debug_backtrace());
	}

	/**
	 * Write Log  a message with the Error Log Level
	 *
	 * @param   mixed  $level     The error level
	 * @param   mixed  $msgs 			The log messages
	 * @param   array  $traces 		debug_backtrace() return value
	 * @return  bool   If it was successfully logged
	 */
	private static function write_log($level, $msgs, $traces)
	{
		return static::write($level, static::parse_arrays($msgs, static::parase_backtraces($traces)));
	}

	/**
	 * Parse output messages
	 *
	 * @param   array  $args     The log message
	 * @return  string flat string
	 */
	private static function parse_arrays($args){
		$msg = array();
		foreach($args as $arg)
		{
			if (is_array($arg))
			{
				$msg[] = print_r($arg, true);
			}
			else if (is_object($arg))
			{
				$msg[] = var_export($arg, true);
			}
			else
			{
				$msg[] = $arg;
			}
		}
		return implode(' ', $msg);
	}

	/**
	 * Parse backtraces
	 *
	 * @param   object  $backTraces backtrace
	 * @return  string  call logging class and function name
	 */
	private static function parse_backtraces($back_traces)
	{
		$method = '';
		if($back_traces && isset($back_traces[1]))
		{
			$caller = $back_traces[1];
			$method = array();
			if(isset($caller['class']))
			{
				$method[] = $caller['class'];
			}
			if(isset($caller['function']))
			{
				$method[] = $caller['function'];
			}
			$method = implode('::', $method);
		}
		return $method;
	}

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @access	public
	 * @param	int|string	the error level
	 * @param	string	the error message
	 * @param	string	information about the method
	 * @return	bool
	 */
	public static function write($level, $msg, $method = null)
	{
		// defined default error labels
		static $oldlabels = array(
			1  => 'Error',
			2  => 'Warning',
			3  => 'Debug',
			4  => 'Info',
		);

		// get the levels defined to be logged
		$loglabels = \Config::get('log_threshold');

		// bail out if we don't need logging at all
		if ($loglabels == \Fuel::L_NONE)
		{
			return false;
		}

		// if it's not an array, assume it's an "up to" level
		if ( ! is_array($loglabels))
		{
			$a = array();
			foreach (static::$levels as $l => $label)
			{
				$l >= $loglabels and $a[] = $l;
			}
			$loglabels = $a;
		}

		// if profiling is active log the message to the profile
		if (\Config::get('profiling'))
		{
			\Console::log($method.' - '.$msg);
		}

		// convert the level to monolog standards if needed
		if (is_int($level) and isset($oldlabels[$level]))
		{
			$level = strtoupper($oldlabels[$level]);
		}
		if (is_string($level))
		{
			if ( ! $level = array_search($level, static::$levels))
			{
				$level = 250;	// can't map it, convert it to a NOTICE
			}
		}

		// make sure $level has the correct value
		if ((is_int($level) and ! isset(static::$levels[$level])) or (is_string($level) and ! array_search(strtoupper($level), static::$levels)))
		{
			throw new \FuelException('Invalid level "'.$level.'" passed to logger()');
		}

		// do we need to log the message with this level?
		if ( ! in_array($level, $loglabels))
		{
			return false;
		}

		// log the message
		static::$monolog->log($level, (empty($method) ? '' : $method.' - ').$msg);

		return true;
	}

}
