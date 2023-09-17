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

final class App
{
    public static function getVersion(): string
    {
        return '[Erin\'s Fork] v. 1.2';
    }

    public static function getLinkWithOriginal(): string
    {
        return self::getLink() . ' (<a href="http://scripts.indisguise.org" target="_blank">original version</a>)';
    }

    public static function getLink(): string
    {
        return '<a href="https://github.com/DudeThatsErin/enth" target="_blank" title="PHP Scripts: Enthusiast, Siteskin, Codesort, FanUpdate, Listing Admin - ported to PHP 8">Enthusiast ' . self::getVersion() . '</a>';
    }
}
