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
define('RHAC_PLUGINS_ROOT',
       preg_replace('/[^\/]+\/$/', '', RHAC_SCORECARD_VIEW_DIR));
define('RHAC_SCORECARD_DIR', RHAC_PLUGINS_ROOT . 'rhac-scorecards/');
define('RHAC_ROUNDS_DIR', RHAC_PLUGINS_ROOT . 'gnas-archery-rounds/');

include_once RHAC_ROUNDS_DIR . 'rounds.php';

###############################################################################

class RHACScorecardCounter {

    private $doz_hits;
    private $doz_xs;
    private $doz_golds;
    private $doz_score;
    private $end_score;
    private $total_hits;
    private $total_xs;
    private $total_golds;
    private $total_score;
    private $spread;
    private $count;

    public function __construct() {
        $this->count = 0;
        $this->spread = array();
    }

    public function debug() {
        return '<pre>' . print_r($this->spread, true) . '</pre>';
    }

    public function dozHits() { return $this->doz_hits; }
    public function dozXs() { return $this->doz_xs; }
    public function dozGolds() { return $this->doz_golds; }
    public function dozScore() { return $this->doz_score; }
    public function endScore() { return $this->end_score; }
    public function totalHits() { return $this->total_hits; }
    public function totalXs() { return $this->total_xs; }
    public function totalGolds() { return $this->total_golds; }
    public function totalScore() { return $this->total_score; }

    public function isRight() {
        return ($this->count % 2) == 0;
    }

    public function isLeft() {
        return !$this->isRight();
    }

    private function newDoz() {
        $this->doz_hits = 0;
        $this->doz_xs = 0;
        $this->doz_golds = 0;
        $this->doz_score = 0;
    }

    public function newEnd() {
        $this->end_score = 0;
        $this->count++;
        if ($this->isLeft()) {
            $this->newDoz();
        }
    }

    public function getCount($arrow) {
        if (isset($this->spread[$arrow])) {
            return $this->spread[$arrow];
        }
        else {
            return 0;
        }
    }

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

    private function arrowScore($arrow) {
        if ($arrow == 'X') {
            return 10;
        } elseif ($arrow == 'M') {
            return 0;
        } else {
            return $arrow;
        }
    }

    private function arrowHit($arrow) {
        return $arrow == 'M' ? 0 : 1;
    }

    private function arrowX($arrow) {
        return $arrow == 'X' ? 1 : 0;
    }

    private function arrowGold($arrow) {
        return $arrow == 'X' ? 1 : $arrow == 10 ? 1 : $arrow == 9 ? 1 : 0;
    }

}

###############################################################################

class RHAC_Bar {

    private $arrow;
    private $class;
    private $height;
    protected $accumulator;

    public function getHeightMultiplier() {
        return 150;
    }

    public function __construct($arrow, $class) {
        $this->arrow = $arrow;
        $this->class = $class;
    }

    public function getWidth() {
        return 1;
    }

    public function getHeight() {
        return $this->height;
    }

    public function getClass() {
        return $this->class;
    }

    public function setAccumulator($accumulator) {
        $this->accumulator = $accumulator;
    }

    public function acceptScore($counter) {
        $this->height = $counter->getCount($this->arrow);
        $this->accumulator->acceptHeight($this->height);
        $this->accumulator->acceptWidth($this->getWidth());
    }

    public function asTD() {
        $height = $this->getHeightMultiplier()
                * $this->getHeight()
                / $this->accumulator->getMaxHeight();
        $width = 100
               * $this->getWidth()
               / $this->accumulator->getTotalWidth();
        $class = $this->getClass();
        return <<<EOTD
<td class="bar" width="$width%">
<div class="bar $class" style="height: ${height}px;">
&nbsp;</div></td>
EOTD;
    }

}

class RHAC_Bar_InnerTen extends RHAC_Bar {

    public function __construct() {
        parent::__construct("10", "arrow-gold");
    }

    public function getWidth() {
        return 0.5;
    }

}

class RHAC_Bar_WideNine extends RHAC_Bar {

    public function __construct() {
        parent::__construct("9", "arrow-gold");
    }

    public function getWidth() {
        return 1.5;
    }

}

class RHAC_Bar_XTen extends RHAC_Bar {

    private $ten_height;
    private $x_height;

    public function __construct() {
    }

    public function getHeight() {
        return $this->x_height + $this->ten_height;
    }

    public function acceptScore($counter) {
        $this->ten_height = $counter->getCount('10');
        $this->x_height = $counter->getCount('X');
        $this->accumulator->acceptHeight($this->getHeight());
        $this->accumulator->acceptWidth($this->getWidth());
    }

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
            $ten_border_top = ' border-top: 2px solid white;';
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

class RHAC_Bar_Accumulator {
    private $total_width = 0;
    private $max_height = 1;

    public function acceptHeight($height) {
        if ($this->max_height < $height) {
            $this->max_height = $height;
        }
    }

    public function acceptWidth($width) {
        $this->total_width += $width;
    }

    public function getMaxHeight() {
        return $this->max_height;
    }

    public function getTotalWidth() {
        return $this->total_width;
    }

}

abstract class RHAC_BarchartBuilder {

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

    public function makeBarchart($counter) {
        $accumulator = new RHAC_Bar_Accumulator();
        $bars = $this->analyseBars($counter, $accumulator);
        return  $this->buildHTML($bars, $accumulator);
    }

    private function analyseBars($counter, $accumulator) {
        $bars = $this->makeArray();
        foreach ($bars as $bar) {
            $bar->setAccumulator($accumulator);
            $bar->acceptScore($counter);
        }
        return $bars;
    }

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

    protected abstract function makeArray();

}

class RHAC_FiveZoneBarchartBuilder extends RHAC_BarchartBuilder {
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

class RHAC_TenZoneBarchartBuilder extends RHAC_BarchartBuilder {
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

class RHAC_InnerTenZoneBarchartBuilder extends RHAC_BarchartBuilder {
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

class RHAC_VegasBarchartBuilder extends RHAC_BarchartBuilder {
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

class RHAC_VegasInnerTenBarchartBuilder extends RHAC_BarchartBuilder {
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

class RHAC_WorcesterBarchartBuilder extends RHAC_BarchartBuilder {
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

class RHAC_UnknownBarchartBuilder extends RHAC_BarchartBuilder {
    private $name;

    protected function makeArray() {}

    public function __construct($name) {
        $this->name = $name;
    }

    public function makeBarchart($counter) {
        return "<p class='error'>Unrecognised scoring: ["
               . $this->name
               . "]</p>";
    }
}

###############################################################################

class RHACScorecardViewer {
    private $pdo;
    private static $instance;
    private $archers;
    private $rounds;

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

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getArchers() {
        if (!isset($this->archers)) {
            $this->archers = array();
            $archers = $this->select("name from archer order by name");
            foreach ($archers as $archer) {
                $this->archers []= $archer['name'];
            }
        }
        return $this->archers;
    }

    public function getRounds($nested = false) {
        if (!isset($this->rounds)) {
            $this->rounds = GNAS_Page::roundData($nested);
        }
        return $this->rounds;
    }

    public function getScorecards($archer, $round, $bow) {
        $conditions = array();
        $conditions []= '1 = 1';
        $params = array();
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

    private function getScorecardEnds($id) {
        return $this->select("*"
                      . " FROM scorecard_end"
                      . " WHERE scorecard_id = ?"
                      . " ORDER BY end_number",
                      array($id));
    }

    private function getMainScorecard($id) {
        $rows = $this->select("*"
                      . " FROM scorecards"
                      . " WHERE scorecard_id = ?",
                      array($id));
        return $rows[0];
    }

    private function getHandicapForScore($scorecard) {
        $compound = $scorecard["bow"] == "compound" ? "Y" : "N";
        $rows = $this->select("min(handicap)"
                            . " from round_handicaps"
                            . " where round = ? and compound = ? and score <= ?",
                            array($scorecard["round"], $compound, $scorecard["score"]));
        return $rows[0]["handicap"];
    }

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
            $counter->newEnd();
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
        $div []= "<p>Handicap rating: " . $this->getHandicapForScore($scorecard) . "</p>";
        $div []= "</div>\n";

        return implode('', $div);
    }

    private function scorecardAsBarchart($counter, $scorecard) {
        $round = GNAS_Round::getInstanceByName($scorecard['round']);
        $scoring_name = $round->getScoringNameByBow($scorecard['bow']);
        $charter = RHAC_BarchartBuilder::getBarchartBuilder($scoring_name);
        return $charter->makeBarchart($counter);
    }

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

    public function makeRoundData($round) {
        $roundData = array();
        $roundData['recurve'] = $round->getScoringNameByBow('recurve');
        $roundData['compound'] = $round->getScoringNameByBow('compound');
        $roundData['barebow'] = $round->getScoringNameByBow('barebow');
        $roundData['longbow'] = $round->getScoringNameByBow('longbow');
        return $roundData;
    }
}

###############################################################################

function rhac_load_deps() {
    global $wp_scripts;
 
    wp_enqueue_script('rhac_scorecard_view',
                      plugins_url('scorecard_view.js', __FILE__),
                      array('jquery-ui-autocomplete', 'jquery'));

    wp_enqueue_style('scorecard_view',
                     plugins_url('scorecard_view.css', __FILE__));
 
    $ui = $wp_scripts->query('jquery-ui-core');
 
    $protocol = is_ssl() ? 'https' : 'http';
    $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/redmond/jquery-ui.min.css";
    wp_enqueue_style('jquery-ui-redmond', $url, false, null);
    wp_localize_script('rhac_scorecard_view', 'rhacScorecardData',
                       rhac_get_scorecard_data());
}
 
add_action('init', 'rhac_load_deps');

function rhac_get_scorecard_data() {
    $data = array();
    $data['ajaxurl'] = admin_url('admin-ajax.php');
    return $data;
}

add_action('wp_ajax_rhac_get_scorecards', 'rhac_ajax_get_scorecards');
add_action('wp_ajax_nopriv_rhac_get_scorecards', 'rhac_ajax_get_scorecards');

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

add_action('wp_ajax_rhac_get_one_scorecard', 'rhac_ajax_get_one_scorecard');
add_action('wp_ajax_nopriv_rhac_get_one_scorecard',
           'rhac_ajax_get_one_scorecard');

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
