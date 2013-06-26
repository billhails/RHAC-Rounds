<?php

include plugin_dir_path(__FILE__) . '../gnas-archery-rounds/rounds.php';

try {
    $pdo = new PDO('sqlite:'
                 . plugin_dir_path(__FILE__)
                 . '../rhac-scorecards/scorecard.db');
} catch (PDOException $e) {
    wp_die('Error!: ' . $e->getMessage());
    exit();
}

$pdo->exec('PRAGMA foreign_keys = ON');

function fetch($query, $params = array()) {
    global $pdo;
    $stmt = $pdo->prepare($query);
    if (!$stmt) {
        die("query: [$query] failed to prepare: " . $pdo->errmsg);
    }
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    $stmt->closeCursor();
    return $rows;
}

if (isset($_POST['edit-scorecard'])) { // update or insert requested
    if ($_POST['scorecard-id']) { // update requested
        do_update();
        do_edit($_POST['scorecard-id']);
    }
    else { // insert requested
        $id = do_insert();
        do_edit($id);
    }
} elseif (isset($_GET['edit-scorecard'])) { // edit or create requested
    if ($_GET['scorecard-id']) { // edit requested
        do_edit($_GET['scorecard-id'] || 0);
    }
    else { // create requested
        do_edit(0);
    }
} elseif (isset($_GET['find-scorecard'])) { // search requested
    do_find();
} else { // homepage
    do_homepage();
}

function do_update() {
    global $pdo;
    $id = $_POST['scorecard-id'];
    $params = array(
        $_POST['archer'],
        $_POST['date'],
        $_POST['round'],
        $_POST['bow'],
        $_POST['i-total-hits'],
        $_POST['i-total-xs'],
        $_POST['i-total-golds'],
        $_POST['i-total-score'],
        $id
    );
    $pdo->exec("UPDATE scorecards"
             . " SET archer = ?,"
             . " date = ?,"
             . " round = ?,"
             . " bow = ?,"
             . " hits = ?,"
             . " xs = ?,"
             . " golds = ?,"
             . " score = ?"
             . " WHERE id = ?",
                $params);
    $pdo->exec("DELETE FROM scorecard_end WHERE scorecard_id = ?", array($id));
    do_insert_ends($id);
}

function do_insert() {
    global $pdo;
    // FIXME needs to be in a transaction
    $pdo->exec("INSERT INTO scorecard"
             . "(archer, date, round, bow, hits, xs, golds, score)"
             . " VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
             array($_POST['archer'], $_POST['date'], $_POST['round'],
                   $_POST['bow'], $_POST['i-total-hits'],
                   $_POST['i-total-xs'], $_POST['i-total-golds'],
                   $_POST['i-total-total']));
    $id = $pdo->lastInsertId();
    do_insert_ends($id);
    // FIXME end transaction
    return $id;
}

function do_insert_ends($id) {
    for ($end = 1; $end < 25; ++$end) {
        if ($_POST["arrow-$end-1"] == "") break;
        if ($_POST["arrow-$end-2"] == "") break;
        if ($_POST["arrow-$end-3"] == "") break;
        if ($_POST["arrow-$end-4"] == "") break;
        if ($_POST["arrow-$end-5"] == "") break;
        if ($_POST["arrow-$end-6"] == "") break;
        $params = array($id,
                        $end,
                        $_POST["arrow-$end-1"],
                        $_POST["arrow-$end-2"],
                        $_POST["arrow-$end-3"],
                        $_POST["arrow-$end-4"],
                        $_POST["arrow-$end-5"],
                        $_POST["arrow-$end-6"]);
        $pdo->exec("INSERT INTO scorecard_end"
                   . "(scorecard_id, end_number, arrow_1, arrow_2,"
                   . " arrow_3, arrow_4, arrow_5, arrow_6)"
                   . " VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                   $params);
    }
}

function do_edit($id) {
    global $scorecard_id;
    global $scorecard_data;
    global $scorecard_end_data;
    $ccorecard_id = $id;
    if ($id) {
        $rows = fetch("SELECT * FROM scorecard WHERE id = ?", array($id));
        $scorecard_data = $rows[0];
        $rows = fetch("SELECT *"
                      . " FROM scorecard_end"
                      . " WHERE scorecard_id = ?"
                      . " ORDER BY end_number",
                      array($id));
        $scorecard_end_data = $rows;
    }
    else {
        $scorecard_data = array();
        $scorecard_end_data = array();
    }
    include "scorecard.php";
}

function do_find() {
    $criteria = array("1 = 1");
    $params = array();
    if ($_GET["archer"]) {
        $criteria []= 'archer = ?';
        $params []= $_GET["archer"];
    }
    if ($_GET["round"]) {
        $criteria []= 'round = ?';
        $params []= $_GET["round"];
    }
    if ($_GET["lower-date"]) {
        if ($_GET["upper-date"]) {
            $criteria []= 'date BETWEEN ? and ?';
            $params []= $_GET["lower-date"];
            $params []= $_GET["upper-date"];
        }
        else {
            $criteria []= 'date = ?';
            $params []= $_GET["lower-date"];
        }
    }
    $query = "SELECT * FROM scorecard WHERE " . implode(' AND ', $criteria);
    global $search_results;
    $search_results = fetch($query, $params);
    include "scorecard_search_results.php"; // data in globals
}

function do_homepage() {
    include "scorecard_homepage.php";
}
