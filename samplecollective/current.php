<?php
include "header.php";
?>
<b>Current owned fanlistings:</b>
<?php      
$status = 'current';
/*      $hide_dropdown = false;      $show_list = true;      $show = 'all';*/	  
$show_list = true;      
include ENTH_PATH . 'show_owned.php';
include "footer.php";