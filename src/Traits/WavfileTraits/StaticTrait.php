<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits;

\defined('_JEXEC') or die;

Trait StaticTrait
{
	/**
	 * Unpacks a single binary sample to numeric value.
	 *
	 * @param string $sampleBinary  (Required) The sample to decode.
	 * @param int $bitDepth  (Optional) The bits per sample to decode. If omitted, derives it from the length of $sampleBinary.
	 * @return int|float|null  The numeric sample value. Float for 32-bit samples. Returns null for unsupported bit depths.
	 */
	protected static function unpackSample($sampleBinary, $bitDepth = null)
	{
		if ($bitDepth === null)
		{
			$bitDepth = strlen($sampleBinary) * 8;
		}

		switch ($bitDepth)
		{
			case 8:
				// unsigned char
				return ord($sampleBinary);

			case 16:
				// signed short, little endian
				$data = unpack('v', $sampleBinary);
				$sample = $data[1];
				if ($sample >= 0x8000) {
					$sample -= 0x10000;
				}
				return $sample;

			case 24:
				// 3 byte packed signed integer, little endian
				$data = unpack('C3', $sampleBinary);
				$sample = $data[1] | ($data[2] << 8) | ($data[3] << 16);
				if ($sample >= 0x800000) {
					$sample -= 0x1000000;
				}
				return $sample;

			case 32:
				// 32-bit float
				$data = unpack('f', $sampleBinary);
				return $data[1];

			default:
				return null;
		}
	}

	/**
	 * Packs a single numeric sample to binary.
	 *
	 * @param int|float $sample  (Required) The sample to encode. Has to be within valid range for $bitDepth. Float values only for 32 bits.
	 * @param int $bitDepth  (Required) The bits per sample to encode with.
	 * @return string|null  The encoded binary sample. Returns null for unsupported bit depths.
	 */
	protected static function packSample($sample, $bitDepth)
	{
		switch ($bitDepth)
		{
			case 8:
				// unsigned char
				return chr($sample);

			case 16:
				// signed short, little endian
				if ($sample < 0) {
					$sample += 0x10000;
				}
				return pack('v', $sample);

			case 24:
				// 3 byte packed signed integer, little endian
				if ($sample < 0) {
					$sample += 0x1000000;
				}
				return pack('C3', $sample & 0xff, ($sample >>  8) & 0xff, ($sample >> 16) & 0xff);

			case 32:
				// 32-bit float
				return pack('f', $sample);

			default:
				return null;
		}
	}

	/**
	 * Unpacks a binary sample block to numeric values.
	 *
	 * @param string $sampleBlock  (Required) The binary sample block (all channels).
	 * @param int $bitDepth  (Required) The bits per sample to decode.
	 * @param int $numChannels  (Optional) The number of channels to decode. If omitted, derives it from the length of $sampleBlock and $bitDepth.
	 * @return array  The sample values as an array of integers of floats for 32 bits. First channel is array index 1.
	 */
	protected static function unpackSampleBlock($sampleBlock, $bitDepth, $numChannels = null)
	{
		$sampleBytes = $bitDepth / 8;
		if ($numChannels === null)
		{
			$numChannels = strlen($sampleBlock) / $sampleBytes;
		}

		$samples = array();
		for ($i = 0; $i < $numChannels; $i++)
		{
			$sampleBinary = substr($sampleBlock, $i * $sampleBytes, $sampleBytes);
			$samples[$i + 1] = self::unpackSample($sampleBinary, $bitDepth);
		}

		return $samples;
	}

	/**
	 * Packs an array of numeric channel samples to a binary sample block.
	 *
	 * @param array $samples  (Required) The array of channel sample values. Expects float values for 32 bits and integer otherwise.
	 * @param int $bitDepth  (Required) The bits per sample to encode with.
	 * @return string  The encoded binary sample block.
	 */
	protected static function packSampleBlock($samples, $bitDepth)
	{
		$sampleBlock = '';
		foreach($samples as $sample)
		{
			$sampleBlock .= self::packSample($sample, $bitDepth);
		}

		return $sampleBlock;
	}

	/**
	 * Normalizes a float audio sample. Maximum input range assumed for compression is [-2, 2].
	 * See http://www.voegler.eu/pub/audio/ for more information.
	 *
	 * @param float $sampleFloat  (Required) The float sample to normalize.
	 * @param float $threshold  (Required) The threshold or gain factor for normalizing the amplitude. <ul>
	 *     <li> >= 1 - Normalize by multiplying by the threshold (boost - positive gain). <br />
	 *            A value of 1 in effect means no normalization (and results in clipping). </li>
	 *     <li> <= -1 - Normalize by dividing by the the absolute value of threshold (attenuate - negative gain). <br />
	 *            A factor of 2 (-2) is about 6dB reduction in volume.</li>
	 *     <li> [0, 1) - (open inverval - not including 1) - The threshold
	 *            above which amplitudes are comressed logarithmically. <br />
	 *            e.g. 0.6 to leave amplitudes up to 60% "as is" and compress above. </li>
	 *     <li> (-1, 0) - (open inverval - not including -1 and 0) - The threshold
	 *            above which amplitudes are comressed linearly. <br />
	 *            e.g. -0.6 to leave amplitudes up to 60% "as is" and compress above. </li></ul>
	 * @return float  The normalized sample.
	 **/
	protected static function normalizeSample($sampleFloat, $threshold)
	{
		// apply positive gain
		if ($threshold >= 1) return $sampleFloat * $threshold;

		// apply negative gain
		if ($threshold <= -1) return $sampleFloat / -$threshold;

		$sign = $sampleFloat < 0 ? -1 : 1;
		$sampleAbs = abs($sampleFloat);

		// logarithmic compression
		if ($threshold >= 0 && $threshold < 1 && $sampleAbs > $threshold)
		{
			$loga = self::$LOOKUP_LOGBASE[(int)($threshold * 20)]; // log base modifier
			return $sign * ($threshold + (1 - $threshold) * log(1 + $loga * ($sampleAbs - $threshold) / (2 - $threshold)) / log(1 + $loga));
		}

		// linear compression
		$thresholdAbs = abs($threshold);
		if ($threshold > -1 && $threshold < 0 && $sampleAbs > $thresholdAbs)
		{
			return $sign * ($thresholdAbs + (1 - $thresholdAbs) / (2 - $thresholdAbs) * ($sampleAbs - $thresholdAbs));
		}

		return $sampleFloat;
	}
}