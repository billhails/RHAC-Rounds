<?php
/*
Plugin Name: RHAC Rounds Explorer
Description: read-only view of archery records, requires rhac-scorecards plugin
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: GPL
*/

define('RHAC_RE_DIR', plugin_dir_path(__FILE__));

define('RHAC_PLUGINS_ROOT',
       preg_replace('/[^\/]+\/$/', '', RHAC_RE_DIR));

define('RHAC_RE_SCORECARD_DIR', RHAC_PLUGINS_ROOT . 'rhac-scorecards/');

define('RHAC_RE_PLUGIN_URL_ROOT', plugin_dir_url(__FILE__));

define('RHAC_PLUGINS_URL_ROOT',
       preg_replace('/[^\/]+\/$/', '', RHAC_RE_PLUGIN_URL_ROOT));


include_once RHAC_RE_DIR . 'RHAC_RecordsViewer.php';

function rhac_re_load_deps() {
    global $wp_scripts;
 
    wp_enqueue_script('rhac_records_view',
                      plugins_url('rhac_records_view.js', __FILE__),
                      array('jquery-ui-datepicker', 'jquery-ui-tooltip', 'jquery-ui-accordion'));

    wp_enqueue_script('rhac_datatables', RHAC_PLUGINS_URL_ROOT . 'gnas-archery-rounds/jquery.dataTables.min.js', array('jquery-ui-core'));
    wp_enqueue_script('rhac_datatables_jquery', RHAC_PLUGINS_URL_ROOT . 'gnas-archery-rounds/dataTables.jqueryui.js', array('jquery-ui-core'));
    wp_enqueue_style('rhac_datatables', RHAC_PLUGINS_URL_ROOT . 'gnas-archery-rounds/jquery.dataTables.min.css');
    wp_enqueue_style('rhac_datatables_jquery', RHAC_PLUGINS_URL_ROOT . 'gnas-archery-rounds/dataTables.jqueryui.css');
    wp_enqueue_style('rhac_records_view',
                     plugins_url('rhac_records_view.css', __FILE__));
 
    $ui = $wp_scripts->query('jquery-ui-core');
 
    $protocol = is_ssl() ? 'https' : 'http';
    $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/redmond/jquery-ui.min.css";
    wp_enqueue_style('jquery-ui-redmond', $url, false, null);
    wp_localize_script('rhac_records_view', 'rhacRoundExplorerData', rhac_get_data());
}

function rhac_get_data() {
    $data = array();
    $data['ajaxurl'] = admin_url('admin-ajax.php');
    return $data;
}

add_action('init', 'rhac_re_load_deps');

function rhac_ajax_display_results() {
    // error_log("rhac_ajax_display_results");
    $viewer = RHAC_RecordsViewer::getInstance();
    echo $viewer->display();
    exit;
}

add_action('wp_ajax_rhac_display_results', 'rhac_ajax_display_results');
add_action('wp_ajax_nopriv_rhac_display_results', 'rhac_ajax_display_results');

function rhac_records_viewer($atts) {
    // error_log("rhac_records_viewer");
    extract( shortcode_atts( array('help_page_id' => ''), $atts ) );
    $viewer = RHAC_RecordsViewer::getInstance();
    return $viewer->view($help_page_id);
}

add_shortcode('records_viewer', 'rhac_records_viewer');

// Icons

function rhac_personalBestIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->personalBestIcon();
}

add_shortcode('personal_best_icon', 'rhac_personalBestIcon');

function rhac_currentClubRecordIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->currentClubRecordIcon();
}

add_shortcode('current_club_record_icon', 'rhac_currentClubRecordIcon');

function rhac_oldClubRecordIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->oldClubRecordIcon();
}

add_shortcode('old_club_record_icon', 'rhac_oldClubRecordIcon');

function rhac_greenTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->greenTwoFiveTwoIcon();
}

add_shortcode('green_two_five_two_icon', 'rhac_greenTwoFiveTwoIcon');

function rhac_halfGreenTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->halfGreenTwoFiveTwoIcon();
}

add_shortcode('half_green_two_five_two_icon', 'rhac_halfGreenTwoFiveTwoIcon');

function rhac_whiteTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->whiteTwoFiveTwoIcon();
}

add_shortcode('white_two_five_two_icon', 'rhac_whiteTwoFiveTwoIcon');

function rhac_halfWhiteTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->halfWhiteTwoFiveTwoIcon();
}

add_shortcode('half_white_two_five_two_icon', 'rhac_halfWhiteTwoFiveTwoIcon');

function rhac_blackTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->blackTwoFiveTwoIcon();
}

add_shortcode('black_two_five_two_icon', 'rhac_blackTwoFiveTwoIcon');

function rhac_halfBlackTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->halfBlackTwoFiveTwoIcon();
}

add_shortcode('half_black_two_five_two_icon', 'rhac_halfBlackTwoFiveTwoIcon');

function rhac_blueTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->blueTwoFiveTwoIcon();
}

add_shortcode('blue_two_five_two_icon', 'rhac_blueTwoFiveTwoIcon');

function rhac_halfBlueTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->halfBlueTwoFiveTwoIcon();
}

add_shortcode('half_blue_two_five_two_icon', 'rhac_halfBlueTwoFiveTwoIcon');

function rhac_redTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->redTwoFiveTwoIcon();
}

add_shortcode('red_two_five_two_icon', 'rhac_redTwoFiveTwoIcon');

function rhac_halfRedTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->halfRedTwoFiveTwoIcon();
}

add_shortcode('half_red_two_five_two_icon', 'rhac_halfRedTwoFiveTwoIcon');

function rhac_bronzeTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->bronzeTwoFiveTwoIcon();
}

add_shortcode('bronze_two_five_two_icon', 'rhac_bronzeTwoFiveTwoIcon');

function rhac_halfBronzeTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->halfBronzeTwoFiveTwoIcon();
}

add_shortcode('half_bronze_two_five_two_icon', 'rhac_halfBronzeTwoFiveTwoIcon');

function rhac_silverTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->silverTwoFiveTwoIcon();
}

add_shortcode('silver_two_five_two_icon', 'rhac_silverTwoFiveTwoIcon');

function rhac_halfSilverTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->halfSilverTwoFiveTwoIcon();
}

add_shortcode('half_silver_two_five_two_icon', 'rhac_halfSilverTwoFiveTwoIcon');

function rhac_goldTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->goldTwoFiveTwoIcon();
}

add_shortcode('gold_two_five_two_icon', 'rhac_goldTwoFiveTwoIcon');

function rhac_halfGoldTwoFiveTwoIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->halfGoldTwoFiveTwoIcon();
}

add_shortcode('half_gold_two_five_two_icon', 'rhac_halfGoldTwoFiveTwoIcon');

function rhac_bronzeMedalIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->bronzeMedalIcon();
}

add_shortcode('bronze_medal_icon', 'rhac_bronzeMedalIcon');

function rhac_silverMedalIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->silverMedalIcon();
}

add_shortcode('silver_medal_icon', 'rhac_silverMedalIcon');

function rhac_goldMedalIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->goldMedalIcon();
}

add_shortcode('gold_medal_icon', 'rhac_goldMedalIcon');

function rhac_archerClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->archerClassificationIcon();
}

add_shortcode('archer_classification_icon', 'rhac_archerClassificationIcon');

function rhac_unclassifiedClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->unclassifiedClassificationIcon();
}

add_shortcode('unclassified_classification_icon', 'rhac_unclassifiedClassificationIcon');

function rhac_thirdClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->thirdClassificationIcon();
}

add_shortcode('third_classification_icon', 'rhac_thirdClassificationIcon');

function rhac_secondClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->secondClassificationIcon();
}

add_shortcode('second_classification_icon', 'rhac_secondClassificationIcon');

function rhac_firstClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->firstClassificationIcon();
}

add_shortcode('first_classification_icon', 'rhac_firstClassificationIcon');

function rhac_bmClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->bmClassificationIcon();
}

add_shortcode('bm_classification_icon', 'rhac_bmClassificationIcon');

function rhac_mbmClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->mbmClassificationIcon();
}

add_shortcode('mbm_classification_icon', 'rhac_mbmClassificationIcon');

function rhac_gmbmClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->gmbmClassificationIcon();
}

add_shortcode('gmbm_classification_icon', 'rhac_gmbmClassificationIcon');

function rhac_aClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->aClassificationIcon();
}

add_shortcode('a_classification_icon', 'rhac_aClassificationIcon');

function rhac_bClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->bClassificationIcon();
}

add_shortcode('b_classification_icon', 'rhac_bClassificationIcon');

function rhac_cClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->cClassificationIcon();
}

add_shortcode('c_classification_icon', 'rhac_cClassificationIcon');

function rhac_dClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->dClassificationIcon();
}

add_shortcode('d_classification_icon', 'rhac_dClassificationIcon');

function rhac_eClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->eClassificationIcon();
}

add_shortcode('e_classification_icon', 'rhac_eClassificationIcon');

function rhac_fClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->fClassificationIcon();
}

add_shortcode('f_classification_icon', 'rhac_fClassificationIcon');

function rhac_gClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->gClassificationIcon();
}

add_shortcode('g_classification_icon', 'rhac_gClassificationIcon');

function rhac_hClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->hClassificationIcon();
}

add_shortcode('h_classification_icon', 'rhac_hClassificationIcon');

function rhac_confirmedArcherClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedArcherClassificationIcon();
}

add_shortcode('confirmed_archer_classification_icon', 'rhac_confirmedArcherClassificationIcon');

function rhac_confirmedUnclassifiedClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedUnclassifiedClassificationIcon();
}

add_shortcode('confirmed_unclassified_classification_icon', 'rhac_confirmedUnclassifiedClassificationIcon');

function rhac_confirmedThirdClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedThirdClassificationIcon();
}

add_shortcode('confirmed_third_classification_icon', 'rhac_confirmedThirdClassificationIcon');

function rhac_confirmedSecondClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedSecondClassificationIcon();
}

add_shortcode('confirmed_second_classification_icon', 'rhac_confirmedSecondClassificationIcon');

function rhac_confirmedFirstClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedFirstClassificationIcon();
}

add_shortcode('confirmed_first_classification_icon', 'rhac_confirmedFirstClassificationIcon');

function rhac_confirmedBmClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedBmClassificationIcon();
}

add_shortcode('confirmed_bm_classification_icon', 'rhac_confirmedBmClassificationIcon');

function rhac_confirmedMbmClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedMbmClassificationIcon();
}

add_shortcode('confirmed_mbm_classification_icon', 'rhac_confirmedMbmClassificationIcon');

function rhac_confirmedGmbmClassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedGmbmClassificationIcon();
}

add_shortcode('confirmed_gmbm_classification_icon', 'rhac_confirmedGmbmClassificationIcon');

function rhac_confirmedAclassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedAclassificationIcon();
}

add_shortcode('confirmed_a_classification_icon', 'rhac_confirmedAclassificationIcon');

function rhac_confirmedBclassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedBclassificationIcon();
}

add_shortcode('confirmed_b_classification_icon', 'rhac_confirmedBclassificationIcon');

function rhac_confirmedCclassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedCclassificationIcon();
}

add_shortcode('confirmed_c_classification_icon', 'rhac_confirmedCclassificationIcon');

function rhac_confirmedDclassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedDclassificationIcon();
}

add_shortcode('confirmed_d_classification_icon', 'rhac_confirmedDclassificationIcon');

function rhac_confirmedEclassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedEclassificationIcon();
}

add_shortcode('confirmed_e_classification_icon', 'rhac_confirmedEclassificationIcon');

function rhac_confirmedFclassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedFclassificationIcon();
}

add_shortcode('confirmed_f_classification_icon', 'rhac_confirmedFclassificationIcon');

function rhac_confirmedGclassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedGclassificationIcon();
}

add_shortcode('confirmed_g_classification_icon', 'rhac_confirmedGclassificationIcon');

function rhac_confirmedHclassificationIcon() {
    $viewer = RHAC_RecordsViewer::getInstance();
    $viewer->initDisplayMaps();
    return $viewer->confirmedHclassificationIcon();
}

add_shortcode('confirmed_h_classification_icon', 'rhac_confirmedHclassificationIcon');

function rhac_handicapImprovementIcon($atts) {
    extract( shortcode_atts( array('val' => ''), $atts ) );
    return "<span class='handicap-improvement'>$val</span>";
}

add_shortcode('handicap_improvement_icon', 'rhac_handicapImprovementIcon');
