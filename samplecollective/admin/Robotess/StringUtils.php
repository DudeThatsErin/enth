<?php
declare(strict_types = 1);
/*****************************************************************************
 * Enthusiast: Listing Collective Management System
 * Copyright (c) by Ekaterina http://scripts.robotess.net
 *
 * Enthusiast is a tool for (fan)listing collective owners to easily
 * maintain their listing collectives and listings under that collective.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * For more information please view the readme.txt file.
 ******************************************************************************/

namespace RobotessNet;

final class StringUtils
{
    use Singleton;

    public function clean(?string $data, bool $leaveHtml = false): string
    {
        if ($data === null) {
            return '';
        }

        if ($leaveHtml) {
            $data = trim($data);
        } else {
            $data = trim(htmlentities(strip_tags($data), ENT_QUOTES));
        }

        $data = addslashes($data);

        return $data;
    }

    public function cleanNormalize(?string $data): string
    {
        if ($data === null) {
            return '';
        }

        return strtolower($this->clean($data));
    }

    public function isEmailValid(?string $email): bool
    {
        if ($email === null) {
            return false;
        }

        return (bool)preg_match("/^([A-Za-z0-9-_.+]+)@(([A-Za-z0-9-_]+\.)+)([a-zA-Z]{2,})$/i", $email);
    }
}
