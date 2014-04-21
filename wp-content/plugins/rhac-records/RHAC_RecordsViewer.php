<?php
define('RHAC_RE_PLUGINS_ROOT',
       preg_replace('/[^\/]+\/$/', '', RHAC_RE_DIR));

define('RHAC_RE_SCORECARD_DIR', RHAC_RE_PLUGINS_ROOT . 'rhac-scorecards/');
define('RHAC_RE_PLUGIN_URL_ROOT', plugin_dir_url(__FILE__));

class RHAC_RecordsViewer {
    private $pdo;
    private $gender_map = array("M" => "Gents", "F" => "Ladies");
    private $ends_map;
    private $venue_map;
    private $pb_map;
    private $cr_map;
    private $tft_map;
    private $medal_map;
    private $classification_map;
    private $archer_map;
    private $initialized = false;
    private $icons;

    private static $instance;

    private function __construct() {
        try {
            $this->pdo = new PDO('sqlite:'
                         . RHAC_RE_SCORECARD_DIR
                         . 'scorecard.db');
        } catch (PDOException $e) {
            die('Error!: ' . $e->getMessage());
            exit();
        }
    }

    private function initIcons() {
        $this->icons = array();
        foreach(array('scorecard', 'personal-best', 'current-club-record', 'old-club-record') as $icon) {
            $this->icons[$icon] = "<img class='no-shadow' src='"
                                . RHAC_RE_PLUGIN_URL_ROOT
                                . "icons/$icon.png'/>";
        }
        foreach(array('green', 'white', 'black', 'blue', 'red', 'bronze', 'silver', 'gold') as $icon) {
            $this->icons["$icon-252"] = "<img class='no-shadow' src='"
                                . RHAC_RE_PLUGIN_URL_ROOT
                                . "icons/$icon-252.png'/>";
            $this->icons["half-$icon-252"] = "<img class='no-shadow' src='"
                                . RHAC_RE_PLUGIN_URL_ROOT
                                . "icons/half-$icon-252.png'/>";
        }
        foreach(array('bronze', 'silver', 'gold') as $icon) {
            $this->icons["medal-$icon"] = "<img class='no-shadow' src='"
                                . RHAC_RE_PLUGIN_URL_ROOT
                                . "icons/medal-$icon.png'/>";
        }
    }

    private function initDisplayMaps() {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        $this->initIcons();
        $venues = $this->select('venue_id, name from venue');
        foreach($venues as $venue) {
            $this->venue_map[$venue['venue_id']] = $venue['name'];
        }
        
        $this->ends_map = array(
            'Y' => $this->icons['scorecard'],
            'N' => '',
        );
        $this->pb_map = array(
            'Y' => $this->icons['personal-best'],
            'N' => '',
        );
        $this->cr_map = array(
            'current' => $this->icons['current-club-record'],
            'old' => $this->icons['old-club-record'],
            'N' => '',
        );
        $this->tft_map = array();
        foreach (array('green', 'white', 'black', 'blue', 'red', 'bronze', 'silver', 'gold') as $colour) {
            $this->tft_map["$colour/1"] = $this->icons["half-$colour-252"];
            $this->tft_map["$colour/2"] = $this->icons["$colour-252"];
        }
        $this->tft_map['N'] = '';
        foreach (array('bronze', 'silver', 'gold') as $colour) {
            $this->medal_map[$colour] = $this->icons["medal-$colour"];
        }
        $this->classification_map['archer'] = '<span class="archer-class">Archer</span>';
        $this->classification_map['unclassified'] = '<span class="unclassified-class">Unclassified</span>';
        $this->classification_map['third'] = '<span class="third-class">Third Class</span>';
        $this->classification_map['second'] = '<span class="second-class">Second Class</span>';
        $this->classification_map['first'] = '<span class="first-class">First Class</span>';
        $this->classification_map['bm'] = '<span class="bowman-class">Bowman</span>';
        $this->classification_map['mbm'] = '<span class="master-bowman-class">Master Bowman</span>';
        $this->classification_map['gmbm'] =
                                '<span class="grand-master-bowman-class">Grand Master Bowman</span>';
        $this->classification_map['A'] = '<span class="a-class">A</span>';
        $this->classification_map['B'] = '<span class="b-class">B</span>';
        $this->classification_map['C'] = '<span class="c-class">C</span>';
        $this->classification_map['D'] = '<span class="d-class">D</span>';
        $this->classification_map['E'] = '<span class="e-class">E</span>';
        $this->classification_map['F'] = '<span class="f-class">F</span>';
        $this->classification_map['G'] = '<span class="g-class">G</span>';
        $this->classification_map['H'] = '<span class="h-class">H</span>';
        $this->classification_map['(archer)'] = '<span class="archer-class">(Archer)</span>';
        $this->classification_map['(unclassified)'] = '<span class="unclassified-class">(Unclassified)</span>';
        $this->classification_map['(third)'] = '<span class="third-class">(Third Class)</span>';
        $this->classification_map['(second)'] = '<span class="second-class">(Second Class)</span>';
        $this->classification_map['(first)'] = '<span class="first-class">(First Class)</span>';
        $this->classification_map['(bm)'] = '<span class="bowman-class">(Bowman)</span>';
        $this->classification_map['(mbm)'] = '<span class="master-bowman-class">(Master Bowman)</span>';
        $this->classification_map['(gmbm)'] =
                                '<span class="grand-master-bowman-class">(Grand Master Bowman)</span>';
        $this->classification_map['(A)'] = '<span class="a-class">(A)</span>';
        $this->classification_map['(B)'] = '<span class="b-class">(B)</span>';
        $this->classification_map['(C)'] = '<span class="c-class">(C)</span>';
        $this->classification_map['(D)'] = '<span class="d-class">(D)</span>';
        $this->classification_map['(E)'] = '<span class="e-class">(E)</span>';
        $this->classification_map['(F)'] = '<span class="f-class">(F)</span>';
        $this->classification_map['(G)'] = '<span class="g-class">(G)</span>';
        $this->classification_map['(H)'] = '<span class="h-class">(H)</span>';
        $this->classification_map[''] = '';

    }

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function select($query, $params = array()) {
        $stmt = $this->pdo->prepare("SELECT " . $query);
        if (!$stmt) {
            die("query: [$query] failed to prepare: "
                . print_r($this->pdo->errorInfo(), true));
        }
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $rows;
    }

    public function view() {
        $scores_form = $this->scoresForm();
        $club_records_form = $this->clubRecordsForm();
        return <<<EOHTML
<div id="tabs">
  <ul>
    <li><a href="#scores">Scores</a></li>
    <li><a href="#club-records">Club Records</a></li>
    <li><a href="#personal-bests">Personal Bests</a></li>
    <li><a href="#improvements">Improvements</a></li>
    <li><a href="#two-five-two">252 Awards</a></li>
  </ul>
  <div id="scores">$scores_form</div>
  <div id="club-records">$club_records_form</div>
  <div id="personal-bests">
  </div>
  <div id="improvements">
  </div>
  <div id="two-five-two">
  </div>
</div>
EOHTML;
    }

    private function clubRecordsForm() {
        $text = array();
        $text []= '<form id="club-records-form">';
        $text []= $this->archersAsSelect('club-records-archer');
        $text []= ' &nbsp;&nbsp; ';
        $text []= $this->currentRecordsConstraint('club-records-current');
        $text []= ' &nbsp;&nbsp; ';
        $text []= $this->seasonsAsSelect('club-records-season');
        $text []= ' &nbsp;&nbsp; ';
        $text []= '<button type="button" name="search" id="club-records-search-button">Search</button>';
        $text []= '</form>';
        $text []= '<div id="club-records-search-results"></div>';
        return implode($text);
    }

    private function currentRecordsConstraint($id) {
        $text = array();
        $text []= "<span style='display: inline-block;'>";
        $text []= "<label for='$id'>Current</label>";
        $text []= "<input type='checkbox' name='$id' id='$id' checked='checked' value='current'/>";
        $text []= "</span>";
        return implode($text);
    }

    private function scoresForm() {
        # error_log("scoresForm called");
        $text = array();
        $text []= '<form id="scores-form">';
        $text []= $this->archersAsSelect('scores-archer');
        $text []= ' &nbsp;&nbsp; ';
        $text []= $this->seasonsAsSelect('scores-season');
        $text []= ' &nbsp;&nbsp; ';
        $text []= $this->dateField('scores-date');
        $text []= '<button type="button" name="search" id="scores-search-button">Search</button>';
        $text []= '</form>';
        $text []= '<div id="score-search-results"></div>';
        return implode($text);
    }

    private function dateField($id) {
        $text = array();
        $text []= "<span style='display: inline-block;'>";
        $text []= "<label for='$id'>Date</label>";
        $text []= "<input type='text' name='$id' id='$id'/>";
        $text []= "</span>";
        return implode($text);
    }

    private function archersAsSelect($id, $include_archived=false) {
        $text = array();
        $text []= "<span style='display: inline-block;'>";
        $text []= "<label for='$id'>Archer</label>";
        $text []= "<select name='$id' id='$id'>";
        $text []= "<option value=''>all</option>";
        $archers = $this->getArcherMap();
        if ($include_archived) {
            foreach ($archers as $archer => $data) {
                $text []= "<option value='$archer'>$archer</option>";
            }
        }
        else {
            foreach ($archers as $archer => $data) {
                if ($data['archived'] == 'N') {
                    $text []= "<option value='$archer'>$archer</option>";
                }
            }
        }
        $text []= "</select>";
        $text []= "</span>";
        return implode($text);
    }

    private function seasonsAsSelect($id) {
        $text = array();
        $text []= "<span style='display: inline-block;'>";
        $text []= "<label for='$id'>Season</label>";
        $text []= "<input type='radio' name='$id' id='$id' value='Y' checked='1'>Outdoor&nbsp;&nbsp;";
        $text []= "<input type='radio' name='$id' id='$id' value='N'>Indoor&nbsp;&nbsp;";
        $text []= "<input type='radio' name='$id' id='$id' value=''>Both&nbsp;&nbsp;";
        $text []= "</span>";
        return implode($text);
    }

    private function getArcherMap() {
        if (!isset($this->archer_map)) {
            $this->archer_map = array();
            $rows = $this->select("* FROM archer ORDER BY name");
            foreach ($rows as $row) {
                $this->archer_map[$row['name']] = $row;
            }
        }
        return $this->archer_map;
    }

    public function display() {
        error_log("display called");
        $params = array();
        $fields = array("1 = 1");
        foreach(array('archer', 'reassessment', 'outdoor', 'club_record', 'date') as $field) {
            if ($_GET[$field]) {
                $val = $_GET[$field];
                if (substr($val, 0, 1) == '!') {
                    $val = substr($val, 1);
                    $fields []= "$field != ?";
                }
                else {
                    $fields []= "$field = ?";
                }
                $params []= $val;
            }
        }
        $query = '* FROM scorecards WHERE '
               . implode(' AND ', $fields)
               . ' ORDER BY date, archer, handicap_ranking desc';
        $rows = $this->select($query, $params);
        return $this->formatResults($rows);
    }

    private function formatResults($rows) {
        $this->initDisplayMaps();
        $text = array();
        $headers = array(
            'Date', 'Archer', 'Category', 'Round', 'Place Shot',
            'H/C', 'Class', 'Score', '&nbsp;');
        $text []= '<table>';
        $text []= '<thead>';
        $text []= '<tr>';
        foreach ($headers as $header) {
            $text []= "<th>$header</th>";
        }
        $text []= '</tr>';
        $text []= '</thead>';
        $text []= '<tbody>';
        foreach ($rows as $row) {
            $text []= "<tr id='$row[scorecard_id]'>";
            $text []= "<td>$row[date]</td>";
            $text []= "<td>$row[archer]</td>";
            $text []= "<td>" . $this->category($row) . "</td>";
            $text []= "<td>$row[round]</td>";
            $text []= "<td>" . $this->venue_map[$row[venue_id]] . "</td>";
            $text []= "<td>$row[handicap_ranking]</td>";
            $text []= "<td>$row[classification]</td>";
            $text []= "<td>$row[score]</td>";
            $text []= "<td>";
            $text []= $this->classification_map[$row[new_classification]];
            $text []= ' ';
            if (strlen($row[handicap_improvement])) {
                $text []= '<span class="handicap-improvement">' . $row[handicap_improvement] . '</span>';
            }
            $text []= $this->medal_map[$row[medal]];
            $text []= $this->cr_map[$row[club_record]];
            $text []= $this->tft_map[$row[two_five_two]];
            $text []= $this->pb_map[$row[personal_best]];
            $text []= "</td>";
            $text []= '</tr>';
        }
        $text []= '</tbody>';
        $text []= '</table>';
        return implode($text);
    }

    private function category($row) {
        $text = array();
        $text []= $this->gender_map[$row['gender']];
        if ($row['category'] != 'adult') {
            $text []= $row['category'];
        }
        $text []= ucfirst($row['bow']);
        return implode(' ', $text);
    }
}
