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

abstract class RHAC_Charter {

    public static function getCharter($scoring_name) {
        switch ($scoring_name) {
            case "five zone":
                return new RHAC_FiveZoneCharter();
            case "ten zone":
                return new RHAC_TenZoneCharter();
            case "metric inner ten":
                return new RHAC_InnerTenZoneCharter();
            case "vegas":
                return new RHAC_VegasCharter();
            case "vegas inner ten":
                return new RHAC_VegasInnerTenCharter();
            case "worcester":
                return new RHAC_WorcesterCharter();
            default:
                return new RHAC_UnknownCharter($scoring_name);
        }
    }

    public function makeBarchart($counter) {
        $array = $this->makeArray();
        $max_height = 1;
        $total_width = 0;
        foreach ($array as &$bar) {
            $bar['height'] = $counter->getCount($bar['arrow']);
            if ($max_height < $bar['height']) {
                $max_height = $bar['height'];
            }
            $total_width += $bar['width'];
        }
        unset($bar);
        $html = array();
        $html []= '<table style="width: '
                 . 100 * $total_width / 11
                 . '%"><tr>';
        foreach ($array as $bar) {
            $height = 150 * $bar['height'] / $max_height;
            $width = 100 * $bar['width'] / $total_width;
            $html []= '<td class="bar" width="' . $width . '%">'
                        . '<div class="bar '
                        . $bar['class']
                        . '" style="height: ' . $height . 'px;">'
                        . '&nbsp;</div></td>';
        }
        $html []= "</tr></table>\n";
        return implode('', $html);
    }

    protected abstract function makeArray();
}

class RHAC_FiveZoneCharter extends RHAC_Charter {
    protected function makeArray() {
        return array(
            array( "arrow" => '9', "width" => 1, "class" => 'arrow-gold'),
            array( 'arrow' => '7', 'width' => 1, 'class' => 'arrow-red'),
            array( 'arrow' => '5', 'width' => 1, 'class' => 'arrow-blue'),
            array( 'arrow' => '3', 'width' => 1, 'class' => 'arrow-black'),
            array( 'arrow' => '1', 'width' => 1, 'class' => 'arrow-white'),
            array( 'arrow' => 'M', 'width' => 1, 'class' => 'arrow-miss')
        );
    }
}

class RHAC_TenZoneCharter extends RHAC_Charter {
    protected function makeArray() {
        return array(
            array( 'arrow' => 'X', 'width' => 0.5, 'class' => 'arrow-gold'),
            array( 'arrow' => '10', 'width' => 0.5, 'class' => 'arrow-gold'),
            array( 'arrow' => '9', 'width' => 1, 'class' => 'arrow-gold'),
            array( 'arrow' => '8', 'width' => 1, 'class' => 'arrow-red'),
            array( 'arrow' => '7', 'width' => 1, 'class' => 'arrow-red'),
            array( 'arrow' => '6', 'width' => 1, 'class' => 'arrow-blue'),
            array( 'arrow' => '5', 'width' => 1, 'class' => 'arrow-blue'),
            array( 'arrow' => '4', 'width' => 1, 'class' => 'arrow-black'),
            array( 'arrow' => '3', 'width' => 1, 'class' => 'arrow-black'),
            array( 'arrow' => '2', 'width' => 1, 'class' => 'arrow-white'),
            array( 'arrow' => '1', 'width' => 1, 'class' => 'arrow-white'),
            array( 'arrow' => 'M', 'width' => 1, 'class' => 'arrow-miss'),
        );
    }
}

class RHAC_InnerTenZoneCharter extends RHAC_Charter {
    protected function makeArray() {
        return array(
            array( 'arrow' => '10', 'width' => 0.5, 'class' => 'arrow-gold'),
            array( 'arrow' => '9', 'width' => 1.5, 'class' => 'arrow-gold'),
            array( 'arrow' => '8', 'width' => 1, 'class' => 'arrow-red'),
            array( 'arrow' => '7', 'width' => 1, 'class' => 'arrow-red'),
            array( 'arrow' => '6', 'width' => 1, 'class' => 'arrow-blue'),
            array( 'arrow' => '5', 'width' => 1, 'class' => 'arrow-blue'),
            array( 'arrow' => '4', 'width' => 1, 'class' => 'arrow-black'),
            array( 'arrow' => '3', 'width' => 1, 'class' => 'arrow-black'),
            array( 'arrow' => '2', 'width' => 1, 'class' => 'arrow-white'),
            array( 'arrow' => '1', 'width' => 1, 'class' => 'arrow-white'),
            array( 'arrow' => 'M', 'width' => 1, 'class' => 'arrow-miss'),
        );
    }
}

class RHAC_VegasCharter extends RHAC_Charter {
    protected function makeArray() {
        return array(
            array( 'arrow' => 'X', 'width' => 0.5, 'class' => 'arrow-gold'),
            array( 'arrow' => '10', 'width' => 0.5, 'class' => 'arrow-gold'),
            array( 'arrow' => '9', 'width' => 1, 'class' => 'arrow-gold'),
            array( 'arrow' => '8', 'width' => 1, 'class' => 'arrow-red'),
            array( 'arrow' => '7', 'width' => 1, 'class' => 'arrow-red'),
            array( 'arrow' => '6', 'width' => 1, 'class' => 'arrow-blue'),
            array( 'arrow' => 'M', 'width' => 1, 'class' => 'arrow-miss'),
        );
    }
}

class RHAC_VegasInnerTenCharter extends RHAC_Charter {
    protected function makeArray() {
        return array(
            array( 'arrow' => '10', 'width' => 0.5, 'class' => 'arrow-gold'),
            array( 'arrow' => '9', 'width' => 1.5, 'class' => 'arrow-gold'),
            array( 'arrow' => '8', 'width' => 1, 'class' => 'arrow-red'),
            array( 'arrow' => '7', 'width' => 1, 'class' => 'arrow-red'),
            array( 'arrow' => '6', 'width' => 1, 'class' => 'arrow-blue'),
            array( 'arrow' => 'M', 'width' => 1, 'class' => 'arrow-miss'),
        );
    }
}

class RHAC_WorcesterCharter extends RHAC_Charter {
    protected function makeArray() {
        return array(
            array( 'arrow' => '5', 'width' => 1, 'class' => 'arrow-white'),
            array( 'arrow' => '4', 'width' => 1, 'class' => 'arrow-black'),
            array( 'arrow' => '3', 'width' => 1, 'class' => 'arrow-black'),
            array( 'arrow' => '2', 'width' => 1, 'class' => 'arrow-black'),
            array( 'arrow' => '1', 'width' => 1, 'class' => 'arrow-black'),
            array( 'arrow' => 'M', 'width' => 1, 'class' => 'arrow-miss'),
        );
    }
}

class RHAC_UnknownCharter extends RHAC_Charter {
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

    public function getRounds() {
        if (!isset($this->rounds)) {
            $this->rounds = GNAS_Page::roundData();
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

    private function scorecardAsTable($ends, $counter) {
        $table = array();
        $arrow_keys = array('arrow_1', 'arrow_2', 'arrow_3',
                            'arrow_4', 'arrow_5', 'arrow_6');
        $table []= "<div class='scorecard-table'>\n";
        $table []= "<table>\n";
        $table []= "<thead>\n";
        $table []= "<tr>";
        for ($i = 0; $i < 2; ++$i) {
            foreach (array(1, 2, 3, 4, 5, 6, 'END') as $th) {
                $table []= "<th>$th</th>";
            }
        }
        foreach (array('HITS', 'XS', 'GOLDS', 'DOZ', 'TOT') as $th) {
            $table []= "<th>$th</th>";
        }
        $table []= "</tr>\n</thead>\n<tbody>";

        foreach ($ends as $end_data) {
            $counter->newEnd();
            if ($counter->isLeft()) {
                $table []= '<tr>';
            }
            foreach ($arrow_keys as $key) {
                $arrow = $end_data[$key];
                $counter->add($arrow);
                $table []= '<td class="arrow '
                         . $this->arrowClass($arrow) . '">'
                         . $arrow . '</td>';
            }
            $table []= '<td class="end-total">'
                     . $counter->endScore()
                     . '</td>';
            if ($counter->isRight()) {
                $table []= '<td class="scorecard-hits">'
                         . $counter->dozHits() . '</td>';
                $table []= '<td class="scorecard-xs">'
                         . $counter->dozXs() . '</td>';
                $table []= '<td class="scorecard-golds">'
                         . $counter->dozGolds() . '</td>';
                $table []= '<td class="scorecard-doz">'
                         . $counter->dozScore() . '</td>';
                $table []= '<td class="scorecard-total">'
                         . $counter->totalScore() . '</td>';
                $table []= "</tr>\n";
            }
        }

        $table []= '<tr>';
        $table []= '<td colspan="14" class="scorecard-totals-label">'
                 . 'Totals:</td>';
        $table []= '<td class="scorecard-total-hits">'
                 . $counter->totalHits() . '</td>';
        $table []= '<td class="scorecard-total-xs">'
                 . $counter->totalXs() . '</td>';
        $table []= '<td class="scorecard-total-golds">'
                 . $counter->totalGolds() . '</td>';
        $table []= '<td></td>';
        $table []= '<td class="scorecard-total-total">'
                 . $counter->totalScore() . '</td>';
        $table []= "</tr>\n";
        $table []= "</tbody>\n";
        $table []= "</table>\n";
        $table []= "</div>\n";

        return implode('', $table);
    }

    private function scorecardAsBarchart($counter, $scorecard) {
        $round = GNAS_Round::getInstanceByName($scorecard['round']);
        $scoring_name = $round->getScoringNameByBow($scorecard['bow']);
        $charter = RHAC_Charter::getCharter($scoring_name);
        return $charter->makeBarchart($counter);
    }

    public function getOneScorecardAsDiv($id) {
        $scorecard = $this->getMainScorecard($id);
        $ends = $this->getScorecardEnds($id);
        $counter = new RHACScorecardCounter();
        return array("html" => '<div class="scorecard">'
                             . $this->scorecardAsTable($ends, $counter)
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

wp_enqueue_script('rhac_scorecard_view',
                  plugins_url('scorecard_view.js', __FILE__),
                  array('jquery'));

wp_localize_script('rhac_scorecard_view', 'rhacScorecardData',
                   rhac_get_scorecard_data());

wp_enqueue_style('scorecard_view',
                 plugins_url('scorecard_view.css', __FILE__));

function rhac_get_scorecard_data() {
    $data = array();
    $data['ajaxurl'] = admin_url('admin-ajax.php');
    return $data;
}

add_action('wp_ajax_rhac_get_scorecards', 'rhac_ajax_get_scorecards');
add_action('wp_ajax_nopriv_rhac_get_scorecards', 'rhac_ajax_get_scorecards');

function rhac_ajax_get_scorecards() {
    $archer = $_POST['archer'];
    $round = $_POST['round'];
    $bow = $_POST['bow'];
    $viewer = RHACScorecardViewer::getInstance();
    $scorecards = $viewer->getScorecards($archer, $round, $bow);
    list($average, $best) = rhac_average_score($scorecards);
    $rows = array();
    $extra_attributes = " id='first-scorecard'"
                      . " data-average='$average'"
                      . " data-best='$best'";
    foreach ($scorecards as $scorecard) {
        $rows []= "<tr class='scorecard-header'$extra_attributes>"
                . "<td><button type='button'"
                . " class='reveal'"
                . " id='reveal-$scorecard[scorecard_id]'"
                . " data-id='$scorecard[scorecard_id]'"
                . " data-round='$scorecard[round]'/></td>"
                . "<td>$scorecard[date]</td>"
                . "<td>$scorecard[archer]</td>"
                . "<td>$scorecard[round]</td>"
                . "<td>$scorecard[bow]</td>"
                . "<td>$scorecard[hits]</td>"
                . "<td>$scorecard[xs]</td>"
                . "<td>$scorecard[golds]</td>"
                . "<td>$scorecard[score]</td>"
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

function rhac_make_select($name, $array) {
    $select = array();
    $label = ucfirst($name);
    $select []= "<label for='$name'>$label</label>";
    $select []= "<select name='$name' id='$name'>";
    $select []= "<option value='all'>all</option>";
    foreach ($array as $option) {
        $select []= "<option value='$option'>$option</option>";
    }
    $select []= "</select>";
    return implode("\n", $select);
}

function rhac_scorecard_viewer() {
    $viewer = RHACScorecardViewer::getInstance();
    $archers = rhac_make_select('archer', $viewer->getArchers());
    $roundNames = array();
    foreach ($viewer->getRounds() as $roundObject) {
        $roundNames []= $roundObject->getName();
    }
    $rounds = rhac_make_select('round', $roundNames);
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
<th>Hits</th>
<th>Xs</th>
<th>Golds</th>
<th>Score</th></tr>
</thead>
<tbody id="results">
</tbody>
</table>
</div>
EOHTML;
}

add_shortcode('scorecard_viewer', 'rhac_scorecard_viewer');
