<?php

if (isset($_POST['update-scorecard'])) { // update or insert requested
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
        do_edit($_GET['scorecard-id']);
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
    "DELETE FROM scorecard_end WHERE scorecard_id = ?";
    "INSERT INTO scorecard_end VALUES ...";
    "UPDATE scorecards SET ... WHERE id = ?";
}

function do_insert() {
    "INSERT INTO scorecard VALUES ...";
    $id = $pdo->lastInsertId();
    "INSERT INTO scorecard_end .... VALUES ...";
    return $id;
}

function do_edit($id) {
    if ($id) {
        "SELECT * FROM scorecard WHERE id = ?";
        "SELECT * FROM scorecard_end WHERE scorecard_id = ? ORDER BY end_number";
    }
    include "scorecard.php"; // gets all its data from globals, including $id
}

function do_find() {
    include "search_scorecards.php";
}

function do_homepage() {
    include "scorecard_homepage.php";
}
