<h1>Search Results</h1>
<table>
<?php
    foreach ($search_results as $result) {
        print '<tr><td>';
        print "$result[archer], $result[bow], $result[round], $result[date]";
        print '</td><td>';
        print "<form method='get' action=''>";
        print "<input type='hidden' name='scorecard-id' value='$result[id]' />";
        print "<input type='submit' name='edit-scorecard' value='Edit' />";
        print "</form>";
        print "</td></tr>\n";
    }
?>
</table>
<a href="">Home</a>
