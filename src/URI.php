<?php

/**
 *        http-message-impl - An implementation of the psr-7
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

use Psr\Http\Message\UriInterface;

/**
 * TODO: "/^(:?[\w\d\-\.~]|(%[[:xdigit]]{2})|[!$&'\(\)\*\+,;=]|:|@)/"
 * (lines 145 and 249), cant be rawurlencode but we need to urlencode the rest
 * for thr path, query and fragment
 */
final class URI implements UriInterface
{
    public const DEFAULT_PORTS = [
        "http" => [80],
        "ftp" => [20, 21],
        "ssh" => [22],
        "smtp" => [25],
        "gopher" => [70],
        "pop3" => [110],
        "imap" => [143, 220],
        "irc" => [194],
        "https" => [443],
    ];

    private ?string $scheme;
    private ?string $user;
    private ?string $password;
    private ?string $host;
    private ?int $port;
    private string $path;
    private ?string $query;
    private ?string $fragment;

    public function __construct(string $uri, bool $validate = true)
    {
        $uri = trim($uri);

        if ($validate && filter_var($uri, FILTER_VALIDATE_URL) === false) {
            throw new \DomainException("Invalid URI: $uri");
        }

        if (($matches = parse_url($uri)) === false) {
            throw new \DomainException("Invalid URI: $uri");
        }

        $this->scheme = $matches["scheme"] ?? null;
        $this->user = $matches["user"] ?? null;
        $this->password = $matches["password"] ?? null;
        $this->host = $matches["host"] ?? null;
        $this->port = isset($matches["port"])
            ? $this->filterPort($matches["port"])
            : null;
        $this->path = $matches["path"] ?? "/";
        $this->query = $matches["query"] ?? null;
        $this->fragment = $matches["fragment"] ?? null;
    }

    public function getScheme(): string
    {
        return strtolower($this->scheme ?? "");
    }

    public function getAuthority(): string
    {
        return (($userInfo = $this->getUserInfo()) ? "$userInfo@" : "")
            . $this->getHost()
            . ((($port = $this->getPort()) === null) ? "" : ":$port");
    }

    public function getUserInfo(): string
    {
        return $this->user
            ? $this->user . ($this->password === null ? "" : ":$this->password")
            : "";
    }

    public function getHost(): string
    {
        return strtolower($this->host ?? "");
    }

    public function getPort(): ?int
    {
        $scheme = $this->getScheme();

        if ((URI::DEFAULT_PORTS[$scheme] ?? null) === null) {
            return $this->port;
        }

        return in_array($this->port, URI::DEFAULT_PORTS[$scheme])
            ? null
            : $this->port;
    }

    public function getPath(): string
    {
        return ($this->path ?? "");
    }

    public function getQuery(): string
    {
        return ($this->query ?? "");
    }

    public function getFragment(): string
    {
        return ($this->fragment ?? "");
    }

    public function withScheme($scheme): static
    {
        if (!is_string($scheme)) {
            throw new \InvalidArgumentException();
        }

        $uri = clone $this;
        $uri->scheme = $scheme ?: null;

        return $uri;
    }

    public function withUserInfo($user, $password = null): static
    {
        if (!is_string($user)) {
            throw new \InvalidArgumentException();
        }

        if (!is_string($password) && $password !== null) {
            throw new \InvalidArgumentException();
        }

        $uri = clone $this;
        $uri->user = $user ?: null;
        $uri->password = $password ?: null;

        return $uri;
    }

    public function withHost($host): static
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException();
        }

        $uri = clone $this;
        $uri->host = $host ?: null;

        return $uri;
    }

    public function withPort($port): static
    {
        $port = $this->filterPort($port);

        $uri = clone $this;
        $uri->port = $port;

        return $uri;
    }

    public function withPath($path): static
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException();
        }

        $uri = clone $this;
        $uri->path = $path;

        return $uri;
    }

    public function withQuery($query): static
    {
        if (!is_string($query)) {
            throw new \InvalidArgumentException();
        }

        $uri = clone $this;
        $uri->query = $query ?: null;

        return $uri;
    }

    public function withFragment($fragment): static
    {
        if (!is_string($fragment)) {
            throw new \InvalidArgumentException();
        }

        $uri = clone $this;
        $uri->fragment = $fragment ?: null;

        return $uri;
    }

    public function __toString()
    {
        $path = $this->getPath();

        return (($scheme = $this->getScheme()) ? "$scheme:" : "")
            . (($authority = $this->getAuthority()) ? "//$authority" : "")
            . ((preg_match(
                "/^(:?[\w\d\-\.~]|(%[[:xdigit]]{2})|[!$&'\(\)\*\+,;=]|:|@)/",
                $path
            ) && $authority) ? "/$path" : ((preg_match(
                "/^\/+/",
                $path
            ) && !$authority) ? ("/" . ltrim($path, "/")) : $path))
            . (($query = $this->getQuery()) ? "?$query" : "")
            . (($fragment = $this->getFragment()) ? "#$fragment" : "");
    }

    private function filterPort(?int $port): ?int
    {
        if (!is_int($port) && $port !== null) {
            throw new \InvalidArgumentException();
        }

        // port -> unsigned 16 bit int
        if (is_int($port) && ($port < 0 || $port > 0xffff)) {
            throw new \InvalidArgumentException();
        }

        return $port;
    }
}
