function RHAC_ScoreViewer() {

    function pleaseWait() {
        return "<p class='scorecard-wait'>please wait...</p>";
    }

    // TODO animate
    function makeCycleVisibility(scorecard) {
        var table = scorecard.find("div.scorecard-table");
        var graph = scorecard.find("div.scorecard-graph");
        var state = 'both';
        return function() {
            switch (state) {
                case 'both':
                    state = 'graph';
                    table.css('display', 'none');
                    graph.css('display', 'block');
                    break;
                case 'graph':
                    state = 'table';
                    table.css('display', 'block');
                    graph.css('display', 'none');
                    break;
                case 'table':
                    state = 'both';
                    table.css('display', 'block');
                    graph.css('display', 'block');
                    break;
            }
        }
    }

    function makePopulateScorecard(id) {
        return function(result) {
            scorecard = jQuery('#scorecard-' + id);
            scorecard.html(result.html).slideDown('fast');
            reveal = jQuery('#reveal-' + id);
            scorecard.unbind('click');
            reveal.unbind('click');
            reveal.click(function() {scorecard.slideToggle()});
            scorecard.click(makeCycleVisibility(scorecard);
        };
    }

    function doReveal() {
        id =  jQuery(this).data('id');
        jQuery.ajax(
            {
                url: rhacScorecardData.ajaxurl,
                type: 'GET',
                timeout: 100000,
                data: {
                    action: 'rhac_get_one_scorecard',
                    scorecard_id: id
                }
            }
        ).done(makePopulateScorecard(id));
        jQuery('#scorecard-' + id).html(pleaseWait()).slideDown('slow');
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
        jQuery('#results').html(results);
        jQuery('#display-average').html(averageAndBest()).slideDown('slow');
        jQuery('button.reveal').click(doReveal);
    }

    function doSearch() {
        jQuery.ajax(
            {
                url: rhacScorecardData.ajaxurl,
                type: 'GET',
                timeout: 100000,
                data: {
                    action: 'rhac_get_scorecards',
                    archer: jQuery('#archer').val(),
                    round: jQuery('#round').val(),
                    bow: jQuery('#bow').val()
                }
            }
        ).done(populateResults);
        jQuery('#results').html('<tr><td colspan="9">' +
                                pleaseWait() +
                                '</td></tr>').slideDown('slow');
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
