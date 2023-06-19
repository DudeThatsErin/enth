<?php
declare(strict_types = 1);

/*****************************************************************************
 * Enthusiast: Listing Collective Management System
 * Copyright (c) by Angela Sabas http://scripts.indisguise.org/
 * Copyright (c) 2018 by Lysianthus (contributor) <she@lysianth.us>
 * Copyright (c) 2020 by Ekaterina http://scripts.robotess.net
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

use RobotessNet\EnthusiastErrorHandler;

require_once('Robotess/Autoloader.php');
{
    require 'config.php';
    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline, array $errcontext) use
    (
        $db_link,
        $db_settings,
        $db_errorlog
    ) {
        $flagIsErrorSuppressed = false;
        // error was suppressed with the @-operator
        if (error_reporting() === 0) {
            $flagIsErrorSuppressed = true;
        }

        return EnthusiastErrorHandler::instance($db_link, $db_settings, $db_errorlog)
                                     ->handleError($errfile . ':' . $errline, $errno, $errstr, !$flagIsErrorSuppressed,
                                         false);
    }, E_ALL);
}