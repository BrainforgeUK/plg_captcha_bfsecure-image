<?php
/**
 * @package      CAPTCHA plugin using Securimage
 * @subpackage   plg_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Classes\SecurimageColorClass;

\defined('_JEXEC') or die;

/*
 */
trait BfsecurimageColorsTrait
{
	/**
	 * Allocate the colors to be used for the image
	 */
	protected function allocateColors($im)
	{
		$image_bg_color  = $this->initColor('image_bg_color',  '#ffffff');
		$text_color      = $this->initColor('text_color',      '#616161');
		$line_color      = $this->initColor('line_color',      '#616161');
		$noise_color     = $this->initColor('noise_color',     '#616161');
		$signature_color = $this->initColor('signature_color', '#616161');

		// allocate bg color first for imagecreate
		$this->gdbgcolor = imagecolorallocate($im,
			$image_bg_color->r,
			$image_bg_color->g,
			$image_bg_color->b);

		$alpha = intval($this->textTransparencyPercentage / 100 * 127);

		if ($this->useTransparentText)
		{
			$this->gdtextcolor = imagecolorallocatealpha($im,
				$text_color->r,
				$text_color->g,
				$text_color->b,
				$alpha);
			$this->gdlinecolor = imagecolorallocatealpha($im,
				$line_color->r,
				$line_color->g,
				$line_color->b,
				$alpha);
			$this->gdnoisecolor = imagecolorallocatealpha($im,
				$noise_color->r,
				$noise_color->g,
				$noise_color->b,
				$alpha);
		}
		else
		{
			$this->gdtextcolor = imagecolorallocate($im,
				$text_color->r,
				$text_color->g,
				$text_color->b);
			$this->gdlinecolor = imagecolorallocate($im,
				$line_color->r,
				$line_color->g,
				$line_color->b);
			$this->gdnoisecolor = imagecolorallocate($im,
				$noise_color->r,
				$noise_color->g,
				$noise_color->b);
		}

		$this->gdsignaturecolor = imagecolorallocate($im,
			$signature_color->r,
			$signature_color->g,
			$signature_color->b);
	}

	/**
	 * Convert an html color code to a Securimage_Color
	 * @param string $color
	 * @param SecurimageColorClass|string $default The defalt color to use if $color is invalid
	 */
	protected function initColor($color, $default)
	{
		$color = $this->pluginParams->get($color);

		if ($color == null) return new SecurimageColorClass($default);

		try
		{
			return new SecurimageColorClass($color);
		}
		catch(\Exception $e)
		{
			return new SecurimageColorClass($default);
		}
	}

	/**
	 * The the background color, or background image to be used
	 */
	protected function setBackground()
	{
		// set background color of image by drawing a rectangle since imagecreatetruecolor doesn't set a bg color
		imagefilledrectangle($this->img, 0, 0,
			$this->imgWidth, $this->imgHeight,
			$this->gdbgcolor);
		if ($this->perturbation > 0)
		{
			imagefilledrectangle($this->tmpimg, 0, 0,
				intval($this->imgWidth * $this->iScale), intval($this->imgHeight * $this->iScale),
				$this->gdbgcolor);
		}

		if ($this->useBackgroundImage)
		{
			$this->backgroundImage = BfsecurimageColorsTrait::getBackgroundFromDirectory();
		}

		if (empty($this->backgroundImage)) return;

		$imageSize = @getimagesize($this->backgroundImage);
		if($imageSize == false) return;

		switch($imageSize[2])
		{
			case 1:  $newImage = @imagecreatefromgif($this->backgroundImage); break;
			case 2:  $newImage = @imagecreatefromjpeg($this->backgroundImage); break;
			case 3:  $newImage = @imagecreatefrompng($this->backgroundImage); break;
			default: return;
		}

		if(!$newImage) return;

		imagecopyresized($this->img, $newImage, 0, 0, 0, 0,
			$this->imgWidth, $this->imgHeight,
			imagesx($newImage), imagesy($newImage));
	}
}
?>