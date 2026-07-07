<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Exceptions\SecurimageWavfileException;

\defined('_JEXEC') or die;

Trait GetsetTrait
{
	/*
	 */
	public function getActualSize()
	{
		return $this->_actualSize;
	}

	/**
	 * @param int $actualSize
	 */
	protected function setActualSize($actualSize = null) {
		if (is_null($actualSize))
		{
			$this->_actualSize = 8 + $this->_chunkSize;  // + "RIFF" header (ID + size)
		}
		else
		{
			$this->_actualSize = $actualSize;
		}

		return $this;
	}

	/*
	 */
	public function getChunkSize()
	{
		return $this->_chunkSize;
	}

	/** @param int $chunkSize */
	protected function setChunkSize($chunkSize = null)
	{
		if (is_null($chunkSize))
		{
			$this->_chunkSize = 4 +                                                            // "WAVE" chunk
				8 + $this->_fmtChunkSize +                                     // "fmt " subchunk
				($this->_factChunkSize > 0 ? 8 + $this->_factChunkSize : 0) +  // "fact" subchunk
				8 + $this->_dataSize +                                         // "data" subchunk
				($this->_dataSize & 1);                                        // padding byte
		}
		else
		{
			$this->_chunkSize = $chunkSize;
		}

		$this->setActualSize();

		return $this;
	}

	/*
	 */
	public function getFmtChunkSize()
	{
		return $this->_fmtChunkSize;
	}

	/** @param int $fmtChunkSize */
	protected function setFmtChunkSize($fmtChunkSize = null)
	{
		if (is_null($fmtChunkSize))
		{
			$this->_fmtChunkSize = 16 + $this->_fmtExtendedSize;
		}
		else
		{
			$this->_fmtChunkSize = $fmtChunkSize;
		}

		$this->setChunkSize()    // implicit setActualSize()
		->setDataOffset();

		return $this;
	}

	/*
	 */
	public function getFmtExtendedSize()
	{
		return $this->_fmtExtendedSize;
	}

	/**
	 * @param int $fmtExtendedSize
	 */
	protected function setFmtExtendedSize($fmtExtendedSize = null)
	{
		if (is_null($fmtExtendedSize))
		{
			if ($this->_audioFormat == self::WAVE_FORMAT_EXTENSIBLE)
			{
				$this->_fmtExtendedSize = 2 + 22;                          // extension size for WAVE_FORMAT_EXTENSIBLE
			}
			elseif ($this->_audioFormat != self::WAVE_FORMAT_PCM)
			{
				$this->_fmtExtendedSize = 2 + 0;                           // empty extension
			}
			else
			{
				$this->_fmtExtendedSize = 0;                               // no extension, only for WAVE_FORMAT_PCM
			}
		}
		else
		{
			$this->_fmtExtendedSize = $fmtExtendedSize;
		}

		$this->setFmtChunkSize();  // implicit setSize(), setActualSize(), setDataOffset()

		return $this;
	}

	public function getFactChunkSize()
	{
		return $this->_factChunkSize;
	}

	/** @param int $factChunkSize */
	protected function setFactChunkSize($factChunkSize = null) {
		if (is_null($factChunkSize))
		{
			if ($this->_audioFormat != self::WAVE_FORMAT_PCM)
			{
				$this->_factChunkSize = 4;
			}
			else
			{
				$this->_factChunkSize = 0;
			}
		}
		else
		{
			$this->_factChunkSize = $factChunkSize;
		}

		$this->setChunkSize()    // implicit setActualSize()
		->setDataOffset();

		return $this;
	}

	public function getDataSize()
	{
		return $this->_dataSize;
	}

	/** @param int $dataSize */
	protected function setDataSize($dataSize = null)
	{
		if (is_null($dataSize)) {
			$this->_dataSize = strlen($this->_samples);
		}
		else
		{
			$this->_dataSize = $dataSize;
		}

		$this->setChunkSize()   // implicit setActualSize()
		->setNumBlocks();
		$this->_dataSize_valid = true;

		return $this;
	}

	public function getDataOffset()
	{
		return $this->_dataOffset;
	}

	/**
	 * @param int $dataOffset
	 */
	protected function setDataOffset($dataOffset = null)
	{
		if (is_null($dataOffset))
		{
			$this->_dataOffset = 8 +                                                            // "RIFF" header (ID + size)
				4 +                                                            // "WAVE" chunk
				8 + $this->_fmtChunkSize +                                     // "fmt " subchunk
				($this->_factChunkSize > 0 ? 8 + $this->_factChunkSize : 0) +  // "fact" subchunk
				8;                                                             // "data" subchunk
		}
		else
		{
			$this->_dataOffset = $dataOffset;
		}

		return $this;
	}

	/*
	 */
	public function getAudioFormat() {
		return $this->_audioFormat;
	}

	/**
	 * @param int $audioFormat
	 */
	protected function setAudioFormat($audioFormat = null)
	{
		if (is_null($audioFormat))
		{
			if (($this->_bitsPerSample <= 16 || $this->_bitsPerSample == 32)
				&& $this->_validBitsPerSample == $this->_bitsPerSample
				&& $this->_channelMask == self::SPEAKER_DEFAULT
				&& $this->_numChannels <= 2)
			{
				if ($this->_bitsPerSample <= 16)
				{
					$this->_audioFormat = self::WAVE_FORMAT_PCM;
				}
				else
				{
					$this->_audioFormat = self::WAVE_FORMAT_IEEE_FLOAT;
				}
			}
			else
			{
				$this->_audioFormat = self::WAVE_FORMAT_EXTENSIBLE;
			}
		}
		else
		{
			$this->_audioFormat = $audioFormat;
		}

		$this->setAudioSubFormat()
			->setFactChunkSize()     // implicit setSize(), setActualSize(), setDataOffset()
			->setFmtExtendedSize();  // implicit setFmtChunkSize(), setSize(), setActualSize(), setDataOffset()

		return $this;
	}

	/*
	 */
	public function getAudioSubFormat()
	{
		return $this->_audioSubFormat;
	}

	/**
	 * @param int $audioSubFormat
	 */
	protected function setAudioSubFormat($audioSubFormat = null)
	{
		if (is_null($audioSubFormat))
		{
			if ($this->_bitsPerSample == 32)
			{
				$this->_audioSubFormat = self::WAVE_SUBFORMAT_IEEE_FLOAT;  // 32 bits are IEEE FLOAT in this class
			}
			else
			{
				$this->_audioSubFormat = self::WAVE_SUBFORMAT_PCM;         // 8, 16 and 24 bits are PCM in this class
			}
		}
		else
		{
			$this->_audioSubFormat = $audioSubFormat;
		}

		return $this;
	}

	/*
	 */
	public function getNumChannels()
	{
		return $this->_numChannels;
	}

	/**
	 * @param int $numChannels
	 */
	public function setNumChannels($numChannels)
	{
		if ($numChannels < 1 || $numChannels > self::MAX_CHANNEL)
		{
			throw new SecurimageWavfileException('Unsupported number of channels. Only up to ' . self::MAX_CHANNEL . ' channels are supported.');
		}

		if ($this->_samples !== '')
		{
			trigger_error('Wav already has sample data. Changing the number of channels does not convert and may corrupt the data.', E_USER_NOTICE);
		}

		$this->_numChannels = (int)$numChannels;

		$this->setAudioFormat()  // implicit setAudioSubFormat(), setFactChunkSize(), setFmtExtendedSize(), setFmtChunkSize(), setSize(), setActualSize(), setDataOffset()
		->setByteRate()
			->setBlockAlign();  // implicit setNumBlocks()

		return $this;
	}

	/*
	 */
	public function getChannelMask()
	{
		return $this->_channelMask;
	}

	/*
	 */
	public function setChannelMask($channelMask = self::SPEAKER_DEFAULT)
	{
		if ($channelMask != 0)
		{
			// count number of set bits - Hamming weight
			$c = (int)$channelMask;
			$n = 0;
			while ($c > 0)
			{
				$n += $c & 1;
				$c >>= 1;
			}
			if ($n != $this->_numChannels || (((int)$channelMask | self::SPEAKER_ALL) != self::SPEAKER_ALL))
			{
				throw new SecurimageWavfileException('Invalid channel mask. The number of channels does not match the number of locations in the mask.');
			}
		}

		$this->_channelMask = (int)$channelMask;

		$this->setAudioFormat();  // implicit setAudioSubFormat(), setFactChunkSize(), setFmtExtendedSize(), setFmtChunkSize(), setSize(), setActualSize(), setDataOffset()

		return $this;
	}

	/*
	 */
	public function getSampleRate()
	{
		return $this->_sampleRate;
	}

	/*
	 */
	public function setSampleRate($sampleRate)
	{
		if ($sampleRate < 1 || $sampleRate > self::MAX_SAMPLERATE)
		{
			throw new SecurimageWavfileException('Invalid sample rate.');
		}

		if ($this->_samples !== '')
		{
			trigger_error('Wav already has sample data. Changing the sample rate does not convert the data and may yield undesired results.', E_USER_NOTICE);
		}

		$this->_sampleRate = (int)$sampleRate;

		$this->setByteRate();

		return $this;
	}

	/*
	 */
	public function getBitsPerSample()
	{
		return $this->_bitsPerSample;
	}

	/*
	 */
	public function setBitsPerSample($bitsPerSample)
	{
		if (!in_array($bitsPerSample, array(8, 16, 24, 32)))
		{
			throw new SecurimageWavfileException('Unsupported bits per sample. Only 8, 16, 24 and 32 bits are supported.');
		}

		if ($this->_samples !== '')
		{
			trigger_error('Wav already has sample data. Changing the bits per sample does not convert and may corrupt the data.', E_USER_NOTICE);
		}

		$this->_bitsPerSample = (int)$bitsPerSample;

		$this->setValidBitsPerSample()  // implicit setAudioFormat(), setAudioSubFormat(), setFmtChunkSize(), setFactChunkSize(), setSize(), setActualSize(), setDataOffset()
		->setByteRate()
			->setBlockAlign();         // implicit setNumBlocks()

		return $this;
	}

	/*
	 */
	public function getValidBitsPerSample()
	{
		return $this->_validBitsPerSample;
	}

	/*
	 */
	protected function setValidBitsPerSample($validBitsPerSample = null)
	{
		if (is_null($validBitsPerSample))
		{
			$this->_validBitsPerSample = $this->_bitsPerSample;
		}
		else
		{
			if ($validBitsPerSample < 1 || $validBitsPerSample > $this->_bitsPerSample)
			{
				throw new SecurimageWavfileException('ValidBitsPerSample cannot be greater than BitsPerSample.');
			}
			$this->_validBitsPerSample = (int)$validBitsPerSample;
		}

		$this->setAudioFormat();  // implicit setAudioSubFormat(), setFactChunkSize(), setFmtExtendedSize(), setFmtChunkSize(), setSize(), setActualSize(), setDataOffset()

		return $this;
	}

	/*
	 */
	public function getBlockAlign()
	{
		return $this->_blockAlign;
	}

	/** @param int $blockAlign */
	protected function setBlockAlign($blockAlign = null)
	{
		if (is_null($blockAlign))
		{
			$this->_blockAlign = $this->_numChannels * $this->_bitsPerSample / 8;
		}
		else
		{
			$this->_blockAlign = $blockAlign;
		}

		$this->setNumBlocks();

		return $this;
	}

	/*
	 */
	public function getNumBlocks()
	{
		return $this->_numBlocks;
	}

	/**
	 * @param int $numBlocks
	 */
	protected function setNumBlocks($numBlocks = null) {
		if (is_null($numBlocks)) {
			$this->_numBlocks = (int)($this->_dataSize / $this->_blockAlign);  // do not count incomplete sample blocks
		} else {
			$this->_numBlocks = $numBlocks;
		}

		return $this;
	}

	/*
	 */
	public function getByteRate()
	{
		return $this->_byteRate;
	}

	/**
	 * @param int $byteRate
	 */
	protected function setByteRate($byteRate = null)
	{
		if (is_null($byteRate)) {
			$this->_byteRate = $this->_sampleRate * $this->_numChannels * $this->_bitsPerSample / 8;
		} else {
			$this->_byteRate = $byteRate;
		}

		return $this;
	}

	/*
	 */
	public function getIgnoreChunkSizes()
	{
		return $this->_ignoreChunkSizes;
	}

	/*
	 */
	public function setIgnoreChunkSizes($ignoreChunkSizes)
	{
		$this->_ignoreChunkSizes = (bool)$ignoreChunkSizes;
		return $this;
	}

	/*
	 */
	public function getSamples()
	{
		return $this->_samples;
	}

	/*
	 */
	public function setSamples(&$samples = '')
	{
		if (strlen($samples) % $this->_blockAlign != 0)
		{
			throw new SecurimageWavfileException('Incorrect samples size. Has to be a multiple of BlockAlign.');
		}

		$this->_samples = $samples;

		$this->setDataSize();  // implicit setSize(), setActualSize(), setNumBlocks()

		return $this;
	}

	/*
	 */
	public function getMinAmplitude()
	{
		if ($this->_bitsPerSample == 8) return 0;

		if ($this->_bitsPerSample == 32)  return -1.0;

		return -(1 << ($this->_bitsPerSample - 1));
	}

	/*
	 */
	public function getZeroAmplitude()
	{
		if ($this->_bitsPerSample == 8) return 0x80;

		if ($this->_bitsPerSample == 32) return 0.0;

		return 0;
	}

	/*
	 */
	public function getMaxAmplitude()
	{
		if($this->_bitsPerSample == 8) return 0xFF;

		if($this->_bitsPerSample == 32) return 1.0;

		return (1 << ($this->_bitsPerSample - 1)) - 1;
	}
}