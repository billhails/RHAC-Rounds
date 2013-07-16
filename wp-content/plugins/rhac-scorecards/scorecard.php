<?php
# A JSON handler for individual scorecards

define('RHAC_PLUGIN_DIR', plugin_dir_path(__FILE__));

include_once (RHAC_PLUGIN_DIR . 'toplevel.php');

if ($_GET['id']) {
    header('Content-Type: application/json; charset=utf-8');
    print RHAC_Scorecards::getInstance()->scorecardJSON($_GET['id']);
}
