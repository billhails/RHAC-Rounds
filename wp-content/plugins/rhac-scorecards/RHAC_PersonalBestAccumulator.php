<?php

class RHAC_PersonalBestAccumulator extends RHAC_ScorecardAccumulator {
    private $children;

    public function __construct() {
        $this->children = array();
    }

    // override
    public function accept($row) {
        $key = implode("\e", array($row['archer'], $row['bow'], $row['round']));
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_PersonalBestAccumulatorLeaf();
        }
        $this->children[$key]->accept($row);
    }

    // override
    protected function getChildren() {
        return array_values($this->children);
    }

}

class RHAC_PersonalBestAccumulatorLeaf extends RHAC_AccumulatorLeaf {
    private $max = 0;
    protected $current_db_values = array();
    protected $proposed_changes = array();
    private $unbeaten_records = array();

    public function accept($row) {
        if ($this->isReassessment($row)) {
            return;
        }
        if ($row['score'] > $this->max) {
            $this->handleNewPB($row);
        }
        elseif ($row['score'] == $this->max) {
            $this->handleEqualPB($row);
        }
        elseif ($row['personal_best'] != 'N') {
            $this->handleInaccuratePB($row);
        }
    }

    private function isReassessment($row) {
        return $row['reassessment'] != 'N';
    }

    protected function keyToChange() {
        return 'personal_best';
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
