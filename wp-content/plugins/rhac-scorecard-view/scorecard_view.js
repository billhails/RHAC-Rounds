function RHAC_ScoreViewer() {

    function debug(msg) {
        if (window.console && window.console.log) {
            console.log(msg);
        }
    }

    function pleaseWait() {
        return "<p class='scorecard-wait'>please wait...</p>";
    }

    function makeCycleVisibility(scorecard) {
        var table = scorecard.find("div.scorecard-table");
        var graph = scorecard.find("div.scorecard-graph");
        var state = 'both';
        return function() {
            switch (state) {
                case 'both':
                    state = 'graph';
                    table.slideUp(400);
                    break;
                case 'graph':
                    state = 'table';
                    table.slideDown(400);
                    graph.slideUp(400);
                    break;
                case 'table':
                    state = 'both';
                    graph.slideDown(400);
                    break;
            }
        }
    }

    function makePopulateScorecard(id) {
        var scorecard = jQuery('#scorecard-' + id);
        var button = jQuery('#reveal-' + id);
        return function(result) {
            scorecard.unbind('click');
            button.unbind('click');
            scorecard.hide();
            scorecard.html(result.html);
            scorecard.show(400);
            button.click(function () { scorecard.toggle(400) });
            scorecard.click(makeCycleVisibility(scorecard));
        };
    }

    function doReveal() {
        var id =  jQuery(this).data('id');
        jQuery('#scorecard-' + id).hide().html(pleaseWait());
        jQuery('#scorecard-' + id).slideDown(400);
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
        jQuery('#display-average').slideUp();
        jQuery('#display-average').html('');
        jQuery('#results').html('<tr><td colspan="9">' +
                                pleaseWait() +
                                '</td></tr>');
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
