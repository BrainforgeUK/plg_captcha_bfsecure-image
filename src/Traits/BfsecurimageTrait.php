<?php
/**
 * @package      CAPTCHA plugin using Securimage
 * @subpackage   plg_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits;

use Joomla\CMS\Language\Text;
use Joomla\Session\SessionInterface;
use Joomla\Utilities\IpHelper;

\defined('_JEXEC') or die;

/*
 */
trait BfsecurimageTrait
{
	/**
	 * The length of the captcha code
	 *
	 * @var int
	 */
	protected int $codeLength;

	/**
	 * Whether the captcha should be case sensitive or not.
	 *
	 * Not recommended, use only for maximum protection.
	 *
	 * @var bool
	 */
	protected bool $caseSensitive;

	/**
	 * The captcha challenge value.
	 *
	 * The case-sensitive/insensitive word captcha
	 *
	 * @var string Captcha challenge value
	 */
	protected string $code;

	/**
	 * The display value of the captcha to draw on the image
	 *
	 * The word captcha
	 *
	 * @var string Captcha display value to draw on the image
	 */
	protected string $codeDisplay;

	/*
	 */
	protected function getCaptchaKey($input, $name='key')
	{
		$captchaKey = $input->getInt($name);
		if (empty($captchaKey)) throw new \RuntimeException(Text::_('PLG_CAPTCHA_BFSECURIMAGE_ERROR_NO_CAPTCHA_KEY'));

		return $captchaKey;
	}

	/*
	 */
	protected function getCaptchaTask($input)
	{
		$captchaTask = $input->getCmd('task');
		if (empty($captchaTask)) throw new \RuntimeException(Text::_('PLG_CAPTCHA_BFSECURIMAGE_ERROR_NO_CAPTCHA_TASK'));

		return $captchaTask;
	}

	/*
	 */
	protected function getRemoteIP()
	{
		$remoteIp = IpHelper::getIp();
		if (empty($remoteIp)) throw new \RuntimeException(Text::_('PLG_CAPTCHA_BFSECURIMAGE_ERROR_NO_IP'));

		return $remoteIp;
	}

	/*
	 */
	protected static function strlen($string)
	{
		$strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';

		return $strlen($string);
	}

	/*
	 */
	protected static function strpos($haystack, $needle, $offset = 0)
	{
		$strpos = function_exists('strpos') ? 'strpos' : 'strpos';

		return $strpos($haystack, $needle, $offset);
	}

	/*
	 *
	 */
	protected static function substr($string, $start, $length = null)
	{
		$substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';

		return $substr($string, $start, $length);
	}

	/**
	 * Return a random float between 0 and 0.9999
	 *
	 * @return float Random float between 0 and 0.9999
	 */
	protected static function frand()
	{
		return 0.0001 * mt_rand(0,9999);
	}

	/**
	 * Generates a random captcha code from the character set
	 */
	protected function generateCode()
	{
		$code = [];

		for($i = 1, $cslen = self::strlen($this->charset); $i <= $this->codeLength; ++$i)
		{
			$code[] = self::substr($this->charset, mt_rand(0, $cslen - 1), 1);
		}

		$this->codeDisplay = $this->code = implode('', $code);
		$this->code        = ($this->caseSensitive) ? $this->code : strtolower($this->code);
	}

	/*
	 */
	protected function saveCode($captchaKey, $remoteIp)
	{
		$time = time();

		$db = $this->getDatabase();

		$sql = 'DELETE FROM #__bfsecurimage' .
				' WHERE `expires` <= ' . $time .
				' OR `key` = ' . $captchaKey;
		$db->setQuery($sql);
		$db->execute();

		$sql = 'INSERT INTO #__bfsecurimage ( `expires`, `key`, `ip`, `code`, `case` )' .
				' VALUES ( ' . ($time + 3600) . ',' .
								$captchaKey . ',' .
								$db->quote($remoteIp) . ',' .
								$db->quote($this->codeDisplay) . ',' .
								($this->caseSensitive ? 1 : 0) .
						' )';
		$db->setQuery($sql);
		$db->execute();
	}

	/*
	 */
	protected function loadCode($captchaKey, $remoteIp, $purge=false)
	{
		$db = $this->getDatabase();

		$sql = 'DELETE FROM #__bfsecurimage' .
			' WHERE `expires` <= ' . time();
		$db->setQuery($sql);
		$db->execute();

		$sql = 'SELECT `code`, `case`' .
			' FROM #__bfsecurimage' .
			' WHERE `ip` = ' . $db->quote($remoteIp) .
			' AND `key` = ' . $captchaKey;
		$db->setQuery($sql);
		$result = $db->loadObject();

		if (empty($result)) throw new \Exception(Text::_('PLG_CAPTCHA_BFSECURIMAGE_ERROR_NOCAPTCHACODE'));

		$this->codeDisplay = $result->code;
		$this->caseSensitive = $result->case;

		if (!$purge) return;

		$sql = 'DELETE FROM #__bfsecurimage' .
			' WHERE `key` = ' . $captchaKey;
		$db->setQuery($sql);
		$db->execute();
	}

	/**
	 * Checks to see if headers can be sent and if any error has been output to the browser
	 *
	 * @return bool true if it is safe to send headers, false if not
	 */
	protected static function canSendHeaders()
	{
		if (headers_sent())
		{
			// output has been flushed and headers have already been sent
			return false;
		}
		else if (strlen((string)ob_get_contents()))
		{
			// headers haven't been sent, but there is data in the buffer that will break image and audio data
			return false;
		}

		return true;
	}

	/*
	 */
	protected static function cacheHeaders()
	{
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}

	/**
	 * Get the width and height (in points) of a character for a given font, angle, and size.
	 *
	 * @param string $char The character to get dimensions for
	 * @param number $size The font size, in points
	 * @param number $angle The angle of the text
	 * @return number[] A 3-element array representing the width, height and baseline of the text
	 */
	public static function getCharacterDimensions($char, $size, $angle, $font)
	{
		$box = imagettfbbox($size, $angle, $font, $char);

		return array($box[2] - $box[0], max($box[1] - $box[7], $box[5] - $box[3]), $box[1]);
	}
}
?>