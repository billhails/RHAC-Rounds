<h1>Score Cards</h1>

<form method="get" action="">
    <table>
        <tr><td>Archer</td><td colspan="2"><select name="archer"><?php
                    print "<option value=''>- - -</option>\n";
                    $archers = fetch('SELECT name FROM archer ORDER BY name');
                    foreach ($archers as $archer) {
                        print "<option value='$archer[name]'>"
                            . $archer["name"]
                            . "</option>\n";
                    }
                ?></select></td></tr>
        <tr><td>Round</td><td colspan="2"><select name="round" id="round"><?php
                print "<option value=''>- - -</option>\n";
                foreach (GNAS_Page::roundData() as $round) {
                    print "<option value='" . $round->getName() . "'>"
                    . $round->getName() . "</option>\n";
                }
                ?></select></td></tr>
        <tr><td>Date or Date Range</td>
            <td>
                <input type="text" name="lower-date" id="datepicker-lower"/>
            </td>
            <td>
                <input type="text" name="upper-date" id="datepicker-upper"/>
            </td>
        </tr>
    </table>
    <input type="submit" name="find-scorecard" value="Search" />
</form>
<hr/>
<form method="get" action="">
    <input type="submit" name="edit-scorecard" value="New" />
</form>
