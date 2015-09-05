<?php

class RHAC_252Accumulator extends RHAC_ScorecardAccumulator {
    private $children;

    public function __construct() {
        $this->children = array();
    }

    // override
    public function accept($row) {
        if (!strstr($row['round'], "252")) {
            return;
        }
        $key = implode("\e", array($row['archer'], $row['bow']));
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_252AccumulatorLeaf();
        }
        $this->children[$key]->accept($row);
    }

    // override
    protected function getChildren() {
        foreach ($this->children as $child) {
            $child->handleDeferrals();
        }
        return array_values($this->children);
    }

}

class RHAC_252AccumulatorLeaf extends RHAC_AccumulatorLeaf {
    protected $current_db_values = array();
    protected $proposed_changes = array();
    private $deferrals = array();
    private $date = '';

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

    private static $requirements = array(
        'Green 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'White 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'Black 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'Blue 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'Red 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'Bronze 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'Silver 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 126, 'barebow' => 164),
        'Gold 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 101, 'barebow' => 139)
    );

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

    public function sortByRound($row1, $row2) {
        $rank1 = self::$rank[$row1['round']];
        $rank2 = self::$rank[$row2['round']];
        if ($rank1 == $rank2) {
            return 0;
        }
        return ($rank1 < $rank2) ? -1 : 1;
    }

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

    private function deferToEndOfDay($row) {
        $this->deferrals []= $row;
    }

    private function gotPrevious($round, $needed = 2) {
        $previous = self::$previous[$round];
        if ($previous) {
            return $this->counts[$previous] >= $needed;
        } else {
            return true;
        }
    }

    private function handleFail($row) {
        if ($row['two_five_two'] != "N") {
            $this->noteChange($row, "N");
        }
    }

    private function handleSuccess($row, $number) {
        ++$this->counts[$row['round']];
        $this->noteChange($row, self::$colours[$row['round']] . '/' . $number);
    }

    # TODO - refactor to parent along with proposed_changes and current_db_values
    private function noteChange($row, $change) {
        $this->proposed_changes[$row['scorecard_id']] = $change;
        $this->current_db_values[$row['scorecard_id']] = $row[$this->keyToChange()];
    }

    private function belowRequiredScore($bow, $round, $score) {
        return self::$requirements[$round][$bow] > $score;
    }

    protected function keyToChange() {
        return 'two_five_two';
    }
}
