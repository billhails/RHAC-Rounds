<?php
/*
<html>
    <head>
    <link rel="stylesheet" href="scorecard.css"/>
    <script src="jquery-2.0.2.min.js" type="text/javascript"></script>
    <link href="jquery-ui-1.10.3.custom/css/ui-lightness/jquery-ui-1.10.3.custom.css" rel="stylesheet"/>
    <!--
    <script src="jquery-ui-1.10.3.custom/js/jquery-1.9.1.js"></script>
    -->
    <script src="jquery-ui-1.10.3.custom/js/jquery-ui-1.10.3.custom.js"></script>
    <script src="scorecard.js" type="text/javascript"></script>
    </head>
    <body>
    <?php
    */
        function plugin_dir_path() { return "./";  }
        function plugin_dir_url() { return "./";  }
        // $_GET['find-scorecard'] = '1';
        $_GET['id'] = '1';
        include "scorecard.php";
        // RHAC_Scorecards::getInstance()->topLevel();
        /*
    ?>
</body>
</html>
*/
