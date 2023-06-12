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

trait MessageTrait
{
    protected StreamInterface $body;

    protected Headers $headers;

    protected string $protocol;

    public function getProtocolVersion(): string
    {
        $matches = [];

        preg_match("/(?<v>\d+\.\d+)$/", $this->protocol, $matches);

        return $matches["v"];
    }

    public function withProtocolVersion($version): static
    {
        if (!is_string($version)) {
            throw new \InvalidArgumentException();
        }

        if (!preg_match("/^\d+\.\d+$/", $version)) {
            throw new \InvalidArgumentException();
        }

        $req = clone $this;

        if (preg_replace("/\d+\.\d+$/", $version, $req->protocol) === null) {
            throw new \RuntimeException();
        }

        return $req;
    }

    public function getHeaders(): array
    {
        return $this->headers->toArray();
    }

    public function hasHeader($name): bool
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException();
        }

        return isset($this->headers[$name]);
    }

    public function getHeader($name): array
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException();
        }

        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine($name): string
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException();
        }

        return "$name: " . implode(",", $this->headers[$name]);
    }

    public function withHeader($name, $value): static
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException();
        }

        if (!is_string($value) && !is_array($value)) {
            throw new \InvalidArgumentException();
        }

        if (is_string($value)) {
            $value = [$value];
        }

        $req = clone $this;

        unset($req->headers[$name]);

        foreach ($value as $i) {
            $req->headers[$name] = (string) $i;
        }

        return $req;
    }

    public function withAddedHeader($name, $value): static
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException();
        }

        if (!is_string($value) && !is_array($value)) {
            throw new \InvalidArgumentException();
        }

        if (is_string($value)) {
            $value = [$value];
        }

        $req = clone $this;

        foreach ($value as $i) {
            $req->headers[$name] = (string) $i;
        }

        return $req;
    }


    public function withoutHeader($name): static
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException();
        }

        $req = clone $this;

        unset($req->headers[$name]);

        return $req;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        $req = clone $this;

        $req->body = $body;

        return $req;
    }
}
