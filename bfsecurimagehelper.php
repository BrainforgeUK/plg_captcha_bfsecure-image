<?php
/**
 * @package        Joomla.Site
 * @subpackage    plg_bfsecureimage
 * @copyright    Copyright (C) 2012 Jonathan Brain. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

class plgCaptchaBFSecurimageHelper
{
	public static function getSecureimageInstance()
	{
		require_once __DIR__ . '/includes/securimage.php';
		return new Securimage(array('use_database' => true, 'no_session' => true));
	}
}

?>