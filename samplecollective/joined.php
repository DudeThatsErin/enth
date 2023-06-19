<?php include "header.php"; ?>

Last joined: <?= isset($joined_newest['added']) && strtotime($joined_newest['added']) !== false ? date('F j, Y', strtotime($joined_newest['added'])) : 'Unknown'; ?>  ﻿<br/>

    <b>Joined fanlistings:</b>

<?php
$show_list = true;
$show_all_by_categories = true;
include ENTH_PATH . 'show_joined.php';
?>

<?php
include "footer.php";