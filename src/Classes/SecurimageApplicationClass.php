<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Classes;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\BfsecurimagePlayTrait;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\BfsecurimageShowTrait;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\BfsecurimageColorsTrait;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\BfsecurimageTrait;
use Joomla\Application\Web\WebClient;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Session\SessionInterface;

\defined('_JEXEC') or die;

final class SecurimageApplicationClass extends CMSApplication
{
	use BfsecurimageTrait;
	use BfsecurimageColorsTrait;
	use BfsecurimagePlayTrait;
	use BfsecurimageShowTrait;
	use DatabaseAwareTrait;

	protected Registry $pluginParams;

	/**
	 * Flag indicating whether or not HTTP headers will be sent when outputting captcha image/audio
	 *
	 * @var bool If true (default) headers will be sent, if false, no headers are sent
	 */
	protected bool $sendHeaders;

	/**
	 * The type of the image, png / jpeg / gif
	 *
	 * @var string
	 */
	protected string $imageType;

	/**
	 * Display random spaces in the captcha text on the image
	 *
	 * @var bool true to insert random spacing between groups of letters
	 */
	protected $useRandomSpaces = false;

	/**
	 * Draw each character at an angle with random starting angle and increase/decrease per character
	 * @var bool true to use random angles, false to draw each character normally
	 */
	protected $useTextAngles = false;

	/**
	 * Instead of centering text vertically in the image, the baseline of each character is randomized in such a way
	 * that the next character is drawn slightly higher or lower than the previous in a step-like fashion.
	 *
	 * @var bool true to use random baselines, false to center text in image
	 */
	protected $useRandomBaseline;

	/**
	 * Draw a bounding box around some characters at random.  20% of the time, random boxes
	 * may be drawn around 0 or more characters on the image.
	 *
	 * @var bool  true to randomly draw boxes around letters, false not to
	 */
	protected $useRandomBoxes;

	/*
	 */
	public function __construct(?Input $input = null, ?Registry $config = null, ?WebClient $client = null, ?Container $container = null)
	{
		parent::__construct($input, $config, $client, $container);

		if ($container !== null)
		{
			$this->setSession($container->get(SessionInterface::class));

			$this->setDatabase($container->get(DatabaseInterface::class));
		}
	}

	/*
	 */
	protected function doExecute()
	{
		throw new \Exception();
	}

	/*
	 */
	protected function loadPluginParams()
	{
		if (!empty($this->pluginParams)) return $this->pluginParams;

		$plugin = PluginHelper::getPlugin('captcha', 'bfsecurimage');

		return $this->pluginParams = new Registry($plugin->params);
	}

	/*
	 */
	public function runCaptchaTask()
	{
		$task = $this->getCaptchaTask($this->input);

		$captchaKey = $this->getCaptchaKey($this->input);

		$remoteIp = $this->getRemoteIp();

		$this->$task($captchaKey, $remoteIp);
	}
}