<?php

include_once 'setupTests.php';

class RHAC_ScorecardAccumulatorTest extends PHPUnit_Framework_TestCase {

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
            'reassessment' => 'N',
        );
        foreach ($changes as $key => $value) {
            $row[$key] = $value;
        }
        return $row;
    }

    public function testNew() {
        $this->assertInstanceOf('RHAC_ScorecardAccumulator', $this->acc);
    }

    public function testFourClassifications() {
        $this->acc->accept($this->makeRow(array('classification' => 'third')));
        $this->acc->accept($this->makeRow(array('classification' => 'third', 'scorecard_id' => 2)));
        $this->acc->accept($this->makeRow(array('classification' => 'third', 'scorecard_id' => 3)));
        $this->acc->accept($this->makeRow(array('classification' => 'second', 'scorecard_id' => 4)));
        $this->acc->accept($this->makeRow(array('reassessment' => 'end_of_season', 'scorecard_id' => 5)));
        $results = $this->acc->results();
        $expected = array(
            1 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
            ),
            2 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
            ),
            3 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
                'new_classification' => 'third',
            ),
            4 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
            ),
            5 => array(
                'new_classification' => 'third',
            ),
        );
        $this->assertEquals($expected, $results);
    }

    public function testFourHandicaps() {
        $this->acc->accept($this->makeRow(array('handicap_ranking' => 52)));
        $this->acc->accept($this->makeRow(array('handicap_ranking' => 51, 'scorecard_id' => 2)));
        $this->acc->accept($this->makeRow(array('handicap_ranking' => 50, 'scorecard_id' => 3)));
        $this->acc->accept($this->makeRow(array('handicap_ranking' => 48, 'scorecard_id' => 4)));
        $this->acc->accept($this->makeRow(array('reassessment' => 'end_of_season', 'scorecard_id' => 5)));
        $results = $this->acc->results();
        $expected = array(
            1 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
            ),
            2 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
            ),
            3 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
                'handicap_improvement' => 51,
            ),
            4 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
                'handicap_improvement' => 50,
            ),
            5 => array(
                'handicap_improvement' => 50,
                'new_classification' => 'archer',
            ),
        );
        $this->assertEquals($expected, $results);
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

    public function testSecondClubRecordPreceeding() {
        $this->acc->accept($this->makeRow());
        $this->acc->accept($this->makeRow(array(
            'scorecard_id' => 2,
            'archer' => 'Archer B',
            'score' => 100,
            'club_record' => 'current',
        )));
        $results = $this->acc->results();
        $expected = array(
            1 => array(
                'club_record' => 'current',
                'personal_best' => 'Y',
            ),
            2 => array(
                'club_record' => 'N',
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
