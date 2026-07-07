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
use Brainforgeuk\Plugin\Captcha\Bfsecurimage\Exceptions\SecurimageWavformatException;

\defined('_JEXEC') or die;

Trait WavTrait
{
	/**
	 * Construct a wav header from this object. Includes "fact" chunk if necessary.
	 * http://www-mmsp.ece.mcgill.ca/documents/audioformats/wave/wave.html
	 *
	 * @return string  The RIFF header data.
	 */
	public function makeHeader()
	{
		// reset and recalculate
		$this->setAudioFormat();                                    // implicit setAudioSubFormat(), setFactChunkSize(), setFmtExtendedSize(), setFmtChunkSize(), setSize(), setActualSize(), setDataOffset()
		$this->setNumBlocks();

		// RIFF header
		$header = pack('N', 0x52494646);                            // ChunkID - "RIFF"
		$header .= pack('V', $this->getChunkSize());                // ChunkSize
		$header .= pack('N', 0x57415645);                           // Format - "WAVE"

		// "fmt " subchunk
		$header .= pack('N', 0x666d7420);                           // SubchunkID - "fmt "
		$header .= pack('V', $this->getFmtChunkSize());             // SubchunkSize
		$header .= pack('v', $this->getAudioFormat());              // AudioFormat
		$header .= pack('v', $this->getNumChannels());              // NumChannels
		$header .= pack('V', $this->getSampleRate());               // SampleRate
		$header .= pack('V', $this->getByteRate());                 // ByteRate
		$header .= pack('v', $this->getBlockAlign());               // BlockAlign
		$header .= pack('v', $this->getBitsPerSample());            // BitsPerSample
		if($this->getFmtExtendedSize() == 24)
		{
			$header .= pack('v', 22);                               // extension size = 24 bytes, cbSize: 24 - 2 = 22 bytes
			$header .= pack('v', $this->getValidBitsPerSample());   // ValidBitsPerSample
			$header .= pack('V', $this->getChannelMask());          // ChannelMask
			$header .= pack('H32', $this->getAudioSubFormat());     // SubFormat
		}
		elseif ($this->getFmtExtendedSize() == 2)
		{
			$header .= pack('v', 0);                                // extension size = 2 bytes, cbSize: 2 - 2 = 0 bytes
		}

		// "fact" subchunk
		if ($this->getFactChunkSize() == 4)
		{
			$header .= pack('N', 0x66616374);                       // SubchunkID - "fact"
			$header .= pack('V', 4);                                // SubchunkSize
			$header .= pack('V', $this->getNumBlocks());            // SampleLength (per channel)
		}

		return $header;
	}

	/**
	 * Construct wav DATA chunk.
	 *
	 * @return string  The DATA header and chunk.
	 */
	public function getDataSubchunk()
	{
		// check preconditions
		if (!$this->_dataSize_valid)
		{
			$this->setDataSize();  // implicit setSize(), setActualSize(), setNumBlocks()
		}


		// create subchunk
		return pack('N', 0x64617461) .                    // SubchunkID - "data"
			pack('V', $this->getDataSize()) .          // SubchunkSize
			$this->_samples .                          // Subchunk data
			($this->getDataSize() & 1 ? chr(0) : '');  // padding byte
	}

	/**
	 * Save the wav data to a file.
	 *
	 * @param string $filename  (Required) The file path to save the wav to.
	 * @throws SecurimageWavfileException
	 */
	public function save($filename)
	{
		$fp = @fopen($filename, 'w+b');
		if (!is_resource($fp))
		{
			throw new SecurimageWavfileException('Failed to open "' . $filename . '" for writing.');
		}

		fwrite($fp, $this->makeHeader());
		fwrite($fp, $this->getDataSubchunk());
		fclose($fp);

		return $this;
	}

	/**
	 * Reads a wav header and data from a file.
	 *
	 * @param string $filename  (Required) The path to the wav file to read.
	 * @param bool $readData  (Optional) If true, also read the data chunk.
	 * @throws SecurimageWavformatException
	 * @throws SecurimageWavfileException
	 */
	public function openWav($filename, $readData = true)
	{
		// check preconditions
		if (!file_exists($filename))
		{
			throw new SecurimageWavfileException('Failed to open "' . $filename . '". File not found.');
		}

		if (!is_readable($filename))
		{
			throw new SecurimageWavfileException('Failed to open "' . $filename . '". File is not readable.');
		}

		if (is_resource($this->_fp))
		{
			$this->closeWav();
		}

		// open the file
		$this->_fp = @fopen($filename, 'rb');
		if (!is_resource($this->_fp))
		{
			throw new SecurimageWavfileException('Failed to open "' . $filename . '".');
		}

		// read the file
		return $this->readWav($readData);
	}

	/**
	 * Close a with openWav() previously opened wav file or free the buffer of setWavData().
	 * Not necessary if the data has been read (readData = true) already.
	 */
	public function closeWav()
	{
		if (is_resource($this->_fp)) fclose($this->_fp);

		return $this;
	}

	/**
	 * Set the wav file data and properties from a wav file in a string.
	 *
	 * @param string $data  (Required) The wav file data. Passed by reference.
	 * @param bool $free  (Optional) True to free the passed $data after copying.
	 * @throws SecurimageWavformatException
	 * @throws SecurimageWavfileException
	 */
	public function setWavData(&$data, $free = true)
	{
		// check preconditions
		if (is_resource($this->_fp)) $this->closeWav();


		// open temporary stream in memory
		$this->_fp = @fopen('php://memory', 'w+b');
		if (!is_resource($this->_fp))
		{
			throw new SecurimageWavfileException('Failed to open memory stream to write wav data. Use openWav() instead.');
		}

		// prepare stream
		fwrite($this->_fp, $data);
		rewind($this->_fp);

		// free the passed data
		if ($free) $data = null;

		// read the stream like a file
		return $this->readWav(true);
	}

	/**
	 * Read wav file from a stream.
	 *
	 * @param bool $readData  (Optional) If true, also read the data chunk.
	 * @throws SecurimageWavformatException
	 * @throws SecurimageWavfileException
	 */
	protected function readWav($readData = true)
	{
		if (!is_resource($this->_fp))
		{
			throw new SecurimageWavfileException('No wav file open. Use openWav() first.');
		}

		try
		{
			$this->readWavHeader();
		}
		catch (SecurimageWavfileException $ex)
		{
			$this->closeWav();
			throw $ex;
		}

		if ($readData) return $this->readWavData();

		return $this;
	}

	/**
	 * Parse a wav header.
	 * http://www-mmsp.ece.mcgill.ca/documents/audioformats/wave/wave.html
	 *
	 * @throws SecurimageWavformatException
	 * @throws SecurimageWavfileException
	 */
	protected function readWavHeader()
	{
		if (!is_resource($this->_fp))
		{
			throw new SecurimageWavfileException('No wav file open. Use openWav() first.');
		}

		// get actual file size
		$stat = fstat($this->_fp);
		$actualSize = $stat['size'];

		$this->_actualSize = $actualSize;


		// read the common header
		$header = fread($this->_fp, 36);  // minimum size of the wav header
		if (strlen($header) < 36)
		{
			throw new SecurimageWavformatException('Not wav format. Header too short.', 1);
		}

		// check "RIFF" header
		$RIFF = unpack('NChunkID/VChunkSize/NFormat', $header);

		if ($RIFF['ChunkID'] != 0x52494646) {  // "RIFF"
			throw new SecurimageWavformatException('Not wav format. "RIFF" signature missing.', 2);
		}

		if ($this->getIgnoreChunkSizes())
		{
			$RIFF['ChunkSize'] = $actualSize - 8;
		}
		else if ($actualSize - 8 < $RIFF['ChunkSize'])
		{
			trigger_error('"RIFF" chunk size does not match actual file size. Found ' . $RIFF['ChunkSize'] . ', expected ' . ($actualSize - 8) . '.', E_USER_NOTICE);
			$RIFF['ChunkSize'] = $actualSize - 8;
		}

		if ($RIFF['Format'] != 0x57415645)
		{  // "WAVE"
			throw new SecurimageWavformatException('Not wav format. "RIFF" chunk format is not "WAVE".', 4);
		}

		$this->_chunkSize = $RIFF['ChunkSize'];


		// check common "fmt " subchunk
		$fmt = unpack('NSubchunkID/VSubchunkSize/vAudioFormat/vNumChannels/'
			.'VSampleRate/VByteRate/vBlockAlign/vBitsPerSample',
			substr($header, 12));

		if ($fmt['SubchunkID'] != 0x666d7420)
		{  // "fmt "
			throw new SecurimageWavformatException('Bad wav header. Expected "fmt " subchunk.', 11);
		}

		if ($fmt['SubchunkSize'] < 16)
		{
			throw new SecurimageWavformatException('Bad "fmt " subchunk size.', 12);
		}

		if (   $fmt['AudioFormat'] != self::WAVE_FORMAT_PCM
			&& $fmt['AudioFormat'] != self::WAVE_FORMAT_IEEE_FLOAT
			&& $fmt['AudioFormat'] != self::WAVE_FORMAT_EXTENSIBLE)
		{
			throw new SecurimageWavformatException('Unsupported audio format. Only PCM or IEEE FLOAT (EXTENSIBLE) audio is supported.', 13);
		}

		if ($fmt['NumChannels'] < 1 || $fmt['NumChannels'] > self::MAX_CHANNEL)
		{
			throw new SecurimageWavformatException('Invalid number of channels in "fmt " subchunk.', 14);
		}

		if ($fmt['SampleRate'] < 1 || $fmt['SampleRate'] > self::MAX_SAMPLERATE)
		{
			throw new SecurimageWavformatException('Invalid sample rate in "fmt " subchunk.', 15);
		}

		if (   ($fmt['AudioFormat'] == self::WAVE_FORMAT_PCM && !in_array($fmt['BitsPerSample'], array(8, 16, 24)))
			|| ($fmt['AudioFormat'] == self::WAVE_FORMAT_IEEE_FLOAT && $fmt['BitsPerSample'] != 32)
			|| ($fmt['AudioFormat'] == self::WAVE_FORMAT_EXTENSIBLE && !in_array($fmt['BitsPerSample'], array(8, 16, 24, 32))))
		{
			throw new SecurimageWavformatException('Only 8, 16 and 24-bit PCM and 32-bit IEEE FLOAT (EXTENSIBLE) audio is supported.', 16);
		}

		$blockAlign = $fmt['NumChannels'] * $fmt['BitsPerSample'] / 8;
		if ($blockAlign != $fmt['BlockAlign'])
		{
			trigger_error('Invalid block align in "fmt " subchunk. Found ' . $fmt['BlockAlign'] . ', expected ' . $blockAlign . '.', E_USER_NOTICE);
			$fmt['BlockAlign'] = $blockAlign;
		}

		$byteRate = $fmt['SampleRate'] * $blockAlign;
		if ($byteRate != $fmt['ByteRate'])
		{
			trigger_error('Invalid average byte rate in "fmt " subchunk. Found ' . $fmt['ByteRate'] . ', expected ' . $byteRate . '.', E_USER_NOTICE);
			$fmt['ByteRate'] = $byteRate;
		}

		$this->_fmtChunkSize  = $fmt['SubchunkSize'];
		$this->_audioFormat   = $fmt['AudioFormat'];
		$this->_numChannels   = $fmt['NumChannels'];
		$this->_sampleRate    = $fmt['SampleRate'];
		$this->_byteRate      = $fmt['ByteRate'];
		$this->_blockAlign    = $fmt['BlockAlign'];
		$this->_bitsPerSample = $fmt['BitsPerSample'];

		// read extended "fmt " subchunk data
		$extendedFmt = '';
		if ($fmt['SubchunkSize'] > 16)
		{
			// possibly handle malformed subchunk without a padding byte
			$extendedFmt = fread($this->_fp, $fmt['SubchunkSize'] - 16 + ($fmt['SubchunkSize'] & 1));  // also read padding byte
			if (strlen($extendedFmt) < $fmt['SubchunkSize'] - 16)
			{
				throw new SecurimageWavformatException('Not wav format. Header too short.', 1);
			}
		}

		// check extended "fmt " for EXTENSIBLE Audio Format
		if ($fmt['AudioFormat'] == self::WAVE_FORMAT_EXTENSIBLE)
		{
			if (strlen($extendedFmt) < 24)
			{
				throw new SecurimageWavformatException('Invalid EXTENSIBLE "fmt " subchunk size. Found ' . $fmt['SubchunkSize'] . ', expected at least 40.', 19);
			}

			$extensibleFmt = unpack('vSize/vValidBitsPerSample/VChannelMask/H32SubFormat', substr($extendedFmt, 0, 24));

			if (   $extensibleFmt['SubFormat'] != self::WAVE_SUBFORMAT_PCM
				&& $extensibleFmt['SubFormat'] != self::WAVE_SUBFORMAT_IEEE_FLOAT)
			{
				throw new SecurimageWavformatException('Unsupported audio format. Only PCM or IEEE FLOAT (EXTENSIBLE) audio is supported.', 13);
			}

			if (   ($extensibleFmt['SubFormat'] == self::WAVE_SUBFORMAT_PCM && !in_array($fmt['BitsPerSample'], array(8, 16, 24)))
				|| ($extensibleFmt['SubFormat'] == self::WAVE_SUBFORMAT_IEEE_FLOAT && $fmt['BitsPerSample'] != 32))
			{
				throw new SecurimageWavformatException('Only 8, 16 and 24-bit PCM and 32-bit IEEE FLOAT (EXTENSIBLE) audio is supported.', 16);
			}

			if ($extensibleFmt['Size'] != 22)
			{
				trigger_error('Invaid extension size in EXTENSIBLE "fmt " subchunk.', E_USER_NOTICE);
				$extensibleFmt['Size'] = 22;
			}

			if ($extensibleFmt['ValidBitsPerSample'] != $fmt['BitsPerSample'])
			{
				trigger_error('Invaid or unsupported valid bits per sample in EXTENSIBLE "fmt " subchunk.', E_USER_NOTICE);
				$extensibleFmt['ValidBitsPerSample'] = $fmt['BitsPerSample'];
			}

			if ($extensibleFmt['ChannelMask'] != 0)
			{
				// count number of set bits - Hamming weight
				$c = (int)$extensibleFmt['ChannelMask'];
				$n = 0;
				while ($c > 0) {
					$n += $c & 1;
					$c >>= 1;
				}
				if ($n != $fmt['NumChannels'] || (((int)$extensibleFmt['ChannelMask'] | self::SPEAKER_ALL) != self::SPEAKER_ALL)) {
					trigger_error('Invalid channel mask in EXTENSIBLE "fmt " subchunk. The number of channels does not match the number of locations in the mask.', E_USER_NOTICE);
					$extensibleFmt['ChannelMask'] = 0;
				}
			}

			$this->_fmtExtendedSize    = strlen($extendedFmt);
			$this->_validBitsPerSample = $extensibleFmt['ValidBitsPerSample'];
			$this->_channelMask        = $extensibleFmt['ChannelMask'];
			$this->_audioSubFormat     = $extensibleFmt['SubFormat'];

		}
		else
		{
			$this->_fmtExtendedSize    = strlen($extendedFmt);
			$this->_validBitsPerSample = $fmt['BitsPerSample'];
			$this->_channelMask        = 0;
			$this->_audioSubFormat     = null;
		}


		// read additional subchunks until "data" subchunk is found
		$factSubchunk = array();
		$dataSubchunk = array();

		while (!feof($this->_fp))
		{
			$subchunkHeader = fread($this->_fp, 8);
			if (strlen($subchunkHeader) < 8)
			{
				throw new SecurimageWavformatException('Missing "data" subchunk.', 101);
			}

			$subchunk = unpack('NSubchunkID/VSubchunkSize', $subchunkHeader);

			if ($subchunk['SubchunkID'] == 0x66616374)
			{        // "fact"
				// possibly handle malformed subchunk without a padding byte
				$subchunkData = fread($this->_fp, $subchunk['SubchunkSize'] + ($subchunk['SubchunkSize'] & 1));  // also read padding byte
				if (strlen($subchunkData) < 4) {
					throw new SecurimageWavformatException('Invalid "fact" subchunk.', 102);
				}

				$factParams = unpack('VSampleLength', substr($subchunkData, 0, 4));
				$factSubchunk = array_merge($subchunk, $factParams);

			}
			elseif ($subchunk['SubchunkID'] == 0x64617461)
			{  // "data"
				$dataSubchunk = $subchunk;

				break;

			}
			elseif ($subchunk['SubchunkID'] == 0x7761766C)
			{  // "wavl"
				throw new SecurimageWavformatException('Wave List Chunk ("wavl" subchunk) is not supported.', 106);
			}
			else
			{
				// skip all other (unknown) subchunks
				// possibly handle malformed subchunk without a padding byte
				if ( $subchunk['SubchunkSize'] < 0
					|| fseek($this->_fp, $subchunk['SubchunkSize'] + ($subchunk['SubchunkSize'] & 1), SEEK_CUR) !== 0) {  // also skip padding byte
					throw new SecurimageWavformatException('Invalid subchunk (0x' . dechex($subchunk['SubchunkID']) . ') encountered.', 103);
				}
			}
		}

		if (empty($dataSubchunk))
		{
			throw new SecurimageWavformatException('Missing "data" subchunk.', 101);
		}

		// check "data" subchunk
		$dataOffset = ftell($this->_fp);
		if ($this->getIgnoreChunkSizes())
		{
			$dataSubchunk['SubchunkSize'] = $actualSize - $dataOffset;
		}
		elseif ($dataSubchunk['SubchunkSize'] < 0 || $actualSize - $dataOffset < $dataSubchunk['SubchunkSize'])
		{
			trigger_error("Invalid \"data\" subchunk size (found {$dataSubchunk['SubchunkSize']}.", E_USER_NOTICE);
			$dataSubchunk['SubchunkSize'] = $actualSize - $dataOffset;
		}

		$this->_dataOffset     = $dataOffset;
		$this->_dataSize       = $dataSubchunk['SubchunkSize'];
		$this->_dataSize_fp    = $dataSubchunk['SubchunkSize'];
		$this->_dataSize_valid = false;
		$this->_samples        = '';

		// check "fact" subchunk
		$numBlocks = (int)($dataSubchunk['SubchunkSize'] / $fmt['BlockAlign']);

		if (empty($factSubchunk))
		{  // construct fake "fact" subchunk
			$factSubchunk = array('SubchunkSize' => 0, 'SampleLength' => $numBlocks);
		}

		if ($factSubchunk['SampleLength'] != $numBlocks)
		{
			trigger_error('Invalid sample length in "fact" subchunk.', E_USER_NOTICE);
			$factSubchunk['SampleLength'] = $numBlocks;
		}

		$this->_factChunkSize = $factSubchunk['SubchunkSize'];
		$this->_numBlocks     = $factSubchunk['SampleLength'];

		return $this;
	}

	/**
	 * Read the wav data from the file into the buffer.
	 *
	 * @param int $dataOffset  (Optional) The byte offset to skip before starting to read. Must be a multiple of BlockAlign.
	 * @param int $dataSize  (Optional) The size of the data to read in bytes. Must be a multiple of BlockAlign. Defaults to all data.
	 * @throws SecurimageWavfileException
	 */
	public function readWavData($dataOffset = 0, $dataSize = null)
	{
		// check preconditions
		if (!is_resource($this->_fp))
		{
			throw new SecurimageWavfileException('No wav file open. Use openWav() first.');
		}

		if ($dataOffset < 0 || $dataOffset % $this->getBlockAlign() > 0)
		{
			throw new SecurimageWavfileException('Invalid data offset. Has to be a multiple of BlockAlign.');
		}

		if (is_null($dataSize))
		{
			$dataSize = $this->_dataSize_fp - ($this->_dataSize_fp % $this->getBlockAlign());  // only read complete blocks
		}
		elseif ($dataSize < 0 || $dataSize % $this->getBlockAlign() > 0)
		{
			throw new SecurimageWavfileException('Invalid data size to read. Has to be a multiple of BlockAlign.');
		}


		// skip offset
		if ($dataOffset > 0 && fseek($this->_fp, $dataOffset, SEEK_CUR) !== 0)
		{
			throw new SecurimageWavfileException('Seeking to data offset failed.');
		}

		// read data
		$this->_samples .= fread($this->_fp, $dataSize);  // allow appending
		$this->setDataSize();  // implicit setSize(), setActualSize(), setNumBlocks()

		// close file or memory stream
		return $this->closeWav();
	}

	/**
	 * Append a wav file to the current wav. <br />
	 * The wav files must have the same sample rate, number of bits per sample, and number of channels.
	 *
	 * @param SecurimageWavfileClass $wav  (Required) The wav file to append.
	 * @throws SecurimageWavfileException
	 */
	public function appendWav(SecurimageWavfileClass $wav)
	{
		// basic checks
		if ($wav->getSampleRate() != $this->getSampleRate())
		{
			throw new SecurimageWavfileException("Sample rate for wav files do not match.");
		}

		if ($wav->getBitsPerSample() != $this->getBitsPerSample())
		{
			throw new SecurimageWavfileException("Bits per sample for wav files do not match.");
		}

		if ($wav->getNumChannels() != $this->getNumChannels())
		{
			throw new SecurimageWavfileException("Number of channels for wav files do not match.");
		}

		$this->_samples .= $wav->_samples;

		$this->setDataSize();  // implicit setSize(), setActualSize(), setNumBlocks()

		return $this;
	}

	/**
	 * Mix 2 wav files together. <br />
	 * Both wavs must have the same sample rate and same number of channels.
	 *
	 * @param SecurimageWavfileClass $wav  (Required) The SecurimageWavfileClass to mix.
	 * @param float $normalizeThreshold  (Optional) See normalizeSample for an explanation.
	 * @throws SecurimageWavfileException
	 */
	public function mergeWav(SecurimageWavfileClass $wav, $normalizeThreshold = null)
	{
		return $this->filter(array(
			SecurimageWavfileClass::FILTER_MIX       => $wav,
			SecurimageWavfileClass::FILTER_NORMALIZE => $normalizeThreshold
		));
	}
}