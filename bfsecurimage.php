<?php
/**
 * @package      Joomla.Site
 * @subpackage   plg_bfsecureimage
 * @copyright    Copyright (C) 2012-2021 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;

// no direct access
defined('_JEXEC') or die;

class plgCaptchaBFSecurimage extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;
	protected $_subject;

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
			Log::add(Text::sprintf('JLIB_CAPTCHA_ERROR_PLUGIN_NOT_FOUND', $name), JLog::WARNING, 'jerror');
			return '';
		}

		$doc = Factory::getApplication()->getDocument();
		if ($this->params->get('cssmode', 1))
		{
			$css = trim($this->params->get('customcss', Text::_('PLG_BFSECURIMAGE_CSSCUSTOM_DEFAULT')));
			if (!empty($css))
			{
				$doc->addStyleDeclaration($css);
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
		$options['input_id'] = 'bfsecurimage_response_field';

		require_once __DIR__ . '/bfsecurimagehelper.php';
		$securImage = plgCaptchaBFSecurimageHelper::getSecureimageInstance();
		return $securImage->getCaptchaHtml($options);
	}

	/**
	 * Verifies if the user's guess was correct
	 *
	 * @return  boolean True if the answer is correct, false otherwise
	 */
	function onCheckAnswer($code)
	{
		$solution = Factory::getApplication()->input->getString('bfsecurimage_response_field');
		if (empty($solution))
		{
			throw new \RuntimeException(Text::_('PLG_BFSECURIMAGE_ERROR_EMPTY_SOLUTION'), 500);
		}

		require_once __DIR__ . '/bfsecurimagehelper.php';
		$securImage = plgCaptchaBFSecurimageHelper::getSecureimageInstance();
		$result = $securImage->check($solution);
		if (!$result)
		{
			throw new \RuntimeException(Text::_('PLG_BFSECURIMAGE_ERROR_INCORRECT_CAPTCHA_SOL'), 500);
		}

		return $result;
	}
}

?>