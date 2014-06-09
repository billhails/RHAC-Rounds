<?php

class RHAC_Sightmarks {
    private static $instance;

    private function __construct() {}

    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function display() {
        return <<<EOHTML
<div id="rhac-sightmarks">

  <h2>Input Sight Marks</h2>
  <button id="sightmark-add-button"
      title="Ad a sightmark to the current set">Add a Sightmark</button>
  <button id="sightmark-delete-button"
      title="Delete the currently selected sightmark from the set"
      >Delete Selected Sightmark</button>
  <table id="sightmarks-table">
    <thead>
      <tr>
        <th>Distance</th>
        <th>Sight Mark</th>
        </tr>
    </thead>
    <tfoot>
      <tr>
        <td>Distance</td>
        <td>Sight Mark</td>
      </tr>
    </tfoot>
    <tbody>
    </tbody>
  </table>

  <h2>Saved Sight Marks</h2>
  <label for="sightmarks-select">Saved Sightmarks: </label>
  <select name="sightmarks-select" id="sightmarks-select">
  </select>
  <button type="button" title="Save the current set of sightmarks"
      id="sightmarks-save-button">Save</button>
  <button type="button" title="Delete the saved set of sightmarks"
      id="sightmarks-delete-button">Delete</button>
  <button type="button" title="Restore this set from the last save"
      id="sightmarks-restore-button">Restore</button>

  <h2>Graph of <span class="sightmarks-title"></span></h2>
  <div class="rhac-canvas-wrapper">
  <canvas id="sightmarks-canvas" width="600" height="300"></canvas>
  </div>

  <h2>Estimates for <span class="sightmarks-title"></span></h2>
  <table id="sightmarks-estimate-table">
    <caption>Royston Heath Archery Club Estimates for <span class="sightmarks-title"></span></caption>
    <thead>
      <tr>
        <th colspan="2">Metric</th>
        <th colspan="2">Imperial</th>
      </tr>
      <tr>
        <th>Distance</th>
        <th>Sight Mark</th>
        <th>Distance</th>
        <th>Sight Mark</th>
        </tr>
    </thead>
    <tfoot>
      <tr>
        <td>Distance</td>
        <td>Sight Mark</td>
        <td>Distance</td>
        <td>Sight Mark</td>
      </tr>
      <tr>
        <td colspan="2">Metric</td>
        <td colspan="2">Imperial</td>
      </tr>
    </tfoot>
    <tbody>
    </tbody>
  </table>

  <div id="sightmarks-quota-exceeded-dialog"
       class="rhac-sightmarks-simple-dialog">
    <p>Quota Exceeded, please delete some old reports first.</p>
  </div>

  <div id="sightmarks-old-browser-dialog"
       class="rhac-sightmarks-simple-dialog">
    <p>You seem to have a very old browser that does not
        support saving reports, please upgrade!</p>
  </div>

  <div id="sightmark-dialog" title="Add a Sightmark">
  <p id="sightmark-tip">All form fields are required.</p>
 
  <form>
  <fieldset>
    <label for="sightmark-distance">Distance</label>
    <input type="text" name="sightmark-distance" id="sightmark-distance" value="" class="text ui-widget-content ui-corner-all"/>
    <label for="sightmark-sightmark">Sightmark</label>
    <input type="text" name="sightmark-sightmark" id="sightmark-sightmark" value="" class="text ui-widget-content ui-corner-all"/>
  </fieldset>
  </form>
  </div>

  <div id="sightmarks-save-dialog" title="Save Sightmarks">
  <p id="sightmarks-tip">Enter a name</p>
  <form>
  <fieldset>
    <label for="sightmarks-name">Name</label>
    <input type="text" name="sightmarks-name" id="sightmarks-name" value="" class="text ui-widget-content ui-corner-all"/>
  </fieldset>
  <form>
  </div>

  <div id="sightmarks-confirm-dialog" title="Change Sightmarks">
    <p>You have unsaved changes. Are you sure you want to continue?</p>
  </div>

</div>
EOHTML;
    }
}

//vim: sw=2
