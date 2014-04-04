<?php
require_once 'RHAC_Handicap.php';

$frostbite = array(
    array("D" => 80, "R" => 30, "N" => 36)
);

for ($handicap = 0; $handicap <= 100; ++$handicap) {
    $hc_imperial = new RHAC_Handicap_Metric($handicap, "metric", $frostbite, 0.357);
    print "$handicap: " . $hc_imperial->predict() . "\n";
}
