function rhacRecordsExplorer() {

    function persist(prefix) {
        var cache = localStorage.getItem(prefix);
        if (cache == null) {
            cache = {};
        }
        else {
            cache = JSON.parse(cache);
        }

        function stash() {
            try {
                localStorage.setItem(prefix, JSON.stringify(cache));
            } catch(e) {
                if (e == QUOTA_EXCEEDED_ERR) {
                    alert('Quota Exceeded, please delete some old reports first');
                }
            }
        }

        this.set = function(key, value) {
            cache[key] = value;
            stash();
        }

        this.get = function(key) {
            return cache[key];
        }

        this.has = function(key) {
            return cache.hasOwnProperty(key);
        }

        this.data = function() {
            return cache;
        }

        this.remove = function(key) {
            delete cache[key];
            stash();
        }
    }

    function semi_persist() {
        var cache = {};

        this.set = function(key, value) {
            cache[key] = value;
        }

        this.get = function(key) {
            return cache[key];
        }

        this.has = function(key) {
            return cache.hasOwnProperty(key);
        }

        this.data = function() {
            return cache;
        }

        this.remove = function(key) {
            delete cache[key];
        }
    }

    var version = "rhac-records-v1.0";

    var persistance;
    if (typeof(Storage) === "undefined") {
        alert("You seem to have a very old browser that does not support saving reports, please upgrade!");
        persistance = new semi_persist();
    }
    else {
        persistance = new persist(version);
    }

    // TODO - no hard-coded dates
    var predefined_reports = {
        'Outdoor Scores 2014': {
            '.rhac-re-outdoor': ['Y'],
            '#rhac-re-include-lapsed': [''],
            '#rhac-re-archer': '',
            '#rhac-re-age': '',
            '#rhac-re-gender': '',
            '#rhac-re-bow': '',
            '.rhac-re-single-round': ['Y'],
            '#rhac-re-round': '',
            '#rhac-re-seasons': '2014/01/01-2014/12/31',
            '#rhac-re-current-records': [''],
            '#rhac-re-old-records': [''],
            '#rhac-re-medals': [''],
            '#rhac-re-252': [''],
            '#rhac-re-personal-bests': [''],
            '#rhac-re-handicap-improvements': [''],
            '#rhac-re-new-classifications': [''],
            '#rhac-re-reassessments': [''],
        },

        'Indoor Scores 2013-2014': {
            '.rhac-re-outdoor': ['N'],
            '#rhac-re-include-lapsed': [''],
            '#rhac-re-archer': '',
            '#rhac-re-age': '',
            '#rhac-re-gender': '',
            '#rhac-re-bow': '',
            '.rhac-re-single-round': ['Y'],
            '#rhac-re-round': '',
            '#rhac-re-seasons': '2013/06/01-2014/05/31',
            '#rhac-re-current-records': [''],
            '#rhac-re-old-records': [''],
            '#rhac-re-medals': [''],
            '#rhac-re-252': [''],
            '#rhac-re-personal-bests': [''],
            '#rhac-re-handicap-improvements': [''],
            '#rhac-re-new-classifications': [''],
            '#rhac-re-reassessments': [''],
        },

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
            '#rhac-re-reassessments': [''],
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
            '#rhac-re-reassessments': [''],
        },

        '252 Awards': {
            '.rhac-re-outdoor': [''],
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
            '#rhac-re-reassessments': [''],
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
            '#rhac-re-reassessments': ['Y'],
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
            '#rhac-re-reassessments': ['Y'],
        },

    };

    var all_reports = {};

    function makeDate(lower, upper) {
        if (lower == '') {
            if (upper == '') { return ''; }
            else { return upper; }
        }
        else {
            if (upper == '') { return lower; }
            else {
                if (lower < upper) { return "[".concat(lower, ",", upper); }
                else if (lower > upper) { return "[".concat(upper, ",", lower); }
                else { return lower; }
            }
        }
    }

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
            function(results) {
                jQuery('#rhac-re-results').removeClass('rhac-re');
                jQuery('#rhac-re-results').html(results);
                jQuery('#rhac-re-results-table').dataTable(
                    {
                        "bJQueryUI": true,
                    }
                );
            }
        );
    }

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
            '#rhac-re-reassessments': [jQuery('#rhac-re-reassessments:checked').val()],
        };
    }

    function saveReport() {
        var report_name = jQuery('#rhac-re-report-name').val().trim();
        if (report_name == "") {
            alert("please enter a report name first");
            return;
        }
        if (predefined_reports.hasOwnProperty(report_name)) {
            alert("You can't change a predefined report, edit the report name first and try again.");
            return;
        }
        if (persistance.has(report_name)) {
            if (!confirm("are you sure you want to replace your \"" + report_name + "\" report?")) {
                return;
            }
        }
        report_data = getCurrentReportSettings();
        persistance.set(report_name, report_data);
        populateReportMenu();
        jQuery('#rhac-re-report').val(report_name);
        jQuery('#rhac-re-report').change();
        alert("report \"" + report_name + "\" saved");
    }

    function deleteReport() {
        var report_name = jQuery('#rhac-re-report-name').val().trim();
        if (report_name == "") {
            alert("please enter a report name first");
            return;
        }
        if (predefined_reports.hasOwnProperty(report_name)) {
            alert("You can't delete a predefined report.");
            return;
        }
        if (persistance.has(report_name)) {
            if (!confirm("are you sure you want to delete your \"" + report_name + "\" report?")) {
                return;
            }
        } else {
            alert("That report doesn't exist");
            return;
        }
        persistance.remove(report_name);
        populateReportMenu();
        alert("report \"" + report_name + "\" deleted");
    }

    function changeDates() {
        var season = jQuery('#rhac-re-seasons').val();
        if (season != '') {
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
        jQuery('#rhac-re-seasons').val('-');
        jQuery('#rhac-re-seasons').change();
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

    function setForm() {
        var name = jQuery('#rhac-re-report').val();
        var report = all_reports[name];
        for (var selector in report) {
            jQuery(selector).val(report[selector]);
            jQuery(selector).change();
        }
        jQuery('#rhac-re-report-name').val(name);
    }

    function populateReportMenu() {
        var names = [];
        var values = {};
        for (var name in predefined_reports) {
            values[name] = predefined_reports[name];
            names.push(name);
        }
        var cache = persistance.data();
        for (name in cache) {
            values[name] = cache[name];
            names.push(name);
        }
        names.sort();
        var html = '';
        for (var index in names) {
            var name = names[index];
            html = html.concat('<option value="', name, '">', name, '</option>');
            all_reports[name] = values[name];
        }
        jQuery('#rhac-re-report').html(html);
    }

    jQuery('#rhac-re-save-report').click(saveReport);
    jQuery('#rhac-re-delete-report').click(deleteReport);
    jQuery('#rhac-re-seasons').change(changeDates);
    jQuery('.rhac-re-outdoor').change(changeSeasonList);
    jQuery('.rhac-re-outdoor').change(changeRoundList);
    jQuery('.rhac-re-single-round').change(changeRoundList);
    jQuery('#rhac-re-include-lapsed').change(toggleArcherList);
    jQuery('.rhac-re-date').datepicker({ dateFormat: "yy/mm/dd" });
    jQuery('#rhac-re-run-report').click(doSearch);
    populateReportMenu();
    setForm();
    jQuery('#rhac-re-report').change(setForm);
    jQuery('#rhac-re-edit-report').click(function () { jQuery('#rhac-re-moreform').toggle(); });
}

jQuery(
    function() {
        jQuery('#rhac-re-main').tooltip();
        jQuery('.accordion').accordion({ heightStyle: "content", collapsible: true });
        if (jQuery('#rhac-re-results')[0]) {
            rhacRecordsExplorer();
        }
    }
);
