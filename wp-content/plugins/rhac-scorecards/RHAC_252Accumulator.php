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
        $key = implode("\e", array($row['archer'], $row['bow'], $row['round']));
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_252AccumulatorLeaf();
        }
        $this->children[$key]->accept($row);
    }

    // override
    protected function getChildren() {
        return array_values($this->children);
    }

}

class RHAC_252AccumulatorLeaf extends RHAC_AccumulatorLeaf {
    protected $current_db_values = array();
    protected $proposed_changes = array();

    private $count;
    private $previous;

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

    public function accept($row) {
        $bow = $row['bow'];
        $round = $row['round'];
        $score = $row['score'];
        if ($this->belowRequiredScore($bow, $round, $score)) {
            if ($row['two_five_two'] != "N") {
                $this->handleWrong($row);
            }
            return;
        }
        switch ($this->count) {
            case 0:
                $this->handleFirst($row);
                break;
            case 1:
                $this->handleSecond($row);
                break;
            default:
                return;
        }
    }

    private function handleWrong($row) {
        $this->noteChange($row, "N");
    }

    private function handleFirst($row) {
        ++$this->count;
        $this->noteChange($row, self::$colours[$row['round']] . '/1');
    }

    private function handleSecond($row) {
        ++$this->count;
        $this->noteChange($row, self::$colours[$row['round']] . '/2');
    }

    private function noteChange($row, $change) {
        $this->proposed_changes[$row['scorecard_id']] = $change;
        $this->current_db_values[$row['scorecard_id']] = $row['two_five_two'];
    }

    private function belowRequiredScore($bow, $round, $score) {
        return self::$requirements[$round][$bow] > $score;
    }

    protected function keyToChange() {
        return 'two_five_two';
    }
}
