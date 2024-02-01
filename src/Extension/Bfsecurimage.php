<?php
/**
 * @package   CAPTCHA plugin uses Securimage
 * @author    https://www.brainforge.co.uk
 * @version   0.0.1
 * @author    https://www.brainforge.co.uk
 * @copyright (C) 2012-2024 Jonathan Brain. All rights reserved.
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Extension;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper\BfsecurimageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/*
 */
class Bfsecurimage extends CMSPlugin implements SubscriberInterface
{
	protected $app;

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
	$this->responseField = $this->params->get('responsefield', 'bfsecurimage_response_field');
	if (empty($this->responseField))
	{
		Log::add(Text::sprintf('JLIB_CAPTCHA_ERROR_PLUGIN_NOT_FOUND', $name), Log::WARNING, 'jerror');
		return '';
	}

	if ($this->params->get('cssmode', 1))
	{
		$css = trim(Text::_($this->params->get('customcss', 'PLG_BFSECURIMAGE_CSSCUSTOM_DEFAULT')));
		if (!empty($css))
		{
			$this->app->getDocument()->addStyleDeclaration($css);
		}
	}

	$options = array();
	$options['show_audio_button'] = $this->params->get('audio');
	$options['show_refresh_button'] = $this->params->get('refresh');
	$options['image_alt_text'] = Text::_('PLG_BFSECURIMAGE_THE_IMAGE');
	$options['audio_title_text'] = Text::_('PLG_BFSECURIMAGE_AUDIO_CHALLENGE');
	$options['refresh_alt_text'] = Text::_('PLG_BFSECURIMAGE_NEW_CHALLENGE');
	$options['refresh_title_text'] = $options['refresh_alt_text'];
	$options['input_text'] = Text::_('PLG_BFSECURIMAGE_VERIFY_CHALLENGE');
	$options['input_id'] = Text::_('PLG_BFSECURIMAGE_RESPONSEFIELD_DEFAULT');

	$securImage = BfsecurimageHelper::getSecureimageInstance();
	return $securImage->getCaptchaHtml($options);
}

	/**
	 * Verifies if the user's guess was correct
	 *
	 * @return  True | false if the answer is correct, false otherwise
	 */
	function onCheckAnswer($code)
	{
		$this->responseField = Text::_($this->params->get('responsefield'));
		$solution = $this->app->getInput()->request->get($this->responseField, '', 'string');
		if (empty($solution))
		{
			return false;
		}

		$securImage = BfsecurimageHelper::getSecureimageInstance();
		$result = $securImage->check($solution);
		if (!$result)
		{
			return false;
		}

		return $result;
	}
}
