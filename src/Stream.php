<?php

/**
 *        http-message-impl - An implementation of the psr-7, psr-17
 *        Copyright (C) 2023  João Torres
 *
 *        This program is free software: you can redistribute it and/or modify
 *        it under the terms of the GNU Affero General Public License as
 *        published by the Free Software Foundation, either version 3 of the
 *        License, or (at your option) any later version.
 *
 *        This program is distributed in the hope that it will be useful,
 *        but WITHOUT ANY WARRANTY; without even the implied warranty of
 *        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *        GNU Affero General Public License for more details.
 *
 *        You should have received a copy of the GNU Affero General Public License
 *        along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @package TorresDeveloper\\HTTPMessage
 * @author João Torres <torres.dev@disroot.org>
 * @copyright Copyright (C) 2023  João Torres
 * @license https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License
 * @license https://opensource.org/licenses/AGPL-3.0 GNU Affero General Public License version 3
 *
 * @since 1.0.0
 * @version 1.0.0
 */

namespace TorresDeveloper\HTTPMessage;

use Psr\Http\Message\StreamInterface;

final class Stream implements StreamInterface
{
    private ?\SplFileObject $body;

    public function __construct(\SplFileObject|string|null $body)
    {
        if (is_string($body) && $body) {
            $text = $body;

            $this->body = new \SplTempFileObject();
            //$this->body = new \SplFileObject("php://temp", "rw+");

            $this->write($text);
        } else {
            $this->body = $body ?: null;
        }
    }

    public function __toString(): string
    {
        try {
            return $this->getContents();
        } catch (\Exception) {
            return "";
        }
    }

    public function close(): void
    {
        $this->body = null;
    }

    public function detach(): ?\SplFileObject
    {
        $resource = $this->body;
        $this->body = null;

        return $resource;
    }

    public function getSize(): ?int
    {
        if ($this->body === null) {
            return null;
        }

        try {
            if (($size = $this->body->getSize()) === false) {
                throw new \RuntimeException();
            }
        } catch (\RuntimeException) {
            return null;
        }

        return $size;
    }

    public function tell(): int
    {
        if ($this->body === null) {
            throw new \RuntimeException("Could not tell");
        }

        if (($pos = $this->body->ftell()) === false) {
            throw new \RuntimeException();
        }

        return $pos;
    }

    public function eof(): bool
    {
        return $this->body && $this->body->eof();
    }

    public function isSeekable(): bool
    {
        return $this->body && $this->body->fseek(0, SEEK_CUR) === 0;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!is_int($offset) || !is_int($whence)) {
            throw new \InvalidArgumentException();
        }

        if ($this->body && $this->body->fseek($offset, $whence) === -1) {
            throw new \RuntimeException();
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->body && $this->body->isWritable();
    }

    public function write($string): int
    {
        if (!is_string($string)) {
            throw new \InvalidArgumentException();
        }

        if (!$this->body) {
            throw new \RuntimeException();
        }

        return $this->body->fwrite($string) ?: throw new \RuntimeException();
    }

    public function isReadable(): bool
    {
        return $this->body && $this->body->isReadable();
    }

    public function read($length): string
    {
        if (!is_int($length)) {
            throw new \InvalidArgumentException();
        }

        if (!$this->body) {
            throw new \RuntimeException();
        }

        return $this->body->fread($length) ?: "";
    }

    public function getContents(): string
    {
        if ($this->body === null) {
            throw new \RuntimeException("No body");
        }

        $pos = $this->tell();

        $this->rewind();

        $contents = "";
        while (!$this->eof()) {
            $contents .= $this->read(64);
        }

        $this->seek($pos);

        return $contents;
    }

    public function getMetadata($key = null): mixed
    {
        if (!is_string($key)) {
            throw new \InvalidArgumentException();
        }

        if ($this->body === null) {
            return $key ? null : [];
        }

        $stats = $this->body->fstat();

        return $key ? $stats[$key] : $stats;
    }

    public function __destruct()
    {
        $this->body = null;
    }
}
