<?php
/**
 * @package      CAPTCHA plugin using Securimage
 * @subpackage   plg_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper;

use Joomla\Filesystem\Folder;
use Joomla\Session\SessionInterface;

\defined('_JEXEC') or die;

/*
 */
abstract class BfsecurimageHelper
{
	protected static string $context = 'plg_captcha_securimage.code';

	/**
	 * Return a random float between 0 and 0.9999
	 *
	 * @return float Random float between 0 and 0.9999
	 */
	public static function frand()
	{
		return 0.0001 * mt_rand(0,9999);
	}

	/*
	 */
	public static function strlen($string)
	{
		$strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';

		return $strlen($string);
	}

	/*
	 */
	public static function strpos($haystack, $needle, $offset = 0)
	{
		$strpos = function_exists('strpos') ? 'strpos' : 'strpos';

		return $strpos($haystack, $needle, $offset);
	}

	/*
	 *
	 */
	public static function substr($string, $start, $length = null)
	{
		$substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';

		return $substr($string, $start, $length);
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

	/**
	 * Scan the directory for a background image to use
	 *
	 * @return string|bool
	 */
	public static function getBackgroundFromDirectory()
	{
		$backgroundDirectory = dirname(__DIR__, 2) . '/files/backgrounds';
		if (!is_dir($backgroundDirectory)) return null;

		$images = Folder::files($backgroundDirectory, '(\\.jpg|\\.gif|\\.png)$', true, true);
		if (empty($images)) return null;

		return $images[mt_rand(0, sizeof($images)-1)];
	}

	/**
	 * Generates a random captcha code from the set character set
	 *
	 * @see Securimage::$charset  Charset option
	 * @return string A randomly generated CAPTCHA code
	 */
	public static function generateCode($charset, $codeLength)
	{
		$code = '';

		for($i = 1, $cslen = self::strlen($charset); $i <= $codeLength; ++$i) {
			$code .= self::substr($charset, mt_rand(0, $cslen - 1), 1);
		}

		return $code;
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

	/**
	 * Sends the appropriate image and cache headers and outputs image to the browser
	 */
	public static function outputImage($img, $imageType='png', $sendHeaders=false)
	{
		// Only send the content-type headers if no headers have been output this will ease debugging on misconfigured
		// servers where warnings may have been output which break the output and prevent easily viewing source to see the error.
		if (self::canSendHeaders())
		{
			if ($sendHeaders)
			{
				self::cacheHeaders();
			}

			switch ($imageType)
			{
				case 'jpeg':
					if ($sendHeaders) header("Content-Type: image/jpeg");
					imagejpeg($img, null, 90);
					break;
				case 'gif':
					if ($sendHeaders) header("Content-Type: image/gif");
					imagegif($img);
					break;
				default:
					if ($sendHeaders) header("Content-Type: image/png");
					imagepng($img);
					break;
			}
		}
		else
		{
			echo '<hr /><strong>'
				.'Failed to generate captcha image, content has already been '
				.'output.<br />This is most likely due to misconfiguration or '
				.'a PHP error was sent to the browser.</strong>';
		}

		imagedestroy($img);
	}

	/*
	 */
	public static function outputSound($audio, $soundType='wav', $sendHeaders=false)
	{
		// Only send the content-type headers if no headers have been output this will ease debugging on misconfigured
		// servers where warnings may have been output which break the output and prevent easily viewing source to see the error.
		if (self::canSendHeaders())
		{
			if ($sendHeaders)
			{
				self::cacheHeaders();

				header('Accept-Ranges: bytes');
			}

			if (isset($_SERVER['HTTP_RANGE'])) {
				$uniq = (isset($_SERVER['HTTP_X_PLAYBACK_SESSION_ID'])) ?
									'ID' . $_SERVER['HTTP_X_PLAYBACK_SESSION_ID']   :
									'ID' . md5($_SERVER['REQUEST_URI']);
			} else {
				$uniq = md5(uniqid(microtime()));
			}

			switch ($soundType)
			{
				case 'wav':
					header('Content-Disposition: attachment; filename="securimage_audio-' . $uniq . '.wav"');
					header('Content-type: audio/wav');
					break;
				default:
					if ($sendHeaders) header("Content-Type: audio");
					break;
			}

			self::rangeDownload($audio);
		}
		else
		{
			echo '<hr /><strong>'
				.'Failed to generate audio file, content has already been '
				.'output.<br />This is most likely due to misconfiguration or '
				.'a PHP error was sent to the browser.</strong>';
		}
	}

	/**
	 * Output audio data with http range support.
	 *
	 * @param string $audio Raw audio file content
	 */
	protected static function rangeDownload($audio)
	{
		$audioLength = $size = strlen($audio);

		if (isset($_SERVER['HTTP_RANGE']))
		{
			// bytes=byte-range-set
			list( , $range) = explode('=', $_SERVER['HTTP_RANGE']);
			$range = trim($range);

			if (strpos($range, ',') !== false)
			{
				// eventually, we should handle requests with multiple ranges
				// most likely these types of requests will never be sent
				header('HTTP/1.1 416 Range Not Satisfiable');
				echo "<h1>Range Not Satisfiable</h1>";
				exit;
			}

			if (preg_match('/(\d+)-(\d+)/', $range, $match))
			{
				// bytes n - m
				$range = array(intval($match[1]), intval($match[2]));
			}
			else if (preg_match('/(\d+)-$/', $range, $match))
			{
				// bytes n - last byte of file
				$range = array(intval($match[1]), null);
			}
			else if (preg_match('/-(\d+)/', $range, $match))
			{
				// final n bytes of file
				$range = array($size - intval($match[1]), $size - 1);
			}

			if ($range[1] === null)
			{
				$range[1] = $size - 1;
			}

			$length = $range[1] - $range[0] + 1;
			$audio = substr($audio, $range[0], $length);
			$audioLength = strlen($audio);

			header('HTTP/1.1 206 Partial Content');
			header("Content-Range: bytes {$range[0]}-{$range[1]}/{$size}");

			if ($range[0] < 0 ||$range[1] >= $size || $range[0] >= $size || $range[0] > $range[1])
			{
				header('HTTP/1.1 416 Range Not Satisfiable');
				echo "<h1>Range Not Satisfiable</h1>";
				exit;
			}
		}

		header('Content-Length: ' . $audioLength);

		echo $audio;
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
}
