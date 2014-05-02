<?php

/**
 * This is the top-level accumulator
 */
$rhac_scorecard_accumulator_manifest = array(
    'RHAC_ClubRecordAccumulator',
    'RHAC_PersonalBestAccumulator',
    'RHAC_HandicapImprovementAccumulator',
    'RHAC_NewClassificationAccumulator',
    'RHAC_252Accumulator',
);

foreach ($rhac_scorecard_accumulator_manifest as $accumulator) {
    include_once plugin_dir_path(__FILE__) . $accumulator . '.php';
}

class RHAC_ScorecardAccumulator {

    private $children = array();

    public function __construct() {
        global $rhac_scorecard_accumulator_manifest;
        foreach ($rhac_scorecard_accumulator_manifest as $class) {
            $this->children []= new $class();
        }
    }

    protected function makeKey($row, $fields) {
        $key = array();
        foreach ($fields as $field) {
            $key []= $row[$field];
        }
        return implode("\e", $key);
    }

    public function accept($row) {
        foreach ($this->getChildren() as $child) {
            $child->accept($row);
        }
    }

    public function results() {
        $results = array();
        foreach ($this->getAllLeaves() as $accumulator) {
            $results = $this->mergeHashes($results, $accumulator->results());
        }
        return $results;
    }

    protected function getAllLeaves() {
        $result = array();
        foreach ($this->getChildren() as $child) {
            $result = array_merge($result, $child->getAllLeaves());
        }
        return $result;
    }

    protected function getChildren() {
        return $this->children;
    }

    private function mergeHashes($hash1, $hash2) {
        foreach ($hash2 as $scorecard_id => $changes) {
            if (isset($hash1[$scorecard_id])) {
                foreach ($changes as $field => $value) {
                    if (isset($hash1[$scorecard_id][$field])) {
                        die("conflicting updates on score #$scorecard_id $field => '"
                        . $hash1[$scorecard_id][$field]
                        . "' vs '"
                        . $hash2[$scorecard_id][$field]
                        . "'");
                    }
                    else {
                        $hash1[$scorecard_id][$field] = $value;
                    }
                }
            }
            else {
                $hash1[$scorecard_id] = $changes;
            }
        }
        return $hash1;
    }

}

abstract class RHAC_AccumulatorLeaf {
    public function getAllLeaves() {
        return array($this);
    }

    public function results() {
        $changes = array();
        foreach ($this->proposed_changes as $scorecard_id => $value) {
            if ($this->current_db_values[$scorecard_id] != $value) {
                $changes[$scorecard_id] = array($this->keyToChange() => $value);
            }
        }
        return $changes;
    }

    abstract protected function keyToChange();
}
