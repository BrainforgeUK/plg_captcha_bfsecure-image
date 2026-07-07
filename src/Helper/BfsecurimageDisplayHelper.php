<?php
/**
 * @package      CAPTCHA plugin using Securimage
 * @subpackage   plg_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper;

use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/*
 */
abstract class BfsecurimageDisplayHelper
{
	public static function getCaptchaHtml(array $options, Registry $params)
	{
		ob_start();

		include dirname(__DIR__, 2) . '/tmpl/default.php';

		return ob_get_clean();
	}
}
