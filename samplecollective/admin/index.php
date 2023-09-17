<?php
/*****************************************************************************
 * Enthusiast: Listing Collective Management System
 * Copyright (c) by Angela Sabas http://scripts.indisguise.org/
 * Copyright (c) 2019 by Ekaterina (contributor) http://scripts.robotess.net
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
session_start();

// if already logged in, redirect to dashboard.php
require_once('logincheck.inc.php');
if (isset($logged_in) && $logged_in) {
    header('location: dashboard.php');
    die('Redirecting you...');
}

$login_message = '';
if (isset($_SESSION['message'])) {
    $login_message = $_SESSION['message'];
}

require('config.php');
require_once('header.php');

$showstats = (isset($_GET['nostats'])) ? false : true;
?>

    <h1>Welcome to the Enthusiast <?= RobotessNet\App::getVersion() ?> admin panel
        for <?= get_setting('collective_title') ?>!</h1>

    <p>
    To log into the admin panel, enter your password in the login box on the left.
<?php
if ($showstats) {
    ?>
    Quick statistics for your collective and the listings under it is shown below.
    If you are having problems with the loading of this page, <a href="index.php?nostats">click here</a>
    to disable the statistics view temporarily and log in.</p>

    <h2>Quick Collective Stats</h2>
    <?php
    require_once('show_collective_stats.php');
    ?>
    <table class="stats">

        <tr>
            <td class="right">
                Number of categories:
            </td>
            <td>
                <?= $total_cats ?>
            </td>
        </tr>

        <tr>
            <td class="right">
                Number of joined listings:
            </td>
            <td>
                <?= $joined_approved ?> approved, <?= $joined_pending ?> pending
            </td>
        </tr>

        <tr>
            <td class="right">
                Number of owned listings:
            </td>
            <td>
                <?= $owned_current ?> current, <?= $owned_upcoming ?>
                upcoming, <?= $owned_pending ?> pending
            </td>
        </tr>

        <tr>
            <td class="right">
                Number of collective affiliates:
            </td>
            <td>
                <?= $affiliates_collective ?> affiliates
            </td>
        </tr>

        <tr>
            <td class="right">
                Newest owned listing
            </td>
            <td>
                <?php
                if (count($owned_newest) > 0) {
                    ?>
                    <a href="<?= $owned_newest['url'] ?>"><?= $owned_newest['title']
                        ?>: the <?= $owned_newest['subject'] ?> <?= $owned_newest['listingtype']
                        ?></a>
                    <?php
                } else {
                    echo 'None';
                }
                ?>
            </td>
        </tr>

        <tr>
            <td class="right">
                Newest joined listing
            </td>
            <td>
                <?php
                if (count($joined_newest) > 0) {
                    ?>
                    <a href="<?= $joined_newest['url'] ?>"><?= $joined_newest['subject'] ?></a>
                    <?php
                } else {
                    echo 'None';
                }
                ?>
            </td>
        </tr>

        <tr>
            <td class="right">
                Total members in collective:
            </td>
            <td>
                <?= $collective_total_fans_approved ?> (<?= $collective_total_fans_pending ?> pending)
            </td>
        </tr>

        <tr>
            <td class="right">
                Collective members growth rate:
            </td>
            <td>
                <?= $collective_fans_growth_rate ?> members/day
            </td>
        </tr>

    </table>

    <?php
} else {
    echo '</p>';
}
require_once('footer.php');
