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
use Joomla\Application\Web\WebClient;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Session\SessionInterface;

\defined('_JEXEC') or die;

final class securimageApplicationClass extends CMSApplication
{
	use BfsecurimageColorsTrait;
	use BfsecurimagePlayTrait;
	use BfsecurimageShowTrait;

	protected Registry $pluginParams;

	/**
	 * The length of the captcha code
	 *
	 * @var int
	 */
	protected int $codeLength;

	/**
	 * Whether the captcha should be case sensitive or not.
	 *
	 * Not recommended, use only for maximum protection.
	 *
	 * @var bool
	 */
	protected bool $caseSensitive;

	/**
	 * The captcha challenge value.
	 *
	 * Either the case-sensitive/insensitive word captcha, or the solution to the math captcha.
	 *
	 * @var string Captcha challenge value
	 */
	protected string $code;

	/**
	 * The display value of the captcha to draw on the image
	 *
	 * Either the word captcha or the math equation to present to the user
	 *
	 * @var string Captcha display value to draw on the image
	 */
	protected string $codeDisplay;

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

		$this->setSession($container->get(SessionInterface::class));
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
}