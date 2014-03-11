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
define('RHAC_PLUGINS_ROOT', preg_replace('/[^\/]+\/$/', '', RHAC_SCORECARD_VIEW_DIR));
define('RHAC_SCORECARD_DIR', RHAC_PLUGINS_ROOT . 'rhac-scorecards/');
define('RHAC_ROUNDS_DIR', RHAC_PLUGINS_ROOT . 'gnas-archery-rounds/');

include_once RHAC_ROUNDS_DIR . 'rounds.php';

#######################################################################################

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

    public function getScorecards($archer, $round) {
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
        return $this->select("* FROM scorecards WHERE "
                           . implode(' AND ', $conditions)
                           . ' ORDER BY date', $params);
    }

    public function getOneScorecard($id) {
        return $this->select("*"
                      . " FROM scorecard_end"
                      . " WHERE scorecard_id = ?"
                      . " ORDER BY end_number",
                      array($id));
    }

    public function getOneScorecardAsTable($id, $round) {
        $scorecard = $this->getOneScorecard($id);
        $ends = array();
        $count = 0;
        foreach ($scorecard as $end_data) {
            $end_html = array();
            $end_total = 0;
            $end_hits = 0;
            $end_xs = 0;
            $end_golds = 0;
            for ($arrow = 1; $arrow < 7; ++$arrow) {
                $key = 'arrow_' . $arrow;
                $arrow_text = $end_data[$key];
                $end_total += $this->arrowValue($arrow_text);
                $end_hits += $this->arrowHit($arrow_text);
                $end_xs += $this->arrowX($arrow_text);
                $end_golds += $this->arrowGold($arrow_text);
                $arrow_class = $this->arrowClass($arrow_text);
                $end_html []= "<td class='arrow $arrow_class'>$arrow_text</td>";
            }
            $end_html []= "<td class='end-total'>$end_total</td>";
            $ends []= array('end_html' => implode('', $end_html),
                            'end_total' => $end_total,
                            'even' => ($count % 2 == 0),
                            'hits' => $end_hits,
                            'xs' => $end_xs,
                            'golds' => $end_golds);
            $count++;
        }
        $table = array();
        $table []= <<<EOHTML
<div class='scorecard'>
<table>
<thead>
<tr>
<th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>end</th>
<th>1</th><th>2</th><th>3</th><th>4</th><th>5</th><th>6</th><th>end</th>
<th>hits</th><th>Xs</th><th>golds</th><th>doz</th><th>tot</th>
</tr>
</thead>
<tbody>
EOHTML;
        $total = 0;
        foreach ($ends as $end) {
            if ($end['even']) {
                $table []= '<tr>';
                $hits = 0;
                $xs = 0;
                $golds = 0;
                $doz = 0;
            }
            $table []= $end['end_html'];
            $doz += $end['end_total'];
            $total += $end['end_total'];
            $hits += $end['hits'];
            $total_hits += $end['hits'];
            $xs += $end['xs'];
            $total_xs += $end['xs'];
            $golds += $end['golds'];
            $total_golds += $end['golds'];
            if (!$end['even']) {
                $table []= "<td class='scorecard-hits'>$hits</td>"
                . "<td class='scorecard-xs'>$xs</td>"
                . "<td class='scorecard-golds'>$golds</td>"
                . "<td class='scorecard-doz'>$doz</td>"
                . "<td class='scorecard-total'>$total</td></tr>";
            }
        }
        $table []= <<<EOHTML
<tr>
<td colspan="14" class='scorecard-totals-label'>Totals:</td>
<td class='scorecard-total-hits'>$total_hits</td>
<td class='scorecard-total-xs'>$total_xs</td>
<td class='scorecard-total-golds'>$total_golds</td>
<td></td>
<td class='scorecard-total-total'>$total</td>
</tr>
</tbody>
</table>
</div>

EOHTML;
        return implode("\n", $table);
    }

    private function arrowValue($arrow) {
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
}

#######################################################################################

wp_enqueue_script('rhac_scorecard_view',
                  plugins_url('scorecard_view.js', __FILE__),
                  array('jquery'));

wp_localize_script('rhac_scorecard_view', 'rhacScorecardData', rhac_get_scorecard_data());

wp_enqueue_style('scorecard_view', plugins_url('scorecard_view.css', __FILE__));

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
    $viewer = RHACScorecardViewer::getInstance();
    $scorecards = $viewer->getScorecards($archer, $round);
    $rows = array();
    foreach ($scorecards as $scorecard) {
        $rows []= '<tr class="scorecard-header">'
                . "<td><button type='button' class='reveal' data-id='$scorecard[scorecard_id]' data-round='$scorecard[round]'/></td>"
                . "<td>$scorecard[date]</td>"
                . "<td>$scorecard[archer]</td>"
                . "<td>$scorecard[round]</td>"
                . "<td>$scorecard[bow]</td>"
                . "<td>$scorecard[hits]</td>"
                . "<td>$scorecard[xs]</td>"
                . "<td>$scorecard[golds]</td>"
                . "<td>$scorecard[score]</td>"
                . '</tr>'
                . "\n<tr><td colspan='9' id='scorecard-$scorecard[scorecard_id]'></td></tr>";
    }
    echo implode("\n", $rows);
    die();
}

add_action('wp_ajax_rhac_get_one_scorecard', 'rhac_ajax_get_one_scorecard');
add_action('wp_ajax_nopriv_rhac_get_one_scorecard', 'rhac_ajax_get_one_scorecard');

function rhac_ajax_get_one_scorecard() {
    $id = $_POST['scorecard_id'];
    $round = $_POST['round'];
    $viewer = RHACScorecardViewer::getInstance();
    echo $viewer->getOneScorecardAsTable($id, $round);
    die();
}

function rhac_scorecard_viewer() {
    $viewer = RHACScorecardViewer::getInstance();
    $archers = array();
    $archers []= "<option value='all'>all</option>";
    foreach ($viewer->getArchers() as $archer) {
        $archers []= "<option value='$archer'>$archer</option>";
    }
    $archers = implode("\n", $archers);
    $rounds = array();
    $rounds []= "<option value='all'>all</option>";
    foreach ($viewer->getRounds() as $round) {
        $name = $round->getName();
        $rounds []= "<option value='$name'>$name</option>";
    }
    $rounds = implode("\n", $rounds);
    return <<<EOHTML
<div id="rhac-scorecard-viewer">
<h1>Score Cards</h1>
<form action="">
<label for="archer">Archer</label>
<select name="archer" id="archer">
$archers
</select>
<label for="round">Round</label>
<select name="round" id="round">
$rounds
</select>
<button type="button" name="search" id="search-button">Search</button>
</form>
<table class="rhac-scorecard-viewer">
<thead>
<tr>
<th></th><th>Date</th><th>Archer</th><th>Round</th><th>Bow</th><th>Hits</th><th>Xs</th><th>Golds</th><th>Score</th></tr>
</thead>
<tbody id="results">
</tbody>
</table>
</div>
EOHTML;
}

add_shortcode('scorecard_viewer', 'rhac_scorecard_viewer');
