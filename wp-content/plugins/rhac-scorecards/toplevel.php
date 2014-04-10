<?php

include_once plugin_dir_path(__FILE__) . '../gnas-archery-rounds/rounds.php';

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
    private static $instance;

    private function __construct() {
        $this->homepath = plugin_dir_path(__FILE__);
        $this->homeurl = plugin_dir_url(__FILE__);
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
        } elseif (isset($_GET['edit-scorecard'])) { // edit or create requested
            if ($_GET['scorecard-id']) { // edit requested
                $this->edit($_GET['scorecard-id']);
            }
            else { // create requested
                $this->edit(0);
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
        $id = $_POST['scorecard-id'];
        $handicap_ranking = $this->getHandicapForScore($_POST['bow'], $_POST['round'], $_POST['total-total']);
        $params = array(
            $_POST['archer'],
            $_POST['venue'],
            $this->dateToStoredFormat($_POST['date']),
            $_POST['round'],
            $_POST['bow'],
            $_POST['total-hits'],
            $_POST['total-xs'],
            $_POST['total-golds'],
            $_POST['total-total'],
            $_POST['has-ends'],
            $handicap_ranking,
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
                 . " has_ends = ?,"
                 . " handicap_ranking = ?"
                 . " WHERE scorecard_id = ?",
                    $params);
        if ($_POST['has-ends'] == "Y") {
            $this->exec("DELETE FROM scorecard_end WHERE scorecard_id = ?",
                             array($id));
            $this->insertEnds($id);
        }
        $this->pdo->commit();
    }

    private function insert() {
        $handicap_ranking = $this->getHandicapForScore($_POST['bow'], $_POST['round'], $_POST['total-total']);
        $this->pdo->beginTransaction();
        // echo '<p>insert() inside transaction</p>';
        $status = $this->exec("INSERT INTO scorecards"
                 . "(archer, venue, date, round, bow, hits, xs, golds, score, has_ends, handicap_ranking)"
                 . " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                 array($_POST['archer'],
                       $_POST['venue'],
                       $this->dateToStoredFormat($_POST['date']),
                       $_POST['round'],
                       $_POST['bow'],
                       $_POST['total-hits'],
                       $_POST['total-xs'],
                       $_POST['total-golds'],
                       $_POST['total-total'],
                       $_POST['has-ends'],
                       $handicap_ranking));
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
        $rows = $this->fetch("SELECT *"
                      . " FROM scorecard_end"
                      . " WHERE scorecard_id = ?"
                      . " ORDER BY end_number",
                      array($id));
        $this->scorecard_end_data = $rows;
    }

    private function edit($id) {
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
            if (isset($_POST['venue'])) {
                $this->scorecard_data['venue'] = $_POST['venue'];
            }
            $this->scorecard_end_data = array();
        }
        print $this->editScorecardPage();
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
        $text []= '<th>Bow</th>';
        $text []= '<th>Age</th>';
        $text []= '<th>Gender</th>';
        $text []= '<th>Round</th>';
        $text []= '<th>Date</th>';
        $text []= '<th>Place Shot</th>';
        $text []= '<th>Hits</th>';
        $text []= '<th>Xs</th>';
        $text []= '<th>Golds</th>';
        $text []= '<th>Score</th>';
        $text []= '<th>Handicap for Score</th>';
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
            $gender = ($this->getGender($result['archer']) == 'M') ? 'Gent' : 'Lady';
            $text []= "<tr class='$tr_class'>";
            $text []= "<td>$result[archer]</td>";
            $text []= "<td>$result[bow]</td>";
            $text []= '<td>' . $this->categoryAt($result['archer'], $result['date']) . '</td>';
            $text []= "<td>$gender</td>";
            $text []= "<td>$result[round]</td>";
            $text []= '<td>' . $this->dateToDisplayedFormat($result['date']) . '</td>';
            $text []= "<td>$venue</td>";
            $text []= "<td>$result[hits]</td>";
            $text []= "<td>$result[xs]</td>";
            $text []= "<td>$result[golds]</td>";
            $text []= "<td>$result[score]</td>";
            $text []= "<td>$result[handicap_ranking]</td>";
            $text []= "<td>";
            $text []= "<form method='get' action=''>";
            $text []= '<input type="hidden" name="page" value="'
                        . $_GET[page] . '"/>';
            $text []=
            "<input type='hidden' name='scorecard-id' value='$result[scorecard_id]' />";
            $text []=
            "<input type='submit' name='edit-scorecard' value='Edit' />";
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

    private function getAllScoreCards() {
        return $this->fetch("SELECT * FROM scorecards");
    }

    private function updateHandicapRanking($scorecard_id, $handicap_ranking) {
        $this->exec("UPDATE scorecards SET handicap_ranking = ? WHERE scorecard_id = ?",
                    array($handicap_ranking, $scorecard_id));
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
        $dob = new DateTime($dob_string);
        $date = new DateTime($date_string);
        $diff = $dob->diff($date);
        return $diff->format('%y');
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
            return 'Senior';
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
        $text []= '<input type="submit" name="edit-scorecard" value="New Scorecard" />';
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

    public function homePageHTML() {
        $text = array();
        $text []= '<h1>Score Cards</h1>';
        $text []= $this->searchForm();
        $text []= '<hr/>';
        $text []= $this->newScorecardForm();
        $text []= '<hr/>';
        $text []= $this->newArcherForm();
        $text []= '<hr/>';
        $text []= $this->deleteArcherForm();
        $text []= '<hr/>';
        $text []= $this->mergeArcherForm();
        $text []= '<hr/>';
        $text []= $this->NewVenueForm();
        $text []= '<hr/>';
        $text []= $this->twoFiveTwoLink();
        $text []= '<hr/>';
        $text []= $this->reCalculateHandicapsForScoresForm();
        $text []= '<hr/>';
        $text []= $this->rebuildRoundHandicapsForm();
        $text []= '<hr/>';
        $text []= "<a href='" . $this->homeurl . 'scorecard.db' ."'>Download a backup</a> (click right and save as...)";
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
        $text []= '<input type="text" name="date" ';
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
        $text = array("<select name='venue' id='venue'>");
        foreach ($venue_map as $id => $name) {
            $text []= "<option value='$id'"
                . ($id == $this->scorecard_data["venue"]
                    ? ' selected="1"'
                    : '')
                . ">$name</option>";
        }
        $text []= '</select>';
        return implode("\n", $text);
    }

    public function archersAsSelect($id = 'archer', $include_archived = false) {
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
            $text []= " value='$bow'>$initial</input>\n";
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
        $text []= '<td colspan="16">';
        $text []= $this->venueAsSelect();
        $text []= '</td>';
        $text []= '</tr>';
        $text []= $this->scorecardHeaderRow();
        $text []= '</thead>';
        return implode($text);
    }

    private function formInputs() {
        $text = array();
        $text []= '<input type="hidden" name="has-ends" value="Y" />';
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
        $text []= $this->formInputs();
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

    private function editScorecardPage() {
        $text = array();
        $text []= '<h1>Edit Score Card';
        if ($this->scorecard_id) {
            $text []= ' #' . $this->scorecard_id;
        }
        $text []= '</h1>';
        $text []= $this->helpBox();
        $text []= $this->deleteScorecardButton();
        $text []= $this->cancelButton();
        $text []= $this->roundData();
        $text []= $this->scorecardForm();
        $text []= $this->zoneCharts();
        return implode($text);
    }

}



