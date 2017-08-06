<?php
/*
Plugin Name: RHAC Score Card Viewer
Description: read-only view of archery scorecards, requires rhac-scorecards plugin
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

define('RHAC_SCORECARD_VIEW_DIR', plugin_dir_path(__FILE__));
define('RHAC_SCORECARD_VIEW_ROOT',
       preg_replace('/[^\/]+\/$/', '', RHAC_SCORECARD_VIEW_DIR));
define('RHAC_SCORECARD_DIR', RHAC_SCORECARD_VIEW_ROOT . 'rhac-scorecards/');
define('RHAC_ROUNDS_DIR', RHAC_SCORECARD_VIEW_ROOT . 'gnas-archery-rounds/');

include_once RHAC_ROUNDS_DIR . 'rounds.php';

/**********************************************
 * This class acts as an accumulator of data
 * for a scorecard under construction
 *
 */
class RHACScorecardCounter {

    /* @var int $doz_hits the number of hits in the current dozen */
    private $doz_hits;

    /* @var int $doz_xs the number of xs in the current dozen */
    private $doz_xs;

    /* @var int $doz_golds the number of golds in the current dozen */
    private $doz_golds;

    /* @var int $doz_score the score for the current dozen */
    private $doz_score;

    /* @var int $end_score the score for the current end */
    private $end_score;

    /* @var int $total_hits the total hits for the round */
    private $total_hits;

    /* @var int $total_xs the total xs for the round */
    private $total_xs;

    /* @var int $total_golds the total golds for the round */
    private $total_golds;

    /* @var int $total_score the total score for the round */
    private $total_score;

    /* @var array $spread count of arrows with a particular score, for the bar-charts */
    private $spread;

    /* @var int $count the current end number */
    private $count;

    public function __construct() {
        $this->count = 0;
        $this->spread = array();
    }

    public function debug() {
        return '<pre>' . print_r($this->spread, true) . '</pre>';
    }

    /* getter */
    public function dozHits() { return $this->doz_hits; }

    /* getter */
    public function dozXs() { return $this->doz_xs; }

    /* getter */
    public function dozGolds() { return $this->doz_golds; }

    /* getter */
    public function dozScore() { return $this->doz_score; }

    /* getter */
    public function endScore() { return $this->end_score; }

    /* getter */
    public function totalHits() { return $this->total_hits; }

    /* getter */
    public function totalXs() { return $this->total_xs; }

    /* getter */
    public function totalGolds() { return $this->total_golds; }

    /* getter */
    public function totalScore() { return $this->total_score; }

    /*
     * True if the end is a rhs (even) end.
     *
     * @return bool
     */
    public function isRight() {
        return ($this->count % 2) == 0;
    }

    /*
     * True if the end is a lhs (odd) end.
     *
     * @return bool
     */
    public function isLeft() {
        return !$this->isRight();
    }

    /*
     * resets the dozen-type counters to zero
     */
    private function newDoz() {
        $this->doz_hits = 0;
        $this->doz_xs = 0;
        $this->doz_golds = 0;
        $this->doz_score = 0;
    }

    /*
     * resets the counters for a new end
     *
     * if a new dozen, resets the dozen-type counters as well.
     */
    public function nextEnd() {
        $this->end_score = 0;
        $this->count++;
        if ($this->isLeft()) {
            $this->newDoz();
        }
    }

    /*
     * return the count of arrows with the given score
     *
     * @param string $score the label, i.e. X, 10 ... M
     * @return int
     */
    public function getCount($score) {
        if (isset($this->spread[$score])) {
            return $this->spread[$score];
        }
        else {
            return 0;
        }
    }

    /*
     * add an arrow
     *
     * adjust all of the counts appropriately
     *
     * @param string $arrow the label, i.e. X, 10 ... M
     * @return void
     */
    public function add($arrow) {
        $score = $this->arrowScore($arrow);
        $this->end_score += $score;
        $this->doz_score += $score;
        $this->total_score += $score;

        $hit = $this->arrowHit($arrow);
        $this->doz_hits += $hit;
        $this->total_hits += $hit;

        $x += $this->arrowX($arrow);
        $this->doz_xs += $x;
        $this->total_xs += $x;

        $gold = $this->arrowGold($arrow);
        $this->doz_golds += $gold;
        $this->total_golds += $gold;

        if (!isset($this->spread[$arrow])) {
            $this->spread[$arrow] = 0;
        }

        $this->spread[$arrow]++;
    }

    /*
     * convert a score name to a numeric value
     *
     * @param string $arrow the label, i.e. X, 10 ... M
     * @return int
     */
    private function arrowScore($arrow) {
        if ($arrow == 'X') {
            return 10;
        } elseif ($arrow == 'M') {
            return 0;
        } else {
            return $arrow;
        }
    }

    /*
     * true if the arrow is not a miss
     *
     * @param string $arrow the label, i.e. X, 10 ... M
     * @return bool
     */
    private function arrowHit($arrow) {
        return $arrow == 'M' ? 0 : 1;
    }

    /*
     * true if the arrow is an X
     *
     * @param string $arrow the label, i.e. X, 10 ... M
     * @return bool
     */
    private function arrowX($arrow) {
        return $arrow == 'X' ? 1 : 0;
    }

    /*
     * true if the arrow is in the gold
     *
     * @param string $arrow the label, i.e. X, 10 ... M
     * @return bool
     */
    private function arrowGold($arrow) {
        return $arrow == 'X' ? 1 : $arrow == 10 ? 1 : $arrow == 9 ? 1 : 0;
    }

}

/************************************************************
 * Represents a "normal" bar in a bar chart, is subclassed for
 * special types of bar (narrow inner ten etc) But copes with
 * the other bar types (9 ... M) itself
 */
class RHAC_Bar {

    /**
     * @var string $arrow the individual score this bar represents (X, 10 ... M)
     */
    private $arrow;

    /**
     * @var string $cssClass the css class used to mark up this bar
     */
    private $cssClass;

    /**
     * @var int $height the height of the bar (actually number of arrows with this score)
     */
    private $height;

    /**
     * @var RHAC_Bar_Accumulator $acumulator a counter for current height etc.
     */
    protected $accumulator;

    /**
     * return a multiplier for the height of the bar
     *
     * @return int
     */
    public function getHeightMultiplier() {
        return 150;
    }

    /**
     * @param string $arrow (X, 10 ... M)
     * @param string $cssClass the css class for display markup
     */
    public function __construct($arrow, $cssClass) {
        $this->arrow = $arrow;
        $this->cssClass = $cssClass;
    }

    /**
     * returns the normalized width (to be multiplied for display width)
     *
     * @return int
     */
    public function getWidth() {
        return 1;
    }

    /**
     * returns the normalized height (to be multiplied for display height)
     *
     * @return int
     */
    public function getHeight() {
        return $this->height;
    }

    /**
     * returns the css class
     *
     * @return string
     */
    public function getClass() {
        return $this->cssClass;
    }

    /**
     * sets the accumulator for calculations
     *
     * @param RHAC_Bar_Accumulator $accumulator
     */
    public function setAccumulator($accumulator) {
        $this->accumulator = $accumulator;
    }

    /**
     * accepts data from a counter (final count of arrows)
     *
     * @param RHACScorecardCounter $counter
     */
    public function acceptScore($counter) {
        $this->height = $counter->getCount($this->arrow);
        $this->accumulator->acceptHeight($this->height);
        $this->accumulator->acceptWidth($this->getWidth());
    }

    /**
     * returns html representing the bar as a td
     *
     * @return string
     */
    public function asTD() {
        $height = $this->getHeightMultiplier()
                * $this->getHeight()
                / $this->accumulator->getMaxHeight();
        $width = 100
               * $this->getWidth()
               / $this->accumulator->getTotalWidth();
        $cssClass = $this->getClass();
        return <<<EOTD
<td class="bar" width="$width%">
<div class="bar $cssClass" style="height: ${height}px;">
&nbsp;</div></td>
EOTD;
    }

}

/************************************************************
 * This class specialises RHAC_Bar for a narrow inner ten bar
 * (compound indoor scoring)
 */
class RHAC_Bar_InnerTen extends RHAC_Bar {

    /**
     * just calls the parent RHAC_Bar constructor with appropriate arguments
     */
    public function __construct() {
        parent::__construct("10", "arrow-gold");
    }

    /**
     * overrides the parent getWidth to return 0.5
     *
     * @return float
     */
    public function getWidth() {
        return 0.5;
    }

}

/************************************************************
 * specialises the RHAC_Bar class to produce an extra-wide bar for a nine next to an inner ten
 * (compound indoor scoring)
 */
class RHAC_Bar_WideNine extends RHAC_Bar {

    /**
     * just calls the parent RHAC_Bar constructor with appropriate arguments
     */
    public function __construct() {
        parent::__construct("9", "arrow-gold");
    }

    /**
     * overrides the parent getWidth to return 1.5
     *
     * @return float
     */
    public function getWidth() {
        return 1.5;
    }

}

/************************************************************
 * This class specialises RHAC_Bar to represent the combined X and outer ten
 * bars stacked on top of one another (non-compound scoring)
 */
class RHAC_Bar_XTen extends RHAC_Bar {

    /**
     * @var int $ten_height count of tens that are not xs
     */
    private $ten_height;

    /**
     * @var int $x_height count of xs
     */
    private $x_height;

    /**
     * prevent the parent constructor from firing
     */
    public function __construct() {
    }

    /**
     * return the cumulative total height
     *
     * @return int
     */
    public function getHeight() {
        return $this->x_height + $this->ten_height;
    }

    /**
     * accepts data from a counter (final count of arrows)
     *
     * overrides the parent RHAC_Bar::acceptScore() to
     * collect both tens and xs from the counter.
     *
     * @param RHACScorecardCounter $counter
     */
    public function acceptScore($counter) {
        $this->ten_height = $counter->getCount('10');
        $this->x_height = $counter->getCount('X');
        $this->accumulator->acceptHeight($this->getHeight());
        $this->accumulator->acceptWidth($this->getWidth());
    }

    /**
     * returns html that will display the stacked bars for
     * tens and Xs with a little gap between them
     *
     * again this overrides the parent RHAC_Bar::asTD()
     *
     * @return string
     */
    public function asTD() {
        $x_height = $this->getHeightMultiplier()
                  * $this->x_height
                  / $this->accumulator->getMaxHeight();
        $ten_height = $this->getHeightMultiplier()
                    * $this->ten_height
                    / $this->accumulator->getMaxHeight();
        $ten_border_top = '';
        if ($ten_height > 1 && $x_height > 1) {
            $ten_height--;
            $x_height--;
            $ten_border_top = ' margin-top: 2px;';
        }
        $width = 100
               * $this->getWidth()
               / $this->accumulator->getTotalWidth();
        return <<<EOTD
<td class="bar" width="$width%">
<div class="bar arrow-x" style="height: ${x_height}px;">&nbsp;</div>
<div class="bar arrow-ten" style="height: ${ten_height}px;$ten_border_top">&nbsp;</div>
</td>
EOTD;
    }

}

/***********************************************
 * This class keeps track of the current
 * width and height of a bar in a barchart
 */
class RHAC_Bar_Accumulator {

    /** @var int $total_width the total width of the bar chart */
    private $total_width = 0;

    /** @var int the height of the tallest bar in the chart */
    private $max_height = 1;

    /**
     * given the height of a bar, sets max height
     *
     * @param int $height
     */
    public function acceptHeight($height) {
        if ($this->max_height < $height) {
            $this->max_height = $height;
        }
    }

    /**
     * given the width of a bar, adds to total width
     *
     * @param int $width (or float)
     */
    public function acceptWidth($width) {
        $this->total_width += $width;
    }

    /** getter */
    public function getMaxHeight() {
        return $this->max_height;
    }

    /** getter */
    public function getTotalWidth() {
        return $this->total_width;
    }

}

/***************************************
 * This abstract class provides common
 * support for the various concrete
 * barchart builder classes
 */
abstract class RHAC_BarchartBuilder {

    /**
     * static method constructs an appropriate concrete barchart builder
     *
     * @param string $scoring_name
     * @return RHAC_BarchartBuilder
     */
    public static function getBarchartBuilder($scoring_name) {
        switch ($scoring_name) {
            case "five zone":
                return new RHAC_FiveZoneBarchartBuilder();
            case "ten zone":
                return new RHAC_TenZoneBarchartBuilder();
            case "metric inner ten":
                return new RHAC_InnerTenZoneBarchartBuilder();
            case "vegas":
                return new RHAC_VegasBarchartBuilder();
            case "vegas inner ten":
                return new RHAC_VegasInnerTenBarchartBuilder();
            case "worcester":
                return new RHAC_WorcesterBarchartBuilder();
            default:
                return new RHAC_UnknownBarchartBuilder($scoring_name);
        }
    }

    /**
     * return html for a barchart using data in the counter
     *
     * @param RHACScorecardCounter $counter a populated counter
     *
     * @return string
     */
    public function makeBarchart($counter) {
        $accumulator = new RHAC_Bar_Accumulator();
        $bars = $this->analyseBars($counter, $accumulator);
        return  $this->buildHTML($bars, $accumulator);
    }

    /**
     * given a counter and an accumulator, returns an array of RHAC_Bar
     *
     * @param RHACScorecardCounter $counter a previously populated counter
     * @param RHAC_Bar_Accumulator a fresh accumulator
     *
     * @return array|RHAC_Bar[]
     */
    private function analyseBars($counter, $accumulator) {
        $bars = $this->makeArray();
        foreach ($bars as $bar) {
            $bar->setAccumulator($accumulator);
            $bar->acceptScore($counter);
        }
        return $bars;
    }

    /**
     * returns html representing the bar chart
     *
     * @param array|RHAC_Bar[] $bars
     * @param RHAC_Bar_Accumulator $accumulator holds total width, max height etc.
     *
     * @return string
     */
    protected function buildHTML($bars, $accumulator) {
        $html = array();
        $html []= '<table style="width: '
                . 100 * $accumulator->getTotalWidth() / 11
                . '%"><tr>';
        foreach ($bars as $bar) {
            $html []= $bar->asTD();
        }
        $html []= "</tr></table>\n";
        return implode('', $html);
    }

    /**
     * This function must be implemented by each class that extends this
     * and must return an array of fresh RHAC_Bar representing the bars
     * in this particular barchart.
     *
     * @return array|RHAC_Bar[]
     */
    protected abstract function makeArray();
}

/**********************************************************************
 * this class specialises RHAC_BarchartBuilder for a five zone barchart
 */
class RHAC_FiveZoneBarchartBuilder extends RHAC_BarchartBuilder {

    /**
     * returns an appropriate array of RHAC_Bar
     *
     * @return array|RHAC_Bar[]
     */
    protected function makeArray() {
        return array(
            new RHAC_Bar('9', 'arrow-gold'),
            new RHAC_Bar('7', 'arrow-red'),
            new RHAC_Bar('5', 'arrow-blue'),
            new RHAC_Bar('3', 'arrow-black'),
            new RHAC_Bar('1', 'arrow-white'),
            new RHAC_Bar('M', 'arrow-miss')
        );
    }
}

/**********************************************************************
 * this class specialises RHAC_BarchartBuilder for a standard ten zone barchart
 */
class RHAC_TenZoneBarchartBuilder extends RHAC_BarchartBuilder {

    /**
     * returns an appropriate array of RHAC_Bar
     *
     * @return array|RHAC_Bar[]
     */
    protected function makeArray() {
        return array(
            new RHAC_Bar_XTen(),
            new RHAC_Bar('9', 'arrow-gold'),
            new RHAC_Bar('8', 'arrow-red'),
            new RHAC_Bar('7', 'arrow-red'),
            new RHAC_Bar('6', 'arrow-blue'),
            new RHAC_Bar('5', 'arrow-blue'),
            new RHAC_Bar('4', 'arrow-black'),
            new RHAC_Bar('3', 'arrow-black'),
            new RHAC_Bar('2', 'arrow-white'),
            new RHAC_Bar('1', 'arrow-white'),
            new RHAC_Bar('M', 'arrow-miss')
        );
    }
}

/**********************************************************************
 * this class specialises RHAC_BarchartBuilder for a compund indoor ten zone barchart
 */
class RHAC_InnerTenZoneBarchartBuilder extends RHAC_BarchartBuilder {

    /**
     * returns an appropriate array of RHAC_Bar
     *
     * @return array|RHAC_Bar[]
     */
    protected function makeArray() {
        return array(
            new RHAC_Bar_InnerTen(),
            new RHAC_Bar_WideNine(),
            new RHAC_Bar('8', 'arrow-red'),
            new RHAC_Bar('7', 'arrow-red'),
            new RHAC_Bar('6', 'arrow-blue'),
            new RHAC_Bar('5', 'arrow-blue'),
            new RHAC_Bar('4', 'arrow-black'),
            new RHAC_Bar('3', 'arrow-black'),
            new RHAC_Bar('2', 'arrow-white'),
            new RHAC_Bar('1', 'arrow-white'),
            new RHAC_Bar('M', 'arrow-miss')
        );
    }
}

/**********************************************************************
 * this class specialises RHAC_BarchartBuilder for a vegas barchart
 */
class RHAC_VegasBarchartBuilder extends RHAC_BarchartBuilder {

    /**
     * returns an appropriate array of RHAC_Bar
     *
     * @return array|RHAC_Bar[]
     */
    protected function makeArray() {
        return array(
            new RHAC_Bar_XTen(),
            new RHAC_Bar('9', 'arrow-gold'),
            new RHAC_Bar('8', 'arrow-red'),
            new RHAC_Bar('7', 'arrow-red'),
            new RHAC_Bar('6', 'arrow-blue'),
            new RHAC_Bar('M', 'arrow-miss')
        );
    }
}

/**********************************************************************
 * this class specialises RHAC_BarchartBuilder for a vegas inner ten barchart
 */
class RHAC_VegasInnerTenBarchartBuilder extends RHAC_BarchartBuilder {

    /**
     * returns an appropriate array of RHAC_Bar
     *
     * @return array|RHAC_Bar[]
     */
    protected function makeArray() {
        return array(
            new RHAC_Bar_InnerTen(),
            new RHAC_Bar_WideNine(),
            new RHAC_Bar('8', 'arrow-red'),
            new RHAC_Bar('7', 'arrow-red'),
            new RHAC_Bar('6', 'arrow-blue'),
            new RHAC_Bar('M', 'arrow-miss')
        );
    }
}

/**********************************************************************
 * this class specialises RHAC_BarchartBuilder for a vegas inner ten barchart
 */
class RHAC_WorcesterBarchartBuilder extends RHAC_BarchartBuilder {

    /**
     * returns an appropriate array of RHAC_Bar
     *
     * @return array|RHAC_Bar[]
     */
    protected function makeArray() {
        return array(
            new RHAC_Bar('5', 'arrow-white'),
            new RHAC_Bar('4', 'arrow-black'),
            new RHAC_Bar('3', 'arrow-black'),
            new RHAC_Bar('2', 'arrow-black'),
            new RHAC_Bar('1', 'arrow-black'),
            new RHAC_Bar('M', 'arrow-miss')
        );
    }
}

/**********************************************************************
 * this class specialises RHAC_BarchartBuilder for an unrecognised type of barchart
 */
class RHAC_UnknownBarchartBuilder extends RHAC_BarchartBuilder {
    /**
     * @var string the name that wasn't recognised
     */
    private $name;

    /**
     * does nothing, but has to be declared
     */
    protected function makeArray() {}

    /**
     * @param $name the unrecognised name
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * overrides the parent to produce an error report
     */
    public function makeBarchart($counter) {
        return "<p class='error'>Unrecognised scoring: ["
               . $this->name
               . "]</p>";
    }
}

/*********************************************
 * This class provides html and other data for
 * displaying a score card
 */
class RHACScorecardViewer {

    /** @var RHACScorecardViewer $instance only allow one instance */
    private static $instance;

    /** @var PDO $pdo the database handle for the scorecard database */
    private $pdo;
    
    /** @var array $archers cache of archer names from the archers table */
    private $archers;

    /** @var array $rounds cache of rounds from the GNAS_Rounds data */
    private $rounds;

    /**
     * private cnstructor prevents external instantiation
     */
    private function __construct() {
        try {
            $this->pdo = new PDO('sqlite:'
                         . RHAC_SCORECARD_DIR
                         . 'scorecard.db');
        } catch (PDOException $e) {
            die('Error!: ' . $e->getMessage());
            exit();
        }
    }

    /**
     * Returns the single instance
     *
     * @return RHACScorecardViewer
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Returns an array of archer names
     *
     * @return array
     */
    public function getArchers() {
        if (!isset($this->archers)) {
            $this->archers = array();
            $archers = $this->select('name from archer where archived = "N" order by name');
            foreach ($archers as $archer) {
                $this->archers []= $archer['name'];
            }
        }
        return $this->archers;
    }

    /**
     * Return an array of rounds data from the GNAS Rounds database
     *
     * @param $nested if true the array will be grouped by round family
     * @return array
     */
    public function getRounds($nested = false) {
        if (!isset($this->rounds)) {
            $this->rounds = GNAS_Page::roundData($nested);
        }
        return $this->rounds;
    }

    /**
     * Return an array of rows from the scorecard table for the given archer, round and bow.
     *
     * Any of the arguments can be 'all'.
     * Only scorecards that have associated ends are returned.
     *
     * @param string $archer
     * @param string $round
     * @param string $bow
     *
     * @return array
     */
    public function getScorecards($archer, $round, $bow) {
        $conditions = array();
        $conditions []= 'has_ends = ?';
        $params = array("Y");
        if ($archer != "all") {
            $conditions []= "archer = ?";
            $params []= $archer;
        }
        if ($round != "all") {
            $conditions []= "round = ?";
            $params []= $round;
        }
        if ($bow != "all") {
            $conditions []= "bow = ?";
            $params []= $bow;
        }
        return $this->select("* FROM scorecards WHERE "
                           . implode(' AND ', $conditions)
                           . ' ORDER BY date', $params);
    }

    /**
     * return an array of arrays of end data from the scorecard_end table
     *
     * @param int $id the scorecard id
     *
     * @return array
     */
    private function getScorecardEnds($id) {
        return $this->select("*"
                      . " FROM scorecard_end"
                      . " WHERE scorecard_id = ?"
                      . " ORDER BY end_number",
                      array($id));
    }

    /**
     * return an array of data from the scorecards table
     *
     * @param int the scorecard id
     * @return array
     */
    private function getMainScorecard($id) {
        $rows = $this->select("*"
                      . " FROM scorecards"
                      . " WHERE scorecard_id = ?",
                      array($id));
        return $rows[0];
    }

    /**
     * (unused) look up the handicap for a scorecard result
     *
     * @param array $scorecard
     * @return int
     */
    private function getHandicapForScore($scorecard) {
        $compound = $scorecard["bow"] == "compound" ? "Y" : "N";
        $rows = $this->select("min(handicap) as result"
                            . " from round_handicaps"
                            . " where round = ? and compound = ? and score <= ?",
                            array($scorecard["round"], $compound, $scorecard["score"]));
        return $rows[0]["result"];
    }

    /**
     * returns html representing the scorecard as a table
     *
     * @param array $ends an array of arrays, the inner arrays are individual ends data
     * @param RHACScorecardCounter $counter a fresh scorecard counter
     * @param array $scorecard the scorecard summary data from the scorecard table
     * @return string
     */
    private function scorecardAsTable($ends, $counter, $scorecard) {
        $div = array();
        $arrow_keys = array('arrow_1', 'arrow_2', 'arrow_3',
                            'arrow_4', 'arrow_5', 'arrow_6');
        $div []= "<div class='scorecard-table'>\n";
        $div []= "<table>\n";
        $div []= "<thead>\n";
        $div []= "<tr>";
        for ($i = 0; $i < 2; ++$i) {
            foreach (array(1, 2, 3, 4, 5, 6, 'END') as $th) {
                $th_class = "scorecard-" . strtolower($th);
                $div []= "<th class='$th_class'>$th</th>";
            }
        }
        foreach (array('HITS', 'XS', 'GOLDS', 'DOZ', 'TOT') as $th) {
            $th_class = "scorecard-" . strtolower($th);
            $div []= "<th class='$th_class'>$th</th>";
        }
        $div []= "</tr>\n</thead>\n<tbody>";

        foreach ($ends as $end_data) {
            $counter->nextEnd();
            if ($counter->isLeft()) {
                $div []= '<tr>';
            }
            foreach ($arrow_keys as $key) {
                $arrow = $end_data[$key];
                $counter->add($arrow);
                $div []= '<td class="arrow '
                         . $this->arrowClass($arrow) . '">'
                         . $arrow . '</td>';
            }
            $div []= '<td class="end-total">'
                     . $counter->endScore()
                     . '</td>';
            if ($counter->isRight()) {
                $div []= '<td class="scorecard-hits">'
                         . $counter->dozHits() . '</td>';
                $div []= '<td class="scorecard-xs">'
                         . $counter->dozXs() . '</td>';
                $div []= '<td class="scorecard-golds">'
                         . $counter->dozGolds() . '</td>';
                $div []= '<td class="scorecard-doz">'
                         . $counter->dozScore() . '</td>';
                $div []= '<td class="scorecard-total">'
                         . $counter->totalScore() . '</td>';
                $div []= "</tr>\n";
            }
        }

        $div []= '<tr>';
        $div []= '<td class="scorecard-inessential"></td>';
        $div []= '<td class="scorecard-inessential"></td>';
        $div []= '<td colspan="12" class="scorecard-totals-label">Totals:</td>';
        $div []= '<td class="scorecard-total-hits">'
                 . $counter->totalHits() . '</td>';
        $div []= '<td class="scorecard-total-xs">'
                 . $counter->totalXs() . '</td>';
        $div []= '<td class="scorecard-total-golds">'
                 . $counter->totalGolds() . '</td>';
        $div []= '<td class="scorecard-total-doz"></td>';
        $div []= '<td class="scorecard-total-total">'
                 . $counter->totalScore() . '</td>';
        $div []= "</tr>\n";
        $div []= "</tbody>\n";
        $div []= "</table>\n";
        $div []= "</div>\n";

        return implode('', $div);
    }

    /**
     * return html representing the scorecard data as a barchart.
     *
     * @param RHACScorecardCounter $counter
     * @param array $scorecard
     *
     * @return string
     */
    private function scorecardAsBarchart($counter, $scorecard) {
        $round = GNAS_Round::getInstanceByName($scorecard['round']);
        $scoring_name = $round->getScoringNameByBow($scorecard['bow']);
        $charter = RHAC_BarchartBuilder::getBarchartBuilder($scoring_name);
        return $charter->makeBarchart($counter);
    }

    /**
     * return html containing the scorecard and its barchart
     *
     * @param int $id the scorecard id
     *
     * @return string
     */
    public function getOneScorecardAsDiv($id) {
        $scorecard = $this->getMainScorecard($id);
        $ends = $this->getScorecardEnds($id);
        $counter = new RHACScorecardCounter();
        return array("html" => '<div class="scorecard">'
                             . $this->scorecardAsTable($ends, $counter, $scorecard)
                             . '<div class="scorecard-graph">'
                             . $this->scorecardAsBarchart($counter,
                                                          $scorecard)
                             . '</div>'
                             . '</div>',
                     "scorecard_data" => $ends);
    }

    /**
     * Returns the css class for marking up the individual arrow td in the tabe.
     *
     * Giving the td a class means we can colour it appropriately using css.
     *
     * @param string $arrow the arrow (X, 10 ... M)
     * @return string
     */
    private function arrowClass($arrow) {
        switch ($arrow) {
            case 'X' :
            case '10' :
            case '9' :
                return 'arrow-gold';
            case '8' :
            case '7' :
                return 'arrow-red';
            case '6' :
            case '5' :
                return 'arrow-blue';
            case '4' :
            case '3' :
                return 'arrow-black';
            case '2' :
            case '1' :
                return 'arrow-white';
            case 'M' :
                return 'arrow-green';
            default:
                return 'arrow-unknown';
        }
    }

    /**
     * (unused) convert a date as stored in the database to a display format.
     *
     * @param string $date
     *
     * @return string
     */
    private function dateToDisplayedFormat($date) {
        $obj = date_create($date);
        if ($obj) {
            return $obj->format('D, j M Y');
        }
        else {
            wp_die("can't recognise internal date: $date");
            exit();
        }
    }

    /**
     * Runs a SELECT query on the scorecard database and returns the result.
     *
     * @param string $query the SELECT query (without the SELECT keyword)
     *
     * @return array
     */
    private function select($query, $params = array()) {
        $stmt = $this->pdo->prepare("SELECT " . $query);
        if (!$stmt) {
            die("query: [$query] failed to prepare: "
                . print_r($this->pdo->errorInfo(), true));
        }
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $rows;
    }

    /**
     * (unused) create an array mapping bow name to scoring name
     *
     * @param GNAS_Round $round
     *
     * @return array
     */
    public function makeRoundData($round) {
        $roundData = array();
        $roundData['recurve'] = $round->getScoringNameByBow('recurve');
        $roundData['compound'] = $round->getScoringNameByBow('compound');
        $roundData['barebow'] = $round->getScoringNameByBow('barebow');
        $roundData['longbow'] = $round->getScoringNameByBow('longbow');
        return $roundData;
    }
}

/**
 * function to register our code with WordPress
 */
function rhac_load_deps() {
    global $wp_scripts;
 
    /** ensure our javascript is loaded */
    wp_enqueue_script('rhac_scorecard_view',
                      plugins_url('scorecard_view.js', __FILE__),
                      array('jquery-ui-autocomplete', 'jquery'));

    /** ensure our css is loaded */
    wp_enqueue_style('scorecard_view',
                     plugins_url('scorecard_view.css', __FILE__));
 
    /** tell WP that the javascript should only load for certain pages */
    wp_localize_script('rhac_scorecard_view', 'rhacScorecardData',
                       rhac_get_scorecard_data());
}
 
/**
 * register our loader
 */
add_action('init', 'rhac_load_deps');

function rhac_get_scorecard_data() {
    $data = array();
    $data['ajaxurl'] = admin_url('admin-ajax.php');
    return $data;
}

/* hook in our ajax handlers */
add_action('wp_ajax_rhac_get_scorecards', 'rhac_ajax_get_scorecards');
add_action('wp_ajax_nopriv_rhac_get_scorecards', 'rhac_ajax_get_scorecards');

/**
 * ajax handler
 *
 * FIXME move the meat of this into a class
 */
function rhac_ajax_get_scorecards() {
    $archer = $_GET['archer'];
    $round = $_GET['round'];
    $bow = $_GET['bow'];
    $viewer = RHACScorecardViewer::getInstance();
    $scorecards = $viewer->getScorecards($archer, $round, $bow);
    list($average, $best) = rhac_average_score($scorecards);
    $rows = array();
    $extra_attributes = " id='first-scorecard'"
                      . " data-average='$average'"
                      . " data-best='$best'";
    foreach ($scorecards as $scorecard) {
        $date = preg_replace('/\//', ' ', $scorecard['date']);
        $rows []= "<tr class='scorecard-header'$extra_attributes>"
                . "<td><button type='button'"
                . " class='reveal'"
                . " id='reveal-$scorecard[scorecard_id]'"
                . " data-id='$scorecard[scorecard_id]'"
                . " data-round='$scorecard[round]'/></td>"
                . "<td>$date</td>"
                . "<td>$scorecard[archer]</td>"
                . "<td>$scorecard[round]</td>"
                . "<td>$scorecard[bow]</td>"
                . "<td class='inessential'>$scorecard[hits]</td>"
                . "<td class='inessential'>$scorecard[xs]</td>"
                . "<td class='inessential'>$scorecard[golds]</td>"
                . "<td><b>$scorecard[score]</b></td>"
                . '</tr>'
                . "\n<tr>"
                . "<td colspan='9'"
                . " id='scorecard-$scorecard[scorecard_id]'>"
                . "</td></tr>";
        $extra_attributes = "";
    }
    echo implode("\n", $rows);
    die();
}

/**
 * helper for rhac_ajax_get_scorecards
 *
 * FIXME move the meat of this into a class
 */
function rhac_average_score($scorecards) {
    $count = 0;
    $sum = 0;
    $best = 0;
    $bow = '';
    $round = '';
    $ok = true;
    foreach ($scorecards as $scorecard) {
        $count++;
        $sum += $scorecard['score'];
        if ($best < $scorecard['score']) {
            $best = $scorecard['score'];
        }
        if ($bow && $bow != $scorecard['bow']) {
            $ok = false;
            break;
        }
        if ($round && $round != $scorecard['round']) {
            $ok = false;
            break;
        }
        $bow = $scorecard['bow'];
        $round = $scorecard['round'];
    }
    if ($ok && $count > 0) {
        return array(sprintf("%d", $sum / $count), $best);
    }
    else {
        return array("", "");
    }
}

/*
 * wire in other ajax handlers
 *
 * as far as I can see these handlers are the only ones currently in use
 */
add_action('wp_ajax_rhac_get_one_scorecard', 'rhac_ajax_get_one_scorecard');
add_action('wp_ajax_nopriv_rhac_get_one_scorecard',
           'rhac_ajax_get_one_scorecard');

/*
 * return an ajax response containing a scorecard
 */
function rhac_ajax_get_one_scorecard() {
    header("Content-Type: application/json");
    $id = $_GET['scorecard_id'];
    $result = wp_cache_get($id, 'scorecard_id');
    if (!$result) {
        $viewer = RHACScorecardViewer::getInstance();
        $result = json_encode($viewer->getOneScorecardAsDiv($id));
        wp_cache_set($id, $result, 'scorecard_id');
    }
    echo $result;
    exit;
}

function rhac_make_select($name, $array, $nested=false) {
    $select = array();
    $label = ucfirst($name);
    $select []= "<span style='display: inline-block;'>";
    $select []= "<label for='$name'>$label</label>";
    $select []= "<select name='$name' id='$name'>";
    $select []= "<option value='all'>all</option>";
    if ($nested) {
        foreach ($array as $roundGroup => $names) {
            $select []= "<optgroup label='$roundGroup'>";
            foreach ($names as $option) {
                $select []= "<option value='$option'>$option</option>";
            }
            $select []= "</optgroup>";
        }
    } else {
        foreach ($array as $option) {
            $select []= "<option value='$option'>$option</option>";
        }
    }
    $select []= "</select>";
    $select []= "</span>";
    return implode("\n", $select);
}

function rhac_scorecard_viewer() {
    $viewer = RHACScorecardViewer::getInstance();
    $archers = rhac_make_select('archer', $viewer->getArchers());
    $roundGroups = array();
    foreach ($viewer->getRounds(true) as $groupName => $roundObjects) {
        $roundNames = array();
        foreach ($roundObjects as $roundObject) {
            $roundNames []= $roundObject->getName();
        }
        $roundGroups[$groupName] = $roundNames;
    }
    $rounds = rhac_make_select('round', $roundGroups, true);
    $bows = rhac_make_select('bow', array('recurve', 'compound',
                                          'longbow', 'barebow'));
    return <<<EOHTML
<div id="rhac-scorecard-viewer" data-rounds='$roundJSON'>
<h1>Score Cards</h1>
<div id="display-average"></div>
<form action="">
$archers
$rounds
$bows
<button type="button" name="search" id="search-button">Search</button>
</form>
<table class="rhac-scorecard-viewer">
<thead>
<tr><th></th>
<th>Date</th>
<th>Archer</th>
<th>Round</th>
<th>Bow</th>
<th class="inessential">Hits</th>
<th class="inessential">Xs</th>
<th class="inessential">Golds</th>
<th>Score</th></tr>
</thead>
<tbody id="results">
</tbody>
</table>
</div>
EOHTML;
}

add_shortcode('scorecard_viewer', 'rhac_scorecard_viewer');
