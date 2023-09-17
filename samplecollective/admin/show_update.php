<?php
/*****************************************************************************
 * Enthusiast: Listing Collective Management System
 * Copyright (c) by Angela Sabas http://scripts.indisguise.org/
 * Copyright (c) 2018 by Lysianthus (contributor) <she@lysianth.us>
 * Copyright (c) 2019 by Ekaterina http://scripts.robotess.net
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
require 'config.php';

use RobotessNet\StringUtils;

require_once('mod_errorlogs.php');
require_once('mod_owned.php');
require_once('mod_members.php');
require_once('mod_settings.php');
require_once('mod_emails.php');

$install_path = get_setting('installation_path');
require_once($install_path . 'Mail.php');

if (!isset($listing)) {
    echo '!! You haven\'t set $listing variable in config.php. Please set it first - the instruction is at the end of the file.<br/>';

    return;
}

// get listing info
$info = get_listing_info($listing);

// initialize variables
$show_form = true;
$messages = [];
$errorstyle = ' style="font-weight: bold; display: block;" ' .
    'class="show_update_error"';
$data = [];

$countriesValues = include 'countries.inc.php';
$countryId = null;

// process forms
if (isset($_POST['enth_update']) && $_POST['enth_update'] === 'yes') {
    // do some spam/bot checking first
    $goahead = false;
    // 1. check that user is submitting from browser
    // 2. check the POST was indeed used
    if (isset($_SERVER['HTTP_USER_AGENT']) &&
        $_SERVER['REQUEST_METHOD'] === 'POST') {
        $goahead = true;
    }

    if (!$goahead) {
        echo "<p$errorstyle>ERROR: Attempted circumventing of the form detected.</p>";
        return;
    }

    // check nonce field
    $nonce = explode(':', StringUtils::instance()->clean($_POST['enth_nonce']));
    $mdfived = substr($nonce[2], 0, (strlen($nonce[2]) - 3));
    $appended = substr($nonce[2], -3);
    // check the timestamp; must be more than three seconds after
    if (abs($nonce[1] - strtotime(date('r'))) < 3) {
        // probably a bot, or multiple-clicking... do this again
        echo "<p$errorstyle>ERROR: Please try again.</p>";
        return;
    }
    // check the timestamp; must not be over 12 hours before, either :p
    if (abs($nonce[1] - strtotime(date('r'))) > (60 * 60 * 12)) {
        // join window expired, try again
        echo "<p$errorstyle>ERROR: Please try again.</p>";
        return;
    }
    // check if the rand and the md5 hash is correct... last three digits first
    if ($appended != substr($nonce[0], 2, 3)) {
        // appended portion of random chars doesn't match actual random chars
        echo "<p$errorstyle>ERROR: Please try again.</p>";
        return;
    }
    // now check the hash
    if (md5($nonce[0]) != $mdfived) {
        // hash of random chars and the submitted one isn't the same!
        echo "<p$errorstyle>ERROR: Please try again.</p>";
        return;
    }

    // check password
    $cleanNormalizedOldEmail = StringUtils::instance()->cleanNormalize($_POST['enth_email']);

    if (!(check_member_password($listing, $cleanNormalizedOldEmail, StringUtils::instance()->clean($_POST['enth_old_password'])))) {
        $messages['form'] = 'The password you supplied does not match ' .
            'the password entered in the system. If you have lost your ' .
            'password, <a href="' . $info['lostpasspage'] .
            '">click here</a>.';
    } // check email
    else {
        $data['email'] = $cleanNormalizedOldEmail;
        $data['old_password'] = StringUtils::instance()->clean($_POST['enth_old_password']);
    }

    // check validate password
    if ($_POST['enth_password'] != $_POST['enth_passwordv']) {
        $messages['password'] = 'Password validation error. Please check ' .
            'if you have entered the new passwords correctly.';
    } else {
        $data['password'] = StringUtils::instance()->clean($_POST['enth_password']);
    }

    if (count($messages) == 0) {
        // fill out blank fields with current member info
        $member = get_member_info($listing, $cleanNormalizedOldEmail);

        // "new" email?
        if ($_POST['enth_email_new'] == '') {
            $data['email_new'] = $member['email'];
        } else {
            $data['email_new'] = StringUtils::instance()->cleanNormalize($_POST['enth_email_new']);
        }

        // new name?
        $data['name'] = ucwords(StringUtils::instance()->clean($_POST['enth_name']));
        if ($data['name'] == '') {
            $data['name'] = ucwords($member['name']);
        }

        // new country
        if (isset($_POST['enth_country']) && $_POST['enth_country'] == '') {
            $data['country'] = $member['country'];
        } else {
            $countryId = (int)(StringUtils::instance()->cleanNormalize($_POST['enth_country']));
            $data['country'] = $countriesValues[$countryId] ?? $member['country'];
        }

        // new url
        if ($_POST['enth_url'] == '' && !isset($_POST['enth_url_delete'])) {
            $data['url'] = $member['url'];
        } else {
            $url = StringUtils::instance()->cleanNormalize($_POST['enth_url']);
            if (preg_match('@^https?://@', $url) === false) {
                $url = 'http://' . $url;
            }
            $data['url'] = $url;
        }

        // show email address?
        $data['showemail'] = $member['showemail'];
        if ($_POST['enth_showemail'] == 'show') {
            $data['showemail'] = 'show';
        } else if ($_POST['enth_showemail'] == 'hide') {
            $data['showemail'] = 'hide';
        } else {
            unset($data['showemail']);
        }

        if ($info['additional'] != '') {
            $fields = explode(',', $info['additional']);
            foreach ($fields as $field) {
                if ($field == '') {
                    continue;
                }
                if ($_POST["enth_$field"] == '' && !isset($_POST['enth_' . $field . '_delete'])) {
                    $data[$field] = $member[$field];
                } else {
                    $data[$field] = StringUtils::instance()->clean($_POST["enth_$field"]);
                }
            }
        }

        // do actual update!
        $success = edit_member_info($listing, $cleanNormalizedOldEmail, $data, 'hold');
        if ($success) {
            // check if hold and send email
            if ($info['holdupdate'] == 1 && $info['notifynew'] == 1) {
                $subject = $info['subject'];
                $listingtype = $info['listingtype'];
                $listingurl = $info['url'];
                $notify_subject = "$subject - Update member!";
                $notify_message = 'Someone has updated his/her information at ' .
                    "$subject $listingtype ($listingurl). Relevant information " .
                    "is below:\r\n\r\n" .
                    'Name: ' . $data['name'] . "\r\n" .
                    'Email: ' . $data['email_new'] . "\r\n" .
                    'Country: ' . $data['country'] . "\r\n" .
                    'URL: ' . $data['url'] . "\r\n";
                $fields = explode(',', $info['additional']);
                foreach ($fields as $field) {
                    if ($field != '') {
                        $notify_message .= ucwords(str_replace('_', ' ',
                                $field)) . ': ' . $data[$field] . "\r\n";
                    }
                }
                $notify_message .= "\r\nTo add this member, go to " .
                    str_replace(get_setting('root_path_absolute'),
                        get_setting('root_path_web'),
                        get_setting('installation_path')) . "members.php\r\n";
                $notify_message = stripslashes($notify_message);
                $notify_from = 'Enthusiast <' .
                    get_setting('owner_email') . '>';

                // use send_email function
                $mail_sent = send_email($info['email'], $notify_from,
                    $notify_subject, $notify_message);
            } // end send email to owner

            // send email to member
            $to = $data['email_new'];
            $body = parse_email('update', $info['listingid'], $to,
                $data['password']);
            $body = stripslashes($body);
            $subject = $info['title'] . ': Update Information';
            $from = '"' . html_entity_decode($info['title'], ENT_QUOTES) .
                '" <' . $info['email'] . '>';

            // use send_email function
            $mail_sent = send_email($to, $from, $subject, $body);

            $show_form = false;
            if ($info['holdupdate'] == 1) {
                ?>
                <p class="show_update_process_hold">Your information has been
                    successfully updated in the member database. Update information
                    holding has been activated for this fanlisting, and you been
                    placed back on the pending queue for me to review the changes
                    you have made to your record. You will be moved back to the
                    members list as soon as I have updated the listing again.</p>

                <p class="show_update_process_hold">If in two weeks you still
                    have not received the approval email again and you are still not
                    on the members list, please feel free to <a
                            href="mailto:<?= str_replace('@', '&#' . ord('@') . ';', $info['email'])
                            ?>">email me</a> and ask about your pending update request.</p>

                <p class="show_update_process_hold">Thank you for keeping your
                    information up to date with us!</p>
                <?php
            } else {
                ?>
                <p class="show_update_process_nohold">Your information has been
                    successfully updated in the member database. Thank you for
                    keeping your information up to date with us!</p>
                <?php
            }
        }
    }
}

if ($show_form) {
    // extra spam checking variable
    $rand = md5(uniqid('', true));
    ?>
    <p class="show_update_intro">If you're a member of the
        <?= $info['listingtype'] ?> and you want to modify your information
        listed here, please fill out the form below. Your [old] email address
        and password is required for this form.</p>

    <p class="show_update_intro_instructions"><b>Important:</b> Leave the
        fields you wish unchanged blank, and hit submit only once when you are
        sure you want to change your information.</p>

    <?= (isset($messages['form']))
        ? "<p$errorstyle>{$messages['form']}</p>" : '' ?>

    <p class="show_update_intro_link_to_join"><a href="<?= $info['joinpage'] ?>">If you want to join the
            <?= $info['listingtype'] ?> please use this other form.</a></p>

    <!-- Enthusiast <?= RobotessNet\App::getVersion() ?> Update Form -->
    <form method="post" action="<?= $info['updatepage'] ?>"
          class="show_update_form">

        <p class="show_update_old_email">
            <input type="hidden" name="enth_update" value="yes"/>
            <input type="hidden" name="enth_nonce"
                   value="<?= $rand ?>:<?= strtotime(date('r')) ?>:<?= md5($rand) . substr($rand, 2, 3) ?>"/>
            <span style="display: block;" class="show_update_old_email_label">
   * Old email address:</span>
            <input type="email" name="enth_email" class="show_update_old_email_field" required/>
        </p>

        <p class="show_update_current_password">
   <span style="display: block;" class="show_update_current_password_label">
   * Current password: (<a href="<?= $info['lostpasspage'] ?>">Lost it?</a>)
   </span>
            <input type="password" name="enth_old_password"
                   class="show_update_current_password_field" autocomplete="off" required/>
        </p>

        <p class="show_update_name">
   <span style="display: block;" class="show_update_name_label">
   New name: </span>
            <input type="text" name="enth_name" class="show_update_name_field"/>
        </p>

        <p class="show_update_email">
   <span style="display: block;" class="show_update_email_label">
   New email address: </span>
            <input type="email" name="enth_email_new" class="show_update_email_field"/>
        </p>

        <p class="show_update_password">
   <span style="display: block;" class="show_update_password_label">
   New password (type twice): </span>
            <?= (isset($messages['password']))
                ? "<p$errorstyle>{$messages['password']}</p>" : '' ?>
            <input type="password" name="enth_password" class="show_update_password_field"/>
            <input type="password" name="enth_passwordv" class="show_update_password_field2"/>
        </p>

        <p class="show_update_email_settings">
   <span style="display: block;" class="show_update_email_settings_label">
   Show email address? </span>
            <span style="display: block" class="show_update_email_settings_leave">
   <input type="radio" name="enth_showemail" value="leave" checked="checked"
          class="show_update_email_settings_field"/>
      <span class="show_update_email_settings_field_label">
      Leave it as it is </span>
   </span><span style="display: block" class="show_update_email_settings_yes">
   <input type="radio" name="enth_showemail" value="show"
          class="show_update_email_settings_field"/>
      <span class="show_update_email_settings_field_label">
      Yes (SPAM-protected on the site) </span>
   </span><span style="display: block" class="show_update_email_settings_no">
   <input type="radio" name="enth_showemail" value="hide"
          class="show_update_email_settings_field"/>
      <span class="show_update_email_settings_field_label">
      No </span>
   </span>
        </p>

        <?php
        if ($info['country'] == 1) {
            ?>
            <p class="show_update_country">
      <span style="display: block;" class="show_update_country_label">
      New country: </span>
                <select name="enth_country" class="show_update_country_field">
                    <option value=""></option>
                    <?php
                    foreach ($countriesValues as $key => $countryVal) {
                        $selected = '';
                        if (!empty($countryId) && $countryId === $key) {
                            $selected = ' selected="selected"';
                        }
                        echo '<option value="' . $key . '"' . $selected . '>' . $countryVal . '</option>';
                    }
                    ?>
                </select>
            </p>
            <?php
        }
        ?>
        <p class="show_update_url">
   <span style="display: block;" class="show_update_url_label">
   New website URL: </span>
            <input type="url" name="enth_url" class="show_update_url_field"/>
            <span style="display: block;" class="show_update_url_delete">
   <input type="checkbox" name="enth_url_delete" value="yes"
          class="show_update_url_delete_field"/>
      Delete your website record?
   </span>
        </p>
        <?php
        if ($info['additional'] && file_exists('updateform.inc.php')) {
            require_once('updateform.inc.php');
        } else {
            $fields = explode(',', $info['additional']);
            foreach ($fields as $field) {
                if ($field == '') {
                    continue;
                }
                ?>
                <p class="show_update_<?= $field ?>">
         <span style="display: block;" class="show_update_<?= $field ?>_label">
         New <?= ucwords(str_replace('_', ' ', $field)) ?>: </span>
                    <input type="text" name="enth_<?= $field
                    ?>" class="show_update_<?= $field ?>_field"/>
                    <span style="display: block;" class="show_update_<?= $field
                    ?>_delete">
         <input type="checkbox" name="enth_<?= $field ?>_delete" value="yes"
                class="show_update_<?= $field ?>_delete_field"/>
         Delete your <?= str_replace('_', ' ', $field) ?> record? </span>
                </p>
                <?php
            }
        }
        ?>
        <p class="show_update_submit">
            <input type="submit" value="Modify my information"
                   class="show_update_submit_button"/>
        </p>

    </form>

   <!-- // do not remove the credit link please-->
    <p style="text-align: center;" class="show_update_credits">
        <?php include ENTH_PATH . 'show_credits.php' ?>
    </p>
    <?php
}
