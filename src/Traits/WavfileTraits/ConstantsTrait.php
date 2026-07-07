<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits\WavfileTraits;

\defined('_JEXEC') or die;

Trait ConstantsTrait
{
	/** @var int Filter flag for mixing two files */
	const FILTER_MIX       = 0x01;

	/** @var int Filter flag for normalizing audio data */
	const FILTER_NORMALIZE = 0x02;

	/** @var int Filter flag for degrading audio data */
	const FILTER_DEGRADE   = 0x04;

	/** @var int Filter flag for amplifying or attenuating audio data. */
	const FILTER_VOLUME    = 0x08;

	/** @var int Maximum number of channels */
	const MAX_CHANNEL = 18;

	/** @var int Maximum sample rate */
	const MAX_SAMPLERATE = 192000;

	/** Channel Locations for ChannelMask */
	const SPEAKER_DEFAULT               = 0x000000;
	const SPEAKER_FRONT_LEFT            = 0x000001;
	const SPEAKER_FRONT_RIGHT           = 0x000002;
	const SPEAKER_FRONT_CENTER          = 0x000004;
	const SPEAKER_LOW_FREQUENCY         = 0x000008;
	const SPEAKER_BACK_LEFT             = 0x000010;
	const SPEAKER_BACK_RIGHT            = 0x000020;
	const SPEAKER_FRONT_LEFT_OF_CENTER  = 0x000040;
	const SPEAKER_FRONT_RIGHT_OF_CENTER = 0x000080;
	const SPEAKER_BACK_CENTER           = 0x000100;
	const SPEAKER_SIDE_LEFT             = 0x000200;
	const SPEAKER_SIDE_RIGHT            = 0x000400;
	const SPEAKER_TOP_CENTER            = 0x000800;
	const SPEAKER_TOP_FRONT_LEFT        = 0x001000;
	const SPEAKER_TOP_FRONT_CENTER      = 0x002000;
	const SPEAKER_TOP_FRONT_RIGHT       = 0x004000;
	const SPEAKER_TOP_BACK_LEFT         = 0x008000;
	const SPEAKER_TOP_BACK_CENTER       = 0x010000;
	const SPEAKER_TOP_BACK_RIGHT        = 0x020000;
	const SPEAKER_ALL                   = 0x03FFFF;

	/** @var int PCM Audio Format */
	const WAVE_FORMAT_PCM           = 0x0001;

	/** @var int IEEE FLOAT Audio Format */
	const WAVE_FORMAT_IEEE_FLOAT    = 0x0003;

	/** @var int EXTENSIBLE Audio Format - actual audio format defined by SubFormat */
	const WAVE_FORMAT_EXTENSIBLE    = 0xFFFE;

	/** @var string PCM Audio Format SubType - LE hex representation of GUID {00000001-0000-0010-8000-00AA00389B71} */
	const WAVE_SUBFORMAT_PCM        = "0100000000001000800000aa00389b71";

	/** @var string IEEE FLOAT Audio Format SubType - LE hex representation of GUID {00000003-0000-0010-8000-00AA00389B71} */
	const WAVE_SUBFORMAT_IEEE_FLOAT = "0300000000001000800000aa00389b71";
}