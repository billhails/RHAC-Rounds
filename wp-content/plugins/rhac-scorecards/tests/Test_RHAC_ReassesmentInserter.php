<?php

include_once('RHAC_ReassesmentInserter.php');

class Test_RHAC_ReassesmentInserter extends PHPUnit_Framework_TestCase {

    private $inserter;

    public function setUp() {
        $this->inserter = new RHAC_ReassesmentInserter();
    }

    public function testNew() {
        $this->assertInstanceOf('RHAC_ReassesmentInserter', $this->inserter);
    }

}
