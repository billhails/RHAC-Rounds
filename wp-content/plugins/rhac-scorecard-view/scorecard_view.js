function RHAC_ScoreViewer() {

    function debug(msg) {
        if (window.console && window.console.log) {
            console.log(msg);
        }
    }

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
                    table.hide(400);
                    break;
                case 'graph':
                    state = 'table';
                    table.slideDown();
                    graph.hide(400);
                    break;
                case 'table':
                    state = 'both';
                    graph.slideDown();
                    break;
            }
        }
    }

    function makeSlideToggle(scorecard) {
        return function() {
            scorecard.slideToggle();
        }
    }

    function makePopulateScorecard(id) {
        scorecard = jQuery('#scorecard-' + id);
        button = jQuery('#reveal-' + id);
        return function(result) {
            scorecard.html(result.html);
            scorecard.unbind('click');
            button.unbind('click');
            button.click(makeSlideToggle(scorecard));
            scorecard.click(makeCycleVisibility(scorecard));
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
        jQuery('#scorecard-' + id).css('display', 'none').html(pleaseWait()).slideDown(1000);
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
        jQuery('#display-average').css('display', 'none').html(averageAndBest()).slideDown('slow');
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
                                '</td></tr>');
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
