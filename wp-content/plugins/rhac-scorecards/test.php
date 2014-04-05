<?php
require_once 'RHAC_Handicap.php';

$tests = array(

    array(
        "round" => "national",
        "distances" => array(
            array("D" => 122, "R" => 60, "N" => 48),
            array("D" => 122, "R" => 50, "N" => 24),
        ),
        "units" => "imperial",
        "scoring" => "five zone",
        "tests" => array(
            array( "handicap" => 100, "expected" => 2),
            array( "handicap" => 50, "expected" => 436),
            array( "handicap" => 0, "expected" => 648),
        )
    ),

    array(
        "round" => "white 252",
        "distances" => array(
            array("D" => 122, "R" => 20, "N" => 36)
        ),
        "units" => "imperial",
        "scoring" => "five zone",
        "tests" => array(
            array( "handicap" => 100, "expected" => 65),
            array( "handicap" => 50, "expected" => 315),
            array( "handicap" => 0, "expected" => 324),
        )
    ),

    array(
        "round" => "gold 252",
        "distances" => array(
            array("D" => 122, "R" => 100, "N" => 36)
        ),
        "units" => "imperial",
        "scoring" => "five zone",
        "tests" => array(
            array( "handicap" => 100, "expected" => 0),
            array( "handicap" => 50, "expected" => 91),
            array( "handicap" => 0, "expected" => 320),
        )
    ),

    array(
        "round" => "frostbite",
        "distances" => array(
            array("D" => 80, "R" => 30, "N" => 36)
        ),
        "units" => "metric",
        "scoring" => "ten zone",
        "tests" => array(
            array( "handicap" => 100, "expected" => 5),
            array( "handicap" => 50, "expected" => 265),
            array( "handicap" => 0, "expected" => 359),
        )
    ),

    array(
        "round" => "metric i",
        "distances" => array(
            array("D" => 122, "R" => 70, "N" => 36),
            array("D" => 122, "R" => 60, "N" => 36),
            array("D" => 80, "R" => 50, "N" => 36),
            array("D" => 80, "R" => 30, "N" => 36),
        ),
        "units" => "metric",
        "scoring" => "ten zone",
        "tests" => array(
            array( "handicap" => 100, "expected" => 7),
            array( "handicap" => 50, "expected" => 817),
            array( "handicap" => 0, "expected" => 1412),
        )
    ),

    array(
        "round" => "worcester",
        "distances" => array(
            array("D" => 40.64, "R" => 20, "N" => 60),
        ),
        "units" => "imperial",
        "scoring" => "worcester",
        "tests" => array(
            array( "handicap" => 100, "expected" => 9),
            array( "handicap" => 50, "expected" => 222),
            array( "handicap" => 0, "expected" => 300),
        )
    ),

    array(
        "round" => "plymouth",
        "distances" => array(
            array("D" => 40, "R" => 15, "N" => 60),
        ),
        "units" => "imperial",
        "scoring" => "ten zone",
        "tests" => array(
            array( "handicap" => 100, "expected" => 39),
            array( "handicap" => 50, "expected" => 470),
            array( "handicap" => 0, "expected" => 599),
        )
    ),

    array(
        "round" => "plymouth (triple)",
        "distances" => array(
            array("D" => 40, "R" => 15, "N" => 60),
        ),
        "units" => "imperial",
        "scoring" => "vegas",
        "tests" => array(
            array( "handicap" => 100, "expected" => 19),
            array( "handicap" => 50, "expected" => 450),
            array( "handicap" => 0, "expected" => 599),
        )
    ),

    array(
        "round" => "plymouth inner ten",
        "distances" => array(
            array("D" => 40, "R" => 15, "N" => 60),
        ),
        "units" => "imperial",
        "scoring" => "metric inner ten",
        "tests" => array(
            array( "handicap" => 100, "expected" => 39),
            array( "handicap" => 50, "expected" => 465),
            array( "handicap" => 0, "expected" => 588),
        )
    ),

    array(
        "round" => "plymouth (triple) inner ten",
        "distances" => array(
            array("D" => 40, "R" => 15, "N" => 60),
        ),
        "units" => "imperial",
        "scoring" => "vegas inner ten",
        "tests" => array(
            array( "handicap" => 100, "expected" => 19),
            array( "handicap" => 50, "expected" => 445),
            array( "handicap" => 0, "expected" => 588),
        )
    ),

);

function run_one_test($test) {
    foreach ($test["tests"] as $testcase) {
        $hc =  RHAC_Handicap::getCalculator($test['scoring'],
                                            $testcase["handicap"],
                                            $test["units"],
                                            $test["distances"],
                                            0.357);
        $result = $hc->predict();
        if ($result == $testcase["expected"]) {
            print "$test[round] $testcase[handicap] [OK]\n";
        }
        else {
            print "$test[round] $testcase[handicap] [NOK] got $result expected $testcase[expected]\n";
        }
    }
}

foreach ($tests as $test) {
    run_one_test($test);
}
