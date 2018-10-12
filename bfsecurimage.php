<?php
/**
 * @package        Joomla.Site
 * @subpackage    plg_bfsecureimage
 * @copyright    Copyright (C) 2012 Jonathan Brain. All rights reserved.
 * @license        GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
jimport('joomla.plugin.plugin');

class plgCaptchaBFSecurimage extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Initialise the captcha
	 *
	 * @param    string $id The id of the field.
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
			JLog::add(JText::sprintf('JLIB_CAPTCHA_ERROR_PLUGIN_NOT_FOUND', $name), JLog::WARNING, 'jerror');
			return '';
		}

		$doc = JFactory::getDocument();
		if($this->params->get('cssmode', 1))
		{
			$css = trim($this->params->get('customcss', JText::_('PLG_BFSECURIMAGE_CSSCUSTOM_DEFAULT')));
			if (!empty($css))
			{
				$doc->addStyleDeclaration($css);
			}
		}

		$options = array();
		$options['show_audio_button'] = $this->params->get('audio');
		$options['show_refresh_button'] = $this->params->get('refresh');
		$options['image_alt_text'] = JText::_('PLG_BFSECURIMAGE_THE_IMAGE');
		$options['audio_title_text'] = JText::_('PLG_BFSECURIMAGE_AUDIO_CHALLENGE');
		$options['refresh_alt_text'] = JText::_('PLG_BFSECURIMAGE_NEW_CHALLENGE');
		$options['refresh_title_text'] = $options['refresh_alt_text'];
		$options['input_text'] = JText::_('PLG_BFSECURIMAGE_VERIFY_CHALLENGE');
		$options['input_id'] = JText::_('PLG_BFSECURIMAGE_RESPONSEFIELD_DEFAULT');

		require_once __DIR__ . '/bfsecurimagehelper.php';
		$securImage = plgCaptchaBFSecurimageHelper::getSecureimageInstance();
		return $securImage->getCaptchaHtml($options);
	}

	/**
	 * Verifies if the user's guess was correct
	 *
	 * @return  True if the answer is correct, false otherwise
	 */
	function onCheckAnswer($code)
	{
		$this->responseField = $this->params->get('responsefield', JText::_('PLG_BFSECURIMAGE_RESPONSEFIELD_DEFAULT'));
		$solution = JRequest::getString($this->responseField);
		if (empty($solution))
		{
			$this->_subject->setError(JText::_('PLG_BFSECURIMAGE_ERROR_EMPTY_SOLUTION'));
			return false;
		}

		require_once __DIR__ . '/bfsecurimagehelper.php';
		$securImage = plgCaptchaBFSecurimageHelper::getSecureimageInstance();
		$result = $securImage->check($solution);
		if (!$result)
		{
			$this->_subject->setError(JText::_('PLG_BFSECURIMAGE_ERROR_INCORRECT_CAPTCHA_SOL'));
			return false;
		}

		return $result;
	}
}

?>