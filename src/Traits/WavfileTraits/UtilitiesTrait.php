<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits;

\defined('_JEXEC') or die;

Trait UtilitiesTrait
{
	/**
	 * Output information about the wav object.
	 */
	public function displayInfo()
	{
		$s = "File Size: %u\n"
			."Chunk Size: %u\n"
			."fmt Subchunk Size: %u\n"
			."Extended fmt Size: %u\n"
			."fact Subchunk Size: %u\n"
			."Data Offset: %u\n"
			."Data Size: %u\n"
			."Audio Format: %s\n"
			."Audio SubFormat: %s\n"
			."Channels: %u\n"
			."Channel Mask: 0x%s\n"
			."Sample Rate: %u\n"
			."Bits Per Sample: %u\n"
			."Valid Bits Per Sample: %u\n"
			."Sample Block Size: %u\n"
			."Number of Sample Blocks: %u\n"
			."Byte Rate: %uBps\n";

		$s = sprintf($s, $this->getActualSize(),
			$this->getChunkSize(),
			$this->getFmtChunkSize(),
			$this->getFmtExtendedSize(),
			$this->getFactChunkSize(),
			$this->getDataOffset(),
			$this->getDataSize(),
			$this->getAudioFormat() == self::WAVE_FORMAT_PCM ? 'PCM' : ($this->getAudioFormat() == self::WAVE_FORMAT_IEEE_FLOAT ? 'IEEE FLOAT' : 'EXTENSIBLE'),
			$this->getAudioSubFormat() == self::WAVE_SUBFORMAT_PCM ? 'PCM' : 'IEEE FLOAT',
			$this->getNumChannels(),
			dechex($this->getChannelMask()),
			$this->getSampleRate(),
			$this->getBitsPerSample(),
			$this->getValidBitsPerSample(),
			$this->getBlockAlign(),
			$this->getNumBlocks(),
			$this->getByteRate());

		if (php_sapi_name() == 'cli') return $s;

		return nl2br($s);
	}
}