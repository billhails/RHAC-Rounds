#! /usr/bin/php
<?php

include "rounds.php";

function plugin_dir_path() { return "./";  }
// $_POST["gnas-submit"] = "true";
// $bow = 'recurve';
// $gender = 'F';
// $age_group = 'U18';
// $standard = 'third';
// $round = 'York';
// $_POST[implode("_", array('gnas-value', $bow, $gender, $age_group, $standard, $round))] = 75;

// $_GET['round'] = 'York';
// // $_GET['gender'] = 'F';

// print GNAS_Page::asText();
$_POST['gnas-value_barebow_F_U18_bm_Long_Metric_I'] = '256';
$_POST['gnas-value_barebow_F_U18_first_Long_Metric_I'] = '128';
$_POST['gnas-submit'] = 'true';
print GNAS_Page::outdoorTable(23);

print '</body></html>';
