<?php

/**
 * this file contains classes supporting the rhac-scorecards plugin for
 * editing and viewing scorecards
 */

include_once plugin_dir_path(__FILE__) . '../gnas-archery-rounds/rounds.php';
include_once plugin_dir_path(__FILE__) . 'RHAC_ScorecardAccumulator.php';
include_once plugin_dir_path(__FILE__) . 'RHAC_ReassesmentInserter.php';

/**
 * class to accumulate and display all qualifying 252 results on the admin page
 *
 * currently unused, and possibly innaccurate
 */
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
        return $score < self::$requirements[$round][$bow];
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

/**
 * this class contains the code fore the scorecards admin area
 */
class RHAC_Scorecards {

    /** @var PDO $pdo the database handle for the scorecards database */
    private $pdo;

    /** @var array $scorecard_data data for the current scorecard */
    private $scorecard_data;

    /** @var int $scorecard_id (unused?) */
    private $scorecard_id;

    /** @var array $sorecard_end_data data for the ends of the current scorecard */
    private $scorecard_end_data;

    /** @var string $homepath the path to this plugin directory */
    private $homepath;

    /** @var string $homeurl the url of this plugin directory */
    private $homeurl;

    /** @var array $archer_map maps name to other data */
    private $archer_map;

    /** @var array $rounds (unused?) */
    private $rounds;

    /** @var RHAC_Scorecards $instance Singleton Pattern */
    private static $instance;

    /**
     * private constructor (Singleton Pattern)
     */
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

    /**
     * Singleton pattern
     *
     * @return RHAC_Scorecards
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * interface to GNAS_Round::getInstanceByName($round_name)
     *
     * @param string $round_name
     * @return GNAS_Round
     */
    public function getRound($round_name) {
        $round = GNAS_Round::getInstanceByName($round_name);
        if ($round instanceof GNAS_UnrecognisedRound) {
            die("unrecognised round: $round_name");
        }
        return $round;
    }

    /**
     * executes an sql request and returns the result
     *
     * @param string $query
     * @param array $parameters
     * @return array
     */
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

    /**
     * executes an sql statement and returns the status
     *
     * @param string $query
     * @param array $params
     * @return int
     */
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

    /**
     * top-level entry point looks for GET and POST parameters
     * and behaves accordingly, rendering the appropriate page view.
     */
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
            $this->addArcher($_POST['archer'], $_POST['dob'], $_POST['gender'], $_POST['guest'], $_POST['archived']);
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
        } elseif (isset($_POST['recalculate-guest-status'])) {
            $this->reCalculateGuestStatus();
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

    /**
     * Updates all scorecards referencing archer $from to reference archer $to
     * then deletes archer $from from the archer table
     *
     * @param string $from archer name
     * @param string $to archer name
     */
    private function mergeArchers($from, $to) {
        if ($from && $to && $from != $to) {
            $this->pdo->beginTransaction();
            $this->exec("UPDATE scorecards SET archer = ? WHERE archer = ?", array($to, $from));
            $this->exec("DELETE FROM archer WHERE name = ?", array($from));
            $this->pdo->commit();
            echo "<p>Archer $from is now $to</p>";
        }
    }

    /**
     * adds an archer to the archer table
     *
     * @param string $archer archer name
     * @param string $dob date of birth
     * @param string $gender M/F
     * @param string $guest Y/N
     * @param string $archived Y/N
     */
    private function addArcher($archer, $dob, $gender, $guest, $archived) {
        if ($archer && preg_match('#^\d\d\d\d/\d\d/\d\d$#', $dob) && $gender && $guest && $archived) {
            $this->exec("INSERT INTO archer(name, date_of_birth, gender, guest, archived) VALUES(?, ?, ?, ?, ?)",
                        array($archer, $dob, $gender, $guest, $archived));
            echo "<p>Archer $archer added</p>";
        }
        else {
            echo "<p>Archer [$archer] [$dob] [$gender] [$archived] <b>NOT</b> added</p>";
        }
    }

    /**
     * adds a venue to the venue table
     *
     * @param string $venue
     */
    private function addVenue($venue) {
        if ($venue) {
            $this->exec("INSERT INTO venue(name) VALUES(?)", array($venue));
            echo "<p>Venue $venue added</p>";
        }
        else {
            echo "<p>Venue <b>NOT</b> added</p>";
        }
    }

    /**
     * deletes a scorecard by id
     *
     * @param int $id
     */
    private function deleteScorecard($id) {
        if ($id) {
            $status1 = $this->exec("DELETE FROM scorecard_end WHERE scorecard_id = ?", array($id));
            $status2 = $this->exec("DELETE FROM scorecards WHERE scorecard_id = ?", array($id));
            echo "<p>Scorecard #$id deleted.</p>";
        }
    }

    /**
     * deletes an archer from the archer table, but only if they have no scorecards
     *
     * @param string $archer the archer name
     */
    private function deleteArcher($archer) {
        if ($this->noScorecards($archer)) {
            $this->exec("DELETE FROM archer where name = ?", array($archer));
            echo "<p>Archer $archer deleted.</p>";
        } else {
            echo "<p>Archer $archer cannot be deleted because they may have scorecards.</p>";
        }
    }

    /**
     * returns true if the archer has no scorecards
     *
     * @param string $archer the archer's name
     * @return bool
     */
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

    /**
     * convert a date to the format suitable for storing in the db (Y/m/d)
     *
     * @param string $date in any recognised format
     * @return string
     */
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

    /**
     * convert a date to the format suitable for display (D, j M Y)
     *
     * @param string $date in any recognised format
     * @return string
     */
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

    /**
     * update a scorecard from data in the POST request
     */
    private function update() {
        $id = $_POST['scorecard-id'];
        $handicap_ranking = $this->getHandicapForScore($_POST['bow'], $_POST['round'], $_POST['total-total']);
        $gender = $this->getGender($_POST['archer']);
        $guest = $this->getGuest($_POST['archer']);
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
            $guest,
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
        $this->pdo->beginTransaction();
        $this->exec("UPDATE scorecards"
                 . " SET archer = ?,"
                 . " guest = ?,"
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

    /**
     * enter a new scorecard from data in the POST request
     */
    private function insert() {
        $handicap_ranking = $this->getHandicapForScore($_POST['bow'], $_POST['round'], $_POST['total-total']);
        $gender = $this->getGender($_POST['archer']);
        $guest = $this->getGuest($_POST['archer']);
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
                 . "(archer, guest, venue_id, date, round, bow, hits, xs, golds, score, medal, has_ends, handicap_ranking, "
                 . "gender, category, classification, next_age_group_classification, outdoor, tens)"
                 . " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                 array($_POST['archer'],
                       $guest,
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

    /**
     * Return the total number of tens in the POST data
     *
     * @return int
     */
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

    /**
     * insert all end data from the POST request into the scorecard_end table
     */
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

    /**
     * read a scorecard and its ends from the database and return a JSON representation
     *
     * @param int $id
     * @return string
     */
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

    /**
     * read a scorecard from the database and populate the scorecard_data field
     *
     * @param int $id
     */
    private function populateScorecardData($id) {
        $rows = $this->fetch("SELECT * FROM scorecards WHERE scorecard_id = ?",
                             array($id));
        $this->scorecard_data = $rows[0];
        $this->scorecard_data['date'] =
            $this->dateToDisplayedFormat($this->scorecard_data['date']);
        $rows = $this->fetchScorecardEnds($id);
        $this->scorecard_end_data = $rows;
    }

    /**
     * read and return scorecard ends from the database
     *
     * @param int $id the scorecard id
     * @return array
     */
    private function fetchScorecardEnds($id) {
        return $this->fetch("SELECT *"
                      . " FROM scorecard_end"
                      . " WHERE scorecard_id = ?"
                      . " ORDER BY end_number",
                      array($id));
    }

    /**
     * populate the scorecard and scorecard_ends fields
     * then draw the edit scorecard page
     *
     * @param int $id the scorecard id
     * @param bool $has_ends does the scorecard have associated scorecard_end entries
     */
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

    /**
     * search for scorecards based on constraints in the GET data
     * then print the search results page.
     */
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

    /**
     * produce a search results page html from the argument search results
     *
     * @param array $search_results
     * @return string
     */
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
     *
     * @return string
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
     *
     * @return string
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

    /**
     * return all of the scorecards from the database
     *
     * @param bool $with_ends_only limit to scorecards with associated scorecard_ends
     * @return array
     */
    private function getAllScoreCards($with_ends_only = false) {
        $query = "SELECT * FROM scorecards";
        if ($with_ends_only) {
            $query .= ' WHERE has_ends = "Y"';
        }
        $query .= ' ORDER BY date, handicap_ranking desc, score';
        return $this->fetch($query);
    }

    /**
     * update the handicap ranking for a scorecard in the database
     *
     * @param int $scorecard_id
     * @param int $handicap_ranking
     */
    private function updateHandicapRanking($scorecard_id, $handicap_ranking) {
        $this->exec("UPDATE scorecards SET handicap_ranking = ? WHERE scorecard_id = ?",
                    array($handicap_ranking, $scorecard_id));
    }

    /**
     * update the age group for a scorecard in the database
     *
     * @param int $scorecard_id
     * @param string $category the age group
     */
    private function updateAgeGroup($scorecard_id, $category) {
        $this->exec("UPDATE scorecards SET category = ? WHERE scorecard_id = ?",
                    array($category, $scorecard_id));
    }

    /**
     * update the gender for a scorecard in the database
     *
     * @param int $scorecard_id
     * @param string $gender M/F
     */
    private function updateGender($scorecard_id, $gender) {
        $this->exec("UPDATE scorecards SET gender = ? WHERE scorecard_id = ?",
                    array($gender, $scorecard_id));
    }

    /**
     * update the guest status for a scorecard in the database
     *
     * @param int $scorecard_id
     * @param string $guest Y/N
     */
    private function updateGuestStatus($scorecard_id, $guest) {
        $this->exec("UPDATE scorecards SET guest = ? WHERE scorecard_id = ?",
                    array($guest, $scorecard_id));
    }

    /**
     * update the classification for a scorecard in the database
     *
     * @param int $scorecard_id
     * @param string $classification
     */
    private function updateClassification($scorecard_id, $classification) {
        $this->exec("UPDATE scorecards SET classification = ? WHERE scorecard_id = ?",
                    array($classification, $scorecard_id));
    }

    /**
     * update the next age group classification for a scorecard in the database
     *
     * @param int $scorecard_id
     * @param string $classification
     */
    private function updateNextAgeGroupClassification($scorecard_id, $classification) {
        $this->exec("UPDATE scorecards SET next_age_group_classification = ? WHERE scorecard_id = ?",
                    array($classification, $scorecard_id));
    }

    /**
     * update the outdoor flag for a scorecard in the database
     *
     * @param int $scorecard_id
     * @param string $outdoor Y/N
     */
    private function updateOutdoor($scorecard_id, $outdoor) {
        $this->exec("UPDATE scorecards SET outdoor = ? WHERE scorecard_id = ?",
                    array($outdoor, $scorecard_id));
    }

    /**
     * update the total tens for a scorecard in the database
     *
     * @param int $scorecard_id
     * @param int $tens
     */
    private function updateTens($scorecard_id, $tens) {
        $this->exec("UPDATE scorecards SET tens = ? WHERE scorecard_id = ?",
                    array($tens, $scorecard_id));
    }

    /**
     * fetch the handicap for a score in a particular round
     *
     * @param string $bow the bow type
     * @param string $round the name of the round
     * @param int score the core
     *
     * @return int
     */
    private function getHandicapForScore($bow, $round, $score) {
        $compound = $bow == "compound" ? "Y" : "N";
        $rows = $this->fetch("SELECT min(handicap) as result"
                            . " from round_handicaps"
                            . " where round = ? and compound = ? and score <= ?",
                            array($round, $compound, $score));
        return $rows[0]["result"];
    }

    /**
     * recalculate and update the handicap ranking for all scorecards in the database.
     */
    private function reCalculateHandicapsForScores() {
        $scorecards = $this->getAllScoreCards();
        foreach ($scorecards as $scorecard) {
            $handicap_ranking = $this->getHandicapForScore($scorecard['bow'],
                                        $scorecard['round'], $scorecard['score']);
            $this->updateHandicapRanking($scorecard['scorecard_id'], $handicap_ranking);
        }
    }

    /**
     * recaclculate and update the age group for each scorecard in the database.
     */
    private function reCalculateAgeGroups() {
        $scorecards = $this->getAllScoreCards();
        foreach ($scorecards as $scorecard) {
            $category = $this->categoryAt($scorecard['archer'], $scorecard['date']);
            $this->updateAgeGroup($scorecard['scorecard_id'], $category);
        }
    }

    /**
     * recaclculate and update the gender for each scorecard in the database.
     */
    private function reCalculateGenders() {
        $scorecards = $this->getAllScoreCards();
        foreach ($scorecards as $scorecard) {
            $gender = $this->getGender($scorecard['archer']);
            $this->updateGender($scorecard['scorecard_id'], $gender);
        }
    }

    /**
     * recaclculate and update the guest status for each scorecard in the database.
     */
    private function reCalculateGuestStatus() {
        $scorecards = $this->getAllScoreCards();
        foreach ($scorecards as $scorecard) {
            $guest = $this->getGuest($scorecard['archer']);
            $this->updateGuestStatus($scorecard['scorecard_id'], $guest);
        }
    }

    /**
     * get the classification for a particular score
     *
     * @param string $round
     * @param string $gender
     * @param string $category
     * @param string $bow
     * @param int $score
     *
     * @return string
     */
    private function getClassification($round, $gender, $category, $bow, $score) {
        return $this->getRound($round)->getClassification($gender, $category, $bow, $score);
    }

    /**
     * recaclculate and update the classification for each scorecard in the database.
     */
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

    /**
     * return the next age group after the current one
     *
     * @param string $category the current age group
     * @return string
     */
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

    /**
     * "Y" if the round is outdoor, "N" otherwise
     *
     * @param string $round
     * @return string
     */
    private function getIsOutdoor($round) {
        $isOutdoor = $this->getRound($round)->isOutdoor();
        return $isOutdoor ? "Y" : "N";
    }

    /**
     * recaclculate and update the outdoor flag for each scorecard in the database.
     */
    private function reCalculateOutdoor() {
        $scorecards = $this->getAllScoreCards();
        foreach ($scorecards as $scorecard) {
            $isOutdoor = $this->getIsOutdoor($scorecard['round']);
            $this->updateOutdoor($scorecard['scorecard_id'], $isOutdoor);
        }
    }

    /**
     * recaclculate and update the number of tens for each scorecard in the database.
     */
    private function reCalculateTens() {
        $scorecards = $this->getAllScoreCards(true);
        foreach ($scorecards as $scorecard) {
            $tens = $this->countTensFromTable($scorecard['scorecard_id']);
            $this->updateTens($scorecard['scorecard_id'], $tens);
        }
    }

    /**
     * recalculate and update the club records for each scorecard in the database
     *
     * This is the method that invokes the accumulators to do their work
     */
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

    /**
     * update a scorecard according to the reccomendations 
     * produced by the accumulators
     *
     * @param int $scorecard_id
     * @param array $changes the accumulator recommendations
     */
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

    /**
     * insert and delete reasessments according to
     * recommendations from RHAC_ReassesmentInserter
     */
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

    /**
     * insert a specific reassesment with data from RHAC_ReassesmentInserter
     *
     * @param array $change
     */
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

    /**
     * delete a specific reassesment with data (scorecard id) from RHAC_ReassesmentInserter
     *
     * @param array $change
     */
    private function deleteReasessment($change) {
        $this->exec("DELETE FROM scorecards WHERE scorecard_id = ?",
                    array($change['scorecard_id']));
    }

    /**
     * Fetch all ends for a given scorecard and count the number of tens
     *
     * @param int $id the scorecard id
     */
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

    /**
     * Deletes and recreates all handicap data by re-calculating it, per round, per score.
     * This is a pretty expensive operation.
     */
    private function rebuildRoundHandicaps() {
        $this->pdo->beginTransaction();
        $this->deleteAllRoundHandicaps();
        foreach (GNAS_Page::roundData() as $round) {
            $this->populateSingleRoundHandicaps('N', $round->getScoring(), $round);
            $this->populateSingleRoundHandicaps('Y', $round->getCompoundScoring(), $round);
        }
        $this->pdo->commit();
    }

    /**
     * delete all handicap information from the round_handicaps table, as a precursor to re-building it
     */
    private function deleteAllRoundHandicaps() {
        $this->exec("DELETE FROM round_handicaps");
    }

    /**
     * Partially re-populate the round_handicaps table.
     *
     * @param string $compoundYN
     * @param GNAS_Scoring $scoring
     * @param GNAS_Round $round
     */
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

    /**
     * insert a single row into the round_handicaps table
     *
     * @param string $round_name
     * @param string $compoundYN
     * @param int $score
     * @param int $handicap
     */
    private function insertOneRoundHandicap($round_name, $compoundYN, $score, $handicap) {
        $this->exec("INSERT INTO round_handicaps(round, compound, score, handicap) values(?,?,?,?)",
            array($round_name, $compoundYN, $score, $handicap)
        );
    }

    /**
     * returns a html select form attribute for all of the rounds
     *
     * @return string
     */
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

    /**
     * returns a html select form attribute for all of the bow types
     *
     * @return string
     */
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

    /**
     * returns a html select form attribute for all of the age groups
     *
     * @return string
     */
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

    /**
     * returns a html select form attribute for the genders
     *
     * @return string
     */
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

    /**
     * returns a html radio button attribute for limiting a search to club records
     *
     * @return string
     */
    private function clubRecordAsRadio() {
        $text = array();
        $text []= '<input type="radio" name="club-record" checked="1" value="">&nbsp;don\'t care&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="club-record" value="current">&nbsp;current&nbsp;</input>&nbsp;';
        return implode($text);
    }

    /**
     * returns a html radio button attribute for limiting a search to indoor or outdoor
     *
     * @return string
     */
    private function venueAsRadio() {
        $text = array();
        $text []= '<input type="radio" name="outdoor" checked="1" value="Y">&nbsp;Outdoor&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="outdoor" value="N">&nbsp;Indoor&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="outdoor" value="">&nbsp;Both&nbsp;</input>&nbsp;';
        return implode($text);
    }

    /**
     * prints the entire home page html
     */
    private function homePage() {
        // echo '<p>in homePage()</p>';
        print $this->homePageHTML();
        // echo '<p>finished homePage()</p>';
    }

    /**
     * returns a map of archers names to their data
     *
     * @return array
     */
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

    /**
     * returns the date of birth of an archer
     *
     * @param string $archer the archer name
     * 
     * @return string
     */
    private function getDoB($archer) {
        $archer_map = $this->getArcherMap();
        return $archer_map[$archer]['date_of_birth'];
    }

    /**
     * returns the gender of an archer
     *
     * @param string $archer the archer name
     * 
     * @return string
     */
    private function getGender($archer) {
        $archer_map = $this->getArcherMap();
        return $archer_map[$archer]['gender'];
    }

    /**
     * returns the guest status of an archer
     *
     * @param string $archer the archer name
     * 
     * @return string
     */
    private function getGuest($archer) {
        $archer_map = $this->getArcherMap();
        return $archer_map[$archer]['guest'];
    }

    /**
     * returns an archers age at a given date
     *
     * @param string $archer
     * @param string $date_string
     *
     * @return int
     */
    private function ageAt($archer, $date_string) {
        return $this->getAge($this->getDoB($archer), $date_string);
    }

    /**
     * return a persons age given their date of birth and the current date
     *
     * @param string $dob
     * @param string $date
     *
     * @return int
     */
    private function getAge($dob, $date) {
        list($byear, $bmonth, $bday) = explode("/", $dob);
        list($dyear, $dmonth, $dday) = explode("/", $date);

        if ($dday - $bday < 0 || $dmonth - $bmonth < 0) {
            return $dyear - $byear - 1;
        } else {
            return $dyear - $byear;
        }
    }

    /**
     * return the unixtime at midnight on a given date
     *
     * @param string $date_string
     * @return int
     */
    private function unixdate($date_string) {
        list ($y, $m, $d) = explode('/', $date_string);
        if ($y < 1970) {
            $y = 1970;
        }
        return mktime(0, 0, 0, $m, $d, $y, 0);
    }

    /**
     * return an array mapping venue id to venue name
     *
     * @return array
     */
    private function getVenueMap() {
        $rows = $this->fetch("select * from venue");
        $map = array();
        foreach ($rows as $row) {
            $map[$row['venue_id']] = $row['name'];
        }
        return $map;
    }

    /**
     * return an archers age group on a given date
     *
     * @param string $archer the archers name
     * @param string $date_string
     *
     * @return string
     */
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

    /**
     * returns the html for the admin page scorecard search form
     *
     * @return string
     */
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

    /**
     * returns the html for the admin "new scorecard" form
     *
     * @return string
     */
    private function newScorecardForm() {
        $text = array();
        $text []= '<form method="get" action="">';
        $text []= '<input type="hidden" name="page" value="'
                    . $_GET[page] . '"/>';
        $text []= '<p>';
        $text []= '<input type="submit" name="edit-scorecard" value="New Scorecard" title="enter new scorecards"/>';
        $text []= '</p>';
        $text []= '<p>';
        $text []= '<input type="submit" name="edit-score" value="New Score" title="enter new scores (when you don\'t have the scorecard)"/>';
        $text []= '</p>';
        $text []= '</form>';
        return implode($text);
    }

    /**
     * returns the html for the admin "add archer" form
     *
     * @return string
     */
    private function newArcherForm() {
        $text = array();
        $text []= '<form method="post" action="">';

        $text []= '<label for="archer" title="add a new archer">Archer:</label>&nbsp;';
        $text []= '<input type="text" name="archer"/>';

        $text []= '&nbsp;<label for="dob">DoB:&nbsp;</label>&nbsp;';
        $text []= '<input type="text" maxlength="10" name="dob" value="0001/01/01"/>';

        $text []= '&nbsp;<label for="gender">Gender:</label>&nbsp;';
        $text []= '<input type="radio" name="gender" checked="1" value="M">&nbsp;M&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="gender" value="F">&nbsp;F&nbsp;</input>&nbsp;';

        $text []= '&nbsp;<label for="guest">Guest:</label>&nbsp;';
        $text []= '<input type="radio" name="guest" value="Y">&nbsp;Y&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="guest" checked="1" value="N">&nbsp;N&nbsp;</input>&nbsp;';

        $text []= '&nbsp;<label for="archived">Archived:</label>&nbsp;';
        $text []= '<input type="radio" name="archived" value="Y">&nbsp;Y&nbsp;</input>&nbsp;';
        $text []= '<input type="radio" name="archived" checked="1" value="N">&nbsp;N&nbsp;</input>&nbsp;';

        $text []= '&nbsp;<input type="submit" name="add-archer" value="Add Archer"/>';

        $text []= '</form>';
        return implode($text);
    }


    /**
     * returns the html for the admin "add venue" form
     *
     * @return string
     */
    private function newVenueForm() {
        $text = array();
        $text []= '<form method="post" action="">';

        $text []= '<label for="venue">Venue:</label>&nbsp;';
        $text []= '<input type="text" name="venue"/>';

        $text []= '&nbsp;<input type="submit" name="add-venue" value="Add Venue" title="create a new venue"/>';

        $text []= '</form>';
        return implode($text);
    }

    /**
     * returns the html for the admin "delete archer" form
     *
     * @return string
     */
    private function deleteArcherForm() {
        $text = array();
        $text []= '<form method="post" action="">';
        $text []= $this->archersAsSelect('archer', true);
        $text []= '<input type="submit" name="delete-archer" value="Delete Archer" title="be careful"/>';
        $text []= '</form>';
        return implode($text);
    }

    /**
     * returns the html for the admin "merge archers" form
     *
     * @return string
     */
    private function mergeArcherForm() {
        $text = array();
        $text []= '<form method="post" id="merge-archers" action="">';
        $text []= '<label for="from-archer">From</label>';
        $text []= $this->archersAsSelect("from-archer", true);
        $text []= '<label for="to-archer">To</label>';
        $text []= $this->archersAsSelect("to-archer", true);
        $text []= '<input type="submit" name="merge-archers" value="Merge Archers" title="move all of the \'From\' archer\'s scores to the \'To\' archer and remove the \'From\' archer"/>';
        $text []= '</form>';
        return implode($text);
    }

    /**
     * returns the html for the admin "rebuild all handicap tables" form
     *
     * @return string
     */
    private function rebuildRoundHandicapsForm() {
        return <<<EOFORM
<p>
<form method="post" id="rebuild-round-handicaps" action="">
<input type="submit" name="rebuild-round-handicaps" value="Rebuild All Handicap Tables"
title="You only need do this if a new round has been added or a round definition changed"/>
</form>
</p>
EOFORM;
    }

    /**
     * returns the html for the admin "recalculate all score handicaps" form
     *
     * @return string
     */
    private function reCalculateHandicapsForScoresForm() {
        return <<<EOFORM
<p>
<form method="post" id="recalculate-score-handicaps" action="">
<input type="submit" name="recalculate-score-handicaps" value="Recalculate All Score Handicaps"
title="only necessary if a round had the wrong handicaps and has been fixed"/>
</form>
</p>
EOFORM;
    }

    /**
     * returns the html for the admin "recalculate all age groups" form
     *
     * @return string
     */
    private function reCalculateAgeGroupsForm() {
        return <<<EOFORM
<p>
<form method="post" id="recalculate-age-groups" action="">
<input type="submit" name="recalculate-age-groups" value="Recalculate All Age Groups"
title="only needed if you have corrected the age of an archer"/>
</form>
</p>
EOFORM;
    }

    /**
     * returns the html for the admin "recalculate all genders" form
     *
     * @return string
     */
    private function reCalculateGendersForm() {
        return <<<EOFORM
<p>
<form method="post" id="recalculate-genders" action="">
<input type="submit" name="recalculate-genders" value="Recalculate All Genders"
title="you only need to do this if you've corrected the gender of an archer with score cards"/>
</form>
</p>
EOFORM;
    }

    /**
     * returns the html for the admin "recalculate all guest statuses" form
     *
     * @return string
     */
    private function reCalculateGuestStatusForm() {
        return <<<EOFORM
<p>
<form method="post" id="recalculate-guest-status" action="">
<input type="submit" name="recalculate-guest-status" value="Recalculate All Guest Statuses"
title="you only need to do this if you've changed the guest status of an archer with score cards"/>
</form>
</p>
EOFORM;
    }

    /**
     * returns the html for the admin "recalculate all classifications" form
     *
     * @return string
     */
    private function reCalculateClassificationsForm() {
        return <<<EOFORM
<p>
<form method="post" id="recalculate-classifications" action="">
<input type="submit" name="recalculate-classifications" value="Recalculate All Classifications"
title="only needed if a score had the wrong classification in the GNAS tables and has been corrected"/>
</form>
</p>
EOFORM;
    }

    /**
     * returns the html for the admin "Reassign Outdoor and Indoor Rounds" form
     *
     * @return string
     */
    private function reCalculateOutdoorForm() {
        return <<<EOFORM
<p>
<form method="post" id="recalculate-outdoor" action="">
<input type="submit" name="recalculate-outdoor" value="Reassign Outdoor and Indoor Rounds"
title="this would only be necessary if a round was wrongly designated indoor/outdoor and then fixed"/>
</form>
</p>
EOFORM;
    }

    /**
     * returns the html for the admin "Recount all Tens" form (unused)
     *
     * @return string
     */
    private function reCalculateTensForm() {
        return <<<EOFORM
<p>
<form method="post" id="recalculate-tens" action="">
<input type="submit" name="recalculate-tens" value="Recount all Tens"
title="probably useless now"/>
</form>
</p>
EOFORM;
    }

    /**
     * returns the html for the admin "Recalculate Club Records" form (unused)
     *
     * @return string
     */
    private function reCalculateRecordsForm() {
        return <<<EOFORM
<p>
<form method="post" id="recalculate-records" action="">
<input type="submit" name="recalculate-records" value="Recalculate Club Records"
title="run this after entering a batch of scorecards"/>
</form>
</p>
EOFORM;
    }

    /**
     * returns the html for the entire admin scorecards home page
     *
     * @return string
     */
    public function homePageHTML() {
        $text = array();
        $text []= '<h1>Score Cards</h1>';
        $text []= '<div id="rhac-admin-accordion">';
        $text []= '<h3 title="enter new scorecards and scores">Scorecards</h3><div>';
        $text []= $this->newScorecardForm();
        $text []= $this->reCalculateRecordsForm();
        $text []= "</div>";
        $text []= '<h3 title="search for scores and scorecards to edit">Search</h3><div>';
        $text []= $this->searchForm();
        $text []= "</div>";
        $text []= '<h3 title="manage archers and venues">Data</h3><div>';
        $text []= $this->newArcherForm();
        $text []= $this->deleteArcherForm();
        $text []= $this->mergeArcherForm();
        $text []= $this->NewVenueForm();
        $text []= "</div>";
        $text []= '<h3 title="manage saved data (you shouldn\'t need any of this very often)">Admin</h3><div>';
        $text []= <<<EODESC
<div style="max-width: 50em;">
<p>Most of these actions were added to fix problems in the data due to bugs in the system while
it was being developed, and which are now fixed.
However some of the actions may still be useful, and none of them do any harm.</p>
<p>For example if you've got the gender of an archer wrong, and entered score cards
for them, you'd need
to create a new temporary archer, merge the old archer to the temp one, re-create the old
archer with the right gender then merge the temp archer to the fixed archer with the right gender.</p>
<p>However that would still leave the categories of the original scorecards wrong, so you can run the
"Recalculate All Genders" action to re-calculate the categories of all of the score sheets.</p>
<p>The same argument applies for "Recalculate all Age Groups," and if you change the guest status of
an archer you'll need to run the "Recalculate Guest Status" action for that to be reflected in their
current score cards.</p>
</div>
EODESC;
        $text []= $this->reCalculateGendersForm();
        $text []= $this->reCalculateGuestStatusForm();
        $text []= $this->reCalculateAgeGroupsForm();
        $text []= $this->reCalculateHandicapsForScoresForm();
        $text []= $this->reCalculateClassificationsForm();
        $text []= $this->reCalculateOutdoorForm();
        $text []= $this->rebuildRoundHandicapsForm();
        $text []= "<a href='" . $this->homeurl . 'scorecard.db' ."'>Download a backup</a> (click right and save as...)";
        $text []= "</div>";
        $text []= "</div>";
        return implode($text);
    }

    /**
     * return the url for the current page
     *
     * @return string
     */
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

    /**
     * print a page of all qualifying 252 results (unused)
     */
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

    /**
     * return an html form input for the date, defaulting to the date of the current scorecard
     *
     * @return string
     */
    private function dateAsInput() {
        $text = array();
        $text []= '<input type="text" name="date" size="16" ';
        if ($this->scorecard_data['date']) {
            $text []= "value='" . $this->scorecard_data["date"] . "'";
        }
        $text []= 'id="date"/>';
        return implode($text);
    }

    /**
     * return all archer names
     *
     * @return array
     */
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
