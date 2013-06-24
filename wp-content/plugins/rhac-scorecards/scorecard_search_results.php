<h1>Search Results</h1>
<ul>
<?php
    foreach ($search_results as $result) {
        print '<li>';
        print "$result[archer] $result[bow] $result[round] $result[date]";
        print "<form method='get' action=''>";
        print "<input type='hidden' name='scorecard-id' value='$result[id]' />";
        print "<input type='submit' name='edit-scorecard' value='Edit'>Edit</input>";
        print "</form>";
        print "</li>";
    }
?>
</ul>
<a href="">Home</a>
