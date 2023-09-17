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

final class PaginationUtils
{
    public static function getPaginatorHTML(int $totalEntries, int $perPage, string $url): string
    {
        $numberOfPages = (int)ceil($totalEntries / $perPage);

        if ($numberOfPages <= 1) {
            return '';
        }

        $result = '<p class="center">Go to page: ';

        $i = 1;
        while ($i <= $numberOfPages) {
            $start_link = ($i - 1) * $perPage;
            $result .= '<a href="' . $url . 'start=' . $start_link . '">' .
                $i . '</a> ';
            $i++;
        }

        $result .= '</p>';

        return $result;
    }
}
