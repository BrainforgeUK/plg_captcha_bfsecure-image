<?php
/**
 * @package   CAPTCHA plugin using Securimage
 * @author    https://www.brainforge.co.uk
 * @version   0.0.1
 * @author    https://www.brainforge.co.uk
 * @copyright (C) 2012-2026 Jonathan Brain. All rights reserved.
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Extension;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper\BfsecurimageDisplayHelper;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\BfsecurimageTrait;
use Joomla\CMS\Environment\Browser;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/*
 */
class Bfsecurimage extends CMSPlugin implements SubscriberInterface
{
	use BfsecurimageTrait;

	protected $app;

	protected $int;

	protected $autoloadLanguage = true;

	/*
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onDisplay' => 'onDisplay',
		];
	}

	/**
	 * Initialise the captcha
	 *
	 * @param string $id The id of the field.
	 *
	 * @return    Boolean    True on success, false otherwise
	 */
	public function onInit($id)
	{
		return true;
	}

	/**
	 * Gets the challenge HTML
	 *
	 * @return  string  The HTML to be embedded in the form.
	 */
	public function onDisplay($name, $id, $class)
	{
		if (empty($this->params)) return '';

		$browser = Browser::getInstance();
		if (!empty($browser) && $browser->isRobot())
		{
			return 'Captcha not accessible to robot.';
		}

		$options = array();
		$options['show_audio_button'] = $this->params->get('audio');
		$options['show_refresh_button'] = $this->params->get('refresh');
		$options['image_alt_text'] = Text::_('PLG_CAPTCHA_BFSECURIMAGE_THE_IMAGE');
		$options['audio_title_text'] = Text::_('PLG_CAPTCHA_BFSECURIMAGE_AUDIO_CHALLENGE');
		$options['refresh_alt_text'] = Text::_('PLG_CAPTCHA_BFSECURIMAGE_NEW_CHALLENGE');
		$options['refresh_title_text'] = $options['refresh_alt_text'];
		$options['input_text'] = Text::_('PLG_CAPTCHA_BFSECURIMAGE_VERIFY_CHALLENGE');
		$options['input_id'] = Text::_('PLG_CAPTCHA_BFSECURIMAGE_RESPONSEFIELD_DEFAULT');

		return BfsecurimageDisplayHelper::getCaptchaHtml($options, $this->params);
	}

	/**
	 * Verifies if the user's guess was correct
	 *
	 * @return  True | false if the answer is correct, false otherwise
	 */
	public function onCheckAnswer($code=null)
	{
		$input = $this->app->getInput();

		$captchaResponse = $input->request->getRaw('bfsecurimage-captcha-response', '', 'string');
		if (empty($captchaResponse)) {
			throw new \RuntimeException(Text::_('PLG_CAPTCHA_BFSECURIMAGE_ERROR_EMPTY_SOLUTION'));
		}

		$captchaKey = $this->getCaptchaKey($input->request, 'bfsecurimage-captcha-key');

		$remoteIp = $this->getRemoteIp();

		$this->loadCode($captchaKey, $remoteIp, true);

		if ($this->caseSensitive) return strcmp($captchaResponse, $this->codeDisplay) ? false : true;

		return strcasecmp($captchaResponse, $this->codeDisplay) ? false : true;
	}

	/*
	 */
	protected function getDatabase()
	{
		return Factory::getContainer()->get(DatabaseInterface::class);
	}
}
