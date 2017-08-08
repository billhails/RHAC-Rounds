<?php

/**
 * @see README.md
 */

/**
 * accumulator for 252 awards, per archer, per bow
 */
class RHAC_252Accumulator extends RHAC_ScorecardAccumulator {
    private $children;

    public function __construct() {
        $this->children = array();
    }

    /**
     * accepts a row, categorises it by archer and bow
     * then passes it to the appropriate RHAC_252AccumulatorLeaf
     * instance, creating one if necessary
     *
     * @param array $row
     */
    public function accept($row) {
        if (!strstr($row['round'], "252")) {
            return;
        }
        $key = $this->makeKey($row, array('archer', 'bow'));
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_252AccumulatorLeaf();
        }
        $this->children[$key]->accept($row);
    }

    /**
     * returns the child leaves, after finalizing them
     *
     * @return array|RHAC_252AccumulatorLeaf[]
     */
    protected function getChildren() {
        foreach ($this->children as $child) {
            $child->handleDeferrals();
        }
        return array_values($this->children);
    }

}

/**
 * the 252 accumulator leaf class does the real work
 */
class RHAC_252AccumulatorLeaf extends RHAC_AccumulatorLeaf {

    /**
     * @var array $current_db_values keeps track of what each scorecard
     *                               currently says
     */
    protected $current_db_values = array();

    /**
     * @var array $proposed_changes keeps a record of things that have to
     *                              change.
     */
    protected $proposed_changes = array();

    /**
     * @var array $deferrals scorecards to be considered at the end of the
     *                       day when we know if subsequent rounds were shot
     */
    private $deferrals = array();

    /**
     * @var string $date the date of the previous scorecard row
     */
    private $date = '';

    /**
     * @var array $counts count of the number of qualifying scores
     *                    per round
     */
    private $counts = array(
        'Green 252' => 0,
        'White 252' => 0,
        'Black 252' => 0,
        'Blue 252' => 0,
        'Red 252' => 0,
        'Bronze 252' => 0,
        'Silver 252' => 0,
        'Gold 252' => 0,
    );

    /**
     * @var array $requirements read-only data on required scores per
     *                          round per bow
     */
    private static $requirements = array(
        'Green 252' => array(
            'recurve' => 252,
            'compound' => 280,
            'longbow' => 164,
            'barebow' => 189
        ),
        'White 252' => array(
            'recurve' => 252,
            'compound' => 280,
            'longbow' => 164,
            'barebow' => 189
        ),
        'Black 252' => array(
            'recurve' => 252,
            'compound' => 280,
            'longbow' => 164,
            'barebow' => 189
        ),
        'Blue 252' => array(
            'recurve' => 252,
            'compound' => 280,
            'longbow' => 164,
            'barebow' => 189
        ),
        'Red 252' => array(
            'recurve' => 252,
            'compound' => 280,
            'longbow' => 164,
            'barebow' => 189
        ),
        'Bronze 252' => array(
            'recurve' => 252,
            'compound' => 280,
            'longbow' => 164,
            'barebow' => 189
        ),
        'Silver 252' => array(
            'recurve' => 252,
            'compound' => 280,
            'longbow' => 126,
            'barebow' => 164
        ),
        'Gold 252' => array(
            'recurve' => 252,
            'compound' => 280,
            'longbow' => 101,
            'barebow' => 139
        )
    );

    /**
     * @var array $colours a map from round to badge colour
     */
    private static $colours = array(
        'Green 252' => 'green',
        'White 252' => 'white',
        'Black 252' => 'black',
        'Blue 252' => 'blue',
        'Red 252' => 'red',
        'Bronze 252' => 'bronze',
        'Silver 252' => 'silver',
        'Gold 252' => 'gold',
    );

    /**
     * @var array $previous a map from round to
     *                      previous round or false
     */
    private static $previous = array(
        'Green 252' => false,
        'White 252' => false,
        'Black 252' => 'White 252',
        'Blue 252' => 'Black 252',
        'Red 252' => 'Black 252',
        'Bronze 252' => 'Red 252',
        'Silver 252' => 'Bronze 252',
        'Gold 252' => 'Silver 252'
    );

    /**
     * @var array $rank a map from round to sort order ascending
     */
    private static $rank = array(
        'Green 252' => 0,
        'White 252' => 1,
        'Black 252' => 2,
        'Blue 252' => 3,
        'Red 252' => 4,
        'Bronze 252' => 5,
        'Silver 252' => 6,
        'Gold 252' => 7
    );

    /**
     * accept the next scorecard
     *
     * @param array $row
     */
    public function accept($row) {
        $date = $row['date'];
        $bow = $row['bow'];
        $round = $row['round'];
        $score = $row['score'];
        $guest = $row['guest'];
        if ($this->date && $this->date !== $date) {
            $this->handleDeferrals();
        }
        $this->date = $date;
        if ($guest == "Y") {
            $this->handleFail($row);
            return;
        }
        if ($this->belowRequiredScore($bow, $round, $score)) {
            $this->handleFail($row);
            return;
        }
        if ($this->gotPrevious($round)) {
            switch ($this->counts[$round]) {
                case 0:
                    $this->handleSuccess($row, 1);
                    break;
                case 1:
                    $this->handleSuccess($row, 2);
                    break;
                default:
                    return;
            }
        } else {
            $this->deferToEndOfDay($row);
        }
    }

    /**
     * mimic spaceship operator
     *
     * @var array $row1
     * @var array $row2
     * @return int
     */
    public function sortByRound($row1, $row2) {
        $rank1 = self::$rank[$row1['round']];
        $rank2 = self::$rank[$row2['round']];
        if ($rank1 == $rank2) {
            return 0;
        }
        return ($rank1 < $rank2) ? -1 : 1;
    }

    /**
     * called at the end of each day's worth of scorecards
     */
    public function handleDeferrals() {
        usort($this->deferrals, array($this, 'sortByRound'));
        foreach ($this->deferrals as $row) {
            $round = $row['round'];
            switch ($this->counts[$round]) {
            case 0:
                if ($this->gotPrevious($round, 1)) {
                    $this->handleSuccess($row, 1);
                } else {
                    $this->handleFail($row);
                }
                break;
            case 1:
                if ($this->gotPrevious($round, 2)) {
                    $this->handleSuccess($row, 2);
                } else {
                    $this->handleFail($row);
                }
                break;
            }
        }
        $this->deferrals = array();
    }

    /**
     * don't consider this scorecard until we've seen all the scorecards
     * for today.
     *
     * @param array $row
     */
    private function deferToEndOfDay($row) {
        $this->deferrals []= $row;
    }

    /**
     * return true if the archer has got a qualifying result
     * at the previous distance
     *
     * @param string $round
     * @param int $needed how many quatifying scores are needed (1 or 2)
     */
    private function gotPrevious($round, $needed = 2) {
        $previous = self::$previous[$round];
        if ($previous) {
            return $this->counts[$previous] >= $needed;
        } else {
            return true;
        }
    }

    /**
     * set the recommendation for this scorecard to 'N'
     * if the scorecard is currently 'Y'
     *
     * @param array $row
     */
    private function handleFail($row) {
        if ($row['two_five_two'] != "N") {
            $this->noteChange($row, "N");
        }
    }

    /**
     * increment the count of qualifying scores for this round
     * and set the recommendation for this scorecard accordingly.
     *
     * @param array $row the scorecard
     * @param int $number 1 or 2
     */
    private function handleSuccess($row, $number) {
        ++$this->counts[$row['round']];
        $this->noteChange(
            $row,
            self::$colours[$row['round']] . '/' . $number
        );
    }

    /**
     * add a recommendation
     *
     * TODO - refactor to parent along with proposed_changes
     * and current_db_values
     *
     * @param array $row the scorecard
     * @param string $change the new value
     */
    private function noteChange($row, $change) {
        $this->proposed_changes[$row['scorecard_id']] = $change;
        $this->current_db_values[$row['scorecard_id']] =
            $row[$this->keyToChange()];
    }

    /**
     * true if the score is below the required value for this round and bow
     *
     * @param int $score
     * @param string $round
     * @param string $bow
     *
     * @return bool
     */
    private function belowRequiredScore($bow, $round, $score) {
        return $score < self::$requirements[$round][$bow];
    }

    /**
     * returns the name of the database field in the scorecards table
     * that this accumulator alters
     *
     * @return string
     */
    protected function keyToChange() {
        return 'two_five_two';
    }
}
