<?php

class RHAC_PersonalBestAccumulator {
    private $max = 0;
    private $current_db_values = array();
    private $proposed_changes = array();
    private $unbeaten_records = array();

    private static $instances = array();

    public static function callAccumulator($row) {
        self::getInstance($row)->accept($row);
    }

    private static function getInstance($row) {
        $key = implode("\e", array($row['archer'], $row['bow'], $row['round']));
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
            $this->handleNewPB($row);
        }
        elseif ($row['score'] == $this->max) {
            $this->handleEqualPB($row);
        }
        elseif ($row['personal_best'] != 'N') {
            $this->handleInaccurateRecord($row);
        }
    }

    public function results() {
        $changes = array();
        foreach ($this->proposed_changes as $scorecard_id => $value) {
            if ($this->current_db_values[$scorecard_id] != $value) {
                $changes[$scorecard_id] = array('personal_best' => $value);
            }
        }
        return $changes;
    }

    private function handleNewPB($row) {
        $this->max = $row['score'];
        $this->current_db_values[$row['scorecard_id']] = $row['personal_best'];
        foreach ($this->unbeaten_records as $scorecard_id) {
            $this->proposed_changes[$scorecard_id] = 'N';
        }
        $this->proposed_changes[$row['scorecard_id']] = 'Y';
        $this->unbeaten_records = array($row['scorecard_id']);
    }

    private function handleEqualPB($row) {
        $this->current_db_values[$row['scorecard_id']] = $row['personal_best'];
        $this->proposed_changes[$row['scorecard_id']] = 'Y';
        $this->unbeaten_records []= $row['scorecard_id'];
    }

    private function handleInaccuratePB($row) {
        $this->current_db_values[$row['scorecard_id']] = $row['personal_best'];
        $this->proposed_changes[$row['scorecard_id']] = 'N';
    }

}
