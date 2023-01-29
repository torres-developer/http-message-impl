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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    use MessageTrait;

    private UriInterface $resource;

    private HTTPVerb $method;

    private string $requestTarget;

    public function __construct(
        UriInterface|string $resource = new URI("/"),
        HTTPVerb|string $method = HTTPVerb::GET,
        StreamInterface|\SplFileObject|string|null $body = new Stream(null),
        Headers|iterable $headers = new Headers(),
        string $protocol = ""
    ) {
        if (is_string($resource)) {
            $resource = new URI($resource);
        }

        $this->resource = $resource;

        if (is_string($method)) {
            $method = HTTPVerb::from(mb_strtoupper($method));
        }

        $this->method = $method;

        if (!($body instanceof StreamInterface)) {
            $body = new Stream($body);
        }

        $this->body = $body;

        if (!($headers instanceof Headers)) {
            $headers = new Headers($headers);
        }

        $this->headers = $headers;

        if (!isset($headers["Host"])) {
            $host = $resource->getHost()
                . ((($port = $resource->getPort()) === null) ? "" : ":$port");

            $headers["Host"] = $host;
        }

        $this->protocol = $protocol;
    }

    public function getRequestTarget(): string
    {
        if (!isset($this->requestTarget)) {
            $this->requestTarget = ($this->resource->getPath() ?: "")
                . (($query = $this->resource->getQuery()) ? "?$query" : "");
        }

        return $this->requestTarget;
    }

    public function withRequestTarget($requestTarget): static
    {
        if ($requestTarget === "*" && $this->method !== HTTPVerb::OPTIONS) {
            throw new \DomainException();
        }

        $matches = parse_url($requestTarget);

        if (isset($matches["host"], $matches["port"]) && $this->method === HTTPVerb::CONNECT) {
            $requestTarget = "$matches[host]:$matches[port]";
        }

        $req = clone $this;
        $req->requestTarget = $requestTarget ?: "/";

        return $req;
    }

    public function getMethod(): string
    {
        return $this->method->value;
    }

    public function withMethod($method): static
    {
        if (!is_string($method)) {
            throw new \InvalidArgumentException();
        }

        $req = clone $this;

        try {
            $req->method = HTTPVerb::from($method);
        } catch (\ValueError $e) {
            throw new \InvalidArgumentException(previous: $e);
        }

        return $req;
    }

    public function getUri(): UriInterface
    {
        return $this->resource;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        $req = clone $this;

        $host = $uri->getHost();

        if ($host && (!$preserveHost || !isset($req->headers["Host"]))) {
            $host .= ((($port = $uri->getPort()) === null) ? "" : ":$port");

            unset($req->headers["Host"]);
            $req->headers["Host"] = $host;
        }

        $req->resource = $uri;

        return $req;
    }

    public function getMethodHTTPVerb(): HTTPVerb
    {
        return $this->method;
    }

    public function withMethodHTTPVerb(HTTPVerb $method): static
    {
        $uri = clone $this;
        $uri->method = $method;

        return $uri;
    }
}
