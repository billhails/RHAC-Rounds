<?php

include_once('RHAC_ReassesmentInserter.php');

date_default_timezone_set('Europe/London');

class RHAC_ReassesmentInserterTest extends PHPUnit_Framework_TestCase {

    private $inserter;

    public function setUp() {
        $archer_map = array(
            'Archer A' => array('date_of_birth' => '2001/07/03')
        );
        $this->inserter = new RHAC_ReassesmentInserter($archer_map);
    }

    public function testNew() {
        $this->assertInstanceOf('RHAC_ReassesmentInserter', $this->inserter);
    }

    public function testOneIndoorRow() {
        $row = array(
            'bow' => 'compound',
            'archer' => 'Archer A',
            'outdoor' => 'N',
            'date' => '2007/04/04',
            'reassessment' => 'N',
        );
        $this->inserter->accept($row);
        $results = $this->inserter->results();
        $expected = array(
            array(
                'action' => 'insert',
                'date' => '2013/07/03',
                'archer' => 'Archer A',
                'bow' => 'compound',
                'outdoor' => 'N',
                'reassessment' => 'age_group',
            ),
            array(
                'action' => 'insert',
                'date' => '2007/06/01',
                'archer' => 'Archer A',
                'bow' => 'compound',
                'outdoor' => 'N',
                'reassessment' => 'end_of_season'
            )
        );
        $this->assertEquals($expected, $results);
    }

    public function testOneIndoorRowWithReassessment() {
        $row = array(
            'scorecard_id' => 1,
            'bow' => 'compound',
            'archer' => 'Archer A',
            'outdoor' => 'N',
            'date' => '2007/04/04',
            'reassessment' => 'N',
        );
        $this->inserter->accept($row);
        $row = array(
            'scorecard_id' => 2,
            'bow' => 'compound',
            'archer' => 'Archer A',
            'outdoor' => 'N',
            'date' => '2007/06/01',
            'reassessment' => 'end_of_season',
        );
        $this->inserter->accept($row);
        $results = $this->inserter->results();
        $expected = array(
            array(
                'action' => 'insert',
                'date' => '2013/07/03',
                'archer' => 'Archer A',
                'bow' => 'compound',
                'outdoor' => 'N',
                'reassessment' => 'age_group',
            ),
        );
        $this->assertEquals($expected, $results);
    }

    public function testThreeOutdoorRows() {
        $row = array(
            'bow' => 'compound',
            'archer' => 'Archer A',
            'outdoor' => 'Y',
            'date' => '2007/04/04',
            'reassessment' => 'N',
        );
        $this->inserter->accept($row);
        $row = array(
            'bow' => 'compound',
            'archer' => 'Archer A',
            'outdoor' => 'Y',
            'date' => '2007/05/04',
            'reassessment' => 'N',
        );
        $this->inserter->accept($row);
        $row = array(
            'bow' => 'compound',
            'archer' => 'Archer A',
            'outdoor' => 'Y',
            'date' => '2008/04/04',
            'reassessment' => 'N',
        );
        $this->inserter->accept($row);
        $results = $this->inserter->results();
        $expected = array(
            array(
                'action' => 'insert',
                'date' => '2013/07/03',
                'archer' => 'Archer A',
                'bow' => 'compound',
                'outdoor' => 'Y',
                'reassessment' => 'age_group',
            ),
            array(
                'action' => 'insert',
                'date' => '2008/01/01',
                'archer' => 'Archer A',
                'bow' => 'compound',
                'outdoor' => 'Y',
                'reassessment' => 'end_of_season',
            ),
            array(
                'action' => 'insert',
                'date' => '2009/01/01',
                'archer' => 'Archer A',
                'bow' => 'compound',
                'outdoor' => 'Y',
                'reassessment' => 'end_of_season',
            ),
        );
        $this->assertEquals($expected, $results);
    }

}
