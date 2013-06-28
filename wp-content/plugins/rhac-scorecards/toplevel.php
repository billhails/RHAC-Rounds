<?php

include plugin_dir_path(__FILE__) . '../gnas-archery-rounds/rounds.php';

class RHAC_Scorecards {

    private $pdo;
    private $scorecard_data;
    private $scorecard_id;
    private static $instance;
    private static $zones = array(
        'TenZoneChart' => array(
            'class' => 'bar',
            'width' => 30,
            'height' => 300,
            'zones' => array()
        )
    );

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
        global $scorecard_end_data;
        $this->scorecard_id = $id;
        if ($id) {
            $rows = $this->fetch("SELECT * FROM scorecard WHERE id = ?",
                                 array($id));
            $this->scorecard_data = $rows[0];
            $this->scorecard_data['date'] =
                $this->dateToDisplayedFormat($this->scorecard_data['date']);
            $rows = $this->fetch("SELECT *"
                          . " FROM scorecard_end"
                          . " WHERE scorecard_id = ?"
                          . " ORDER BY end_number",
                          array($id));
            $scorecard_end_data = $rows;
        }
        else {
            $this->scorecard_data = array();
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

    /**
     * Generate round data to html that javascript can inspect.
     */
    public function roundData() {
        $text = '<span id="round-data">';
        foreach (GNAS_Page::roundData() as $round) {
            $name = $round->getName();
            $text .= '<span name="' . $name . '">';
            $text .= '<span class="measure">'
                   . $round->getMeasure()->getName()
                   . '</span>';
            foreach ($round->getDistances()->rawData() as $distance) {
                $text .= '<span class="count">'
                       . $distance->getNumArrows()
                       . '</span>';
            }
            $text .= "</span>\n";
        }
        $text .= '</span>';
        return $text;
    }

    /**
     * Generate round data to JSON directly.
     */
    public function roundDataAsJSON() {
        $rounds = array();
        foreach (GNAS_Page::roundData() as $round) {
            $round_json = '"' . $round->getName() . '":{';
            $round_json .= '"measure":"' . $round->getMeasure()->getName() . '",';
            $count = 0;
            foreach ($round->getDistances()->rawData() as $distance) {
                $count += $distance->getNumArrows();
            }
            $round_json .= '"arrows":' . $count . '}';
            $rounds []= $round_json;
        }
        return '{' . implode(',', $rounds) . '}';
    }

    public function roundDataAsSelect() {
        $text = array('<select name="round" id="round">');
        $text []= "<option value=''>- - -</option>\n";
        foreach (GNAS_Page::roundData() as $round) {
            $text []= "<option value='" . $round->getName() . "'";
            if ($round->getName() == $this->scorecard_data['round']) {
                $text []= " selected='1'";
            }
            $text []= ">" . $round->getName() . "</option>\n";
        }
        $text []= '<select>';
        return implode($text);
    }

    public function dateAsInput() {
        $text = array();
        $text []= '<input type="text" name="date" ';
        if ($this->scorecard_data['date']) {
            $text []= "value='" . $this->scorecard_data["date"] . "'";
        }
        $text []= 'id="date"/>';
        return implode($text);
    }

    public function archersAsSelect() {
        $text = array('<select name="archer" id="archer">');
        $text []= "<option value=''>- - -</option>\n";
        $archers = RHAC_Scorecards::getInstance()->fetch(
                            'SELECT name FROM archer ORDER BY name');
        foreach ($archers as $archer) {
            $text []= "<option value='$archer[name]'"
                . ($archer["name"] == $this->scorecard_data["archer"]
                    ? ' selected="1"'
                    : '')
                .">"
                . $archer["name"]
                . "</option>\n";
        }
        $text []= '</select>';
        return implode($text);
    }

    public function bowsAsRadio() {
        $text = array();
        foreach(array('R' => 'recurve',
                      'C' => 'compound',
                      'L' => 'longbow',
                      'B' => 'barebow') as $initial => $bow) {
            $text []= '<input type="radio" name="bow" id="bow"';
            if ($this->scorecard_data['bow'] == $bow) {
                $text []= " selected='1'";
            }
            $text []= " value='$bow'>$initial</input>\n";
        }
        return implode($text);
    }

    public function scorecardIdAsHidden() {
        return '<input type="hidden" name="scorecard-id" value="' . $this->scorecard_id . '" />';
    }
}

RHAC_Scorecards::getInstance()->topLevel();
