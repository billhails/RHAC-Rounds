<?php

################
class GNAS_PDO {
    private static $pdo;

    /**
     * disallow creation of instances.
     */
    private function __construct() {
    }

    public static function get() {
        if (!isset(self::$pdo)) {
            $path = plugin_dir_path(__FILE__)
                     . '../gnas-archery-rounds/archery.db';
            try {
                self::$pdo = new PDO('sqlite:' . $path);
            } catch (PDOException $e) {
                wp_die('Error!: ' . $e->getMessage());
                exit();
            }
            self::$pdo->exec('PRAGMA foreign_keys = ON');
        }
        return self::$pdo;
    }

    public static function SELECT($query, $params = array()) {
        // is_array
        $stmt = self::get()->prepare('SELECT ' . $query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();
        return $rows;
    }
}

####################
class GNAS_PageURL {
    private static $pageURL;
    private static $existingParams;
    /**
     * disallow creation of instances.
     */
    private function __construct() {
    }

    public static function make($params) {
        if (!isset(self::$pageURL)) {
            $pageURL = 'http';
            if ($_SERVER['HTTPS'] == 'on') {
                $pageURL .= 's';
            }
            $pageURL .= '://';
            $pageURL .= $_SERVER['SERVER_NAME'];
            if ($_SERVER['SERVER_PORT'] != '80') {
                 $pageURL .= ':' . $_SERVER['SERVER_PORT'];
            }
            $parts = explode('?', $_SERVER['REQUEST_URI'], 2);
            $requestURI = $parts[0];
            self::$existingParams = array();
            foreach (explode('&', $parts[1]) as $param) {
                list($key, $value) = explode('=', $param);
                if ($key == 'page_id') {
                    self::$existingParams[$key] = $value;
                }
            }
            $pageURL .= $requestURI;
            self::$pageURL = $pageURL;
        }
        $params = array_merge(self::$existingParams, $params);
        if (count($params) > 0) {
            $p = array();
            foreach ($params as $key => $value) {
                $p []= $key . '=' . $value;
            }
            return self::$pageURL . '?' . implode('&', $p);
        } else {
            return self::$pageURL;
        }
    }

}

######################
class GNAS_AgeGroups {
    private static $age_groups;

    /**
     * disallow creation of instances.
     */
    private function __construct() {
    }

    static function get() {
        if (!isset(self::$age_groups)) {
            $rows = GNAS_PDO::SELECT('age_group'
                                    . ' FROM age_groups'
                                    . ' ORDER BY display_order');
            $age_groups = array();
            foreach ($rows as $row) {
                $age_groups []= $row['age_group'];
            }
            self::$age_groups = $age_groups;
        }
        return self::$age_groups;
    }
}


####################
class GNAS_Genders {
    private static $genders;

    /**
     * disallow creation of instances.
     */
    private function __construct() {
    }

    static function get() {
        if (!isset(self::$genders)) {
            $rows = GNAS_PDO::SELECT('gender'
                                    . ' FROM genders'
                                    . ' ORDER BY gender');
            $genders = array();
            foreach ($rows as $row) {
                $genders []= $row['gender'];
            }
            self::$genders = $genders;
        }
        return self::$genders;
    }
}

#############################
abstract class GNAS_Measure {
    abstract public function getUnits();
    abstract public function getAllDistances();
    abstract public function getName();

    public function getTableHeader() {
        return '<thead><tr><th rowspan="2">&nbsp;</th><th colspan="'
            . $this->totalDistances()
            . '">Dozens at each distance</th></tr>'
            . '<tr>' . $this->getFaceHeaders() . '</tr>'
            . '<tr><th>Round</th>'
            . $this->getDistanceHeaders()
            . '</tr></thead>';
    }

    public function makeTableDistances(GNAS_Distances $distance) {
        $row = array();
        foreach ($this->getAllDistances() as $face => $allDistances) {
            foreach ($allDistances as $thisDistance) {
                $row []= $distance->tableData($face, $thisDistance);
            }
        }
        return implode($row);
    }

    private function totalDistances() {
        $total = 0;
        foreach($this->getAllDistances() as $distances) {
            $total += count($distances);
        }
        return $total;
    }

    private function getFaceHeaders() {
        $result = '';
        foreach($this->getAllDistances() as $face => $distances) {
            $result .= '<th colspan="'
                    . count($distances)
                    . '">'
                    . $face
                    . '</th>';
        }
        return $result;
    }

    private function getDistanceHeaders() {
        $units = $this->getUnits();
        $headers = array();
        foreach ($this->getAllDistances() as $distances) {
            foreach ($distances as $distance) {
                $headers []= $distance . $units;
            }
        }
        return '<th>' . implode('</th><th>', $headers) . '</th>';
    }

}

#################################################
class GNAS_ImperialMeasure extends GNAS_Measure {

    public function getUnits() {
        return 'y';
    }

    public function getAllDistances() {
        return array('122cm' => array(100, 80, 60, 50, 40, 30, 20, 10));
    }

    public function getName() {
        return 'imperial';
    }

}

###############################################
class GNAS_MetricMeasure extends GNAS_Measure {

    public function getUnits() {
        return 'm';
    }

    public function getAllDistances() {
        return array('122cm' => array(90, 70, 60, 50, 40, 30, 20),
                     '80cm' => array(50, 40, 30, 20, 15, 10));
    }

    public function getName() {
        return 'metric';
    }

}

#############################
abstract class GNAS_Scoring {
    abstract public function getName();
    abstract public function getMultiplier();

    public function maxScore(GNAS_ArrowCounts $arrowCounts) {
        return $this->getMultiplier() * $arrowCounts->getTotalArrows();
    }

}

################################################
class GNAS_TenZoneScoring extends GNAS_Scoring {
    public function getName() { return 'ten zone'; }
    public function getMultiplier() { return 10; }
}

#################################################
class GNAS_FiveZoneScoring extends GNAS_Scoring {
    public function getName() { return 'five zone'; }
    public function getMultiplier() { return 9; }
}

################################################
class GNAS_FiveMaxScoring extends GNAS_Scoring {
    public function getName() { return 'five max'; }
    public function getMultiplier() { return 5; }
}

#############################
class GNAS_SingleArrowCount {
    private $numArrows;
    private $dozens;
    private $face;
    private $diameter;

    public function getNumArrows() {
        return $this->numArrows;
    }

    public function getDozens() {
        return $this->dozens;
    }

    public function getFace() {
        return $this->face;
    }

    public function __construct($row) {
        $this->numArrows = $row['num_arrows'];
        $this->dozens = self::doz($row['num_arrows']);
        $this->face = $row['face'];
        $this->diameter = $row['diameter'];
    }

    public function getDescriptionRow() {
        return '<li>'
               . $this->dozens
               . ' doz, '
               . $this->face
               . ' face</li>';
    }

    public function getDiameter() {
        return $this->diameter;
    }

    private static function doz($num) {
        $doz = floor($num / 12);
        $half = '';
        if ($num % 12 != 0) {
            $half = '&frac12;';
        }
        return($doz . $half);
    }
}

########################
class GNAS_ArrowCounts {
    private $counts;
    private $total;

    private function __construct(array $counts) {
        $this->counts = $counts;
    }

    public function getCounts() {
        return $this->counts;
    }

    public function getTotalArrows() {
        if (!isset($this->total)) {
            $this->total = 0;
            foreach ($this->counts as $count) {
                $this->total += $count->getNumArrows();
            }
        }
        return $this->total;
    }

    public function getDescription(GNAS_Scoring $scoring) {
        $description = '<ul>';
        foreach ($this->counts as $count) {
            $description .= $count->getDescriptionRow();
        }
        $description .= '</ul>';
        $description .= '<p>Maximum score '
                        . $scoring->maxScore($this)
                        . '.</p>';
        return $description;
    }

    public function getNumArrowCounts() {
        return count($this->counts);
    }

    public function getSingleArrowCount($i) {
        return $this->counts[$i];
    }

    public static function create($familyName) {
        $rows = GNAS_PDO::SELECT('*'
            . ' FROM arrow_count'
            . ' WHERE family_name = ?'
            . ' ORDER BY distance_number',
            array($familyName));
        $counts = array();
        foreach ($rows as $row) {
            $counts []= new GNAS_SingleArrowCount($row);
        }
        return new self($counts);
    }
}

###########################
class GNAS_SingleDistance {
    private $distance;
    private $singleArrowCount;
    private $measure;

    public function __construct($distance,
                                GNAS_SingleArrowCount $singleArrowCount,
                                GNAS_Measure $measure) {
        $this->distance = $distance;
        $this->singleArrowCount = $singleArrowCount;
        $this->measure = $measure;
    }

    public function getDistance() {
        return $this->distance;
    }

    public function getNumArrows() {
        return $this->singleArrowCount->getNumArrows();
    }

    public function getDozens() {
        return $this->singleArrowCount->getDozens();
    }

    public function getFace() {
        return $this->singleArrowCount->getFace();
    }

    public function getDiameter() {
        return $this->singleArrowCount->getDiameter();
    }

    public function getUnits() {
        return $this->measure->getUnits();
    }

    public function getDescription() {
        return '<li>'
            . $this->getDozens()
            . ' doz at '
            . $this->getDistance()
            . $this->getUnits()
            . ', '
            . $this->getFace()
            . ' face</li>';

    }

    public function getJavaScript() {
        return '{'
             . 'N: ' . $this->getNumArrows()
             . ', D: ' . $this->getDiameter()
             . ', R: ' . $this->getDistance()
             . '}';
    }

}

######################
class GNAS_Distances {
    private $roundName;
    private $distances;
    private $arrowCounts;
    private $rawDistances;
    
    public function tableData($face, $distance) {
        $content = '&nbsp;';
        $classdecl = '';
        if (isset($this->distances[$face])
            && isset($this->distances[$face][$distance])) {
            $content = $this->distances[$face][$distance]->getDozens();
            $classdecl = ' class="distance-populated"';
        }
        return "<td$classdecl>$content</td>";
    }

    private function __construct($roundName,
                                 array $distances,
                                 GNAS_ArrowCounts $arrowCounts) {
        $this->roundName = $roundName;
        $this->rawDistances = $distances;
        $this->arrowCounts = $arrowCounts;
        $this->distances = array();
        foreach ($distances as $distance) {
            if (!isset($this->distances[$distance->getFace()])) {
                $this->distances[$distance->getFace()] = array();
            }
            $this->distances[$distance->getFace()][$distance->getDistance()] =
                $distance;
        }
    }

    public function rawData() {
        return $this->rawDistances;
    }

    public function getJavaScript(GNAS_Measure $measure) {
        $distances_js = array();
        foreach ($this->distances as $face => $faceDistances) {
            foreach ($faceDistances as $singleDistance) {
                $distances_js []= $singleDistance->getJavaScript();
            }
        }
        $javaScript = '<script>';
        $javaScript .= 'rhac_measure="' . $measure->getName() . '";';
        $javaScript .= 'rhac_distances=[' .implode(', ', $distances_js) . ']';
        $javaScript .= '</script>';
        return $javaScript;
    }

    public function getDescription(GNAS_Scoring $scoring,
                                   GNAS_Measure $measure) {
        $description = '';
        $description .= '<p>'
                        . ucfirst($measure->getName())
                        . ', '
                        . $scoring->getName()
                        . ' scoring</p>';
        $description .= '<ul>';
        foreach ($this->distances as $face => $faceDistances) {
            foreach ($faceDistances as $singleDistance) {
                $description .= $singleDistance->getDescription();
            }
        }
        $description .= '</ul>';
        $description .= '<p>Maximum score '
                        . $scoring->maxScore($this->arrowCounts)
                        . '.</p>';
        return $description;
    }

    public static function create($roundName,
                                  GNAS_ArrowCounts $arrowCounts,
                                  GNAS_Measure $measure) {
        $rows = GNAS_PDO::SELECT('*'
            . ' FROM distance'
            . ' WHERE round_name = ?'
            . ' ORDER BY distance_number',
            array($roundName));
        $distances = array();
        foreach ($rows as $row) {
            $distances []= $row['distance'];
        }
        return new self($roundName,
                        self::collateArrows($distances,
                                            $arrowCounts,
                                            $measure),
                        $arrowCounts);
    }

    private static function collateArrows(array $distances,
                                          GNAS_ArrowCounts $arrowCounts,
                                          GNAS_Measure $measure) {
        $collation = array();
        $num_distances = count($distances);
        $num_arrowCounts = $arrowCounts->getNumArrowCounts();
        for ($i = 0; $i < $num_distances && $i < $num_arrowCounts; ++ $i) {
            $collation []=
                new GNAS_SingleDistance($distances[$i],
                                        $arrowCounts->getSingleArrowCount($i),
                                        $measure);
        }
        return($collation);
    }

}

##################################
abstract class GNAS_Unrecognised {
    private $name;

    public function asText() {
        return '<h1>Unrecognised '
               . $this->getTypeName()
               . ': <q>'
               . $this->name
               . '</q></h1>'
               . '<p>If you got here by following a link from the site,'
               . ' then please inform the management.</p>'
               . '<p>If you got here by messing with the url parameters'
               . ' directly, then better luck next time.</p>';
    }

    public function getTableBody() {
        return '<tbody></tbody>';
    }

    public function __construct($name) {
        $this->name = htmlentities($name); // security
    }

    abstract public function getTypeName();
}


################################
interface GNAS_FamilyInterface {
    public function asText();
    public function getTableBody();
}

##################################
class GNAS_UnrecognisedRoundFamily
    extends GNAS_Unrecognised
    implements GNAS_FamilyInterface {

    public function getTypeName() {
        return 'round family';
    }

}

########################################################
class GNAS_RoundFamily implements GNAS_FamilyInterface {
    private $name;
    private $scoring;
    private $venue;
    private $measure;
    private $rounds = null;
    private $arrowCounts;

    private static $instances = array();

    public function asText() {
        return $this->getTitle()
             . $this->getDescription()
             . $this->getTable();
    }

    public function asMenu() {
        $this->populate();
        if (count($this->rounds) == 1) {
            return array($this->rounds[0]->getName());
        }
        else {
            $children = array();
            foreach ($this->rounds as $round) {
                $children []= $round->getName();
            }
            return array($this->name => $children);
        }
    }

    private function getTitle() {
        return '<h1>' . $this->name . '</h1>';
    }

    private function getDescription() {
        return '<p>'
               . ucfirst($this->measure->getName())
               . ', '
               . $this->scoring->getName()
               . ' scoring.</p>'
              . $this->getArrowCounts()->getDescription($this->scoring);
    }

    private function getTable() {
        return '<table>'
               . $this->getTableHeader()
               . $this->getTableBody()
               . '</table>';
    }

    public function getTableHeader() {
        return $this->measure->getTableHeader();
    }

    public function getArrowCounts() {
        if (!isset($this->arrowCounts)) {
            $this->arrowCounts = GNAS_ArrowCounts::create($this->name);
        }
        return $this->arrowCounts;
    }

    public function getTableBody() {
        $this->populate();
        $rows = array();
        $rows []= '<tbody>';
        foreach ($this->rounds as $round) {
            $rows []= $round->getTableRow();
        }
        $rows []= '</tbody>';
        return implode($rows);
    }

    public function getMeasure() {
        return $this->measure;
    }

    public function getScoring() {
        return $this->scoring;
    }

    private function populate() {
        if (isset($this->rounds)) return;
        $this->rounds = array();
        $rows = GNAS_PDO::SELECT('*'
            . ' FROM round'
            . ' WHERE family_name = ?'
            . ' ORDER by display_order',
            array($this->name));
        foreach ($rows as $row) {
            $this->rounds[$row['display_order']] =
                GNAS_Round::getInstanceFromRow($row);
        }
    }

    private function __construct($name, $scoring, $venue, $measure) {
        $this->name = $name;
        if ($scoring == 'ten zone')
            $this->scoring = new GNAS_TenZoneScoring();
        else if ($scoring == 'five zone')
            $this->scoring = new GNAS_FiveZoneScoring();
        else
            $this->scoring = new GNAS_FiveMaxScoring();
        $this->venue = $venue;
        $this->measure = $measure == 'imperial'
                         ? new GNAS_ImperialMeasure()
                         : new GNAS_MetricMeasure();
    }

    public static function getInstance($name) {
        if (!array_key_exists($name, self::$instances)) {
            $family = array();
            $rows = GNAS_PDO::SELECT('*'
                                    . ' FROM round_family'
                                    . ' WHERE name = ?',
                                    array($name));
            $family = $rows[0];
            if (isset($family['name']))
                self::$instances[$name] = new self($family['name'],
                                                   $family['scoring'],
                                                   $family['venue'],
                                                   $family['measure']);
            else
                self::$instances[$name] =
                    new GNAS_UnrecognisedRoundFamily($name);
        }
        return self::$instances[$name];
    }

}

###############################
interface GNAS_RoundInterface {
    public function getTableRow();
    public function asText();
}

############################
class GNAS_UnrecognisedRound
    extends GNAS_Unrecognised
    implements GNAS_RoundInterface {

    public function getTypeName() {
        return 'round';
    }

    public function getTableRow() {
        return '';
    }

}

#################################################
class GNAS_Round implements GNAS_RoundInterface {

    private $name;
    private $printName;
    private $familyName;
    private $display_order;
    private $distances;
    private $classifications;
    private $searchTerm;

    private static $instances = array();

    public function asText() {
        return $this->getTitle()
             . $this->getDescription()
             . $this->getJavaScript()
             . $this->getClassifications()->getTable();
    }

    public function getTitle() {
        return '<h1>' . $this->printName . '</h1>';
    }

    public function getName() {
        return $this->printName;
    }

    private function getDescription() {
        return $this->getDistances()
                    ->getDescription($this->getFamily()->getScoring(),
                                     $this->getFamily()->getMeasure());
    }

    private function getJavaScript() {
        $js = $this->getDistances()
                   ->getJavaScript($this->getFamily()->getMeasure());
        $js .= <<<EOJS
<h3>Beat Your Handicap</h3>
<p>Enter your current handicap:
<input type="number" name="handicap" id="handicap" min="0" max="100" value="100"/>.</p>
<p>Your predicted score for that handicap is: <span id="prediction">0</span>.</p>
EOJS;
        return $js;
    }

    private function getClassifications() {
        if (!isset($this->classifications)) {
            $this->classifications = new GNAS_Classifications($this->name);
        }
        return $this->classifications;
    }

    public function getTableHeader() {
        return $this->getFamily()->getTableHeader();
    }

    public function getTableRow() {
        return '<tr><td>'
             . $this->getLink()
             . '</td>'
             . $this->getFamily()
                    ->getMeasure()
                    ->makeTableDistances($this->getDistances())
             . '</tr>';
    }

    private function getLink() {
        return '<a href="'
             . GNAS_PageURL::make(array('round' => $this->searchTerm))
             . '">'
             . $this->name
             . '</a>';
    }

    public function getDistances() {
        if (!isset($this->distances)) {
            $this->distances =
                GNAS_Distances::create($this->name,
                                       $this->getFamily()->getArrowCounts(),
                                       $this->getFamily()->getMeasure());
        }
        return $this->distances;
    }

    private function getFamily() {
        return GNAS_RoundFamily::getInstance($this->familyName);
    }

    public function getMeasure() {
        return $this->getFamily()->getMeasure();
    }

    private function __construct($name, $familyName, $display_order) {
        $this->name = $name;
        $this->printName = $name;
        $this->familyName = $familyName;
        $this->display_order = $display_order;
        $this->searchTerm = implode('+', explode(' ', $name));
    }

    public static function getInstanceFromRow(array $row) {
        if (!array_key_exists($row['name'], self::$instances)) {
            self::$instances[$row['name']] = new self($row['name'],
                                                      $row['family_name'],
                                                      $row['display_order']);
        }
        return self::$instances[$row['name']];
    }

    public static function getInstanceByName($name) {
        if (!array_key_exists($name, self::$instances)) {
            $family = array();
            $rows = GNAS_PDO::SELECT('*'
                                    . ' FROM round'
                                    . ' WHERE name = ?',
                                    array($name));
            $row = $rows[0];
            if (isset($row['name']))
                return self::getInstanceFromRow($row);
            else
                self::$instances[$name] = new GNAS_UnrecognisedRound($name);
        }
        return self::$instances[$name];
    }

}

#########################
class GNAS_Requirements {

    public function __construct() {
    }

    public function finder() {
        $ret = $this->form();
        if (   $_GET['classification']
            && $_GET['age_group']
            && $_GET['bow']
            && $_GET['gender']) {
            $ret .= $this->results();
        }
        return $ret;
    }

    private function form() {
        $result = "<form method='get' action=''>\n";
        if ($_GET['page_id']) {
            $result .= "<input type='hidden' name='page_id' value='$_GET[page_id]'/>\n";
        }
        $result .= "<table>\n";
        $result .= $this->option('Desired Classification', 'classification',
            array(
            'third' => 'Third Class',
            'second' => 'Second Class',
            'first' => 'First Class',
            'bm' => 'Bowman',
            'mbm' => 'Master Bowman',
            'gmbm' => 'Grand Master Bowman'));
        $result .= $this->option('Age Group', 'age_group', array(
            'adult' => 'Adult',
            'U18' => 'Under Eighteen',
            'U16' => 'Under Sixteen',
            'U14' => 'Under Fourteen',
            'U12' => 'Under Twelve'));
        $result .= $this->option('Bow', 'bow', array(
            'recurve' => 'Recurve',
            'compound' => 'Compound',
            'longbow' => 'Longbow',
            'barebow' => 'Barebow'));
        $result .= $this->option('Gender', 'gender', array(
            'M' => 'Gent',
            'F' => 'Lady'));
        $result .= "<tr><th>&nbsp;</th>
<td><input type='submit' value='Search'/></td></tr>
</table>
</form>
";
    return $result;
    }

    private function option($title, $id, $options) {
        $result = "<tr><th>$title</th><td><select name='$id' id='$id'>\n";
        foreach ($options as $value => $label) {
            $result .= "<option value='$value'";
            if ($_GET[$id] == $value) {
                $result .= " selected='selected'";
            }
            $result .= ">$label</option>\n";
        }
        $result .= "</select></td></tr>\n";
        return $result;
    }

    private function results() {
        $result = '';
        $class = $_GET['classification'];
        if (   $class != 'third'
            && $class != 'second'
            && $class != 'first'
            && $class != 'bm'
            && $class != 'mbm'
            && $class != 'gmbm') {
            return "<p>Please don't hack me!</p>\n";
        }

        $rows = GNAS_PDO::SELECT("round, $class as score"
                               . " from outdoor_classifications"
                               . " where $class > 0"
                               . " and age_group = ?"
                               . " and bow = ?"
                               . " and gender = ?"
                               . " order by round",
                               array($_GET['age_group'],
                                     $_GET['bow'],
                                     $_GET['gender']));
        $result = "<table><thead><tr><th>Round</th><th>Score</th></tr></thead>\n";
        $result .= "<tbody>\n";
        foreach ($rows as $row) {
            $round = $row['round'];
            if ($round == 'National') {
                $round = ':National';
            }
            $result .= "<tr><td>$round</td><td>$row[score]</td></tr>\n";
        }
        $result .= "<tbody></table>\n";
        return $result;
    }
}

############################
class GNAS_Classifications {

    private $roundName;
    private $gender_map;

    public function __construct($roundName) {
        $this->roundName = $roundName;
        $this->gender_map = array('M' => 'Gents', 'F' => 'Ladies');
    }

    function getTable() {

        $subtitle = array();
        $headers = array();
        $fields = array();
        $conditions = array('round = ?');
        $parameters = array($this->roundName);
        $num_params = 0;

        if (array_key_exists('gender', $_GET)) {
            $subtitle []= $this->gender_map[$_GET['gender']];
            $conditions []= 'gender = ?';
            $parameters []= $_GET['gender'];
            ++$num_params;
        }
        else {
            $headers []= 'M/F';
            $fields []= 'gender';
        }

        if (array_key_exists('age_group', $_GET)) {
            $subtitle []= $_GET['age_group'];
            $conditions []= 'outdoor_classifications.age_group = ?';
            $parameters []= $_GET['age_group'];
            ++$num_params;
        }
        else {
            $headers []= 'Age';
            $fields []= 'outdoor_classifications.age_group AS age_group';
        }

        if (array_key_exists('bow', $_GET)) {
            $subtitle []= $_GET['bow'];
            $conditions []= 'bow = ?';
            $parameters []= $_GET['bow'];
            ++$num_params;
        }
        else {
            $headers []= 'Bow';
            $fields []= 'bow';
        }

        $table = array();

        $subtitle []= 'required scores for classifications';

        $table []= '<h3>' . ucfirst(implode(' ', $subtitle)) . '</h3>';
        if ($num_params < 3) {
            $table []= '<p>Click on a highlighted value in the table'
                       . ' below to exclude other values and refine'
                       . ' your view.</p>';
        }

        $headers []= '3rd';
        $headers []= '2nd';
        $headers []= '1st';
        $headers []= 'BM';
        $headers []= 'MBM';
        $headers []= 'GMBM';

        $fields []= 'third';
        $fields []= 'second';
        $fields []= 'first';
        $fields []= 'bm';
        $fields []= 'mbm';
        $fields []= 'gmbm';

        $query = implode(', ', $fields)
                . ' FROM outdoor_classifications'
                . ' JOIN age_groups'
                . ' ON outdoor_classifications.age_group'
                . ' = age_groups.age_group'
                . ' WHERE '
                . implode(' AND ', $conditions)
                . ' ORDER BY gender, age_groups.display_order, bow';

        $table []= '<table><thead><tr><th>'
                   . implode('</th><th>', $headers)
                   . '</th></tr></thead><tbody>';

        $rows = GNAS_PDO::SELECT($query, $parameters);

        $seen = false;
        foreach ($rows as $row) {
            $seen = true;
            $table []= '<tr>';
            foreach ($fields as $key) {
                $column = $this->make_column($row,
                                             array_pop(explode(' ', $key)));
                $table []= '<td>' . $column . '</td>';
            }
            $table []= '</tr>';
        }

        if (!$seen) {
            return '';
        }

        $table []= '</tbody></table>';
        return implode($table);
    }

    private function make_column($row, $key) {
        $params = array();
        $wrap = false;
        foreach(array('round', 'gender', 'age_group', 'bow') as $valid) {
            if ($key == $valid) {
                $params [$valid] = urlencode($row[$key]);
                $wrap = true;
            }
            else if (array_key_exists($valid, $_GET)) {
                $params [$valid] = urlencode($_GET[$valid]);
            }
        }
        if ($wrap) {
            return ('<a href="'
                    . GNAS_PageURL::make($params)
                    . '">'
                    . $row[$key]
                    . '</a>');
        }
        else {
            return ($row[$key]);
        }
    }

}

###############################
abstract class GNAS_AllRounds {

    public static function asText() {
        $text = '';
        $rounds = new GNAS_ImperialRounds();
        $text .= $rounds->roundsAsText();
        $rounds = new GNAS_MetricRounds();
        $text .= $rounds->roundsAsText();
        return $text;
    }

    public static function asData() {
        $rounds = new GNAS_ImperialRounds();
        $data = $rounds->getAllRounds();
        $rounds = new GNAS_MetricRounds();
        $data = array_merge($data, $rounds->getAllRounds());
        return $data;
    }

    public abstract function getTitle();
    public abstract function getMeasure();

    public function roundsAsText() {
        return $this->getTitle()
             . $this->getTable();
    }

    public function getTable() {
        $rounds = $this->getAllRounds();
        $table = array('<table>');
        $table []= $rounds[0]->getTableHeader();
        $table []= '<tbody>';
        foreach ($rounds as $round) {
            $table []= $round->getTableRow();
        }
        $table []= '</tbody></table>';
        return implode($table);
    }

    private function getAllRounds() {
        $query = 'round.* FROM round, round_family'
               . ' WHERE round.family_name = round_family.name'
               . ' AND round_family.measure = ?'
               . ' AND round_family.venue = ?'
               . ' ORDER BY round.family_name, round.display_order';
        $params = array($this->getMeasure(), 'outdoor');
        $rows = GNAS_PDO::SELECT($query, $params);
        $rounds = array();
        foreach ($rows as $row) {
            $rounds []= GNAS_Round::getInstanceFromRow($row);
        }
        return $rounds;

    }

}

##################################################
class GNAS_ImperialRounds extends GNAS_AllRounds {

    public function getTitle() {
        return '<h1>Imperial Rounds, Five Zone Scoring</h1>';
    }

    public function getMeasure() {
        return 'imperial';
    }

}

################################################
class GNAS_MetricRounds extends GNAS_AllRounds {

    public function getTitle() {
        return '<h1>Metric Rounds, Ten Zone Scoring</h1>';
    }

    public function getMeasure() {
        return 'metric';
    }

}

#########################
/**
 * Renders the admin menus.
 * Reproduces as closely as possible a representation of the given GNAS table.
 */
class GNAS_OutdoorTable {

    protected $table_number;
    private $agb_outdoor_table;
    private $agb_outdoor_table_columns;
    private $classifications;
    private $thead;
    private $submit_key = 'gnas-submit';
    private $table_number_key = 'gnas-table-number';
    private $value_prefix = 'gnas-value';
    private $collected_posts;

    private static $STANDARDS =
        array('gmbm', 'mbm', 'bm', 'first', 'second', 'third');

    public function __construct($table_number) {
        // print "<p>GNAS_OutdoorTable::new</p>";
        $this->table_number = $table_number;
    }

    public function handlePOST() {
        // print "<p>GNAS_OutdoorTable::handlePOST</p>";
        if ($_POST[$this->submit_key] == 'true') {
            $this->do_handlePOST();
        }
    }

    public function asText() {
        // print "<p>GNAS_OutdoorTable::asText</p>";
        return $this->getTitle() . $this->getTables();
    }

    protected function do_handlePOST() {
        // print "<p>GNAS_OutdoorTable::do_handlePOST</p>";
        $this->collected_posts = array();
        foreach ($_POST as $name => $score) {
            $exploded_post_name = explode('_', $name);
            if ($exploded_post_name[0] != $this->value_prefix) {
                continue;
            }
            array_shift($exploded_post_name);
            list($bow, $gender, $age_group, $standard) =
                array_splice($exploded_post_name, 0, 4);
            $valid = false;
            foreach (self::$STANDARDS as $valid_standard) {
                if ($standard == $valid_standard) {
                    $valid = true;
                    break;
                }
            }
            if (!$valid) {
                continue;
            }
            $round = implode(' ', $exploded_post_name);
            $this->collectFromPOST($standard,
                                   $bow,
                                   $gender,
                                   $age_group,
                                   $round,
                                   $score);
        }
        $this->updateFromPOSTs();
    }

    private function collectFromPOST($standard,
                                     $bow,
                                     $gender,
                                     $age_group,
                                     $round,
                                     $score) {
        // print "<p>GNAS_OutdoorTable::collectFromPOST</p>";
        $key = "$gender $age_group $bow $round";
        if (!isset($this->collected_posts[$key])) {
            $this->collected_posts[$key] = array(
                'gender' => $gender,
                'age_group' => $age_group,
                'bow' => $bow,
                'round' => $round,
            );
            foreach (self::$STANDARDS as $std) {
                $this->collected_posts[$key][$std] = NULL;
            }
        }
        if (ctype_digit($score)) {
            $this->collected_posts[$key][$standard] = $score;
        }
    }

    private function updateFromPOSTs() {
        // print "<p>GNAS_OutdoorTable::updateFromPOSTs</p>";
        $query = 'INSERT OR REPLACE INTO outdoor_classifications'
               . ' (gmbm, mbm, bm, first, second, third,'
               . ' round, bow, gender, age_group) VALUES'
               . ' (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = GNAS_PDO::get()->prepare($query);
        foreach ($this->collected_posts as $collected_post) {
            $arguments = array(
                $collected_post['gmbm'],
                $collected_post['mbm'],
                $collected_post['bm'],
                $collected_post['first'],
                $collected_post['second'],
                $collected_post['third'],
                $collected_post['round'],
                $collected_post['bow'],
                $collected_post['gender'],
                $collected_post['age_group']
            );
            $stmt->execute($arguments);
        }
    }

    protected function getTHead() {
        // print "<p>GNAS_OutdoorTable::getTHead</p>";
        if (!isset($this->thead)) {
            $parts = array();
            $parts []='<thead><tr><th colspan="2">&nbsp;</th>';
            foreach ($this->getDBHeaderTable() as $header) {
                $th_class = '';
                if ($header['edge']) {
                    $th_class = ' class="gnas-edge"';
                }
                $parts []= "<th$th_class>"
                    . implode('<br/>', explode(' ', $header['round']))
                    . '</th>';
            }
            $parts []='</tr></thead>';
            $this->thead = implode($parts);
        }
        return $this->thead;
    }

    protected function getTables() {
        // print "<p>GNAS_OutdoorTable::getTables</p>";
        $table = $this->getDBTable();
        $bow = $table['bow'];
        $tables = array();
        foreach (GNAS_Genders::get() as $gender) {
            foreach (GNAS_AgeGroups::get() as $age_group) {
                $tables []=
                    $this->getSingleTableForm($bow, $gender, $age_group);
            }
        }
        return implode($tables);
    }

    private function getSingleTableForm($bow, $gender, $age_group) {
        // print "<p>GNAS_OutdoorTable::getSingleTableForm</p>";
        $parts = array();
        $parts []= '<form method="POST" action=""><table>';
        $parts []= $this->getTHead();
        $parts []= $this->getTBody($bow, $gender, $age_group);
        $parts []= '</table>'
                 . '<input type="hidden" name="'
                 . $this->submit_key
                 . '" value="true"/>'
                 . '<input type="hidden" name="'
                 . $this->table_number_key
                 . '" value="'
                 . $this->table_number . '"/>'
                 . '<input type="submit" value="Update"/>'
                 . '</form>';
        return implode($parts);
    }

    private function getTBody($bow, $gender, $age_group) {
        // print "<p>GNAS_OutdoorTable::getTBody</p>";
        $parts = array();
        $parts []= '<tbody>';
        $body = $this->getDBBodyTable();
        $key = $this->niceName($gender, $age_group);
        $parts []= '<tr><td rowspan="6">' . $key . '</td>';
        foreach (self::$STANDARDS as $standard) {
            if ($standard != 'gmbm') {
                if ($standard == 'third') {
                    $parts []= '<tr class="gnas-edge">';
                }
                else {
                    $parts []= '<tr>';
                }
            }
            $parts []= '<td class="gnas-edge">' . $standard . '</td>';
            foreach ($this->getDBHeaderTable() as $header) {
                $td_class = '';
                if ($header['edge']) {
                    $td_class = ' class="gnas-edge"';
                }
                $parts []= "<td$td_class>"
                           . $this->makeEditBox($body,
                                                $bow,
                                                $gender,
                                                $age_group,
                                                $standard,
                                                $header['round'])
                           . '</td>';
            }
            $parts []= '</tr>';
        }
        $parts []='</tbody>';
        return implode($parts);
    }

    private function niceName($gender, $age_group) {
        // print "<p>GNAS_OutdoorTable::niceName</p>";
        $parts = array();
        if ($gender == 'F') {
            $parts []= 'Ladies';
        }
        else {
            $parts []= 'Gents';
        }
        if ($age_group != 'adult') {
            array_unshift($parts, 'Junior');
            $age_group = preg_replace('/^U/', '', $age_group);
            $parts []= 'under';
            $parts []= $age_group;
        }
        return implode('<br/>', $parts);
    }

    private function makeEditBox($body,
                                 $bow,
                                 $gender,
                                 $age_group,
                                 $standard,
                                 $round) {
        // print "<p>GNAS_OutdoorTable::makeEditBox</p>";
        $value = $body["$gender $age_group"][$standard][$round];
        return '<input type="text" name="'
            . implode('_',
                      array($this->value_prefix,
                            $bow,
                            $gender,
                            $age_group,
                            $standard,
                            implode('_', explode(' ', $round))))
            . '" size="4" value="' . $value . '"/>';
    }

    private function getDBBodyTable() {
        // print "<p>GNAS_OutdoorTable::getDBBodyTable</p>";
        if (!isset($this->classifications)) {
            $this->classifications = array();
            $placeholders = array();
            $arguments = array();
            $table = $this->getDBTable();
            foreach ($this->getDBHeaderTable() as $header) {
                $placeholders []= '?';
                $arguments []= $header['round'];
            }
            $query = '*'
                   . ' FROM outdoor_classifications'
                   . ' WHERE round IN ('
                   . implode(',', $placeholders)
                   . ') and bow = ?';
            $arguments []= $table['bow'];
            $rows = GNAS_PDO::SELECT($query, $arguments);
            foreach ($rows as $row) {
                $key = $row['gender'] . ' ' . $row['age_group'];
                $round = $row['round'];
                if (!isset($this->classifications[$key])) {
                    $this->classifications[$key] = array();
                }
                foreach (self::$STANDARDS as $standard) {
                    if (!isset($this->classifications[$key][$standard])) {
                        $this->classifications[$key][$standard] = array();
                    }
                    $this->classifications[$key][$standard][$round] =
                        $row[$standard];
                }
            }
        }
        return $this->classifications;
    }

    protected function getTitle() {
        // print "<p>GNAS_OutdoorTable::getTitle</p>";
        $table = $this->getDBTable();
        return '<h1>'
               . 'Table '
               . $this->table_number
               . ' - '
               . $table['title']
               . '</h1>';
    }

    protected function getDBTable() {
        // print "<p>GNAS_OutdoorTable::getDBTable</p>";
        if (!isset($this->agb_outdoor_table)) {
            $this->agb_outdoor_table = array();
            $rows = GNAS_PDO::SELECT('*'
                                    . ' FROM agb_outdoor_table'
                                    . ' WHERE table_number = ?',
                                    array($this->table_number));
            $this->agb_outdoor_table = $rows[0];
        }
        return $this->agb_outdoor_table;
    }

    protected function getDBHeaderTable() {
        // print "<p>GNAS_OutdoorTable::getDBHeaderTable</p>";
        if (!isset($this->agb_outdoor_table_columns)) {
            $table = $this->getDBTable();
            $this->agb_outdoor_table_columns = GNAS_PDO::SELECT('*'
                . ' FROM agb_outdoor_table_column'
                . ' WHERE header_number = ? ORDER BY column_number',
                array($table['header_number']));
        }
        return $this->agb_outdoor_table_columns;
    }

    public function tableCSS() {
        // print "<p>GNAS_OutdoorTable::tableCSS</p>";
        return <<<EOCSS
<style type="text/css">
td.gnas-edge, th.gnas-edge {
    border-right: 2px solid black;
}
tr.gnas-edge td {
    border-bottom: 2px solid black;
}
</style>

EOCSS;
    }

}

class GNAS_IndoorTable extends GNAS_OutdoorTable {

    private $agb_outdoor_table_columns;
    private $classifications;
    private $thead;
    private $submit_key = 'gnas-submit';
    private $table_number_key = 'gnas-table-number';
    private $value_prefix = 'gnas-value';
    private $collected_posts;

    private static $STANDARDS =
        array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');


    protected function do_handlePOST() {
        // print "<p>GNAS_IndoorTable::do_handlePOST</p>";
        $this->collected_posts = array();
        foreach ($_POST as $name => $score) {
        // print "<p>$name =&gt; $score</p>";
            $exploded_post_name = explode('_', $name);
            if ($exploded_post_name[0] != $this->value_prefix) {
                continue;
            }
            array_shift($exploded_post_name);
            list($bow, $gender, $standard, $triple) =
                array_splice($exploded_post_name, 0, 4);
            $valid = false;
            foreach (self::$STANDARDS as $valid_standard) {
                if ($standard == $valid_standard) {
                    $valid = true;
                    break;
                }
            }
            if (!$valid) {
                continue;
            }
            $round = implode(' ', $exploded_post_name);
            $this->collectFromPOST($standard,
                                   $bow,
                                   $gender,
                                   $triple,
                                   $round,
                                   $score);
        }
        $this->updateFromPOSTs();
    }

    private function collectFromPOST($standard,
                                     $bow,
                                     $gender,
                                     $triple,
                                     $round,
                                     $score) {
        $key = "$gender $triple $bow $round";
        // print "<p>GNAS_IndoorTable::collectFromPOST $key</p>";
        if (!isset($this->collected_posts[$key])) {
            $this->collected_posts[$key] = array(
                'gender' => $gender,
                'triple' => $triple,
                'bow' => $bow,
                'round' => $round,
            );
            foreach (self::$STANDARDS as $std) {
                $this->collected_posts[$key][$std] = NULL;
            }
        }
        if (ctype_digit($score)) {
            $this->collected_posts[$key][$standard] = $score;
        }
        // print "<pre>\n";
        // print_r($this->collected_posts);
        // print "</pre>\n";
    }

    private function updateFromPOSTs() {
        // print "<p>GNAS_IndoorTable::updateFromPOSTs</p>";
        $query = 'INSERT OR REPLACE INTO indoor_classifications'
               . ' (A, B, C, D, E, F, G, H,'
               . ' round, bow, gender, triple) VALUES'
               . ' (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = GNAS_PDO::get()->prepare($query);
        foreach ($this->collected_posts as $collected_post) {
            $arguments = array(
                $collected_post['A'],
                $collected_post['B'],
                $collected_post['C'],
                $collected_post['D'],
                $collected_post['E'],
                $collected_post['F'],
                $collected_post['G'],
                $collected_post['H'],
                $collected_post['round'],
                $collected_post['bow'],
                $collected_post['gender'],
                $collected_post['triple']
            );
            // print "<pre>\n";
            // print "$query\n";
            // print_r($arguments);
            $stmt->execute($arguments);
            // print_r($stmt->errorInfo());
            // print "</pre>\n";
        }
    }

    protected function getTables() {
        // print "<p>GNAS_IndoorTable::getTables</p>";
        $table = $this->getDBTable();
        $bow = $table['bow'];
        $tables = array();
        foreach (GNAS_Genders::get() as $gender) {
            $tables []= $this->getSingleTableForm($bow, $gender);
        }
        return implode($tables);
    }

    private function getSingleTableForm($bow, $gender) {
        // print "<p>GNAS_IndoorTable::getSingleTableForm</p>";
        $parts = array();
        $parts []= '<form method="POST" action=""><table>';
        $parts []= $this->getTHead();
        $parts []= $this->getTBody($bow, $gender);
        $parts []= '</table>'
                 . '<input type="hidden" name="'
                 . $this->submit_key
                 . '" value="true"/>'
                 . '<input type="hidden" name="'
                 . $this->table_number_key
                 . '" value="'
                 . $this->table_number . '"/>'
                 . '<input type="submit" value="Update"/>'
                 . '</form>';
        return implode($parts);
    }

    private function getTBody($bow, $gender) {
        // print "<p>GNAS_IndoorTable::getTBody</p>";
        $parts = array();
        $parts []= '<tbody>';
        $body = $this->getDBBodyTable();
        $key = $this->niceName($gender);
        $parts []= '<tr><td rowspan="9">' . $key . '</td>';
        foreach (self::$STANDARDS as $standard) {
            if ($standard == 'H') {
                $parts []= '<tr class="gnas-edge">';
            }
            else {
                $parts []= '<tr>';
            }
            $parts []= '<td class="gnas-edge">' . $standard . '</td>';
            foreach ($this->getDBHeaderTable() as $header) {
                $td_class = '';
                if ($header['edge']) {
                    $td_class = ' class="gnas-edge"';
                }
                $parts []= "<td$td_class>"
                           . $this->makeEditBox($body,
                                                $bow,
                                                $gender,
                                                $standard,
                                                $header['triple'],
                                                $header['round'])
                           . '</td>';
            }
            $parts []= '</tr>';
        }
        $parts []='</tbody>';
        return implode($parts);
    }

    private function niceName($gender) {
        // print "<p>GNAS_IndoorTable::niceName</p>";
        if ($gender == 'F') {
            return implode('<br/>', explode(' ', 'Ladies Senior and Junior'));
        }
        else {
            return implode('<br/>', explode(' ', 'Gents Senior and Junior'));
        }
    }

    private function makeEditBox($body,
                                 $bow,
                                 $gender,
                                 $standard,
                                 $triple,
                                 $round) {
        // print "<p>GNAS_IndoorTable::makeEditBox</p>";
        $value = $body["$gender $triple"][$standard][$round];
        return '<input type="text" name="'
            . implode('_',
                      array($this->value_prefix,
                            $bow,
                            $gender,
                            $standard,
                            $triple,
                            implode('_', explode(' ', $round))))
            . '" size="4" value="' . $value . '"/>';
    }

    private function getDBBodyTable() {
        // print "<p>GNAS_IndoorTable::getDBBodyTable</p>";
        if (!isset($this->classifications)) {
            $this->classifications = array();
            $placeholders = array();
            $arguments = array();
            $table = $this->getDBTable();
            foreach ($this->getDBHeaderTable() as $header) {
                $placeholders []= '?';
                $arguments []= $header['round'];
            }
            $query = '*'
                   . ' FROM indoor_classifications'
                   . ' WHERE round IN ('
                   . implode(',', $placeholders)
                   . ') and bow = ?';
            $arguments []= $table['bow'];
            $rows = GNAS_PDO::SELECT($query, $arguments);
            foreach ($rows as $row) {
                $key = $row['gender'] . ' ' . $row['triple'];
                $round = $row['round'];
                if (!isset($this->classifications[$key])) {
                    $this->classifications[$key] = array();
                }
                foreach (self::$STANDARDS as $standard) {
                    if (!isset($this->classifications[$key][$standard])) {
                        $this->classifications[$key][$standard] = array();
                    }
                    $this->classifications[$key][$standard][$round] =
                        $row[$standard];
                }
            }
        }
        // print("<pre>\n");
        // print_r($this->classifications);
        // print("</pre>\n");
        return $this->classifications;
    }

}

#################
/*
 * Entry Point.
 */
class GNAS_Page {

    /**
     * disallow creation of instances.
     */
    private function __construct() {
    }

    /**
     * used by the [rounds] shortcode.
     */
    public static function asText() {
        if (array_key_exists('round', $_GET)) {
            return GNAS_Round::getInstanceByName($_GET['round'])->asText();
        } else if (array_key_exists('round_group', $_GET)) {
            return
                GNAS_RoundFamily::getInstance($_GET['round_group'])->asText();
        } else {
            return GNAS_AllRounds::asText();
        }
    }

    /**
     * used by the admin menus.
     */
    public static function outdoorTable($table_number) {
        $table = new GNAS_OutdoorTable($table_number);
        $table->handlePOST();
        return $table->tableCSS() . $table->asText();
    }
    public static function indoorTable($table_number) {
        $table = new GNAS_IndoorTable($table_number);
        $table->handlePOST();
        return $table->tableCSS() . $table->asText();
    }

    /**
     * used by other plugins
     */
    public static function roundData() {
        return GNAS_AllRounds::asData();
    }

    public static function roundFinder() {
        $requirements = new GNAS_Requirements();
        return $requirements->finder();
    }
}
