<?php

/**
 * Initial grading and subsequent upgrading occurs immediately the necessary
 * scores have been made in the defined year.
 *
 * Apart from occasions when juniors become seniors or change age groups (see below),
 * the qualification, as a minimum, holds for one year immediately following that in
 * which it is gained. If it is not maintained during that year, reclassification
 * shall be on the scores made during the year.
 *
 * An archer who has failed to reach the qualifying scores for the lowest
 * classification grade shall be listed as an Archer.
 *
 * An archer who previously held a classification but failed to shoot the
 * minimum number of rounds required to acquire a classification during the
 * following defined year shall be listed as unclassified.
 *
 * When a junior reaches the age of the next higher age group, the classification of
 * Bowman/Junior Bowman, 1st, 2nd or 3rd Class will be assessed on the three best
 * qualifying scores shot in the twelve months preceding the birthday date.
 * If three rounds as nominated in the higher section have not been shot in the
 * twelve months, the archer will be unclassified until the necessary rounds have
 * been shot.
 */
class RHAC_NewClassificationAccumulator extends RHAC_ScorecardAccumulator {
    private $children;
    private $debug = false;

    public function __construct() {
        $this->children = array();
    }

    public function accept($row) {
        $key = $this->makeKey($row, array('archer', 'bow', 'outdoor'));
        if (!isset($this->children[$key])) {
            $this->children[$key] = new RHAC_NewClassificationAccumulatorLeaf($this->debug);
        }
        $this->children[$key]->accept($row);
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }

    protected function getChildren() {
        return array_values($this->children);
    }
}


class RHAC_NewClassificationAccumulatorLeaf extends RHAC_AccumulatorLeaf {
    private $debug;
    private $classification_this_season;
    private $current_classification = 'archer';
    private $classifications_this_season;
    private $next_age_group_classifications_this_season;
    private $classifications_last_twelve_months = array();
    protected $proposed_changes = array();
    protected $current_db_values = array();

    private static $classification_order = array(
        'archer' => 1, 'unclassified' => 0, 'third' => 2, 'second' => 3, 'first' => 4,
        'bm' => 5, 'mbm' => 6, 'gmbm' => 7,
        'H' => 2, 'G' => 3, 'F' => 4, 'E' => 5, 'D' => 6, 'C' => 7, 'B' => 8, 'A' => 9,
    );

    public function __construct($debug) {
        $this->debug = $debug;
        $this->resetClassificationsThisSeason();
    }

    private function debug($msg) {
        if ($this->debug) {
            error_log("# $msg");
        }
    }

    private function resetClassificationsThisSeason() {
        $this->debug("current_classification is " . $this->current_classification);
        if ($this->current_classification == 'archer') {
            $this->debug("resetting current classification this season to archer");
            $this->classification_this_season = 'archer';
        }
        else {
            $this->debug("resetting current classification this season to unclassified");
            $this->classification_this_season = 'unclassified';
        }
        $this->classifications_this_season = array(
            'archer' => 0,
            'third' => 0, 'second' => 0, 'first' => 0, 'bm' => 0, 'mbm' => 0, 'gmbm' => 0,
            'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0,
        );
        $this->next_age_group_classifications_this_season = array(
            'archer' => 0,
            'third' => 0, 'second' => 0, 'first' => 0, 'bm' => 0, 'mbm' => 0, 'gmbm' => 0,
            'A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'F' => 0, 'G' => 0, 'H' => 0,
        );
    }

    public function accept($row) {
        $row = $this->wrap($row);
        if ($row->isAgeGroupReassessment()) {
            $this->debug("row is age group reassessment");
            $this->setCurrentClassification($this->bestLastYear());
            $this->setClassificationThisSeason($this->current_classification);
            $this->rewriteClassificationsThisSeason();
            $this->noteClassificationChange($row);
        } elseif ($row->isEndOfSeasonReassessment()) {
            $this->debug("row is end of season reassessment");
            $this->setCurrentClassification($this->classification_this_season);
            $this->noteClassificationChange($row);
            $this->resetClassificationsThisSeason();
        }
        else {
            $this->debug("row is normal score");
            $classification = $row->classification();
            if ($this->addAndReportCount($classification) == 3) {
                $this->debug("saw three '$classification' classifications");
                if ($this->better($classification, $this->classification_this_season)) {
                    $this->setClassificationThisSeason($classification);
                }
                if ($this->better($classification, $this->current_classification)) {
                    $this->setCurrentClassification($classification);
                    $this->noteClassificationChange($row);
                }
                elseif ($this->equal($classification, $this->current_classification)) {
                    $this->noteClassificationConfirmed($row);
                }
            }
            $next_age_group_classification = $row->nextAgeGroupClassification();
            if ($this->addAndReportNextAgeGroupCount($next_age_group_classification) == 3) {
                $this->debug("saw three '$next_age_group_classification' next age group classifications");
                $this->rememberForAgeChange($next_age_group_classification, $row->date());
            }
        }
    }

    private function rewriteClassificationsThisSeason() {
        $this->debug("rewriting this season's classifications to be the new age group");
        $this->classifications_this_season = $this->next_age_group_classifications_this_season;
    }

    private function setClassificationThisSeason($classification) {
        $this->debug("setting classification this season to $classification");
        $this->classification_this_season = $classification;
    }

    private function setCurrentClassification($classification) {
        $this->debug("setting current classification to $classification");
        $this->current_classification = $classification;
    }

    private function wrap($row) {
        return new RHAC_NewClassificationAccumulatorRow($row);
    }

    private function rememberForAgeChange($classification, $date) {
        $this->classifications_last_twelve_months[$date] = $classification;
        $year_ago = $this->subtractOneYear($date);
        foreach (array_keys($this->classifications_last_twelve_months) as $old_date) {
            if ($old_date < $year_ago) {
                unset($this->classifications_last_twelve_months[$old_date]);
            }
            else {
                break;
            }
        }
    }

    private function subtractOneYear($date) {
        $year = substr($date, 0, 4);
        $rest = substr($date, 4);
        $year--;
        return $year . $rest;
    }

    private function bestLastYear() {
        $result = 'unclassified';
        foreach ($this->classifications_last_twelve_months as $classification) {
            if ($this->better($classification, $result)) {
                $result = $classification;
            }
        }
        return $result;
    }

    private function addAndReportCount($classification) {
        if ($classification) {
            if (isset($this->classifications_this_season[$classification])) {
                return ++$this->classifications_this_season[$classification];
            }
            else {
                die("unrecognised classification: $classification\n");
            }
        }
        else {
            return 0;
        }
    }

    private function addAndReportNextAgeGroupCount($classification) {
        if ($classification) {
            if (isset($this->next_age_group_classifications_this_season[$classification])) {
                return ++$this->next_age_group_classifications_this_season[$classification];
            }
            else {
                die("unrecognised classification: $classification\n");
            }
        }
        else {
            return 0;
        }
    }

    private function better($classification_a, $classification_b) {
        $better = self::$classification_order[$classification_a]
                > self::$classification_order[$classification_b];
        $this->debug("$classification_a is " . ($better ? 'better' : 'not better') . " than $classification_b");
        return $better;
    }

    private function equal($classification_a, $classification_b) {
        $equal = self::$classification_order[$classification_a]
               == self::$classification_order[$classification_b];
        $this->debug("$classification_a is " . ($equal ? 'equal' : 'not equal') . " to $classification_b");
        return $equal;
    }

    protected function keyToChange() {
        return 'new_classification';
    }

    private function noteClassificationChange($row) {
        $this->proposed_changes[$row->id()] = $this->current_classification;
        $this->current_db_values[$row->id()] = $row->newClassification();
    }

    private function noteClassificationConfirmed($row) {
        $this->proposed_changes[$row->id()] = "(" . $this->current_classification . ")";
        $this->current_db_values[$row->id()] = $row->newClassification();
    }

}

class RHAC_NewClassificationAccumulatorRow {
    private $row;

    public function __construct($row) {
        $this->row = $row;
    }

    public function isAgeGroupReassessment() {
        return ($this->row['reassessment'] == "age_group");
    }

    public function isEndOfSeasonReassessment() {
        return ($this->row['reassessment'] == "end_of_season");
    }

    public function date() {
        return $this->row['date'];
    }

    public function classification() {
        return $this->row['classification'];
    }

    public function nextAgeGroupClassification() {
        return $this->row['next_age_group_classification'];
    }

    public function newClassification() {
        return $this->row['new_classification'];
    }

    public function id() {
        return $this->row['scorecard_id'];
    }
}
