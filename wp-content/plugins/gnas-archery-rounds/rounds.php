<?php

class GNAS_PDO {
    private static $pdo;

    /**
     * disallow creation of instances.
     */
    private function __construct() {
    }

    public static function get() {
        if (!isset(self::$pdo)) {
            try {
                self::$pdo = new PDO('sqlite:'
                                     . plugin_dir_path(__FILE__)
                                     . '../gnas-archery-rounds/archery.db');
            } catch (PDOException $e) {
                wp_die('Error!: ' . $e->getMessage());
                exit();
            }
            self::$pdo->exec('PRAGMA foreign_keys = ON');
        }
        return self::$pdo;
    }

    public static function fetch($query, $params = array()) {
        // is_array
        $stmt = self::get()->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();
        return $rows;
    }
}

class GNAS_PageURL {
    private static $pageURL;
    /**
     * disallow creation of instances.
     */
    private function __construct() {
    }

    public static function get() {
        if (!isset(self::$pageURL)) {
            $pageURL = 'http';
            if ($_SERVER['HTTPS'] == 'on') {
                $pageURL .= 's';
            }
            $pageURL .= '://';
            if ($_SERVER['SERVER_PORT'] != '80') {
                $pageURL .= $_SERVER['SERVER_NAME']
                            . ':'
                            . $_SERVER['SERVER_PORT']
                            . $_SERVER['REQUEST_URI'];
            } else {
                $pageURL .= $_SERVER['SERVER_NAME']
                            . $_SERVER['REQUEST_URI'];
            }
            $pageURL = preg_replace('/\?.*/', '', $pageURL);
            self::$pageURL = $pageURL;
        }
        return self::$pageURL;
    }

}

class GNAS_AgeGroups {
    private static $age_groups;

    /**
     * disallow creation of instances.
     */
    private function __construct() {
    }

    static function get() {
        if (!isset(self::$age_groups)) {
            $rows = GNAS_PDO::fetch('SELECT age_group'
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


class GNAS_Genders {
    private static $genders;

    static function get() {
        if (!isset(self::$genders)) {
            $rows = GNAS_PDO::fetch('SELECT gender'
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

abstract class GNAS_Scoring {
    abstract public function getName();
    abstract public function getMultiplier();

    public function maxScore(GNAS_ArrowCounts $arrowCounts) {
        return $this->getMultiplier() * $arrowCounts->getTotalArrows();
    }

}

class GNAS_TenZoneScoring extends GNAS_Scoring {
    public function getName() { return 'ten zone'; }
    public function getMultiplier() { return 10; }
}

class GNAS_FiveZoneScoring extends GNAS_Scoring {
    public function getName() { return 'five zone'; }
    public function getMultiplier() { return 9; }
}

class GNAS_FiveMaxScoring extends GNAS_Scoring {
    public function getName() { return 'five max'; }
    public function getMultiplier() { return 5; }
}

class GNAS_SingleArrowCount {

    private $numArrows;
    private $dozens;
    private $face;

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
    }

    public function getDescriptionRow() {
        return '<li>'
               . $this->dozens
               . ' doz, '
               . $this->face
               . ' face</li>';
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
        $rows = GNAS_PDO::fetch(
            'SELECT *'
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

}

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
        $rows = GNAS_PDO::fetch(
            'SELECT *'
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


interface GNAS_FamilyInterface {
    public function asText();
    public function getTableBody();
}

class GNAS_UnrecognisedRoundFamily
    extends GNAS_Unrecognised
    implements GNAS_FamilyInterface {

    public function getTypeName() {
        return 'round family';
    }

}

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
        $rows = GNAS_PDO::fetch(
            'SELECT *'
            . ' FROM round'
            . ' WHERE family_name = ?'
            . ' ORDER by display_order',
            array($this->name));
        foreach ($rows as $row) {
            $this->rounds[$row['display_order']] =
                GNAS_Round::getInstanceByRow($row);
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
            $rows = GNAS_PDO::fetch('SELECT *'
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

interface GNAS_RoundInterface {
    public function getTableRow();
    public function asText();
}

class GNAS_UnrecognisedRound
    extends GNAS_Unrecognised
    implements GNAS_RoundInterface {

    public function getTypeName() {
        return 'round';
    }
    public function asText() {
        return super();
    }

    public function getTableRow() {
        return '';
    }

}

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
             . GNAS_PageURL::get()
             . '?round='
             . $this->searchTerm
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

    public static function getInstanceByRow(array $row) {
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
            $rows = GNAS_PDO::fetch('SELECT *'
                                    . ' FROM round'
                                    . ' WHERE name = ?',
                                    array($name));
            $row = $rows[0];
            if (isset($row['name']))
                return self::getInstanceByRow($row);
            else
                self::$instances[$name] = new GNAS_UnrecognisedRound($name);
        }
        return self::$instances[$name];
    }

}

class GNAS_Classifications {

    private $roundName;
    private $gender_magender_map;

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

        $query = 'SELECT '
                . implode(', ', $fields)
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

        $rows = GNAS_PDO::fetch($query, $parameters);

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
                $params []= $valid . '=' . urlencode($row[$key]);
                $wrap = true;
            }
            else if (array_key_exists($valid, $_GET)) {
                $params []= $valid . '=' . urlencode($_GET[$valid]);
            }
        }
        if ($wrap) {
            return ('<a href="'
                    . GNAS_PageURL::get()
                    . '?'
                    . implode('&', $params)
                    . '">'
                    . $row[$key]
                    . '</a>');
        }
        else {
            return ($row[$key]);
        }
    }

}

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
        $query = 'SELECT round.* FROM round, round_family'
               . ' WHERE round.family_name = round_family.name'
               . ' AND round_family.measure = ?'
               . ' AND round_family.venue = ?'
               . ' ORDER BY round.family_name, round.display_order';
        $params = array($this->getMeasure(), 'outdoor');
        $rows = GNAS_PDO::fetch($query, $params);
        $rounds = array();
        foreach ($rows as $row) {
            $rounds []= GNAS_Round::getInstanceByRow($row);
        }
        return $rounds;

    }

}

class GNAS_ImperialRounds extends GNAS_AllRounds {

    public function getTitle() {
        return '<h1>Imperial Rounds, Five Zone Scoring</h1>';
    }

    public function getMeasure() {
        return 'imperial';
    }

}

class GNAS_MetricRounds extends GNAS_AllRounds {

    public function getTitle() {
        return '<h1>Metric Rounds, Ten Zone Scoring</h1>';
    }

    public function getMeasure() {
        return 'metric';
    }

}

/**
 * Renders the admin menus.
 * Produces as closely as possible a represdentation of the given GNAS table.
 */
class GNAS_OutdoorTable {

    private $table_number;
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
        $this->table_number = $table_number;
    }

    public function handlePOST() {
        if ($_POST[$this->submit_key] == 'true') {
            $this->do_handlePOST();
        }
    }

    public function asText() {
        return $this->getTitle() . $this->getTables();
    }

    private function do_handlePOST() {
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

    private function getTHead() {
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

    private function getTables() {
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
        if (!isset($this->classifications)) {
            $this->classifications = array();
            $placeholders = array();
            $arguments = array();
            $table = $this->getDBTable();
            foreach ($this->getDBHeaderTable() as $header) {
                $placeholders []= '?';
                $arguments []= $header['round'];
            }
            $query = 'SELECT *'
                   . ' FROM outdoor_classifications'
                   . ' WHERE round IN ('
                   . implode(',', $placeholders)
                   . ') and bow = ?';
            $arguments []= $table['bow'];
            $rows = GNAS_PDO::fetch($query, $arguments);
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

    private function getTitle() {
        $table = $this->getDBTable();
        return '<h1>'
               . 'Table '
               . $this->table_number
               . ' - '
               . $table['title']
               . '</h1>';
    }

    private function getDBTable() {
        if (!isset($this->agb_outdoor_table)) {
            $this->agb_outdoor_table = array();
            $rows = GNAS_PDO::fetch('SELECT *'
                                    . ' FROM agb_outdoor_table'
                                    . ' WHERE table_number = ?',
                                    array($this->table_number));
            $this->agb_outdoor_table = $rows[0];
        }
        return $this->agb_outdoor_table;
    }

    private function getDBHeaderTable() {
        if (!isset($this->agb_outdoor_table_columns)) {
            $table = $this->getDBTable();
            $this->agb_outdoor_table_columns = GNAS_PDO::fetch(
                'SELECT * FROM agb_outdoor_table_column'
                . ' WHERE header_number = ? ORDER BY column_number',
                array($table['header_number']));
        }
        return $this->agb_outdoor_table_columns;
    }

    public function tableCSS() {
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
     * used by the shortcode.
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

    /**
     * used by other plugins
     */
    public static function roundData() {
        return GNAS_AllRounds::asData();
    }
}
