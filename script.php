<?php
/**
 * @package		CAPTCHA plugin using Securimage.
 * @subpackage	plg_secureimage
 * @copyright	Copyright (C) 2018-2024 Jonathan Brain. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Folder;

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
	public function install($parent)
	{
		$this->createTable();
	}

	/**
	 * method to uninstall the component
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$sql = 'DROP TABLE  IF EXISTS `#__bfsecurimage`';
		$db->setQuery($sql);
		$db->execute();
	}

	/**
	 * method to update the component
	 *
	 * @return void
	 */
	public function update($parent)
	{
		$includes = JPATH_SITE . '/plugins/plgCaptchaBFSecurimage/includes';
		if (is_dir($includes))
		{
			Folder::delete($includes);
		}

		// Tidyup language files left over from earlier version
		if (!is_dir(JPATH_SITE . '/plugins/captcha/plg_captcha_bfsecurimage/language')) {
			@unlink(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.plg_captcha_bfsecurimage.ini');
			@unlink(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.plg_captcha_bfsecurimage.sys.ini');
		}

		$this->createTable();
	}

	/**
	 * method to run before an install/update/uninstall method
	 *
	 * @return void
	 */
	public function preflight($type, $parent)
	{
	}

	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @return void
	 */
	public function postflight($type, $parent)
	{
	}

	/*
	 */
	protected function createTable()
	{
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		$sql = '
CREATE TABLE IF NOT EXISTS `#__bfsecurimage` (
    `expires`       INT UNSIGNED NOT NULL,
    `key`    		BIGINT UNSIGNED NOT NULL,
    `ip`     		VARCHAR(32) NOT NULL,
    `code`        	VARCHAR(64) NOT NULL,
    `case`        	TINYINT DEFAULT 0 NOT NULL,
    PRIMARY KEY		(`key`),
    INDEX           (`expires`),
    INDEX           (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		';
		$db->setQuery($sql);
		$db->execute();
	}
}