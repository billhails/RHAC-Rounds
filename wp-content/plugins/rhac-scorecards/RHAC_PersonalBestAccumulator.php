<?php

/**
 * accumulator for personal best badges, per archer, bow and round
 */
class RHAC_PersonalBestAccumulator extends RHAC_ScorecardAccumulator {
    private $children;

    public function __construct() {
        $this->children = array();
    }

    /**
     * accept the next row from the scorecards table and
     * dispatch to the appropriate leaf accumultor
     *
     * @param array $row
     */
    public function accept($row) {
        $key = $this->makeKey($row, array('archer', 'bow', 'round'));
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_PersonalBestAccumulatorLeaf();
        }
        $this->children[$key]->accept($row);
    }

    /**
     * return the leaf accumulators
     *
     * @return array
     */
    protected function getChildren() {
        return array_values($this->children);
    }

}

/**
 * the leaf accumulator does the actual work
 */
class RHAC_PersonalBestAccumulatorLeaf extends RHAC_AccumulatorLeaf {
    private $max = 0;
    protected $current_db_values = array();
    protected $proposed_changes = array();
    private $unbeaten_records = array();

    /**
     * accept the next relevant row from the scorecard table
     *
     * @param array $row
     */
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

    /**
     * true if the row is a reassessment
     *
     * @param array $row
     *
     * @return bool
     */
    private function isReassessment($row) {
        return $row['reassessment'] != 'N';
    }

    /**
     * the field of the scorecard table that this accumulator
     * affects
     *
     * @return string
     */
    protected function keyToChange() {
        return 'personal_best';
    }

    /**
     * handles a row containing a new PB
     *
     * @param array $row
     */
    private function handleNewPB($row) {
        $this->max = $row['score'];
        $this->current_db_values[$row['scorecard_id']] = $row['personal_best'];
        foreach ($this->unbeaten_records as $scorecard_id) {
            $this->proposed_changes[$scorecard_id] = 'N';
        }
        $this->proposed_changes[$row['scorecard_id']] = 'Y';
        $this->unbeaten_records = array($row['scorecard_id']);
    }

    /**
     * handles a row containing a score equal to the current PB
     *
     * @param array $row
     */
    private function handleEqualPB($row) {
        $this->current_db_values[$row['scorecard_id']] = $row['personal_best'];
        $this->proposed_changes[$row['scorecard_id']] = 'Y';
        $this->unbeaten_records []= $row['scorecard_id'];
    }

    /**
     * handle a row that disagrees with a pb
     */
    private function handleInaccuratePB($row) {
        $this->current_db_values[$row['scorecard_id']] = $row['personal_best'];
        $this->proposed_changes[$row['scorecard_id']] = 'N';
    }

}
