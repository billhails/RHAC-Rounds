<?php

include_once plugin_dir_path(__FILE__) . '../gnas-archery-rounds/rounds.php';
include_once plugin_dir_path(__FILE__) . 'RHAC_ScorecardAccumulator.php';
include_once plugin_dir_path(__FILE__) . 'RHAC_ReassesmentInserter.php';

class RHAC_Archer252 {
    private static $instances;
    private static $scores;
    private static $counts;
    private static $requirements = array(
        'Green 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'White 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'Black 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'Blue 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'Red 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'Bronze 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 164, 'barebow' => 189),
        'Silver 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 126, 'barebow' => 164),
        'Gold 252' => array( 'recurve' => 252, 'compound' => 280, 'longbow' => 101, 'barebow' => 139)
    );

    public static function init() {
        self::$instances = array();
        self::$scores = array();
        self::$counts = array();
    }

    // assumes the data is sorted by date, archer, round, bow
    public static function addRow($row) {
        $archer = $row['archer'];
        $bow = $row['bow'];
        $round = $row['round'];
        $score = $row['score'];
        if (self::below_required_score($bow, $round, $score)) {
            return;
        }
        if (self::already_seen_two($archer, $bow, $round)) {
            return;
        }
        $row['previous'] = self::previous($archer, $bow, $round, $score);
        self::count($archer, $bow, $round);
        self::$instances []= $row;
    }

    private static function count($archer, $bow, $round) {
        if (!isset(self::$counts[$archer])) {
            self::$counts[$archer] = array();
        }
        if (!isset(self::$counts[$archer][$bow])) {
            self::$counts[$archer][$bow] = array();
        }
        if (!isset(self::$counts[$archer][$bow][$round])) {
            self::$counts[$archer][$bow][$round] = 0;
        }
        self::$counts[$archer][$bow][$round]++;
    }

    private static function already_seen_two($archer, $bow, $round) {
        return self::$counts[$archer][$bow][$round] >= 2;
    }

    private static function previous($archer, $bow, $round, $score) {
        if (!isset(self::$scores[$archer])) {
            self::$scores[$archer] = array();
        }
        if (!isset(self::$scores[$archer][$bow])) {
            self::$scores[$archer][$bow] = array();
        }
        $previous = '&nbsp;';
        if (isset(self::$scores[$archer][$bow][$round])) {
            $previous = self::$scores[$archer][$bow][$round];
        }
        self::$scores[$archer][$bow][$round] = $score;
        return $previous;
    }

    private static function below_required_score($bow, $round, $score) {
        return self::$requirements[$round][$bow] > $score;
    }

    public static function getResultsHTML() {
        $result = '<h1>252 Results So Far</h1>';
        $result .= '<table>';
        $result .= '<thead>';
        $result .= '<tr>';
        $result .= '<th>Date</th><th>Archer</th><th>Round</th><th>Bow</th><th>Prev</th><th>This</th>';
        $result .= '</tr><thead>';
        $result .= '<tbody>';
        $classes = array('even', 'odd');
        $date = '';
        $count = 0;
        foreach (self::$instances as $row) {
            if ($date != $row['date']) {
                $date = $row['date'];
                $class = $classes[$count];
                $count++;
                $count = $count % 2;
            }
            $result .= "<tr class='$class'>";
            $result .= "<td>$row[date]</td>";
            $result .= "<td>$row[archer]</td>";
            $result .= "<td>$row[round]</td>";
            $result .= "<td>$row[bow]</td>";
            $result .= "<td>$row[previous]</td>";
            $result .= "<td>$row[score]</td>";
            $result .= '</tr>';
        }
        $result .= '</tbody></table>';
        return $result;
    }
}

class RHAC_Scorecards {

    private $pdo;
    private $scorecard_data;
    private $scorecard_id;
    private $scorecard_end_data;
    private $homepath;
    private $homeurl;
    private $archer_map;
    private $rounds;
    private static $instance;

    private function __construct() {
        $this->homepath = plugin_dir_path(__FILE__);
        $this->homeurl = plugin_dir_url(__FILE__);
        $this->rounds = array();
        try {
            $this->pdo = new PDO('sqlite:'
                         . $this->homepath
                         . 'scorecard.db');
        } catch (PDOException $e) {
            die('Error!: ' . $e->getMessage());
            exit();
        }
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        // echo '<p>construct successful</p>';
    }

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getRound($round_name) {
        $round = GNAS_Round::getInstanceByName($round_name);
        if ($round instanceof GNAS_UnrecognisedRound) {
            die("unrecognised round: $round_name");
        }
        return $round;
    }

    public function fetch($query, $params = array()) {
        $stmt = $this->pdo->prepare($query);
        if (!$stmt) {
            die("query: [$query] failed to prepare: "
                . print_r($this->pdo->errorInfo(), true));
        }
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $rows;
    }

    public function exec($query, $params = array()) {
        $stmt = $this->pdo->prepare($query);
        if (!$stmt) {
            die("statement: [$query] failed to prepare: "
                . print_r($this->pdo->errorInfo(), true));
        }
        $status = $stmt->execute($params);
        $stmt->closeCursor();
        return $status;
    }

    public function topLevel() {

        // echo '<p>topLevel() entered</p>';
        if (isset($_POST['submit-scorecard-and-edit'])) {
            // update or insert requested
            if ($_POST['scorecard-id']) { // update requested
                // echo '<p>topLevel() update req</p>';
                $this->update();
                $id = $_POST['scorecard-id'];
            } else { // insert requested
                // echo '<p>topLevel() insert req</p>';
                $id = $this->insert();
            }
            $this->edit($id);
        } elseif (isset($_POST['submit-scorecard-and-new'])) {
            if ($_POST['scorecard-id']) { // update requested
                $this->update();
            } else { // insert requested
                $this->insert();
            }
            $this->edit(0);
        } elseif (isset($_POST['submit-scorecard-and-finish'])) {
            if ($_POST['scorecard-id']) { // update requested
                $this->update();
            } else { // insert requested
                $this->insert();
            }
            $this->homePage();
        } elseif (isset($_POST['submit-score-and-edit'])) {
            // update or insert requested
            if ($_POST['scorecard-id']) { // update requested
                // echo '<p>topLevel() update req</p>';
                $this->update();
                $id = $_POST['scorecard-id'];
            } else { // insert requested
                // echo '<p>topLevel() insert req</p>';
                $id = $this->insert();
            }
            $this->edit($id, false);
        } elseif (isset($_POST['submit-score-and-new'])) {
            if ($_POST['scorecard-id']) { // update requested
                $this->update();
            } else { // insert requested
                $this->insert();
            }
            $this->edit(0, false);
        } elseif (isset($_POST['submit-score-and-finish'])) {
            if ($_POST['scorecard-id']) { // update requested
                $this->update();
            } else { // insert requested
                $this->insert();
            }
            $this->homePage();

        } elseif (isset($_POST['delete-scorecard'])) { // delete requested
            // echo '<p>topLevel() delete req</p>';
            $this->deleteScorecard($_POST['scorecard-id']);
            $this->homePage();
        } elseif (isset($_POST['delete-archer'])) { // delete requested
            $this->deleteArcher($_POST['archer']);
            $this->homePage();
        } elseif (isset($_POST['merge-archers'])) { // merge requested
            $this->mergeArchers($_POST['from-archer'], $_POST['to-archer']);
            $this->homePage();
        } elseif (isset($_POST['add-venue'])) {
            $this->addVenue($_POST['venue']);
            $this->homePage();
        } elseif (isset($_POST['add-archer'])) {
            $this->addArcher($_POST['archer'], $_POST['dob'], $_POST['gender'], $_POST['archived']);
            $this->homePage();
        } elseif (isset($_POST['rebuild-round-handicaps'])) {
            $this->rebuildRoundHandicaps();
            $this->homePage();
        } elseif (isset($_POST['recalculate-score-handicaps'])) {
            $this->reCalculateHandicapsForScores();
            $this->homePage();
        } elseif (isset($_POST['recalculate-age-groups'])) {
            $this->reCalculateAgeGroups();
            $this->homePage();
        } elseif (isset($_POST['recalculate-genders'])) {
            $this->reCalculateGenders();
            $this->homePage();
        } elseif (isset($_POST['recalculate-classifications'])) {
            $this->reCalculateClassifications();
            $this->homePage();
        } elseif (isset($_POST['recalculate-outdoor'])) {
            $this->reCalculateOutdoor();
            $this->homePage();
        } elseif (isset($_POST['recalculate-tens'])) {
            $this->reCalculateTens();
            $this->homePage();
        } elseif (isset($_POST['recalculate-records'])) {
            $this->reCalculateRecords();
            $this->homePage();
        } elseif (isset($_GET['edit-scorecard'])) { // edit or create requested
            if ($_GET['scorecard-id']) { // edit requested
                $this->edit($_GET['scorecard-id']);
            }
            else { // create requested
                $this->edit(0);
            }
        } elseif (isset($_GET['edit-score'])) { // edit or create requested
            if ($_GET['scorecard-id']) { // edit requested
                $this->edit($_GET['scorecard-id'], false);
            }
            else { // create requested
                $this->edit(0, false);
            }
        } elseif (isset($_GET['find-scorecard'])) { // search requested
            $this->find();
        } elseif (isset($_GET['two-five-two'])) {
            $this->two_five_two();
        } else { // homePage
            // echo '<p>doing home page</p>';
            $this->homePage();
        }
    }

    private function mergeArchers($from, $to) {
        if ($from && $to && $from != $to) {
            $this->pdo->beginTransaction();
            $this->exec("UPDATE scorecards SET archer = ? WHERE archer = ?", array($to, $from));
            $this->exec("DELETE FROM archer WHERE name = ?", array($from));
            $this->pdo->commit();
            echo "<p>Archer $from is now $to</p>";
        }
    }

    private function addArcher($archer, $dob, $gender, $archived) {
        if ($archer && preg_match('#^\d\d\d\d/\d\d/\d\d$#', $dob) && $gender && $archived) {
            $this->exec("INSERT INTO archer(name, date_of_birth, gender, archived) VALUES(?, ?, ?, ?)",
                        array($archer, $dob, $gender, $archived));
            echo "<p>Archer $archer added</p>";
        }
        else {
            echo "<p>Archer [$archer] [$dob] [$gender] [$archived] <b>NOT</b> added</p>";
        }
    }

    private function addVenue($venue) {
        if ($venue) {
            $this->exec("INSERT INTO venue(name) VALUES(?)", array($venue));
            echo "<p>Venue $venue added</p>";
        }
        else {
            echo "<p>Venue <b>NOT</b> added</p>";
        }
    }

    private function deleteScorecard($id) {
        if ($id) {
            $status1 = $this->exec("DELETE FROM scorecard_end WHERE scorecard_id = ?", array($id));
            $status2 = $this->exec("DELETE FROM scorecards WHERE scorecard_id = ?", array($id));
            echo "<p>Scorecard #$id deleted.</p>";
        }
    }

    private function deleteArcher($archer) {
        if ($this->noScorecards($archer)) {
            $this->exec("DELETE FROM archer where name = ?", array($archer));
            echo "<p>Archer $archer deleted.</p>";
        } else {
            echo "<p>Archer $archer cannot be deleted because they may have scorecards.</p>";
        }
    }

    private function noScorecards($archer) {
        $rows = $this->fetch("SELECT COUNT(*) AS num FROM scorecards WHERE archer = ?", array($archer));
        $count = $rows[0]['num'];
        if (isset($count)) {
            return ($count == 0);
        } else {
            echo "<p>Cannot find count of scorecards for $archer.</p>";
            return false;
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
            return $obj->format('D, j M Y');
        }
        else {
            wp_die("can't recognise internal date: $date");
            exit();
        }
    }

    private function update() {
        # print "<pre>\n";
        # print_r($_POST);
        # print "</pre>\n";

        $id = $_POST['scorecard-id'];
        $handicap_ranking = $this->getHandicapForScore($_POST['bow'], $_POST['round'], $_POST['total-total']);
        $gender = $this->getGender($_POST['archer']);
        $date = $this->dateToStoredFormat($_POST['date']);
        $category = $this->categoryAt($_POST['archer'], $date);
        $classification = $this->getClassification($_POST['round'], $gender, $category,
                                                   $_POST['bow'], $_POST['total-total']);
        $next_age_group = $this->getNextAgeGroup($category);
        if ($next_age_group) {
            $next_age_group_classification = $this->getClassification($_POST['round'],
                                                                      $gender,
                                                                      $next_age_group,
                                                                      $_POST['bow'],
                                                                      $_POST['total-total']);
        }
        $outdoor = $this->getIsOutdoor($_POST['round']);
        $tens = $this->countTens();
        $params = array(
            $_POST['archer'],
            $_POST['venue_id'],
            $date,
            $_POST['round'],
            $_POST['bow'],
            $_POST['total-hits'],
            $_POST['total-xs'],
            $_POST['total-golds'],
            $_POST['total-total'],
            $_POST['medal'],
            $handicap_ranking,
            $_POST['has_ends'],
            $classification,
            $next_age_group_classification,
            $outdoor,
            $category,
            $gender,
            $tens,
            $id
        );
        // echo '<p>update() ' . print_r($params, true) . '</p>';
        $this->pdo->beginTransaction();
        $this->exec("UPDATE scorecards"
                 . " SET archer = ?,"
                 . " venue_id = ?,"
                 . " date = ?,"
                 . " round = ?,"
                 . " bow = ?,"
                 . " hits = ?,"
                 . " xs = ?,"
                 . " golds = ?,"
                 . " score = ?,"
                 . " medal = ?,"
                 . " handicap_ranking = ?,"
                 . " has_ends = ?,"
                 . " classification = ?,"
                 . " next_age_group_classification = ?,"
                 . " outdoor = ?,"
                 . " category = ?,"
                 . " gender = ?,"
                 . " tens = ?"
                 . " WHERE scorecard_id = ?",
                    $params);
        if ($_POST['has_ends'] == "Y") {
            $this->exec("DELETE FROM scorecard_end WHERE scorecard_id = ?",
                             array($id));
            $this->insertEnds($id);
        }
        $this->pdo->commit();
    }

    private function insert() {
        $handicap_ranking = $this->getHandicapForScore($_POST['bow'], $_POST['round'], $_POST['total-total']);
        $gender = $this->getGender($_POST['archer']);
        $date = $this->dateToStoredFormat($_POST['date']);
        $category = $this->categoryAt($_POST['archer'], $date);
        $classification = $this->getClassification($_POST['round'], $gender, $category,
                                                   $_POST['bow'], $_POST['total-total']);
        $next_age_group = $this->getNextAgeGroup($category);
        if ($next_age_group) {
            $next_age_group_classification = $this->getClassification($_POST['round'],
                                                                      $gender,
                                                                      $next_age_group,
                                                                      $_POST['bow'],
                                                                      $_POST['total-total']);
        }
        $outdoor = $this->getIsOutdoor($_POST['round']);
        $tens = $this->countTens();
        $this->pdo->beginTransaction();
        // echo '<p>insert() inside transaction</p>';
        $status = $this->exec("INSERT INTO scorecards"
                 . "(archer, venue_id, date, round, bow, hits, xs, golds, score, medal, has_ends, handicap_ranking, "
                 . "gender, category, classification, next_age_group_classification, outdoor, tens)"
                 . " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                 array($_POST['archer'],
                       $_POST['venue_id'],
                       $date,
                       $_POST['round'],
                       $_POST['bow'],
                       $_POST['total-hits'],
                       $_POST['total-xs'],
                       $_POST['total-golds'],
                       $_POST['total-total'],
                       $_POST['medal'],
                       $_POST['has_ends'],
                       $handicap_ranking,
                       $gender,
                       $category,
                       $classification,
                       $next_age_group_classification,
                       $outdoor,
                       $tens));
        if (!$status) {
            echo '<p>INSERT returned false:'
                . print_r($this->pdo->errorInfo(), true) . '</p>';
            $this->pdo->rollback();
            return 0;
        }
        $id = $this->pdo->lastInsertId();
        $this->insertEnds($id);
        $this->pdo->commit();
        return $id;
    }

    private function countTens() {
        if (isset($_POST['total-tens'])) {
            return $_POST['total-tens'];
        }
        $total = 0;
        for ($end = 1; $end < 25; ++$end) {
            if ($_POST["arrow-$end-1"] == "") break;
            if ($_POST["arrow-$end-2"] == "") break;
            if ($_POST["arrow-$end-3"] == "") break;
            if ($_POST["arrow-$end-4"] == "") break;
            if ($_POST["arrow-$end-5"] == "") break;
            if ($_POST["arrow-$end-6"] == "") break;
            for ($arrow = 1; $arrow <= 6; ++$arrow) {
                $score = $_POST["arrow-$end-$arrow"];
                if ($score == "10" || $score == "X") {
                    ++$total;
                }
            }
        }
        return $total;
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
            $this->exec("INSERT INTO scorecard_end"
                       . "(scorecard_id, end_number, arrow_1, arrow_2,"
                       . " arrow_3, arrow_4, arrow_5, arrow_6)"
                       . " VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                       $params);
        }
    }

    public function scorecardJSON($id) {
        $this->populateScorecardData($id);
        $data = array();
        foreach ($this->scorecard_data as $key => $value) {
            $data[$key] = $value;
        }
        $data['ends'] = $this->scorecard_end_data;
        $gnas_round = GNAS_Round::getInstanceByName($data['round']);
        $data['measure'] = $gnas_round->getMeasure()->getName();
        return json_encode($data);
    }

    private function populateScorecardData($id) {
        $rows = $this->fetch("SELECT * FROM scorecards WHERE scorecard_id = ?",
                             array($id));
        $this->scorecard_data = $rows[0];
        $this->scorecard_data['date'] =
            $this->dateToDisplayedFormat($this->scorecard_data['date']);
        $rows = $this->fetchScorecardEnds($id);
        $this->scorecard_end_data = $rows;
    }

    private function fetchScorecardEnds($id) {
        return $this->fetch("SELECT *"
                      . " FROM scorecard_end"
                      . " WHERE scorecard_id = ?"
                      . " ORDER BY end_number",
                      array($id));
    }

    private function edit($id, $has_ends = true) {
        // echo "<p>edit($id)</p>";
        $this->scorecard_id = $id;
        if ($id) {
            $this->populateScorecardData($id);
        }
        else {
            $this->scorecard_data = array();
            if (isset($_POST['date'])) {
                $this->scorecard_data['date'] = $_POST['date'];
            }
            if (isset($_POST['round'])) {
                $this->scorecard_data['round'] = $_POST['round'];
            }
            if (isset($_POST['venue_id'])) {
                $this->scorecard_data['venue_id'] = $_POST['venue_id'];
            }
            if (isset($_POST['has_ends'])) {
                $this->scorecard_data['has_ends'] = $_POST['has_ends'];
                $has_ends = $_POST['has_ends'] == "Y";
            }
            $this->scorecard_end_data = array();
        }
        print $this->editScorecardPage($has_ends);
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
        if ($_GET["bow"]) {
            $criteria []= 'bow = ?';
            $params []= $_GET["bow"];
        }
        if ($_GET["club-record"] == "current") {
            $criteria []= 'club_record = ?';
            $params []= "current";
        }
        if ($_GET["outdoor"] == "Y") {
            $criteria []= 'outdoor = ?';
            $params []= "Y";
        }
        elseif ($_GET["outdoor"] == "N") {
            $criteria []= 'outdoor = ?';
            $params []= "N";
        }
        if ($_GET["category"]) {
            $criteria []= 'category = ?';
            $params []= $_GET['category'];
        }
        if ($_GET["gender"]) {
            $criteria []= 'gender = ?';
            $params []= $_GET['gender'];
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
        else if ($_GET["upper-date"]) {
            $criteria []= 'date = ?';
            $params []= $this->dateToStoredFormat($_GET["upper-date"]);
        }
        $query = "SELECT * FROM scorecards WHERE "
               . implode(' AND ', $criteria)
               . " ORDER BY date, archer";
        $search_results = $this->fetch($query, $params);
        print $this->searchResultsPage($search_results);
    }

    private function searchResultsPage($search_results) {
        $text = array();
        $text []= '<table>';
        $text []= '<thead>';
        $text []= '<tr>';
        $text []= '<th>Archer</th>';
        $text []= '<th>Outdoor</th>';
        $text []= '<th>Bow</th>';
        $text []= '<th>Age</th>';
        $text []= '<th>Gender</th>';
        $text []= '<th>Round</th>';
        $text []= '<th>Date</th>';
        $text []= '<th>Place Shot</th>';
        $text []= '<th>Hits</th>';
        $text []= '<th>Xs</th>';
        $text []= '<th>Golds</th>';
        $text []= '<th>Tens</th>';
        $text []= '<th>Score</th>';
        $text []= '<th>Handicap</th>';
        $text []= '<th>Cfn</th>';
        $text []= '<th>Nxt Cfn</th>';
        $text []= '<th>Record</th>';
        $text []= '<th>PB</th>';
        $text []= '<th>HCI</th>';
        $text []= '<th>NC</th>';
        $text []= '<th>252</th>';
        $text []= '<th>Medal</th>';
        $text []= '<th>&nbsp;</th>';
        $text []= '</tr>';
        $text []= '</thead>';
        $text []= '<tbody>';
        $odd = true;
        $prev_date = '';
        $count = 0;
        $venue_map = $this->getVenueMap();
        foreach ($search_results as $result) {
            ++$count;
            if ($result['date'] != $prev_date) {
                $odd = !$odd;
                $prev_date = $result['date'];
            }
            $tr_class = $odd ? 'odd' : 'even';
            if ($result['venue_id']) {
                $venue = $venue_map[$result['venue_id']];
            }
            else {
                $venue = '?';
            }
            if ($result['reassessment'] != "N") {
                $tr_class = 'reassessment';
                $venue = '';
                $result['hits'] = '';
                $result['xs'] = '';
                $result['golds'] = '';
                $result['tens'] = '';
                $result['score'] = '';
                $result['handicap_ranking'] = '';
                $result['classification'] = '';
                $result['next_age_group_classification'] = '';
                $result['club_record'] = '';
                $result['personal_best'] = '';
                $result['two_five_two'] = '';
                $result['medal'] = '';
            }
            $text []= "<tr class='$tr_class'>";
            $text []= "<td>$result[archer]</td>";
            $text []= "<td>$result[outdoor]</td>";
            $text []= "<td>$result[bow]</td>";
            $text []= "<td>$result[category]</td>";
            $text []= "<td>$result[gender]</td>";
            $text []= "<td>$result[round]</td>";
            $text []= '<td>' . $this->dateToDisplayedFormat($result['date']) . '</td>';
            $text []= "<td>$venue</td>";
            $text []= "<td>$result[hits]</td>";
            $text []= "<td>$result[xs]</td>";
            $text []= "<td>$result[golds]</td>";
            $text []= "<td>$result[tens]</td>";
            $text []= "<td>$result[score]</td>";
            $text []= "<td>$result[handicap_ranking]</td>";
            $text []= "<td>$result[classification]</td>";
            $text []= "<td>$result[next_age_group_classification]</td>";
            $text []= "<td>$result[club_record]</td>";
            $text []= "<td>$result[personal_best]</td>";
            $text []= "<td>$result[handicap_improvement]</td>";
            $text []= "<td>$result[new_classification]</td>";
            $text []= "<td>$result[two_five_two]</td>";
            $text []= "<td>$result[medal]</td>";
            $text []= "<td>";
            $text []= "<form method='get' action=''>";
            $text []= '<input type="hidden" name="page" value="'
                        . $_GET[page] . '"/>';
            $text []=
            "<input type='hidden' name='scorecard-id' value='$result[scorecard_id]' />";
            $editname = $result['has_ends'] == "Y" ? 'edit-scorecard' : 'edit-score';
            $text []= "<input type='submit' name='$editname' value='Edit' />";
            $text []= "</form>";
            $text []= "</td>";
            $text []= "</tr>\n";
        }
        $text []= '</tbody></table>';
        return "<h1>$count Search Results</h1>" . implode($text);
    }

    /**
     * Generate round data to html that javascript can inspect.
     * TODO replace with wp_localize_script()
     */
    private function roundData() {
        $text = '<span id="round-data">';
        foreach (GNAS_Page::roundData() as $round) {
            $name = $round->getName();
            $text .= '<span name="' . $name . '">';
            $text .= '<span class="measure">'
                   . $round->getMeasure()->getName()
                   . '</span>';
            $text .= '<span class="scoring">'
                   . $round->getScoring()->getName()
                   . '</span>';
            $text .= '<span class="compound-scoring">'
                   . $round->getCompoundScoring()->getName()
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
    private function roundDataAsJSON() {
        $rounds = array();
        foreach (GNAS_Page::roundData() as $round) {
            $round_json = array();
            $round_json['measure'] = $round->getMeasure()->getName();
            $round_json['scoring'] = $round->getScoring()->getName();
            $round_json['compound-scoring'] = $round->getCompoundScoring()->getName();
            $round_json['distances'] = array();
            foreach ($round->getDistances()->rawData() as $distance) {
                $round_json['distances'] []= $distance->getNumArrows();
            }
            $rounds[$round->getName()] = $round_json;
        }
        return json_encode($rounds);
    }

    private function getAllScoreCards($with_ends_only = false) {
        $query = "SELECT * FROM scorecards";
        if ($with_ends_only) {
            $query .= ' WHERE has_ends = "Y"';
        }
        $query .= ' ORDER BY date, handicap_ranking desc, score';
        return $this->fetch($query);
    }

    private function updateHandicapRanking($scorecard_id, $handicap_ranking) {
        $this->exec("UPDATE scorecards SET handicap_ranking = ? WHERE scorecard_id = ?",
                    array($handicap_ranking, $scorecard_id));
    }

    private function updateAgeGroup($scorecard_id, $category) {
        $this->exec("UPDATE scorecards SET category = ? WHERE scorecard_id = ?",
                    array($category, $scorecard_id));
    }

    private function updateGender($scorecard_id, $gender) {
        $this->exec("UPDATE scorecards SET gender = ? WHERE scorecard_id = ?",
                    array($gender, $scorecard_id));
    }

    private function updateClassification($scorecard_id, $classification) {
        $this->exec("UPDATE scorecards SET classification = ? WHERE scorecard_id = ?",
                    array($classification, $scorecard_id));
    }

    private function updateNextAgeGroupClassification($scorecard_id, $classification) {
        $this->exec("UPDATE scorecards SET next_age_group_classification = ? WHERE scorecard_id = ?",
                    array($classification, $scorecard_id));
    }

    private function updateOutdoor($scorecard_id, $outdoor) {
        $this->exec("UPDATE scorecards SET outdoor = ? WHERE scorecard_id = ?",
                    array($outdoor, $scorecard_id));
    }

    private function updateTens($scorecard_id, $tens) {
        $this->exec("UPDATE scorecards SET tens = ? WHERE scorecard_id = ?",
                    array($tens, $scorecard_id));
    }

    private function getHandicapForScore($bow, $round, $score) {
        $compound = $bow == "compound" ? "Y" : "N";
        $rows = $this->fetch("SELECT min(handicap) as result"
                            . " from round_handicaps"
                            . " where round = ? and compound = ? and score <= ?",
                            array($round, $compound, $score));
        return $rows[0]["result"];
    }

    private function reCalculateHandicapsForScores() {
        $scorecards = $this->getAllScoreCards();
        foreach ($scorecards as $scorecard) {
            $handicap_ranking = $this->getHandicapForScore($scorecard['bow'],
                                        $scorecard['round'], $scorecard['score']);
            $this->updateHandicapRanking($scorecard['scorecard_id'], $handicap_ranking);
        }
    }

    private function reCalculateAgeGroups() {
        $scorecards = $this->getAllScoreCards();
        foreach ($scorecards as $scorecard) {
            $category = $this->categoryAt($scorecard['archer'], $scorecard['date']);
            $this->updateAgeGroup($scorecard['scorecard_id'], $category);
        }
    }

    private function reCalculateGenders() {
        $scorecards = $this->getAllScoreCards();
        foreach ($scorecards as $scorecard) {
            $gender = $this->getGender($scorecard['archer']);
            $this->updateGender($scorecard['scorecard_id'], $gender);
        }
    }

    private function getClassification($round, $gender, $category, $bow, $score) {
        return  $this->getRound($round)->getClassification($gender, $category, $bow, $score);
    }

    private function reCalculateClassifications() {
        $scorecards = $this->getAllScoreCards();
        foreach ($scorecards as $scorecard) {
            if ($scorecard['reassessment'] != "N") {
                continue;
            }
            $classification = $this->getClassification($scorecard['round'],
                                                       $scorecard['gender'],
                                                       $scorecard['category'],
                                                       $scorecard['bow'],
                                                       $scorecard['score']);
            $this->updateClassification($scorecard['scorecard_id'], $classification);
            $next_age_group = $this->getNextAgeGroup($scorecard['category']);
            if ($next_age_group) {
                $classification = $this->getClassification($scorecard['round'],
                                                           $scorecard['gender'],
                                                           $next_age_group,
                                                           $scorecard['bow'],
                                                           $scorecard['score']);
                $this->updateNextAgeGroupClassification($scorecard['scorecard_id'], $classification);
            }
        }
    }

    private function getNextAgeGroup($category) {
        switch ($category) {
            case 'U12': return 'U14';
            case 'U14': return 'U16';
            case 'U16': return 'U18';
            case 'U18': return 'adult';
            case 'adult': return ''; # false
            default:
                error_log("unrecognised category: $category");
                return '';
        }
    }

    private function getIsOutdoor($round) {
        $isOutdoor = $this->getRound($round)->isOutdoor();
        return $isOutdoor ? "Y" : "N";
    }

    private function reCalculateOutdoor() {
        $scorecards = $this->getAllScoreCards();
        foreach ($scorecards as $scorecard) {
            $isOutdoor = $this->getIsOutdoor($scorecard['round']);
            $this->updateOutdoor($scorecard['scorecard_id'], $isOutdoor);
        }
    }

    private function reCalculateTens() {
        $scorecards = $this->getAllScoreCards(true);
        foreach ($scorecards as $scorecard) {
            $tens = $this->countTensFromTable($scorecard['scorecard_id']);
            $this->updateTens($scorecard['scorecard_id'], $tens);
        }
    }

    private function reCalculateRecords() {
        $this->insertReasessments();
        $scorecards = $this->getAllScoreCards();
        $accumulator = new RHAC_ScorecardAccumulator();
        foreach ($scorecards as $scorecard) {
            $accumulator->accept($scorecard);
        }
        $changes = $accumulator->results();
        foreach ($changes as $scorecard_id => $change) {
            $this->updateRecords($scorecard_id, $change);
        }
    }

    private function updateRecords($scorecard_id, $changes) {
        $params = array();
        $arguments = array();
        $update_statement = "UPDATE scorecards SET ";
        foreach ($changes as $key => $value) {
            $arguments []= "$key = ?";
            $params []= $value;
        }
        $update_statement .= implode(', ', $arguments);
        $update_statement .= " WHERE scorecard_id = ?";
        $params []= $scorecard_id;
        $this->exec($update_statement, $params);
    }

    private function insertReasessments() {
        $scorecards = $this->getAllScoreCards();
        $inserter = new RHAC_ReassesmentInserter($this->getArcherMap());
        foreach ($scorecards as $scorecard) {
            $inserter->accept($scorecard);
        }
        foreach ($inserter->results() as $change) {
            if ($change['action'] == 'insert') {
                $this->insertReasessment($change);
            }
            elseif ($change['action'] == 'delete') {
                $this->deleteReasessment($change);
            }
        }
    }

    private function insertReasessment($change) {
        $venue = $change['outdoor'] == 'Y' ? 'Outdoor' : 'Indoor';
        $round = $change['reassessment'] == 'end_of_season'
            ? "End of $venue Season Reassessment"
            : "Age Group Reassessment";
        $defaults = array(
            'archer' => '',
            'date' => '0001/01/01',
            'round' => $round,
            'bow' => '',
            'hits' => 0,
            'xs' => 0,
            'tens' => 0,
            'golds' => 0,
            'score' => 0,
            'venue_id' => $venue == 'Outdoor' ? 1 : 2,
            'handicap_ranking' => 101,
            'has_ends' => 'N',
            'classification' => '',
            'outdoor' => '',
            'club_record' => 'N',
            'personal_best' => 'N',
        );
        foreach ($change as $key => $value) {
            if ($key == 'action') continue;
            $defaults[$key] = $value;
        }
        $keys = array();
        $placeholders = array();
        $params = array();
        foreach ($defaults as $key => $value) {
            $keys []= $key;
            $placeholders []= '?';
            $params []= $value;
        }
        $query = "INSERT INTO scorecards("
            . implode(',', $keys)
            . ") VALUES("
            . implode(',', $placeholders)
            . ")";
        $this->exec($query, $params);

    }

    private function deleteReasessment($change) {
    }

    private function countTensFromTable($id) {
        $ends = $this->fetchScorecardEnds($id);
        $total = 0;
        foreach ($ends as $end) {
            for ($arrow = 1; $arrow <= 6; ++ $arrow) {
                $score = $end["arrow_$arrow"];
                if ($score == "10" || $score == "X") {
                    ++$total;
                }
            }
        }
        return $total;
    }

    private function rebuildRoundHandicaps() {
        $this->pdo->beginTransaction();
        $this->deleteAllRoundHandicaps();
        foreach (GNAS_Page::roundData() as $round) {
            $this->populateSingleRoundHandicaps('N', $round->getScoring(), $round);
            $this->populateSingleRoundHandicaps('Y', $round->getCompoundScoring(), $round);
        }
        $this->pdo->commit();
    }

    private function deleteAllRoundHandicaps() {
        $this->exec("DELETE FROM round_handicaps");
    }

    private function populateSingleRoundHandicaps($compoundYN, $scoring, $round) {
        $scoring_name = $scoring->getName();
        $measure = $round->getMeasure()->getName();
        $distances = $round->getDistances()->asArray();
        $round_name = $round->getName();
        $previous_prediction = -1;
        for ($handicap = 100; $handicap >= 0; --$handicap) {
            $calc = RHAC_Handicap::getCalculator($scoring_name, $handicap, $measure, $distances, 0.357);
            $predicted_score = $calc->predict();
            if ($previous_predicction != $predicted_score) {
                $this->insertOneRoundHandicap($round_name, $compoundYN, $predicted_score, $handicap);
                $previous_predicction = $predicted_score;
            }
        }
    }

    private function insertOneRoundHandicap($round_name, $compoundYN, $score, $handicap) {
        $this->exec("INSERT INTO round_handicaps(round, compound, score, handicap) values(?,?,?,?)",
            array($round_name, $compoundYN, $score, $handicap)
        );
    }

    public function roundDataAsSelect() {
        // echo '<p>in roundDataAsSelect()</p>';
        $text = array('<select name="round" id="round">');
        $text []= "<option value=''>- - -</option>\n";
        foreach (GNAS_Page::roundData() as $round) {
            // echo '<p>roundDataAsSelect() got round</p>';
            $text []= "<option value='" . $round->getName() . "'";
            if ($round->getName() == $this->scorecard_data['round']) {
                $text []= " selected='1'";
            }
            $text []= ">" . $round->getName() . "</option>\n";
        }
        $text []= '<select>';
        // echo '<p>roundDataAsSelect() returning</p>';
        return implode($text);
    }

    public function bowDataAsSelect() {
        $bows = array('recurve', 'compound', 'longbow', 'barebow');
        $text = array('<select name="bow" id="bow">');
        $text []= "<option value=''>- - -</option>\n";
        foreach ($bows as $bow) {
            $text []= "<option value='$bow'>$bow</option>\n";
        }
        $text []= '<select>';
        return implode($text);
    }

    public function categoryAsSelect() {
        $categories = array('adult', 'U18', 'U16', 'U14', 'U12');
        $text = array('<select name="category" id="category">');
        $text []= "<option value=''>- - -</option>\n";
        foreach ($categories as $category) {
            $text []= "<option value='$category'>$category</option>\n";
        }
        $text []= '<select>';
        return implode($text);
    }

    public function genderAsSelect() {
        $genders = array('M', 'F');
        $text = array('<select name="gender" id="gender">');
        $text []= "<option value=''>- - -</option>\n";
        foreach ($genders as $gender) {
            $text []= "<option value='$gender'>$gender</option>\n";
        }
        $text []= '<select>';
        return implode($text);
    }

    private function clubRecordAsRadio() {
        $text = array();
        $text []= '<input type="radio" name="club-record" checked="1" value="">&nbsp;don\'t care&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="club-record" value="current">&nbsp;current&nbsp;</input>&nbsp;';
        return implode($text);
    }

    private function venueAsRadio() {
        $text = array();
        $text []= '<input type="radio" name="outdoor" checked="1" value="Y">&nbsp;Outdoor&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="outdoor" value="N">&nbsp;Indoor&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="outdoor" value="">&nbsp;Both&nbsp;</input>&nbsp;';
        return implode($text);
    }

    private function homePage() {
        // echo '<p>in homePage()</p>';
        print $this->homePageHTML();
        // echo '<p>finished homePage()</p>';
    }

    private function getArcherMap() {
        if (!isset($this->archer_map)) {
            $this->archer_map = array();
            $rows = $this->fetch('select * from archer');
            foreach ($rows as $row) {
                $this->archer_map[$row['name']] = $row;
            }
        }
        return $this->archer_map;
    }

    private function getDoB($archer) {
        $archer_map = $this->getArcherMap();
        return $archer_map[$archer]['date_of_birth'];
    }

    private function getGender($archer) {
        $archer_map = $this->getArcherMap();
        return $archer_map[$archer]['gender'];
    }

    private function ageAt($archer, $date_string) {
        $dob_string = $this->getDoB($archer);
        $dob = $this->unixdate($dob_string);
        $date = $this->unixdate($date_string);
        $diff = $date - $dob;
        return floor($diff / (60 * 60 * 24 * 365.242));
    }

    private function unixdate($date_string) {
        $y = substr($date_string, 0, 4) + 0;
        if ($y < 1970) {
            $y = 1970;
        }
        $m = substr($date_string, 5, 2) + 0;
        $d = substr($date_string, 8, 2) + 0;
        return mktime(0, 0, 0, $m, $d, $y, 0);
    }

    private function getVenueMap() {
        $rows = $this->fetch("select * from venue");
        $map = array();
        foreach ($rows as $row) {
            $map[$row['venue_id']] = $row['name'];
        }
        return $map;
    }

    private function categoryAt($archer, $date_string) {
        $age = $this->ageAt($archer, $date_string);
        if ($age < 12) {
            return 'U12';
        }
        else if ($age < 14) {
            return 'U14';
        }
        else if ($age < 16) {
            return 'U16';
        }
        else if ($age < 18) {
            return 'U18';
        }
        else {
            return 'adult';
        }
    }

    private function searchForm() {
        $text = array();
        $text []= '<form method="get" action="">';
        $text []= '<input type="hidden" name="page" value="'
                    . $_GET[page] . '"/>';
        $text []= '<table>';
        $text []= '<tr><td>Archer</td><td colspan="2">';
        $text []= $this->archersAsSelect('archer', true);
        $text []= '</td></tr>';
        $text []= '<tr><td>Round</td><td colspan="2">';
        $text []= $this->roundDataAsSelect();
        $text []= '</td></tr>';
        $text []= '<tr><td>Bow</td><td colspan="2">';
        $text []= $this->bowDataAsSelect();
        $text []= '</td></tr>';
        $text []= '<tr><td>Age Group</td><td colspan="2">';
        $text []= $this->categoryAsSelect();
        $text []= '</td></tr>';
        $text []= '<tr><td>Gender</td><td colspan="2">';
        $text []= $this->genderAsSelect();
        $text []= '</td></tr>';
        $text []= '<tr><td>Club Record</td><td colspan="2">';
        $text []= $this->clubRecordAsRadio();
        $text []= '</td></tr>';
        $text []= '<tr><td>Venue</td><td colspan="2">';
        $text []= $this->venueAsRadio();
        $text []= '</td></tr>';
        $text []= '<tr><td>Date or Date Range</td>';
        $text []= '<td>';
        $text []=
            '<input type="text" name="lower-date" id="datepicker-lower"/>';
        $text []= '</td>';
        $text []= '<td>';
        $text []=
            '<input type="text" name="upper-date" id="datepicker-upper"/>';
        $text []= '</td>';
        $text []= '</tr>';
        $text []= '</table>';
        $text []=
            '<input type="submit" name="find-scorecard" value="Search" />';
        $text []= '</form>';
        return implode($text);
    }

    private function newScorecardForm() {
        $text = array();
        $text []= '<form method="get" action="">';
        $text []= '<input type="hidden" name="page" value="'
                    . $_GET[page] . '"/>';
        $text []= '<p>';
        $text []= '<input type="submit" name="edit-scorecard" value="New Scorecard" />';
        $text []= '</p>';
        $text []= '<p>';
        $text []= '<input type="submit" name="edit-score" value="New Score" />';
        $text []= '</p>';
        $text []= '</form>';
        return implode($text);
    }

    private function newArcherForm() {
        $text = array();
        $text []= '<form method="post" action="">';

        $text []= '<label for="archer">Archer:</label>&nbsp;';
        $text []= '<input type="text" name="archer"/>';

        $text []= '&nbsp;<label for="dob">DoB:&nbsp;</label>&nbsp;';
        $text []= '<input type="text" maxlength="10" name="dob" value="0001/01/01"/>';

        $text []= '&nbsp;<label for="gender">Gender:</label>&nbsp;';
        $text []= '<input type="radio" name="gender" checked="1" value="M">&nbsp;M&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="gender" value="F">&nbsp;F&nbsp;</input>&nbsp;';

        $text []= '&nbsp;<label for="archived">Archived:</label>&nbsp;';
        $text []= '<input type="radio" name="archived" value="Y">&nbsp;Y&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="archived" checked="1" value="N">&nbsp;N&nbsp;</input>&nbsp;';

        $text []= '&nbsp;<input type="submit" name="add-archer" value="Add Archer"/>';

        $text []= '</form>';
        return implode($text);
    }


    private function newVenueForm() {
        $text = array();
        $text []= '<form method="post" action="">';

        $text []= '<label for="venue">Venue:</label>&nbsp;';
        $text []= '<input type="text" name="venue"/>';

        $text []= '&nbsp;<input type="submit" name="add-venue" value="Add Venue"/>';

        $text []= '</form>';
        return implode($text);
    }

    private function deleteArcherForm() {
        $text = array();
        $text []= '<form method="post" action="">';
        $text []= $this->archersAsSelect('archer', true);
        $text []= '<input type="submit" name="delete-archer" value="Delete Archer"/>';
        $text []= '</form>';
        return implode($text);
    }

    private function mergeArcherForm() {
        $text = array();
        $text []= '<form method="post" id="merge-archers" action="">';
        $text []= '<label for="from-archer">From</label>';
        $text []= $this->archersAsSelect("from-archer", true);
        $text []= '<label for="to-archer">To</label>';
        $text []= $this->archersAsSelect("to-archer", true);
        $text []= '<input type="submit" name="merge-archers" value="Merge Archers"/>';
        $text []= '</form>';
        return implode($text);
    }

    private function rebuildRoundHandicapsForm() {
        return <<<EOFORM
</p>
<form method="post" id="rebuild-round-handicaps" action="">
<input type="submit" name="rebuild-round-handicaps" value="Rebuild All Handicap Tables"/>
</form>
You only need do this if a new round has been added or a round definition changed.
</p>
EOFORM;
    }

    private function reCalculateHandicapsForScoresForm() {
        return <<<EOFORM
</p>
<form method="post" id="recalculate-score-handicaps" action="">
<input type="submit" name="recalculate-score-handicaps" value="Recalculate All Score Handicaps"/>
</form>
</p>
EOFORM;
    }

    private function reCalculateAgeGroupsForm() {
        return <<<EOFORM
</p>
<form method="post" id="recalculate-age-groups" action="">
<input type="submit" name="recalculate-age-groups" value="Recalculate All Age Groups"/>
</form>
</p>
EOFORM;
    }

    private function reCalculateGendersForm() {
        return <<<EOFORM
</p>
<form method="post" id="recalculate-genders" action="">
<input type="submit" name="recalculate-genders" value="Recalculate All Genders"/>
</form>
</p>
EOFORM;
    }

    private function reCalculateClassificationsForm() {
        return <<<EOFORM
</p>
<form method="post" id="recalculate-classifications" action="">
<input type="submit" name="recalculate-classifications" value="Recalculate All Classifications"/>
</form>
</p>
EOFORM;
    }

    private function reCalculateOutdoorForm() {
        return <<<EOFORM
</p>
<form method="post" id="recalculate-outdoor" action="">
<input type="submit" name="recalculate-outdoor" value="Reassign Outdoor and Indoor Rounds"/>
</form>
</p>
EOFORM;
    }

    private function reCalculateTensForm() {
        return <<<EOFORM
</p>
<form method="post" id="recalculate-tens" action="">
<input type="submit" name="recalculate-tens" value="Recount all Tens"/>
</form>
</p>
EOFORM;
    }

    private function reCalculateRecordsForm() {
        return <<<EOFORM
</p>
<form method="post" id="recalculate-records" action="">
<input type="submit" name="recalculate-records" value="Recalculate Club Records"/>
</form>
</p>
EOFORM;
    }

    public function homePageHTML() {
        $text = array();
        $text []= '<h1>Score Cards</h1>';
        $text []= '<div id="rhac-admin-accordion">';
        $text []= '<h3>Search</h3><div>';
        $text []= $this->searchForm();
        $text []= $this->twoFiveTwoLink();
        $text []= "</div>";
        $text []= '<h3>Scorecards</h3><div>';
        $text []= $this->newScorecardForm();
        $text []= $this->reCalculateRecordsForm();
        $text []= "</div>";
        $text []= '<h3>Data</h3><div>';
        $text []= $this->newArcherForm();
        $text []= $this->deleteArcherForm();
        $text []= $this->mergeArcherForm();
        $text []= $this->NewVenueForm();
        $text []= "</div>";
        $text []= '<h3>Admin</h3><div>';
        $text []= $this->reCalculateGendersForm();
        $text []= $this->reCalculateAgeGroupsForm();
        $text []= $this->reCalculateHandicapsForScoresForm();
        $text []= $this->reCalculateClassificationsForm();
        $text []= $this->reCalculateOutdoorForm();
        $text []= $this->reCalculateTensForm();
        $text []= $this->rebuildRoundHandicapsForm();
        $text []= "<a href='" . $this->homeurl . 'scorecard.db' ."'>Download a backup</a> (click right and save as...)";
        $text []= "</div>";
        $text []= "</div>";
        return implode($text);
    }

    private function twoFiveTwoLink() {
        return "<a href='" . $this->curPageURL() . "&two-five-two=y'>252 Results</a>";
    }

    private function curPageURL() {
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
            $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

    private function two_five_two() {
        RHAC_Archer252::init();
        $rows = $this->fetch(<<<EOT
select * from scorecards
where round in ('Green 252', 'White 252', 'Black 252', 'Blue 252', 'Red 252',
                'Bronze 252', 'Silver 252', 'Gold 252')
order by date, archer, round, bow
EOT
);
        foreach ($rows as $row) {
            RHAC_Archer252::addRow($row);
        }
        print RHAC_Archer252::getResultsHTML();
    }

    private function dateAsInput() {
        $text = array();
        $text []= '<input type="text" name="date" size="16" ';
        if ($this->scorecard_data['date']) {
            $text []= "value='" . $this->scorecard_data["date"] . "'";
        }
        $text []= 'id="date"/>';
        return implode($text);
    }

    private function archers() {
        $archer_map = $this->getArcherMap();
        return array_keys($archer_map);
    }

    private function venueAsSelect() {
        $venue_map = $this->getVenueMap();
        $text = array("<select name='venue_id' id='venue_id'>");
        foreach ($venue_map as $id => $name) {
            $text []= "<option value='$id'"
                . ($id == $this->scorecard_data['venue_id']
                    ? ' selected="1"'
                    : '')
                . ">$name</option>";
        }
        $text []= '</select>';
        return implode("\n", $text);
    }

    private function archerIsArchived($archer) {
        $row = RHAC_Scorecards::getInstance()->fetch(
            "SELECT archived FROM archer where name = ?",
            array($archer)
        );
        if (count($row) == 0) {
            return false;
        }
        if ($row[0]['archived'] == 'N') {
            return false;
        }
        return true;
    }

    public function archersAsSelect($id = 'archer', $include_archived = false) {
        if ($this->scorecard_data["archer"] && $this->archerIsArchived($this->scorecard_data["archer"])) {
            $include_archived = true;
        }
        $text = array("<select name='$id' id='$id'>");
        $text []= "<option value=''>- - -</option>\n";
        if ($include_archived) {
            $exclude_archived = '';
        }
        else {
            $exclude_archived = 'where archived = "N"';
        }
        $archers = RHAC_Scorecards::getInstance()->fetch(
                            "SELECT name FROM archer $exclude_archived ORDER BY name");
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

    private function bowsAsRadio() {
        $text = array();
        foreach(array('R' => 'recurve',
                      'C' => 'compound',
                      'L' => 'longbow',
                      'B' => 'barebow') as $initial => $bow) {
            $text []= '<input type="radio" name="bow" id="bow"';
            if ($this->scorecard_data['bow'] == $bow) {
                $text []= " checked='1'";
            }
            $text []= " value='$bow'>$initial&nbsp;&nbsp;</input>\n";
        }
        return implode($text);
    }

    private function medalsAsRadio() {
        $text = array();
        foreach(array('N' => '',
                      'B' => 'bronze',
                      'S' => 'silver',
                      'G' => 'gold') as $initial => $medal) {
            $text []= '<input type="radio" name="medal" id="medal"';
            if ($this->scorecard_data['medal'] == $medal) {
                $text []= " checked='1'";
            }
            $text []= " value='$medal'>$initial&nbsp;&nbsp;</input>\n";
        }
        return implode($text);
    }

    private function endDataAsTBody() {
        $text = array();
        $text []= '<tbody id="scorecard">';
        $end = 0;
        for ($dozen = 1; $dozen < 13; ++$dozen) {
            $text []= '<tr>' . "\n<th>$dozen</th>\n";
            foreach (array('odd', 'even') as $pos) {
                $end++;
                for ($arrow = 1; $arrow < 7; ++$arrow) {
                    $text []= " <td><input type='text'"
                        . " class='score'"
                        . "value='"
                        . $this->scorecard_end_data[$end -1]["arrow_$arrow"]
                        . "'"
                        . " name='arrow-$end-$arrow'"
                        . " id='arrow-$end-$arrow'/></td>\n";
                }
                $text []= " <td class='end' name='end-total-$end'"
                    . " id='end-total-$end'>&nbsp;</td>\n";
            }
            $text []= " <td class='hits' name='doz-hits-$dozen'"
                . " id='doz-hits-$dozen'>&nbsp;</td>\n";
            $text []= " <td class='Xs' name='doz-xs-$dozen'"
                . " id='doz-xs-$dozen'>&nbsp;</td>\n";
            $text []= " <td class='golds' name='doz-golds-$dozen'"
                . " id='doz-golds-$dozen'>&nbsp;</td>\n";
            $text []= " <td class='doz' name='doz-doz-$dozen'"
                . " id='doz-doz-$dozen'>&nbsp;</td>\n";
            $text []= " <td class='tot' name='doz-tot-$dozen'"
                . " id='doz-tot-$dozen'>&nbsp;</td>\n";
            $text []= "</tr>\n";
        }
        $text []= '</tbody>';
        return implode($text);
    }

    private function tenZoneData() {
        return array(
            array('label' => 'X', 'png' => 'gold', 'width' => 15),
            array('label' => '10', 'png' => 'gold', 'width' => 15),
            array('label' => '9', 'png' => 'gold', 'width' => 30),
            array('label' => '8', 'png' => 'red', 'width' => 30),
            array('label' => '7', 'png' => 'red', 'width' => 30),
            array('label' => '6', 'png' => 'blue', 'width' => 30),
            array('label' => '5', 'png' => 'blue', 'width' => 30),
            array('label' => '4', 'png' => 'black', 'width' => 30),
            array('label' => '3', 'png' => 'black', 'width' => 30),
            array('label' => '2', 'png' => 'white', 'width' => 30),
            array('label' => '1', 'png' => 'white', 'width' => 30),
            array('label' => 'M', 'png' => 'green', 'width' => 30)
        );
    }

    private function tenZoneChart() {
        return $this->zoneChart($this->tenZoneData(),
                                'TenZoneChart', 'tbar_');
    }

    private function tenZoneCompoundData() {
        return array(
            array('label' => '10', 'png' => 'gold', 'width' => 15),
            array('label' => '9', 'png' => 'gold', 'width' => 45),
            array('label' => '8', 'png' => 'red', 'width' => 30),
            array('label' => '7', 'png' => 'red', 'width' => 30),
            array('label' => '6', 'png' => 'blue', 'width' => 30),
            array('label' => '5', 'png' => 'blue', 'width' => 30),
            array('label' => '4', 'png' => 'black', 'width' => 30),
            array('label' => '3', 'png' => 'black', 'width' => 30),
            array('label' => '2', 'png' => 'white', 'width' => 30),
            array('label' => '1', 'png' => 'white', 'width' => 30),
            array('label' => 'M', 'png' => 'green', 'width' => 30)
        );
    }

    private function tenZoneCompoundChart() {
        return $this->zoneChart($this->tenZoneCompoundData(),
                                'TenZoneCompoundChart', 'tcbar_');
    }

    private function fiveZoneData() {
        return array(
            array('label' => '9', 'png' => 'gold', 'width' => 50),
            array('label' => '7', 'png' => 'red', 'width' => 50),
            array('label' => '5', 'png' => 'blue', 'width' => 50),
            array('label' => '3', 'png' => 'black', 'width' => 50),
            array('label' => '1', 'png' => 'white', 'width' => 50),
            array('label' => 'M', 'png' => 'green', 'width' => 50)
        );
    }

    private function fiveZoneChart() {
        return $this->zoneChart($this->fiveZoneData(),
                                'FiveZoneChart', 'fbar_');
    }

    private function vegasData() {
        return array(
            array('label' => 'X', 'png' => 'gold', 'width' => 25),
            array('label' => '10', 'png' => 'gold', 'width' => 25),
            array('label' => '9', 'png' => 'gold', 'width' => 50),
            array('label' => '8', 'png' => 'red', 'width' => 50),
            array('label' => '7', 'png' => 'red', 'width' => 50),
            array('label' => '6', 'png' => 'blue', 'width' => 50),
            array('label' => 'M', 'png' => 'green', 'width' => 50)
        );
    }

    private function vegasChart() {
        return $this->zoneChart($this->vegasData(),
                                'VegasChart', 'vbar_');
    }

    private function worcesterData() {
        return array(
            array('label' => '5', 'png' => 'white', 'width' => 50),
            array('label' => '4', 'png' => 'black', 'width' => 50),
            array('label' => '3', 'png' => 'black', 'width' => 50),
            array('label' => '2', 'png' => 'black', 'width' => 50),
            array('label' => '1', 'png' => 'black', 'width' => 50),
            array('label' => 'M', 'png' => 'green', 'width' => 50)
        );
    }

    private function worcesterChart() {
        return $this->zoneChart($this->worcesterData(),
                                'WorcesterChart', 'wbar_');
    }

    private function vegasInnerTenData() {
        return array(
            array('label' => '10', 'png' => 'gold', 'width' => 25),
            array('label' => '9', 'png' => 'gold', 'width' => 75),
            array('label' => '8', 'png' => 'red', 'width' => 50),
            array('label' => '7', 'png' => 'red', 'width' => 50),
            array('label' => '6', 'png' => 'blue', 'width' => 50),
            array('label' => 'M', 'png' => 'green', 'width' => 50)
        );
    }

    private function vegasInnerTenChart() {
        return $this->zoneChart($this->vegasInnerTenData(),
                                'VegasInnerTenChart', 'vtbar_');
    }

    private function zoneChart($zoneData, $tableId, $idPrefix) {
        $text = array();
        $text []= '<table id="' . $tableId . '"><tr>';
        foreach ($zoneData as $zone) {
            $text []= '<td class="bar">';
            $text []= '<img id="'
                    . $idPrefix
                    . $zone['label']
                    . '" src="'
                    . $this->png($zone['png'])
                    . '" height="425" width="' . $zone['width'] . '"/>';
            $text []= '</td>';
        }
        $text []= '</tr></table>';
        return implode($text);
    }

    private function png($basename) {
        return $this->homeurl . $basename . '.png';
    }

    private function zoneCharts() {
        $text = array();
        $text []= $this->tenZoneChart();
        $text []= $this->tenZoneCompoundChart();
        $text []= $this->fiveZoneChart();
        $text []= $this->vegasChart();
        $text []= $this->vegasInnerTenChart();
        $text []= $this->worcesterChart();
        $text []= '<p><b>Average:</b> <span id="average">-</span></p>';
        return implode($text);
    }

    private function scorecardHeaderRow() {
        $text = array();
        $text []= '<tr>';
        $text []= '<th colspan="7">&nbsp;</th>';
        $text []= '<th>End</th>';
        $text []= '<th colspan="6">&nbsp;</th>';
        $text []= '<th>End</th>';
        $text []= '<th>Hits</th>';
        $text []= '<th>Xs</th>';
        $text []= '<th>Golds</th>';
        $text []= '<th>Doz</th>';
        $text []= '<th>Tot</th>';
        $text []= '</tr>';
        return implode($text);
    }

    private function tFooter() {
        $text = array();
        $text []= '<tfoot>';
        $text []= '<tr>';
        $text []= '<th colspan="15">Totals:</th>';
        $text []= '<td class="total-hits" id="total-hits">&nbsp;</td>';
        $text []= '<td class="total-Xs" id="total-xs">&nbsp;</td>';
        $text []= '<td class="total-golds" id="total-golds">&nbsp;</td>';
        $text []= '<td>&nbsp;</td>';
        $text []= '<td class="total-total" id="total-total" >&nbsp;</td>';
        $text []= '</tr>';
        $text []= '</tfoot>';
        return implode($text);
    }

    private function tHeader() {
        $text = array();
        $text []= '<thead>';
        $text []= '<tr>';
        $text []= '<th colspan="4">Archer</th>';
        $text []= '<td colspan="7">';
        $text []= $this->archersAsSelect();
        $text []= '</td>';
        $text []= '<th colspan="3">Bow</th>';
        $text []= '<td colspan="6">';
        $text []= $this->bowsAsRadio();
        $text []= '</td>';
        $text []= '</tr>';
        $text []= '<tr>';
        $text []= '<th colspan="4">Round</th>';
        $text []= '<td colspan="7">';
        $text []= $this->roundDataAsSelect();
        $text []= '</td>';
        $text []= '<th colspan="3">Date</th>';
        $text []= '<td colspan="6">';
        $text []= $this->dateAsInput();
        $text []= '</td>';
        $text []= '</tr>';
        $text []= '<tr>';
        $text []= '<th colspan="4">Venue</th>';
        $text []= '<td colspan="7">';
        $text []= $this->venueAsSelect();
        $text []= '</td>';
        $text []= '<th colspan="3">Medal</th>';
        $text []= '<td colspan="6">';
        $text []= $this->medalsAsRadio();
        $text []= '</td>';
        $text []= '</tr>';
        $text []= $this->scorecardHeaderRow();
        $text []= '</thead>';
        return implode($text);
    }

    private function formControls($has_ends) {
        $has_ends_yn = $has_ends ? "Y" : "N";
        $text = array();
        $text []= "<input type='hidden' name='has_ends' value='$has_ends_yn' />";
        $text []= '<input type="hidden" name="scorecard-id" value="'
                . $this->scorecard_id . '" />';
        $text []= '<input type="hidden"'
                . ' name="total-hits" id="i-total-hits" />';
        $text []= '<input type="hidden"'
                . ' name="total-xs" id="i-total-xs" />';
        $text []= '<input type="hidden"'
                . ' name="total-golds" id="i-total-golds" />';
        $text []= '<input type="hidden"'
                . ' name="total-total" id="i-total-total" />';
        $text []= '<input type="submit" name="submit-scorecard-and-edit" value="Submit and Edit" />';
        $text []= '<input type="submit" name="submit-scorecard-and-new" value="Submit and New" />';
        $text []= '<input type="submit" name="submit-scorecard-and-finish" value="Submit and Finish" />';
        return implode($text);
    }

    private function scorecardTable() {
        $text = array();
        $text []= '<table>';
        $text []= $this->tHeader();
        $text []= $this->endDataAsTBody();
        $text []= $this->tFooter();
        $text []= '</table>';
        return implode($text);
    }

    private function scorecardForm() {
        $text = array();
        $text []= '<form method="post" action="" id="edit-scorecard">';
        $text []= $this->scorecardTable();
        $text []= $this->formControls(true);
        $text []= '</form>';
        return implode($text);
    }

    private function scoreForm() {
        $text = array();
        $text []= '<form method="post" action="" id="edit-score">';
        $text []= '<table>';
        $text []= '<thead>';
        $text []= '<tr>';
        $text []= '<th>Archer</th>';
        $text []= '<th>Bow</th>';
        $text []= '<th>Round</th>';
        $text []= '<th>Date</th>';
        $text []= '<th>Venue</th>';
        $text []= '<th>Hits</th>';
        $text []= '<th>Xs</th>';
        $text []= '<th>Golds</th>';
        $text []= '<th>Tens</th>';
        $text []= '<th>Score</th>';
        $text []= '<th>Medal</th>';
        $text []= '</tr>';
        $text []= '</thead>';
        $text []= '<tbody>';
        $text []= '<tr>';
        $text []= '<td>' . $this->archersAsSelect('archer', true) . '</td>';
        $text []= '<td>' . $this->bowsAsRadio() . '</td>';
        $text []= '<td>' . $this->roundDataAsSelect() . '</td>';
        $text []= '<td>' . $this->dateAsInput() . '</td>';
        $text []= '<td>' . $this->venueAsSelect() . '</td>';
        $text []= '<td><input type="text" name="total-hits" size="4" value="' . $this->scorecard_data['hits'] . '" /></td>';
        $text []= '<td><input type="text" name="total-xs" size="4" value="' . $this->scorecard_data['xs'] . '" /></td>';
        $text []= '<td><input type="text" name="total-golds" size="4" value="' . $this->scorecard_data['golds'] . '" /></td>';
        $text []= '<td><input type="text" name="total-tens" size="4" value="' . $this->scorecard_data['tens'] . '" /></td>';
        $text []= '<td><input type="text" name="total-total" size="6" value="' . $this->scorecard_data['score'] . '" /></td>';
        $text []= '<td>' . $this->medalsAsRadio() . '</td>';
        $text []= '</tr>';
        $text []= '</tbody>';
        $text []= '</table>';
        $text []= "<input type='hidden' name='has_ends' value='N' />";
        $text []= '<input type="hidden" name="scorecard-id" value="'
                . $this->scorecard_id . '" />';
        $text []= '<input type="submit" name="submit-score-and-edit" value="Submit and Edit" />';
        $text []= '<input type="submit" name="submit-score-and-new" value="Submit and New" />';
        $text []= '<input type="submit" name="submit-score-and-finish" value="Submit and Finish" />';
        $text []= '</form>';
        return implode($text);
    }

    private function deleteScorecardButton() {
        $text = array();
        if ($this->scorecard_id) {
            $text []= '<form method="post" action="" id="delete-scorecard">';
            $text []= '<input type="hidden" name="scorecard-id" value="';
            $text []= $this->scorecard_id;
            $text []= '"/>';
            $text []= '<input type="submit" name="delete-scorecard"';
            $text []= ' value="Delete" />';
            $text []= '</form>';
        }
        return implode($text);
    }

    private function helpBox() {
        $text = array();
        $text []= '<button id="help-button">help</button>';
        $text []= '<br/>';
        $text []= '<div id="help-text">';
        $text []= '<p>';
        $text []= '';
        $text []= 'Select the Archer and Round from the drop down menus.';
        $text []= ' You can add new archers from the main admin page.';
        $text []= ' Next, select the Bow type and the Date.';
        $text []= ' Click on the first arrow and start entering scores.';
        $text []= ' Valid inputs are <q>x</q>, <q>0</q>-<q>9</q> and <q>m</q>.';
        $text []= ' <b>Note</b> Use a <q>0</q> to enter a 10.';
        $text []= ' If you have selected an imperial round, any scores that';
        $text []= ' you enter will be';
        $text []= ' rounded down to the nearest valid five-zone score.';
        $text []= '</p>';
        $text []= '</div>';
        return implode($text);
    }

    private function cancelButton() {
        $text = array();
        $text []= '<form method="get" action="">';
        if ($_GET['page']) {
            $text []= '<input type="hidden" name="page" value="' . $_GET['page'] . '">';
        }
        $text []= '<input type="submit" name="cancel" value="Cancel">';
        $text []= '</form>';
        return implode($text);
    }

    private function editScorecardPage($has_ends) {
        $text = array();
        $text []= '<h1>Edit Score';
        if ($has_ends) {
            $text []= ' Card';
        }
        if ($this->scorecard_id) {
            $text []= ' #' . $this->scorecard_id;
        }
        $text []= '</h1>';
        $text []= $this->helpBox();
        $text []= $this->deleteScorecardButton();
        $text []= $this->cancelButton();
        $text []= $this->roundData();
        if ($has_ends) {
            $text []= $this->scorecardForm();
            $text []= $this->zoneCharts();
        } else {
            $text []= $this->scoreForm();
        }
        return implode($text);
    }

}



