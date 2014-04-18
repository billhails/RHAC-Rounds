<?php

class RHAC_ClubRecordAccumulator extends RHAC_ScorecardAccumulator {
    private $children;

    public function __construct() {
        $this->children = array();
    }

    public function accept($row) {
        $key = implode("\e", array($row['bow'], $row['category'], $row['gender'], $row['round']));
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_ClubRecordAccumulatorLeaf();
        }
        $this->children[$key]->accept($row);
    }

    protected function getChildren() {
        return array_values($this->children);
    }
}

class RHAC_ClubRecordAccumulatorLeaf extends RHAC_AccumulatorLeaf {
    private $max = 0;
    protected $current_db_values = array();
    protected $proposed_changes = array();
    private $unbeaten_records = array();

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

    protected function keyToChange() {
        return 'club_record';
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
