<?php
/*
Plugin Name: GNAS Archery Rounds
Description: Display Recognised GNAS Rounds and Classifications
Version: 0.1
Author: Bill Hails
Author URI: http://www.billhails.net/
License: Uncertain
*/

define('GNAS_ARCHERY_PLUGIN_DIR', plugin_dir_path(__FILE__));

require GNAS_ARCHERY_PLUGIN_DIR . 'rounds.php';

function gnas_archery_rounds_display() {
    return GNAS_Page::asText();
}

function gnas_archery_rounds_find() {
    return GNAS_Page::roundFinder();
}

function gnas_archery_table_display() {
    return GNAS_Page::outdoorTable();
}

add_shortcode('rounds', 'gnas_archery_rounds_display');
add_shortcode('requirements', 'gnas_archery_rounds_find');

add_action( 'admin_menu', 'register_gnas_plugin_menu' );

function register_gnas_plugin_menu() {
    add_menu_page('Edit GNAS Tables',
                  'GNAS Tables',
                  'manage_options',
                  'gnas-archery-rounds',
                  'gnas_edit_tables',
                  plugin_dir_url( __FILE__ )
                  . 'gnas-archery-rounds-icon.png' );
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 4',
                     'Table 4',
                     'manage_options',
                     'edit-table-4',
                     'gnas_edit_table_4');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 5',
                     'Table 5',
                     'manage_options',
                     'edit-table-5',
                     'gnas_edit_table_5');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 6',
                     'Table 6',
                     'manage_options',
                     'edit-table-6',
                     'gnas_edit_table_6');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 7',
                     'Table 7',
                     'manage_options',
                     'edit-table-7',
                     'gnas_edit_table_7');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 8',
                     'Table 8',
                     'manage_options',
                     'edit-table-8',
                     'gnas_edit_table_8');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 9',
                     'Table 9',
                     'manage_options',
                     'edit-table-9',
                     'gnas_edit_table_9');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 10',
                     'Table 10',
                     'manage_options',
                     'edit-table-10',
                     'gnas_edit_table_10');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 11',
                     'Table 11',
                     'manage_options',
                     'edit-table-11',
                     'gnas_edit_table_11');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 12',
                     'Table 12',
                     'manage_options',
                     'edit-table-12',
                     'gnas_edit_table_12');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 13',
                     'Table 13',
                     'manage_options',
                     'edit-table-13',
                     'gnas_edit_table_13');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 14',
                     'Table 14',
                     'manage_options',
                     'edit-table-14',
                     'gnas_edit_table_14');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 15',
                     'Table 15',
                     'manage_options',
                     'edit-table-15',
                     'gnas_edit_table_15');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 16',
                     'Table 16',
                     'manage_options',
                     'edit-table-16',
                     'gnas_edit_table_16');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 17',
                     'Table 17',
                     'manage_options',
                     'edit-table-17',
                     'gnas_edit_table_17');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 18',
                     'Table 18',
                     'manage_options',
                     'edit-table-18',
                     'gnas_edit_table_18');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 19',
                     'Table 19',
                     'manage_options',
                     'edit-table-19',
                     'gnas_edit_table_19');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 20',
                     'Table 20',
                     'manage_options',
                     'edit-table-20',
                     'gnas_edit_table_20');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 21',
                     'Table 21',
                     'manage_options',
                     'edit-table-21',
                     'gnas_edit_table_21');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 22',
                     'Table 22',
                     'manage_options',
                     'edit-table-22',
                     'gnas_edit_table_22');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 23',
                     'Table 23',
                     'manage_options',
                     'edit-table-23',
                     'gnas_edit_table_23');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 24',
                     'Table 24',
                     'manage_options',
                     'edit-table-24',
                     'gnas_edit_table_24');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 25',
                     'Table 25',
                     'manage_options',
                     'edit-table-25',
                     'gnas_edit_table_25');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 26',
                     'Table 26',
                     'manage_options',
                     'edit-table-26',
                     'gnas_edit_table_26');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 27',
                     'Table 27',
                     'manage_options',
                     'edit-table-27',
                     'gnas_edit_table_27');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 28a',
                     'Table 28a',
                     'manage_options',
                     'edit-table-28a',
                     'gnas_edit_table_28a');
    add_submenu_page('gnas-archery-rounds',
                     'Edit Table 28b',
                     'Table 28b',
                     'manage_options',
                     'edit-table-28b',
                     'gnas_edit_table_28b');
}

function gnas_check_user() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions'
                    . ' to access this page.' ) );
    }
}

function gnas_edit_tables() {
    gnas_check_user();
    echo '<div class="wrap">';
    echo '<p>Edit the GNAS Tables directly using the sub-menus.</p>';
    echo '<p>You will need to grab the most recent version of'
         . ' the document "G-07-01 Shooting Administration Procedures"'
         . ' from the <a href="http://www.archerygb.org/documents/index.php"'
         . ' target="blank">Archery GB Documents Archive</a>.</p>';
    echo '<p>Look under <q>Governance</q> <tt>&gt;</tt> <q>General Documents</q>.</p>';
    echo '</div>';
}

function gnas_edit_table_4() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(4);
}
function gnas_edit_table_5() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(5);
}
function gnas_edit_table_6() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(6);
}
function gnas_edit_table_7() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(7);
}
function gnas_edit_table_8() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(8);
}
function gnas_edit_table_9() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(9);
}
function gnas_edit_table_10() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(10);
}
function gnas_edit_table_11() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(11);
}
function gnas_edit_table_12() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(12);
}
function gnas_edit_table_13() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(13);
}
function gnas_edit_table_14() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(14);
}
function gnas_edit_table_15() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(15);
}
function gnas_edit_table_16() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(16);
}
function gnas_edit_table_17() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(17);
}
function gnas_edit_table_18() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(18);
}
function gnas_edit_table_19() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(19);
}
function gnas_edit_table_20() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(20);
}
function gnas_edit_table_21() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(21);
}
function gnas_edit_table_22() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(22);
}
function gnas_edit_table_23() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(23);
}
function gnas_edit_table_24() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(24);
}
function gnas_edit_table_25() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(25);
}
function gnas_edit_table_26() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(26);
}
function gnas_edit_table_27() {
    gnas_check_user();
    print GNAS_Page::outdoorTable(27);
}
function gnas_edit_table_28a() {
    gnas_check_user();
    print GNAS_Page::indoorTable("28a");
}
function gnas_edit_table_28b() {
    gnas_check_user();
    print GNAS_Page::indoorTable("28b");
}
