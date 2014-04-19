<?php

/**
 * A handicap starts out undef. It is never undef once assigned.
 * after three scores are submitted within the first season
 *    the handicap is the average.
 * the fourth and subsequent better scores are averaged with
 *    the current handicap.
 * at the end of a season the handicap is the average of the
 *    three best scores from the previous season, or the
 *    previous handicap if less than three scores were recorded.
 */

class RHAC_HandicapImprovementAccumulator extends RHAC_ScorecardAccumulator {
    private $children;

    public function __construct() {
        $this->children = array();
    }

    public function accept($row) {
        $key = implode("\e", array($row['archer'], $row['bow'], $row['outdoor']));
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_HandicapImprovementAccumulatorLeaf();
        }
        $this->children[$key]->accept($row);
    }

    protected function getChildren() {
        return array_values($this->children);
    }
}

class RHAC_HandicapImprovementAccumulatorLeaf extends RHAC_AccumulatorLeaf {
    private $current_handicap;
    private $handicaps_this_season = array();
    protected $proposed_changes = array();
    protected $current_db_values = array();

    /*
     * order by date, handicap desc, score
     * need new 'end_of_season text not null default "N"'
     *  and those end of season records need a handicap of 101
     *  so they show up before real scores.
     */
    public function accept($row) {
        if ($row['reassessment'] == "age_group") {
            return;
        } elseif ($row['reassessment'] == "end_of_season") {
            if (count($this->handicaps_this_season) >= 3) {
                $this->current_handicap = $this->averageBestThree();
            }
            $this->handicaps_this_season = array();
            $this->noteHandicapChange($row);
        }
        else {
            if (isset($row['handicap_ranking'])) {
                $this->handicaps_this_season []= $row['handicap_ranking'];
                if (isset($this->current_handicap)) {
                    $average = intval(ceil(($row['handicap_ranking'] + $this->current_handicap) / 2));
                    if ($average < $this->current_handicap) {
                        $this->noteHandicapChange($row, $average);
                    }
                } else {
                    if (count($this->handicaps_this_season) == 3) {
                        $this->noteHandicapChange($row, $this->averageBestThree());
                    }
                }
            }
        }
    }

    protected function keyToChange() {
        return 'handicap_improvement';
    }

    private function noteHandicapChange($row, $new_handicap = -1) {
        if ($new_handicap != -1) {
            $this->current_handicap = $new_handicap;
        }
        $this->proposed_changes[$row['scorecard_id']] = $this->current_handicap;
        $this->current_db_values[$row['scorecard_id']] = $row['handicap_improvement'];
    }

    private function averageBestThree() {
        sort($this->handicaps_this_season, SORT_NUMERIC);
        return intval(ceil(($this->handicaps_this_season[0] +
                            $this->handicaps_this_season[1] +
                            $this->handicaps_this_season[2]) / 3));
    }

}