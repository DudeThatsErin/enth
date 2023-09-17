<?php
declare(strict_types = 1);
/*****************************************************************************
 * Enthusiast: Listing Collective Management System
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

namespace RobotessNet;

use PDO;
use PDOException;
use function addslashes;
use function htmlspecialchars;
use const E_ERROR;
use const E_NOTICE;
use const E_USER_ERROR;
use const E_USER_WARNING;
use const E_WARNING;

class EnthusiastErrorHandler
{
    use Singleton;

    private $isMonitoring;
    private $dbLink;
    private $dbErrorLog;

    private function __construct(PDO $db_link, string $db_settings, string $db_errorlog)
    {
        $query = "SELECT `value` FROM `$db_settings` WHERE " .
            "`setting` = 'log_errors'";

        try {
            $result = $db_link->query($query);
        } catch (PDOException $e) {
            die('Error executing query: ' . $e->getMessage());
        }
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $row = $result->fetch();

        if($row !== false) {
            $this->isMonitoring = $row['value'] === 'yes';
        } else {
            $this->isMonitoring = false;
        }

        $this->dbErrorLog = $db_errorlog;
        $this->dbLink = $db_link;
    }

    public static function instance(PDO $db_link, string $db_settings, string $db_errorlog): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self($db_link, $db_settings, $db_errorlog);
        }

        return self::$instance;
    }

    public function handleError(string $page, int $errno, string $errstr, bool $showError = true, bool $kill = true): bool
    {
        $errstr = htmlspecialchars($errstr);

        switch ($errno) {
            case E_ERROR:
                $errhumantype = "ERROR";
                $kill = true;
                break;

            case E_WARNING:
                $errhumantype = "WARNING";
                $showError = false;
                break;

            case E_NOTICE:
                $errhumantype = "NOTICE";
                $showError = false;
                break;

            case E_USER_WARNING:
                $errhumantype = "[Script] WARNING";
                $showError = false;
                break;

            case E_USER_ERROR:
                $errhumantype = "[Script] ERROR";
                $kill = true;
                break;

            default:
                $errhumantype = "Unknown error type";
                break;
        }
        
        // check if we're monitoring errors!
        if ($this->isMonitoring) {
            $errstr = "$errhumantype: [$errno] $errstr<br />\n";
            $errstr = addslashes($errstr);
            
            $query = "INSERT INTO `$this->dbErrorLog` VALUES( NOW(), :page, :dtext )";
            try {
                $result = $this->dbLink->prepare($query);
                $result->bindParam(':page', $page);
                $result->bindParam(':dtext', $errstr);
                $result->execute();

                if(!$showError) {
                    // we're logging it anyway. Just don't show the message
                    return true;
                }
                
                echo $errhumantype . " occurred on the page. Please check logs.<br/>";
            } catch (PDOException $e) {
                die('Error executing query: ' . $e->getMessage());
            }
        } else if($showError) {
            echo $errhumantype." occurred on the page: $errstr<br/>";
        }

        if ($kill) {
            echo "DIE: Execution of the script stopped";
            die();
        }

        return true;
    }

}