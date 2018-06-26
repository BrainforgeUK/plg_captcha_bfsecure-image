<?php
/**
 * @package		Joomla.Site
 * @subpackage	plg_secureimage
 * @copyright	Copyright (C) 2018 Jonathan Brain. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

/**
 * Script file
 */
class plgCaptchaBFSecurimageInstallerScript
{
	/**
	 * method to install the component
	 *
	 * @return void
	 */
	function install($parent)
	{
	}

	/**
	 * method to uninstall the component
	 *
	 * @return void
	 */
	function uninstall($parent)
	{
	}

	/**
	 * method to update the component
	 *
	 * @return void
	 */
	function update($parent)
	{
	}

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @return void
	 */
	function preflight($type, $parent)
	{
		// Tidyup language files left over from earlier version
		if (!is_dir(JPATH_SITE . '/plugins/captcha/plg_captcha_bfsecurimage/language')) {
			@unlink(JPATH_SITE . '/language/en-GB/en-GB.plg_captcha_bfsecurimage.ini');
			@unlink(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.plg_captcha_bfsecurimage.ini');
			@unlink(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.plg_captcha_bfsecurimage.sys.ini');
		}
	}

	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @return void
	 */
	function postflight($type, $parent)
	{
	}
}