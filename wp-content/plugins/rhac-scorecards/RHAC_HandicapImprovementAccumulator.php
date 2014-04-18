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

class RHAC_HandicapImprovementAccumulator {
    private $current_handicap;
    private $handicaps_this_season;
    private $proposals;
    private $current_values;

    /* archer, bow, outdoor */
    public function __construct($row) {
        $this->handicaps_this_season = array();
        $this->proposals = array();
        $this->current_values = array();
    }

    /*
     * order by date, handicap desc, score
     * need new 'end_of_season text not null default "N"'
     *  and those end of season records need a handicap of 101
     *  so they show up before real scores.
     */
    public function accept($row) {
        if ($row['end_of_season'] == "Y") {
            if (count($this->handicaps_this_season) >= 3) {
                $this->current_handicap = $this->averageBestThree();
            }
            $this->handicaps_this_season = array();
            $this->noteHandicapChange($row);
        }
        else {
            $this->handicaps_this_season []= $row['handicap'];
            if (isset($this->current_handicap)) {
                $average = ceil(($row['handicap'] + $this->current_handicap) / 2);
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

    public function results() {
        $results = array();
        foreach ($this->proposals as $scorecard_id => $handicap_improvement) {
            if ($this->current_values[$scorecard_id] != $handicap_improvement) {
                $results[$scorecard_id] = array('handicap_improvement' => $handicap_improvement);
            }
        }
        return $results;
    }

    private function noteHandicapChange($row, $new_handicap = -1) {
        if ($new_handicap != -1) {
            $this->current_handicap = $new_handicap;
        }
        $this->proposals[$row['scorecard_id']] = $this->current_handicap;
        $this->current_values[$row['scorecard_id']] = $row['handicap_improvement'];
    }

    private function averageBestThree() {
        $sorted = sort($this->handicaps_this_season); # FIXME sort numeric descending
        return ceil(($sorted[0] + $sorted[1] + $sorted[2]) / 3);
    }

}
