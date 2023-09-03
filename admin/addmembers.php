<?php
/*****************************************************************************
 Enthusiast: Listing Collective Management System
 Copyright (c) by Angela Sabas
 http://scripts.indisguise.org/

 This script is made available for free download, use, and modification as
 long as this note remains intact and a link back to
 http://scripts.indisguise.org/ is given. It is hoped that the script
 will be useful, but does not guarantee that it will solve any problem or is
 free from errors of any kind. Users of this script are forbidden to sell or
 distribute the script in whole or in part without written and explicit
 permission from me, and users agree to hold me blameless from any
 liability directly or indirectly arising from the use of this script.

 For more information please view the readme.txt file.
******************************************************************************/
use RobotessNet\StringUtils;

session_start();
require_once( 'logincheck.inc.php' );
if( !isset( $logged_in ) || !$logged_in ) {
   $_SESSION['message'] = 'You are not logged in. Please log in to continue.';
   $next = '';
   if( isset( $_SERVER['REQUEST_URI'] ) )
      $next = $_SERVER['REQUEST_URI'];
   else if( isset( $_SERVER['PATH_INFO'] ) )
      $next = $_SERVER['PATH_INFO'];
   $_SESSION['next'] = $next;
   header( 'location: index.php' );
   die( 'Redirecting you...' );
   }
require_once( 'header.php' );
require_once( 'config.php' );
require_once( 'mod_categories.php' );
require_once( 'mod_owned.php' );
require_once( 'mod_settings.php' );
require_once( 'mod_members.php' );

$country = '';
$countriesValues = include 'countries.inc.php';
$countryId = null;

$show_default = true;
?>
<p class="title">
Add Members
</p>
<?php
if( isset( $_REQUEST['id'] ) && $_REQUEST['id'] != '' ) {
   $show_default = false;

   $info = get_listing_info( $_REQUEST['id'] );
   $fields = explode( ',', $info['additional'] );
   if( $fields[0] == '' )
      array_pop( $fields );


   if( isset( $_POST['add'] ) && $_POST['add'] == 'yes' ) {

      // generate password
      $password = '';
      $k = 0;
      while( $k <= 10 ) {
         $password .= chr( rand( 97, 122 ) );
         $k++;
         }

      if( !isset( $_POST['addanother'] ) || $_POST['addanother'] != 'yes' )
         $show_default = true;

      $table = $info['dbtable'];
      $email = $_POST['email'];
      $name = $_POST['name'];

      // get country
      if (isset($_POST['enth_country']) && $_POST['enth_country'] !== '') {
        $countryId = (int)(StringUtils::instance()->cleanNormalize($_POST['enth_country']));
          if (isset($countriesValues[$countryId])) {
            $country = $countriesValues[$countryId];
          }
      }

      $url = $_POST['url'];
      if( count( $fields ) > 0 )
         foreach( $fields as $field ) {
            $values[$field] = '';
            if( isset( $_POST[$field] ) )
               $values[$field] = $_POST[$field];
            }


      $query = "INSERT INTO `$table` VALUES( '$email', '$name', ";
      if( $info['country'] == 1 )
         $query .= "'$country', ";
      $query .= "'$url', ";
      if( count( $fields ) > 0 )
         foreach( $fields as $field )
            $query .= '"' . $values[$field] . '", ';
      $query .= "0, MD5( '$password' ), 1, 1, NULL )";

      $db_link = mysqli_connect( $db_server, $db_user, $db_password )
         or die( 'Cannot connect to the database. Try again.' );
      mysqli_select_db( $db_link, $db_database )
         or die( 'Cannot connect to the database. Try again.' );
      $result = mysqli_query( $db_link, $query );

      if( $result )
         echo '<p class="important">Successfully added ' . $name . ' (' .
            $email . ') to the ' . $info['listingtype'] . '!</p>';
      else
         echo '<p class="important">Error adding ' . $name . ' (' .
            $email . ') to the ' . $info['listingtype'] . '.</p>';

      }

   if( !$show_default ) {
?>
   <p>
   You are adding members to the <i><?= $info['subject'] ?>
   <?= $info['listingtype'] ?>: <?= $info['title'] ?></i>.
   </p>

   <p>
   Please fill out the form below. If you wish to add another member after you
   submit the form, please remember to keep the "Add another" checkbox
   checked. All members added this way are automatically approved,
   passwords are generated for them, and email addresses are shown on the site.
   </p>

   <p>
   Please note that these members are added without a date attached to their
   records. That means you will have to edit one or two (or add an affiliate
   to the <?= $info['listingtype'] ?>) to have a valid last updated date
   appear for your statistics.
   </p>

   <form action="addmembers.php" method="post">
   <input type="hidden" name="id" value="<?= $_REQUEST['id'] ?>" />
   <input type="hidden" name="add" value="yes" />

   <p class="center">
   <table>

   <tr><td>
   Name
   </td><td>
   <input type="text" name="name" required />
   </td></tr>

   <tr><td>
   Email address
   </td><td>
   <input type="text" name="email" required />
   </td></tr>

<?php
   if( $info['country'] == 1 ) {
?>
      <tr><td>
      Country
      </td><td>


      <select name="enth_country" class="show_join_country_field" required>
        <?php
          foreach ($countriesValues as $key => $countryVal) {
          $selected = '';
            if ($country !== '' && $countryId === $key) {
              $selected = ' selected="selected"';
            }
            echo '<option value="' . $key . '"' . $selected . '>' . $countryVal . '</option>';
          }
          ?>
      </select>
      </td></tr>
        <?php
          }
        ?>

   <tr><td>
   Website URL
   </td><td>
   <input type="text" name="url" />
   </td></tr>

<?php
   if( count( $fields ) > 0 ) {
      foreach( $fields as $field ) {
?>
         <tr><td>
         <?= ucfirst( $field ) ?>
         </td><td>
         <input type="text" name="<?= $field ?>" />
         </td></tr>
<?php
         }
      }
?>

   <tr><td colspan="2">
   Add another?
<?php
   if( isset( $_POST['addanother'] ) && $_POST['addanother'] == 'yes' ) {
?>
      <input type="checkbox" name="addanother" value="yes" checked="checked" />
<?php
      }
   else {
?>
      <input type="checkbox" name="addanother" value="yes" />
<?php
      }
?>
   </td></tr>

   <tr><td colspan="2">
   <input type="submit" value="Add member" />
   </td></tr>

   </table></p>

   </form>

<?php
      }
   }

if( $show_default ) {
?>
   <p>
   This addon allows you to add members to a listing without the "side-effects"
   of emailing them or getting a notification yourself. This is useful if you
   have adopted a fanlisting from someone who updates manually or if the
   script s/he's using cannot be easily converted to the Enthusiast database
   structure (i.e., no conversion script is available). <b>Please use this
   feature responsibly!</b> You will need to be logged in in order to use
   this feature to prevent unwanted people from wreaking havoc on your
   listings. :P
   </p>

   <p>
   To use this feature, first select the listing you wish to add members to
   below. You will then be presented with a form for adding members, and
   an option to add another, until you are done.
   </p>

   <form action="addmembers.php" method="get">

   <p class="center">
   <select name="id">
<?php
   $owned = get_owned();
   foreach( $owned as $id ) {
      $info = get_listing_info( $id );
      echo '<option value="' . $id . '">' . $info['subject'] . ' ' .
         $info['listingtype'] . '</option>';
      }
?>
   </select>

   <input type="submit" value="Add members" />

   </p></form>

<?php
   }
require_once( 'footer.php' );
?>