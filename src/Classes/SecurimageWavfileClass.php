<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Classes;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Exceptions\SecurimageWavfileException;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Exceptions\SecurimageWavformatException;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits\ConstantsTrait;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits\GetsetTrait;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits\PropertiesTrait;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits\ProcessingTrait;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits\SampleTrait;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits\StaticTrait;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits\UtilitiesTrait;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits\WavTrait;

\defined('_JEXEC') or die;

class SecurimageWavfileClass
{
	use ConstantsTrait;
	use GetsetTrait;
	use ProcessingTrait;
	use PropertiesTrait;
	use SampleTrait;
	use StaticTrait;
	use UtilitiesTrait;
	use WavTrait;

	/**
	 * <code>
	 * $wav1 = new SecurimageWavfileClass(2, 44100, 16);         // new wav with 2 channels, at 44100 samples/sec and 16 bits per sample
	 * $wav2 = new SecurimageWavfileClass('./audio/sound.wav');  // open and read wav file
	 * </code>
	 *
	 * @param string|int $numChannelsOrFileName  (Optional) If string, the filename of the wav file to open. The number of channels otherwise. Defaults to 1.
	 * @param int|bool $sampleRateOrReadData  (Optional) If opening a file and boolean, decides whether to read the data chunk or not. Defaults to true. The sample rate in samples per second otherwise. 8000 = standard telephone, 16000 = wideband telephone, 32000 = FM radio and 44100 = CD quality. Defaults to 8000.
	 * @param int $bitsPerSample  (Optional) The number of bits per sample. Has to be 8, 16 or 24 for PCM audio or 32 for IEEE FLOAT audio. 8 = telephone, 16 = CD and 24 or 32 = studio quality. Defaults to 8.
	 * @throws WavFormatException
	 * @throws SecurimageWavfileException
	 */
	public function __construct($numChannelsOrFileName = null, $sampleRateOrReadData = null, $bitsPerSample = null)
	{
		$this->_actualSize         = 44;
		$this->_chunkSize          = 36;
		$this->_fmtChunkSize       = 16;
		$this->_fmtExtendedSize    = 0;
		$this->_factChunkSize      = 0;
		$this->_dataSize           = 0;
		$this->_dataSize_fp        = 0;
		$this->_dataSize_valid     = true;
		$this->_dataOffset         = 44;
		$this->_audioFormat        = self::WAVE_FORMAT_PCM;
		$this->_audioSubFormat     = null;
		$this->_numChannels        = 1;
		$this->_channelMask        = self::SPEAKER_DEFAULT;
		$this->_sampleRate         = 8000;
		$this->_bitsPerSample      = 8;
		$this->_validBitsPerSample = 8;
		$this->_blockAlign         = 1;
		$this->_numBlocks          = 0;
		$this->_byteRate           = 8000;
		$this->_ignoreChunkSizes   = false;
		$this->_samples            = '';
		$this->_fp                 = null;


		if (is_string($numChannelsOrFileName)) {
			$this->openWav($numChannelsOrFileName, is_bool($sampleRateOrReadData) ? $sampleRateOrReadData : true);

		} else {
			$this->setNumChannels(is_null($numChannelsOrFileName) ? 1 : $numChannelsOrFileName)
				->setSampleRate(is_null($sampleRateOrReadData) ? 8000 : $sampleRateOrReadData)
				->setBitsPerSample(is_null($bitsPerSample) ? 8 : $bitsPerSample);
		}
	}

	/*
	 */
	public function __destruct()
	{
		if (is_resource($this->_fp))
		{
			$this->closeWav();
		}
	}

	/*
	 */
	protected function __clone()
	{
		$this->_fp = null;
	}

	/**
	 * Output the wav file headers and data.
	 *
	 * @return string  The encoded file.
	 */
	public function __toString()
	{
		return $this->makeHeader() .
			$this->getDataSubchunk();
	}
}
