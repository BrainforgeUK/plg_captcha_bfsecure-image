<?php
/**
 * @package      CAPTCHA plugin using Securimage
 * @subpackage   plg_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper;

use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Folder;
use Joomla\Session\SessionInterface;

\defined('_JEXEC') or die;

/*
 */
abstract class BfsecurimageCodeHelper
{
	protected static string $context = 'plg_captcha_securimage.code';

	/*
	 * We cannot hash this as we have to retrieve it into order to play the audio
	 */
	public static function saveCode(SessionInterface $session, $code=null, $caseSensitive=false)
	{
		if (empty($code)) return $session->set(self::$context);

		return $session->set(self::$context, intval($caseSensitive) . ':' . $code);
	}

	/*
	 */
	public static function queryCode(SessionInterface $session, $withCaseSensitivity=false)
	{
		$value = $session->get(self::$context);

		if (empty($value)) return $value;

		if ($withCaseSensitivity) return $value;

		return explode(':', $value, 2)[1];
	}

	/*
	 */
	public static function checkCode(SessionInterface $session, $code)
	{
		$value = self::queryCode($session, true);
		if (empty($value)) return false;

		BfsecurimageCodeHelper::saveCode($session);

		$value = explode(':', $value, 2);

		if ($value[0]) return $value[1] == $code;

		return strcasecmp($value[1], $code) == 0;
	}

	/*
	 */
	public static function codeIsValid($solution)
	{
		$url = Uri::root() . 'plugins/captcha/bfsecurimage/index.php?task=check&solution=' . $solution . '&' . time();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);

		$options = array(
			CURLOPT_URL             => $url,
			CURLOPT_ENCODING        => "",
			CURLOPT_RETURNTRANSFER  => true,
			CURLOPT_AUTOREFERER     => true,
			CURLOPT_CONNECTTIMEOUT  => 600,
			CURLOPT_TIMEOUT         => 600,
			CURLOPT_FAILONERROR     => true,
			CURLOPT_FOLLOWLOCATION  => true,
			CURLOPT_MAXREDIRS       => 30,
			CURLOPT_SSL_VERIFYPEER  => false,
			CURLOPT_SSL_VERIFYHOST  => false,
		);

		foreach($_COOKIE as $name => $value)
		{
			$options[CURLOPT_COOKIE] = $name . '=' . $value;
		}

		foreach ($options as $option => $value)
		{
			curl_setopt($ch, $option, $value);
		}

		$data = curl_exec($ch);

		curl_close($ch);

		if (empty($data)) return false;

		return trim($data);
	}
}
