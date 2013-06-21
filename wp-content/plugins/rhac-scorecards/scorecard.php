<html>
    <head>
    <link rel="stylesheet" href="scorecard.css"/>
    <script src="jquery-2.0.2.min.js" type="text/javascript"></script>
    <script src="scorecard.js" type="text/javascript"></script>
    </head>
    <body>
        <h1>Prototype for the Score Cards</h1>
        <dl id="scorecard-data">
            <!-- css can hide this -->
            <!-- all data for js to read will be here -->
        </dl>
        <form method="post" action="">
            <input type="hidden" name="scorecard-id" value="0"/>
            <table>
                <thead>
                    <tr>
                        <th colspan="3">Archer</th>
                        <td colspan="7"><input type="text" name="archer"/></td>
                        <th colspan="3">Bow</th>
                        <td colspan="6"><input type="text" name="bow"/></td>
                    </tr>
                    <tr>
                        <th colspan="3">Round</th>
                        <td colspan="7"><input type="text" name="round"/></td>
                        <th colspan="3">Date</th>
                        <td colspan="6"><input type="text" name="date"/></td>
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
                <tbody>
                <?php

                    $end = 0;
                    for ($dozen = 1; $dozen < 9; ++$dozen) {
                        print '<tr>' . "\n";
                        foreach (array('odd', 'even') as $pos) {
                            $end++;
                            for ($arrow = 1; $arrow < 7; ++$arrow) {
                                print '<td>'
                                    . '<input type="text"'
                                    . ' class="score"'
                                    . ' name="arrow-'
                                    . $end
                                    . '-'
                                    . $arrow
                                    . '"/>'
                                    . '</td>' . "\n";
                            }
                            print '<td class="end" name="end-total-'
                                . $end
                                . '">&nbsp;</td>' . "\n";
                        }
                        print '<td class="hits" name="doz-hits-'
                            . $dozen . '">&nbsp;</td>';
                        print '<td class="Xs" name="doz-xs-'
                            . $dozen . '">&nbsp;</td>';
                        print '<td class="golds" name="doz-golds-'
                            . $dozen . '">&nbsp;</td>';
                        print '<td class="doz" name="doz-doz-'
                            . $dozen . '">&nbsp;</td>';
                        print '<td class="tot" name="doz-tot-'
                            . $dozen . '">&nbsp;</td>';
                        print "\n";
                        print "</tr>\n";
                    }
                ?>
                    <tr>
                        <th colspan="14">Totals:</th>
                        <td class="total-hits" name="total-hits">&nbsp;</td>
                        <td class="total-Xs" name="total-xs">&nbsp;</td>
                        <td class="total-golds" name="total-golds">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="total-total" name="total-total">&nbsp;</td>
                    </tr>
                </tbody>
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

    </body>
</html>
