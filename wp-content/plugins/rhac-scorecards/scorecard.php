<?php
    include plugin_dir_path(__FILE__) . '../gnas-archery-rounds/rounds.php';
    try {
        $pdo = new PDO('sqlite:'
                     . plugin_dir_path(__FILE__)
                     . '../rhac-scorecards/scorecard.db');
        } catch (PDOException $e) {
            wp_die('Error!: ' . $e->getMessage());
            exit();
        }
    $pdo->exec('PRAGMA foreign_keys = ON');
    function fetch($query, $params = array()) {
        global $pdo;
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();
        return $rows;
    }
?>
<h1>Prototype for the Score Cards</h1>
<span id="round-data">
<?php
    foreach (GNAS_Page::roundData() as $round) {
        $name = $round->getName();
        print '<span name="' . $name . '">';
        print '<span class="measure">'
            . $round->getMeasure()->getName()
            . '</span>';
        foreach ($round->getDistances()->rawData() as $distance) {
            print '<span class="count">'
                . $distance->getNumArrows()
                . '</span>';
        }
        print "</span>\n";
    }
?>
</span>
<form method="post" action="">
    <input type="hidden" name="scorecard-id" value="0"/>
    <table>
        <thead>
            <tr>
                <th colspan="3">Archer</th>
                <td colspan="7"><select name="archer"><?php
                    print "<option value=''>- - -</option>\n";
                    $archers = fetch('SELECT name FROM archer ORDER BY name');
                    foreach ($archers as $archer) {
                        print "<option value='$archer[name]'>"
                            . $archer["name"]
                            . "</option>\n";
                    }
                ?></select></td>
                <th colspan="3">Bow</th>
                <td colspan="6">
                    <input type="radio" name="bow" value="recurve">R</input>
                    <input type="radio" name="bow" value="compound">C</input>
                    <input type="radio" name="bow" value="barebow">B</input>
                    <input type="radio" name="bow" value="longbow">L</input>
                </td>
            </tr>
            <tr>
                <th colspan="3">Round</th>
                <td colspan="7"><select name="round" id="round"><?php
                print "<option value=''>- - -</option>\n";
                foreach (GNAS_Page::roundData() as $round) {
                    print "<option value='" . $round->getName() . "'>"
                    . $round->getName() . "</option>\n";
                }
                ?></select></td>
                <th colspan="3">Date</th>
                <td colspan="6"><input type="text" name="date" id="datepicker"/></td>
            </tr>
            <tr>
                <th colspan="6">&nbsp;</th>
                <th>End</th>
                <th colspan="6">&nbsp;</th>
                <th>End</th>
                <th>Hits</th>
                <th>Xs</th>
                <th>Golds</th>
                <th>Doz</th>
                <th>Tot</th>
            </tr>
        </thead>
        <tbody id="scorecard">
        <?php

            $end = 0;
            for ($dozen = 1; $dozen < 13; ++$dozen) {
                print '<tr>' . "\n";
                foreach (array('odd', 'even') as $pos) {
                    $end++;
                    for ($arrow = 1; $arrow < 7; ++$arrow) {
                        print " <td><input type='text' class='score' name='arrow-$end-$arrow' id='arrow-$end-$arrow'/></td>\n";
                    }
                    print " <td class='end' name='end-total-$end' id='end-total-$end'>&nbsp;</td>\n";
                }
                print " <td class='hits' name='doz-hits-$dozen' id='doz-hits-$dozen'>&nbsp;</td>\n";
                print " <td class='Xs' name='doz-xs-$dozen' id='doz-xs-$dozen'>&nbsp;</td>\n";
                print " <td class='golds' name='doz-golds-$dozen' id='doz-golds-$dozen'>&nbsp;</td>\n";
                print " <td class='doz' name='doz-doz-$dozen' id='doz-doz-$dozen'>&nbsp;</td>\n";
                print " <td class='tot' name='doz-tot-$dozen' id='doz-tot-$dozen'>&nbsp;</td>\n";
                print "</tr>\n";
            }
        ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="14">Totals:</th>
                <td class="total-hits" name="total-hits" id="total-hits">&nbsp;</td>
                <td class="total-Xs" name="total-xs" id="total-xs">&nbsp;</td>
                <td class="total-golds" name="total-golds" id="total-golds">&nbsp;</td>
                <td>&nbsp;</td>
                <td class="total-total" id="total-total" name="total-total">&nbsp;</td>
            </tr>
        </tfoot>
    </table>
    <input type="submit" />
</form>
<table id="TenZoneChart">
  <td class="bar"><img id="tbar_X" src="gold.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_10" src="gold.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_9" src="gold.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_8" src="red.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_7" src="red.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_6" src="blue.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_5" src="blue.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_4" src="black.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_3" src="black.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_2" src="white.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_1" src="white.png" height="100" width="30"/></td>
  <td class="bar"><img id="tbar_M" src="green.png" height="100" width="30"/></td>
</table>
<table id="FiveZoneChart">
  <td class="bar"><img id="fbar_9" src="gold.png" height="100" width="50"/></td>
  <td class="bar"><img id="fbar_7" src="red.png" height="100" width="50"/></td>
  <td class="bar"><img id="fbar_5" src="blue.png" height="100" width="50"/></td>
  <td class="bar"><img id="fbar_3" src="black.png" height="100" width="50"/></td>
  <td class="bar"><img id="fbar_1" src="white.png" height="100" width="50"/></td>
  <td class="bar"><img id="fbar_M" src="green.png" height="100" width="50"/></td>
</table>
