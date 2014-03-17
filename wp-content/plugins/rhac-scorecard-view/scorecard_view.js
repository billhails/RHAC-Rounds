function RHAC_ScoreViewer() {

    function pleaseWait() {
        return "<p class='scorecard-wait'>please wait...</p>";
    }

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
            jQuery('#reveal-' + id).click(
                makeToggleVisibility('#scorecard-' + id));
        };
    }

    function doReveal() {
        id =  jQuery(this).data('id');
        jQuery('#scorecard-' + id).html(pleaseWait());
        jQuery.ajax(
            {
                url: rhacScorecardData.ajaxurl,
                type: 'GET',
                timeout: 20000,
                data: {
                    action: 'rhac_get_one_scorecard',
                    scorecard_id: id
                }
            }
        ).done(makePopulateScorecard(id));
    }

    function averageAndBest() {
        if (jQuery('#first-scorecard').length &&
            jQuery('#first-scorecard').data('best')) {
            return '<dl><dt>Average score</dt><dd>' +
                   jQuery('#first-scorecard').data('average') +
                   '</dd><dt>Best score</dt><dd>' +
                   jQuery('#first-scorecard').data('best') +
                   '</dd></dl>';
        } else {
            return '';
        }
    }

    function populateResults(results) {
        // alert("DEBUG\ngot response with length " + String(results.length));
        jQuery('#results').html(results);
        jQuery('#display-average').html(averageAndBest());
        jQuery('button.reveal').click(doReveal);
    }

    function doSearch() {
        jQuery('#results').html('<tr><td colspan="9">' +
                                pleaseWait() +
                                '</td></tr>');
        jQuery.ajax(
            {
                url: rhacScorecardData.ajaxurl,
                type: 'POST',
                timeout: 20000,
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
