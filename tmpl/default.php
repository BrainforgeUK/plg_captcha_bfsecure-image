<?php
/**
 * @package   CAPTCHA plugin using Securimage
 * @author    https://www.brainforge.co.uk
 * @version   0.0.1
 * @author    https://www.brainforge.co.uk
 * @copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/** @var Registry $params */

\defined('_JEXEC') or die;

$captchaKey = random_int(0,999999999999);

$mediaUri = Uri::root() . 'media/plg_captcha_bfsecurimage/';
$pluginUri = Uri::root() . 'plugins/captcha/bfsecurimage/';
$imageSrc = $pluginUri . 'index.php?task=show&key=' . $captchaKey;
$playSrc = $pluginUri . 'index.php?task=play&key=' . $captchaKey;

?>
<table>
    <tr>
        <td class="captcha_bfsecurimage_image">
            <label for="bfsecurimage_response_field">
                <img id="captcha_bfsecurimage_image"
                     class="bfcaptcha_image"
                     src="<?php echo $imageSrc; ?>&<?php echo time(); ?>"
                     alt="<?php echo Text::_('PLG_CAPTCHA_BFSECURIMAGE_IMAGE_CHALLENGE'); ?>"
                />
            </label>
        </td>
        <td class="captcha_bfsecurimage_buttons">
            <?php
			if (intval($params->get('audio')))
            {
                $source = '<source id="captcha_image_source_wav" src="%s" type="audio/wav" />';

                $audio = '
                    <audio id="captcha_image_audio"
                           preload="none"
                           style="display: none"
                           onended="bfsecurimageAudioEnded(this);"
                           data-audioicon="' . $mediaUri . 'images/audio_icon.png"
                           data-loadingicon="' . $mediaUri . 'images/loading.png"
                           data-source="' . base64_encode($source) . '"
                    >
                    %s
                    </audio>
                ';
                ?>
                <div id="captcha_image_audio_div"
                     data-audio="<?php echo base64_encode($audio); ?>"
                     data-source="<?php echo base64_encode($source); ?>"
                >
                    <?php
                    echo sprintf($audio, sprintf($source, $playSrc . '&' . time()));
                    ?>
                </div>
                <div id="captcha_bfsecurimage_audio_button"
                     class="captcha_bfsecurimage_button"
                >
                    <a tabindex="-1"
                       href="#"
                       title="<?php echo Text::_('PLG_CAPTCHA_BFSECURIMAGE_REFRESH_CHALLENGE'); ?>"
                       onclick="return bfsecurimagePlay(this);"
                    >
                        <img id="captcha_bfsecurimage_play_image"
                             class="captcha_bfsecurimage_play_image"
                             src="<?php echo $mediaUri; ?>images/audio_icon.png"
                             title="<?php echo Text::_('PLG_CAPTCHA_BFSECURIMAGE_AUDIO_CHALLENGE'); ?>"
                             alt="<?php echo Text::_('PLG_CAPTCHA_BFSECURIMAGE_AUDIO_CHALLENGE'); ?>"
                        />
                    </a>
                </div>
                <?php
            }
			if (intval($params->get('refresh')))
			{
                ?>
                <div class="captcha_bfsecurimage_button"
                >
                    <a tabindex="-1"
                       href="#"
                       title="<?php echo Text::_('PLG_CAPTCHA_BFSECURIMAGE_REFRESH_CHALLENGE'); ?>"
                       onclick="return bfsecurimageRefresh(this);"
                       data-imgsrc="<?php echo $imageSrc; ?>"
                       data-playsrc="<?php echo $playSrc; ?>"
                    >
                        <img src="<?php echo $mediaUri; ?>images/refresh.png"
                             alt="<?php echo Text::_('PLG_CAPTCHA_BFSECURIMAGE_REFRESH_CHALLENGE'); ?>"
                        />
                    </a>
                </div>
                <?php
			}
            ?>
        </td>
    </tr>
    <tr>
        <td  class="captcha_bfsecurimage_response"
             colspan="2">
            <label for="bfsecurimage-captcha-response"><?php echo Text::_('PLG_CAPTCHA_BFSECURIMAGE_VERIFY_CHALLENGE'); ?></label>
            <input type="text"
                   name="bfsecurimage-captcha-response"
                   id="bfsecurimage-captcha-response"
                   autocomplete="off"
                   required="required"
            />
            <input type="hidden"
                   name="bfsecurimage-captcha-key"
                   value="<?php echo $captchaKey; ?>"
            />
        </td>
    </tr>
</table>

<link type="text/css"
      href="<?php echo $mediaUri; ?>css/bfsecurimage.css"
      rel="stylesheet"
/>

<script type="text/javascript"
        src="<?php echo $mediaUri; ?>js/bfsecurimage.js">
</script>
