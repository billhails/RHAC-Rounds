/**
 * This file provides JavaScript support for the Club Records Browser
 *
 * Apart from basic stuff like hiding and revealing the form, it
 * additionally makes an Ajax request when you click on a result
 * to see the scorecard, since loading all of the scorecards with
 * the original query would be very expensive and slow.
 */

/**
*
*  Base64 encode / decode
*  this was taken from http://www.webtoolkit.info/
*
**/
var Base64 = {

    // private property
    _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

    // public method for encoding
    encode : function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        input = Base64._utf8_encode(input);

        while (i < input.length) {
            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
            this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
            this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
        }
        return output;
    },

    // public method for decoding
    decode : function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;

        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

        while (i < input.length) {
            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));

            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;

            output = output + String.fromCharCode(chr1);

            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }
        }
        output = Base64._utf8_decode(output);
        return output;
    },

    // private method for UTF-8 encoding
    _utf8_encode : function (string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {
            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }
        }
        return utftext;
    },

    // private method for UTF-8 decoding
    _utf8_decode : function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;

        while ( i < utftext.length ) {
            c = utftext.charCodeAt(i);

            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            }
            else if((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i+1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            }
            else {
                c2 = utftext.charCodeAt(i+1);
                c3 = utftext.charCodeAt(i+2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }
        }
        return string;
    }
}

/**
 * this is the top-level function that runs when the page is loaded.
 */
function rhacRecordsExplorer() {
    'use strict';

    var version = "rhac-records-v1.0", persistance, tableStatePersistance, currentTableState;

    /*
     * each predefined report is just a set of
     * values for the form. selecting one
     * fills in the form with those values.
     *
     * The keys are the CSS selectors for the
     * form fields, used directly by jQuery to
     * find the field.
     */
    var predefined_reports = {

        'Personal Bests': {
            '.rhac-re-outdoor': [''],
            '#rhac-re-include-lapsed': [''],
            '#rhac-re-archer': '',
            '#rhac-re-age': '',
            '#rhac-re-gender': '',
            '#rhac-re-bow': '',
            '.rhac-re-single-round': ['Y'],
            '#rhac-re-round': '',
            '#rhac-re-seasons': '-',
            '#rhac-re-current-records': [''],
            '#rhac-re-old-records': [''],
            '#rhac-re-medals': [''],
            '#rhac-re-252': [''],
            '#rhac-re-personal-bests': ['Y'],
            '#rhac-re-handicap-improvements': [''],
            '#rhac-re-new-classifications': [''],
            '#rhac-re-reassessments': ['']
        },

        'Club Records': {
            '.rhac-re-outdoor': [''],
            '#rhac-re-include-lapsed': [''],
            '#rhac-re-archer': '',
            '#rhac-re-age': '',
            '#rhac-re-gender': '',
            '#rhac-re-bow': '',
            '.rhac-re-single-round': ['Y'],
            '#rhac-re-round': '',
            '#rhac-re-seasons': '-',
            '#rhac-re-current-records': ['Y'],
            '#rhac-re-old-records': [''],
            '#rhac-re-medals': [''],
            '#rhac-re-252': [''],
            '#rhac-re-personal-bests': [''],
            '#rhac-re-handicap-improvements': [''],
            '#rhac-re-new-classifications': [''],
            '#rhac-re-reassessments': ['']
        },

        '252 Awards': {
            '.rhac-re-outdoor': ['Y'],
            '#rhac-re-include-lapsed': [''],
            '#rhac-re-archer': '',
            '#rhac-re-age': '',
            '#rhac-re-gender': '',
            '#rhac-re-bow': '',
            '.rhac-re-single-round': ['N'],
            '#rhac-re-round': ':Gold 252|Silver 252|Bronze 252|Red 252|Blue 252|Black 252|White 252|Green 252',
            '#rhac-re-seasons': '-',
            '#rhac-re-current-records': [''],
            '#rhac-re-old-records': [''],
            '#rhac-re-medals': [''],
            '#rhac-re-252': ['Y'],
            '#rhac-re-personal-bests': [''],
            '#rhac-re-handicap-improvements': [''],
            '#rhac-re-new-classifications': [''],
            '#rhac-re-reassessments': ['']
        },

        'All Outdoor Handicaps and Classifications': {
            '.rhac-re-outdoor': ['Y'],
            '#rhac-re-include-lapsed': [''],
            '#rhac-re-archer': '',
            '#rhac-re-age': '',
            '#rhac-re-gender': '',
            '#rhac-re-bow': '',
            '.rhac-re-single-round': ['Y'],
            '#rhac-re-round': '',
            '#rhac-re-seasons': '-',
            '#rhac-re-current-records': [''],
            '#rhac-re-old-records': [''],
            '#rhac-re-medals': [''],
            '#rhac-re-252': [''],
            '#rhac-re-personal-bests': [''],
            '#rhac-re-handicap-improvements': ['Y'],
            '#rhac-re-new-classifications': ['Y'],
            '#rhac-re-reassessments': ['Y']
        },

        'All Indoor Handicaps and Classifications': {
            '.rhac-re-outdoor': ['N'],
            '#rhac-re-include-lapsed': [''],
            '#rhac-re-archer': '',
            '#rhac-re-age': '',
            '#rhac-re-gender': '',
            '#rhac-re-bow': '',
            '.rhac-re-single-round': ['Y'],
            '#rhac-re-round': '',
            '#rhac-re-seasons': '-',
            '#rhac-re-current-records': [''],
            '#rhac-re-old-records': [''],
            '#rhac-re-medals': [''],
            '#rhac-re-252': [''],
            '#rhac-re-personal-bests': [''],
            '#rhac-re-handicap-improvements': ['Y'],
            '#rhac-re-new-classifications': ['Y'],
            '#rhac-re-reassessments': ['Y']
        }

    };

    var all_reports = {};
    var all_states = {};

    var d = new Date();
    var year = d.getFullYear();
    var month = d.getMonth() + 1;
    var today_formatted = format_date(d);
    var last_week_formatted = format_date(new Date(d.getTime() - (7 * 24 * 60 * 60 * 1000)));

    function format_date(date) {
        var day = String(date.getDate());
        var month = String(date.getMonth() + 1);
        var year = String(date.getFullYear());
        if (day.length === 1) {
            day = "0" + day;
        }
        if (month.length === 1) {
            month = "0" + month;
        }
        return year + '/' + month + '/' + day;
    }

    /**
     * we declare a couple more predefined reports now that we know the date
     */
    predefined_reports["Last Week's Scores"] = {
        '.rhac-re-outdoor': [''],
        '#rhac-re-include-lapsed': [''],
        '#rhac-re-archer': '',
        '#rhac-re-age': '',
        '#rhac-re-gender': '',
        '#rhac-re-bow': '',
        '.rhac-re-single-round': ['Y'],
        '#rhac-re-round': '',
        '#rhac-re-lower-date': last_week_formatted,
        '#rhac-re-upper-date': today_formatted,
        '#rhac-re-current-records': [''],
        '#rhac-re-old-records': [''],
        '#rhac-re-medals': [''],
        '#rhac-re-252': [''],
        '#rhac-re-personal-bests': [''],
        '#rhac-re-handicap-improvements': [''],
        '#rhac-re-new-classifications': [''],
        '#rhac-re-reassessments': ['']
    };

    if (month >= 7) {
        predefined_reports["Indoor Scores " + String(year) + "-" + String(year + 1)] = {
            '.rhac-re-outdoor': ['N'],
            '#rhac-re-include-lapsed': [''],
            '#rhac-re-archer': '',
            '#rhac-re-age': '',
            '#rhac-re-gender': '',
            '#rhac-re-bow': '',
            '.rhac-re-single-round': ['Y'],
            '#rhac-re-round': '',
            '#rhac-re-seasons': String(year) + '/07/01-' + String(year + 1) + '/06/31',
            '#rhac-re-current-records': [''],
            '#rhac-re-old-records': [''],
            '#rhac-re-medals': [''],
            '#rhac-re-252': [''],
            '#rhac-re-personal-bests': [''],
            '#rhac-re-handicap-improvements': [''],
            '#rhac-re-new-classifications': [''],
            '#rhac-re-reassessments': ['']
        };
    }

    /**
     * we create predefined reports for every season back to 2012
     *
     * FIXME we might want to change this to be i.e. "the last 5 years" instead
     */
    while (year > 2012) {
        predefined_reports["Outdoor Scores " + String(year)] = {
            '.rhac-re-outdoor': ['Y'],
            '#rhac-re-include-lapsed': [''],
            '#rhac-re-archer': '',
            '#rhac-re-age': '',
            '#rhac-re-gender': '',
            '#rhac-re-bow': '',
            '.rhac-re-single-round': ['Y'],
            '#rhac-re-round': '',
            '#rhac-re-seasons': String(year) + '/01/01-' + String(year) + '/12/31',
            '#rhac-re-current-records': [''],
            '#rhac-re-old-records': [''],
            '#rhac-re-medals': [''],
            '#rhac-re-252': [''],
            '#rhac-re-personal-bests': [''],
            '#rhac-re-handicap-improvements': [''],
            '#rhac-re-new-classifications': [''],
            '#rhac-re-reassessments': ['']
        };
        predefined_reports["Indoor Scores " + String(year - 1) + "-" + String(year)] = {
            '.rhac-re-outdoor': ['N'],
            '#rhac-re-include-lapsed': [''],
            '#rhac-re-archer': '',
            '#rhac-re-age': '',
            '#rhac-re-gender': '',
            '#rhac-re-bow': '',
            '.rhac-re-single-round': ['Y'],
            '#rhac-re-round': '',
            '#rhac-re-seasons': String(year-1) + '/07/01-' + String(year) + '/06/31',
            '#rhac-re-current-records': [''],
            '#rhac-re-old-records': [''],
            '#rhac-re-medals': [''],
            '#rhac-re-252': [''],
            '#rhac-re-personal-bests': [''],
            '#rhac-re-handicap-improvements': [''],
            '#rhac-re-new-classifications': [''],
            '#rhac-re-reassessments': ['']
        };
        year--;
    }

    /*
     * if the page url was supplied with parameters, this function will fish them out.
     */
    function searchKeys() {
        var keys = {};
        if (window.location.search.length > 1) {
            var query = window.location.search.substring(1);
            var vars = query.split("&");
            for (var i=0;i<vars.length;i++) {
                var pair = vars[i].split("=");
                if (pair.length == 2) {
                    keys[pair[0]] = pair[1];
                }
            }
        }
        return keys;
    }

    /*
     * construct a url appending parameters from the current form values
     * (used by the "link to this report" code)
     */
    function formatQuery(keys) {
        var url = window.location.origin.concat(window.location.pathname);
        var pairs = [];
        jQuery.each(keys, function(k, v) {
            pairs.push(k.concat('=', v));
        });
        return url.concat('?', pairs.join('&'));
    }

    /*
     * convert the current form values to a string suitable for saving to local browser storage
     */
    function settingsToString() {
        return encodeURIComponent(Base64.encode(JSON.stringify(getCurrentReportSettings())));
    }

    /*
     * convert a string from local browser storage into keys and values,
     * and populate the form with them
     */
    function stringToSettings(string) {
        setFormFromReport(JSON.parse(Base64.decode(decodeURIComponent(string))));
    }

    /*
     * if the page was invoked with a 'report' parameter,
     * populate the form from it and run the query immediately.
     */
    function runReportFromQuery() {
        var keys = searchKeys();
        if (keys.hasOwnProperty("report")) {
            stringToSettings(keys.report);
            doSearch();
        }
    }

    /*
     * construct a url for the current query, including any report hash
     * and send the resulting huge url to tinyurl (via ajax) to get a
     * small url suitable for posting/sharing
     */
    function copyReportToLink() {
        var keys = searchKeys();
        keys["report"] = settingsToString();
        jQuery.ajax(
            {
                url: rhacRoundExplorerData.ajaxurl,
                type: 'GET',
                timeout: 30000,
                data: {
                    action: 'rhac_get_tinyurl',
                    url: formatQuery(keys)
                },
            }
        ).done(function (results) {
            jQuery('#rhac-re-link').val(results);
            jQuery('#rhac-re-tiny-url').dialog( "open" );
        });
    }

    /*
     * create handles for saving data to local browser storage
     */
    persistance = persist(version, '#rhac-re-quota-exceeded', '#rhac-re-old-browser');
    tableStatePersistance = persist(version.concat("-state"), '#rhac-re-quota-exceeded', '#rhac-re-old-browser');

    /* construct a date or date range */
    function makeDate(lower, upper) {
        if (lower === '') {
            if (upper === '') { return ''; }
            return upper;
        }
        if (upper === '') { return lower; }
        if (lower < upper) { return "[".concat(lower, ",", upper); }
        if (lower > upper) { return "[".concat(upper, ",", lower); }
        return lower;
    }

    /* perform the top level query as an Ajax request */
    function doSearch() {
        if (!jQuery('#rhac-re-results').hasClass('rhac-re')) {
            jQuery('#rhac-re-results').addClass('rhac-re');
        }
        jQuery('#rhac-re-results').html("<p>Please wait...</p>");
        jQuery.ajax(
            {
                url: rhacRoundExplorerData.ajaxurl,
                type: 'GET',
                timeout: 30000,
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
                },
                error: function(jqxhr, textStatus, errorThrown) {
                    alert("error: " + textStatus + " [" + errorThrown + "]");
                }
            }
        ).done(
            /* this function is called asynchronously when the results arrive */
            function(results) {
                jQuery('#rhac-re-results').removeClass('rhac-re');
                jQuery('#rhac-re-results').html(results);
                var colVisInitialized = false;
                var table = jQuery('#rhac-re-results-table').DataTable(
                    {
                        "columnDefs": [
                            { "orderable": false, "targets": 8 }
                        ],
                        stateSave: true,
                        stateSaveCallback: function(settings, data) {
                            if (colVisInitialized) {
                                console.log("stateSaveCallback setting current table state to %o", data);
                                currentTableState = data;
                            }
                            else {
                                console.log("stateSaveCallback ignoring current table state %o", data);
                            }
                        },
                        stateLoadCallback: function(settings) {
                            console.log("stateLoadCallback returning current table state %o", currentTableState);
                            return currentTableState;
                        }
                    }
                );

                /* hook in the function to make the requests for individual scorecards */
                jQuery('#rhac-re-results-table tbody').on('click', 'td.rhac-re-score-with-ends', function () {
                    var jQthis = jQuery(this);
                    var scorecard_id = jQthis.data('scorecard-id');
                    var tr = jQthis.parents('tr');
                    var row = table.row(tr);
                    /* we only make the scorecard request once */
                    if (row.child.isShown()) {
                        /* toggle visibility of the scorecard (hide it) */
                        row.child.hide();
                        jQthis.tooltip({content: "click to show scorecard"});
                    } else {
                        if (row.child() === undefined) {
                            /* the request for this scorecard has never been made */
                            row.child('<div class="rhac-re-scorecard" id="rhac-re-scorecard-' + scorecard_id + '">Please wait...</div>');
                            /* make an Ajax request for the scorecard */
                            jQuery.ajax(
                                {
                                    'url': rhacRoundExplorerData.ajaxurl,
                                    'type': 'GET',
                                    'timeout': 60000,
                                    'data': {
                                        'action': "rhac_get_one_scorecard",
                                        'scorecard_id': scorecard_id,
                                    },
                                    'error': function(obj, stat, err) { alert("error: [" + stat + "] [" + err + "]"); },
                                }
                            ).done(function(result) {
                                /* when we get the data, plug it in to the div
                                 * that we've already created under the result
                                 */
                                jQuery('#rhac-re-scorecard-' + scorecard_id).html(result.html);
                            });
                        }
                        /* in any case, the scorecard was hidden, so show it */
                        row.child.show();
                        jQthis.tooltip({content: "click to hide scorecard"});
                    }
                });
            }
        );
    }

    /*
    * read the current values from the form fields
    *
    * used to save the report for later re-use
    */
    function getCurrentReportSettings() {
        return {
            '.rhac-re-outdoor': [jQuery('.rhac-re-outdoor:checked').val()],
            '#rhac-re-include-lapsed': [jQuery('#rhac-re-include-lapsed:checked').val()],
            '#rhac-re-archer': jQuery('#rhac-re-archer').val(),
            '#rhac-re-age': jQuery('#rhac-re-age').val(),
            '#rhac-re-gender': jQuery('#rhac-re-gender').val(),
            '#rhac-re-bow': jQuery('#rhac-re-bow').val(),
            '.rhac-re-single-round': [jQuery('.rhac-re-single-round:checked').val()],
            '#rhac-re-round': jQuery('#rhac-re-round').val(),
            '#rhac-re-seasons': jQuery('#rhac-re-seasons').val(),
            '#rhac-re-lower-date': jQuery('#rhac-re-lower-date').val(),
            '#rhac-re-upper-date': jQuery('#rhac-re-upper-date').val(),
            '#rhac-re-current-records': [jQuery('#rhac-re-current-records:checked').val()],
            '#rhac-re-old-records': [jQuery('#rhac-re-old-records:checked').val()],
            '#rhac-re-medals': [jQuery('#rhac-re-medals:checked').val()],
            '#rhac-re-252': [jQuery('#rhac-re-252:checked').val()],
            '#rhac-re-personal-bests': [jQuery('#rhac-re-personal-bests:checked').val()],
            '#rhac-re-handicap-improvements': [jQuery('#rhac-re-handicap-improvements:checked').val()],
            '#rhac-re-new-classifications': [jQuery('#rhac-re-new-classifications:checked').val()],
            '#rhac-re-reassessments': [jQuery('#rhac-re-reassessments:checked').val()]
        };
    }

    /* fill the list of available reports */
    function populateReportMenu() {
        var names = [];
        var values = {};
        var states = {};
        var name;
        var cache = persistance.data();
        var stateCache = tableStatePersistance.data();
        var html = '';
        var index;
        for (name in predefined_reports) {
            values[name] = predefined_reports[name];
            names.push(name);
        }
        for (name in cache) {
            values[name] = cache[name];
            names.push(name);
        }
        for (name in stateCache) {
            all_states[name] = stateCache[name];
            console.log("populateReportMenu set all_states[%s] = %o", name, all_states[name]);
        }
        names.sort();
        for (index in names) {
            name = names[index];
            html = html.concat('<option value="', name, '">', name, '</option>');
            all_reports[name] = values[name];
        }
        jQuery('#rhac-re-report').html(html);
    }

    /* dynamically set the "report name" text of the popup modal identified by the selector */
    function dialog_param(selector, report_name) {
        jQuery(selector + ' span.rhac-re-report-name').html(report_name);
        jQuery(selector).dialog( "open" );
    }

    /* don't allow markup in report names (security) */
    function getSafeReportName() {
        return jQuery('#rhac-re-report-name').val().trim().replace(/[<>]/ig,"");
    }

    /* save the report after verifying that it is safe to do so */
    function saveReport() {
        var report_name = getSafeReportName();
        if (report_name === "") {
            jQuery('#rhac-re-enter-name').dialog( "open" );
            return;
        }
        if (predefined_reports.hasOwnProperty(report_name)) {
            jQuery('#rhac-re-cannot-save').dialog( "open" );
            return;
        }
        if (persistance.has(report_name)) {
            dialog_param('#rhac-re-confirm-replace', report_name);
        }
        else {
            do_saveReport(report_name);
        }
    }

    /* directly save the report, skipping verification */
    function do_saveReport(report_name) {
        var report_data = getCurrentReportSettings();
        persistance.set(report_name, report_data);
        console.log("do_saveReport persisting table state for [%s] as %o", report_name, currentTableState);
        tableStatePersistance.set(report_name, currentTableState);
        populateReportMenu();
        jQuery('#rhac-re-report').val(report_name);
        jQuery('#rhac-re-report').change();
        dialog_param('#rhac-re-confirm-saved', report_name);
    }

    /* delete a saved report, after verifying that it's sensible */
    function deleteReport() {
        var report_name = getSafeReportName();
        if (report_name === "") {
            jQuery('#rhac-re-enter-name').dialog( "open" );
            return;
        }
        if (predefined_reports.hasOwnProperty(report_name)) {
            jQuery('#rhac-re-cannot-delete').dialog( "open" );
            return;
        }
        if (!persistance.has(report_name)) {
            dialog_param('#rhac-re-report-nonexistant', report_name);
            return;
        }
        dialog_param('#rhac-re-confirm-delete', report_name);
    }

    /* set the actual dates from the selected seasons */
    function changeDates() {
        var season = jQuery('#rhac-re-seasons').val();
        if (season !== '') {
            var season_dates = season.split('-');
            jQuery('#rhac-re-lower-date').val(season_dates[0]);
            jQuery('#rhac-re-upper-date').val(season_dates[1]);
        }
    }

    /* change the list of available seasons depending on outdoor indoor or both being selected */
    function changeSeasonList() {
        var outdoor = jQuery('.rhac-re-outdoor:checked').val();
        if (outdoor === "Y") {
            jQuery('#rhac-re-seasons').html(jQuery('#rhac-re-outdoor-seasons').html());
        }
        else if (outdoor === "N") {
            jQuery('#rhac-re-seasons').html(jQuery('#rhac-re-indoor-seasons').html());
        }
        else {
            jQuery('#rhac-re-seasons').html(jQuery('#rhac-re-all-seasons').html());
        }
        jQuery('#rhac-re-seasons').val('-');
        jQuery('#rhac-re-seasons').change();
    }

    /* change the available rounds depending on outdoor/indoor/both and round/round-family selections */
    function changeRoundList() {
        var outdoor = jQuery('.rhac-re-outdoor:checked').val();
        var single_round = jQuery('.rhac-re-single-round:checked').val();
        if (outdoor === "Y") {
            if (single_round === "Y") {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-outdoor-rounds').html());
            }
            else {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-outdoor-families').html());
            }
        }
        else if (outdoor === "N") {
            if (single_round === "Y") {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-indoor-rounds').html());
            }
            else {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-indoor-families').html());
            }
        }
        else {
            if (single_round === "Y") {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-all-rounds').html());
            }
            else {
                jQuery('#rhac-re-round').html(jQuery('#rhac-re-all-families').html());
            }
        }
    }

    /* toggle between all and current members depending on the "include lapsed archers" choice */
    function toggleArcherList() {
        if (jQuery('#rhac-re-include-lapsed').prop('checked')) {
            jQuery('#rhac-re-archer').html(jQuery('#rhac-re-all-archers').html());
        }
        else {
            jQuery('#rhac-re-archer').html(jQuery('#rhac-re-current-archers').html());
        }
    }

    /* populate the form from values in the currently selected report */
    function setForm() {
        var name = jQuery('#rhac-re-report').val();
        var report = all_reports[name];
        currentTableState = all_states[name];
        console.log("setForm setting currentTableState from all_states[%s] to %o", name, currentTableState);
        setFormFromReport(report);
        jQuery('#rhac-re-report-name').val(name);
    }

    /* populate the form directly from argument report data */
    function setFormFromReport(report) {
        for (var selector in report) {
            jQuery(selector).val(report[selector]);
            jQuery(selector).change();
        }
    }

    /**
     * add basic behaviour to the initially hidden popup modal dialog
     */
    jQuery('.rhac-re-simple-dialog').dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            OK: function () {
                jQuery(this).dialog( "close" );
            }
        }
    });

    /*
     * add behaviour to the 'confirm replace saved report' popup
     */
    jQuery('#rhac-re-confirm-replace').dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            Confirm: function() {
                jQuery(this).dialog( "close" );
                // this is horrible
                var report_name = jQuery(this).find('span.rhac-re-report-name').html();
                do_saveReport(report_name);
            },
            Cancel: function() {
                jQuery(this).dialog( "close" );
            }
        }
    });

    /**
     * add behaviour to the 'confirm delete saved report' modal
     */
    jQuery('#rhac-re-confirm-delete').dialog({
        autoOpen: false,
        modal: true,
        buttons: {
            Confirm: function() {
                jQuery(this).dialog( "close" );
                // this is horrible
                var report_name = jQuery(this).find('span.rhac-re-report-name').html();
                persistance.remove(report_name);
                tableStatePersistance.remove(report_name);
                populateReportMenu();
                dialog_param('#rhac-re-confirm-deleted', report_name);
            },
            Cancel: function() {
                jQuery(this).dialog( "close" );
            }
        }
    });

    /*
     * attach the previously declared functions
     * to the various buttons and form values
     */
    jQuery('#rhac-re-save-report').click(saveReport);
    jQuery('#rhac-re-delete-report').click(deleteReport);
    jQuery('#rhac-re-seasons').change(changeDates);
    jQuery('.rhac-re-outdoor').change(changeSeasonList);
    jQuery('.rhac-re-outdoor').change(changeRoundList);
    jQuery('.rhac-re-single-round').change(changeRoundList);
    jQuery('#rhac-re-include-lapsed').change(toggleArcherList);
    jQuery('.rhac-re-date').datepicker({ dateFormat: "yy/mm/dd" });
    jQuery('#rhac-re-run-report').click(doSearch);
    jQuery('#rhac-re-get-link').click(copyReportToLink);
    jQuery('#rhac-re-run-report').button( { icons: { primary: "ui-icon-search" } } );
    jQuery('#rhac-re-get-link').button( { icons: { primary: "ui-icon-link" } } );
    jQuery('#rhac-re-edit-report').button( { icons: { primary: "ui-icon-triangle-1-e" } } );
    jQuery('#rhac-re-save-report').button( { icons: { primary: "ui-icon-disk" } } );
    jQuery('#rhac-re-delete-report').button( { icons: { primary: "ui-icon-trash" } } );
    jQuery('a.rhac-re-help').button( { icons: { primary: "ui-icon-help" } } );
    jQuery('.rhac-re-radios').buttonset();
    /* behaviour for the pin toggle */
    jQuery('.rhac-re-checkbox').button( { icons: { primary: "ui-icon-pin-w" } } );
    jQuery('.rhac-re-checkbox').change( function () {
        var b = jQuery(this);
        var icons = { primary: "" };
        if (b.prop("checked")) {
            icons.primary = "ui-icon-pin-s";
        } else {
            icons.primary = "ui-icon-pin-w";
        }
        b.button("option", "icons", icons);
    });

    /* get all currently available reports */
    populateReportMenu();

    /* populate the form from the currently selected or default report */
    setForm();

    /* add a trigger to re-populate the form whenever the currently selected report changes */
    jQuery('#rhac-re-report').change(setForm);

    /* add a trigger to toggle visibility of the form */
    jQuery('#rhac-re-edit-report').click(function () {
        jQuery('#rhac-re-moreform').toggle(0);
        var b = jQuery(this);
        var icons = b.button("option", "icons");
        if (icons.primary == "ui-icon-triangle-1-e") {
            icons.primary = "ui-icon-triangle-1-s";
        } else {
            icons.primary = "ui-icon-triangle-1-e";
        }
        b.button("option", "icons", icons);
    });
    /*
     * if the page was supplied with parameters, we may run the report immediately
     */
    runReportFromQuery();
}

/**
 * This call to jQuery causes rhacRecordsExplorer() above
 * to execute after the html document has fully loaded
 */
jQuery(
    function () {
        "use strict";
        /* add tooltips to the query form */
        jQuery('#rhac-re-main').tooltip();
        if (jQuery('#rhac-re-results')[0]) {
            rhacRecordsExplorer();
        }
    }
);
