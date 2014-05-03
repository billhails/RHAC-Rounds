<?php

include_once 'setupTests.php';

date_default_timezone_set('Europe/London');

class RHAC_NewClassificationAccumulatorTest extends PHPUnit_Framework_TestCase {

    private $accumulator;

    public function setUp() {
        $this->accumulator = new RHAC_NewClassificationAccumulator();
    }

    public function testNew() {
        $this->assertInstanceOf('RHAC_NewClassificationAccumulator', $this->accumulator);
    }

    public function testOneScore() {
        $scores = array(
            array( 'date' => '2014/01/02', 'classification' => 'third',),
        );
        $results = $this->feedIt($scores);
        $expected = array();
        $this->assertEquals($expected, $results);
    }

    # Initial grading and subsequent upgrading occurs immediately the necessary
    # scores have been made in the defined year.
    public function testInitialGrading() {
        $scores = array(
            array( 'date' => '2014/01/02', 'classification' => 'third',),
            array( 'date' => '2014/01/03', 'classification' => 'third',),
            array( 'date' => '2014/01/04', 'classification' => 'third',),
        );
        $results = $this->feedIt($scores);
        $expected = array(
            3 => array(
                'new_classification' => 'third'
            )
        );
        $this->assertEquals($expected, $results);
    }

    # the qualification, as a minimum, holds for one year immediately following that in
    # which it is gained.
    public function testQualificationHolds() {
        $scores = array(
            array( 'date' => '2014/01/02', 'classification' => 'third',),
            array( 'date' => '2014/01/03', 'classification' => 'third',),
            array( 'date' => '2014/01/04', 'classification' => 'third',),
            array( 'reassessment' => 'end_of_season', 'date' => '2014/06/01',),
        );
        $results = $this->feedIt($scores);
        $expected = array(
            3 => array( 'new_classification' => 'third' ),
            4 => array( 'new_classification' => 'third' ),
        );
        $this->assertEquals($expected, $results);
    }

    # If [the classification] is not maintained during that year, reclassification
    # shall be on the scores made during the year
    public function testClassificationNotMaintained() {
        $scores = array(
            array( 'date' => '2013/01/02', 'classification' => 'second',),
            array( 'date' => '2013/01/03', 'classification' => 'second',),
            array( 'date' => '2013/01/04', 'classification' => 'second',),
            array( 'reassessment' => 'end_of_season', 'date' => '2013/06/01',),
            array( 'date' => '2014/01/02', 'classification' => 'third',),
            array( 'date' => '2014/01/03', 'classification' => 'third',),
            array( 'date' => '2014/01/04', 'classification' => 'third',),
            array( 'reassessment' => 'end_of_season', 'date' => '2014/06/01',),
        );
        $results = $this->feedIt($scores);
        $expected = array(
            3 => array( 'new_classification' => 'second' ),
            4 => array( 'new_classification' => 'second' ),
            8 => array( 'new_classification' => 'third' ),
        );
        $this->assertEquals($expected, $results);
    }

    # An archer who has failed to reach the qualifying scores for the lowest
    # classification grade shall be listed as an Archer.
    public function testNoQualifyingScores() {
        $scores = array(
            array( 'date' => '2013/01/02', 'classification' => 'second',),
            array( 'date' => '2013/01/03', 'classification' => 'second',),
            array( 'date' => '2013/01/04', 'classification' => 'second',),
            array( 'reassessment' => 'end_of_season', 'date' => '2013/06/01',),
            array( 'date' => '2014/01/02', 'classification' => 'archer',),
            array( 'date' => '2014/01/03', 'classification' => 'archer',),
            array( 'date' => '2014/01/04', 'classification' => 'archer',),
            array( 'reassessment' => 'end_of_season', 'date' => '2014/06/01',),
        );
        $results = $this->feedIt($scores);
        $expected = array(
            3 => array( 'new_classification' => 'second' ),
            4 => array( 'new_classification' => 'second' ),
            8 => array( 'new_classification' => 'archer' ),
        );
        $this->assertEquals($expected, $results);
    }

    # An archer who previously held a classification but failed to shoot the
    # minimum number of rounds required to acquire a classification during the
    # following defined year shall be listed as unclassified. 
    public function testNotEnoughScores() {
        $scores = array(
            array( 'date' => '2013/01/02', 'classification' => 'second',),
            array( 'date' => '2013/01/03', 'classification' => 'second',),
            array( 'date' => '2013/01/04', 'classification' => 'second',),
            array( 'reassessment' => 'end_of_season', 'date' => '2013/06/01',),
            array( 'date' => '2014/01/02', 'classification' => '',), # unclassified scores don't count
            array( 'date' => '2014/01/03', 'classification' => '',),
            array( 'date' => '2014/01/04', 'classification' => '',),
            array( 'reassessment' => 'end_of_season', 'date' => '2014/06/01',),
        );
        $results = $this->feedIt($scores);
        $expected = array(
            3 => array( 'new_classification' => 'second' ),
            4 => array( 'new_classification' => 'second' ),
            8 => array( 'new_classification' => 'unclassified' ),
        );
        $this->assertEquals($expected, $results);
    }

    # When a junior reaches the age of the next higher age group, the classification of
    # Bowman/Junior Bowman, 1st, 2nd or 3rd Class will be assessed on the three best
    # qualifying scores shot in the twelve months preceding the birthday date.
    public function testJuniorReassessment() {
        $scores = array(
            array( 'date' => '2013/01/02', 'classification' => 'second', 'next_age_group_classification' => 'third'),
            array( 'date' => '2013/01/03', 'classification' => 'second', 'next_age_group_classification' => 'third'),
            array( 'date' => '2013/01/04', 'classification' => 'second', 'next_age_group_classification' => 'third'),
            array( 'reassessment' => 'age_group', 'date' => '2013/01/05',),
        );
        $results = $this->feedIt($scores);
        $expected = array(
            3 => array( 'new_classification' => 'second' ),
            4 => array( 'new_classification' => 'third' ),
        );
        $this->assertEquals($expected, $results);
    }

    # If three rounds as nominated in the higher section have not been shot in the
    # twelve months, the [junior] archer will be unclassified until the necessary rounds have
    # been shot.
    public function testJuniorReassessmentNoQualifyingScores() {
        $scores = array(
            array( 'date' => '2013/01/02', 'classification' => 'second', 'next_age_group_classification' => ''),
            array( 'date' => '2013/01/03', 'classification' => 'second', 'next_age_group_classification' => ''),
            array( 'date' => '2013/01/04', 'classification' => 'second', 'next_age_group_classification' => ''),
            array( 'reassessment' => 'age_group', 'date' => '2013/01/05',),
        );
        $results = $this->feedIt($scores);
        $expected = array(
            3 => array( 'new_classification' => 'second' ),
            4 => array( 'new_classification' => 'unclassified' ),
        );
        $this->assertEquals($expected, $results);
    }

    ####################### additional 'reading between the lines' tests. ##########################

    # when an end of season reassessment occurs after an age change reassessment, the previous
    # seasons scores are reassessed at the new age group level.
    public function testJuniorReassessmentPlusEndOfSeason() {
        $scores = array(
            array( 'date' => '2013/01/02', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2013/01/03', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2013/01/04', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2013/01/05', 'reassessment' => 'age_group' ),
            array( 'date' => '2013/01/06', 'classification' => 'second' ),
            array( 'date' => '2013/06/01', 'reassessment' => 'end_of_season' ),
        );
        $results = $this->feedIt($scores);
        $expected = array(
            3 => array( 'new_classification' => 'second' ),
            4 => array( 'new_classification' => 'third' ),
            6 => array( 'new_classification' => 'third' ),
        );
        $this->assertEquals($expected, $results);
    }

    # when an end of season reassessment occurs after an age change reassessment, the previous
    # seasons scores, at the new level of assessment, contribute to the archer's new classification.
    public function testJuniorReassessmentPlusEndOfSeasonPlusPrevious() {
        $scores = array(
            array( 'date' => '2013/01/01', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2013/01/02', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2013/01/03', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2013/01/04', 'classification' => 'second', 'next_age_group_classification' => 'second' ),
            array( 'date' => '2013/01/05', 'reassessment' => 'age_group' ),
            array( 'date' => '2013/01/06', 'classification' => 'second' ),
            array( 'date' => '2013/01/07', 'classification' => 'second' ),
            array( 'date' => '2013/06/01', 'reassessment' => 'end_of_season' ),
        );
        $results = $this->feedIt($scores);
        $expected = array(
            3 => array( 'new_classification' => 'second' ),
            5 => array( 'new_classification' => 'third' ),
            7 => array( 'new_classification' => 'second' ),
            8 => array( 'new_classification' => 'second' ),
        );
        $this->assertEquals($expected, $results);
    }

    # when an end of season reassessment occurs after an age change reassessment, the previous
    # seasons scores, at the new level of assessment, contribute to the archer's new classification.
    # But only the previous season, not the whole year before the age change reassessment.
    public function testJuniorReassessmentPlusEndOfSeasonPlusPreviousOld() {
        $scores = array(
            array( 'date' => '2012/05/01', 'classification' => 'second', 'next_age_group_classification' => 'second' ),
            array( 'date' => '2012/06/01', 'reassessment' => 'end_of_season' ),
            array( 'date' => '2013/01/01', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2013/01/02', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2013/01/03', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2013/01/05', 'reassessment' => 'age_group' ),
            array( 'date' => '2013/01/06', 'classification' => 'second' ),
            array( 'date' => '2013/01/07', 'classification' => 'second' ),
            array( 'date' => '2013/06/01', 'reassessment' => 'end_of_season' ),
        );
        $results = $this->feedIt($scores);
        $expected = array(
            2 => array( 'new_classification' => 'archer' ),
            5 => array( 'new_classification' => 'second' ),
            6 => array( 'new_classification' => 'third' ),
            9 => array( 'new_classification' => 'third' ),
        );
        $this->assertEquals($expected, $results);
    }

    # when an end of season reassessment is followed by an age chane reassessment,
    # the entire previous year's worth of scores are available for the age change reassessment.
    public function testJuniorEndOfSeasonFollowedByAgeChange() {
        $scores = array(
            array( 'date' => '2012/05/01', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2012/06/01', 'reassessment' => 'end_of_season' ),
            array( 'date' => '2012/07/01', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2012/07/02', 'classification' => 'second', 'next_age_group_classification' => 'third' ),
            array( 'date' => '2012/07/03', 'reassessment' => 'age_group' ),
        );
        $this->setDebug(true);
        $results = $this->feedIt($scores);
        $expected = array(
            2 => array( 'new_classification' => 'archer' ),
            5 => array( 'new_classification' => 'third' ),
        );
        $this->assertEquals($expected, $results);
    }

    private function setDebug($bool) {
        $this->accumulator->setDebug($bool);
    }

    private function feedIt($scores, $outdoor='Y') {
        $count = 1;
        foreach ($scores as $score) {
            $score['scorecard_id'] = $count++;
            $score['archer'] = 'Archer A';
            $score['bow'] = 'compound';
            $score['outdoor'] = $outdoor;
            if (!$row['reassessment']) {
                $row['reassessment'] = 'N';
            }
            if (!$row['classification']) {
                $row['classification'] = '';
            }
            if (!$row['next_age_group_classification']) {
                $row['next_age_group_classification'] = '';
            }
            if (!$row['new_classification']) {
                $row['new_classification'] = '';
            }
            $this->accumulator->accept($score);
        }
        return $this->accumulator->results();
    }

}
