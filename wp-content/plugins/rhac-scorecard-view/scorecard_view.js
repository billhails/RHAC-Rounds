function RHAC_ScoreViewer() {

    function makeToggleVisibility(key) {
        var scorecard = jQuery(key);
        var visible = scorecard.css('display');
        return function() {
            if (scorecard.css('display') == 'none') {
                scorecard.css('display', visible);
            } else {
                scorecard.css('display', 'none');
            }
        }
    }

    function makePopulateScorecard(id) {
        return function(result) {
            jQuery('#scorecard-' + id).html(result.html);
            jQuery('#reveal-' + id).unbind('click');
            jQuery('#reveal-' + id).click(makeToggleVisibility('#scorecard-' + id));
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
        if (jQuery('#first-scorecard').length && jQuery('#first-scorecard').data('best')) {
            jQuery('#display-average').html(
                '<dl><dt>Average score</dt><dd>' +
                jQuery('#first-scorecard').data('average') +
                '</dd><dt>Best score</dt><dd>' +
                jQuery('#first-scorecard').data('best') +
                '</dd></dl>');
        } else {
            jQuery('#display-average').html('');
        }
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
                    round: jQuery('#round').val(),
                    bow: jQuery('#bow').val()
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
