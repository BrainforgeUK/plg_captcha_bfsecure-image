<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits;

\defined('_JEXEC') or die;

Trait PropertiesTrait
{
	/** @var array Log base modifier lookup table for a given threshold (in 0.05 steps) used by normalizeSample.
	 * Adjusts the slope (1st derivative) of the log function at the threshold to 1 for a smooth transition
	 * from linear to logarithmic amplitude output. */
	protected static $LOOKUP_LOGBASE = array(
		2.513, 2.667, 2.841, 3.038, 3.262, 3.520, 3.819, 4.171, 4.589, 5.093,
		5.711, 6.487, 7.483, 8.806, 10.634, 13.302, 17.510, 24.970, 41.155, 96.088
	);

	/** @var int The actual physical file size */
	protected $_actualSize;

	/** @var int The size of the file in RIFF header */
	protected $_chunkSize;

	/** @var int The size of the "fmt " chunk */
	protected $_fmtChunkSize;

	/** @var int The size of the extended "fmt " data */
	protected $_fmtExtendedSize;

	/** @var int The size of the "fact" chunk */
	protected $_factChunkSize;

	/** @var int Size of the data chunk */
	protected $_dataSize;

	/** @var int Size of the data chunk in the opened wav file */
	protected $_dataSize_fp;

	/** @var bool Does _dataSize really reflect strlen($_samples)? Case when a wav file is read with readData = false */
	protected $_dataSize_valid;

	/** @var int Starting offset of data chunk */
	protected $_dataOffset;

	/** @var int The audio format - SecurimageWavfileClass::WAVE_FORMAT_* */
	protected $_audioFormat;

	/** @var int|string|null The audio subformat - SecurimageWavfileClass::WAVE_SUBFORMAT_* */
	protected $_audioSubFormat;

	/** @var int Number of channels in the audio file */
	protected $_numChannels;

	/** @var int The channel mask */
	protected $_channelMask;

	/** @var int Samples per second */
	protected $_sampleRate;

	/** @var int Number of bits per sample */
	protected $_bitsPerSample;

	/** @var int Number of valid bits per sample */
	protected $_validBitsPerSample;

	/** @var int NumChannels * BitsPerSample/8 */
	protected $_blockAlign;

	/** @var int Number of sample blocks */
	protected $_numBlocks;

	/** @var int Bytes per second */
	protected $_byteRate;

	/** @var bool Ignore chunk sizes when reading wav data (useful when reading data from a stream where chunk sizes contain dummy values) */
	protected $_ignoreChunkSizes;

	/** @var string Binary string of samples */
	protected $_samples;

	/** @var resource|null The file pointer used for reading wavs from file or memory */
	protected $_fp;
}