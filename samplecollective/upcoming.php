<?php
include "header.php"; ?><b>Upcoming fanlistings:</b><?php
$status = 'upcoming';
$hide_dropdown = false;
$show_list = true;
$show = 'all';
include ENTH_PATH . 'show_owned.php';
include "footer.php";