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
        return '<thead><tr><th rowspan="3" style="vertical-align: bottom;">Round</th><th colspan="'
            . $this->totalDistances()
            . '">Dozens at each distance</th></tr>'
            . '<tr>' . $this->getFaceHeaders() . '</tr>'
            . '<tr>'
            . $this->getDistanceHeaders()
            . '</tr></thead>';
    }

    public function getTableFooter() {
        return '<tfoot>'
            . '<tr><td rowspan="2">Round</td>'
            . $this->getDistanceHeaders('td')
            . '</tr>'
            . '<tr>' . $this->getFaceHeaders('td') . '</tr>'
            . '</tfoot>';
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

    private function getFaceHeaders($type='th') {
        $result = '';
        foreach($this->getAllDistances() as $face => $distances) {
            $result .= "<$type colspan='"
                    . count($distances)
                    . "'>"
                    . $face
                    . "</$type>";
        }
        return $result;
    }

    private function getDistanceHeaders($type='th') {
        $units = $this->getUnits();
        $headers = array();
        foreach ($this->getAllDistances() as $distances) {
            foreach ($distances as $distance) {
                $headers []= $distance . $units;
            }
        }
        return "<$type>" . implode("</$type><$type>", $headers) . "</$type>";
    }

}

#########################################################
abstract class GNAS_ImperialMeasure extends GNAS_Measure {

    public function getUnits() {
        return 'y';
    }

    public function getName() {
        return 'imperial';
    }

}

################################################################
class GNAS_OutdoorImperialMeasure extends GNAS_ImperialMeasure {

    public function getAllDistances() {
        return array('122cm' => array(100, 80, 60, 50, 40, 30, 20, 10));
    }

}

###############################################################
class GNAS_IndoorImperialMeasure extends GNAS_ImperialMeasure {

    public function getAllDistances() {
        return array('80cm' => array(30),
                     '60cm' => array(25, 20),
                     '40cm' => array(25, 20),
                     '16in special' => array(20));
    }

}

########################################################
abstract class GNAS_MetricMeasure extends GNAS_Measure {

    public function getUnits() {
        return 'm';
    }

    public function getName() {
        return 'metric';
    }

}

############################################################
class GNAS_OutdoorMetricMeasure extends GNAS_MetricMeasure {

    public function getAllDistances() {
        return array('122cm' => array(90, 70, 60, 50, 40, 30, 20),
                     '80cm'  => array(50, 40, 30, 20, 15, 10));
    }

}

###########################################################
class GNAS_IndoorMetricMeasure extends GNAS_MetricMeasure {

    public function getAllDistances() {
        return array('80cm' => array(30),
                     '60cm' => array(25, 18),
                     '40cm' => array(18));
    }

}

#############################
abstract class GNAS_Scoring {
    abstract public function getName();
    abstract public function getMultiplier();

    public function maxScore(GNAS_ArrowCounts $arrowCounts) {
        return $this->getMultiplier() * $arrowCounts->getTotalArrows();
    }

    public function isPresent() {
        return true;
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

#######################################################
class GNAS_MetricInnerTenScoring extends GNAS_Scoring {
    public function getName() { return 'metric inner ten'; }
    public function getMultiplier() { return 10; }
}

#######################################################
class GNAS_VegasScoring extends GNAS_Scoring {
    public function getName() { return 'vegas'; }
    public function getMultiplier() { return 10; }
}

#######################################################
class GNAS_VegasInnerTenScoring extends GNAS_Scoring {
    public function getName() { return 'vegas inner ten'; }
    public function getMultiplier() { return 10; }
}

################################################
class GNAS_WorcesterScoring extends GNAS_Scoring {
    public function getName() { return 'worcester'; }
    public function getMultiplier() { return 5; }
}

################################################
class GNAS_FITASixZoneScoring extends GNAS_Scoring {
    public function getName() { return 'fita six zone'; }
    public function getMultiplier() { return 10; }
}

################################################
class GNAS_NoScoring extends GNAS_Scoring {
    public function getName() { return 'none'; }
    public function getMultiplier() { return 0; }
    public function isPresent() { return false; }
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
        $rows = GNAS_PDO::SELECT('arrow_count.*, faces.diameter'
            . ' FROM arrow_count, faces'
            . ' WHERE family_name = ?'
            . ' AND arrow_count.face = faces.face'
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

    public function getJSON() {
        return '{'
             . '"N": ' . $this->getNumArrows()
             . ', "D": ' . $this->getDiameter()
             . ', "R": ' . $this->getDistance()
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

    public function getJSON() {
        $distances_js = array();
        foreach ($this->distances as $face => $faceDistances) {
            foreach ($faceDistances as $singleDistance) {
                $distances_js []= $singleDistance->getJSON();
            }
        }
        return '[' .implode(', ', $distances_js) . ']';
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
    private $compound_scoring;
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
        return '<table class="rounds">'
               . $this->getTableHeader()
               . $this->getTableFooter()
               . $this->getTableBody()
               . '</table>';
    }

    public function getTableHeader() {
        return $this->measure->getTableHeader();
    }

    public function getTableFooter() {
        return $this->measure->getTableFooter();
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

    public function getVenue() {
        return $this->venue;
    }

    public function getScoring() {
        return $this->scoring;
    }

    public function getCompoundScoring() {
        return $this->compound_scoring;
    }

    public function getScoringByBow($bow) {
        if ($bow == 'compound') {
            return $this->getCompoundScoring();
        } else {
            return $this->getScoring();
        }
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

    private function __construct($name, $venue, $measure, $scoring, $compound_scoring) {
        $this->name = $name;
        $this->scoring = self::calcScoring($scoring);
        $this->compound_scoring = self::calcScoring($compound_scoring);
        $this->venue = $venue;
        $this->measure = $measure == 'imperial'
                       ? ($venue == 'outdoor'
                                   ? new GNAS_OutdoorImperialMeasure()
                                   : new GNAS_IndoorImperialMeasure())
                       : ($venue == 'outdoor'
                                   ? new GNAS_OutdoorMetricMeasure()
                                   : new GNAS_IndoorMetricMeasure());
    }

    private static function calcScoring($scoring) {
        switch ($scoring) {
            case 'ten zone':
                return new GNAS_TenZoneScoring();
            case 'five zone':
                return new GNAS_FiveZoneScoring();
            case 'metric inner ten':
                return new GNAS_MetricInnerTenScoring();
            case 'vegas':
                return new GNAS_VegasScoring();
            case 'vegas inner ten':
                return new GNAS_VegasInnerTenScoring();
            case 'worcester':
                return new GNAS_WorcesterScoring();
            case 'fita six zone':
                return new GNAS_FITASixZoneScoring();
            default:
                wp_die('Error!: unrecognised scoring system: ' .$scoring);
        }
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
                                                   $family['venue'],
                                                   $family['measure'],
                                                   $family['scoring'],
                                                   $family['compound_scoring']);
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
             . $this->getHandicapText()
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
                                     $this->getMeasure());
    }

    private function getJavaScript() {
        $javascript = '<script>';
        $javascript .= 'rhac_scoring="' . $this->getScoringNameByBow('recurve') . '";';
        $javascript .= 'rhac_compound_scoring="' . $this->getScoringNameByBow('compound') . '";';
        $javascript .= 'rhac_distances=' . $this->getJSON() . ';';
        $javascript .= 'rhac_units="' . $this->getMeasureName() . '";';
        $javascript .= '</script>';
        return $javascript;
    }

    public function getJSON() {
        return $this->getDistances()->getJSON();
    }

    public function getMeasureName() {
        return $this->getFamily()->getMeasure()->getName();
    }

    public function getScoringName() {
        return $this->getFamily()->getScoring()->getName();
    }

    public function getScoringNameByBow($bow) {
        return $this->getFamily()->getScoringByBow($bow)->getName();
    }

    public function getVenue() {
        return $this->getFamily()->getVenue();
    }

    private function getHandicapText() {
        $name = $this->getName();
        $compound = '';
        if ($this->getVenue() == "indoor") {
            $compound = " <input type='checkbox' id='compound_scoring'>Compound</input>";
        }
        return <<<EOJS
<h3>Beat Your Handicap</h3>
<p>Enter your current handicap:
<input type="number" name="handicap" id="handicap" min="0" max="100" value="100"/>$compound</p>
<p>Your predicted score for a $name with a handicap of
<span id="handicap-copy">100</span> is <span id="prediction">0</span>.</p>
EOJS;
    }

    private function getClassifications() {
        if (!isset($this->classifications)) {
            if ($this->getVenue() == 'outdoor') {
                $this->classifications = new GNAS_Classifications($this->name);
            } else {
                $this->classifications = new GNAS_IndoorClassifications($this->name);
            }
        }
        return $this->classifications;
    }

    public function getTableHeader() {
        return $this->getFamily()->getTableHeader();
    }

    public function getTableFooter() {
        return $this->getFamily()->getTableFooter();
    }

    public function getTableRow() {
        return '<tr><td>'
             . $this->getLink()
             . '</td>'
             . $this->getFamily()
                    ->getMeasure()
                    ->makeTableDistances($this->getDistances())
             . '</tr>'
             . "\n";
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

    public function getScoring() {
        return $this->getFamily()->getScoring();
    }

    public function getCompoundScoring() {
        return $this->getFamily()->getCompoundScoring();
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
        if (   $_GET['outdoor_classification']
            && $_GET['indoor_classification']
            && $_GET['venue']
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
        $result .= $this->multiOption(
            'Desired Classification',
            array(
                array(
                    'radio_label' => 'Outdoor',
                    'radio_name' => 'venue',
                    'radio_value' => 'outdoor',
                    'select_name' => 'outdoor_classification',
                    'select_options' => array(
                        'third' => 'Third Class',
                        'second' => 'Second Class',
                        'first' => 'First Class',
                        'bm' => 'Bowman',
                        'mbm' => 'Master Bowman',
                        'gmbm' => 'Grand Master Bowman'
                    )
                ),
                array(
                    'radio_label' => 'Indoor',
                    'radio_name' => 'venue',
                    'radio_value' => 'indoor',
                    'select_name' => 'indoor_classification',
                    'select_options' => array(
                        'H' => 'H',
                        'G' => 'G',
                        'F' => 'F',
                        'E' => 'E',
                        'D' => 'D',
                        'C' => 'C',
                        'B' => 'B',
                        'A' => 'A'
                    )
                )
            )
        );
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
        $result .= $this->number('Handicap', 'handicap', 0, 100);
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

    private function multiOption($title, $components) {
        $first = true;
        $result = "<tr><th>$title</th><td><table>";
        foreach ($components as $component) {
            $result .= "<tr>";
            $result .= "<td><input type='radio' name='$component[radio_name]'";
            $result .= " value='$component[radio_value]'";
            if ($_GET[$component['radio_name']]) {
                if ($_GET[$component['radio_name']] == $component['radio_value']) {
                    $result .= " checked='checked'";
                }
            } elseif ($first) {
                $result .= " checked='checked'";
                $first = false;
            }
            $result .= ">$component[radio_label]</input></td>";

            $id = $component['select_name'];
            $result .= "<td><select name='$id' id='$id'>\n";
            foreach ($component['select_options'] as $value => $label) {
                $result .= "<option value='$value'";
                if ($_GET[$id] == $value) {
                    $result .= " selected='selected'";
                }
                $result .= ">$label</option>\n";
            }
            $result .= "</select></td></tr>\n";
        }
        $result .= '</table></td></tr>';
        return $result;
    }

    private function number($title, $id, $min, $max) {
        if (isset($_GET[$id])) {
            $value = $_GET[$id];
        } else {
            $value = $max;
        }
        $result = "<tr>";
        $result .= "<th>$title</th>";
        $result .= "<td><input type='number' name='$id' id='$id' min='$min max='$max' value='$value'/></td>";
        $result .= "</tr>";
        return $result;
    }

    private function results() {
        $result = '';
        if ($_GET['venue'] == 'outdoor') {
            return $this->outdoorResults();
        } else {
            return $this->indoorResults();
        }
    }

    private function outdoorResults() {
        $class = $_GET['outdoor_classification'];
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
        return $this->formatResults($rows);
    }

    private function indoorResults() {
        $class = $_GET['indoor_classification'];
        if (   $class != 'A'
            && $class != 'B'
            && $class != 'C'
            && $class != 'C'
            && $class != 'D'
            && $class != 'E'
            && $class != 'F'
            && $class != 'G'
            && $class != 'H') {
            return "<p>Please don't hack me!</p>\n";
        }
        $rows = GNAS_PDO::SELECT("round, $class as score"
                               . " from indoor_classifications"
                               . " where $class > 0"
                               . " and bow = ?"
                               . " and gender = ?"
                               . " order by round",
                               array($_GET['bow'],
                                     $_GET['gender']));
        return $this->formatResults($rows);
    }

    private function formatResults($rows) {
        $result = "<table id='predictions'><thead><tr>"
                    . "<th>Round</th>"
                    . "<th>Required Score</th>"
                    . "<th>Predicted Score</th>"
                    // . "<th>Debug</th>"
                    . "</tr></thead>\n";
        $result .= "<tbody>\n";
        foreach ($rows as $row) {
            $printname = $roundname = $row['round'];
            if ($printname == 'National') {
                $printname = ':National';
            }
            $round = GNAS_Round::getInstanceByName($roundname);
            $scoring = $round->getScoringNameByBow($_GET['bow']);
            $units = $round->getMeasureName();
            $json = $round->getJSON();
            $result .= "<tr data-scoring='$scoring' data-units='$units' data-distances='$json'>"
                    . "<td>$printname</td>"
                    . "<td class='score'>$row[score]</td>"
                    . "<td class='prediction'>0</td>"
                    // . "<td>$scoring <br/> $units <br/> $json</td>"
                    . "</tr>\n";
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

    public function getClassificationHeaders() {
        return array('3rd', '2nd', '1st', 'BM', 'MBM', 'GMBM');
    }

    public function getClassificationFields() {
        return array('third', 'second', 'first', 'bm', 'mbm', 'gmbm');
    }

    public function processAgeGroup(&$subtitle, &$conditions, &$parameters,
                                    &$headers, &$fields, &$num_params) {
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
    }

    public function generateQuery($fields, $conditions) {
        return implode(', ', $fields)
                . ' FROM outdoor_classifications'
                . ' JOIN age_groups'
                . ' ON outdoor_classifications.age_group'
                . ' = age_groups.age_group'
                . ' WHERE '
                . implode(' AND ', $conditions)
                . ' ORDER BY gender, age_groups.display_order, bow';
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

        $this->processAgeGroup($subtitle, $conditions, $parameters, $headers, $fields, $num_params);

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

        $headers = array_merge($headers, $this->getClassificationHeaders());
        $fields = array_merge($fields, $this->getClassificationFields());

        $query = $this->generateQuery($fields, $conditions);

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

###############################################################
class GNAS_IndoorClassifications extends GNAS_Classifications {

    public function getClassificationHeaders() {
        return array('H', 'G', 'F', 'E', 'D', 'C', 'B', 'A');
    }

    public function getClassificationFields() {
        return array('H', 'G', 'F', 'E', 'D', 'C', 'B', 'A');
    }

    public function processAgeGroup(&$subtitle, &$conditions, &$parameters,
                                    &$headers, &$fields, &$num_params) {
    }

    public function generateQuery($fields, $conditions) {
        return implode(', ', $fields)
                . ' FROM indoor_classifications'
                . ' WHERE '
                . implode(' AND ', $conditions)
                . ' ORDER BY gender, bow';
    }

}

###############################
abstract class GNAS_AllRounds {

    public static function asText() {
        $text = '';
        $rounds = new GNAS_OutdoorImperialRounds();
        $text .= $rounds->roundsAsText();
        $rounds = new GNAS_OutdoorMetricRounds();
        $text .= $rounds->roundsAsText();
        $rounds = new GNAS_IndoorImperialRounds();
        $text .= $rounds->roundsAsText();
        $rounds = new GNAS_IndoorMetricRounds();
        $text .= $rounds->roundsAsText();
        return $text;
    }

    public static function asData() {
        $rounds = new GNAS_OutdoorImperialRounds();
        $data = $rounds->getAllRounds();
        $rounds = new GNAS_OutdoorMetricRounds();
        $data = array_merge($data, $rounds->getAllRounds());
        $rounds = new GNAS_IndoorImperialRounds();
        $data = array_merge($data, $rounds->getAllRounds());
        $rounds = new GNAS_IndoorMetricRounds();
        $data = array_merge($data, $rounds->getAllRounds());
        return $data;
    }

    public abstract function getTitle();
    public abstract function getMeasure();
    public abstract function getVenue();

    public function roundsAsText() {
        return $this->getTitle()
             . $this->getTable();
    }

    public function getTable() {
        $rounds = $this->getAllRounds();
        $table = array('<table class="rounds">');
        $table []= $rounds[0]->getTableHeader();
        $table []= $rounds[0]->getTableFooter();
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
        $params = array($this->getMeasure(), $this->getVenue());
        $rows = GNAS_PDO::SELECT($query, $params);
        $rounds = array();
        foreach ($rows as $row) {
            $rounds []= GNAS_Round::getInstanceFromRow($row);
        }
        return $rounds;

    }

}

#########################################################
class GNAS_OutdoorImperialRounds extends GNAS_AllRounds {

    public function getTitle() {
        return '<h1>Outdoor Imperial Rounds, Five Zone Scoring</h1>';
    }

    public function getMeasure() {
        return 'imperial';
    }

    public function getVenue() {
        return 'outdoor';
    }

}

#######################################################
class GNAS_OutdoorMetricRounds extends GNAS_AllRounds {

    public function getTitle() {
        return '<h1>Outdoor Metric Rounds, Ten Zone Scoring</h1>';
    }

    public function getMeasure() {
        return 'metric';
    }

    public function getVenue() {
        return 'outdoor';
    }

}

######################################################
class GNAS_IndoorMetricRounds extends GNAS_AllRounds {

    public function getTitle() {
        return '<h1>Indoor Metric Rounds, Various Scorings</h1>';
    }

    public function getMeasure() {
        return 'metric';
    }

    public function getVenue() {
        return 'indoor';
    }

}

########################################################
class GNAS_IndoorImperialRounds extends GNAS_AllRounds {

    public function getTitle() {
        return '<h1>Indoor Imperial Rounds, Various Scorings</h1>';
    }

    public function getMeasure() {
        return 'imperial';
    }

    public function getVenue() {
        return 'indoor';
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

##################################################
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
            list($bow, $gender, $standard) =
                array_splice($exploded_post_name, 0, 3);
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
                                   $round,
                                   $score);
        }
        $this->updateFromPOSTs();
    }

    private function collectFromPOST($standard,
                                     $bow,
                                     $gender,
                                     $round,
                                     $score) {
        $key = "$gender $bow $round";
        // print "<p>GNAS_IndoorTable::collectFromPOST $key</p>";
        if (!isset($this->collected_posts[$key])) {
            $this->collected_posts[$key] = array(
                'gender' => $gender,
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
               . ' round, bow, gender) VALUES'
               . ' (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
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
                $collected_post['gender']
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
                                 $round) {
        // print "<p>GNAS_IndoorTable::makeEditBox</p>";
        $value = $body[$gender][$standard][$round];
        return '<input type="text" name="'
            . implode('_',
                      array($this->value_prefix,
                            $bow,
                            $gender,
                            $standard,
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
                $key = $row['gender'];
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
