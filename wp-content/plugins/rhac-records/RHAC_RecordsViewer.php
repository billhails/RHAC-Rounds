<?php
define('RHAC_RE_PLUGINS_ROOT',
       preg_replace('/[^\/]+\/$/', '', RHAC_RE_DIR));

define('RHAC_RE_SCORECARD_DIR', RHAC_RE_PLUGINS_ROOT . 'rhac-scorecards/');

include_once(RHAC_RE_PLUGINS_ROOT . 'gnas-archery-rounds/rounds.php');

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
    private $round_families;
    private $rounds;
    private $time;

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
        $current_archers = $this->archerOptions(false);
        $all_archers = $this->archerOptions(true);

        $all_rounds = $this->allRoundOptions();
        $outdoor_rounds = $this->outdoorRoundOptions();
        $indoor_rounds = $this->indoorRoundOptions();

        $all_families = $this->allFamilyOptions();
        $outdoor_families = $this->outdoorFamilyOptions();
        $indoor_families = $this->indoorFamilyOptions();

        $all_seasons = $this->allSeasonOptions();
        $outdoor_seasons = $this->outdoorSeasonOptions();
        $indoor_seasons = $this->indoorSeasonOptions();

        return <<<EOHTML
<div id="rhac-re-main">
  <div class="rhac-re-invisible" id="rhac-re-current-archers">$current_archers</div>
  <div class="rhac-re-invisible" id="rhac-re-all-archers">$all_archers</div>
  <div class="rhac-re-invisible" id="rhac-re-all-rounds">$all_rounds</div>
  <div class="rhac-re-invisible" id="rhac-re-outdoor-rounds">$outdoor_rounds</div>
  <div class="rhac-re-invisible" id="rhac-re-indoor-rounds">$indoor_rounds</div>
  <div class="rhac-re-invisible" id="rhac-re-all-families">$all_families</div>
  <div class="rhac-re-invisible" id="rhac-re-outdoor-families">$outdoor_families</div>
  <div class="rhac-re-invisible" id="rhac-re-indoor-families">$indoor_families</div>
  <div class="rhac-re-invisible" id="rhac-re-all-seasons">$all_seasons</div>
  <div class="rhac-re-invisible" id="rhac-re-outdoor-seasons">$outdoor_seasons</div>
  <div class="rhac-re-invisible" id="rhac-re-indoor-seasons">$indoor_seasons</div>
  <div id="rhac-re-help-toggle" class="rhac-re">Help</div>
  <div id="rhac-re-help" class="rhac-re">
    <p>The simplest thing to do is ... </p>
  </div>
  <div id="rhac-re-simpleform" class="rhac-re">
    <select id="rhac-re-report" name="rhac-re-report">
      <option value="Scores">Scores</option>
      <option value="Club Records">Club Records</option>
    </select>
    <button type="button" id="rhac-re-run-report">Run Report</button>
  </div>
  <div id="rhac-re-more-toggle" class="rhac-re">More</div>
  <div id="rhac-re-moreform" class="rhac-re">
    <div id="rhac-re-more-left">

      <div class="rhac-re-section">
        <label class="rhac-re-label" for="seasons">Seasons</label>
        <div class="rhac-re-radios">
          <input type="radio" name="season" class="rhac-re-outdoor" value="Y" checked="1">Outdoor</input>
          <input type="radio" name="season" class="rhac-re-outdoor" value="N">Indoor</input>
          <input type="radio" name="season" class="rhac-re-outdoor" value="">Both</input>
        </div>
      </div>

      <div class="rhac-re-section">
        <label class="rhac-re-label" for="archer">Archer</label>
        <div>
          <div><input type="checkbox" id="rhac-re-include-lapsed">Include lapsed members</input></div>
          <div>
            <select id="rhac-re-archer" name="archer">
$current_archers
            </select>
          </div>
        </div>
      </div>

      <div class="rhac-re-section">
        <label class="rhac-re-label">Category</label>
        <div class="rhac-re-selects">
          <select id="rhac-re-age" name="age">
              <option value="">Any</option>
              <option value="adult">Senior</option>
              <option value="U18">Under 18</option>
              <option value="U16">Under 16</option>
              <option value="U14">Under 14</option>
              <option value="U12">Under 12</option>
          </select>
          <select id="rhac-re-gender" name="gender">
              <option value="">Any</option>
              <option value="M">Gent</option>
              <option value="F">Lady</option>
          </select>
          <select id="rhac-re-bow" name="bow">
              <option value="">Any</option>
              <option value="recurve">Recurve</option>
              <option value="compound">Compound</option>
              <option value="longbow">Longbow</option>
              <option value="barebow">Barebow</option>
          </select>
        </div>
      </div>

      <div class="rhac-re-section">
        <label class="rhac-re-label" for="round">Round</label>
        <div class="rhac-re-radios">
          <input type="radio" class="rhac-re-single-round" value="Y" name="single-round" checked="1">Round</input>
          <input type="radio" class="rhac-re-single-round" value="N" name="single-round">Round Family</input>
        </div>
        <select id="rhac-re-round" name="round">
$outdoor_rounds
        </select>
      </div>

      <div class="rhac-re-section">
        <label class="rhac-re-label">Dates</label>
        <div class="rhac-re-dates">
          <input type="text" class="rhac-re-date" id="rhac-re-lower-date" name="lower-date"></input>
          <input type="text" class="rhac-re-date" id="rhac-re-upper-date" name="upper-date"></input>
        </div>
        <select id="rhac-re-seasons" name="seasons">
$outdoor_seasons
        </select>
      </div>

    </div>
    <div id="rhac-re-more-right">

      <div class="rhac-re-section">
        <label class="rhac-re-label">Limit to</label>
        <div class="rhac-re-checklist">
          <div><input type="checkbox" id="rhac-re-current-records" value="Y" name="current-records">Current Records</input></div>
          <div><input type="checkbox" id="rhac-re-old-records" value="Y" name="old-records">Old Records</input></div>
          <div><input type="checkbox" id="rhac-re-medals" value="Y" name="medals">Medals</input></div>
          <div><input type="checkbox" id="rhac-re-252" value="Y" name="medals">252 awards</input></div>
          <div><input type="checkbox" id="rhac-re-personal-bests" value="Y" name="personal-bests">Personal Bests</input></div>
          <div><input type="checkbox" id="rhac-re-handicap-improvements" value="Y" name="handicap-improvements">Handicap Improvements</input></div>
          <div><input type="checkbox" id="rhac-re-new-classifications" value="Y" name="new-classifications">New Classifications</input></div>
        </div>
      </div>

      <div class="rhac-re-section">
        <label class="rhac-re-label">Include</label>
        <div class="rhac-re-checklist">
          <div><input type="checkbox" id="rhac-re-reassessments" value="Y" name="reassessments">Reassessments</input></div>
        </div>
      </div>

      <div class="rhac-re-reports-section">
        <label class="rhac-re-label">Reports</label>
        <div>
          <input type="text" id="rhac-re-report-name" value="" name="report-name"/>
        </div>
        <div>
          <button type="button" id="rhac-re-save-report">Save This Report</button>
        </div>
        <div>
          <button type="button" id="rhac-re-delete-report">Delete This Report</button>
        </div>
      </div>

    </div>
    <div id="rhac-re-clear"></div>
  </div>
  <div id="rhac-re-results" class="rhac-re">
    Results
  </div>
</div>
EOHTML;
    }

    private function archerOptions($include_archived=false) {
        $text = array();
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
        return implode($text);
    }

    private function outdoorRoundOptions() {
        $text = array();
        $text []= '<option value="">Any</option>';
        foreach ($this->getAllRounds() as $round) {
            if ($round->isOutdoor()) {
                $name = $round->getName();
                $text []= "<option value='$name'>$name</option>";
            }
        }
        return implode($text);
    }

    private function indoorRoundOptions() {
        $text = array();
        $text []= '<option value="">Any</option>';
        foreach ($this->getAllRounds() as $round) {
            if ($round->isIndoor()) {
                $name = $round->getName();
                $text []= "<option value='$name'>$name</option>";
            }
        }
        return implode($text);
    }

    private function allRoundOptions() {
        $text = array();
        $text []= '<option value="">Any</option>';
        foreach ($this->getAllRounds() as $round) {
            $name = $round->getName();
            $text []= "<option value='$name'>$name</option>";
        }
        return implode($text);
    }

    private function outdoorFamilyOptions() {
        $text = array();
        $text []= '<option value="">Any</option>';
        foreach ($this->getAllFamilies() as $family) {
            if ($family->isOutdoor()) {
                $name = $family->getName();
                $round_names = array();
                foreach ($family->getRounds() as $round) {
                    $round_names []= $round->getName();
                }
                if (count($round_names) > 1) {
                    $names = ':' . implode('|', $round_names);
                }
                else {
                    $names = $round_names[0];
                }
                $text []= "<option value='$names'>$name</option>";
            }
        }
        return implode($text);
    }

    private function indoorFamilyOptions() {
        $text = array();
        $text []= '<option value="">Any</option>';
        foreach ($this->getAllFamilies() as $family) {
            if ($family->isIndoor()) {
                $name = $family->getName();
                $round_names = array();
                foreach ($family->getRounds() as $round) {
                    $round_names []= $round->getName();
                }
                if (count($round_names) > 1) {
                    $names = ':' . implode('|', $round_names);
                }
                else {
                    $names = $round_names[0];
                }
                $text []= "<option value='$names'>$name</option>";
            }
        }
        return implode($text);
    }

    private function allFamilyOptions() {
        $text = array();
        foreach ($this->getAllFamilies() as $family) {
            $name = $family->getName();
            $round_names = array();
            foreach ($family->getRounds() as $round) {
                $round_names []= $round->getName();
            }
            if (count($round_names) > 1) {
                $names = ':' . implode('|', $round_names);
            }
            else {
                $names = $round_names[0];
            }
            $text []= "<option value='$names'>$name</option>";
        }
        return implode($text);
    }

    private function getAllFamilies() {
        if (!isset($this->round_families)) {
            $this->round_families = GNAS_Page::familyData();
        }
        return $this->round_families;
    }

    private function getAllRounds() {
        if (!isset($this->rounds)) {
            $this->rounds = GNAS_Page::roundData();
        }
        return $this->rounds;
    }

    private function allSeasonOptions() {
        $time = $this->getTime();
        $year = date('Y', $time);
        $month_day = date('md', $time);
        $seasons = array();
        $seasons []= '<option value="-">Any</option>';
        if ($month_day >= "0601") {
            $seasons []= sprintf('<option value="%04d/06/01-%04d/05/31">%04d - %04d</option>',
                                                            $year, $year + 1, $year, $year + 1);
        }
        while ($year >= 1996) {
            $seasons []= sprintf('<option value="%04d/01/01-%04d/12/31">%04d</option>',
                                                            $year, $year, $year);
            $seasons []= sprintf('<option value="%04d/06/01-%04d/05/31">%04d - %04d</option>',
                                                            $year - 1, $year, $year - 1, $year);
            $year--;
        }
        return implode($seasons);
    }

    private function outdoorSeasonOptions() {
        $time = $this->getTime();
        $year = date('Y', $time);
        $seasons = array();
        $seasons []= '<option value="-">Any</option>';
        while ($year >= 1996) {
            $seasons []= sprintf('<option value="%04d/01/01-%04d/12/31">%04d</option>',
                                                            $year, $year, $year);
            $year--;
        }
        return implode($seasons);
    }

    private function indoorSeasonOptions() {
        $time = $this->getTime();
        $year = date('Y', $time);
        $month_day = date('md', $time);
        $seasons = array();
        $seasons []= '<option value="-">Any</option>';
        if ($month_day >= "0601") {
            $seasons []= sprintf('<option value="%04d/06/01-%04d/05/31">%04d - %04d</option>',
                                                            $year, $year + 1, $year, $year + 1);
        }
        while ($year >= 1996) {
            $seasons []= sprintf('<option value="%04d/06/01-%04d/05/31">%04d - %04d</option>',
                                                            $year - 1, $year, $year - 1, $year);
            $year--;
        }
        return implode($seasons);
    }

    private function getTime() {
        if (!isset($this->time)) {
            $this->time = time();
        }
        return $this->time;
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
        $params = array();
        $fields = array("1 = 1");
        foreach(array(
                'outdoor',
                'archer',
                'category',
                'gender',
                'bow',
                'round',
                'date',
            ) as $field) {
            if ($_GET[$field]) {
                $val = $_GET[$field];
                if (substr($val, 0, 1) == '!') {
                    $val = substr($val, 1);
                    $fields []= "$field != ?";
                    $params []= $val;
                }
                elseif (substr($val, 0, 1) == ':') {
                    $val = substr($val, 1);
                    $subfields = array();
                    foreach(explode('|', $val) as $subval) {
                        $params []= $subval;
                        $subfields []= '?';
                    }
                    $fields []= "$field IN (" . implode(',', $subfields) . ")";
                }
                elseif (substr($val, 0, 1) == '[') {
                    $val = substr($val, 1);
                    $subfields = array();
                    foreach (explode(',', $val, 2) as $subval) {
                        $params []= $subval;
                        $subfields []= '?';
                    }
                    $fields []= "$field BETWEEN ? AND ?";
                }
                else {
                    $fields []= "$field = ?";
                    $params []= $val;
                }
            }
        }
        $subfields = array();
        if ($_GET['current_records']) {
            $subfields []= "club_record = ?";
            $params []= 'current';
        }
        if ($_GET['old_records']) {
            $subfields []= "club_record = ?";
            $params []= 'old';
        }
        if ($_GET['medals']) {
            $subfields []= "medal IN = (?,?,?)";
            $params []= 'bronze';
            $params []= 'silver';
            $params []= 'gold';
        }
        if ($_GET['two_five_two_awards']) {
            $subfields []= "two_five_two != ?";
            $params []= 'N';
        }
        if ($_GET['personal_bests']) {
            $subfields []= "personal_best = ?";
            $params []= 'Y';
        }
        if ($_GET['handicap_improvements']) {
            $subfields []= "handicap_improvement IS NOT NULL";
        }
        if ($_GET['new_classifications']) {
            $subfields []= "new_classification IS NOT NULL";
        }
        if (count($subfields) > 0) {
            $fields []= '(' . implode(' OR ', $subfields) . ')';
        }
        if (!$_GET['include_reassessment']) {
            $fields []= 'reassessment = ?';
            $params []= 'N';
        }
        $query = '* FROM scorecards WHERE '
               . implode(' AND ', $fields)
               . ' ORDER BY date, archer, handicap_ranking desc';
        $rows = $this->select($query, $params);
        return $this->debugQuery($query, $params) . $this->formatResults($rows);
    }

    private function debugQuery($query, $params) {
        // return '';
        $text = "<pre>\n";
        $text .= $query . "\n";
        $text .= print_r($params, true);
        $text .= "</pre>\n";
        return $text;
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
            $tr_class = '';
            if ($row['reassessment'] != "N") {
                $row['score'] = '';
                $row['handicap_ranking'] = '';
                $row['venue_id'] = 0;
                $tr_class = ' class="rhac-re-reassessment-row"';
            }
            $text []= "<tr$tr_class id='card-$row[scorecard_id]'>";
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
