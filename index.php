<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Classes\SecurimageApplicationClass;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Input\Input as CMSInput;
use Joomla\Registry\Registry;
use Joomla\Session\SessionInterface;

defined('_JEXEC') and die;

\define('_JEXEC', 1);

$baseDir =  dirname(__DIR__, 3);

// Load global path definitions
if (file_exists($baseDir . '/defines.php')) {
	include_once $baseDir . '/defines.php';
}

require_once $baseDir . '/includes/defines.php';

// Run the application - All executable code should be triggered through this file
//require_once JPATH_BASE . '/includes/app.php';

require_once JPATH_BASE . '/includes/framework.php';

// Boot the DI container
$container = \Joomla\CMS\Factory::getContainer();

/*
 * Alias the session service keys to the web session service as that is the primary session backend for this application
 *
 * In addition to aliasing "common" service keys, we also create aliases for the PHP classes to ensure autowiring objects
 * is supported.  This includes aliases for aliased class names, and the keys for aliased class names should be considered
 * deprecated to be removed when the class name alias is removed as well.
 */
$container->alias('session.web', 'session.web.site')
	->alias('session', 'session.web.site')
	->alias('JSession', 'session.web.site')
	->alias(\Joomla\CMS\Session\Session::class, 'session.web.site')
	->alias(\Joomla\Session\Session::class, 'session.web.site')
	->alias(\Joomla\Session\SessionInterface::class, 'session.web.site');

JLoader::registerNamespace('Brainforgeuk\Plugin\Captcha\Bfsecurimage', __DIR__ . '/src');

// Instantiate the application.
$app = new SecurimageApplicationClass($container->get(CMSInput::class), $container->get('config'), null, $container);

$app->setSession($container->get(SessionInterface::class));

// Set the application as global app
Factory::$application = $app;

$lang = $container->get(LanguageFactoryInterface::class)->createLanguage($app->get('language'), $app->get('debug_lang'));
$lang->load('plg_captcha_bfsecurimage', __DIR__);

$task = $app->getInput()->getCmd('task', 'unknown');

$app->$task();

$app->close();
