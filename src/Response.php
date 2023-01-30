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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response implements ResponseInterface
{
    use MessageTrait;

    public const STATUS = [
        100 => "Continue",
        101 => "Switching Protocols",
        102 => "Processing",
        103 => "Early Hints",
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        207 => "Multi-Status",
        208 => "Already Reported",
        226 => "IM Used",
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        307 => "Temporary Redirect",
        308 => "Permanent Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Content Too Large",
        414 => "URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Range Not Satisfiable",
        417 => "Expectation Failed",
        421 => "Misdirected Request",
        422 => "Unprocessable Content",
        423 => "Locked",
        424 => "Failed Dependency",
        425 => "Too Early",
        426 => "Upgrade Required",
        428 => "Precondition Required",
        429 => "Too Many Requests",
        431 => "Request Header Fields Too Large",
        451 => "Unavailable For Legal Reasons",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported",
        506 => "Variant Also Negotiates",
        507 => "Insufficient Storage",
        508 => "Loop Detected",
        511 => "Network Authentication Required"
    ];

    protected int $status;
    protected string $statusText;

    public function __construct(
        int $status,
        string $reasonPhrase = null,
        StreamInterface|\SplFileObject|string|null $body = new Stream(null),
        Headers|iterable $headers = new Headers()
    ) {
        $this->status = $this->filterStatus($status);
        $this->statusText = $reasonPhrase ?? Response::STATUS[$status] ?? "";

        if (!($body instanceof StreamInterface)) {
            $body = new Stream($body);
        }

        $this->body = $body;

        if (!($headers instanceof Headers)) {
            $headers = new Headers($headers);
        }

        $this->headers = $headers;
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }

    public function withStatus($code, $reasonPhrase = null): static
    {
        if (
            !is_int($code)
            || (!is_string($reasonPhrase) && $reasonPhrase !== null)
        ) {
            throw new \InvalidArgumentException();
        }

        $res = clone $this;
        $res->status = $this->filterStatus($code);
        $res->statusText = $reasonPhrase
            ?? Response::STATUS[$reasonPhrase]
            ?? "";

        return $res;
    }

    public function getReasonPhrase(): string
    {
        return $this->statusText;
    }

    private function filterStatus(int $status): int
    {
        if ($status < 100 || $status >= 600) {
            throw new \DomainException();
        }

        return $status;
    }
}
