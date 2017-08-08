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

/**
 * accumulator for handicaps
 */
class RHAC_HandicapImprovementAccumulator extends RHAC_ScorecardAccumulator {
    private $children;

    public function __construct() {
        $this->children = array();
    }

    /**
     * accept the next scorecard and dispatch to the correct accumulator leaf
     */
    public function accept($row) {
        $key = $this->makeKey($row, array('archer', 'bow', 'outdoor'));
        if (!isset($this->children[$key])) {
            $this->children[$key] =
                new RHAC_HandicapImprovementAccumulatorLeaf();
        }
        $this->children[$key]->accept($row);
    }

    /**
     * return the accumulator leaves as an array
     */
    protected function getChildren() {
        return array_values($this->children);
    }
}

/**
 * handicap accumulator for an individual archer, bow and venue
 */
class RHAC_HandicapImprovementAccumulatorLeaf extends RHAC_AccumulatorLeaf {

    /**
     * @var int $current_handicap the current handicap
     */
    private $current_handicap;

    /**
     * @var array $handicaps_this_season qualifying scores this season
     */
    private $handicaps_this_season = array();

    /**
     * @var array $proposed_changes proposed changes to scorecards
     */
    protected $proposed_changes = array();

    /**
     * @var array $current_db_values current values in the database
     */
    protected $current_db_values = array();

    /*
     * accept and process the next scorecard for this archer, bow and venue.
     * order by date, handicap desc, score
     */
    public function accept($row) {
        if ($this->isAgeGroupReassessment($row)) {
            return;
        } elseif ($this->isEndOfSeason($row)) {
            $this->handleEndOfSeason($row);
        } elseif ($this->scoreHasHandicapRanking($row)) {
            $this->handleNormalScore($row);
        }
    }

    /**
     * process an end-of-season reassessment
     *
     * @param array $row
     */
    private function handleEndOfSeason($row) {
        if (count($this->handicaps_this_season) >= 3) {
            $this->current_handicap = $this->averageBestThree();
        }
        $this->handicaps_this_season = array();
        $this->noteHandicapChange($row);
    }

    /**
     * process a normal score card
     *
     * @param array $row
     */
    private function handleNormalScore($row) {
        if ($row['guest'] == 'Y') {
            if (isset($row['handicap_improvement'])) {
                $this->noteInaccurateHandicap($row);
            }
            return;
        }
        // FIXME here we should check if the round is official
        $this->handicaps_this_season []= $row['handicap_ranking'];
        if (isset($this->current_handicap)) {
            $average = $this->averageWithCurrentHandicap($row);
            if ($average < $this->current_handicap) {
                $this->noteHandicapChange($row, $average);
            } elseif (isset($row['handicap_improvement'])) {
                $this->noteInaccurateHandicap($row);
            }
        } elseif (count($this->handicaps_this_season) == 3) {
            $this->noteHandicapChange($row, $this->averageBestThree());
        } elseif (isset($row['handicap_improvement'])) {
            $this->noteInaccurateHandicap($row);
        }
    }

    /**
     * return true if the scorecard has a handicap ranking for the score
     * irrespective of whether or not the round is official
     *
     * @param array $row
     * @return bool
     */
    private function scoreHasHandicapRanking($row) {
        return isset($row['handicap_ranking']);
    }

    /**
     * return true if the scorecard is an age group reasessment
     *
     * @param array $row
     * @return bool
     */
    private function isAgeGroupReassessment($row) {
        return $row['reassessment'] == "age_group";
    }

    /**
     * return true if the scorecard is an end of season reasessment
     *
     * @param array $row
     * @return bool
     */
    private function isEndOfSeason($row) {
        return $row['reassessment'] == "end_of_season";
    }

    /**
     * average the handicap ranking of the score
     * with the archers current handicap
     *
     * @param array $row
     * @return int
     */
    private function averageWithCurrentHandicap($row) {
        return intval(
            ceil(($row['handicap_ranking'] + $this->current_handicap) / 2)
        );
    }

    /**
     * the name of the field in the scorecard table
     * that this accumulator changes
     *
     * @return string
     */
    protected function keyToChange() {
        return 'handicap_improvement';
    }

    /**
     * add a recommendation for a modification to the scorecard
     *
     * @param array $row the scorecard
     * @param int $new_handicap
     */
    private function noteHandicapChange($row, $new_handicap = -1) {
        if ($new_handicap != -1) {
            $this->current_handicap = $new_handicap;
        }
        $this->proposed_changes[$row['scorecard_id']] =
            $this->current_handicap;
        $this->current_db_values[$row['scorecard_id']] =
            $row['handicap_improvement'];
    }

    /**
     * add a recommendation for a modification to the scorecard
     *
     * @param array $row the scorecard data
     */
    private function noteInaccurateHandicap($row) {
        $this->current_db_values[$row['scorecard_id']] =
            $row['handicap_improvement'];
        $this->proposed_changes[$row['scorecard_id']] = null;
    }

    /**
     * return the average of the best three handicap rankings this season
     *
     * @return int
     */
    private function averageBestThree() {
        sort($this->handicaps_this_season, SORT_NUMERIC);
        return intval(ceil(($this->handicaps_this_season[0] +
                            $this->handicaps_this_season[1] +
                            $this->handicaps_this_season[2]) / 3));
    }

}
