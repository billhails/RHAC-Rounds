<?php

$rhac_scorecard_accumulator_manifest = array(
    'RHAC_ClubRecordAccumulator',
    'RHAC_PersonalBestAccumulator',
    # 'RHAC_HandicapImprovementAccumulator',
    # 'RHAC_NewClassificationAccumulator',
);

foreach ($rhac_scorecard_accumulator_manifest as $accumulator) {
    include_once plugin_dir_path(__FILE__) . $accumulator . '.php';
}

class RHAC_ScorecardAccumulator {

    public function accept($row) {
        global $rhac_scorecard_accumulator_manifest;
        foreach ($rhac_scorecard_accumulator_manifest as $class) {
            call_user_func(array($class, 'callAccumulator'), $row);
        }
    }

    public function results() {
        $results = array();
        foreach ($this->getAllAccumulators() as $accumulator) {
            $results = $this->mergeHashes($results, $accumulator->results());
        }
        return $results;
    }

    private function getAllAccumulators() {
        $result = array();
        global $rhac_scorecard_accumulator_manifest;
        foreach ($rhac_scorecard_accumulator_manifest as $class) {
            $result = array_merge($result, call_user_func(array($class, 'getAllInstances')));
        }
        return $result;
    }

    private function mergeHashes($hash1, $hash2) {
        foreach ($hash2 as $scorecard_id => $changes) {
            if (isset($hash1[$scorecard_id])) {
                foreach ($changes as $field => $value) {
                    if (isset($hash1[$scorecard_id][$field])) {
                        die("conflicting updates on score #$scorecard_id $field => '"
                        . $hash1[$scorecard_id][$field]
                        . "' vs '"
                        . $hash2[$scorecard_id][$field]
                        . "'");
                    }
                    else {
                        $hash1[$scorecard_id][$field] = $value;
                    }
                }
            }
            else {
                $hash1[$scorecard_id] = $changes;
            }
        }
        return $hash1;
    }

}
