<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Classes;

\defined('_JEXEC') or die;

class SecurimageColorClass
{
	/**
	 * Red value (0-255)
	 * @var int
	 */
	public int $r;

	/**
	 * Gree value (0-255)
	 * @var int
	 */
	public int $g;

	/**
	 * Blue value (0-255)
	 * @var int
	 */
	public int $b;

	/**
	 * Create a new Securimage_Color object.
	 *
	 * Constructor expects 1 or 3 arguments.
	 *
	 * When passing a single argument, specify the color using HTML hex format.
	 *
	 * When passing 3 arguments, specify each RGB component (from 0-255)
	 * individually.
	 *
	 * Examples:
	 *
	 *     $color = new Securimage_Color('#0080FF');
	 *     $color = new Securimage_Color(0, 128, 255);
	 *
	 * @param string $color  The html color code to use
	 * @throws \Exception  If any color value is not valid
	 */
	public function __construct($color = '#ffffff')
	{
		$args = func_get_args();

		switch(sizeof($args))
		{
			case 0:
				$this->r = 255;
				$this->g = 255;
				$this->b = 255;
				return;
			case 1:
				// set based on html code
				if (substr($color, 0, 1) == '#')
				{
					$color = substr($color, 1);
				}

				switch(strlen($color))
				{
					case 3:
					case 6:
						$this->constructHTML($color);
						return;
					default:
						throw new \InvalidArgumentException('Invalid HTML color code passed to Securimage_Color');
				}
			case 3:
				$this->constructRGB($args[0], $args[1], $args[2]);
				return;
			default:
				throw new \InvalidArgumentException( 'Securimage_Color constructor expects 0, 1 or 3 arguments; ' . sizeof($args) . ' given');
		}
	}

	public function toLongColor()
	{
		return ($this->r << 16) + ($this->g << 8) + $this->b;
	}

	public function fromLongColor($color)
	{
		$this->r = ($color >> 16) & 0xff;
		$this->g = ($color >>  8) & 0xff;
		$this->b =  $color        & 0xff;

		return $this;
	}

	/**
	 * Construct from an rgb triplet
	 *
	 * @param int $red The red component, 0-255
	 * @param int $green The green component, 0-255
	 * @param int $blue The blue component, 0-255
	 */
	protected function constructRGB($red, $green, $blue)
	{
		if ($red < 0)     $red   = 0;
		if ($red > 255)   $red   = 255;
		if ($green < 0)   $green = 0;
		if ($green > 255) $green = 255;
		if ($blue < 0)    $blue  = 0;
		if ($blue > 255)  $blue  = 255;

		$this->r = $red;
		$this->g = $green;
		$this->b = $blue;
	}

	/**
	 * Construct from an html hex color code
	 *
	 * @param string $color
	 */
	protected function constructHTML($color)
	{
		if (strlen($color) == 3) {
			$red   = str_repeat(substr($color, 0, 1), 2);
			$green = str_repeat(substr($color, 1, 1), 2);
			$blue  = str_repeat(substr($color, 2, 1), 2);
		} else {
			$red   = substr($color, 0, 2);
			$green = substr($color, 2, 2);
			$blue  = substr($color, 4, 2);
		}

		$this->r = hexdec($red);
		$this->g = hexdec($green);
		$this->b = hexdec($blue);
	}
}
