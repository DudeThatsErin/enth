<?php
/*****************************************************************************
 * Enthusiast: Listing Collective Management System
 * Copyright (c) by Angela Sabas http://scripts.indisguise.org/
 * Copyright (c) 2018 by Lysianthus (contributor) <she@lysianth.us>
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
 *****************************************************************************
 */

/**
 * @param string $status
 * @param string $start
 * @param string $bydate
 *
 * @return array
 */
function get_owned($status = 'all', $start = 'none', $bydate = 'no')
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    $query = "SELECT `listingid` FROM `$db_owned`";

    if ($status == 'pending') {
        $query .= " WHERE `status` = 0";
    } elseif ($status == 'upcoming') {
        $query .= " WHERE `status` = 1";
    } elseif ($status == 'current') {
        $query .= " WHERE `status` = 2";
    }

    if ($bydate == 'bydate') {
        $query .= " ORDER BY `opened` DESC";
    } else {
        $query .= " ORDER BY `subject` ASC";
    }

    if ($start != 'none' && ctype_digit($start)) {
        $settingq = "SELECT `value` FROM `$db_settings` WHERE `setting` = " .
            "'per_page'";
        $result = $db_link->prepare($settingq);
        $result->execute();
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $row = $result->fetch();
        $limit = $row['value'];
        $query .= " LIMIT $start, $limit";
    }

    $result = $db_link->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    $ids = [];
    $result->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $result->fetch()) {
        $ids[] = $row['listingid'];
    }

    return $ids;
}


/*___________________________________________________________________________*/
function get_listing_info($id = '', $table = '')
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = '$id'";
    if ($table) {
        $query = "SELECT * FROM `$db_owned` WHERE `dbtable` = '$table'";
    }

    $result = $db_link->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    $result->setFetchMode(PDO::FETCH_ASSOC);
    $row = $result->fetch();
    if (!$row || count($row) === 0) {
        trigger_error(sprintf("There is no information about listing = %s in the database. Are you sure this is correct ID?",
            $id), E_USER_ERROR);
    }

    foreach ($row as $key => $value) {
        $row[$key] = stripslashes($value ?? '');
    }

    return $row;
}


/*___________________________________________________________________________*/
function show_edit_forms()
{
    require 'config.php';
    $info = get_listing_info($_REQUEST['id']);
    ?>
    <div class="submenu">
        <a href="owned.php?action=edit&id=<?= $info['listingid']
        ?>&type=database">Database</a>
        <a href="owned.php?action=edit&id=<?= $info['listingid']
        ?>&type=info">Info</a>
        <a href="owned.php?action=edit&id=<?= $info['listingid']
        ?>&type=settings">Settings</a>
        <a href="owned.php?action=edit&id=<?= $info['listingid']
        ?>&type=emails">Emails</a>
        <a href="owned.php?action=edit&id=<?= $info['listingid']
        ?>&type=templates">Templates</a>
    </div>

    <p>This page allows you to edit information and settings for the
        <i><?= $info['title'] ?>: <?= $info['subject'] ?>
            <?= $info['listingtype'] ?></i>. Click on one of the submenu items
        to modify the <?= $info['listingtype'] ?>.
    </p>

    <h2>Quick <?= $info['listingtype'] ?> stats</h2>
    <?php
    $stats = get_listing_stats($info['listingid']);
    // prepare date format
    $lastupdated = @date(get_setting('date_format'),
        strtotime($stats['lastupdated']));
    $countries = $stats['countries'];
    $average = $stats['average'];
    ?>
    <p><b>Last updated:</b> <?= $lastupdated ?><br/>
        <b>Members:</b> <?= $stats['total'] ?><?= ($info['country'] == 1)
            ? ' from ' . $countries . ' countries'
            : '' ?>, <?= $stats['pending'] ?> pending<br/>
        <b>Growth rate:</b> <?= $average ?> per day since opening
    </p>
    <?php

    if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'database') {
        ?>
        <form action="owned.php" method="post">
            <input type="hidden" name="action" value="edit"/>
            <input type="hidden" name="done" value="yes"/>
            <input type="hidden" name="type" value="database"/>
            <input type="hidden" name="id" value="<?= $_REQUEST['id'] ?>"/>

            <table>
                <tr>
                    <th colspan="2">Database/fields Settings</th>
                </tr>

                <tr>
                    <td>
                        Server
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="dbserver" value="<?= $info['dbserver'] ?>"/>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Name
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="dbdatabase" value="<?= $info['dbdatabase']
                        ?>"/>
                    </td>
                </tr>

                <tr>
                    <td>
                        User
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="dbuser" value="<?= $info['dbuser'] ?>"/>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Table
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="dbtable" value="<?= $info['dbtable'] ?>"/>
                    </td>
                </tr>

                <tr>
                    <td>
                        Password
                    </td>
                    <td style="text-align: left;">
                        <small>Fill out only if changing the password.</small><br/>
                        <input type="password" name="dbpassword"/>
                        <input type="password" name="dbpasswordv"/>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Country field
                    </td>
                    <td style="text-align: left;">
                        <?php
                        if ($info['country'] == 1) {
                            ?>
                            <input type="radio" name="country" value="leave" checked="checked"/>
                            Leave as is (Enabled)<br/>
                            <input type="radio" name="country" value="disable"/> Disable (will
                                                                                 delete current values from database!)
                            <br/>
                            <?php
                        } else {
                            ?>
                            <input type="radio" name="country" value="leave" checked="checked"/>
                            Leave as is (Disabled)<br/>
                            <input type="radio" name="country" value="enable"/> Enable<br/>
                            <?php
                        }
                        ?>
                    </td>
                </tr>

                <tr>
                    <td>
                        Affiliates
                    </td>
                    <td style="text-align: left;">
                        <input type="radio" name="affiliates" value="leave" checked="checked"/>
                        Leave as is
                        <?php
                        if ($info['affiliates'] == 0) {
                            echo ' (Disabled)<br />';
                            echo '<input type="radio" name="affiliates" value="enable" /> Yes, ';
                            echo 'images directory at <input type="text" name="affiliatesdir" ' .
                                '/><br /><small>' .
                                'Please don\'t forget the trailing slash; this folder (absolute ' .
                                'path, i.e., /home/user/public_html/images/) must have ' .
                                'proper permissions set (i.e., must be CHMODed to ' .
                                '755).</small><br />';
                        } else {
                            echo ' (Enabled)<br />';
                            echo '<input type="radio" name="affiliates" value="rename" /> Move ';
                            echo 'images directory to <input type="text" name="affiliatesdir" ' .
                                'value="' . $info['affiliatesdir'] . '" /><br /><small>' .
                                'Please don\'t forget the trailing slash; this folder (absolute ' .
                                'path, i.e., /home/user/public_html/images/) must have ' .
                                'proper permissions set (i.e., must be CHMODed to ' .
                                '755).</small><br />';
                            echo '<input type="radio" name="affiliates" value="disable" /> ';
                            echo 'Disable';
                        }
                        ?>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Additional Fields
                    </td>
                    <td style="text-align: left;">
                        <small>Click the "+" sign if you need more additional field fields
                            below. You may edit the name of the existing fields by modifying the
                            field directly, or deleting existing fields by removing the name
                            entirely from the field.<br/>
                            Additional fields must be ALL LOWERCASE, with NO SPACES and NO
                            PUNCTUATION and NO SPECIAL CHARACTERS; if you wish for the field to
                            have a space in its "name", use an underscore. For example:
                            <i>favorite_book, do_you_like_apples</i><br/>
                            Deleting an existing field will cause its contents to be discarded
                            and this cannot be undone.</small><br/>
                        <?php
                        $fields = explode(',', $info['additional']);
                        $printed = 0;
                        foreach ($fields as $f) {
                            if ($f != '') {
                                $printed++;
                                echo '<div style="padding: 2px;"> ' . $printed .
                                    ' <input type="text" name="additional[]" value="' . $f .
                                    '" /> </div>';
                            }
                        }
                        ?>
                        <div id="multifields" style="padding: 2px; display: block;">
                            <input type="text" name="additional[]"/>
                            <input type="button" value="+" onclick="moreFields()"/>
                            <input type="button" value="x"
                                   onclick="this.parentNode.parentNode.removeChild(this.parentNode);"/>
                        </div>
                        <span id="multifieldshere"></span>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" class="right">
                        <input type="submit" value="Update database settings"/>
                        <input type="reset" value="Reset form values"/>
                        <input type="button" value="Cancel"
                               onclick="javascript:window.location='owned.php';"/>
                    </td>
                </tr>

            </table>
        </form>
        <?php
    } elseif (isset($_REQUEST['type']) && $_REQUEST['type'] == 'info') {
        ?>
        <form action="owned.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit"/>
            <input type="hidden" name="done" value="yes"/>
            <input type="hidden" name="type" value="info"/>
            <input type="hidden" name="id" value="<?= $_REQUEST['id'] ?>"/>

            <table>
                <tr>
                    <th colspan="2">Listing information</th>
                </tr>

                <tr>
                    <td>
                        Listing Category
                    </td>
                    <td>
                        <select name="catid[]" multiple="multiple" size="5">
                            <?php
                            $cats = enth_get_categories();
                            $options = [];
                            foreach ($cats as $cat) {
                                $optiontext = $cat['catname'];
                                if (count($ancestors =
                                        array_reverse(get_ancestors($cat['catid']))) > 1) {
                                    // get ancestors
                                    $text = '';
                                    foreach ($ancestors as $a) {
                                        $text .= get_category_name($a) . ' > ';
                                    }
                                    $optiontext = rtrim($text, ' > ');
                                    $optiontext = str_replace('>', '&raquo;', $optiontext);
                                }
                                $options[] = ['text' => $optiontext, 'id' => $cat['catid']];
                            }
                            usort($options, 'category_array_compare');
                            $selected = explode('|', $info['catid']);
                            foreach ($options as $o) {
                                echo '<option value="' . $o['id'];
                                if (in_array($o['id'], $selected)) {
                                    echo '" selected="selected';
                                }
                                echo '">' . $o['text'] . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Subject
                    </td>
                    <td>
                        <input type="text" name="subject" value="<?= $info['subject'] ?>"/>
                    </td>
                </tr>

                <tr>
                    <td>
                        Email
                    </td>
                    <td>
                        <input type="text" name="email" value="<?= $info['email'] ?>"/>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        URL
                    </td>
                    <td>
                        <input type="text" name="url" value="<?= $info['url'] ?>"/>
                    </td>
                </tr>

                <tr>
                    <td>
                        Title
                    </td>
                    <td>
                        <input type="text" name="title" value="<?= $info['title'] ?>"/>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Listing type
                    </td>
                    <td>
                        <input type="text" name="listingtype" value="<?= $info['listingtype']
                        ?>"/>
                    </td>
                </tr>

                <tr>
                    <td>
                        Description
                    </td>
                    <td>
      <textarea name="desc" rows="3" cols="30"><?= $info['desc']
          ?></textarea>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td rowspan="3">
                        Image
                        <br />
                        <strong>Note:</strong> Make sure you have created the directories<br />before uploading an image.<br />Image upload WILL fail if the directories<br />have not been created yet.
                    </td>
                    <td>
                        <?php
                        $dir = get_setting('owned_images_dir');
                        if ($info['imagefile'] == '' || !is_file($dir . $info['imagefile'])) {
                            echo 'No image specified.';
                        } else {
                            $root_web = get_setting('root_path_web');
                            $root_abs = get_setting('root_path_absolute');
                            @$image = getimagesize($dir . $info['imagefile']);
                            $dir = str_replace($root_abs, $root_web, $dir);
                            $dir = str_replace('\\', '/', $dir);
                            echo '<img src="' . $dir . $info['imagefile'] . '" ' . $image[3] .
                                ' border="0" alt="" />';
                        }
                        ?>
                    </td>
                </tr>
                <tr class="rowshade">
                    <td>
                        <input type="radio" name="image_change" value="no" checked="checked"
                        /> Leave as it is<br/>
                        <input type="radio" name="image_change" value="delete"/> Delete
                        image<br/>
                        <input type="radio" name="image_change" value="yes"/> Change with:
                    </td>
                </tr>
                <tr class="rowshade">
                    <td>
                        <input type="file" name="image"/>
                    </td>
                </tr>

                <tr>
                    <td>
                        Status
                    </td>
                    <td>
                        <select name="status">
                            <?php
                            if ($info['status'] == 0) {
                                echo '<option value="pending">Leave as is (Pending)</option>';
                                echo '<option value="pending">--</option>';
                            } elseif ($info['status'] == 1) {
                                echo '<option value="upcoming">Leave as is (Upcoming)</option>';
                                echo '<option value="upcoming">--</option>';
                            } elseif ($info['status'] == 2) {
                                echo '<option value="current">Leave as is (Current)</option>';
                                echo '<option value="current">--</option>';
                            }
                            ?>
                            <option value="pending">Pending</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="current">Current</option>
                        </select>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Date opened
                    </td>
                    <td>
                        <select name="date_day">
                            <option value="<?= @date('j', strtotime($info['opened']))
                            ?>">Current (<?= @date('j', strtotime($info['opened'])) ?>)
                            </option>
                            <?php
                            for ($i = 1; $i <= 31; $i++) {
                                echo '<option>' . $i . '</option>';
                            }
                            ?>
                        </select>

                        <select name="date_month">
                            <option value="<?= @date('n', strtotime($info['opened']))
                            ?>">Current (<?= @date('F', strtotime($info['opened'])) ?>)
                            </option>
                            <option value="01">January</option>
                            <option value="02">February</option>
                            <option value="03">March</option>
                            <option value="04">April</option>
                            <option value="05">May</option>
                            <option value="06">June</option>
                            <option value="07">July</option>
                            <option value="08">August</option>
                            <option value="09">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>

                        <select name="date_year">
                            <option value="<?= @date('Y', strtotime($info['opened']))
                            ?>">Current (<?= @date('Y', strtotime($info['opened'])) ?>)
                            </option>
                            <?php
                            for ($year = date('Y'); $year >= 2000; $year--) {
                                echo '<option>' . $year . '</option>';
                            }
                            ?>
                        </select>

                    </td>
                </tr>

                <tr>
                    <td colspan="2" class="right">
                        <input type="submit" value="Update information"/>
                        <input type="reset" value="Reset form values"/>
                        <input type="button" value="Cancel"
                               onclick="javascript:window.location='owned.php';"/>
                    </td>
                </tr>

            </table>
        </form>
        <?php
    } elseif (isset($_REQUEST['type']) && $_REQUEST['type'] == 'settings') {
        ?>
        <form action="owned.php" method="post">
            <input type="hidden" name="action" value="edit"/>
            <input type="hidden" name="done" value="yes"/>
            <input type="hidden" name="type" value="settings"/>
            <input type="hidden" name="id" value="<?= $_REQUEST['id'] ?>"/>

            <table>
                <tr>
                    <th colspan="2">Management/look settings</th>
                </tr>

                <tr>
                    <td>
                        Hold member updates
                    </td>
                    <td style="text-align: left;">
                        <?php
                        if ($info['holdupdate'] == 1) {
                            ?>
                            <input type="radio" name="holdupdate" value="leave"
                                   checked="checked"/> Leave as is (Enabled)<br/>
                            <input type="radio" name="holdupdate" value="disable"/> Disable<br/>
                            <?php
                        } else {
                            ?>
                            <input type="radio" name="holdupdate" value="leave" checked="checked"
                            /> Leave as is (Disabled)<br/>
                            <input type="radio" name="holdupdate" value="enable"/> Enable<br/>
                            <?php
                        }
                        ?>
                        <small>This setting will determine whether a member who updates his/her
                            information will be placed back on pending or not.</small>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Pending notify
                    </td>
                    <td style="text-align: left;">
                        <?php
                        if ($info['notifynew'] == 1) {
                            ?>
                            <input type="radio" name="notifynew" value="leave" checked="checked"
                            /> Leave as is (Enabled)<br/>
                            <input type="radio" name="notifynew" value="disable"/> Disable<br/>
                            <?php
                        } else {
                            ?>
                            <input type="radio" name="notifynew" value="leave" checked="checked"
                            /> Leave as is (Disabled)<br/>
                            <input type="radio" name="notifynew" value="enable"/> Enable<br/>
                            <?php
                        }
                        ?>
                        <small>This setting will determine if you will be notified via
                            email when a member has been added/placed on the pending queue.</small>
                    </td>
                </tr>

                <tr>
                    <td>
                        Dropdown sorting
                    </td>
                    <td style="text-align: left;">
                        <?php
                        if ($info['dropdown'] == 1) {
                            ?>
                            <input type="radio" name="dropdown" value="leave" checked="checked"/>
                            Leave as is (Enabled)<br/>
                            <input type="radio" name="dropdown" value="disable"/> Disable<br/>
                            <?php
                        } else {
                            ?>
                            <input type="radio" name="dropdown" value="leave" checked="checked"/>
                            Leave as is (Disabled)<br/>
                            <input type="radio" name="dropdown" value="enable"/> Enable<br/>
                            <?php
                        }
                        ?>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Sort members by
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="sort" value="<?= $info['sort'] ?>"/><br/>
                        <small>This is the database field that will determine how your
                            members are sorted. This can be either any of your additional fields
                            or by 'country'. Sorting by multiple fields are allowed -- separate
                            fields using a comma (,).</small>
                    </td>
                </tr>

                <tr>
                    <td>
                        Members per page
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="perpage" value="<?= $info['perpage']
                        ?>"/><br/>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Link target
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="linktarget" value="<?= $info['linktarget']
                        ?>"/><br/>
                    </td>
                </tr>

                <tr>
                    <td>
                        Join page
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="joinpage" value="<?= $info['joinpage']
                        ?>"/><br/>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        List page
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="listpage" value="<?= $info['listpage']
                        ?>"/><br/>
                    </td>
                </tr>

                <tr>
                    <td>
                        Update page
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="updatepage" value="<?= $info['updatepage']
                        ?>"/><br/>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Lostpass page
                    </td>
                    <td style="text-align: left;">
                        <input type="text" name="lostpasspage" value="<?= $info['lostpasspage']
                        ?>"/><br/>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" class="right">
                        <input type="submit" value="Update settings"/>
                        <input type="reset" value="Reset form values"/>
                        <input type="button" value="Cancel"
                               onclick="javascript:window.location='owned.php';"/>
                    </td>
                </tr>

            </table>
        </form>
        <?php
    } elseif (isset($_REQUEST['type']) && $_REQUEST['type'] == 'emails') {
        ?>
        <form action="owned.php" method="post">
            <input type="hidden" name="action" value="edit"/>
            <input type="hidden" name="done" value="yes"/>
            <input type="hidden" name="type" value="emails"/>
            <input type="hidden" name="id" value="<?= $_REQUEST['id'] ?>"/>

            <table>
                <tr>
                    <th colspan="2">Email Templates</th>
                </tr>

                <tr>
                    <td>
                        Signup email
                    </td>
                    <td>
      <textarea name="emailsignup" rows="10" cols="60"><?= $info['emailsignup']
          ?></textarea>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Approval email
                    </td>
                    <td>
                        <textarea name="emailapproved" rows="10"
                                  cols="60"><?= $info['emailapproved'] ?></textarea>
                    </td>
                </tr>

                <tr>
                    <td>
                        Update info email
                    </td>
                    <td>
      <textarea name="emailupdate" rows="10" cols="60"><?= $info['emailupdate']
          ?></textarea>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Lost password email
                    </td>
                    <td>
                        <textarea name="emaillostpass" rows="10"
                                  cols="60"><?= $info['emaillostpass'] ?></textarea>
                    </td>
                </tr>

                <tr>
                    <td colspan="2" class="right">
                        <input type="submit" value="Update emails"/>
                        <input type="reset" value="Reset form values"/>
                        <input type="button" value="Cancel"
                               onclick="javascript:window.location='owned.php';"/>
                    </td>
                </tr>

            </table>
        </form>
        <?php
    } elseif (isset($_REQUEST['type']) &&
        $_REQUEST['type'] == 'templates') {
        ?>
        <form action="owned.php" method="post">
            <input type="hidden" name="action" value="edit"/>
            <input type="hidden" name="done" value="yes"/>
            <input type="hidden" name="type" value="templates"/>
            <input type="hidden" name="id" value="<?= $_REQUEST['id'] ?>"/>

            <table>
                <tr>
                    <th colspan="2">Website Templates</th>
                </tr>

                <tr>
                    <td>
                        Members List
                    </td>
                    <td>
                        <textarea name="listtemplate" rows="10" cols="60"><?= $info['listtemplate'] ?></textarea>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Affiliates
                    </td>
                    <td>
                        <textarea name="affiliatestemplate" rows="10"
                                  cols="60"><?= $info['affiliatestemplate'] ?></textarea>
                    </td>
                </tr>

                <tr>
                    <td>
                        Statistics
                    </td>
                    <td>
                        Please note, if there's a link to scripts.indisguise.org present in the template, it will be
                        replaced with 2 links - to scripts.robotess.net and to scripts.indisguise.org; <br/>if there's a
                        link to my old website workshop.katenkka.ru - it will be replaced with a link to
                        scripts.robotess.net
                        <br/>
                        <br/>
                        <textarea name="statstemplate" rows="10"
                                  cols="60"><?= $info['statstemplate'] ?></textarea>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td colspan="2" class="right">
                        <input type="submit" value="Update templates"/>
                        <input type="reset" value="Reset form values"/>
                        <input type="button" value="Cancel"
                               onclick="javascript:window.location='owned.php';"/>
                    </td>
                </tr>

            </table>
        </form>
        <?php
    }
}


/*___________________________________________________________________________*/
function edit_owned($id, $fields)
{
    require 'config.php';
    $changes = [];

    // get listing info
    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :id";
    $result = $db_link->prepare($query);
    $result->bindParam(':id', $id, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();
    $table = $info['dbtable'];
    $dbserver = $info['dbserver'];
    $dbdatabase = $info['dbdatabase'];
    $dbuser = $info['dbuser'];
    $dbpassword = $info['dbpassword'];

    foreach ($fields as $field => $value) {

        switch ($field) {

            case 'dbdatabase' :
                // should move table to appropriate database
                // NOT YET IMPLEMENTED
                if ($value == $dbdatabase) {
                    continue 2;
                } // don't change
                $query = "UPDATE `$db_owned` SET `$field` = '$value' WHERE " .
                    "`listingid` = :id";
                if ($value == 'null') {
                    $query = "UPDATE `$db_owned` SET `$field` = null WHERE " .
                        "`listingid` = :id";
                }
                $result = $db_link->prepare($query);
                $result->bindParam(':id', $id, PDO::PARAM_INT);
                $result->execute();
                if (!$result) {
                    log_error(__FILE__ . ':' . __LINE__,
                        'Error executing query: <i>' . $result->errorInfo()[2] .
                        '</i>; Query is: <code>' . $query . '</code>');
                    die(STANDARD_ERROR);
                }
                $changes[] = 'Database changed.';
                break;

            case 'dbtable' :
                if ($value != $table) {
                    // change data! we actually change the database table
                    try {
                        $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8',
                            $dbuser, $dbpassword);
                        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    } catch (PDOException $e) {
                        die(DATABASE_CONNECT_ERROR . $e->getMessage());
                    }

                    // rename physically
                    $query = "ALTER TABLE `$table` RENAME `$value`";
                    $result = $db_link_list->query($query);
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $db_link_list = null;

                    // update db_owned table
                    $query = "UPDATE `$db_owned` SET `dbtable` = :value WHERE " .
                        "`listingid` = :id";
                    $result = $db_link->prepare($query);
                    $result->bindParam(':value', $value);
                    $result->bindParam(':id', $id, PDO::PARAM_INT);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $changes[] = 'Database table changed.';
                }
                break;

            case 'country' :
                // get info
                if ($value == 'leave') {
                    continue 2;
                }
                try {
                    $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8',
                        $dbuser, $dbpassword);
                    $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    die(DATABASE_CONNECT_ERROR . $e->getMessage());
                }

                if ($value == 'disable') {
                    // alter table
                    $query = "ALTER TABLE `$table` DROP `country`";
                    $result = $db_link_list->query($query);
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $db_link_list = null;

                    // update db_owned
                    $query = "UPDATE `$db_owned` SET `country` = 0 WHERE " .
                        "`listingid` = :id";
                    $result = $db_link->prepare($query);
                    $result->bindParam(':id', $id, PDO::PARAM_INT);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $changes[] = 'Country field disabled.';

                } elseif ($value == 'enable') {
                    // alter table
                    $query = "ALTER TABLE `$table` ADD `country` VARCHAR(128) " .
                        "NOT NULL default '' AFTER `name`";
                    $result = $db_link_list->prepare($query);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }

                    // drop fulltext index
                    $query = "ALTER TABLE `$table` DROP INDEX `email`";
                    $result = $db_link_list->prepare($query);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                    }

                    // re-add fulltext index
                    $query = "ALTER TABLE `$table` ADD FULLTEXT ( `email`, " .
                        "`name`, `country`, `url` )";
                    $result = $db_link_list->prepare($query);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $db_link_list = null;

                    // update db_owned
                    $query = "UPDATE `$db_owned` SET `country` = 1 WHERE " .
                        "`listingid` = :id";
                    $result = $db_link->prepare($query);
                    $result->bindParam(':id', $id, PDO::PARAM_INT);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $changes[] = 'Country field enabled.';

                }
                break;

            case 'affiliates' :
                if ($value == 'leave') {
                    continue 2;
                }

                // connect to remote table
                try {
                    $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8',
                        $dbuser, $dbpassword);
                    $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $e) {
                    die(DATABASE_CONNECT_ERROR . $e->getMessage());
                }

                if ($value == 'disable') {
                    // drop aff table
                    $afftable = $table . '_affiliates';
                    $query = "DROP TABLE IF EXISTS `$afftable`";
                    $result = $db_link_list->prepare($query);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $db_link_list = null;

                    try {
                        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8',
                            $db_user, $db_password);
                        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    } catch (PDOException $e) {
                        die(DATABASE_CONNECT_ERROR . $e->getMessage());
                    }

                    // update db_owned
                    $query = "UPDATE `$db_owned` SET `affiliates` = 0, " .
                        "`affiliatesdir` = NULL WHERE `listingid` = :id";
                    $result = $db_link->prepare($query);
                    $result->bindParam(':id', $id, PDO::PARAM_INT);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $changes[] = 'Affiliates feature disabled.';

                } elseif ($value == 'enable') {
                    // add table
                    $afftable = $table . '_affiliates';
                    $mysqlVersion = $db_link_list->getAttribute(PDO::ATTR_SERVER_VERSION);
                    if ((float)$mysqlVersion >= 8.0) {
                        $query = "CREATE TABLE IF NOT EXISTS `$afftable` (" .
                            "`affiliateid` int(5) NOT NULL auto_increment, " .
                            "`url` varchar(255) NOT NULL default '', " .
                            "`title` varchar(255) NOT NULL default '', " .
                            "`imagefile` varchar(255) default NULL, " .
                            "`email` varchar(255) NOT NULL default '', " .
                            "`added` DATE NOT NULL, " .
                            "PRIMARY KEY( affiliateid ) " .
                            ") ENGINE=MyISAM AUTO_INCREMENT=1";
                    } else {
                        $query = "CREATE TABLE IF NOT EXISTS `$afftable` (" .
                            "`affiliateid` int(5) NOT NULL auto_increment, " .
                            "`url` varchar(255) NOT NULL default '', " .
                            "`title` varchar(255) NOT NULL default '', " .
                            "`imagefile` varchar(255) default NULL, " .
                            "`email` varchar(255) NOT NULL default '', " .
                            "`added` DATE NOT NULL default '0000-00-00', " .
                            "PRIMARY KEY( affiliateid ) " .
                            ") ENGINE=MyISAM AUTO_INCREMENT=1";
                    }
                    $result = $db_link_list->prepare($query);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $db_link_list = null;

                    try {
                        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8',
                            $db_user, $db_password);
                        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    } catch (PDOException $e) {
                        die(DATABASE_CONNECT_ERROR . $e->getMessage());
                    }

                    // set db_owned
                    $query = "UPDATE `$db_owned` SET `affiliates` = 1, " .
                        "`affiliatesdir` = '" . $fields['affiliatesdir'] .
                        "' WHERE `listingid` = :id";
                    $result = $db_link->prepare($query);
                    $result->bindParam(':id', $id, PDO::PARAM_INT);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $changes[] = 'Affiliates feature enabled.';

                } elseif ($value == 'rename') {
                    $db_link_list = null; // no need for remote database

                    // connect to actual database
                    try {
                        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8',
                            $db_user, $db_password);
                        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    } catch (PDOException $e) {
                        die(DATABASE_CONNECT_ERROR . $e->getMessage());
                    }

                    $query = "UPDATE `$db_owned` SET `affiliatesdir` = '" .
                        $fields['affiliatesdir'] . "' WHERE `listingid` = :id";
                    $result = $db_link->prepare($query);
                    $result->bindParam(':id', $id, PDO::PARAM_INT);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $changes[] = 'Affiliates image directory updated.';

                }
                break;

            case 'additional' :
                $additionaltext = rtrim(implode(',', $value), ',');
                if ($additionaltext != $info['additional']) {
                    // there are changes-- process them
                    $table = $info['dbtable'];
                    $dbserver = $info['dbserver'];
                    $dbdatabase = $info['dbdatabase'];
                    $dbuser = $info['dbuser'];
                    $dbpassword = $info['dbpassword'];

                    try {
                        $db_link_list = new PDO('mysql:host=' . $dbserver . ';dbname=' . $dbdatabase . ';charset=utf8',
                            $dbuser, $dbpassword);
                        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    } catch (PDOException $e) {
                        die(DATABASE_CONNECT_ERROR . $e->getMessage());
                    }

                    // get current additional fields
                    $query = "DESCRIBE `$table`";
                    try {
                        $result = $db_link->prepare($query);
                        $result->execute();
                    } catch (PDOException $e) {
                        die($e->getMessage());
                    }
                    $current = [];
                    $start = false;
                    $result->setFetchMode(PDO::FETCH_ASSOC);
                    while ($row = $result->fetch()) {
                        if ($row['Field'] == 'url') {
                            $start = true;
                            continue;
                        }

                        if ($row['Field'] == 'pending') {
                            $start = false;
                            break;
                        }
                        if ($start) {
                            $current[] = $row['Field'];
                        }
                    }

                    $prev = 'url';

                    foreach ($value as $index => $new) {
                        if (isset($current[$index]) &&
                            $new == $current[$index]) { // same, continue
                            $prev = $new;
                            continue;
                        }

                        if ($new == '' && $index == (count($value) - 1)) {
                            break;
                        } elseif ($new == '') {
                            // delete the field
                            $query = "ALTER TABLE `$table` DROP `" .
                                $current[$index] . '`';
                            $result = $db_link_list->prepare($query);
                            $result->execute();
                            if (!$result) {
                                log_error(__FILE__ . ':' . __LINE__,
                                    'Error executing query: <i>' . $result->errorInfo()[2] .
                                    '</i>; Query is: <code>' . $query . '</code>');
                                die(STANDARD_ERROR);
                            }
                        } elseif (!isset($current[$index])) {
                            // add after the previous column
                            $query = "ALTER TABLE `$table` ADD COLUMN `$new` " .
                                "VARCHAR(255) DEFAULT NULL AFTER `$prev`";
                            $result = $db_link_list->prepare($query);
                            $result->execute();
                            if (!$result) {
                                log_error(__FILE__ . ':' . __LINE__,
                                    'Error executing query: <i>' . $result->errorInfo()[2] .
                                    '</i>; Query is: <code>' . $query . '</code>');
                                die(STANDARD_ERROR);
                            }
                            $prev = $new;
                        } else {
                            // rename column
                            $query = "ALTER TABLE `$table` CHANGE `" .
                                $current[$index] . "` `$new` " .
                                "VARCHAR(255) DEFAULT NULL";
                            $result = $db_link_list->prepare($query);
                            $result->execute();
                            if (!$result) {
                                log_error(__FILE__ . ':' . __LINE__,
                                    'Error executing query: <i>' . $result->errorInfo()[2] .
                                    '</i>; Query is: <code>' . $query . '</code>');
                                die(STANDARD_ERROR);
                            }
                            $prev = $new;
                        } // end if field has been edited

                    } // end foreach value as index -> new

                    $db_link_list = null;

                    // update db_owned
                    $additionaltext = str_replace(',,', ',', $additionaltext);
                    $query = "UPDATE `$db_owned` SET `additional` = " .
                        "'$additionaltext' WHERE `listingid` = :id";
                    $result = $db_link->prepare($query);
                    $result->bindParam(':id', $id, PDO::PARAM_INT);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }

                    $changes[] = 'Additional fields updated.';

                } // end if there is an edit

                break;

            case 'status' :
                if (($value == 'current' && $info['status'] == 2) ||
                    ($value == 'upcoming' && $info['status'] == 1) ||
                    ($value == 'pending' && $info['status'] == 0)) {
                    continue 2;
                }
                $query = "UPDATE `$db_owned` SET `status` = ";
                $status = 0;
                if ($value == 'upcoming') {
                    $status = 1;
                } elseif ($value == 'current') {
                    $status = 2;
                }
                $query .= "$status WHERE `listingid` = :id";
                $result = $db_link->prepare($query);
                $result->bindParam(':id', $id, PDO::PARAM_INT);
                $result->execute();
                if (!$result) {
                    log_error(__FILE__ . ':' . __LINE__,
                        'Error executing query: <i>' . $result->errorInfo()[2] .
                        '</i>; Query is: <code>' . $query . '</code>');
                    die(STANDARD_ERROR);
                }
                $changes[] = 'Status updated.';
                break;

            case 'date_year' :
                break;
            case 'date_month' :
                break;
            case 'date_day' :
                $new = $fields['date_year'] . '-' .
                    str_pad($fields['date_month'], 2, '0', STR_PAD_LEFT) . '-' .
                    str_pad($fields['date_day'], 2, '0', STR_PAD_LEFT);
                if ($new == $info['opened']) {
                    continue 2;
                }
                $query = "UPDATE `$db_owned` SET `opened` = '$new' " .
                    "WHERE `listingid` = :id";
                $result = $db_link->prepare($query);
                $result->bindParam(':id', $id, PDO::PARAM_INT);
                $result->execute();
                if (!$result) {
                    log_error(__FILE__ . ':' . __LINE__,
                        'Error executing query: <i>' . $result->errorInfo()[2] .
                        '</i>; Query is: <code>' . $query . '</code>');
                    die(STANDARD_ERROR);
                }
                $changes[] = 'Date opened updated.';
                break;

            case 'image_change' :
                // check image_change
                if ($value == 'delete') {
                    // get absolute path
                    $query = "SELECT `value` FROM `$db_settings` WHERE " .
                        '`setting` = "owned_images_dir"';
                    $result = $db_link->query($query);
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $result->setFetchMode(PDO::FETCH_ASSOC);
                    $row = $result->fetch();
                    $dir = $row['value'];

                    $success = @unlink($dir . $info['imagefile']);
                    if ($success) {
                        $changes[] = 'Image deleted.';
                        $query = "UPDATE `$db_owned` SET `imagefile` = NULL WHERE " .
                            "`listingid` = :id";
                        $result = $db_link->prepare($query);
                        $result->bindParam(':id', $id, PDO::PARAM_INT);
                        $result->execute();
                        if (!$result) {
                            log_error(__FILE__ . ':' . __LINE__,
                                'Error executing query: <i>' . $result->errorInfo()[2] .
                                '</i>; Query is: <code>' . $query . '</code>');
                            die(STANDARD_ERROR);
                        }
                    }

                } elseif ($value == 'yes') {
                    if (!isset($fields['imagefile']) ||
                        $fields['imagefile'] == '') {
                        continue 2;
                    } // there is no uploaded image ata eh
                    // get absolute path
                    $query = "SELECT `value` FROM `$db_settings` WHERE " .
                        '`setting` = "owned_images_dir"';
                    $result = $db_link->prepare($query);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $result->setFetchMode(PDO::FETCH_ASSOC);
                    $row = $result->fetch();
                    $dir = $row['value'];

                    // delete the old image file
                    $file = $info['imagefile'];
                    if ($file && is_file($dir . $file) &&
                        $file != $fields['imagefile']) {
                        @unlink($dir . $file);
                    }

                    // if the new one is a valid file
                    if ($fields['imagefile'] &&
                        is_file($dir . $fields['imagefile'])) {
                        $file = $fields['imagefile'];

                        // chmod the new image file to 644
                        @chmod($dir . $file, 0644);

                        // update db_owned
                        $file = $fields['imagefile'];
                        $query = "UPDATE `$db_owned` SET `imagefile` = :file " .
                            "WHERE `listingid` = :id";
                        $result = $db_link->prepare($query);
                        $result->bindParam(':file', $file, PDO::PARAM_STR);
                        $result->bindParam(':id', $id, PDO::PARAM_INT);
                        $result->execute();
                        if (!$result) {
                            log_error(__FILE__ . ':' . __LINE__,
                                'Error executing query: <i>' . $result->errorInfo()[2] .
                                '</i>; Query is: <code>' . $query . '</code>');
                            die(STANDARD_ERROR);
                        }
                        $changes[] = 'Image file uploaded.';
                    } else {
                        $changes[] = 'Error uploading image file.';
                    }
                }
                break;

            case 'dbpassword' :
                if ($value == '') {
                    continue 2;
                }
                // verify
                if ($value == $fields['dbpasswordv'] && $value != '') {
                    // update db_owned
                    $query = "UPDATE `$db_owned` SET `dbpassword` = :value " .
                        "WHERE `listingid` = :id";
                    $result = $db_link->prepare($query);
                    $result->bindParam(':value', $value, PDO::PARAM_STR);
                    $result->bindParam(':id', $id, PDO::PARAM_INT);
                    $result->execute();
                    if (!$result) {
                        log_error(__FILE__ . ':' . __LINE__,
                            'Error executing query: <i>' . $result->errorInfo()[2] .
                            '</i>; Query is: <code>' . $query . '</code>');
                        die(STANDARD_ERROR);
                    }
                    $changes[] = 'Database password updated.';
                } else {
                    $changes[] = '<span class="error">Error changing database ' .
                        'password: password not the same as the validation.</span>';
                }
                break;

            case 'dropdown' :
            case 'notifynew' :
            case 'holdupdate' :
                if ($value == 'leave') {
                    continue 2;
                }
                $query = "UPDATE `$db_owned` SET `$field` = ";
                $set = 0;
                if ($value == 'disable') {
                    $set = 0;
                } elseif ($value == 'enable') {
                    $set = 1;
                } else {
                    continue 2;
                }
                $query .= "$set WHERE `listingid` = :id";
                $result = $db_link->prepare($query);
                $result->bindParam(':id', $id, PDO::PARAM_INT);
                $result->execute();
                if (!$result) {
                    log_error(__FILE__ . ':' . __LINE__,
                        'Error executing query: <i>' . $result->errorInfo()[2] .
                        '</i>; Query is: <code>' . $query . '</code>');
                    die(STANDARD_ERROR);
                }
                if ($field == 'dropdown') {
                    $changes[] = 'Dropdown usage ' . $value . 'd.';
                } elseif ($field == 'notifynew') {
                    $changes[] = 'Notify owner of pending members ' . $value .
                        'd.';
                } elseif ($field == 'holdupdate') {
                    $changes[] = 'Hold member updates ' . $value . 'd.';
                }
                break;

            case 'catid' :
                if ($value == '' || !is_array($value)) {
                    continue 2;
                }
                $cats = implode('|', $value);
                $cats = str_replace('||', '|', $cats);
                $cats = '|' . trim($cats, '|') . '|';
                if ($cats == $info['catid']) {
                    continue 2;
                }
                $query = "UPDATE `$db_owned` SET `catid` = :cats " .
                    "WHERE `listingid` = :id";
                $result = $db_link->prepare($query);
                $result->bindParam(':cats', $cats);
                $result->bindParam(':id', $id, PDO::PARAM_INT);
                $result->execute();
                if (!$result) {
                    log_error(__FILE__ . ':' . __LINE__,
                        'Error executing query: <i>' . $result->errorInfo()[2] .
                        '</i>; Query is: <code>' . $query . '</code>');
                    die(STANDARD_ERROR);
                }
                $changes[] = 'Categories updated.';
                break;

            case 'dbserver' :
            case 'dbuser' :
            case 'email' :
            case 'listingtype' :
            case 'sort' :
            case 'perpage' :
            case 'joinpage' :
            case 'listpage' :
            case 'updatepage' :
            case 'lostpasspage' :
                if ($value == '') {
                    continue 2;
                } // the above fields are required
            case 'title' :
            case 'subject' :
            case 'url' :
            case 'desc' :
            case 'linktarget' :
            case 'emailsignup' :
            case 'emailapproved' :
            case 'emailupdate' :
            case 'emaillostpass' :
            case 'listtemplate' :
            case 'affiliatestemplate' :
            case 'statstemplate' :
                if (stripslashes($value) == $info[$field]) {
                    continue 2;
                }
                $query = "UPDATE `$db_owned` SET `$field` = '$value' " .
                    "WHERE `listingid` = :id";
                if ($value == 'null') {
                    $query = "UPDATE `$db_owned` SET `$field` = null WHERE " .
                        "`listingid` = :id";
                }
                $result = $db_link->prepare($query);
                $result->bindParam(':id', $id, PDO::PARAM_INT);
                $result->execute();
                if (!$result) {
                    log_error(__FILE__ . ':' . __LINE__,
                        'Error executing query: <i>' . $result->errorInfo()[2] .
                        '</i>; Query is: <code>' . $query . '</code>');
                    die(STANDARD_ERROR);
                }
                if ($field == 'dbserver') {
                    $changes[] = 'Database server updated.';
                } elseif ($field == 'dbuser') {
                    $changes[] = 'Database user updated.';
                } elseif ($field == 'title') {
                    $changes[] = 'Listing title updated.';
                } elseif ($field == 'subject') {
                    $changes[] = 'Listing subject updated.';
                } elseif ($field == 'email') {
                    $changes[] = 'Email address updated.';
                } elseif ($field == 'url') {
                    $changes[] = 'Listing URL updated.';
                } elseif ($field == 'desc') {
                    $changes[] = 'Description updated.';
                } elseif ($field == 'listingtype') {
                    $changes[] = 'Listing type updated.';
                } elseif ($field == 'sort') {
                    $changes[] = 'Member sorting field updated.';
                } elseif ($field == 'perpage') {
                    $changes[] = 'Items per page updated.';
                } elseif ($field == 'linktarget') {
                    $changes[] = 'Link targets updated.';
                } elseif ($field == 'joinpage') {
                    $changes[] = 'Join page file updated.';
                } elseif ($field == 'updatepage') {
                    $changes[] = 'Update page file updated.';
                } elseif ($field == 'lostpasspage') {
                    $changes[] = 'Lost password page file updated.';
                } elseif ($field == 'emailsignup') {
                    $changes[] = 'Signup email template updated.';
                } elseif ($field == 'emailapproved') {
                    $changes[] = 'Approved member email template updated.';
                } elseif ($field == 'emailupdate') {
                    $changes[] = 'Member update information email ' .
                        'template updated.';
                } elseif ($field == 'emaillostpass') {
                    $changes[] = 'Lost password email template updated.';
                } elseif ($field == 'listtemplate') {
                    $changes[] = 'Members list template updated';
                } elseif ($field == 'affiliatestemplate') {
                    $changes[] = 'Affiliates template updated.';
                } elseif ($field == 'statstemplate') {
                    $changes[] = 'Listing statistics template updated.';
                }
                break;

            case 'id' :
            case 'dbpasswordv' :
            default :
                break;

        } // end switch
    } // end foreach

    return $changes;
}


/*___________________________________________________________________________*/
function delete_owned($id)
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get table info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :id";
    $result = $db_link->prepare($query);
    $result->bindParam(':id', $id, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $row = $result->fetch();
    $server = $row['dbserver'];
    $user = $row['dbuser'];
    $password = $row['dbpassword'];
    $database = $row['dbdatabase'];
    $table = $row['dbtable'];
    $image = $row['imagefile'];

    //get $dir setting
    $query = "SELECT `value` FROM `$db_settings` WHERE `setting` = " .
        "'owned_images_dir'";
    $result = $db_link->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $row = $result->fetch();
    $dir = $row['value'];

    // delete from $db_owned
    $query = "DELETE FROM `$db_owned` WHERE `listingid` = :id";
    $result = $db_link->prepare($query);
    $result->bindParam(':id', $id, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    // connect to proper database
    try {
        $db_link_list = new PDO('mysql:host=' . $server . ';dbname=' . $database . ';charset=utf8', $user, $password);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // drop affiliates table
    $afftable = $table . '_affiliates';
    $query = "DROP TABLE IF EXISTS `$afftable`";
    $result = $db_link_list->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    // drop actual table
    $query = "DROP TABLE `$table`";
    $result = $db_link_list->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    // unlink image if present
    if ($dir . $image) {
        unlink($dir . $file);
    }

    return true;
}


/*___________________________________________________________________________*/
function get_owned_cats($status = 'all')
{
    require 'config.php';
    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    $query = "SELECT DISTINCT( `catid` ) as `id` FROM `$db_owned`";
    if ($status && $status != 'all') {
        if ($status == 'pending') {
            $query .= " WHERE `status` = 0";
        } elseif ($status == 'upcoming') {
            $query .= " WHERE `status` = 1";
        } elseif ($status == 'current') {
            $query .= " WHERE `status` = 2";
        }
    }

    $result = $db_link->prepare($query);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    if ($result->rowCount() == 0) {
        return [];
    } // return empty array, no cats

    $query = "SELECT `catid` FROM `$db_category` WHERE ( ";
    $allcats = [];
    $result->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $result->fetch()) {
        $cats = explode('|', $row['id']);
        foreach ($cats as $cat) {
            if ($cat != '' && !in_array($cat, $allcats)) {
                $query .= "`catid` = '$cat' OR ";
                $allcats[] = $cat;
            }
        }
    }
    $query = rtrim($query, 'OR ') . ' ) ';
    $query .= ' ORDER BY `catname` ASC';
    $result = $db_link->prepare($query);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    $ids = [];
    $result->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $result->fetch()) {
        $ids[] = $row['catid'];
    }

    return $ids;
}


/*___________________________________________________________________________*/
function parse_owned_template($id)
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    // get date setting
    $query = "SELECT `value` FROM `$db_settings` WHERE " .
        "`setting` = 'date_format'";
    $result = $db_link->prepare($query);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $datesetting = $result->fetch();
    $dateformat = $datesetting['value'];

    // get info
    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :id";
    $result = $db_link->prepare($query);
    $result->bindParam(':id', $id, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();

    // find categories this is listed under (collective cats)
    $cats = '';
    $i = 0;
    $catsarray = explode('|', $info['catid']);
    foreach ($catsarray as $index => $c) {
        if ($c == '') {
            $i++;
            continue;
        } // blank
        if ($i == (count($catsarray) - 1) && count($catsarray) != 1) {
            $cats .= 'and ';
        }
        $cat = '';
        $aline = get_ancestors($c);
        foreach ($aline as $a) {
            $cat = get_category_name($a) . ' > ' . $cat;
        }
        $cat = rtrim($cat, '> ');
        $cat = str_replace('>', '&raquo;', $cat);
        $cats .= "$cat, ";
        $i++;
    }
    $cats = rtrim($cats, ', ');

    $query = "SELECT `setting`, `value` FROM `$db_settings` WHERE `setting` " .
        '= "owned_images_dir" OR `setting` = "root_path_absolute" OR ' .
        '`setting` = "root_path_web"';
    $result = $db_link->prepare($query);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $dir = '';
    $root_web = '';
    $root_abs = '';
    $result->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $result->fetch()) {
        if ($row['setting'] == 'owned_images_dir') {
            $dir = $row['value'];
        } elseif ($row['setting'] == 'root_path_absolute') {
            $root_abs = $row['value'];
        } else {
            $root_web = $row['value'];
        }
    }
    $image = ($info['imagefile'] && is_file($dir . $info['imagefile']))
        ? getimagesize($dir . $info['imagefile']) : ['', '', ''];
    // make sure $image is an array, in case getimagesize() failed
    if (!is_array($image)) {
        $image = [];
    }
    $dir = str_replace($root_abs, $root_web, $dir);

    $query = "SELECT `value` FROM `$db_settings` WHERE `setting` = " .
        "'owned_template'";
    $result = $db_link->prepare($query);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $setting = $result->fetch();

    // get listing stats now
    $stats = get_listing_stats($info['listingid']);

    $formatted = str_replace('enth3-url', $info['url'], $setting['value']);
    $formatted = str_replace('enth3-subject', $info['subject'], $formatted);
    $formatted = str_replace('enth3-title', $info['title'], $formatted);
    $formatted = str_replace('enth3-desc', $info['desc'], $formatted);
    $formatted = str_replace('enth3-image', $dir . $info['imagefile'],
        $formatted);
    if (count($image)) {
        $formatted = str_replace('enth3-width', $image[0], $formatted);
        $formatted = str_replace('enth3-height', $image[1], $formatted);
    }
    $formatted = str_replace('enth3-categories', $cats, $formatted);
    $formatted = str_replace('enth3-listingtype', $info['listingtype'],
        $formatted);
    $formatted = str_replace('enth3-desc', $info['desc'], $formatted);
    $formatted = @str_replace('enth3-opened', date($dateformat,
        strtotime($info['opened'])), $formatted);
    $formatted = @str_replace('enth3-updated', date($dateformat,
        strtotime($stats['lastupdated'])), $formatted);
    $formatted = str_replace('enth3-pending', $stats['pending'], $formatted);
    $formatted = str_replace('enth3-approved', $stats['total'], $formatted);
    $formatted = str_replace('enth3-status', $info['status'], $formatted);
    $formatted = str_replace('enth3-growth', $stats['average'], $formatted);
    $formatted = str_replace('enth3-countries', $stats['countries'],
        $formatted);
    $formatted = str_replace('enth3-newmembers', $stats['new_members'],
        $formatted);

    // deprecated, mostly here for scaling down
    $formatted = str_replace('enth3-cat', $cats, $formatted);

    return $formatted;
}


/*___________________________________________________________________________*/
function get_owned_by_category($catid, $status = 'all')
{
    require 'config.php';
    $query = "SELECT `listingid` FROM `$db_owned` WHERE `catid` LIKE :catid";
    if ($status != '' && $status != 'all') {
        $query .= " AND status = '$status'";
    }
    $query .= ' ORDER BY `subject`';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }
    $result = $db_link->prepare($query);
    $catidForLike = '%|' . $catid . '|%';
    $result->bindParam(':catid', $catidForLike, PDO::PARAM_STR);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    $ids = [];
    $result->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $result->fetch()) {
        $ids[] = $row['listingid'];
    }

    return $ids;
}


/*___________________________________________________________________________*/
function search_owned($search, $status = 'all', $start = 'none')
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    $query = "SELECT `listingid` FROM `$db_owned` WHERE ( MATCH( " .
        "`title`, `subject`, `url`, `desc` ) AGAINST( '$search' ) " .
        "OR `title` LIKE '%$search%' OR `subject` LIKE '%$search%' )";

    if ($status == 'pending') {
        $query .= " AND `status` = 0";
    } elseif ($status == 'upcoming') {
        $query .= " AND `status` = 1";
    } elseif ($status == 'current') {
        $query .= " AND `status` = 2";
    }

    $query .= " ORDER BY `subject` DESC";

    if ($start != 'none' && ctype_digit($start)) {
        $settingq = "SELECT value FROM $db_settings WHERE setting = 'per_page'";
        $result = $db_link->prepare($settingq);
        $result->execute();
        if (!$result) {
            log_error(__FILE__ . ':' . __LINE__,
                'Error executing query: <i>' . $result->errorInfo()[2] .
                '</i>; Query is: <code>' . $query . '</code>');
            die(STANDARD_ERROR);
        }
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $row = $result->fetch();
        $limit = $row['value'];
        $query .= " LIMIT $start, $limit";
    }

    $result = $db_link->prepare($query);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }

    $ids = [];
    $result->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $result->fetch()) {
        $ids[] = $row['listingid'];
    }

    return $ids;
}


/*__________________________________________________________________________*/
function get_listing_stats($id, $extended = false)
{
    require 'config.php';

    try {
        $db_link = new PDO('mysql:host=' . $db_server . ';dbname=' . $db_database . ';charset=utf8', $db_user,
            $db_password);
        $db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(DATABASE_CONNECT_ERROR . $e->getMessage());
    }

    $query = "SELECT * FROM `$db_owned` WHERE `listingid` = :id";
    $result = $db_link->prepare($query);
    $result->bindParam(':id', $id, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $info = $result->fetch();

    try {
        $db_link_list = new PDO('mysql:host=' . $info['dbserver'] . ';charset=utf8', $info['dbuser'],
            $info['dbpassword']);
        $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo '<p class="error">' . DATABASE_CONNECT_ERROR .
            " Can't connect to MySQL server on {$info['dbserver']}</p>";

        return;
    }
    $dbselected = $db_link_list->query('USE ' . $info['dbdatabase']);
    if (!$dbselected) {
        echo '<p class="error">' . DATABASE_CONNECT_ERROR .
            " Can't connect to MySQL database '{$info['dbdatabase']}'</p>";

        return;
    }

    $table = $info['dbtable'];
    $afftable = $table . '_affiliates';
    $stats = [];

    // get added date in main table - make sure it is only approved members
    $query = "SELECT `added` FROM `$table` WHERE `pending` = 0 " .
        'ORDER BY `added` DESC LIMIT 1';
    $result = $db_link_list->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $row = $result->fetch();

    $stats['lastupdated'] = null;
    $new = [];
    if ($row !== false) {
        $stats['lastupdated'] = $row['added'];

        // get most recent members - make sure it is only approved members
        $query = "SELECT * FROM `$table` WHERE `added` = '" .
            $stats['lastupdated'] . '\' AND `pending` = 0';
        $result = $db_link_list->query($query);
        if (!$result) {
            log_error(__FILE__ . ':' . __LINE__,
                'Error executing query: <i>' . $result->errorInfo()[2] .
                '</i>; Query is: <code>' . $query . '</code>');
            die(STANDARD_ERROR);
        }
        $result->setFetchMode(PDO::FETCH_ASSOC);
        while ($row = $result->fetch()) {
            $new[] = $row;
        }
    }

    // get added date in affiliates table if affiliates is present
    if ($info['affiliates'] == 1) {
        $query = "SELECT `added` FROM `$afftable` ORDER BY `added` " .
            'DESC LIMIT 1';
        $result = $db_link_list->prepare($query);
        $result->execute();
        if (!$result) {
            log_error(__FILE__ . ':' . __LINE__,
                'Error executing query: <i>' . $result->errorInfo()[2] .
                '</i>; Query is: <code>' . $query . '</code>');
            die(STANDARD_ERROR);
        }
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $row = $result->fetch();

        if ($row !== false && $row['added'] && ($stats['lastupdated'] === null || $row['added'] > $stats['lastupdated'])) {
            $stats['lastupdated'] = $row['added'];
        }

        if ($extended && $row !== false && $row['added']) { // do this only if we're looking for "extended" stats
            // now we take the newest affiliates added
            $query = "SELECT * FROM `$afftable` WHERE `added` = '" .
                $row['added'] . "'";
            $result = $db_link_list->prepare($query);
            $result->execute();
            if (!$result) {
                log_error(__FILE__ . ':' . __LINE__,
                    'Error executing query: <i>' . $result->errorInfo()[2] .
                    '</i>; Query is: <code>' . $query . '</code>');
                die(STANDARD_ERROR);
            }
            $affrows = [];
            $result->setFetchMode(PDO::FETCH_ASSOC);
            while ($affrow = $result->fetch()) {
                $affrows[] = $affrow;
            }

            // prep new affiliates
            require_once('mod_affiliates.php'); // require for f'n
            $newaffiliates = '';
            $newaffiliates_img = '';
            $i = 0;
            foreach ($affrows as $a) {
                if (($i == count($affrows) - 1) && count($affrows) != 1) {
                    $newaffiliates .= 'and ';
                }
                $newaffiliates .= '<a href="' . $a['url'];
                if ($info['linktarget']) {
                    $newaffiliates .= '" target="' . $info['linktarget'];
                }
                $newaffiliates .= '">' . $a['title'] . '</a>, ';
                $newaffiliates_img .= parse_affiliates_template($a['affiliateid'],
                    $info['listingid']);
                $i++;
            }
            $stats['newaffiliates'] = rtrim($newaffiliates, ', ');
            $stats['newaffiliatesimg'] = $newaffiliates_img;

            // sigh, reconnect :p
            try {
                $db_link_list = new PDO('mysql:host=' . $info['dbserver'] . ';charset=utf8', $info['dbuser'],
                    $info['dbpassword']);
                $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo '<p class="error">' . DATABASE_CONNECT_ERROR .
                    " Can't connect to MySQL server on {$info['dbserver']}</p>";

                return;
            }
            $dbselected = $db_link_list->query('USE ' . $info['dbdatabase']);
            if (!$dbselected) {
                echo '<p class="error">' . DATABASE_CONNECT_ERROR .
                    " Can't connect to MySQL database '{$info['dbdatabase']}'</p>";

                return;
            }

            // set default values so that no "undefined index" notice is shown
            $stats['randomaffiliates'] = '';
            $stats['randomaffiliateimg'] = '';
            $stats['randomaffiliate'] = '';

            // get total affiliates
            $query = "SELECT COUNT(*) AS `count` FROM `$afftable`";
            $result = $db_link_list->prepare($query);
            $result->execute();
            if (!$result) {
                log_error(__FILE__ . ':' . __LINE__,
                    'Error executing query: <i>' . $result->errorInfo()[2] .
                    '</i>; Query is: <code>' . $query . '</code>');
                die(STANDARD_ERROR);
            }
            $result->setFetchMode(PDO::FETCH_ASSOC);

            $affnum = $result->fetch();
            $stats['totalaffiliates'] = $affnum['count'];

            if ($stats['totalaffiliates'] > 0) {
                // random affiliate
                $rand = rand(1, $stats['totalaffiliates']) - 1;
                $query = "SELECT * FROM `$afftable` LIMIT :rand, 1";
                $result = $db_link_list->prepare($query);
                $result->bindParam(':rand', $rand, PDO::PARAM_INT);
                $result->execute();
                if (!$result) {
                    log_error(__FILE__ . ':' . __LINE__,
                        'Error executing query: <i>' . $result->errorInfo()[2] .
                        '</i>; Query is: <code>' . $query . '</code>');
                    die(STANDARD_ERROR);
                }
                $result->setFetchMode(PDO::FETCH_ASSOC);
                $randaff = $result->fetch();
                $stats['randomaffiliate'] = '<a href="' . $randaff['url'];
                if ($info['linktarget']) {
                    $stats['randomaffiliate'] .= '" target="' . $info['linktarget'];
                }
                $stats['randomaffiliates'] .= '">' . $randaff['title'] . '</a> ';
                $stats['randomaffiliateimg'] = parse_affiliates_template(
                    $randaff['affiliateid'], $info['listingid']);
            }

            // sigh, reconnect :p
            try {
                $db_link_list = new PDO('mysql:host=' . $info['dbserver'] . ';charset=utf8', $info['dbuser'],
                    $info['dbpassword']);
                $db_link_list->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo '<p class="error">' . DATABASE_CONNECT_ERROR .
                    " Can't connect to MySQL server on {$info['dbserver']}</p>";

                return;
            }
            $dbselected = $db_link_list->query('USE ' . $info['dbdatabase']);
            if (!$dbselected) {
                echo '<p class="error">' . DATABASE_CONNECT_ERROR .
                    " Can't connect to MySQL database '{$info['dbdatabase']}'</p>";

                return;
            }
        }
    }

    // prepare new members format
    $newmembers = '';
    $i = 0;
    foreach ($new as $n) {
        if (($i == count($new) - 1) && count($new) != 1) {
            $newmembers .= 'and ';
        }
        if ($n['url'] != '' && $n['showurl'] == 1) {
            $newmembers .= '<a href="' . $n['url'];
            if ($info['linktarget']) {
                $newmembers .= '" target="' . $info['linktarget'];
            }
            $newmembers .= '">' . $n['name'] . '</a>, ';
        } else {
            $newmembers .= $n['name'] . ', ';
        }
        $i++;
    }
    $stats['new_members'] = rtrim($newmembers, ', ');

    // get total number of members
    $query = "SELECT COUNT(*) AS `count` FROM `$table` WHERE `pending` = 0";
    $result = $db_link_list->prepare($query);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $row = $result->fetch();
    $stats['total'] = (int)$row['count'];

    // random member
    $rand = rand(1, $stats['total']) - 1;
    if ($rand < 1) {
        $rand++;
    } // Fix for 0 or negative output
    $query = "SELECT * FROM `$table` WHERE `pending` = 0 LIMIT :rand, 1";
    $result = $db_link_list->prepare($query);
    $result->bindParam(':rand', $rand, PDO::PARAM_INT);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $randmem = $result->fetch();
    if ($randmem !== false) {
        $stats['randommember'] = $randmem['name'];
        if ($randmem['url'] && $randmem['showurl'] == 1) {
            $stats['randommember'] = '<a href="' . $randmem['url'];
            if ($info['linktarget']) {
                $stats['randommember'] .= '" target="' . $info['linktarget'];
            }
            $stats['randommember'] .= '">' . $stats['randommember'] .
                '</a>';
        }
        if (isset($randmem['country']) && $randmem['country']) {
            $stats['randommember'] .= 'from ' . $randmem['country'];
        }
        $stats['randommember_url'] = $randmem['url'];
        $stats['randommember_name'] = $randmem['name'];
        $stats['randommember_country'] =
            (isset($randmem['country']) && $randmem['country'])
                ? $randmem['country'] : '';
        $stats['randommember_email'] = $randmem['email'];
        $afields = explode(',', $info['additional']);
        foreach ($afields as $field) {
            if ($field) {
                $stats['randommember_' . $field] = $randmem[$field];
            }
        }
    }

    // get total number of PENDING members
    $query = "SELECT COUNT(*) AS `count` FROM `$table` WHERE `pending` = 1";
    $result = $db_link_list->prepare($query);
    $result->execute();
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $row = $result->fetch();
    $stats['pending'] = $row['count'];

    // prepare average number of new fans a day
    $query = "SELECT YEAR( `added` ) AS `year`, MONTH( `added` ) AS " .
        "`month`, DAYOFMONTH( `added` ) AS `day` FROM `$table` WHERE " .
        "`pending` = 0 AND COALESCE(`added`, '0000-00-00') != '0000-00-00' ORDER BY `added` ASC " .
        'LIMIT 1';
    $result = $db_link_list->query($query);
    if (!$result) {
        log_error(__FILE__ . ':' . __LINE__,
            'Error executing query: <i>' . $result->errorInfo()[2] .
            '</i>; Query is: <code>' . $query . '</code>');
        die(STANDARD_ERROR);
    }
    $result->setFetchMode(PDO::FETCH_ASSOC);
    $row = $result->fetch();
    $stats['average'] = 'n/a';
    if ($row !== false) {
        $firstyear = $row['year'];
        $firstmonth = $row['month'];
        $firstday = $row['day'];
        $today = getdate();
        $first = getdate(mktime(0, 0, 0, $firstmonth, $firstday, $firstyear));
        $seconds = $today[0] - $first[0];
        $days = round($seconds / 86400);
        if ($days == 0) {
            $days = 1;
        }
        $stats['average'] = round($stats['total'] / $days, 2);
    }

    // prepare number of countries
    $stats['countries'] = '0';
    if ($info['country'] == 1) {
        $query = 'SELECT COUNT( DISTINCT( `country` ) ) AS `countries` FROM ' .
            "`$table` WHERE `pending` = 0";
        $result = $db_link_list->prepare($query);
        $result->execute();
        if (!$result) {
            log_error(__FILE__ . ':' . __LINE__,
                'Error executing query: <i>' . $result->errorInfo()[2] .
                '</i>; Query is: <code>' . $query . '</code>');
            die(STANDARD_ERROR);
        }
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $row = $result->fetch();
        if ($row !== false) {
            $stats['countries'] = $row['countries'];
        }
    }

    $db_link_list = null;

    return $stats;
}
