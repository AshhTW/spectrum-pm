<?php

declare(strict_types=1);

namespace pmmp\encoding;

use function chr;
use function class_exists;
use function ord;
use function pack;
use function strlen;
use function substr;
use function unpack;

if (class_exists(ByteBuffer::class, false)) {
	return;
}

/**
 * Compatibility shim for dependencies still using the old pmmp\encoding ByteBuffer API.
 */
final class ByteBuffer{
	private string $buffer = "";
	private int $readOffset = 0;
	private int $writeOffset = 0;

	public function reserve(int $capacity) : void{
		// PHP strings grow dynamically; this method exists for API compatibility.
	}

	public function trim() : void{
		$this->buffer = substr($this->buffer, 0, $this->writeOffset);
	}

	public function clear() : void{
		$this->buffer = "";
		$this->readOffset = 0;
		$this->writeOffset = 0;
	}

	public function getReadOffset() : int{
		return $this->readOffset;
	}

	public function setReadOffset(int $offset) : void{
		$this->readOffset = $offset;
	}

	public function setWriteOffset(int $offset) : void{
		$this->writeOffset = $offset;
		if ($offset < strlen($this->buffer)) {
			$this->buffer = substr($this->buffer, 0, $offset);
		}
	}

	public function getUsedLength() : int{
		return $this->writeOffset;
	}

	public function writeUnsignedByte(int $value) : void{
		$this->writeByteArray(chr($value & 0xff));
	}

	public function readUnsignedByte() : int{
		return ord($this->readByteArray(1));
	}

	public function writeUnsignedIntLE(int $value) : void{
		$this->writeByteArray(pack("V", $value));
	}

	public function readUnsignedIntLE() : int{
		$result = unpack("V", $this->readByteArray(4));
		return $result[1];
	}

	public function writeSignedLongLE(int $value) : void{
		$low = $value & 0xffffffff;
		$high = ($value >> 32) & 0xffffffff;
		$this->writeByteArray(pack("V2", $low, $high));
	}

	public function readSignedLongLE() : int{
		$result = unpack("Vlow/Vhigh", $this->readByteArray(8));
		return ($result["high"] << 32) | $result["low"];
	}

	public function writeByteArray(string $data) : void{
		$length = strlen($data);
		$this->buffer = substr($this->buffer, 0, $this->writeOffset) . $data . substr($this->buffer, $this->writeOffset + $length);
		$this->writeOffset += $length;
	}

	public function readByteArray(int $length) : string{
		$data = substr($this->buffer, $this->readOffset, $length);
		$this->readOffset += $length;
		return $data;
	}

	public function toString() : string{
		return substr($this->buffer, 0, $this->writeOffset);
	}
}
