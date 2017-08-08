<?php
/*
 * classes to calculate handicaps
 */

class RHAC_HC_Zone {
    private $diameter;
    private $arrow_radius;
    private $sigma_r_squared;

    public function __construct($diameter, $arrow_radius, $sigma_r_squared) {
        $this->diameter = $diameter;
        $this->arrow_radius = $arrow_radius;
        $this->sigma_r_squared = $sigma_r_squared;
    }

    public function sum ($lower, $upper, $div) {
        $sum = 0.0;
        while ($lower <= $upper) {
            $sum += $this->calc($lower, $div);
            ++$lower;
        }
        return $sum;
    }

    private function square($x) {
        return $x * $x;
    }

    public function calc($band, $div) {
        $diameter = $this->diameter;
        $sigma_r_squared = $this->sigma_r_squared;
        $arrow_radius = $this->arrow_radius;

        $result = exp(
            - $this->square($band * $diameter / $div + $arrow_radius)
            / $sigma_r_squared
        );

        return $result;
    }
}

abstract class RHAC_Handicap {

    private $distances;
    protected $arrow_radius;
    private $sigma_theta;
    private $K;
    private $conversion;
    private static $converters = array(
        'five zone' => 'RHAC_Handicap_Imperial',
        'ten zone' => 'RHAC_Handicap_Metric',
        'metric inner ten' => 'RHAC_Handicap_MetricInnerTen',
        'vegas' => 'RHAC_Handicap_Vegas',
        'vegas inner ten' => 'RHAC_Handicap_VegasInnerTen',
        'worcester' => 'RHAC_Handicap_Worcester',
        'fita six zone' => 'RHAC_Handicap_FitaSixZone',
    );

    public function __construct($handicap, $units, $distances, $arrow_radius) {
        $this->conversion = ($units == "metric" ? 1.0 : 0.9144);
        $this->distances = $distances;
        $this->arrow_radius = $arrow_radius;
        $this->sigma_theta = pow(1.036, $handicap +  12.9) * 5E-4;
        $this->K = 1.429E-6 * pow(1.07, $handicap + 4.3);
    }

    protected function square($x) {
        return $x * $x;
    }

    protected function F($range) {
        return 1 + $this->K * $range * $range;
    }

    protected function sigma_r($range) {
        return 100 * $range * $this->sigma_theta * $this->F($range);
    }

    protected function sigma_r_squared($range) {
        return $this->square($this->sigma_r($range));
    }

    abstract protected function calc($diameter, $range);

    public function predict() {
        $total = 0.0;
        foreach ($this->distances as $distance) {
            $total += $distance["N"]
                    * $this->calc(
                        $distance["D"],
                        $distance["R"] * $this->conversion
                    );
        }
        return round($total);
    }

    public static function getCalculator(
        $scoring,
        $handicap,
        $units,
        $distances,
        $arrow_radius
    ) {
        $class = self::$converters[$scoring];
        if ($class) {
            return new $class($handicap, $units, $distances, $arrow_radius);
        }
        else {
            die("no class available for scoring [$scoring]\n");
        }
    }
}

class RHAC_Handicap_Imperial extends RHAC_Handicap { # tested
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 9 - 2 * $zone->sum(1, 4, 10) - $zone->calc(1, 2);
    }
}

class RHAC_Handicap_Metric extends RHAC_Handicap { # tested
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 10 - $zone->sum(1, 10, 20);
    }
}

class RHAC_Handicap_MetricInnerTen extends RHAC_Handicap { # tested
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 10 - $zone->calc(1, 40) - $zone->sum(2, 10, 20);
    }
}

class RHAC_Handicap_Vegas extends RHAC_Handicap { # tested
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 10 - $zone->sum(1, 4, 20) - 6 * $zone->calc(5, 20);
    }
}

class RHAC_Handicap_VegasInnerTen extends RHAC_Handicap { # tested
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 10 - $zone->calc(1, 40)
            - $zone->sum(2, 4, 20)
            - 6 * $zone->calc(5, 20);
    }
}

class RHAC_Handicap_Worcester extends RHAC_Handicap { # tested
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 5 - $zone->sum(1, 5, 10);
    }
}

class RHAC_Handicap_FitaSixZone  extends RHAC_Handicap {
    protected function calc ($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 10 - $zone->sum(1, 5, 20) - 5 * $zone->calc(6, 20);
    }
}
