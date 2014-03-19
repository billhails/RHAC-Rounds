function RHAC_ScoreViewer() {

    "use strict";

    var fast = 200,
        medium = 400,
        slow = 600,
        verySlow = 1000;

    function debug(msg) {
        if (window.console && window.console.log) {
            console.log(msg);
        }
    }

    function pleaseWait() {
        return "<p class='scorecard-wait'>please wait...</p>";
    }

    function makeCycleVisibility(scorecard) {
        var table = scorecard.find("div.scorecard-table"),
            graph = scorecard.find("div.scorecard-graph"),
            state = 'both';
        return function (e) {
            e.stopImmediatePropagation();
            switch (state) {
            case 'both':
                state = 'graph';
                table.slideUp(medium);
                break;
            case 'graph':
                state = 'table';
                table.slideDown(medium);
                graph.slideUp(medium);
                break;
            case 'table':
                state = 'both';
                graph.slideDown(medium);
                break;
            }
        };
    }

    function makePopulateScorecard(id) {
        var scorecard = jQuery('#scorecard-' + id),
            button = jQuery('#reveal-' + id);
        return function (result) {
            scorecard.unbind('click');
            button.unbind('click');
            scorecard.hide();
            scorecard.html(result.html);
            scorecard.show(slow);
            button.click(function () { scorecard.toggle(slow); });
            scorecard.click(makeCycleVisibility(scorecard));
        };
    }

    function doReveal() {
        var id =  jQuery(this).data('id');
        jQuery('#scorecard-' + id).hide().html(pleaseWait());
        jQuery('#scorecard-' + id).slideDown(slow);
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
        }
        return '';
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
    function () {
        if (jQuery('#rhac-scorecard-viewer')) {
            new RHAC_ScoreViewer();
        }
    }
);
