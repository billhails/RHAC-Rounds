function rhacRecordsExplorer() {

    function doScoreSearch() {
        jQuery.ajax(
            {
                url: rhacRoundExplorerData.ajaxurl,
                type: 'GET',
                timeout: 1000,
                data: {
                    action: 'rhac_display_results',
                    archer: jQuery('#scores-archer').val(),
                    outdoor: jQuery('#scores-season:checked').val(),
                    date: jQuery('#scores-date').val(),
                    reassessment: 'N'
                }
            }
        ).done(
            function(results) {
                jQuery('#score-search-results').html(results);
            }
        );
    }

    function doClubRecordsSearch() {
        var record = jQuery('#club-records-current').is(':checked') ? 'current' : '!N';
        jQuery.ajax(
            {
                url: rhacRoundExplorerData.ajaxurl,
                type: 'GET',
                timeout: 1000,
                data: {
                    action: 'rhac_display_results',
                    archer: jQuery('#club-records-archer').val(),
                    outdoor: jQuery('#club-records-season:checked').val(),
                    club_record: record,
                    reassessment: 'N'
                }
            }
        ).done(
            function(results) {
                jQuery('#club-records-search-results').html(results);
            }
        );
    }

    jQuery('#scores-search-button').click(doScoreSearch);
    jQuery('#club-records-search-button').click(doClubRecordsSearch);
    jQuery('#scores-date').datepicker({ dateFormat: "yy/mm/dd" });
}

jQuery(
    function() {
        if (jQuery('#search-results')) {
            jQuery('#tabs').tabs();
            rhacRecordsExplorer();
        }
    }
);
