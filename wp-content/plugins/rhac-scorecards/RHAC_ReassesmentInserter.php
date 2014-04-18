<?php

class RHAC_ReassesmentInserter {

    private $next_season;
    private $suggestions = array();
    private $unexpected = array();
    private $seen = array();
    private $archer;
    private $bow;
    private $outdoor;
    private $season_calculator;

    private static $instances = array();

    public static function call($row) {
        self::getInstance($row)->accept($row);
    }

    private function __construct($row) {
        $this->bow = $row['bow'];
        $this->archer = $row['archer'];
        $this->outdoor = $row['outdoor'];
        if ($outdoor = "Y") {
            $this->season_calculator = new RHAC_NextOutdoorSeasonCalculator();
        }
        else {
            $this->season_calculator = new RHAC_NextIndoorSeasonCalculator();
        }
    }

    private static function getInstance($row) {
        $key = implode("\e", array($row['bow'], $row['archer'], $row['outdoor']));
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($row);
        }
        return self::$instances[$key];
    }

    private static function getAllInstances() {
        return array_values(self::$instances);
    }

    # outdoor, archer, bow
    # TODO verify this logic.
    public function accept($row) {
        if (!isset($this->next_season)) {
            if ($row['end_of_season'] == "Y") {
                $this->noteUnexpectedReasessment($row['date']);
            }
            else {
                $this->next_season = $this->nextSeason($row['date']);
                $this->suggestReasessment($this->next_season);
            }
        }
        else {
            if ($row['date'] >= $this->next_season) {
                $this->next_season = $this->nextSeason($row['date']);
            }
            if ($row['end_of_season'] == "Y") {
                $this->noteReassesmentPresent($row['date']);
            }
            else {
                $this->suggestReasessment($this->next_season);
            }
        }
    }

    private function suggestReasessment($date) {
        $this->suggestions[$date] = 1;
    }

    private function noteUnexpectedReasessment($date) {
        $this->unexpected[$date] = 1;
    }

    private function noteReassesmentPresent($date) {
        $this->seen[$date] = 1;
    }

    public function results() {
        if (isset($this->next_season) && $this->next_season < date('Y/m/d')) {
            $this->suggestReasessment($this->next_season);
        }
        $results = array();
        foreach (array_keys($this->suggestions) as $date) {
            if (!isset($this->seen[$date])) {
                $results []= $this->makeAction('insert', $date);
            }
        }
        foreach (array_keys($this->unexpected) as $date) {
            $results []= $this->makeAction('delete', $date);
        }
        return $results;
    }

    private function makeAction($action, $date) {
        return array('action' => $action,
                     'date' => $date,
                     'archer' => $this->archer,
                     'bow' => $this->bow,
                     'outdoor' => $this->outdoor);
    }

    protected function nextSeason($date) {
        return $this->season_calculator->nextSeason($date);
    }
}

class RHAC_NextOutdoorSeasonCalculator {
    public function nextSeason($date) {
        $year = substr($date, 0, 4);
        return sprintf('%04d/01/01', $year + 1);
    }
}

class RHAC_NextIndoorSeasonCalculator {
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
