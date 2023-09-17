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
require_once('header.php');
require_once('mod_errorlogs.php');
require_once('mod_categories.php');
require_once('mod_owned.php');
require_once('mod_settings.php');
require_once('mod_members.php');
require_once('mod_emails.php');

$show_default = true;
echo '<h1>Manage Members</h1>';
$action = $_REQUEST['action'] ?? '';
$listing = $_REQUEST['id'] ?? '';

/*_____________________________________________________________DELETE/REJECT_*/
if ($action === 'reject' || $action === 'delete') {
    $info = get_listing_info($listing);
    $success = delete_member($listing, $_REQUEST['email']);
    if ($success) {
        echo '<p class="success">Successfully deleted the member with email ' .
            'address <i>' . $_REQUEST['email'] . '</i> from the <i>' .
            $info['subject'] . ' ' . $info['listingtype'] . '</i>.</p>';
    }
    if ($action === 'reject') {
        // still approving/rejecting members
        $listing = '';
    }

    // free up memory
    unset($info, $success);
}


/*___________________________________________________________________APPROVE_*/
if ($action === 'approve') {
    $info = get_listing_info($listing);
    $success = approve_member($listing, $_REQUEST['email']);
    if ($success) {
        echo '<p class="success">Successfully approved the member with ' .
            'email address <i>' . $_REQUEST['email'] . '</i> from the <i>' .
            $info['subject'] . ' ' . $info['listingtype'] . '</i>.</p>';
        // send approval email?
        if ($info['emailapproved']) {
            $to = $_REQUEST['email'];
            $body = parse_email('approved', $listing, $to);
            $subject = $info['title'] . ': Added';
            $from = '"' . html_entity_decode($info['title'], ENT_QUOTES) .
                '" <' . $info['email'] . '>';

            // use send_email function
            $mail_sent = send_email($to, $from, $subject, $body);
            if (!$mail_sent) {
                echo '<p class="error">Approval email sending failed.</p>';
            }
        }
    } else {
        echo '<p class="error">Error approving the member. Try again.</p>';
    }
    $listing = '';

    // free up memory
    unset($info, $success, $to, $body, $subject, $headers);
}


/*______________________________________________________________MULTIPLE_*/
if ($action === 'multiple') {
    $info = get_listing_info($listing);
    $subject = $info['title'] . ': Added';
    $from = '"' . html_entity_decode($info['title'], ENT_QUOTES) .
        '" <' . $info['email'] . '>';
    if (!isset($_POST['email']) || count($_POST['email']) == 0) {
        echo '<p class="error">No pending members were checked and no ' .
            'members have been approved or rejected.</p>';
    } else {
        // check which it is
        $selected = $_POST['selected'];
        if ($selected === 'APPROVE') {
            require_once('Mail.php');
            foreach ($_POST['email'] as $email) {
                $success = approve_member($listing, $email);
                if (!$success) {
                    echo '<p class="error">Error approving member with ' .
                        'email address <i>' . $_REQUEST['email'] . '</i>.</p>';
                } elseif ($info['emailapproved']) { // send if there is
                    $body = parse_email('approved', $listing, $email);

                    // use send_email function
                    $mail_sent = send_email($email, $from, $subject, $body);
                }
            }
            echo '<p class="success">Finished approving selected members.</p>';
        } elseif ($selected === 'REJECT') {
            foreach ($_POST['email'] as $email) {
                $success = delete_member($listing, $email);
                if (!$success) {
                    echo '<p class="error">Error rejecting member with ' .
                        'email address <i>' . $_REQUEST['email'] . '</i>.</p>';
                }
            }
            echo '<p class="success">Finished rejecting selected members.</p>';
        }
    }
    $listing = '';

    // free up memory
    unset($info, $subject, $headers, $success, $body);
}

/*______________________________________________________________________EDIT_*/
if ($action === 'edit') {
    $info = get_listing_info($listing);
    $member = get_member_info($listing, $_REQUEST['email']);
    $show_default = false;
    $show_edit_form = true;

    if (isset($_POST['done'])) {
        $success = edit_member_info($listing, $_REQUEST['email'], $_POST);
        if ($success) {
            echo '<p class="success">Successfully edited the information of ' .
                'the member with email address <i>' . $_REQUEST['email'] .
                '</i> in the <i>' . $info['subject'] . ' ' . $info['listingtype'] .
                '</i>.</p>';
            $show_edit_form = false;
            $show_default = true;
            // if index "approved" is present, the page is from the pending mem
            // unset $listing
            if (isset($_REQUEST['approved'])) {
                $listing = '';
            }
        }
    }

    if ($show_edit_form) {
        $shade = false;
        ?>
        <p>You can edit the member's information using the form below, where the
            current information is shown. Once you've finished editing the
            fields, click on "Edit member info".</p>

        <form method="post" action="members.php">
            <input type="hidden" name="action" value="edit"/>
            <input type="hidden" name="id" value="<?= $info['listingid'] ?>"/>
            <input type="hidden" name="email" value="<?= $member['email'] ?>"/>
            <input type="hidden" name="done" value="yes"/>

            <table>

                <tr>
                    <th colspan="2">
                        <?= $info['subject'] ?> <?= ucwords($info['listingtype']) ?> Member
                    </th>
                </tr>

                <tr>
                    <td>
                        Name
                    </td>
                    <td>
                        <input type="text" name="name" value="<?= htmlentities($member['name']) ?>"/>
                    </td>
                </tr>

                <tr class="rowshade">
                    <td>
                        Email
                    </td>
                    <td>
                        <input type="text" name="email_new" value="<?= $member['email'] ?>"/>
                    </td>
                </tr>

                <tr>
                    <td>
                        Show/Hide Email
                    </td>
                    <td style="text-align: left;">
                        <?php
                        if ($member['showemail'] == 1) {
                            ?>
                            <input type="radio" name="showemail" value="leave" checked="checked"/>
                            Leave as is (Show)<br/>
                            <input type="radio" name="showemail" value="hide"/> Hide<br/>
                            <?php
                        } elseif ($member['showemail'] == 0) {
                            ?>
                            <input type="radio" name="showemail" value="leave" checked="checked"/>
                            Leave as is (Hide)<br/>
                            <input type="radio" name="showemail" value="show"/> Show<br/>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
                <?php
                // toggle $shade after show/hide
                if ($shade) {
                    $shade = false;
                } else {
                    $shade = true;
                }
                ?>

                <?php
                if ($info['country'] == 1) {
                    ?>
                    <tr <?= ($shade) ? 'class="rowshade"' : '' ?>>
                        <td>
                            Country
                        </td>
                        <td>
                            <select name="country">
                                <option value="<?= $member['country'] ?>">Current
                                    (<?= $member['country'] ?>)
                                </option>
                                <option value="<?= $member['country'] ?>">---</option>
                                <?php
                                $countriesValues = include 'countries.inc.php';
                                foreach ($countriesValues as $key => $countryVal) {
                                    echo '<option value="' . $countryVal . '">' . $countryVal . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <?php
                }

                // toggle $shade after country
                if ($shade) {
                    $shade = false;
                } else {
                    $shade = true;
                }
                ?>
                <tr <?= ($shade) ? 'class="rowshade"' : '' ?>>
                    <td>
                        URL
                    </td>
                    <td>
                        <input type="text" name="url" value="<?= $member['url'] ?>"/>
                        <?= ($member['url']) ? '<a href="' . $member['url'] . '"' .
                            ' target="' . $info['linktarget'] . '">(visit)</a>' : '' ?>
                    </td>
                </tr>
                <?php
                // toggle $shade after URL field
                if ($shade) {
                    $shade = false;
                } else {
                    $shade = true;
                }

                if ($info['additional'] != '') {
                    foreach (explode(',', $info['additional']) as $field) {
                        if (!$field) {
                            continue;
                        }
                        ?>
                        <tr <?= ($shade) ? 'class="rowshade"' : '' ?>>
                            <td>
                                <?= ucwords(str_replace('_', ' ', $field)) ?>
                            </td>
                            <td>
                                <input type="text" name="<?= $field ?>"
                                       value="<?= htmlentities($member[$field]) ?>"/>
                            </td>
                        </tr>
                        <?php
                        if ($shade) {
                            $shade = false;
                        } else {
                            $shade = true;
                        }
                    } // end foreach
                } // end if additional
                ?>
                <tr <?= ($shade) ? 'class="rowshade"' : '' ?>>
                    <td>
                        Show/Hide Website
                    </td>
                    <td style="text-align: left;">
                        <?php
                        if ($member['showurl'] == 1) {
                            ?>
                            <input type="radio" name="showurl" value="leave" checked="checked"/>
                            Leave as is (Show)<br/>
                            <input type="radio" name="showurl" value="hide"/> Hide<br/>
                            <?php
                        } elseif ($member['showurl'] == 0) {
                            ?>
                            <input type="radio" name="showurl" value="leave" checked="checked"/>
                            Leave as is (Hide)<br/>
                            <input type="radio" name="showurl" value="show"/> Show<br/>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
                <?php
                // toggle $shade after show/hide
                if ($shade) {
                    $shade = false;
                } else {
                    $shade = true;
                }
                ?>
                <tr<?= ($shade) ? 'class="rowshade"' : '' ?>>
                    <td colspan="2" class="right">
                        <?php
                        if ($member['pending'] == 1) {
                            ?>
                            Approve already?
                            <input type="checkbox" name="approved" value="1"/>
                            <?php
                        }
                        ?>
                        <input type="submit" value="Edit member info"/>
                        <input type="reset" value="Reset form values"/>
                        <input type="button" value="Cancel"
                               onclick="javascript:window.location='members.php?id=<?= $listing
                               ?>';"/>
                    </td>
                </tr>

            </table>
        </form>
        <?php
    }

    // free up memory
    unset($info, $member, $show_edit_form, $success);

}

if ($show_default) {
    ?>
    <div class="submenu">
        <?= ($listing)
            ? '<a href="emails.php?action=members&id=' . $listing . '">Email</a>'
            : '' ?>
    </div>

    <form action="members.php" method="get">

        <p class="right"> Manage:
            <select name="id">
                <option value="">All pending members</option>
                <?php
                $owned = get_owned('current'); // all current owned
                foreach ($owned as $id) {
                    $info = get_listing_info($id);
                    echo '<option value="' . $id;
                    if ($id === $listing) {
                        echo '" selected="selected';
                    }
                    echo '">' . $info['subject'] . ' ' . $info['listingtype'] . ' </option>';
                }
                ?>
            </select>
            <input type="submit" value="Manage"/>
        </p>

    </form>

    <p>Via this section, you may manage the members of each listing you run.
        Select which members you would like to manage from the dropdown above.
    </p>
    <?php
    if ($listing) { //////////////////////////////////////////////// MANAGE
        $info = get_listing_info($listing);
        $searchText = $_GET['search'] ?? null;
        ?>
        <form action="members.php" method="get">
            <input type="hidden" name="dosearch" value="now"/>
            <input type="hidden" name="id" value="<?= $listing ?>"/>

            <p class="center">
                <input type="text" name="search" <?= $searchText !== null ? (' value="' . $searchText . '"') : '' ?>/>
                <input type="submit" value="Search"/>
            </p>

        </form>
        <?php
        $start = $_REQUEST['start'] ?? '0';

        $total = 0;
        $members = [];
        if (isset($_GET['dosearch'])) {
            $members = search_members($searchText, $listing,
                'approved', $start, get_setting('per_page'));
            $total = count(search_members($searchText, $listing,
                'approved'));
        } else {
            $members = get_members($listing, 'approved', [], $start,
                'bydate', get_setting('per_page'));
            $total = count(get_members($listing, 'approved'));
        }
        ?>
        <table>

            <tr>
                <th>Action</th>
                <th>Email</th>
                <?= ($info['country']) ? '<th>Country</th>' : '' ?>
                <th>Name</th>
                <th>URL</th>
                <?= ($info['additional'] !== '') ? '<th>Additional</th>' : '' ?>
            </tr>
            <?php
            $shade = false;
            foreach ($members as $member) {
                $class = ($shade) ? ' class="rowshade"' : '';
                $shade = !$shade;
                ?>
                <tr<?= $class ?>>
                    <td>
                        <a href="members.php?action=edit&id=<?= $listing
                        ?>&email=<?= urlencode($member['email']) ?>"><img src="edit.gif" width="42"
                                                                          height="19" border="0" alt=" edit"/></a>
                        <a href="emails.php?action=directemail&address=<?= urlencode($member['email']) ?>&listing=<?= $listing ?>"><img
                                    src="email.gif"
                                    width="42" height="19" border="0" alt=" email"/></a>
                        <a href="members.php?action=delete&id=<?= $listing
                        ?>&email=<?= urlencode($member['email']) ?>" onclick="
                                go = confirm('Are you sure you want to delete <?= addslashes($member['name']) ?>?'); return go;"><img
                                    src="delete.gif" width="42" height="19" border="0" alt=" delete"
                            /></a>
                    </td>
                    <td>
                        <?= $member['email'] ?>
                    </td>
                    <td>
                        <?= ($info['country']) ? $member['country'] . '</td><td>' : '' ?>
                        <?= $member['name'] ?>
                    </td>
                    <td>
                        <a href="<?= $member['url'] ?>" target="<?= $info['linktarget']
                        ?>"><?= $member['url'] ?></a>
                    </td>
                    <?php
                    if ($info['additional'] != '') {
                        echo '<td>';
                        foreach (explode(',', $info['additional']) as $field) {
                            if ($member[$field] == '') {
                                continue;
                            }
                            if ($field != '') {
                                echo '<b>' . ucwords(str_replace('_', ' ', $field)) .
                                    '</b>: ' . $member[$field] . '<br />';
                            }
                        }
                        echo '</td>';
                    }
                    ?>
                </tr>
                <?php
            }
            ?>
        </table>
        <?php
        $url = 'members.php';
        $connector = '?';
        $req = array_merge($_GET, $_POST);
        foreach ($req as $key => $value) {
            if ($key !== 'start' && $key !== 'PHPSESSID') {
                $url .= $connector . $key . '=' . $value;
                $connector = '&amp;';
            }
        }

        echo PaginationUtils::getPaginatorHTML($total, (int)get_setting('per_page'), $url . $connector);

    } else { /////////////////////////////////////////////////////// PENDING
        $finalcount = 0;
        foreach ($owned as $id) {
            $info = get_listing_info($id);
            $qtycol = 6;
            if ($info['country'] == 0) {
                $qtycol--;
            }
            if ($info['additional'] != '') {
                $qtycol++;
            }
            $pending = get_members($id, 'pending');
            if (count($pending) > 0) {
                $finalcount += count($pending);
                $approved = count(get_members($id, 'approved'));
                ?>
                <form action="members.php" method="post" name="listing<?= $id ?>">
                    <input type="hidden" name="id" value="<?= $id ?>"/>
                    <input type="hidden" name="action" value="multiple"/>

                    <table style="width: 100%;">

                        <tr>
                            <th colspan="<?= $qtycol ?>">
                                <b><?= ucwords($info['subject']) ?>
                                    <?= ucwords($info['listingtype']) ?></b>
                                <small><a href="<?= $info['url'] ?>">(view site)</a></small> -
                                <?= count($pending) ?> pending,
                                <?= $approved ?> approved
                            </th>
                        </tr>

                        <tr class="subheader">
                            <td>
                                <input type="checkbox" onclick="
                                        checkAll( document.listing<?= $id ?>, this, 'email[]' );"/>
                            </td>
                            <td>Action</td>
                            <td>Email</td>
                            <?= ($info['country']) ? '<td>Country</td>' : '' ?>
                            <td>Name</td>
                            <td>URL</td>
                            <?= ($info['additional'] != '') ? '<td>Additional</td>' : '' ?>
                        </tr>
                        <?php
                        $shade = false;
                        foreach ($pending as $member) {
                            $class = '';
                            if ($shade) {
                                $class = ' class="rowshade"';
                                $shade = false;
                            } else {
                                $shade = true;
                            }

                            $update = '';
                            if ($member['added'] != '') {
                                $update = '<b class="important">*</b>';
                            }
                            ?>
                            <tr<?= $class ?>>
                                <td class="center">
                                    <input type="checkbox" name="email[]" value="<?= $member['email'] ?>"/>
                                </td>
                                <td>
                                    <a href="members.php?action=approve&id=<?= $id ?>&email=<?= urlencode($member['email']) ?>"><img
                                                src="approve.gif" width="42"
                                                height="19" border="0" alt=" approve" title=" approve"/></a>
                                    <a href="members.php?action=edit&id=<?= $id ?>&email=<?= urlencode($member['email']) ?>"><img
                                                src="edit.gif" width="42"
                                                height="19" border="0" alt=" edit" title=" edit"/></a>
                                    <a href="emails.php?action=directemail&address=<?= urlencode($member['email']) ?>&listing=<?= $id ?>"><img
                                                src="email.gif" width="42" height="19" border="0"
                                                alt=" email" title=" email"/></a>
                                    <a href="members.php?action=reject&id=<?= $id ?>&email=<?= urlencode($member['email']) ?>"
                                       onclick="
                                               go = confirm('Are you sure you want to reject <?= addslashes($member['name']) ?>?'); return go;"><img
                                                src="reject.gif" width="42" height="19" border="0"
                                                alt=" reject" title=" reject"/></a>
                                </td>
                                <td>
                                    <?= $update ?><?= $member['email'] ?>
                                </td>
                                <td>
                                    <?= ($info['country'] == 1)
                                        ? $member['country'] . '</td><td>' : '' ?>
                                    <?= $member['name'] ?>
                                </td>
                                <td>
                                    <?= ($member['url'])
                                        ? '<a href="' . $member['url'] . '" target="' .
                                        $info['linktarget'] . '">' . $member['url'] . '</a>'
                                        : '' ?>
                                </td>
                                <?php
                                if ($info['additional'] != '') {
                                    echo '<td>';
                                    foreach (explode(',', $info['additional']) as $field) {
                                        if (!$member[$field]) {
                                            continue;
                                        }
                                        echo '<b>' . ucwords(str_replace('_', ' ', $field)) .
                                            '</b>: ' . $member[$field] . '<br />';
                                    }
                                    echo '</td>';
                                }
                                ?>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr<?= ($shade) ? ' class="rowshade"' : '' ?>>
                            <td colspan="<?= $qtycol ?>" class="right">
                                Mass approval:
                                <input type="submit" name="selected" value="APPROVE" style="font-weight: bold;"/>
                                <input type="submit" name="selected" value="REJECT" onclick="
                  go=confirm('Are you sure you want to reject all the checked members?');return go;"/>
                            </td>
                        </tr>
                    </table>

                </form>
                <?php
            }
        }

        if ($finalcount == 0) {
            echo '<p class="success">There are no pending members!</p>';
        }
    }
    unset($owned, $info, $id, $start, $total, $members, $shade, $class,
        $member, $field, $page_qty, $url, $connector, $start_link,
        $finalcount, $qtycol, $pending);
}
require_once('footer.php');
