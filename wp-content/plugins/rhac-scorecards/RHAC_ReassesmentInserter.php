<?php

class RHAC_ReassesmentInserter {

    private static $children = array();
    private $age_helper;

    public function __construct($archer_map) {
        $this->age_helper = new RHAC_AgeHelper($archer_map);
    }

    public function accept($row) {
        $key = implode("\e", array($row['bow'], $row['archer'], $row['outdoor']));
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_ReassesmentInserterLeaf($row, $this->age_helper);
        }
        $this->children[$key]->accept($row);
    }

    public function results() {
        $results = array();
        foreach ($this->children as $child) {
            $results = array_merge($results, $child->results());
        }
        return $results;
    }

}

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

    private function suggestReasessments($row) {
        $this->end_of_season_suggestions[$this->nextSeason($row['date'])] = 1;
        if ($row['category'] != 'adult') {
            $this->age_change_suggestions[$this->nextAgeChange($row['date'])] = 1;
        }
    }

    private function nextAgeChange($date) {
        return $this->age_helper->dateOfNextAgeGroupChange($this->archer, $date);
    }

    private function noteSeenEndOfSeason($row) {
        $this->end_of_season_seen[$row['date']] = $row['scorecard_id'];
    }

    private function noteSeenAgeChange($row) {
        $this->age_change_seen[$row['date']] = $row['scorecard_id'];
    }

    public function results() {
        return array_merge($this->ageChangeResults(), $this->endOfSeasonResults());
    }

    private function ageChangeResults() {
        return $this->changeResults($this->age_change_suggestions, $this->age_change_seen, 'age_group');
    }

    private function endOfSeasonResults() {
        return $this->changeResults($this->end_of_season_suggestions, $this->end_of_season_seen,
                                                                                'end_of_season');
    }

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

    private function categoryAt($date) {
        return $this->age_helper->categoryAt($this->archer, $date);
    }

    protected function nextSeason($date) {
        return $this->season_calculator->nextSeason($date);
    }
}

class RHAC_NextOutdoorSeasonCalculator {
    private static $instance;

    private function __construct() {}

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function nextSeason($date) {
        $year = substr($date, 0, 4);
        return sprintf('%04d/01/01', $year + 1);
    }
}

class RHAC_NextIndoorSeasonCalculator {
    private static $instance;

    private function __construct() {}

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function nextSeason($date) {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $day = substr($date, 8, 2);
        if ($month < 6) {
            return sprintf('%04d/06/01', $year);
        }
        return sprintf('%04d/06/01', $year + 1);
    }
}

class RHAC_AgeHelper {

    private $archer_map;

    public function __construct($archer_map) {
        $this->archer_map = $archer_map;
    }

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

    private function addYears($date, $years) {
        $year = substr($date, 0, 4);
        $month = substr($date, 5, 2);
        $day = substr($date, 8, 2);
        return sprintf('%04d/%02d/%02d', $year + $years, $month, $day);
    }

    public function categoryAt($archer, $date_string) {
        $age = $this->ageAt($archer, $date_string);
        return $this->categoryAtAge($age);
    }

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

    private function ageAt($archer, $date_string) {
        $dob_string = $this->getDoB($archer);
        $dob = $this->unixdate($dob_string);
        $date = $this->unixdate($date_string);
        $diff = $date - $dob;
        return floor($diff / (60 * 60 * 24 * 365.242));
    }

    private function unixdate($date_string) {
        $y = substr($date_string, 0, 4) + 0;
        if ($y < 1970) {
            $y = 1970;
        }
        $m = substr($date_string, 5, 2) + 0;
        $d = substr($date_string, 8, 2) + 0;
        return mktime(0, 0, 0, $m, $d, $y, 0);
    }

    private function getDoB($archer) {
        return $this->archer_map[$archer]['date_of_birth'];
    }
}
