<?php
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

namespace RobotessNet\DeleteYourself {

    use RobotessNet\DeleteFromFl\Form;
    use RobotessNet\DeleteFromFl\Handler;

    require 'config.php';

    require_once('mod_errorlogs.php');
    require_once('mod_owned.php');
    require_once('mod_members.php');
    require_once('mod_settings.php');

    if (!isset($listing)) {
        echo '!! You haven\'t set $listing variable in config.php. Please set it first - the instruction is at the end of the file.<br/>';

        return;
    }

    $info = get_listing_info($listing);
    $errorstyle = ' style="font-weight: bold; display: block;" ' .
        'class="show_update_error"';

    if (isset($_POST['enth_delettte'])) {
        $handler = new Handler();
        $success = $handler->handle($listing, $_POST, $info, $errorstyle);
        if (!$success && $handler->isShowForm()) {
            $form = new Form();
            $form->print($info, $errorstyle, $handler->getCleanEmail(), $handler->getResponseMessage());
            return;
        }
        echo $handler->getResponseMessage();
        return;
    }

    $form = new Form();
    $form->print($info, $errorstyle);
}
