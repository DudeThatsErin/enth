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

use RobotessNet\PaginationUtils;

session_start();
require_once('logincheck.inc.php');
if (!isset($logged_in) || !$logged_in) {
    $_SESSION['message'] = 'You are not logged in. Please log in to continue.';
    $next = '';
    if (isset($_SERVER['REQUEST_URI'])) {
        $next = $_SERVER['REQUEST_URI'];
    } elseif (isset($_SERVER['PATH_INFO'])) {
        $next = $_SERVER['PATH_INFO'];
    }
    $_SESSION['next'] = $next;
    header('location: index.php');
    die('Redirecting you...');
}
require('config.php');
require_once('mod_errorlogs.php');
require_once('mod_settings.php');

$postAction = '';
if (isset($_POST["action"])) {
    $postAction = $_POST['action'];
}
if ($postAction === 'flush') {
    flush_logs();
    header('location: ' . $_SERVER['PHP_SELF'] . '?action=flushed');
    die('Redirecting you...');
}

require_once('header.php');

$show_default = true;
?>
    <h1>Enthusiast 3 Error Logs</h1>
<?php

$getAction = '';
if (isset($_GET["action"])) {
    $getAction = $_GET['action'];
}

/*______________________________________________________________________EDIT_*/

if ($getAction === 'flushed') {
    echo '<p class="success">Error logs flushed.</p>';
}

/*___________________________________________________________________DEFAULT_*/
if ($show_default) {
    ?>
    <div class="submenu">
        <form action="errorlog.php" method="post">
            <input type="hidden" name="action" value="flush"/>
            <button type="submit"
                    onclick="if(!confirm( 'Are you sure you wish to flush your error logs?' )){return false}">Flush logs
            </button>
        </form>
    </div>

    <p>You may see all existing error logs on this page. These error logs
        keep track of the errors that your installation outputs.</p>
    <p>This feature is here for (hopefully) easier debugging of errors. However,
        turning this feature on takes up a fraction of your database space,
        for each database error that your installation generates. Feel free to
        turn this off if your installation is running smoothly, and remember to
        flush the logs regularly.</p>
    <p>There are different types of messages: error, script error, warning, notice. Most important are error and script error, and you can ignore warnings and notices unless there's some issue with the script. When asking for help, make sure to share the whole log.</p>

    <table>

        <tr>
            <th>Date</th>
            <th>Source</th>
            <th>Log</th>
        </tr>
    <?php
    $start = $_REQUEST['start'] ?? '0';
    $logs = get_logs($start);
    $total = count(get_logs());
    $datestring = 'Y-m-d h:i:s';

    $shade = false;
    foreach ($logs as $l) {
        $class = ($shade) ? ' class="rowshade"' : '';
        $shade = !$shade;
        echo "<tr$class><td>";
        echo date($datestring, strtotime($l['date']));
        echo '</td><td>' . $l['source'];
        echo '</td><td>' . $l['log'];
    }
    echo '</table>';

    $url = 'errorlog.php';
    $connector = '?';
    foreach ($_GET as $key => $value) {
        if ($key !== 'start' && $key !== 'PHPSESSID') {
            $url .= $connector . $key . '=' . $value;
            $connector = '&amp;';
        }
    }

    echo PaginationUtils::getPaginatorHTML($total, (int)get_setting('per_page'), $url . $connector);
}
require_once('footer.php');
