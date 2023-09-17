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

use RobotessNet\App;

final class Form
{
    public function print(array $listingInfo = [], string $errorStyle = '', string $email = '', string $errorMessage = ''): void
    {
        $rand = md5(uniqid('', true));
        ?>
        <form method="post" class="show_delete_form">
            <p class="show_delete_intro">If you're a member of the
                fanlisting and you want to remove your data from the fanlisting, please fill out the form
                below. </p>

            <?= (isset($errorMessage)) ? "<p$errorStyle>{$errorMessage}</p>" : '' ?>

            <!-- Enthusiast <?= App::getVersion() ?> Delete Form -->
            <p class="show_delete_email">
                <input type="hidden" name="enth_delettte" value="yes">
                <input type="hidden" name="enth_nonce"
                       value="<?= $rand ?>:<?= strtotime(date('r')) ?>:<?= md5($rand) . substr($rand, 2, 3) ?>"/>
                <span style="display: block;" class="show_delete_email_label">
   Email address:</span>
                <input type="email" autocomplete="off" name="enth_email" class="show_delete_email_field" required
                       value="<?= $email ?>">
            </p>

            <p class="show_delete_password">
   <span style="display: block;" class="show_delete_password_label">
   Password: (<a href="<?= $listingInfo['lostpasspage'] ?>">Lost it?</a>)
   </span>
                <input type="password" autocomplete="off" name="enth_password" class="show_delete_password_field"
                       required>
            </p>

            <p class="show_delete_submit">
                <input type="submit" value="Delete yourself" class="show_delete_submit_button">
            </p>

        </form>
        <?php
    }
}
