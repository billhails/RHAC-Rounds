function RHAC_ScoreViewer() {

    function makePopulateScorecard(id) {
        return function(result) {
            jQuery('#scorecard-' + id).html(result.html);
        };
    }

    function doReveal() {
        id =  jQuery(this).data('id');
        round =  jQuery(this).data('round');
        jQuery('#scorecard-' + id).html("<p class='scorecard-wait'>please wait...</p>");
        jQuery.ajax(
            {
                url: rhacScorecardData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rhac_get_one_scorecard',
                    scorecard_id: id,
                    round: round
                }
            }
        ).done(makePopulateScorecard(id));
    }

    function populateResults(results) {
        jQuery('#results').html(results);
        jQuery('button.reveal').click(doReveal);
    }

    function doSearch() {
        jQuery('#results').html('<tr><td colspan="9"><p class="scorecard-wait">please wait...</p></td></tr>');
        jQuery.ajax(
            {
                url: rhacScorecardData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'rhac_get_scorecards',
                    archer: jQuery('#archer').val(),
                    round: jQuery('#round').val()
                }
            }
        ).done(populateResults);
    }

    function setUp() {
        jQuery('#search-button').click(doSearch);
    }

    setUp();
}

jQuery(
    function() {
        if (jQuery('#rhac-scorecard-viewer')) {
            new RHAC_ScoreViewer();
        }
    }
);
