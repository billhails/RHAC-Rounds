<?php

/**
 * Class RHAC_ReassesmentInserter
 */
class RHAC_ReassesmentInserter {

    private static $children = array();
    private $age_helper;

    /**
     * RHAC_ReassesmentInserter constructor.
     * @param $archer_map
     */
    public function __construct($archer_map) {
        $this->age_helper = new RHAC_AgeHelper($archer_map);
    }

    /**
     * @param $row
     */
    public function accept($row) {
        $key = implode("\e", array($row['bow'], $row['archer'], $row['outdoor']));
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_ReassesmentInserterLeaf($row, $this->age_helper);
        }
        $this->children[$key]->accept($row);
    }

    /**
     * @return array
     */
    public function results() {
        $results = array();
        foreach ($this->children as $child) {
            $results = array_merge($results, $child->results());
        }
        return $results;
    }

}

/**
 * Class RHAC_ReassesmentInserterLeaf
 */
class RHAC_ReassesmentInserterLeaf {

    private $age_change_suggestions = array();
    private $age_change_seen = array();
    private $end_of_season_suggestions = array();
    private $end_of_season_seen = array();
    private $archer;
    private $bow;
    private $outdoor;
    private $gender;
    private $season_calculator;
    private $age_helper;

    /**
     * RHAC_ReassesmentInserterLeaf constructor.
     * @param $row
     * @param $age_helper
     */
    public function __construct($row, $age_helper) {
        $this->bow = $row['bow'];
        $this->archer = $row['archer'];
        $this->outdoor = $row['outdoor'];
        $this->gender = $row['gender'];
        if ($this->outdoor == "Y") {
            $this->season_calculator = RHAC_NextOutdoorSeasonCalculator::getInstance();
        }
        else {
            $this->season_calculator = RHAC_NextIndoorSeasonCalculator::getInstance();
        }
        $this->age_helper = $age_helper;
    }

    # outdoor, archer, bow
    public function accept($row) {
        switch ($row['reassessment']) {
            case 'N':
                $this->suggestReasessments($row);
                break;
            case 'end_of_season':
                $this->noteSeenEndOfSeason($row);
                break;
            case 'age_group':
                $this->noteSeenAgeChange($row);
                break;
        }
    }

    /**
     * @param $row
     */
    private function suggestReasessments($row) {
        if ($row['guest'] == 'Y') {
            return;
        }
        $this->end_of_season_suggestions[$this->nextSeason($row['date'])] = 1;
        if ($row['category'] != 'adult') {
            $this->age_change_suggestions[$this->nextAgeChange($row['date'])] = 1;
        }
    }

    /**
     * @param $date
     * @return mixed
     */
    private function nextAgeChange($date) {
        return $this->age_helper->dateOfNextAgeGroupChange($this->archer, $date);
    }

    /**
     * @param $row
     */
    private function noteSeenEndOfSeason($row) {
        $this->end_of_season_seen[$row['date']] = $row['scorecard_id'];
    }

    /**
     * @param $row
     */
    private function noteSeenAgeChange($row) {
        $this->age_change_seen[$row['date']] = $row['scorecard_id'];
    }

    /**
     * @return array
     */
    public function results() {
        return array_merge($this->ageChangeResults(), $this->endOfSeasonResults());
    }

    /**
     * @return array
     */
    private function ageChangeResults() {
        return $this->changeResults($this->age_change_suggestions, $this->age_change_seen, 'age_group');
    }

    /**
     * @return array
     */
    private function endOfSeasonResults() {
        return $this->changeResults($this->end_of_season_suggestions, $this->end_of_season_seen,
                                                                                'end_of_season');
    }

    /**
     * @param $suggestions
     * @param $seen
     * @param $reassessment
     * @return array
     */
    private function changeResults($suggestions, $seen, $reassessment) {
        $results = array();
        $now = date('Y/m/d');
        foreach(array_keys($suggestions) as $date) {
            if ($date > $now) {
                unset($suggestions[$date]);
            }
        }
        foreach ($seen as $date => $scorecard_id) {
            if (!isset($suggestions[$date])) {
                $results []= $this->makeDelete($scorecard_id);
            }
        }
        foreach (array_keys($suggestions) as $date) {
            if (!isset($seen[$date])) {
                $results []= $this->makeInsert($date, $reassessment);
            }
        }
        return $results;
    }

    /**
     * @param $date
     * @param $reassessment
     * @return array
     */
    private function makeInsert($date, $reassessment) {
        return array('action' => 'insert',
                     'date' => $date,
                     'archer' => $this->archer,
                     'bow' => $this->bow,
                     'outdoor' => $this->outdoor,
                     'gender' => $this->gender,
                     'category' => $this->categoryAt($date),
                     'reassessment' => $reassessment);
    }

    /**
     * @param $scorecard_id
     * @return array
     */
    private function makeDelete($scorecard_id) {
        return array('action' => 'delete', 'scorecard_id' => $scorecard_id);
    }

    /**
     * @param $date
     * @return mixed
     */
    private function categoryAt($date) {
        return $this->age_helper->categoryAt($this->archer, $date);
    }

    /**
     * @param $date
     * @return string
     */
    protected function nextSeason($date) {
        return $this->season_calculator->nextSeason($date);
    }
}

/**
 * Class RHAC_NextOutdoorSeasonCalculator
 */
class RHAC_NextOutdoorSeasonCalculator {
    private static $instance;

    /**
     * RHAC_NextOutdoorSeasonCalculator constructor.
     */
    private function __construct() {}

    /**
     * @return RHAC_NextOutdoorSeasonCalculator
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $date
     * @return string
     */
    public function nextSeason($date) {
        $year = substr($date, 0, 4);
        return sprintf('%04d/01/01', $year + 1);
    }
}

/**
 * Class RHAC_NextIndoorSeasonCalculator
 */
class RHAC_NextIndoorSeasonCalculator {
    private static $instance;

    /**
     * RHAC_NextIndoorSeasonCalculator constructor.
     */
    private function __construct() {}

    /**
     * @return RHAC_NextIndoorSeasonCalculator
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param $date
     * @return string
     */
    public function nextSeason($date) {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $day = substr($date, 8, 2);
        if ($month < 7) {
            return sprintf('%04d/07/01', $year);
        }
        return sprintf('%04d/07/01', $year + 1);
    }
}

/**
 * Class RHAC_AgeHelper
 */
class RHAC_AgeHelper {

    /**
     * @var
     */
    private $archer_map;

    /**
     * RHAC_AgeHelper constructor.
     * @param $archer_map
     */
    public function __construct($archer_map) {
        $this->archer_map = $archer_map;
    }

    /**
     * @param $archer
     * @param $date
     * @return string
     */
    public function dateOfNextAgeGroupChange($archer, $date) {
        $category = $this->categoryAt($archer, $date);
        if ($category != 'adult') {
            $dob = $this->getDoB($archer);
            $age = $this->ageAt($archer, $date);

            do {
                $age++;
                $new_category = $this->categoryAtAge($age);
            } while ($category == $new_category);
            return $this->addYears($dob, $age);
        }
    }

    /**
     * @param $date
     * @param $years
     * @return string
     */
    private function addYears($date, $years) {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $day = substr($date, 8, 2);
        return sprintf('%04d/%02d/%02d', $year + $years, $month, $day);
    }

    /**
     * @param $archer
     * @param $date_string
     * @return string
     */
    public function categoryAt($archer, $date_string) {
        $age = $this->ageAt($archer, $date_string);
        return $this->categoryAtAge($age);
    }

    /**
     * @param $age
     * @return string
     */
    private function categoryAtAge($age) {
        if ($age < 12) {
            return 'U12';
        }
        else if ($age < 14) {
            return 'U14';
        }
        else if ($age < 16) {
            return 'U16';
        }
        else if ($age < 18) {
            return 'U18';
        }
        else {
            return 'adult';
        }
    }

    /**
     * @param $archer
     * @param $date_string
     * @return float
     */
    private function ageAt($archer, $date_string) {
        $dob_string = $this->getDoB($archer);
        $dob = $this->unixdate($dob_string);
        $date = $this->unixdate($date_string);
        $diff = $date - $dob;
        return floor($diff / (60 * 60 * 24 * 365.242));
    }

    /**
     * @param $date_string
     * @return false|int
     */
    private function unixdate($date_string) {
        $y = substr($date_string, 0, 4) + 0;
        if ($y < 1970) {
            $y = 1970;
        }
        $m = substr($date_string, 5, 2) + 0;
        $d = substr($date_string, 8, 2) + 0;
        return mktime(0, 0, 0, $m, $d, $y, 0);
    }

    /**
     * @param $archer
     * @return mixed
     */
    private function getDoB($archer) {
        return $this->archer_map[$archer]['date_of_birth'];
    }
}
