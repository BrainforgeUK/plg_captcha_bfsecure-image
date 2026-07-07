<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper\BfsecurimageCodeHelper;

\defined('_JEXEC') or die;

Trait BfsecurimageCheckTrait
{
	/*
	 */
	public function check()
	{
		$solution = $this->getInput()->getText('solution');
		if (empty($solution))
		{
			echo '0';
			return;
		}

		$code = BfsecurimageCodeHelper::queryCode($this->getSession(), true);
		if (empty($code) ||
			strpos($code, ':') === false)
		{
			echo '0';
			return;
		}

		BfsecurimageCodeHelper::saveCode($this->getSession());

		list($caseSensitive, $captcha) = explode(':', $code, 2);

		if (intval($caseSensitive))
		{
			echo $solution == $captcha ? '1' : '0';
			return;
		}

		echo strcasecmp($solution, $captcha) ? '0' : '1';
	}
}