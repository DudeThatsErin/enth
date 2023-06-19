<?php
declare(strict_types = 1);
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

namespace RobotessNet\DeleteFromFl;

use RobotessNet\StringUtils;

final class Handler
{
    /**
     * @var bool
     */
    private $showForm = false;

    /**
     * @var string
     */
    private $responseMessage = '';

    /**
     * @var string
     */
    private $cleanEmail = '';

    public function handle(int $listing, array $postData, array $listingInfo, string $errorstyle): bool
    {
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
            return false;
        }

        // check nonce field
        $nonce = explode(':', StringUtils::instance()->clean($postData['enth_nonce']));
        $mdfived = substr($nonce[2], 0, (strlen($nonce[2]) - 3));
        $appended = substr($nonce[2], -3);

        // check the timestamp; must not be over 12 hours before, either :p
        if (abs($nonce[1] - strtotime(date('r'))) > (60 * 60 * 12)) {
            // join window expired, try again
            echo "<p$errorstyle>ERROR: Please try again.</p>";
            return false;
        }

        // check if the rand and the md5 hash is correct... last three digits first
        if ($appended !== substr($nonce[0], 2, 3)) {
            // appended portion of random chars doesn't match actual random chars
            echo "<p$errorstyle>ERROR: Please try again.</p>";
            return false;
        }

        // now check the hash
        if (md5($nonce[0]) !== $mdfived) {
            // hash of random chars and the submitted one isn't the same!
            echo "<p$errorstyle>ERROR: Please try again.</p>";
            return false;
        }

        $this->cleanEmail = StringUtils::instance()->cleanNormalize($postData['enth_email']);
        $password = StringUtils::instance()->clean($postData['enth_password']);

        if ($this->cleanEmail === '' || $password === '') {
            $this->showForm = true;
            $this->responseMessage = 'Please fill out both email and password';
            return false;
        }

        $member = get_member_info($listing, $this->cleanEmail);
        if (!$member) {
            $this->showForm = true;
            $this->responseMessage = 'User with the given email address was not found in the system';
            return false;
        }

        // check password
        if (!(check_member_password($listing, $this->cleanEmail, $password))) {
            $this->showForm = true;
            $this->responseMessage = 'The password you supplied does not match ' .
                'the password entered in the system. If you have lost your ' .
                'password, <a href="' . $listingInfo['lostpasspage'] .
                '">click here</a>.';
            return false;
        }

        if ((int)$member['pending'] === 1) {
            $this->showForm = true;
            $this->responseMessage = 'Looks like this user is pending approval. Please get approved first. Sorry for inconvenience';
            return false;
        }

        $success = delete_member($listing, $this->cleanEmail);
        if (!$success) {
            $this->responseMessage = 'Something went wrong during trying to remove you from fanlisting.' .
                ' Please <a href="mailto:' . (str_replace('@', '&#' . ord('@') . ';', $listingInfo['email'])) .
                '">email me</a> so that I can manually remove your data. Sorry for inconvenience.';
            return false;
        }

        $this->responseMessage = <<<HTML
                    <p class="show_delete_process">Your information has been
                        successfully removed from the member database.</p>
HTML;
        return true;
    }

    public function isShowForm(): bool
    {
        return $this->showForm;
    }

    public function getResponseMessage(): string
    {
        return $this->responseMessage;
    }

    public function getCleanEmail(): string
    {
        return $this->cleanEmail;
    }
}
