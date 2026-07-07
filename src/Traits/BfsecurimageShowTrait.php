<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper\BfsecurimageCodeHelper;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper\BfsecurimageHelper;

\defined('_JEXEC') or die;

Trait BfsecurimageShowTrait
{
	/**
	 * The GD image resource of the captcha image
	 *
	 * @var resource
	 */
	protected \GdImage $img;

	/**
	 * A temporary GD image resource of the captcha image for distortion
	 *
	 * @var resource
	 */
	protected \GdImage $tmpimg;

	/**
	 * Include background image
	 *
	 * @var bool
	 */
	protected bool $useBackgroundImage;

	/**
	 * The background image GD resource
	 *
	 * @var string
	 */
	protected string|null $backgroundImage;

	/**
	 * The GD color for the background color
	 *
	 * @var int
	 */
	protected int|false $gdbgcolor;

	/**
	 * The GD color for the text color
	 *
	 * @var int
	 */
	protected int|false $gdtextcolor;

	/**
	 * The GD color for the line color
	 *
	 * @var int
	 */
	protected int|false $gdlinecolor;

	/**
	 * The GD color for the noise color
	 *
	 * @var int
	 */
	protected int|false $gdnoisecolor;

	/**
	 * The GD color for the signature text color
	 *
	 * @var int
	 */
	protected int|false $gdsignaturecolor;

	/**
	 * The width of the captcha image
	 *
	 * @var int
	 */
	protected int $imgWidth;

	/**
	 * The height of the captcha image
	 *
	 * @var int
	 */
	protected int $imgHeight;

	// The level of distortion.
	// 75 = normal, 100 = very high distortion
	protected int $perturbation;

	// Scale factor for magnification of distorted captcha image
	protected float $iScale;

	/**
	 * How transparent to make the text.
	 *
	 * 0 = completely opaque, 100 = invisible
	 *
	 * @var int
	 */
	protected int $textTransparencyPercentage;

	/**
	 * Whether or not to draw the text transparently.
	 *
	 * true = use transparency, false = no transparency
	 *
	 * @var bool
	 */
	protected bool $useTransparentText;

	/**
	 * The character set to use for generating the captcha code
	 *
	 * @var string
	 */
	protected string $charset;

	/**
	 * The level of noise (random dots) to place on the image, 0-10
	 *
	 * @var int
	 */
	protected int $noiseLevel;

	/**
	 * How many lines to draw over the captcha code to increase security

	 * @var int
	 */
	protected int $numLines;

	/**
	 * The TTF font file to use to draw the captcha code.
	 *
	 * Leave blank for default font AHGBold.ttf
	 *
	 * @var string
	 */
	protected $ttfFile;

	/**
	 * The path to the ttf font file to use for the signature text.
	 * Defaults to $ttf_file (AHGBold.ttf)
	 *
	 * @var string
	 */
	protected string|null $signatureFont;

	/**
	 * The signature text to draw on the bottom corner of the image
	 *
	 * @var string
	 */
	protected $imageSignature = '';

	/**
	 * Font size is calculated by image height and this ratio.
	 *
	 * Valid range: 1 - 99.
	 *
	 * Depending on imgWidth, values > 6 are probably too large and values < 3 are too small.
	 *
	 * @var int
	 */
	protected int $fontRatio;

	/**
	 * The type of the image, png / jpeg / gif
	 *
	 * @var string
	 */
	protected string $imageType;

	/*
	 */
	public function show($config=[])
	{
		$this->loadPluginShowParams($config);

		$this->img = imagecreatetruecolor($this->imgWidth, $this->imgHeight);

		imageantialias($this->img, true);

		$this->allocateColors($this->img);

		if ($this->perturbation > 0)
		{
			$this->tmpimg = imagecreatetruecolor(intval($this->imgWidth * $this->iScale), intval($this->imgHeight * $this->iScale));
			imagepalettecopy($this->tmpimg, $this->img);
		}
		else
		{
			$this->iScale = 1;
		}

		$this->setBackground();

		$this->code = BfsecurimageHelper::generateCode($this->charset, $this->codeLength);

		$this->codeDisplay = $this->code;
		$this->code        = ($this->caseSensitive) ? $this->code : strtolower($this->code);

		BfsecurimageCodeHelper::saveCode($this->getSession(), $this->code, $this->caseSensitive);

		if ($this->noiseLevel > 0)
		{
			$this->drawNoise();
		}

		$this->drawWord();

		if ($this->perturbation > 0)
		{
			$this->distortedCopy();
		}

		if ($this->numLines > 0)
		{
			$this->drawLines();
		}

		if (!empty($this->imageSignature))
		{
			$this->addSignature();
		}

		BfsecurimageHelper::outputImage($this->img, $this->imageType, $this->sendHeaders);

		$this->close();
	}

	/*
	 */
	protected function loadPluginShowParams($config=[])
	{
		$params = $this->loadPluginParams();

		$this->imgWidth = $params->get('imgWidth', 215);
		$this->imgHeight = $params->get('imgHeight', 80);

		$this->perturbation = $params->get('perturbation', 85);

		$this->iScale = $params->get('iScale', 85) / 100;

		$this->textTransparencyPercentage = $params->get('textTransparencyPercentage', 20);

		$this->useTransparentText = $params->get('textTransparentText', true);

		$this->useBackgroundImage = $params->get('useBackgroundImage', false);

		$this->codeLength = $params->get('codeLength', 6);

		$this->caseSensitive = $params->get('caseSensitive', false);

		$this->charset = $params->get('charset', 'abcdefghijkmnopqrstuvwxzyABCDEFGHJKLMNPQRSTUVWXZY0123456789');

		$this->noiseLevel = $params->get('noiseLevel', 2);

		$this->numLines = $params->get('numLines', 5);

		$this->ttfFile = dirname(__DIR__, 2) . '/files/ttffonts/' .
			$params->get('ttfFile', 'AHGBold') . '.ttf';

		$this->signatureFont = $params->get('signatureFont', null);

		$this->imageSignature = $params->get('imageSignature', null);

		$this->fontRatio = $params->get('fontRatio', 40);

		$this->sendHeaders = $params->get('sendHeaders', true);

		$this->imageType = $params->get('imageType', 'png');

		$this->useRandomSpaces = $params->get('useRandomSpaces', false);

		$this->useTextAngles = $params->get('useTextAngles', false);

		$this->useRandomBaseline = $params->get('useRandomBaseline', false);

		$this->useRandomBoxes = $params->get('useRandomBoxes', false);

		foreach($config as $key => $value)
		{
			$this->$key = $value;
		}

		if ($this->perturbation > 100)
		{
			$this->perturbation = 100;
		}

		if ($this->textTransparencyPercentage > 100)
		{
			$this->textTransparencyPercentage = 100;
		}

		if ($this->noiseLevel > 10)
		{
			$this->noiseLevel = 10;
		}

		if ($this->fontRatio > 99)
		{
			$this->fontRatio = 99;
		}
	}

	/**
	 * Draws random noise on the image
	 */
	protected function drawNoise()
	{
		$noiseLevel = $this->noiseLevel * M_LOG2E;

		for ($x = 1; $x < $this->imgWidth; $x += 20)
		{
			for ($y = 1; $y < $this->imgHeight; $y += 20)
			{
				for ($i = 0; $i < $noiseLevel; ++$i)
				{
					$x1 = mt_rand($x, $x + 20);
					$y1 = mt_rand($y, $y + 20);
					$size = mt_rand(1, 3);

					if ($x1 - $size <= 0 && $y1 - $size <= 0) continue; // don't cover 0,0 since it is used by imagedistortedcopy
					imagefilledarc($this->img, $x1, $y1, $size, $size, 0, mt_rand(180,360), $this->gdlinecolor, IMG_ARC_PIE);
				}
			}
		}
	}

	/**
	 * Copies the captcha image to the final image with distortion applied
	 */
	protected function distortedCopy()
	{
		$numpoles = 3;       // distortion factor
		$px       = array(); // x coordinates of poles
		$py       = array(); // y coordinates of poles
		$rad      = array(); // radius of distortion from pole
		$amp      = array(); // amplitude
		$x        = ($this->imgWidth / 4); // lowest x coordinate of a pole
		$maxX     = $this->imgWidth - $x;  // maximum x coordinate of a pole
		$dx       = mt_rand(intval($x / 10), intval($x));     // horizontal distance between poles
		$y        = mt_rand(20, $this->imgHeight - 20);  // random y coord
		$dy       = mt_rand(20, $this->imgHeight * 0.7); // y distance
		$minY     = 20;                                     // minimum y coordinate
		$maxY     = $this->imgHeight - 20;               // maximum y cooddinate

		// make array of poles AKA attractor points
		for ($i = 0; $i < $numpoles; ++ $i)
		{
			$px[$i]  = intval($x + floatval($dx * $i)) % intval($maxX);
			$py[$i]  = intval($y + floatval($dy * $i)) % intval($maxY + $minY);
			$rad[$i] = mt_rand(intval($this->imgHeight * 0.4), intval($this->imgHeight * 0.8));
			$tmp     = ((- BfsecurimageHelper::frand()) * 0.15) - .15;
			$amp[$i] = $this->perturbation * $tmp /100;
		}

		$bgCol   = imagecolorat($this->tmpimg, 0, 0);
		$width2  = $this->iScale * $this->imgWidth;
		$height2 = $this->iScale * $this->imgHeight;
		imagepalettecopy($this->img, $this->tmpimg); // copy palette to final image so text colors come across

		// loop over $img pixels, take pixels from $tmpimg with distortion field
		for ($ix = 0; $ix < $this->imgWidth; ++ $ix)
		{
			for ($iy = 0; $iy < $this->imgHeight; ++ $iy)
			{
				$x = $ix;
				$y = $iy;
				for ($i = 0; $i < $numpoles; ++ $i)
				{
					$dx = $ix - $px[$i];
					$dy = $iy - $py[$i];
					if ($dx == 0 && $dy == 0) continue;

					$r = sqrt($dx * $dx + $dy * $dy);
					if ($r > $rad[$i]) continue;

					$rscale = $amp[$i] * sin(3.14 * $r / $rad[$i]);
					$x += $dx * $rscale;
					$y += $dy * $rscale;
				}
				$c = $bgCol;
				$x *= $this->iScale;
				$y *= $this->iScale;
				if ($x >= 0 && $x < $width2 && $y >= 0 && $y < $height2)
				{
					$c = imagecolorat($this->tmpimg, intval($x), intval($y));
				}
				if ($c != $bgCol)
				{ // only copy pixels of letters to preserve any background image
					imagesetpixel($this->img, intval($ix), intval($iy), intval($c));
				}
			}
		}
	}

	/**
	 * Draws distorted lines on the image
	 */
	protected function drawLines()
	{
		for ($line = 0; $line < $this->numLines; ++ $line)
		{
			$x = $this->imgWidth * (1 + $line) / ($this->numLines + 1);
			$x += (0.5 - BfsecurimageHelper::frand()) * $this->imgWidth / $this->numLines;
			$y = mt_rand($this->imgHeight * 0.1, $this->imgHeight * 0.9);

			$theta = (BfsecurimageHelper::frand() - 0.5) * M_PI * 0.33;
			$w = $this->imgWidth;
			$len = mt_rand(intval($w * 0.4), intval($w * 0.7));
			$lwid = mt_rand(0, 2);

			$k = BfsecurimageHelper::frand() * 0.6 + 0.2;
			$k = $k * $k * 0.5;
			$phi = BfsecurimageHelper::frand() * 6.28;
			$step = 0.5;
			$dx = $step * cos($theta);
			$dy = $step * sin($theta);
			$n = $len / $step;
			$amp = 1.5 * BfsecurimageHelper::frand() / ($k + 5.0 / $len);
			$x0 = $x - 0.5 * $len * cos($theta);
			$y0 = $y - 0.5 * $len * sin($theta);

			$ldx = round(- $dy * $lwid);
			$ldy = round($dx * $lwid);

			for ($i = 0; $i < $n; ++ $i)
			{
				$x = intval($x0 + $i * $dx + $amp * $dy * sin($k * $i * $step + $phi));
				$y = intval($y0 + $i * $dy - $amp * $dx * sin($k * $i * $step + $phi));
				imagefilledrectangle($this->img, $x, $y, $x + $lwid, $y + $lwid, $this->gdlinecolor);
			}
		}
	}

	/**
	 * Draws the captcha code on the image
	 */
	protected function drawWord()
	{
		$fontRatio = $this->fontRatio / 100;

		if ($this->perturbation > 0)
		{
			$width     = $this->imgWidth * $this->iScale;
			$height    = $this->imgHeight * $this->iScale;
			$fontSize  = $height * $fontRatio;
			$img       = &$this->tmpimg;
			$scale     = $this->iScale;
		}
		else
		{
			$height    = $this->imgHeight;
			$width     = $this->imgWidth;
			$fontSize  = $this->imgHeight * $fontRatio;
			$img       = &$this->im;
			$scale     = 1;
		}

		$captchaText = $this->codeDisplay;

		if ($this->useRandomSpaces && BfsecurimageHelper::strpos($captchaText, ' ') === false)
		{
			if (mt_rand(1, 100) % 5 > 0)
			{ // ~20% chance no spacing added
				$index  = mt_rand(1, BfsecurimageHelper::strlen($captchaText) -1);
				$spaces = mt_rand(1, 3);

				// in general, we want all characters drawn close together to
				// prevent easy segmentation by solvers, but this adds random
				// spacing between two groups to make character positioning
				// less normalized.

				$captchaText = sprintf(
					'%s%s%s',
					BfsecurimageHelper::substr($captchaText, 0, $index),
					str_repeat(' ', $spaces),
					BfsecurimageHelper::substr($captchaText, $index)
				);
			}
		}

		$angles   = array();  // angles corresponding to each char $i
		$distance = array();  // distance from current char $i to previous char
		$dims     = array();  // dimensions of each individual char $i
		$txtWid   = 0;        // width of the entire text string, including spaces and distances

		// Character positioning and angle

		if ($this->useTextAngles)
		{
			$angle0 = mt_rand(10, 20);
			$angleN = mt_rand(-20, 10);
		}
		else
		{
			$angle0 = $angleN = $step = 0;
		}

		if (mt_rand(0, 99) % 2 == 0)
		{
			$angle0 = -$angle0;
		}
		if (mt_rand(0, 99) % 2 == 1)
		{
			$angleN = -$angleN;
		}

		$step   = abs($angle0 - $angleN) / (BfsecurimageHelper::strlen($captchaText) - 1);
		$step   = ($angle0 > $angleN) ? -$step : $step;
		$angle  = $angle0;

		for ($c = 0; $c < BfsecurimageHelper::strlen($captchaText); ++$c)
		{
			$angles[] = $angle;  // the angle of this character
			$dist     = mt_rand(-2, 0) * $scale; // random distance between this and next character
			$distance[] = $dist;
			$char     = BfsecurimageHelper::substr($captchaText, $c, 1); // the character to draw for this sequence

			$dim = BfsecurimageHelper::getCharacterDimensions($char, $fontSize, $angle, $this->ttfFile); // calculate dimensions of this character

			$dim[0] += $dist;   // add the distance to the dimension (negative to bring them closer)
			$txtWid += $dim[0]; // increment width based on character width

			$dims[] = $dim;

			$angle += $step; // next angle

			if ($angle > 20)
			{
				$angle = 20;
				$step  = $step * -1;
			}
			elseif ($angle < -20)
			{
				$angle = -20;
				$step  = -1 * $step;
			}
		}

		$nextYPos = function($y, $i, $step) use ($height, $scale, $dims)
		{
			static $dir = 1;

			if ($y + $step + $dims[$i][2] + (10 * $scale) > $height)
			{
				$dir = 0;
			}
			elseif
			($y - $step - $dims[$i][2] < $dims[$i][1] + $dims[$i][2] + (5 * $scale))
			{
				$dir = 1;
			}

			if ($dir)
			{
				$y += $step;
			}
			else
			{
				$y -= $step;
			}

			return $y;
		};

		$cx = floor($width / 2 - ($txtWid / 2));
		$x  = mt_rand(intval(5 * $scale), intval(max($cx * 2 - (5 * $scale), 5 * $scale)));

		if ($this->useRandomBaseline)
		{
			$y = mt_rand($dims[0][1], $height - 10);
		}
		else
		{
			$y = ($height / 2 + $dims[0][1] / 2 - $dims[0][2]);
		}

		$st = $scale * mt_rand(5, 10);

		for ($c = 0; $c < BfsecurimageHelper::strlen($captchaText); ++$c)
		{
			$char  = BfsecurimageHelper::substr($captchaText, $c, 1);
			$angle = $angles[$c];
			$dim   = $dims[$c];

			if ($this->useRandomBaseline)
			{
				$y = $nextYPos($y, $c, $st);
			}

			imagettftext(
				$img,
				$fontSize,
				$angle,
				(int)$x,
				(int)$y,
				$this->gdtextcolor,
				$this->ttfFile,
				$char
			);

			if ($this->useRandomBoxes && strlen(trim($char)) && mt_rand(1,100) % 5 == 0)
			{
				imagesetthickness($img, 3);
				imagerectangle($img, $x, $y - $dim[1] + $dim[2], $x + $dim[0], $y + $dim[2], $this->gdtextcolor);
			}

			if ($c == ' ')
			{
				$x += $dim[0];
			}
			else
			{
				$x += $dim[0] + $distance[$c];
			}
		}
	}

	/**
	 * Print signature text on image
	 */
	protected function addSignature()
	{
		$bbox = imagettfbbox(10, 0, $this->signature_font, $this->image_signature);
		$textlen = $bbox[2] - $bbox[0];
		$x = $this->imgWidth - $textlen - 5;
		$y = $this->imgHeight - 3;

		imagettftext($this->img, 10, 0, $x, $y, $this->gdsignaturecolor, $this->signatureFont, $this->imageSignature);
	}
}