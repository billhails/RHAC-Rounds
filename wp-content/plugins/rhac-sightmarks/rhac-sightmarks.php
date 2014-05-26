<?php
/*
Plugin Name: RHAC Sightmarks Calculator
Description: Calculate, save and edit archery sightmarks.
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

define('RHAC_SIGHTMARKS_DIR', plugin_dir_path(__FILE__));
include_once RHAC_SIGHTMARKS_DIR . 'RHAC_Sightmarks.php';

function rhac_load_deps() {
    global $wp_scripts;
 
    wp_enqueue_script('rhac_scorecard_view',
                      plugins_url('scorecard_view.js', __FILE__),
                      array('jquery-ui-autocomplete', 'jquery'));

    wp_enqueue_style('scorecard_view',
                     plugins_url('scorecard_view.css', __FILE__));
 
    $ui = $wp_scripts->query('jquery-ui-core');
 
    /*
    $protocol = is_ssl() ? 'https' : 'http';
    $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/redmond/jquery-ui.min.css";
    wp_enqueue_style('jquery-ui-redmond', $url, false, null);
    */
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
