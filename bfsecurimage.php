<?php
/**
 * @package		Joomla.Site
 * @subpackage	plg_secureimage
 * @copyright	Copyright (C) 2012 Jonathan Brain. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;
jimport('joomla.plugin.plugin');

class plgCaptchaBFSecurimage extends JPlugin{
  
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
	 * @param	string	$id	The id of the field.
	 *
	 * @return	Boolean	True on success, false otherwise
	 */
	public function onInit($id) {
    $this->responseField = $this->params->get('responsefield', 'bfsecurimage_response_field');
	  return true;
  }

	/**
	 * Gets the challenge HTML
	 *
	 * @return  string  The HTML to be embedded in the form.
	 */
	public function onDisplay($name, $id, $class) {
    if (empty($this->responseField)) {
  		JLog::add(JText::sprintf('JLIB_CAPTCHA_ERROR_PLUGIN_NOT_FOUND', $name), JLog::WARNING, 'jerror');
      return '';
    }
  
		$pluginPath = substr(__DIR__, strlen(JPATH_BASE));
	  $pluginPath = JURI::base() . str_replace('\\', '/', $pluginPath) . '/includes';
    $imageAlt = JText::_('PLG_BFSECURIMAGE_THE_IMAGE');
    $image = '<img id="' . $id . '" ' .
                  'src="' . $pluginPath . '/securimage_show.php" ' .
                  'alt="' . $imageAlt . '" title="' . $imageAlt . '" />';
    if ($this->params->get('audio')) {
      $audio = '<object title="' . JText::_('PLG_BFSECURIMAGE_AUDIO_CHALLENGE') . '" ' .
                       'width="22" height="24" tabindex="1" ' .
                       'data="' . $pluginPath . '/securimage_play.swf?audio=' . $pluginPath . '/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#999999" type="application/x-shockwave-flash"><param value="' . $pluginPath . '/securimage_play.swf?audio=' . $pluginPath . '/securimage_play.php&amp;bgColor1=#fff&amp;bgColor2=#fff&amp;iconColor=#777&amp;borderWidth=1&amp;borderColor=#000" name="movie"></object>';
    }
    else $audio = null;
    if ($this->params->get('refresh')) {
      $newChallenge = JText::_('PLG_BFSECURIMAGE_NEW_CHALLENGE');
      $refresh = '<a title="' . $newChallenge . '" ' .
                    'style="vertical-align: middle;" ' .
                    'onclick="document.getElementById(\'' . $id . '\').src = \'' . $pluginPath . '/securimage_show.php?sid=\' + Math.random(); return false" ' .
                    'href="#">' .
                 '<img border="0" align="bottom" onclick="this.blur()" alt="' . $newChallenge . '" src="' . $pluginPath . '/images/refresh.gif" /></a>';
    }
    else $refresh = null;

    return '<table class="' . trim('bfsecurimage ' . $class) . '">' .
           '<tr class="bfsi_challenge">' .
           '<td class="bfsi_challenge">' . $image . '</td>' .
           '<td class="bfsi_extras">' . $audio . '<br /><br />' . $refresh . '</td>' . 
           '</tr>' .
           '<tr class="bfsi_response">' .
           '<td class="bfsi_response" colspan="2">' .
           '<input class="required" autocomplete="off" title="' . JText::_('PLG_BFSECURIMAGE_VERIFY_CHALLENGE') . '" type="text" ' .
                  'name="' . $this->responseField . '" id="' . $this->responseField . '" size="10" maxlength="6" />' .
           '</td>' .
           '</tr>' .
           '</table>';
	}

	/**
	  * Verifies if the user's guess was correct
	  *
	  * @return  True if the answer is correct, false otherwise
	  */
  function onCheckAnswer($code) {
    $solution = JRequest::getString($this->responseField);
    if (empty($solution)) {
 			$this->_subject->setError(JText::_('PLG_BFSECURIMAGE_ERROR_EMPTY_SOLUTION'));
      return false;
    }
    
    require_once __DIR__.'/includes/securimage.php';
    $bfsecurimage = new Securimage();
    $result = $bfsecurimage->check($solution);
    if (!$result) {
 			$this->_subject->setError(JText::_('PLG_BFSECURIMAGE_ERROR_INCORRECT_CAPTCHA_SOL'));
      return false;
    }

    return $result;
  }
}
?>