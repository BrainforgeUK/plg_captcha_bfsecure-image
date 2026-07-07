<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Classes\SecurimageWavfileClass;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Exceptions\SecurimageWavfileException;

\defined('_JEXEC') or die;

Trait ProcessingTrait
{
	/**
	 * Add silence to the wav file.
	 *
	 * @param float $duration  (Optional) How many seconds of silence. If negative, add to the beginning of the file. Defaults to 1s.
	 */
	public function insertSilence($duration = 1.0)
	{
		$numSamples  = (int)($this->getSampleRate() * abs($duration));
		$numChannels = $this->getNumChannels();

		$data = str_repeat(self::packSample($this->getZeroAmplitude(), $this->getBitsPerSample()), $numSamples * $numChannels);
		if ($duration >= 0)
		{
			$this->_samples .= $data;
		}
		else
		{
			$this->_samples = $data . $this->_samples;
		}

		$this->setDataSize();  // implicit setSize(), setActualSize(), setNumBlocks()

		return $this;
	}

	/**
	 * Degrade the quality of the wav file by introducing random noise.
	 *
	 * @param float quality  (Optional) The quality relative to the amplitude. 1 = no noise, 0 = max. noise.
	 */
	public function degrade($quality = 1.0)
	{
		return $this->filter(array(
			self::FILTER_DEGRADE => $quality
		));
	}

	/**
	 * Generate noise at the end of the wav for the specified duration and volume.
	 *
	 * @param float $duration  (Optional) Number of seconds of noise to generate.
	 * @param float $percent  (Optional) The percentage of the maximum amplitude to use. 100 = full amplitude.
	 */
	public function generateNoise($duration = 1.0, $percent = 100)
	{
		$numChannels = $this->getNumChannels();
		$numSamples  = $this->getSampleRate() * $duration;
		$minAmp      = $this->getMinAmplitude();
		$maxAmp      = $this->getMaxAmplitude();
		$bitDepth    = $this->getBitsPerSample();

		for ($s = 0; $s < $numSamples; ++$s)
		{
			if ($bitDepth == 32)
			{
				$val = rand(-$percent * 10000, $percent * 10000) / 1000000;
			}
			else
			{
				$val = rand($minAmp, $maxAmp);
				$val = (int)($val * $percent / 100);
			}

			$this->_samples .= str_repeat(self::packSample($val, $bitDepth), $numChannels);
		}

		$this->setDataSize();  // implicit setSize(), setActualSize(), setNumBlocks()

		return $this;
	}

	/**
	 * Convert sample data to different bits per sample.
	 *
	 * @param int $bitsPerSample  (Required) The new number of bits per sample;
	 * @throws SecurimageWavfileException
	 */
	public function convertBitsPerSample($bitsPerSample)
	{
		if ($this->getBitsPerSample() == $bitsPerSample)
		{
			return $this;
		}

		$tempWav = new SecurimageWavfileClass($this->getNumChannels(), $this->getSampleRate(), $bitsPerSample);
		$tempWav->filter(
			array(self::FILTER_MIX => $this),
			0,
			$this->getNumBlocks()
		);

		$this->setSamples()                       // implicit setDataSize(), setSize(), setActualSize(), setNumBlocks()
		->setBitsPerSample($bitsPerSample);  // implicit setValidBitsPerSample(), setAudioFormat(), setAudioSubFormat(), setFmtChunkSize(), setFactChunkSize(), setSize(), setActualSize(), setDataOffset(), setByteRate(), setBlockAlign(), setNumBlocks()
		$this->_samples = $tempWav->_samples;
		$this->setDataSize();                     // implicit setSize(), setActualSize(), setNumBlocks()

		return $this;
	}
}