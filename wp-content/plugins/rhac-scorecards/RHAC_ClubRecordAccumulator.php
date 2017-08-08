<?php

/**
 * accumulator for club records, per bow, per age group, per gender, per round
 */
class RHAC_ClubRecordAccumulator extends RHAC_ScorecardAccumulator {
    private $children;

    public function __construct() {
        $this->children = array();
    }

    /**
     * accept and process a scorecard
     *
     * @param array $row the scorecard data
     */
    public function accept($row) {
        $key = $this->makeKey(
            $row,
            array('bow', 'category', 'gender', 'round')
        );
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_ClubRecordAccumulatorLeaf();
        }
        $this->children[$key]->accept($row);
    }

    /**
     * return the child leaf accumulators
     *
     * @return array|RHAC_ClubRecordAccumulatorLeaf[]
     */
    protected function getChildren() {
        return array_values($this->children);
    }
}

/**
 * accumulate recommendations for club record changes
 * for a particular bow, age group, gender, and round
 */
class RHAC_ClubRecordAccumulatorLeaf extends RHAC_AccumulatorLeaf {

    /** @var int $max current maximum score */
    private $max = 0;

    /**
     * @var array $current_db_values current scorecards table data
     */
    protected $current_db_values = array();

    /**
     * @var array $proposed_changes
     */
    protected $proposed_changes = array();
    
    /**
     * @var array $unbeaten_records current notion of current records
     */
    private $unbeaten_records = array();

    /**
     * accept and process a row from the scorecards table
     *
     * @param array $row
     */
    public function accept($row) {
        if ($row['reassessment'] != 'N') {
            return;
        }
        if ($row['guest'] == "Y") {
            if  ($row['club_record'] != 'N') {
                $this->handleInaccurateRecord($row);
            }
        }
        elseif ($row['score'] > $this->max) {
            $this->handleNewRecord($row);
        }
        elseif ($row['score'] == $this->max) {
            $this->handleEqualRecord($row);
        }
        elseif ($row['club_record'] != 'N') {
            $this->handleInaccurateRecord($row);
        }
    }

    /**
     * the name of the field in the scorecards table
     * that this accumulator affects
     */
    protected function keyToChange() {
        return 'club_record';
    }

    /**
     * called if the row represents a new club record
     *
     * @param array $row
     */
    private function handleNewRecord($row) {
        $this->max = $row['score'];
        $this->current_db_values[$row['scorecard_id']] = $row['club_record'];
        foreach ($this->unbeaten_records as $scorecard_id) {
            $this->proposed_changes[$scorecard_id] = 'old';
        }
        $this->proposed_changes[$row['scorecard_id']] = 'current';
        $this->unbeaten_records = array($row['scorecard_id']);
    }

    /**
     * called if the row represents a record equal to the current
     * club record
     *
     * @param array $row
     */
    private function handleEqualRecord($row) {
        $this->current_db_values[$row['scorecard_id']] = $row['club_record'];
        $this->proposed_changes[$row['scorecard_id']] = 'current';
        $this->unbeaten_records []= $row['scorecard_id'];
    }

    /**
     * called if the row represents an inaccurate
     * club record
     *
     * @param array $row
     */
    private function handleInaccurateRecord($row) {
        $this->current_db_values[$row['scorecard_id']] = $row['club_record'];
        $this->proposed_changes[$row['scorecard_id']] = 'N';
    }

}
