function RHAC_ZoneMap() {}

RHAC_ZoneMap.prototype.score=function(input) {
    if (this.map[input]) {
        return this.map[input].score;
    }
    else {
        return 0;
    }
}

RHAC_ZoneMap.prototype.classes=function(input) {
    if (this.map[input]) {
        return "score " + this.map[input].className;
    }
    else {
        return "score";
    }
}

RHAC_ZoneMap.prototype.value=function(input) {
    if (this.map[input]) {
        return this.map[input].value;
    }
    else {
        return "";
    }
}

function RHAC_TenZoneMap() {
    this.map = {
        X: {score: 10, className: "gold", value: "X"},
        10: {score: 10, className: "gold", value: "10"},
        0: {score: 10, className: "gold", value: "10"},
        9: {score: 9, className: "gold", value: "9"},
        8: {score: 8, className: "red", value: "8"},
        7: {score: 7, className: "red", value: "7"},
        6: {score: 6, className: "blue", value: "6"},
        5: {score: 5, className: "blue", value: "5"},
        4: {score: 4, className: "black", value: "4"},
        3: {score: 3, className: "black", value: "3"},
        2: {score: 2, className: "white", value: "2"},
        1: {score: 1, className: "white", value: "1"},
        M: {score: 0, className: "miss", value: "M"},
    };
    this.getZoneCounts = function() {
        return { tbar_X: 0, tbar_10: 0, tbar_9: 0,
                 tbar_8: 0, tbar_7: 0, tbar_6: 0,
                 tbar_5: 0, tbar_4: 0, tbar_3: 0,
                 tbar_2: 0, tbar_1: 0, tbar_M: 0 };
    };
    this.bar_prefix = 'tbar_';
}
RHAC_TenZoneMap.prototype = new RHAC_ZoneMap();
RHAC_TenZoneMap.prototype.constructor = RHAC_TenZoneMap;

function RHAC_FiveZoneMap() {
    this.map = {
        X: {score: 9, className: "gold", value: "9"},
        10: {score: 9, className: "gold", value: "9"},
        0: {score: 9, className: "gold", value: "9"},
        9: {score: 9, className: "gold", value: "9"},
        8: {score: 7, className: "red", value: "7"},
        7: {score: 7, className: "red", value: "7"},
        6: {score: 5, className: "blue", value: "5"},
        5: {score: 5, className: "blue", value: "5"},
        4: {score: 3, className: "black", value: "3"},
        3: {score: 3, className: "black", value: "3"},
        2: {score: 1, className: "white", value: "1"},
        1: {score: 1, className: "white", value: "1"},
        M: {score: 0, className: "miss", value: "M"},
    };
    this.getZoneCounts = function() {
        return { fbar_9: 0, fbar_7: 0, fbar_5: 0,
                 fbar_3: 0, fbar_1: 0, fbar_M: 0 };
    };
    this.bar_prefix = 'fbar_';
}

RHAC_FiveZoneMap.prototype = new RHAC_ZoneMap();
RHAC_FiveZoneMap.prototype.constructor = RHAC_FiveZoneMap;

function RHAC_Scorer() {
    var zoneMap = new RHAC_TenZoneMap();

    var focusables = jQuery(":focusable");

    function setMeasure(measure) {
        if (measure == "imperial") {
            zoneMap = new RHAC_FiveZoneMap();
            jQuery('#TenZoneChart').css('display', 'none');
            jQuery('#FiveZoneChart').css('display', 'inline');
        } else if (measure == "metric") {
            zoneMap = new RHAC_TenZoneMap();
            jQuery('#TenZoneChart').css('display', 'inline');
            jQuery('#FiveZoneChart').css('display', 'none');
        } else {
            alert("unrecognised measure: " + measure);
        }
    }

    function everyArrow(fn) {
        var end = 0;
        for (var dozen = 1; dozen < 13; dozen++) {
            for (var even in [false, true]) {
                ++end;
                for (var arrow = 1; arrow < 7; ++arrow) {
                    var score = jQuery( '#arrow-' + end + '-' + arrow);
                    if (!fn(score)) {
                        return;
                    }
                }
            }
        }
    }

    function changeScore(score) {
        var val = score.val();
        var ucval = val.toUpperCase();
        score.get(0).className = zoneMap.classes(ucval);
        var newval = zoneMap.value(ucval);
        if (newval != val) {
            score.val(newval);
        }
        score.data("score", zoneMap.score(ucval));
        return true;
    }

    function addUp() {
        var counts = {
            end: 0,
            doz_tot: 0,
            total_hits: 0,
            total_xs: 0,
            total_golds: 0
        };
        var total_total = 0;
        var arrow_count = 0;
        var zoneCounts = zoneMap.getZoneCounts();
        for (var dozen = 1; dozen < 13; dozen++) {
            var doz_hits = 0;
            var doz_xs = 0;
            var doz_golds = 0;
            var doz_doz = 0;
            var doz_empty = true;
            for (var even in [false, true]) {
                ++counts.end;
                var end_total = 0;
                var end_empty = true;
                for (var arrow = 1; arrow < 7; ++arrow) {
                    var element = jQuery("#arrow-" + counts.end + "-" + arrow);
                    var score = element.data("score") || 0;
                    end_total += score;
                    doz_doz += score;
                    counts.doz_tot += score;
                    total_total += score;

                    if (score > 0) { ++doz_hits; ++counts.total_hits; }
                    if (score > 8) { ++doz_golds; ++counts.total_golds; }
                    if (element.val() == "X") { ++doz_xs; ++counts.total_xs; }

                    if (element.val() != "") {
                        ++arrow_count;
                        end_empty = false;
                        doz_empty = false;
                        var bar_class = zoneMap.bar_prefix + element.val();
                        zoneCounts[bar_class]++;
                    }
                }
                if (!end_empty) {
                    jQuery("#end-total-" + counts.end).text(String(end_total));
                }
            }
            if (!doz_empty) {
                jQuery("#doz-hits-" + dozen).text(String(doz_hits));
                jQuery("#doz-xs-" + dozen).text(String(doz_xs));
                jQuery("#doz-golds-" + dozen).text(String(doz_golds));
                jQuery("#doz-doz-" + dozen).text(String(doz_doz));
                jQuery("#doz-tot-" + dozen).text(String(counts.doz_tot));
            }
        }
        jQuery("#total-hits").text(String(counts.total_hits));
        jQuery("#i-total-hits").val(String(counts.total_hits));
        jQuery("#total-xs").text(String(counts.total_xs));
        jQuery("#i-total-xs").val(String(counts.total_xs));
        jQuery("#total-golds").text(String(counts.total_golds));
        jQuery("#i-total-golds").val(String(counts.total_golds));
        jQuery("#total-total").text(String(total_total));
        jQuery("#i-total-total").val(String(total_total));
        var max = 0;
        for (bar_class in zoneCounts) {
            if (max < zoneCounts[bar_class]) {
                max = zoneCounts[bar_class];
            }
        }
        if (max) {
            for (bar_class in zoneCounts) {
                jQuery('#' + bar_class).attr("height",
                                        (zoneCounts[bar_class] / max) * 300);
            }
        }
        if (arrow_count > 0) {
            jQuery('#average').text((total_total / arrow_count).toFixed(2));
        }
    }

    var TAB = 9;

    function watchScore(e) {
        var jqthis = jQuery(this);
        changeScore(jqthis);
        if (jqthis.val() != "") {
            if (e.keyCode != TAB) {
                var current = focusables.index(this);
                var next = focusables.eq(current+1);
                if (next.length == 0) {
                    next = focusables.eq(0);
                }
                next.focus();
            }
        }
        addUp();
    }

    function totalArrowsForRound(round) {
        var total = 0;
        jQuery( '#round-data span[name="' + round + '"] span.count' ).each(
            function() {
                total += Number(jQuery(this).text());
            }
        );
        return total;
    }

    function changeRound(round) {
        setMeasure(
            jQuery(
                '#round-data span[name="' + round + '"] span.measure'
            ).text()
        );
    }

    function watchRound() {
        changeRound(jQuery(this).val());
        everyArrow(changeScore);
        addUp();
    }

    function countArrows() {
        var count = 0;
        everyArrow(
            function(score) {
                if (score.val() == "") {
                    return false;
                }
                else {
                    ++count;
                    return true;
                }
            }
        );
        return count;
    }

    function missing(thing) {
        alert("Please select the " + thing + " first");
        return false;
    }

    function validate() {
        if (!jQuery('#archer').val()) {
            return missing("Archer");
        }
        if (!jQuery('#round').val()) {
            return missing("Round");
        }
        if (jQuery('input[name="bow"]:checked').length == 0) {
            return missing("Bow");
        }
        if (!jQuery('#date').val()) {
            return missing("Date");
        }
        var seenArrows = countArrows();
        var expectedArrows = totalArrowsForRound(jQuery('#round').val());
        if (seenArrows != expectedArrows) {
            alert("Expected "
                + expectedArrows
                + " arrows, found "
                + seenArrows);
            return false;
        }
        return true;
    }

    function setup() {
        jQuery('#TenZoneChart').css('display', 'none');
        jQuery('#FiveZoneChart').css('display', 'none');
        var round = jQuery('#round');
        if (round.val()) {
            changeRound(round.val());
        }
        round.change(watchRound);
        everyArrow(
            function(score) {
                changeScore(score);
                score.keyup(watchScore);
                return true;
            }
        )
        addUp();
        jQuery('#edit-scorecard').submit(validate);
    }

    setup();
}

jQuery(
    function() {
        if (jQuery('#round-data').text()) {
            var scorer = new RHAC_Scorer();
            jQuery( "#date" ).datepicker(
                    { dateFormat: "D, d M yy" }
                );
        }
        if (jQuery('#datepicker-lower')) {
            jQuery( '#datepicker-lower' ).datepicker(
                { dateFormat: "D, d M yy" }
            );
            jQuery( '#datepicker-upper' ).datepicker(
                { dateFormat: "D, d M yy" }
            );
        }
    }
);
