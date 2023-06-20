<?php
declare(strict_types = 1);

/*****************************************************************************
 * Enthusiast: Listing Collective Management System
 * Copyright (c) by Angela Sabas http://scripts.indisguise.org/
 * Copyright (c) 2019 by Ekaterina (contributor) http://scripts.robotess.net
 * Copyright (c) 2023 by Erin (contributor) https://github.com/DudeThatsErin/enth
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

require_once('Robotess/Autoloader.php');
require_once('mod_robotess_errorhandler.php');

// automatically clean inputs
foreach ($_GET as $index => $value) {
    $_GET[$index] = RobotessNet\StringUtils::instance()
                                           ->clean($value);
}
foreach ($_POST as $index => $value) {
    // if the index has "template" or "desc" in it, leave it be!
    $leavehtml = false;
    if (substr_count($index, 'template') ||
        substr_count($index, 'header') ||
        substr_count($index, 'footer') ||
        substr_count($index, 'desc') ||
        substr_count($index, 'content') ||
        substr_count($index, 'emailbody')) {
        $leavehtml = true;
    }

    if (is_array($value)) {
        foreach ($value as $i => $v) {
            $value[$i] = RobotessNet\StringUtils::instance()
                                                ->clean($v, $leavehtml);
        }
        $_POST[$index] = $value;
    } else {
        $_POST[$index] = RobotessNet\StringUtils::instance()
                                                ->clean($value, $leavehtml);
    }
}

foreach ($_COOKIE as $index => $value) {
    if (is_array($value)) {
        foreach ($value as $i => $v) {
            $value[$i] = RobotessNet\StringUtils::instance()
                                                ->clean($v);
        }
        $_COOKIE[$index] = $value;
    } else {
        $_COOKIE[$index] = RobotessNet\StringUtils::instance()
                                                  ->clean($value);
    }
}

?>
<!DOCTYPE html>
<html lang="en" prefix="og: http://ogp.me/ns#">
<head>
    <meta name="language" content="en"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title> Enthusiast <?= RobotessNet\App::getVersion() ?> ~ Listing Collective Management System </title>
    <meta name="author"
          content="Angela Maria Protacia M. Sabas, Lysianthus <she@lysianth.us>, Ekaterina [http://robotess.net], Erin Skidds [https://github.com/DudeThatsErin/enth]"/>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="style.css?v=3"/>
    <script src="js.js?v=2" type="text/javascript"></script>
</head>
<body>

<div class="header">
    <img src="logo.gif" width="190" height="60" alt=""/>
    <div class="topmenu">
        <a href="dashboard.php">Dashboard</a>
        <a href="settings.php">Settings</a>
        <?= (isset($_COOKIE['e3login'], $_SESSION['logerrors']) && $_SESSION['logerrors'] === 'yes')
            ? '<a href="errorlog.php">Error Log</a> ' : '' ?>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="content">
