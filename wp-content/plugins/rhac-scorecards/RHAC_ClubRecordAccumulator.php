<?php

class RHAC_ClubRecordAccumulator {
    private $max = 0;
    private $current_db_values = array();
    private $proposed_changes = array();
    private $unbeaten_records = array();

    private static $instances = array();

    public static function callAccumulator($row) {
        self::getInstance($row)->accept($row);
    }

    private static function getInstance($row) {
        $key = implode("\e", array($row['bow'], $row['category'], $row['gender'], $row['round']));
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self();
        }
        return self::$instances[$key];
    }

    public static function getAllInstances() {
        return array_values(self::$instances);
    }

    public function accept($row) {
        if ($row['score'] > $this->max) {
            $this->handleNewRecord($row);
        }
        elseif ($row['score'] == $this->max) {
            $this->handleEqualRecord($row);
        }
        elseif ($row['club_record'] != 'N') {
            $this->handleInaccurateRecord($row);
        }
    }

    public function results() {
        $changes = array();
        foreach ($this->proposed_changes as $scorecard_id => $value) {
            if ($this->current_db_values[$scorecard_id] != $value) {
                $changes[$scorecard_id] = array('club_record' => $value);
            }
        }
        return $changes;
    }

    private function handleNewRecord($row) {
        $this->max = $row['score'];
        $this->current_db_values[$row['scorecard_id']] = $row['club_record'];
        foreach ($this->unbeaten_records as $scorecard_id) {
            $this->proposed_changes[$scorecard_id] = 'old';
        }
        $this->proposed_changes[$row['scorecard_id']] = 'current';
        $this->unbeaten_records = array($row['scorecard_id']);
    }

    private function handleEqualRecord($row) {
        $this->current_db_values[$row['scorecard_id']] = $row['club_record'];
        $this->proposed_changes[$row['scorecard_id']] = 'current';
        $this->unbeaten_records []= $row['scorecard_id'];
    }

    private function handleInaccurateRecord($row) {
        $this->current_db_values[$row['scorecard_id']] = $row['club_record'];
        $this->proposed_changes[$row['scorecard_id']] = 'N';
    }

}
