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

final class Headers
{
    private array $headers;

    public function __construct()
    {
        $this->headers = [];
    }

    public function __set(string $name, mixed $value): void
    {
        $name = $this->keyGen($name);

        if ($this->__isset($name)) {
            array_push($this->headers[$name], $value);
        } else {
            $this->headers[$name] = [$value];
        }
    }

    public function __get(string $name): mixed
    {
        $name = $this->keyGen($name);

        return $this->headers[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        $name = $this->keyGen($name);

        return isset($this->headers[$name]);
    }

    public function __unset(string $name): void
    {
        $name = $this->keyGen($name);

        unset($this->headers[$name]);
    }

    public function toArray(): array
    {
        return $this->headers;
    }

    private function keyGen(string $key): string {
        return ucfirst(mb_strtolower($key));
    }
}
