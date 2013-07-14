<?php

include_once plugin_dir_path(__FILE__) . '../gnas-archery-rounds/rounds.php';

class RHAC_Scorecards {

    private $pdo;
    private $scorecard_data;
    private $scorecard_id;
    private $scorecard_end_data;
    private $homepath;
    private $homeurl;
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
        $rows = $stmt->fetchAll();
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
        if (isset($_POST['edit-scorecard'])) { // update or insert requested
            if ($_POST['scorecard-id']) { // update requested
                // echo '<p>topLevel() update req</p>';
                $this->update();
                $this->edit($_POST['scorecard-id']);
            }
            else { // insert requested
                // echo '<p>topLevel() insert req</p>';
                $id = $this->insert();
                $this->edit($id);
            }
        } elseif (isset($_POST['add-archer'])) {
            $this->addArcher($_POST['archer']);
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
        } else { // homePage
            // echo '<p>doing home page</p>';
            $this->homePage();
        }
    }

    private function addArcher($archer) {
        if ($archer) {
            $this->exec("INSERT INTO archer(name) VALUES(?)", array($archer));
            echo "<p>Archer $archer added</p>";
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
        $params = array(
            $_POST['archer'],
            $this->dateToStoredFormat($_POST['date']),
            $_POST['round'],
            $_POST['bow'],
            $_POST['total-hits'],
            $_POST['total-xs'],
            $_POST['total-golds'],
            $_POST['total-total'],
            $id
        );
        // echo '<p>update() ' . print_r($params, true) . '</p>';
        $this->pdo->beginTransaction();
        $this->exec("UPDATE scorecards"
                 . " SET archer = ?,"
                 . " date = ?,"
                 . " round = ?,"
                 . " bow = ?,"
                 . " hits = ?,"
                 . " xs = ?,"
                 . " golds = ?,"
                 . " score = ?"
                 . " WHERE scorecard_id = ?",
                    $params);
        $this->exec("DELETE FROM scorecard_end WHERE scorecard_id = ?",
                         array($id));
        $this->insertEnds($id);
        $this->pdo->commit();
    }

    private function insert() {
        $this->pdo->beginTransaction();
        // echo '<p>insert() inside transaction</p>';
        $status = $this->exec("INSERT INTO scorecards"
                 . "(archer, date, round, bow, hits, xs, golds, score)"
                 . " VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                 array($_POST['archer'],
                       $this->dateToStoredFormat($_POST['date']),
                       $_POST['round'],
                       $_POST['bow'],
                       $_POST['total-hits'],
                       $_POST['total-xs'],
                       $_POST['total-golds'],
                       $_POST['total-total']));
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
        $query = "SELECT * FROM scorecards WHERE "
               . implode(' AND ', $criteria);
        $search_results = $this->fetch($query, $params);
        print $this->searchResultsPage($search_results);
    }

    private function searchResultsPage($search_results) {
        $text = array();
        $text []= '<h1>Search Results</h1>';
        $text []= '<table>';
        $text []= '<thead>';
        $text []= '<tr>';
        $text []= '<th>Archer</th>';
        $text []= '<th>Bow</th>';
        $text []= '<th>Round</th>';
        $text []= '<th>Date</th>';
        $text []= '<th>Hits</th>';
        $text []= '<th>Xs</th>';
        $text []= '<th>Golds</th>';
        $text []= '<th>Score</th>';
        $text []= '<th>&nbsp;</th>';
        $text []= '</tr>';
        $text []= '</thead>';
        $text []= '<tbody>';
        foreach ($search_results as $result) {
            $text []= '<tr>';
            $text []= "<td>$result[archer]</td>";
            $text []= "<td>$result[bow]</td>";
            $text []= "<td>$result[round]</td>";
            $yext []= '<td>' . $this->dateToDisplayedFormat($result['date']) . '</td>';
            $text []= "<td>$result[hits]</td>";
            $text []= "<td>$result[xs]</td>";
            $text []= "<td>$result[golds]</td>";
            $text []= "<td>$result[score]</td>";
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
        return implode($text);
    }

    /**
     * Generate round data to html that javascript can inspect.
     */
    private function roundData() {
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
    private function roundDataAsJSON() {
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


    private function homePage() {
        // echo '<p>in homePage()</p>';
        print $this->homePageHTML();
        // echo '<p>finished homePage()</p>';
    }

    public function homePageHTML() {
        $text = array();
        $text []= '<h1>Score Cards</h1>';
        $text []= '<form method="get" action="">';
        $text []= '<input type="hidden" name="page" value="'
                    . $_GET[page] . '"/>';
        $text []= '<table>';
        $text []= '<tr><td>Archer</td><td colspan="2">';
        $text []= $this->archersAsSelect();
        $text []= '</td></tr>';
        $text []= '<tr><td>Round</td><td colspan="2">';
        $text []= $this->roundDataAsSelect();
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
        $text []= '<hr/>';
        $text []= '<form method="get" action="">';
        $text []= '<input type="hidden" name="page" value="'
                    . $_GET[page] . '"/>';
        $text []= '<input type="submit" name="edit-scorecard" value="New" />';
        $text []= '</form>';
        $text []= '<hr/>';
        $text []= '<form method="post" action="">';
        $text []= '<input type="text" name="archer"/>';
        $text []= '<input type="submit" name="add-archer" value="Add Archer"/>';
        $text []= '</form>';
        $text []= '<hr/>';
        $text []= "<a href='" . $this->homeurl . 'scorecard.db' ."'>Download a backup</a>";
        return implode($text);
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
            array('label' => 'X', 'png' => 'gold'),
            array('label' => '10', 'png' => 'gold'),
            array('label' => '9', 'png' => 'gold'),
            array('label' => '8', 'png' => 'red'),
            array('label' => '7', 'png' => 'red'),
            array('label' => '6', 'png' => 'blue'),
            array('label' => '5', 'png' => 'blue'),
            array('label' => '4', 'png' => 'black'),
            array('label' => '3', 'png' => 'black'),
            array('label' => '2', 'png' => 'white'),
            array('label' => '1', 'png' => 'white'),
            array('label' => 'M', 'png' => 'green')
        );
    }

    private function tenZoneChart() {
        return $this->zoneChart($this->tenZoneData(),
                                'TenZoneChart', 'tbar_', 30);
    }

    private function fiveZoneData() {
        return array(
            array('label' => '9', 'png' => 'gold'),
            array('label' => '7', 'png' => 'red'),
            array('label' => '5', 'png' => 'blue'),
            array('label' => '3', 'png' => 'black'),
            array('label' => '1', 'png' => 'white'),
            array('label' => 'M', 'png' => 'green')
        );
    }

    private function fiveZoneChart() {
        return $this->zoneChart($this->fiveZoneData(),
                                'FiveZoneChart', 'fbar_', 50);
    }

    private function zoneChart($zoneData, $tableId, $idPrefix, $width) {
        $text = array();
        $text []= '<table id="' . $tableId . '"><tr>';
        foreach ($zoneData as $zone) {
            $text []= '<td class="bar">';
            $text []= '<img id="'
                    . $idPrefix
                    . $zone['label']
                    . '" src="'
                    . $this->png($zone['png'])
                    . '" height="425" width="' . $width . '"/>';
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
        $text []= $this->fiveZoneChart();
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
        $text []= $this->scorecardHeaderRow();
        $text []= '</thead>';
        return implode($text);
    }

    private function formInputs() {
        $text = array();
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
        $text []= '<input type="submit" name="edit-scorecard" />';
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
        $text []= '<form method="post" action="" id="delete-scorecard">';
        $text []= '<input type="hidden" name="scorecard-id" value="';
        $text []= $this->scorecard_id;
        $text []= '"/>';
        $text []= '<input type="submit" name="delete-scorecard"';
        $text []= ' value="Delete" />';
        $text []= '</form>';
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

    private function editScorecardPage() {
        $text = array();
        $text []= '<h1>Edit Score Card';
        if ($this->scorecard_id) {
            $text []= ' #' . $this->scorecard_id;
        }
        $text []= '</h1>';
        $text []= $this->helpBox();
        $text []= $this->roundData();
        $text []= $this->scorecardForm();
        $text []= $this->zoneCharts();
        return implode($text);
    }

}

