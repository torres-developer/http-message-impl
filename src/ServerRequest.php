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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

final class ServerRequest extends Request implements ServerRequestInterface
{
    private string $controller;
    private string $action;
    private array $parameters;

    private array $serverParams;
    private array $cookieParams = [];
    private array $queryParams;
    private array $uploadedFiles = [];

    private array|object|null $parsedBody;

    private array $attributes = [];

    public function __construct(
        UriInterface|string $resource = new URI("/", false),
        HTTPVerb|string $method = HTTPVerb::GET,
        StreamInterface|\SplFileObject|string|null $body = new Stream(null),
        Headers|iterable $headers = new Headers(),
        string $protocol = "",
        array $serverParams = []
    ) {
        parent::__construct($resource, $method, $body, $headers, $protocol);

        $this->serverParams = $serverParams;

        $this->findRoute($resource->getPath());
    }

    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        // to cast to self
        $req = (fn ($i): self => $i)(parent::withUri($uri, $preserveHost));

        $req->findRoute($req->getUri()->getPath());

        return $req;
    }

    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    public function withCookieParams(array $cookies): static
    {
        $req = clone $this;
        $req->cookieParams = [];

        return $req;
    }

    public function getQueryParams(): array
    {
        if (($queries = $this->queryParams) === null) {
            $queries = [];
            parse_str($this->resource->getQuery(), $queries);
        }

        return $queries;
    }

    public function withQueryParams(array $query): static
    {
        $req = clone $this;
        $req->queryParams = [];

        return $req;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    public function withUploadedFiles(array $uploadedFiles): static
    {
        foreach ($uploadedFiles as $i) {
            if (!($i instanceof UploadedFileInterface)) {
                throw new \InvalidArgumentException();
            }
        }

        $req = clone $this;
        $req->uploadedFiles = [];

        return $req;
    }

    public function getParsedBody(): null|array|object
    {
        return $this->parsedBody ?? null;
    }

    public function withParsedBody($data): static
    {
        if (!is_array($data) && !is_object($data) && $data !== null) {
            throw new \InvalidArgumentException();
        }

        $req = clone $this;
        $req->parsedBody = $data;

        return $req;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($name, $default = null): mixed
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException();
        }

        return $this->attributes[$name] ?? $default;
    }

    public function withAttribute($name, $value): static
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException();
        }

        $req = clone $this;
        $req->attributes[$name] = $value;

        return $req;
    }

    public function withoutAttribute($name): static
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException();
        }

        $req = clone $this;
        unset($req->attributes[$name]);

        return $req;
    }

    private function findRoute(string $path): void
    {
        $path = $path ?: "/";

        $path = explode(
            "/",
            trim(filter_var($path, FILTER_SANITIZE_URL), "/\//")
        );

        $controller = $path[0] ?? null;
        $action = $path[1] ?? null;

        unset($path[0], $path[1]);

        $this->parameters = array_values($path);

        $controller ??= "";
        $controller = explode("-", $controller);
        $controller = array_map(ucfirst(...), $controller);
        $this->controller = implode("", $controller);

        $this->action = $action ?? "index";
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
