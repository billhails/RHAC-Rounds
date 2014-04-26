function rhacRecordsExplorer() {

    function makeDate(lower, upper) {
        if (lower == '') {
            if (upper == '') {
                return '';
            }
            else {
                return upper;
            }
        }
        else {
            if (upper == '') {
                return lower;
            }
            else {
                if (lower < upper) {
                    return "[".concat(lower, ",", upper);
                }
                else if (lower > upper) {
                    return "[".concat(upper, ",", lower);
                }
                else {
                    return lower;
                }
            }
        }
    }

    function doSearch() {
        jQuery('#rhac-re-results').html("<p>Please wait...</p>");
        jQuery.ajax(
            {
                url: rhacRoundExplorerData.ajaxurl,
                type: 'GET',
                timeout: 1000,
                data: {
                    action: 'rhac_display_results',
                    outdoor: jQuery('.rhac-re-outdoor:checked').val(),
                    archer: jQuery('#rhac-re-archer').val(),
                    category: jQuery('#rhac-re-age').val(),
                    gender: jQuery('#rhac-re-gender').val(),
                    bow: jQuery('#rhac-re-bow').val(),
                    round: jQuery('#rhac-re-round').val(),
                    date: makeDate(jQuery('#rhac-re-lower-date').val(), jQuery('#rhac-re-upper-date').val()),
                    current_records: jQuery('#rhac-re-current-records:checked').val(),
                    old_records: jQuery('#rhac-re-old-records:checked').val(),
                    medals: jQuery('#rhac-re-medals:checked').val(),
                    two_five_two_awards: jQuery('#rhac-re-252:checked').val(),
                    personal_bests: jQuery('#rhac-re-personal-bests:checked').val(),
                    handicap_improvements: jQuery('#rhac-re-handicap-improvements:checked').val(),
                    new_classifications: jQuery('#rhac-re-new-classifications:checked').val(),
                    include_reassessment: jQuery('#rhac-re-reassessments:checked').val()
                }
            }
        ).done(
            function(results) {
                jQuery('#rhac-re-results').html(results);
            }
        );
    }
    
    function changeDates() {
        var season = jQuery('#rhac-re-seasons').val();
        if (season != '') {
            // alert("changeDates " + season_dates[0] + " - " + season_dates[1]);
            var season_dates = season.split('-');
            jQuery('#rhac-re-lower-date').val(season_dates[0]);
            jQuery('#rhac-re-upper-date').val(season_dates[1]);
        }
    }

    function changeSeasonList() {
        var outdoor = jQuery('.rhac-re-outdoor:checked').val();
        if (outdoor == "Y") {
            jQuery('#rhac-re-seasons').html(jQuery('#rhac-re-outdoor-seasons').html());
        }
        else if (outdoor == "N") {
            jQuery('#rhac-re-seasons').html(jQuery('#rhac-re-indoor-seasons').html());
        }
        else {
            jQuery('#rhac-re-seasons').html(jQuery('#rhac-re-all-seasons').html());
        }
    }

    function changeRoundList() {
        var outdoor = jQuery('.rhac-re-outdoor:checked').val();
        var single_round = jQuery('.rhac-re-single-round:checked').val();
        if (outdoor == "Y") {
            if (single_round == "Y") {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-outdoor-rounds').html());
            }
            else {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-outdoor-families').html());
            }
        }
        else if (outdoor == "N") {
            if (single_round == "Y") {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-indoor-rounds').html());
            }
            else {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-indoor-families').html());
            }
        }
        else {
            if (single_round == "Y") {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-all-rounds').html());
            }
            else {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-all-families').html());
            }
        }
    }

    function toggleArcherList() {
        if (jQuery('#rhac-re-include-lapsed').prop('checked')) {
            jQuery('#rhac-re-archer').html(jQuery('#rhac-re-all-archers').html());
        }
        else {
            jQuery('#rhac-re-archer').html(jQuery('#rhac-re-current-archers').html());
        }
    }

    jQuery('#rhac-re-seasons').change(changeDates);
    jQuery('.rhac-re-outdoor').change(changeSeasonList);
    jQuery('.rhac-re-outdoor').change(changeRoundList);
    jQuery('.rhac-re-single-round').change(changeRoundList);
    jQuery('#rhac-re-include-lapsed').change(toggleArcherList);
    jQuery('.rhac-re-date').datepicker({ dateFormat: "yy/mm/dd" });
    jQuery('#rhac-re-run-report').click(doSearch);
}

jQuery(
    function() {
        if (jQuery('#rhac-re-results')[0]) {
            rhacRecordsExplorer();
        }
    }
);
