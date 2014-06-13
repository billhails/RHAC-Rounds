<?php
include_once(RHAC_PLUGINS_ROOT . 'gnas-archery-rounds/rounds.php');

class RHAC_RecordsViewer {
    private $pdo;
    private $gender_map = array("M" => "Gents", "F" => "Ladies");
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
        $titles = array(
            'personal-best' => 'Personal Best',
            'current-club-record' => 'Current Club Record',
            'old-club-record' => 'Old Club Record',
            'green-252' => 'Second qualifying Green 252 score and award',
            'half-green-252' => 'First qualifying Green 252 score',
            'white-252' => 'Second qualifying White 252 score and award',
            'half-white-252' => 'First qualifying White 252 score',
            'black-252' => 'Second qualifying Black 252 score and award',
            'half-black-252' => 'First qualifying Black 252 score',
            'blue-252' => 'Second qualifying Blue 252 score and award',
            'half-blue-252' => 'First qualifying Blue 252 score',
            'red-252' => 'Second qualifying Red 252 score and award',
            'half-red-252' => 'First qualifying Red 252 score',
            'bronze-252' => 'Second qualifying Bronze 252 score and award',
            'half-bronze-252' => 'First qualifying Bronze 252 score',
            'silver-252' => 'Second qualifying Silver 252 score and award',
            'half-silver-252' => 'First qualifying Silver 252 score',
            'gold-252' => 'Second qualifying Gold 252 score and award',
            'half-gold-252' => 'First qualifying Gold 252 score',
            'medal-bronze' => 'Bronze medal in competition',
            'medal-silver' => 'Silver medal in competition',
            'medal-gold' => 'Gold medal in competition',
        );
        foreach(array('personal-best', 'current-club-record', 'old-club-record') as $icon) {
            $this->icons[$icon] = "<img class='badge no-shadow' title='$titles[$icon]' src='"
                                . RHAC_RE_PLUGIN_URL_ROOT
                                . "icons/$icon.png'/>";
        }
        foreach(array('green', 'white', 'black', 'blue', 'red', 'bronze', 'silver', 'gold') as $icon) {
            $key = "$icon-252";
            $halfkey = "half-$icon-252";
            $this->icons[$key] = "<img class='badge no-shadow' title='$titles[$key]' src='"
                                . RHAC_RE_PLUGIN_URL_ROOT
                                . "icons/$key.png'/>";
            $this->icons[$halfkey] = "<img class='badge no-shadow' title='$titles[$halfkey]' src='"
                                . RHAC_RE_PLUGIN_URL_ROOT
                                . "icons/$halfkey.png'/>";
        }
        foreach(array('bronze', 'silver', 'gold') as $icon) {
            $key = "medal-$icon";
            $this->icons[$key] = "<img class='badge no-shadow' title='$titles[$key]' src='"
                                . RHAC_RE_PLUGIN_URL_ROOT
                                . "icons/$key.png'/>";
        }
    }

    public function initDisplayMaps() {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        $this->initIcons();
        $venues = $this->select('venue_id, name from venue');
        foreach($venues as $venue) {
            $this->venue_map[$venue['venue_id']] = $venue['name'];
        }
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
        $this->classification_map['third'] = '<span title="New outdoor classification" class="third-class">Third&nbsp;Class</span>';
        $this->classification_map['second'] = '<span title="New outdoor classification" class="second-class">Second&nbsp;Class</span>';
        $this->classification_map['first'] = '<span title="New outdoor classification" class="first-class">First&nbsp;Class</span>';
        $this->classification_map['bm'] = '<span title="New outdoor classification" class="bowman-class">Bowman</span>';
        $this->classification_map['mbm'] = '<span title="New outdoor classification" class="master-bowman-class">Master&nbsp;Bowman</span>';
        $this->classification_map['gmbm'] =
                                '<span title="New outdoor classification" class="grand-master-bowman-class">Grand&nbsp;Master&nbsp;Bowman</span>';
        $this->classification_map['A'] = '<span title="New indoor classification" class="a-class">A</span>';
        $this->classification_map['B'] = '<span title="New indoor classification" class="b-class">B</span>';
        $this->classification_map['C'] = '<span title="New indoor classification" class="c-class">C</span>';
        $this->classification_map['D'] = '<span title="New indoor classification" class="d-class">D</span>';
        $this->classification_map['E'] = '<span title="New indoor classification" class="e-class">E</span>';
        $this->classification_map['F'] = '<span title="New indoor classification" class="f-class">F</span>';
        $this->classification_map['G'] = '<span title="New indoor classification" class="g-class">G</span>';
        $this->classification_map['H'] = '<span title="New indoor classification" class="h-class">H</span>';
        $this->classification_map['(archer)'] = '';
        $this->classification_map['(unclassified)'] = '';
        $this->classification_map['(third)'] = '<span title="confirmed outdoor classification" class="third-class">(Third&nbsp;Class)</span>';
        $this->classification_map['(second)'] = '<span title="confirmed outdoor classification" class="second-class">(Second&nbsp;Class)</span>';
        $this->classification_map['(first)'] = '<span title="confirmed outdoor classification" class="first-class">(First&nbsp;Class)</span>';
        $this->classification_map['(bm)'] = '<span title="confirmed outdoor classification" class="bowman-class">(Bowman)</span>';
        $this->classification_map['(mbm)'] = '<span title="confirmed outdoor classification" class="master-bowman-class">(Master&nbsp;Bowman)</span>';
        $this->classification_map['(gmbm)'] =
                        '<span title="confirmed outdoor classification" class="grand-master-bowman-class">(Grand&nbsp;Master&nbsp;Bowman)</span>';
        $this->classification_map['(A)'] = '<span title="Confirmed indoor classification" class="a-class">(A)</span>';
        $this->classification_map['(B)'] = '<span title="Confirmed indoor classification" class="b-class">(B)</span>';
        $this->classification_map['(C)'] = '<span title="Confirmed indoor classification" class="c-class">(C)</span>';
        $this->classification_map['(D)'] = '<span title="Confirmed indoor classification" class="d-class">(D)</span>';
        $this->classification_map['(E)'] = '<span title="Confirmed indoor classification" class="e-class">(E)</span>';
        $this->classification_map['(F)'] = '<span title="Confirmed indoor classification" class="f-class">(F)</span>';
        $this->classification_map['(G)'] = '<span title="Confirmed indoor classification" class="g-class">(G)</span>';
        $this->classification_map['(H)'] = '<span title="Confirmed indoor classification" class="h-class">(H)</span>';
        $this->classification_map[''] = '';
    }

    public function personalBestIcon() {
        return $this->pb_map['Y'];
    }

    public function currentClubRecordIcon() {
        return $this->cr_map['current'];
    }

    public function oldClubRecordIcon() {
        return $this->cr_map['old'];
    }

    public function greenTwoFiveTwoIcon() {
        return $this->tft_map['green/2'];
    }

    public function halfGreenTwoFiveTwoIcon() {
        return $this->tft_map['green/1'];
    }

    public function whiteTwoFiveTwoIcon() {
        return $this->tft_map['white/2'];
    }

    public function halfWhiteTwoFiveTwoIcon() {
        return $this->tft_map['white/1'];
    }

    public function blackTwoFiveTwoIcon() {
        return $this->tft_map['black/2'];
    }

    public function halfBlackTwoFiveTwoIcon() {
        return $this->tft_map['black/1'];
    }

    public function blueTwoFiveTwoIcon() {
        return $this->tft_map['blue/2'];
    }

    public function halfBlueTwoFiveTwoIcon() {
        return $this->tft_map['blue/1'];
    }

    public function redTwoFiveTwoIcon() {
        return $this->tft_map['red/2'];
    }

    public function halfRedTwoFiveTwoIcon() {
        return $this->tft_map['red/1'];
    }

    public function bronzeTwoFiveTwoIcon() {
        return $this->tft_map['bronze/2'];
    }

    public function halfBronzeTwoFiveTwoIcon() {
        return $this->tft_map['bronze/1'];
    }

    public function silverTwoFiveTwoIcon() {
        return $this->tft_map['silver/2'];
    }

    public function halfSilverTwoFiveTwoIcon() {
        return $this->tft_map['silver/1'];
    }

    public function goldTwoFiveTwoIcon() {
        return $this->tft_map['gold/2'];
    }

    public function halfGoldTwoFiveTwoIcon() {
        return $this->tft_map['gold/1'];
    }

    public function bronzeMedalIcon() {
        return $this->medal_map['bronze'];
    }

    public function silverMedalIcon() {
        return $this->medal_map['silver'];
    }

    public function goldMedalIcon() {
        return $this->medal_map['gold'];
    }

    public function archerClassificationIcon() {
        return $this->classification_map['archer'];
    }

    public function unclassifiedClassificationIcon() {
        return $this->classification_map['unclassified'];
    }

    public function thirdClassificationIcon() {
        return $this->classification_map['third'];
    }

    public function secondClassificationIcon() {
        return $this->classification_map['second'];
    }

    public function firstClassificationIcon() {
        return $this->classification_map['first'];
    }

    public function bmClassificationIcon() {
        return $this->classification_map['bm'];
    }

    public function mbmClassificationIcon() {
        return $this->classification_map['mbm'];
    }

    public function gmbmClassificationIcon() {
        return $this->classification_map['gmbm'];
    }

    public function aClassificationIcon() {
        return $this->classification_map['A'];
    }

    public function bClassificationIcon() {
        return $this->classification_map['B'];
    }

    public function cClassificationIcon() {
        return $this->classification_map['C'];
    }

    public function dClassificationIcon() {
        return $this->classification_map['D'];
    }

    public function eClassificationIcon() {
        return $this->classification_map['E'];
    }

    public function fClassificationIcon() {
        return $this->classification_map['F'];
    }

    public function gClassificationIcon() {
        return $this->classification_map['G'];
    }

    public function hClassificationIcon() {
        return $this->classification_map['H'];
    }

    public function confirmedArcherClassificationIcon() {
        return $this->classification_map['(archer)'];
    }

    public function confirmedUnclassifiedClassificationIcon() {
        return $this->classification_map['(unclassified)'];
    }

    public function confirmedThirdClassificationIcon() {
        return $this->classification_map['(third)'];
    }

    public function confirmedSecondClassificationIcon() {
        return $this->classification_map['(second)'];
    }

    public function confirmedFirstClassificationIcon() {
        return $this->classification_map['(first)'];
    }

    public function confirmedBmClassificationIcon() {
        return $this->classification_map['(bm)'];
    }

    public function confirmedMbmClassificationIcon() {
        return $this->classification_map['(mbm)'];
    }

    public function confirmedGmbmClassificationIcon() {
        return $this->classification_map['(gmbm)'];
    }

    public function confirmedAclassificationIcon() {
        return $this->classification_map['(A)'];
    }

    public function confirmedBclassificationIcon() {
        return $this->classification_map['(B)'];
    }

    public function confirmedCclassificationIcon() {
        return $this->classification_map['(C)'];
    }

    public function confirmedDclassificationIcon() {
        return $this->classification_map['(D)'];
    }

    public function confirmedEclassificationIcon() {
        return $this->classification_map['(E)'];
    }

    public function confirmedFclassificationIcon() {
        return $this->classification_map['(F)'];
    }

    public function confirmedGclassificationIcon() {
        return $this->classification_map['(G)'];
    }

    public function confirmedHclassificationIcon() {
        return $this->classification_map['(H)'];
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

    public function view($help_page_id) {
        $this->initDisplayMaps();
        $help_page_url = get_permalink($help_page_id);
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
  <div id="rhac-re-simpleform" class="rhac-re">
    <select id="rhac-re-report" title="select a report to run">
    </select>
    <button type="button" title="run the selected report" class="rhac-re-button" id="rhac-re-run-report">Run Report</button>
    <button type="button" title="customize the selected report" class="rhac-re-button" id="rhac-re-edit-report">Customize Report</button>
    <a href="$help_page_url" target="_blank" class="rhac-re-help" title="see help (in a new window)">Help</a>
  </div>
  <div id="rhac-re-moreform" class="rhac-re">
    <div id="rhac-re-more-left">

      <div class="rhac-re-section">
        <label class="rhac-re-label" title="select a season type" for="seasons">Seasons</label>
        <div class="rhac-re-radios">
          <input type="radio" name="season" class="rhac-re-outdoor" value="Y" id="rhac-re-outdoor-y"/><label for="rhac-re-outdoor-y">Outdoor</label>
          <input type="radio" name="season" class="rhac-re-outdoor" value="N" id="rhac-re-outdoor-n"/><label for="rhac-re-outdoor-n">Indoor</label>
          <input type="radio" name="season" class="rhac-re-outdoor" checked="checked" value="" id="rhac-re-outdoor-both"/><label for="rhac-re-outdoor-both">Both</label>
        </div>
      </div>

      <div class="rhac-re-section">
        <label class="rhac-re-label" title="select an archer" for="archer">Archer</label>
        <div>
          <div><input type="checkbox" class="rhac-re-checkbox" value="Y" title="allows you to select lapsed archers in the dropdown below" id="rhac-re-include-lapsed"/><label for="rhac-re-include-lapsed">Include lapsed members</label></div>
          <div>
            <select id="rhac-re-archer" name="archer">
$current_archers
            </select>
          </div>
        </div>
      </div>

      <div class="rhac-re-section">
        <label class="rhac-re-label" title="you can restrict the search to a particular age group, gender and/or bow type">Category</label>
        <div class="rhac-re-selects">
          <select id="rhac-re-age" name="age">
              <option value="">Any Age</option>
              <option value="adult">Senior</option>
              <option value="U18">Under 18</option>
              <option value="U16">Under 16</option>
              <option value="U14">Under 14</option>
              <option value="U12">Under 12</option>
          </select>
          <select id="rhac-re-gender" name="gender">
              <option value="">Any Gender</option>
              <option value="M">Gent</option>
              <option value="F">Lady</option>
          </select>
          <select id="rhac-re-bow" name="bow">
              <option value="">Any Bow</option>
              <option value="recurve">Recurve</option>
              <option value="compound">Compound</option>
              <option value="longbow">Longbow</option>
              <option value="barebow">Barebow</option>
          </select>
        </div>
      </div>

      <div class="rhac-re-section">
        <label class="rhac-re-label" title="restrict the search to a particular round or round family" for="round">Round</label>
        <div class="rhac-re-radios">
          <input type="radio" title="select from individual rounds" class="rhac-re-single-round" value="Y" name="single-round" checked="checked" id="rhac-re-single-round-y"/><label for="rhac-re-single-round-y">Round</label>
          <input type="radio" title="select from round families (like 252s)" class="rhac-re-single-round" value="N" name="single-round" id="rhac-re-single-round-n"/><label for="rhac-re-single-round-n">Round Family</label>
        </div>
        <select id="rhac-re-round" name="round">
$outdoor_rounds
        </select>
      </div>

      <div class="rhac-re-section">
        <label class="rhac-re-label" title="choose a single date or a range of dates, or pick a season">Dates</label>
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
        <label class="rhac-re-label" title="limit the search to scores with any of these attributes">Limit to</label>
        <div class="rhac-re-checklist">
          <span class="rhac-re-span-limit"><input type="checkbox" class="rhac-re-checkbox" id="rhac-re-current-records" value="Y" name="current-records"/><label for="rhac-re-current-records">Current Records</label></span>

          <span class="rhac-re-span-limit"><input type="checkbox" class="rhac-re-checkbox" id="rhac-re-old-records" value="Y" name="old-records"/><label for="rhac-re-old-records" >Old Records</label></span>

          <span class="rhac-re-span-limit"><input type="checkbox" class="rhac-re-checkbox" id="rhac-re-medals" value="Y" name="medals"/><label for="rhac-re-medals" >Medals</label></span>

          <span class="rhac-re-span-limit"><input type="checkbox" class="rhac-re-checkbox" id="rhac-re-252" value="Y" name="medals"/><label for="rhac-re-252" >252 awards</label></span>

          <span class="rhac-re-span-limit"><input type="checkbox" class="rhac-re-checkbox" id="rhac-re-personal-bests" value="Y" name="personal-bests"/><label for="rhac-re-personal-bests" >Personal Bests</label></span>

          <span class="rhac-re-span-limit"><input type="checkbox" class="rhac-re-checkbox" id="rhac-re-handicap-improvements" value="Y" name="handicap-improvements"/><label for="rhac-re-handicap-improvements" >Handicap Improvements</label></span>

          <span class="rhac-re-span-limit"><input type="checkbox" class="rhac-re-checkbox" id="rhac-re-new-classifications" value="Y" name="new-classifications"/><label for="rhac-re-new-classifications" >New Classifications</label></span>

          <span class="rhac-re-span-limit"><input type="checkbox" class="rhac-re-checkbox" id="rhac-re-reassessments" value="Y" name="reassessments" /><label for="rhac-re-reassessments" title="reassessments are not real scores, they happen at the end of each indoor and outdoor season, and whenever an archer changes age group. The only way to see these is to check this box.">Include Reassessments</label></span>

        </div>
      </div>

      <div class="rhac-re-reports-section">
        <label class="rhac-re-label rhac-re-label-save" title="save or delete personal reports">Save Report</label>
        <div>
          <input type="text" id="rhac-re-report-name" value="" name="report-name"/>
        </div>
        <div>
          <button type="button" class="rhac-re-button" id="rhac-re-save-report">Save This Report</button>
        </div>
        <div>
          <button type="button" class="rhac-re-button" id="rhac-re-delete-report">Delete This Report</button>
        </div>
      </div>

    </div>
    <div id="rhac-re-clear"></div>
  </div>
  <div id="rhac-re-results" class="rhac-re">
    Results
  </div>
  <div id="rhac-re-cannot-save" class="rhac-re-simple-dialog">
    <p>You can't change a predefined report, try editing the report name first.</p>
  </div>
  <div id="rhac-re-cannot-delete" class="rhac-re-simple-dialog">
    <p>You can't delete a predefined report.</p>
  </div>
  <div id="rhac-re-enter-name" class="rhac-re-simple-dialog">
    <p>Please enter a report name first.</p>
  </div>
  <div id="rhac-re-confirm-replace">
    <p>Are you sure you want to replace your "<span class="rhac-re-report-name"></span>" report?</p>
  </div>
  <div id="rhac-re-confirm-saved" class="rhac-re-simple-dialog">
    <p>Report "<span class="rhac-re-report-name"></span>" saved.</p>
    <p>You should now see it in your list of reports.</p>
  </div>
  <div id="rhac-re-confirm-delete">
    <p>Are you sure you want to delete your "<span class="rhac-re-report-name"></span>" report?</p>
  </div>
  <div id="rhac-re-report-nonexistant" class="rhac-re-simple-dialog">
    <p>Report "<span class="rhac-re-report-name"></span>" does not exist!</p>
  </div>
  <div id="rhac-re-confirm-deleted" class="rhac-re-simple-dialog">
    <p>Report "<span class="rhac-re-report-name"></span>" deleted.</p>
  </div>
  <div id="rhac-re-quota-exceeded" class="rhac-re-simple-dialog">
    <p>Quota Exceeded, please delete some old reports first.</p>
  </div>
  <div id="rhac-re-old-browser" class="rhac-re-simple-dialog">
    <p>You seem to have a very old browser that does not support saving reports, please upgrade!</p>
  </div>
</div>
EOHTML;
    }

    private function archerOptions($include_archived=false) {
        $text = array();
        $text []= "<option value=''>Any Archer</option>";
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
        $text []= '<option value="">Any Outdoor Round</option>';
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
        $text []= '<option value="">Any Indoor Round</option>';
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
        $text []= '<option value="">Any Round</option>';
        foreach ($this->getAllRounds() as $round) {
            $name = $round->getName();
            $text []= "<option value='$name'>$name</option>";
        }
        return implode($text);
    }

    private function outdoorFamilyOptions() {
        $text = array();
        $text []= '<option value="">Any Outdoor Round Family</option>';
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
        $text []= '<option value="">Any Indoor Round Family</option>';
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
        $text []= '<option value="">Any Round Family</option>';
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
        $seasons []= '<option value="-">Any Season</option>';
        if ($month_day >= "0701") {
            $seasons []= sprintf('<option value="%04d/07/01-%04d/06/31">%04d - %04d</option>',
                                                            $year, $year + 1, $year, $year + 1);
        }
        while ($year >= 1996) {
            $seasons []= sprintf('<option value="%04d/01/01-%04d/12/31">%04d</option>',
                                                            $year, $year, $year);
            $seasons []= sprintf('<option value="%04d/07/01-%04d/06/31">%04d - %04d</option>',
                                                            $year - 1, $year, $year - 1, $year);
            $year--;
        }
        return implode($seasons);
    }

    private function outdoorSeasonOptions() {
        $time = $this->getTime();
        $year = date('Y', $time);
        $seasons = array();
        $seasons []= '<option value="-">Any Outdoor Season</option>';
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
        $seasons []= '<option value="-">Any Indoor Season</option>';
        if ($month_day >= "0701") {
            $seasons []= sprintf('<option value="%04d/07/01-%04d/06/31">%04d - %04d</option>',
                                                            $year, $year + 1, $year, $year + 1);
        }
        while ($year >= 1996) {
            $seasons []= sprintf('<option value="%04d/07/01-%04d/06/31">%04d - %04d</option>',
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
            $subfields []= "medal IN (?,?,?)";
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

    public function matchReport($date) {
        $query = "* FROM scorecards where date = ? and reassessment = ?";
        $params = array($date, 'N');
        $rows = $this->select($query, $params);
        $personal_bests = array();
        $club_records = array();
        $handicap_improvements = array();
        $new_classifications = array();
        $two_five_twos = array();
        $genders = array(
            'M' => 'Gents',
            'F' => 'Ladies',
        );
        $outdoors = array(
            'Y' => 'outdoor',
            'N' => 'indoor',
        );
        $text = array();
        foreach ($rows as $row) {
            if ($row['round'] == 'National') {
                $row['round'] = ':National'; # for hover
            }
            if ($row['personal_best'] == 'Y') {
                $personal_bests []= $row;
            }
            if ($row['club_record'] && $row['club_record'] != 'N') {
                $club_records []= $row;
            }
            if ($row['handicap_improvement']) {
                $handicap_improvements []= $row;
            }
            if (   $row['new_classification']
                && $row['new_classification'] != 'archer'
                && $row['new_classification'] != '(archer)') {
                    $new_classifications []= $row;
            }
            if ($row['two_five_two'] != 'N'
                && substr($row['two_five_two'], -1, 1) != '1') {
                $two_five_twos []= $row;
            }
        }

        if (count($personal_bests)) {
            $text []= '<h2>Personal Bests</h2>';
            $text []= '<ul>';
            foreach ($personal_bests as $row) {
                $text []= '<li>' . $row['archer'] . ' - '
                        . $row['bow'] . ' - '
                        . $row['round'] . '</li>';
            }
            $text []= '</ul>';
        }
        if (count($handicap_improvements)) {
            $text []= '<h2>Handicap Improvements</h2>';
            $text []= '<ul>';
            foreach ($handicap_improvements as $row) {
                $text []= '<li>' . $row['archer'] . ' - '
                        . $outdoors[$row['outdoor']] . ' '
                        . $row['bow'] . ' - '
                        . $row['handicap_improvement'] . '</li>';
            }
            $text []= '</ul>';
        }
        if (count($club_records)) {
            $text []= '<h2>Club Records</h2>';
            $text []= '<ul>';
            foreach ($club_records as $row) {
                $text []= '<li>' . $row['archer'] . ' - '
                        . $genders[$row['gender']] . ' '
                        . $row['category'] . ' ' . $row['bow'] . ' - '
                        . $row['round'] . '</li>';
            }
            $text []= '</ul>';
        }
        if (count($two_five_twos)) {
            $text []= '<h2>252 Awards</h2>';
            $text []= '<ul>';
            foreach ($two_five_twos as $row) {
                $text []= '<li>' . $row['archer'] . ' - '
                        . $row['bow'] . ' - '
                        . $row['round'] . '</li>';
            }
            $text []= '</ul>';
        }
        if (count($new_classifications)) {
            $text []= '<h2>New Classifications</h2>';
            $text []= '<ul>';
            foreach ($new_classifications as $row) {
                $classification =
                    $this->mungeClassification($row['new_classification']);
                $text []= '<li>' . $row['archer'] . ' - '
                        . $genders[$row['gender']] . ' '
                        . $row['category'] . ' ' . $row['bow'] . ' - '
                        . $classification . '</li>';
            }
            $text []= '</ul>';
        }
        return implode("\n", $text);
    }

    private function mungeClassification($classification) {
        $ext = '';
        $map = array(
            'archer' => 'Archer',
            'third' => 'Third Class',
            'second' => 'Second Class',
            'first' => 'First Class',
            'bm' => 'Bowman',
            'mbm' => 'Master Bowman',
            'gmbm' => 'Grand Master Bowman',
            'A' => 'A',
            'B' => 'B',
            'C' => 'C',
            'D' => 'D',
            'E' => 'E',
            'F' => 'F',
            'G' => 'G',
            'H' => 'H',
        );
        if (substr($classification, 0, 1) == '(') {
            $ext = ' (confirmed)';
            $classification = substr($classification, 1, -1);
        }
        return $map[$classification] . $ext;
    }

    private function debugQuery($query, $params) {
        return '';
        $text = "<pre>\n";
        $text .= $query . "\n";
        $text .= print_r($params, true);
        $text .= "</pre>\n";
        return $text;
    }

    private function formatResults($rows) {
        $this->initDisplayMaps();
        $classification_sort = array(
            'A' => 10, 'B' => 9, 'C' => 8, 'D' => 7, 'E' => 6, 'F' => 5, 'G' => 4, 'H' => 3,
            'gmbm' => 8, 'mbm' => 7, 'bm' => 6, 'first' => 5, 'second' => 4, 'third' => 3,
            'unclassified' => 2, 'archer' => 1, '' => 0,
        );
        $text = array();
        $headers = array(
            'Date', 'Archer', 'Category', 'Round', 'Place Shot',
            'H/C', 'Class', 'Score', 'Badges');
        $text []= '<table id="rhac-re-results-table">';
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
            $score_class = '';
            $score_data = '';
            $score_title = '';
            if ($row['reassessment'] == "N") {
                if ($row['has_ends'] == "Y") {
                    $score_class = ' rhac-re-score-with-ends';
                    $score_data = "data-scorecard-id='$row[scorecard_id]'";
                    $score_title = 'title="click to show score card"';
                }
            } else {
                $row['score'] = '';
                $row['handicap_ranking'] = '';
                $row['venue_id'] = 0;
                $tr_class = 'class="rhac-re-reassessment-row"';
            }
            $text []= "<tr $tr_class id='card-$row[scorecard_id]'>";
            $text []= "<td>$row[date]</td>";
            $text []= "<td class='rhac-re-archer-row'>$row[archer]</td>";
            $text []= "<td>" . $this->category($row) . "</td>";
            $text []= "<td>$row[round]</td>";
            $text []= "<td>" . $this->venue_map[$row[venue_id]] . "</td>";
            $text []= "<td>$row[handicap_ranking]</td>";
            $classification_order = $classification_sort[$row['classification']];
            $text []= "<td data-sort='$classification_order'>$row[classification]</td>";
            $text []= "<td class='rhac-re-score-row $score_class' $score_title $score_data>$row[score]</td>";
            $data_icon_search = array();
            $badges = array("<span class='rhac-re-badges'>");
            $badges []= $this->classification_map[$row[new_classification]];
            if (strlen($row[handicap_improvement])) {
                $badges []= '<span title="New or improved handicap" class="handicap-improvement">' . $row[handicap_improvement] . '</span>';
            }
            if ($row['medal']) {
                $data_icon_search []= $row['medal'];
                $data_icon_search []= 'medal';
            }
            $badges []= $this->medal_map[$row[medal]];
            if ($row['club_record'] != "N") {
                $data_icon_search []= "$row[club_record] record";
            }
            $badges []= $this->cr_map[$row[club_record]];
            $badges []= $this->tft_map[$row[two_five_two]];
            if ($row[personal_best] == "Y") {
                $data_icon_search []= "personal best";
            }
            $badges []= $this->pb_map[$row[personal_best]];
            if (0) {
                $badges []= 'new_classification=[' . $row[new_classification] . ']';
                $badges []= 'handicap_improvement=[' . $row[handicap_improvement] . ']';
                $badges []= 'medal=[' . $row[medal] . ']';
                $badges []= 'club_record=[' . $row[club_record] . ']';
                $badges []= 'two_five_two=[' . $row[two_five_two] . ']';
                $badges []= 'personal_best=[' . $row[personal_best] . ']';
            }
            $badges []= "</span>";
            $text []= "<td data-search='" . implode(' ', $data_icon_search) . "'>";
            $text []= implode(' ', $badges);
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
