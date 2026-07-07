<?php
/**
 * @package      CAPTCHA plugin based on Securimage
 * @subpackage   plg_captcha_bfsecurimage
 * @copyright    Copyright (C) 2026 Jonathan Brain. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Brainforgeuk\Plugin\Captcha\Bfsecurimage\Traits;

use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Classes\SecurimageWavfileClass;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper\BfsecurimageCodeHelper;
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Helper\BfsecurimageHelper;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;

\defined('_JEXEC') or die;

Trait BfsecurimagePlayTrait
{
	/**
	 * The path to the audio files to be used for audio captchas.
	 *
	 * @var string
	 */
	public $audio_path = JPATH_SITE . '/media/plg_captcha_bfsecurimage/audio/en';

	/**
	 * Maximum delay to insert between captcha audio letters in milliseconds
	 *
	 * @var float
	 */
	public $audio_gap_max;

	/**
	 * The path to the SoX binary on your system
	 *
	 * @var string
	 */
	public $sox_binary_path;

	/**
	 * Use SoX (The Swiss Army knife of audio manipulation) for audio effects and processing.
	 * Using SoX should make it more difficult for bots to solve audio captchas.
	 *
	 * @var bool true to use SoX, false to use PHP
	 */
	public $audio_use_sox;

	/**
	 * The path to the directory containing audio files that will be selected randomly and mixed with the captcha audio.
	 *
	 * @var string
	 */
	public $audio_noise_path;

	/**
	 * Whether or not to mix background noise files into captcha audio
	 *
	 * Mixing random background audio with noise can help improve security of audio captcha.
	 *
	 * @var bool true = mix, false = no
	 */
	public $audio_use_noise;

	/**
	 * Whether or not to degrade audio by introducing random noise.
	 *
	 * Current research shows this may not increase the security of audible captchas.
	 *
	 * @var bool
	 */
	public $degrade_audio;
	/**
	 * Minimum delay to insert between captcha audio letters in milliseconds
	 *
	 * @since 3.0.3
	 * @var float
	 */
	public $audio_gap_min;

	/**
	 * The method and threshold (or gain factor) used to normalize the mixing with background noise.
	 *
	 * See http://www.voegler.eu/pub/audio/ for more information.
	 *
	 * Valid:
	 *     >= 1
	 *     Normalize by multiplying by the threshold (boost - positive gain).
	 *     A value of 1 in effect means no normalization (and results in clipping).
	 *
	 *     <= -1
	 *     Normalize by dividing by the the absolute value of threshold (attenuate - negative gain).
	 *     A factor of 2 (-2) is about 6dB reduction in volume.
	 *
	 *     [0, 1)  (open inverval - not including 1)
	 *     The threshold above which amplitudes are comressed logarithmically.
	 *     e.g. 0.6 to leave amplitudes up to 60% "as is" and compressabove.
	 *
	 *     (-1, 0) (open inverval - not including -1 and 0)
	 *     The threshold above which amplitudes are comressed linearly.
	 *     e.g. -0.6 to leave amplitudes up to 60% "as is" and compress above.
	 *
	 * @var float
	 */
	public $audio_mix_normalization;

	/*
	 */
	public function play($config=[])
	{
		$this->loadPluginPlayParams($config);

		$code = BfsecurimageCodeHelper::queryCode($this->getSession());

		if (empty($code)) throw new \Exception(Text::_('PLG_BFSECURIMAGE_ERROR_NOCAPTCHACODE'));

		$length = BfsecurimageHelper::strlen($code);

		$letters = [];
		for($i = 0; $i < $length; ++$i)
		{
			$letters[] = BfsecurimageHelper::substr($code, $i, 1);
		}

		$audio = $this->generateWAV($letters);

		BfsecurimageHelper::outputSound($audio);
	}

	/*
	 */
	protected function loadPluginPlayParams($config=[])
	{
		$params = $this->loadPluginParams();

		$this->audio_gap_max = $params->get('audio_gap_max', 3000);

		$this->sox_binary_path = $params->get('sox_binary_path', '/usr/bin/sox');

		$this->audio_use_sox = $params->get('audio_use_sox', false);

		$this->audio_noise_path = $params->get('audio_noise_path', JPATH_SITE . '/media/plg_captcha_bfsecurimage/audio/noise');

		$this->audio_use_noise = $params->get('audio_use_noise', false);

		$this->degrade_audio = $params->get('degrade_audio', false);

		$this->audio_gap_min = $params->get('audio_gap_min', 0);

		$this->audio_mix_normalization = $params->get('audio_mix_normalization', 0.8);

		foreach($config as $key => $value)
		{
			$this->$key = $value;
		}
	}

	/**
	 * Generate a wav file given the $letters in the code
	 *
	 * @param array $letters  The letters making up the captcha
	 * @return string The audio content in WAV format
	 */
	protected function generateWAV($letters)
	{
		$wavCaptcha = new SecurimageWavfileClass();
		$first      = true;     // reading first wav file

		if ($this->audio_use_sox && !is_executable($this->sox_binary_path))
		{
			throw new \Exception("Path to SoX binary is incorrect or not executable");
		}

		foreach ($letters as $letter)
		{
			$letter = strtoupper($letter);

			try
			{
				$letter_file = $this->audio_path . '/' . $letter . '.wav';
				if (!is_file($letter_file))
				{
					throw new \Exception('File not found: ' . str_replace(JPATH_SITE, 'JPATH_SITE', $letter_file));
				}

				if ($this->audio_use_sox)
				{
					$sox_cmd = sprintf("%s %s -t wav - %s",
						$this->sox_binary_path,
						$letter_file,
						$this->getSoxEffectChain());

					$data = `$sox_cmd`;

					$l = new SecurimageWavfileClass();
					$l->setIgnoreChunkSizes(true);
					$l->setWavData($data);
				}
				else
				{
					$l = new SecurimageWavfileClass($letter_file);
				}

				if ($first)
				{
					// set sample rate, bits/sample, and # of channels for file based on first letter
					$wavCaptcha->setSampleRate($l->getSampleRate())
						->setBitsPerSample($l->getBitsPerSample())
						->setNumChannels($l->getNumChannels());
					$first = false;
				}

				// append letter to the captcha audio
				$wavCaptcha->appendWav($l);

				// random length of silence between $audio_gap_min and $audio_gap_max
				if ($this->audio_gap_max > 0 && $this->audio_gap_max > $this->audio_gap_min) {
					$wavCaptcha->insertSilence( mt_rand($this->audio_gap_min, $this->audio_gap_max) / 1000.0 );
				}
			}
			catch (\Exception $ex)
			{
				// failed to open file, or the wav file is broken or not supported
				// 2 wav files were not compatible, different # channels, bits/sample, or sample rate
				throw new \Exception("Error generating audio captcha on letter '$letter': " . $ex->getMessage());
			}
		}

		/********* Set up audio filters *****************************/
		$filters = array();

		if ($this->audio_use_noise)
		{
			// use background audio - find random file
			$wavNoise   = false;
			$randOffset = 0;

			if ( ($noiseFile = $this->getRandomNoiseFile()) !== false)
			{
				try
				{
					$wavNoise = new SecurimageWavfileClass($noiseFile, false);
				}
				catch(\Exception $ex)
				{
					throw $ex;
				}

				// start at a random offset from the beginning of the wavfile
				// in order to add more randomness

				$randOffset = 0;

				if ($wavNoise->getNumBlocks() > 2 * $wavCaptcha->getNumBlocks())
				{
					$randBlock = mt_rand(0, $wavNoise->getNumBlocks() - $wavCaptcha->getNumBlocks());
					$wavNoise->readWavData($randBlock * $wavNoise->getBlockAlign(), $wavCaptcha->getNumBlocks() * $wavNoise->getBlockAlign());
				}
				else
				{
					$wavNoise->readWavData();
					$randOffset = mt_rand(0, $wavNoise->getNumBlocks() - 1);
				}
			}

			if ($wavNoise !== false)
			{
				$mixOpts = array('wav'  => $wavNoise,
					'loop' => true,
					'blockOffset' => $randOffset);

				$filters[SecurimageWavfileClass::FILTER_MIX]       = $mixOpts;
				$filters[SecurimageWavfileClass::FILTER_NORMALIZE] = $this->audio_mix_normalization;
			}
		}

		if ($this->degrade_audio == true)
		{
			// add random noise.
			// any noise level below 95% is intensely distorted and not pleasant to the ear
			$filters[SecurimageWavfileClass::FILTER_DEGRADE] = mt_rand(95, 98) / 100.0;
		}

		if (!empty($filters))
		{
			$wavCaptcha->filter($filters);  // apply filters to captcha audio
		}

		return $wavCaptcha->__toString();
	}

	/**
	 * Gets and returns the path to a random noise file from the audio noise directory.
	 *
	 * @return bool|string  false if a file could not be found, or a string containing the path to the file.
	 */
	public function getRandomNoiseFile()
	{
		$noiseFiles = Folder::files($this->audio_noise_path, '\\.wav', true, true);
		if (empty($noiseFiles)) return false;

		return $noiseFiles[array_rand($noiseFiles, 1)];
	}

	/**
	 * Get a random effect or chain of effects to apply to a segment of the
	 * audio file.
	 *
	 * These effects should increase the randomness of the audio for
	 * a particular letter/number by modulating the signal.  The SoX effects
	 * used are *bend*, *chorus*, *overdrive*, *pitch*, *reverb*, *tempo*, and
	 * *tremolo*.
	 *
	 * For each effect selected, random parameters are supplied to the effect.
	 *
	 * @param int $numEffects  How many effects to chain together
	 * @return string  A string of valid SoX effects and their respective options.
	 */
	public function getSoxEffectChain($numEffects = 2)
	{
		$effectsList = array('bend', 'chorus', 'overdrive', 'pitch', 'reverb', 'tempo', 'tremolo');
		$effects     = array_rand($effectsList, $numEffects);
		$outEffects  = array();

		if (!is_array($effects)) $effects = array($effects);

		foreach($effects as $effect)
		{
			$effect = $effectsList[$effect];

			switch($effect)
			{
				case 'bend':
					$delay = mt_rand(0, 15) / 100.0;
					$cents = mt_rand(-120, 120);
					$dur   = mt_rand(75, 400) / 100.0;
					$outEffects[] = "$effect $delay,$cents,$dur";
					break;

				case 'chorus':
					$gainIn  = mt_rand(75, 90) / 100.0;
					$gainOut = mt_rand(70, 95) / 100.0;
					$chorStr = "$effect $gainIn $gainOut";

					for ($i = 0; $i < mt_rand(2, 3); ++$i)
					{
						$delay = mt_rand(20, 100);
						$decay = mt_rand(10, 100) / 100.0;
						$speed = mt_rand(20, 50) / 100.0;
						$depth = mt_rand(150, 250) / 100.0;

						$chorStr .= " $delay $decay $speed $depth -s";
					}

					$outEffects[] = $chorStr;
					break;

				case 'overdrive':
					$gain = mt_rand(5, 25);
					$color = mt_rand(20, 70);
					$outEffects[] = "$effect $gain $color";
					break;

				case 'pitch':
					$cents = mt_rand(-300, 300);
					$outEffects[] = "$effect $cents";
					break;

				case 'reverb':
					$reverberance = mt_rand(20, 80);
					$damping      = mt_rand(10, 80);
					$scale        = mt_rand(85, 100);
					$depth        = mt_rand(90, 100);
					$predelay     = mt_rand(0, 5);
					$outEffects[] = "$effect $reverberance $damping $scale $depth $predelay";
					break;

				case 'tempo':
					$factor = mt_rand(65, 135) / 100.0;
					$outEffects[] = "$effect -s $factor";
					break;

				case 'tremolo':
					$hz    = mt_rand(10, 30);
					$depth = mt_rand(40, 85);
					$outEffects[] = "$effect $hz $depth";
					break;
			}
		}

		return implode(' ', $outEffects);
	}

	/**
	 * This function is not yet used.
	 *
	 * Generate random background noise from sweeping oscillators
	 *
	 * @param float $duration  How long in seconds the generated sound will be
	 * @param int $numChannels Number of channels in output wav
	 * @param int $sampleRate  Sample rate of output wav
	 * @param int $bitRate     Bits per sample (8, 16, 24)
	 * @return string          Audio data in wav format
	 */
	public function getSoxNoiseData($duration, $numChannels, $sampleRate, $bitRate)
	{
		$shapes = array('sine', 'square', 'triangle', 'sawtooth', 'trapezium');
		$steps  = array(':', '+', '/', '-');
		$selShapes = array_rand($shapes, 2);
		$selSteps  = array_rand($steps, 2);
		$sweep0    = array();
		$sweep0[0] = mt_rand(100, 700);
		$sweep0[1] = mt_rand(1500, 2500);
		$sweep1    = array();
		$sweep1[0] = mt_rand(500, 1000);
		$sweep1[1] = mt_rand(1200, 2000);

		if (mt_rand(0, 10) % 2 == 0)
		{
			$sweep0 = array_reverse($sweep0);
		}

		if (mt_rand(0, 10) % 2 == 0)
		{
			$sweep1 = array_reverse($sweep1);
		}

		$cmd = sprintf("%s -c %d -r %d -b %d -n -t wav - synth noise create vol 0.3 synth %.2f %s mix %d%s%d vol 0.3 synth %.2f %s fmod %d%s%d vol 0.3",
			$this->sox_binary_path,
			$numChannels,
			$sampleRate,
			$bitRate,
			$duration,
			$shapes[$selShapes[0]],
			$sweep0[0],
			$steps[$selSteps[0]],
			$sweep0[1],
			$duration,
			$shapes[$selShapes[1]],
			$sweep1[0],
			$steps[$selSteps[1]],
			$sweep1[1]
		);
		$data = `$cmd`;

		return $data;
	}
}