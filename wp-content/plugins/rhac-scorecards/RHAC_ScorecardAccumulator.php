<?php

/**
 * This is the top-level accumulator
 *
 * @see README.md in this directory for an overview of how this all works
 */

 /*
  * this is an array of classes to be loaded and instantiated
  */
$rhac_scorecard_accumulator_manifest = array(
    'RHAC_ClubRecordAccumulator',
    'RHAC_PersonalBestAccumulator',
    'RHAC_HandicapImprovementAccumulator',
    'RHAC_NewClassificationAccumulator',
    'RHAC_252Accumulator',
);

/**
 * load the manifest
 */
foreach ($rhac_scorecard_accumulator_manifest as $accumulator) {
    include_once plugin_dir_path(__FILE__) . $accumulator . '.php';
}

/**********************************
 * the top-level accumulator class
 *
 */
class RHAC_ScorecardAccumulator {

    /**
     * @var array $children the specific accumulator instances
     */
    private $children = array();

    /**
     * This constructor populates the children array from the manifest.
     */
    public function __construct() {
        global $rhac_scorecard_accumulator_manifest;
        foreach ($rhac_scorecard_accumulator_manifest as $class) {
            $this->children []= new $class();
        }
    }

    /**
     * create a key from a database row and an array of significant fields
     *
     * The key is used to store a specific accumulator leaf instance
     *
     * @param array $row a row from the scorecard table
     * @param array $fields a list of significant fields in the row
     *
     * @return string
     */
    protected function makeKey($row, $fields) {
        $key = array();
        foreach ($fields as $field) {
            $key []= $row[$field];
        }
        return implode("\e", $key);
    }

    /**
     * accept the next row from the scorecard table and dispatch it to the cild accumulators
     *
     * @param array $row a row from the scorecard table
     */
    public function accept($row) {
        foreach ($this->getChildren() as $child) {
            $child->accept($row);
        }
    }

    /**
     * return the results (recommendations) from the leaves
     * as an associative array keyed on scorecard id.
     *
     * @return array
     */
    public function results() {
        $results = array();
        foreach ($this->getAllLeaves() as $accumulator) {
            $results = $this->mergeReccomendations($results, $accumulator->results());
        }
        return $results;
    }

    /**
     * returns a flattened array of all the accumulator leaves
     *
     * @return array|RHAC_AccumulatorLeaf[]
     */
    protected function getAllLeaves() {
        $result = array();
        foreach ($this->getChildren() as $child) {
            $result = array_merge($result, $child->getAllLeaves());
        }
        return $result;
    }

    /**
     * return the children
     */
    protected function getChildren() {
        return $this->children;
    }

    /**
     * merge two nested hashes on the outer id
     *
     * for example [a => [b => c]] and [a => [d => e]] should produce [a => [b => c, d => e]]
     *
     * it is a fatal error if inner keys are duplicate (i.e. differing recommendtions for the same field)
     *
     * @param array $hash1
     * @param array $hash2
     *
     * @return array
     */
    private function mergeReccomendations($hash1, $hash2) {
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

/*********************************************
 * parent class for all the accumulator leaves
 */
abstract class RHAC_AccumulatorLeaf {
    /**
     * returns this leaf, in an array
     *
     * @return array
     */
    public function getAllLeaves() {
        return array($this);
    }

    /**
     * returns the recommendations from this accumulator leaf, keyed on scorecard id
     *
     * note that it compares the recommended value with the current value and discards
     * the recommendation if it is already the same.
     *
     * @return array
     */
    public function results() {
        $changes = array();
        foreach ($this->proposed_changes as $scorecard_id => $value) {
            if ($this->current_db_values[$scorecard_id] != $value) {
                $changes[$scorecard_id] = array($this->keyToChange() => $value);
            }
        }
        return $changes;
    }

    /**
     * each accumulator leaf can only change one field, this returns the name of the field
     *
     * @return string
     */
    abstract protected function keyToChange();
}
