<?php
include "header.php"; ?><b>Pending fanlistings:</b><?php
$status = 'pending';
$hide_dropdown = false;
$show_list = true;
$show = 'all';
include ENTH_PATH . 'show_owned.php';
include "footer.php";