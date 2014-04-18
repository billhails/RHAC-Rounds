<?php

function plugin_dir_path($file) {
    return './';
}

include_once('RHAC_ScorecardAccumulator.php');

class Test_RHAC_ReassesmentInserter extends PHPUnit_Framework_TestCase {

    private $acc;

    public function setUp() {
        $this->acc = new RHAC_ScorecardAccumulator();
    }

    private function makeRow($changes = array()) {
        $row = array(
            'scorecard_id' => 1,
            'archer' => 'Archer A',
            'bow' => 'compound',
            'category' => 'adult',
            'gender' => 'M',
            'round' => 'Frostbite',
            'score' => 252,
            'club_record' => 'N',
            'personal_best' => 'N',
        );
        foreach ($changes as $key => $value) {
            $row[$key] = $value;
        }
        return $row;
    }

    public function testNew() {
        $this->assertInstanceOf('RHAC_ScorecardAccumulator', $this->acc);
    }

    public function testSingleRow() {
        $this->acc->accept($this->makeRow());
        $results = $this->acc->results();
        $expected = array(
            1 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
            ),
        );
        $this->assertEquals($expected, $results);
    }

    public function testSingleRowAlreadyClubRecord() {
        $this->acc->accept($this->makeRow(array('club_record' => 'current')));
        $results = $this->acc->results();
        $expected = array(
            1 => array(
                'personal_best' => 'Y',
            ),
        );
        $this->assertEquals($expected, $results);
    }

    public function testSecondClubRecord() {
        $this->acc->accept($this->makeRow());
        $this->acc->accept($this->makeRow(array(
            'scorecard_id' => 2,
            'archer' => 'Archer B',
            'score' => 280,
        )));
        $results = $this->acc->results();
        $expected = array(
            1 => array(
                'club_record' => 'old',
                'personal_best' => 'Y',
            ),
            2 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
            ),
        );
        $this->assertEquals($expected, $results);
    }

    public function testReplaceClubRecord() {
        $this->acc->accept($this->makeRow(array('club_record' => 'current')));
        $this->acc->accept($this->makeRow(array(
            'scorecard_id' => 2,
            'archer' => 'Archer B',
            'score' => 280,
        )));
        $results = $this->acc->results();
        $expected = array(
            1 => array(
                'club_record' => 'old',
                'personal_best' => 'Y',
            ),
            2 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
            ),
        );
        $this->assertEquals($expected, $results);
    }

    public function testNoOp() {
        $this->acc->accept($this->makeRow(array('club_record' => 'old', 'personal_best' => 'Y')));
        $this->acc->accept($this->makeRow(array(
            'scorecard_id' => 2,
            'archer' => 'Archer B',
            'score' => 280,
            'club_record' => 'current',
            'personal_best' => 'Y',
        )));
        $results = $this->acc->results();
        $expected = array();
        $this->assertEquals($expected, $results);
    }

}
