<?php
if (basename($_SERVER['SCRIPT_FILENAME']) == 'header.php') {
    die();
}

include_once 'config.php';
require_once(ENTH_PATH . 'get_full_info.php');
require_once(ENTH_PATH . 'show_collective_stats.php');

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>Collective</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
Sample Collective
<br/><br/>

<div style="text-align: center; margin: auto; font-weight: bold;">
    <a href="index.php">Index</a> &bull;
    <a href="joined.php">Joined</a> &bull;
    <a href="current.php">Current</a> &bull;
    <a href="upcoming.php">Upcoming</a> &bull;
    <a href="pending.php">Pending</a> &bull;
    <a href="affiliates.php">Affiliates</a>
</div>