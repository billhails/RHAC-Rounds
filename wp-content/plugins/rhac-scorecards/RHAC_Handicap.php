<?php
/*
 * classes to calculate handicaps
 */

/**
 * Class RHAC_HC_Zone
 */
class RHAC_HC_Zone {
    private $diameter;
    private $arrow_radius;
    private $sigma_r_squared;

    /**
     * RHAC_HC_Zone constructor.
     * @param float $diameter
     * @param float $arrow_radius
     * @param float $sigma_r_squared
     */
    public function __construct($diameter, $arrow_radius, $sigma_r_squared) {
        $this->diameter = $diameter;
        $this->arrow_radius = $arrow_radius;
        $this->sigma_r_squared = $sigma_r_squared;
    }

    /**
     * @param float $lower
     * @param float $upper
     * @param float $div
     * @return float
     */
    public function sum ($lower, $upper, $div) {
        $sum = 0.0;
        while ($lower <= $upper) {
            $sum += $this->calc($lower, $div);
            ++$lower;
        }
        return $sum;
    }

    /**
     * @param float $x
     * @return float
     */
    private function square($x) {
        return $x * $x;
    }

    /**
     * @param float $band
     * @param float $div
     * @return float
     */
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

/**
 * Class RHAC_Handicap
 */
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

    /**
     * RHAC_Handicap constructor.
     * @param $handicap
     * @param $units
     * @param $distances
     * @param $arrow_radius
     */
    public function __construct($handicap, $units, $distances, $arrow_radius) {
        $this->conversion = ($units == "metric" ? 1.0 : 0.9144);
        $this->distances = $distances;
        $this->arrow_radius = $arrow_radius;
        $this->sigma_theta = pow(1.036, $handicap +  12.9) * 5E-4;
        $this->K = 1.429E-6 * pow(1.07, $handicap + 4.3);
    }

    /**
     * @param float $x
     * @return float
     */
    protected function square($x) {
        return $x * $x;
    }

    /**
     * @param float $range
     * @return float
     */
    protected function F($range) {
        return 1 + $this->K * $range * $range;
    }

    /**
     * @param float $range
     * @return float
     */
    protected function sigma_r($range) {
        return 100 * $range * $this->sigma_theta * $this->F($range);
    }

    /**
     * @param float $range
     * @return float
     */
    protected function sigma_r_squared($range) {
        return $this->square($this->sigma_r($range));
    }

    /**
     * @param float $diameter
     * @param float $range
     * @return int
     */
    abstract protected function calc($diameter, $range);

    /**
     * @return float
     */
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

    /**
     * @param string $scoring
     * @param int $handicap
     * @param string $units
     * @param array $distances
     * @param float $arrow_radius
     * @return RHAC_Handicap
     */
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

/**
 * Class RHAC_Handicap_Imperial
 */
class RHAC_Handicap_Imperial extends RHAC_Handicap { # tested
    /**
     * @param float $diameter
     * @param float $range
     * @return int
     */
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 9 - 2 * $zone->sum(1, 4, 10) - $zone->calc(1, 2);
    }
}

/**
 * Class RHAC_Handicap_Metric
 */
class RHAC_Handicap_Metric extends RHAC_Handicap { # tested
    /**
     * @param float $diameter
     * @param float $range
     * @return int
     */
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 10 - $zone->sum(1, 10, 20);
    }
}

/**
 * Class RHAC_Handicap_MetricInnerTen
 */
class RHAC_Handicap_MetricInnerTen extends RHAC_Handicap { # tested
    /**
     * @param float $diameter
     * @param float $range
     * @return int
     */
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 10 - $zone->calc(1, 40) - $zone->sum(2, 10, 20);
    }
}

/**
 * Class RHAC_Handicap_Vegas
 */
class RHAC_Handicap_Vegas extends RHAC_Handicap { # tested
    /**
     * @param float $diameter
     * @param float $range
     * @return int
     */
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 10 - $zone->sum(1, 4, 20) - 6 * $zone->calc(5, 20);
    }
}

/**
 * Class RHAC_Handicap_VegasInnerTen
 */
class RHAC_Handicap_VegasInnerTen extends RHAC_Handicap { # tested
    /**
     * @param float $diameter
     * @param float $range
     * @return int
     */
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 10 - $zone->calc(1, 40)
            - $zone->sum(2, 4, 20)
            - 6 * $zone->calc(5, 20);
    }
}

/**
 * Class RHAC_Handicap_Worcester
 */
class RHAC_Handicap_Worcester extends RHAC_Handicap { # tested
    /**
     * @param float $diameter
     * @param float $range
     * @return int
     */
    protected function calc($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 5 - $zone->sum(1, 5, 10);
    }
}

/**
 * Class RHAC_Handicap_FitaSixZone
 */
class RHAC_Handicap_FitaSixZone  extends RHAC_Handicap {
    /**
     * @param float $diameter
     * @param float $range
     * @return int
     */
    protected function calc ($diameter, $range) {
        $sigma_r_squared = $this->sigma_r_squared($range);
        $zone =
            new RHAC_HC_Zone($diameter, $this->arrow_radius, $sigma_r_squared);
        return 10 - $zone->sum(1, 5, 20) - 5 * $zone->calc(6, 20);
    }
}
