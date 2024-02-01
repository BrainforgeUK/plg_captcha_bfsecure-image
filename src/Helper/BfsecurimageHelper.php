<?php
/**
 * @package      CAPTCHA plugin uses Securimage
 * @subpackage   plg_bfsecurimage
 * @copyright    Copyright (C) 2012-2024 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper;

// Sometimes used outside of Joomla (see securimage_show.php amd securimage_play.php).
// \defined('_JEXEC') or die;

/*
 */
abstract class BfsecurimageHelper
{
	public static function getSecureimageInstance()
	{
		require_once dirname(__DIR__, 2) . '/includes/securimage.php';
		return new \Securimage(array('use_database' => true, 'no_session' => true));
	}
}
?>