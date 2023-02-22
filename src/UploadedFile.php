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
use Psr\Http\Message\UploadedFileInterface;

class UploadedFile implements UploadedFileInterface
{
    private StreamInterface $stream;
    private bool $moved = false;

    private ?int $size;
    private int $error;
    private ?string $name;
    private ?string $type;

    public function __construct(
        StreamInterface $stream,
        ?int $size,
        int $error,
        ?string $name,
        ?string $type
    ) {
        $this->stream = $stream;
        $this->size = $size;
        $this->error = $error;
        $this->name = $name;
        $this->type = $type;
    }

    public static function from_FILES(array $file): ?static
    {
        if ($file["error"] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return new static(
            new Stream(new \SplFileObject($file["tmp_name"])),
            $file["size"],
            $file["error"],
            $file["name"],
            $file["type"]
        );
    }

    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new \RuntimeException();
        }

        return $this->stream;
    }

    public function moveTo($targetPath): void
    {
        if (!is_string($targetPath)) {
            throw new \InvalidArgumentException();
        }

        $target = new Stream(new \SplFileObject($targetPath, "w"));

        $target->write($this->stream->getContents());

        $this->moved = true;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    public function getClientMediaType(): ?string
    {
        return $this->type;
    }
}
