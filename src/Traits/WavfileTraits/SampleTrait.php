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

Trait SampleTrait
{
	/**
	 * Return a single sample block from the file.
	 *
	 * @param int $blockNum  (Required) The sample block number. Zero based.
	 * @return string|null  The binary sample block (all channels). Returns null if the sample block number was out of range.
	 */
	public function getSampleBlock($blockNum)
	{
		// check preconditions
		if (!$this->_dataSize_valid)
		{
			$this->setDataSize();  // implicit setSize(), setActualSize(), setNumBlocks()
		}

		$offset = $blockNum * $this->_blockAlign;
		if ($offset + $this->_blockAlign > $this->_dataSize || $offset < 0) return null;

		// read data
		return substr($this->_samples, $offset, $this->_blockAlign);
	}

	/**
	 * Set a single sample block. <br />
	 * Allows to append a sample block.
	 *
	 * @param string $sampleBlock  (Required) The binary sample block (all channels).
	 * @param int $blockNum  (Required) The sample block number. Zero based.
	 * @throws SecurimageWavfileException
	 */
	public function setSampleBlock($sampleBlock, $blockNum)
	{
		// check preconditions
		$blockAlign = $this->_blockAlign;
		if (!isset($sampleBlock[$blockAlign - 1]) || isset($sampleBlock[$blockAlign]))
		{  // faster than: if (strlen($sampleBlock) != $blockAlign)
			throw new SecurimageWavfileException('Incorrect sample block size. Got ' . strlen($sampleBlock) . ', expected ' . $blockAlign . '.');
		}

		if (!$this->_dataSize_valid)
		{
			$this->setDataSize();  // implicit setSize(), setActualSize(), setNumBlocks()
		}

		$numBlocks = (int)($this->_dataSize / $blockAlign);
		$offset = $blockNum * $blockAlign;
		if ($blockNum > $numBlocks || $blockNum < 0)
		{  // allow appending
			throw new SecurimageWavfileException('Sample block number is out of range.');
		}

		// replace or append data
		if ($blockNum == $numBlocks)
		{
			// append
			$this->_samples    .= $sampleBlock;
			$this->_dataSize   += $blockAlign;
			$this->_chunkSize  += $blockAlign;
			$this->_actualSize += $blockAlign;
			$this->_numBlocks++;
		}
		else
		{
			// replace
			for ($i = 0; $i < $blockAlign; ++$i)
			{
				$this->_samples[$offset + $i] = $sampleBlock[$i];
			}
		}

		return $this;
	}

	/**
	 * Get a float sample value for a specific sample block and channel number.
	 *
	 * @param int $blockNum  (Required) The sample block number to fetch. Zero based.
	 * @param int $channelNum  (Required) The channel number within the sample block to fetch. First channel is 1.
	 * @return float|null  The float sample value. Returns null if the sample block number was out of range.
	 * @throws SecurimageWavfileException
	 */
	public function getSampleValue($blockNum, $channelNum)
	{
		// check preconditions
		if ($channelNum < 1 || $channelNum > $this->_numChannels)
		{
			throw new SecurimageWavfileException('Channel number is out of range.');
		}

		if (!$this->_dataSize_valid)
		{
			$this->setDataSize();  // implicit setSize(), setActualSize(), setNumBlocks()
		}

		$sampleBytes = $this->_bitsPerSample / 8;
		$offset = $blockNum * $this->_blockAlign + ($channelNum - 1) * $sampleBytes;
		if ($offset + $sampleBytes > $this->_dataSize || $offset < 0)
		{
			return null;
		}

		// read binary value
		$sampleBinary = substr($this->_samples, $offset, $sampleBytes);

		// convert binary to value
		switch ($this->_bitsPerSample)
		{
			case 8:
				// unsigned char
				return (float)((ord($sampleBinary) - 0x80) / 0x80);

			case 16:
				// signed short, little endian
				$data = unpack('v', $sampleBinary);
				$sample = $data[1];
				if ($sample >= 0x8000)
				{
					$sample -= 0x10000;
				}
				return (float)($sample / 0x8000);

			case 24:
				// 3 byte packed signed integer, little endian
				$data = unpack('C3', $sampleBinary);
				$sample = $data[1] | ($data[2] << 8) | ($data[3] << 16);
				if ($sample >= 0x800000)
				{
					$sample -= 0x1000000;
				}
				return (float)($sample / 0x800000);

			case 32:
				// 32-bit float
				$data = unpack('f', $sampleBinary);
				return (float)$data[1];

			default:
				return null;
		}
	}

	/**
	 * Sets a float sample value for a specific sample block number and channel. <br />
	 * Converts float values to appropriate integer values and clips properly. <br />
	 * Allows to append samples (in order).
	 *
	 * @param float $sampleFloat  (Required) The float sample value to set. Converts float values and clips if necessary.
	 * @param int $blockNum  (Required) The sample block number to set or append. Zero based.
	 * @param int $channelNum  (Required) The channel number within the sample block to set or append. First channel is 1.
	 * @throws SecurimageWavfileException
	 */
	public function setSampleValue($sampleFloat, $blockNum, $channelNum)
	{
		// check preconditions
		if ($channelNum < 1 || $channelNum > $this->_numChannels)
		{
			throw new SecurimageWavfileException('Channel number is out of range.');
		}

		if (!$this->_dataSize_valid)
		{
			$this->setDataSize();  // implicit setSize(), setActualSize(), setNumBlocks()
		}

		$dataSize = $this->_dataSize;
		$bitsPerSample = $this->_bitsPerSample;
		$sampleBytes = $bitsPerSample / 8;
		$offset = $blockNum * $this->_blockAlign + ($channelNum - 1) * $sampleBytes;
		if (($offset + $sampleBytes > $dataSize && $offset != $dataSize) || $offset < 0)
		{ // allow appending
			throw new SecurimageWavfileException('Sample block or channel number is out of range.');
		}


		// convert to value, quantize and clip
		if ($bitsPerSample == 32)
		{
			$sample = $sampleFloat < -1.0 ? -1.0 : ($sampleFloat > 1.0 ? 1.0 : $sampleFloat);
		}
		else
		{
			$p = 1 << ($bitsPerSample - 1); // 2 to the power of _bitsPerSample divided by 2

			// project and quantize (round) float to integer values
			$sample = $sampleFloat < 0 ? (int)($sampleFloat * $p - 0.5) : (int)($sampleFloat * $p + 0.5);

			// clip if necessary to [-$p, $p - 1]
			if ($sample < -$p)
			{
				$sample = -$p;
			} elseif ($sample > $p - 1)
			{
				$sample = $p - 1;
			}
		}

		// convert to binary
		switch ($bitsPerSample)
		{
			case 8:
				// unsigned char
				$sampleBinary = chr($sample + 0x80);
				break;

			case 16:
				// signed short, little endian
				if ($sample < 0)
				{
					$sample += 0x10000;
				}
				$sampleBinary = pack('v', $sample);
				break;

			case 24:
				// 3 byte packed signed integer, little endian
				if ($sample < 0)
				{
					$sample += 0x1000000;
				}
				$sampleBinary = pack('C3', $sample & 0xff, ($sample >>  8) & 0xff, ($sample >> 16) & 0xff);
				break;

			case 32:
				// 32-bit float
				$sampleBinary = pack('f', $sample);
				break;

			default:
				$sampleBinary = null;
				$sampleBytes = 0;
				break;
		}

		// replace or append data
		if ($offset == $dataSize)
		{
			// append
			$this->_samples    .= $sampleBinary;
			$this->_dataSize   += $sampleBytes;
			$this->_chunkSize  += $sampleBytes;
			$this->_actualSize += $sampleBytes;
			$this->_numBlocks = (int)($this->_dataSize / $this->_blockAlign);
		}
		else
		{
			// replace
			for ($i = 0; $i < $sampleBytes; ++$i)
			{
				// 2024 Brainforge.UK - Modified for Joomla 8.1 compatibility
				$this->_samples[$offset + $i] = $sampleBinary[$i];
			}
		}

		return $this;
	}

	/**
	 * Run samples through audio processing filters.
	 *
	 * <code>
	 * $wav->filter(
	 *      array(
	 *          SecurimageWavfileClass::FILTER_MIX => array(          // Filter for mixing 2 SecurimageWavfileClass instances.
	 *              'wav' => $wav2,                    // (Required) The SecurimageWavfileClass to mix into this WhavFile. If no optional arguments are given, can be passed without the array.
	 *              'loop' => true,                    // (Optional) Loop the selected portion (with warping to the beginning at the end).
	 *              'blockOffset' => 0,                // (Optional) Block number to start mixing from.
	 *              'numBlocks' => null                // (Optional) Number of blocks to mix in or to select for looping. Defaults to the end or all data for looping.
	 *          ),
	 *          SecurimageWavfileClass::FILTER_NORMALIZE => 0.6,      // (Required) Normalization of (mixed) audio samples - see threshold parameter for normalizeSample().
	 *          SecurimageWavfileClass::FILTER_DEGRADE => 0.9         // (Required) Introduce random noise. The quality relative to the amplitude. 1 = no noise, 0 = max. noise.
	 *          SecurimageWavfileClass::FILTER_VOLUME => 1.0          // (Required) Amplify or attenuate the audio signal.  Beware of clipping when amplifying.  Values range from >= 0 - <= 2.  1 = no change in volume; 0.5 = 50% reduction of volume; 1.5 = 150% increase in volume.
	 *      ),
	 *      0,                                         // (Optional) The block number of this SecurimageWavfileClass to start with.
	 *      null                                       // (Optional) The number of blocks to process.
	 *  );
	 *  </code>
	 *
	 * @param array $filters  (Required) An array of 1 or more audio processing filters.
	 * @param int $blockOffset  (Optional) The block number to start precessing from.
	 * @param int $numBlocks  (Optional) The maximum  number of blocks to process.
	 * @throws SecurimageWavfileException
	 */
	public function filter($filters, $blockOffset = 0, $numBlocks = null)
	{
		// check preconditions
		$totalBlocks = $this->getNumBlocks();
		$numChannels = $this->getNumChannels();
		if (is_null($numBlocks)) $numBlocks = $totalBlocks - $blockOffset;

		if (!is_array($filters) || empty($filters) || $blockOffset < 0 || $blockOffset > $totalBlocks || $numBlocks <= 0)
		{
			// nothing to do
			return $this;
		}

		// check filtes
		$filter_mix = false;
		if (array_key_exists(self::FILTER_MIX, $filters))
		{
			if (!is_array($filters[self::FILTER_MIX]))
			{
				// assume the 'wav' parameter
				$filters[self::FILTER_MIX] = array('wav' => $filters[self::FILTER_MIX]);
			}

			$mix_wav = @$filters[self::FILTER_MIX]['wav'];
			if (!($mix_wav instanceof SecurimageWavfileClass))
			{
				throw new SecurimageWavfileException("SecurimageWavfileClass to mix is missing or invalid.");
			}

			if ($mix_wav->getSampleRate() != $this->getSampleRate())
			{
				throw new SecurimageWavfileException("Sample rate of SecurimageWavfileClass to mix does not match.");
			}

			if ($mix_wav->getNumChannels() != $this->getNumChannels())
			{
				throw new SecurimageWavfileException("Number of channels of SecurimageWavfileClass to mix does not match.");
			}

			$mix_loop = @$filters[self::FILTER_MIX]['loop'];
			if (is_null($mix_loop)) $mix_loop = false;

			$mix_blockOffset = @$filters[self::FILTER_MIX]['blockOffset'];
			if (is_null($mix_blockOffset)) $mix_blockOffset = 0;

			$mix_totalBlocks = $mix_wav->getNumBlocks();
			$mix_numBlocks = $filters[self::FILTER_MIX]['numBlocks'] ?? null;
			if (is_null($mix_numBlocks)) $mix_numBlocks = $mix_loop ? $mix_totalBlocks : $mix_totalBlocks - $mix_blockOffset;
			$mix_maxBlock = min($mix_blockOffset + $mix_numBlocks, $mix_totalBlocks);

			$filter_mix = true;
		}

		$filter_normalize = false;
		if (array_key_exists(self::FILTER_NORMALIZE, $filters))
		{
			$normalize_threshold = @$filters[self::FILTER_NORMALIZE];

			if (!is_null($normalize_threshold) && abs($normalize_threshold) != 1)
			{
				$filter_normalize = true;
			}
		}

		$filter_degrade = false;
		if (array_key_exists(self::FILTER_DEGRADE, $filters))
		{
			$degrade_quality = @$filters[self::FILTER_DEGRADE];
			if (is_null($degrade_quality))
			{
				$degrade_quality = 1;
			}

			if ($degrade_quality >= 0 && $degrade_quality < 1)
			{
				$filter_degrade = true;
			}
		}

		$filter_vol = false;
		if (array_key_exists(self::FILTER_VOLUME, $filters))
		{
			$volume_amount = @$filters[self::FILTER_VOLUME];
			if (is_null($volume_amount))
			{
				$volume_amount = 1;
			}

			if ($volume_amount >= 0 && $volume_amount <= 2 && $volume_amount != 1.0)
			{
				$filter_vol = true;
			}
		}


		// loop through all sample blocks
		for ($block = 0; $block < $numBlocks; ++$block)
		{
			// loop through all channels
			for ($channel = 1; $channel <= $numChannels; ++$channel)
			{
				// read current sample
				$currentBlock = $blockOffset + $block;
				$sampleFloat = $this->getSampleValue($currentBlock, $channel);

				/************* MIX FILTER ***********************/
				if ($filter_mix)
				{
					if ($mix_loop)
					{
						$mixBlock = ($mix_blockOffset + ($block % $mix_numBlocks)) % $mix_totalBlocks;
					}
					else
					{
						$mixBlock = $mix_blockOffset + $block;
					}

					if ($mixBlock < $mix_maxBlock)
					{
						$sampleFloat += $mix_wav->getSampleValue($mixBlock, $channel);
					}
				}

				/************* NORMALIZE FILTER *******************/
				if ($filter_normalize)
				{
					$sampleFloat = $this->normalizeSample($sampleFloat, $normalize_threshold);
				}

				/************* DEGRADE FILTER *******************/
				if ($filter_degrade)
				{
					$sampleFloat += rand(1000000 * intval($degrade_quality - 1), 1000000 * intval(1 - $degrade_quality)) / 1000000;
				}

				/************* VOLUME FILTER *******************/
				if ($filter_vol)
				{
					$sampleFloat *=  $volume_amount;
				}

				// write current sample
				$this->setSampleValue($sampleFloat, $currentBlock, $channel);
			}
		}

		return $this;
	}
}