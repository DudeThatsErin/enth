<?php
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
 *****************************************************************************
 */

use RobotessNet\EnthusiastErrorHandler;

require_once('mod_robotess_errorhandler.php');

/**
 * @param $page
 * @param $text
 * @param bool $kill
 *
 * @return bool
 * @deprecated
 *
 * Use trigger_error instead
 */
function log_error($page, $text, $kill = true)
{
    require 'config.php';
    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    return EnthusiastErrorHandler::instance($db_link, $db_settings, $db_errorlog)->handleError($page, E_USER_WARNING, $text, true, $kill);
}

/*___________________________________________________________________________*/
function get_logs($start = 'none', $date = '')
{
    require 'config.php';
    $query = "SELECT * FROM `$db_errorlog`";
    if ($date) {
        $query .= " WHERE `date` = '$date'";
    }
    $query .= ' ORDER BY `date` DESC';
    if (ctype_digit($start)) {
        $query .= " LIMIT $start, " . get_setting('per_page');
    }
    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }
    $result = $db_link->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $logs = [];
    $result->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $result->fetch()) {
        $logs[] = $row;
    }

    return $logs;
}


/*___________________________________________________________________________*/
function flush_logs()
{
    require 'config.php';
    $query = "TRUNCATE `$db_errorlog`";
    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $result = $db_link->prepare($query);
        $result->execute();
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    return $result;
}