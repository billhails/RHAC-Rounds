<?php

include plugin_dir_path(__FILE__) . '../gnas-archery-rounds/rounds.php';

class RHAC_Scorecards {

    private $pdo;
    private static $instance;

    private function __construct() {
        try {
            $this->pdo = new PDO('sqlite:'
                         . plugin_dir_path(__FILE__)
                         . '../rhac-scorecards/scorecard.db');
        } catch (PDOException $e) {
            wp_die('Error!: ' . $e->getMessage());
            exit();
        }
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public function fetch($query, $params = array()) {
        $stmt = $this->pdo->prepare($query);
        if (!$stmt) {
            die("query: [$query] failed to prepare: " . $this->pdo->errmsg);
        }
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();
        return $rows;
    }

    public function topLevel() {

        if (isset($_POST['edit-scorecard'])) { // update or insert requested
            if ($_POST['scorecard-id']) { // update requested
                $this->update();
                $this->edit($_POST['scorecard-id']);
            }
            else { // insert requested
                $id = $this->insert();
                $this->edit($id);
            }
        } elseif (isset($_GET['edit-scorecard'])) { // edit or create requested
            if ($_GET['scorecard-id']) { // edit requested
                $this->edit($_GET['scorecard-id'] || 0);
            }
            else { // create requested
                $this->edit(0);
            }
        } elseif (isset($_GET['find-scorecard'])) { // search requested
            $this->find();
        } else { // homePage
            $this->homePage();
        }
    }

    private function dateToStoredFormat($date) {
        $obj = date_create($date);
        if ($obj) {
            return $obj->format('Y/m/d');
        }
        else {
            wp_die("can't recognise external date: $date");
            exit();
        }
    }

    private function dateToDisplayedFormat($date) {
        $obj = date_create($date);
        if ($obj) {
            return $obj->format('D, j M yy');
        }
        else {
            wp_die("can't recognise internal date: $date");
            exit();
        }

    }

    private function update() {
        $id = $_POST['scorecard-id'];
        $params = array(
            $_POST['archer'],
            $this->dateToStoredFormat($_POST['date']),
            $_POST['round'],
            $_POST['bow'],
            $_POST['i-total-hits'],
            $_POST['i-total-xs'],
            $_POST['i-total-golds'],
            $_POST['i-total-score'],
            $id
        );
        $this->pdo->exec("UPDATE scorecards"
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
        $this->pdo->exec("DELETE FROM scorecard_end WHERE scorecard_id = ?",
                         array($id));
        $this->insertEnds($id);
    }

    private function insert() {
        // FIXME needs to be in a transaction
        $this->pdo->exec("INSERT INTO scorecard"
                 . "(archer, date, round, bow, hits, xs, golds, score)"
                 . " VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                 array($_POST['archer'],
                       $this->dateToStoredFormat($_POST['date']),
                       $_POST['round'],
                       $_POST['bow'], $_POST['i-total-hits'],
                       $_POST['i-total-xs'], $_POST['i-total-golds'],
                       $_POST['i-total-total']));
        $id = $this->pdo->lastInsertId();
        $this->insertEnds($id);
        // FIXME end transaction
        return $id;
    }

    private function insertEnds($id) {
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
            $this->pdo->exec("INSERT INTO scorecard_end"
                       . "(scorecard_id, end_number, arrow_1, arrow_2,"
                       . " arrow_3, arrow_4, arrow_5, arrow_6)"
                       . " VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                       $params);
        }
    }

    private function edit($id) {
        global $scorecard_id;
        global $scorecard_data;
        global $scorecard_end_data;
        $ccorecard_id = $id;
        if ($id) {
            $rows = $this->fetch("SELECT * FROM scorecard WHERE id = ?",
                                 array($id));
            $scorecard_data = $rows[0];
            $scorecard_data['date'] =
                $this->dateToDisplayedFormat($scorecard_data['date']);
            $rows = $this->fetch("SELECT *"
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

    private function find() {
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
                $params []= $this->dateToStoredFormat($_GET["lower-date"]);
                $params []= $this->dateToStoredFormat($_GET["upper-date"]);
            }
            else {
                $criteria []= 'date = ?';
                $params []= $this->dateToStoredFormat($_GET["lower-date"]);
            }
        }
        $query = "SELECT * FROM scorecard WHERE "
               . implode(' AND ', $criteria);
        global $search_results;
        $search_results = $this->fetch($query, $params);
        foreach ($search_results as $result) {
            $result['date'] = $this->dateToDisplayedFormat($result['date']);
        }
        include "scorecard_search_results.php";
    }

    private function homePage() {
        include "scorecard_homepage.php";
    }
}

RHAC_Scorecards::getInstance()->topLevel();
