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
        9: {score: 9, className: "gold", value: "9"},
        8: {score: 8, className: "red", value: "8"},
        7: {score: 7, className: "red", value: "7"},
        6: {score: 6, className: "blue", value: "6"},
        5: {score: 5, className: "blue", value: "5"},
        4: {score: 4, className: "black", value: "4"},
        3: {score: 3, className: "black", value: "3"},
        2: {score: 2, className: "white", value: "2"},
        1: {score: 1, className: "white", value: "1"},
        0: {score: 0, className: "miss", value: "M"},
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
        9: {score: 9, className: "gold", value: "9"},
        8: {score: 7, className: "red", value: "7"},
        7: {score: 7, className: "red", value: "7"},
        6: {score: 5, className: "blue", value: "5"},
        5: {score: 5, className: "blue", value: "5"},
        4: {score: 3, className: "black", value: "3"},
        3: {score: 3, className: "black", value: "3"},
        2: {score: 1, className: "white", value: "2"},
        1: {score: 1, className: "white", value: "1"},
        0: {score: 0, className: "miss", value: "M"},
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

    function setMeasure(measure) {
        if (measure == "imperial") {
            zoneMap = new RHAC_FiveZoneMap();
            $('#TenZoneChart').css('display', 'none');
            $('#FiveZoneChart').css('display', 'inline');
        } else if (measure == "metric") {
            zoneMap = new RHAC_TenZoneMap();
            $('#TenZoneChart').css('display', 'inline');
            $('#FiveZoneChart').css('display', 'none');
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
                    var score = $( '#arrow-' + end + '-' + arrow);
                    if (!fn(score)) {
                        return;
                    }
                }
            }
        }
    }

    function changeScore(score) {
        var val = score.val();
        var value = val.toUpperCase();
        score.get(0).className = zoneMap.classes(value);
        score.val(zoneMap.value(value));
        score.data("score", zoneMap.score(value));
        return true;
    }

    function addUp() {
        var counts = {
            end: 0, doz_tot: 0, total_hits: 0,
            total_xs: 0, total_golds: 0
        };
        var total_total = 0;
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
                    var element = $("#arrow-" + counts.end + "-" + arrow);
                    var score = element.data("score") || 0;
                    end_total += score;
                    doz_doz += score;
                    counts.doz_tot += score;
                    total_total += score;

                    if (score > 0) { ++doz_hits; ++counts.total_hits; }
                    if (score > 8) { ++doz_golds; ++counts.total_golds; }
                    if (element.val() == "X") { ++doz_xs; ++counts.total_xs; }

                    if (element.val() != "") {
                        end_empty = false;
                        doz_empty = false;
                        var bar_class = zoneMap.bar_prefix + element.val();
                        zoneCounts[bar_class]++;
                    }
                }
                if (!end_empty) {
                    $("#end-total-" + counts.end).text(String(end_total));
                }
            }
            if (!doz_empty) {
                $("#doz-hits-" + dozen).text(String(doz_hits));
                $("#doz-xs-" + dozen).text(String(doz_xs));
                $("#doz-golds-" + dozen).text(String(doz_golds));
                $("#doz-doz-" + dozen).text(String(doz_doz));
                $("#doz-tot-" + dozen).text(String(counts.doz_tot));
            }
        }
        $("#total-hits").text(String(counts.total_hits));
        $("#i-total-hits").text(String(counts.total_hits));
        $("#total-xs").text(String(counts.total_xs));
        $("#i-total-xs").text(String(counts.total_xs));
        $("#total-golds").text(String(counts.total_golds));
        $("#i-total-golds").text(String(counts.total_golds));
        $("#total-total").text(String(total_total));
        $("#i-total-total").text(String(total_total));
        var max = 0;
        for (bar_class in zoneCounts) {
            if (max < zoneCounts[bar_class]) {
                max = zoneCounts[bar_class];
            }
        }
        if (max) {
            for (bar_class in zoneCounts) {
                $('#' + bar_class).attr("height",
                                        (zoneCounts[bar_class] / max) * 300);
            }
        }
    }

    function watchScore() {
        changeScore($(this));
        addUp();
    }

    function totalArrowsForRound(round) {
        var total = 0;
        $( '#round-data span[name="' + round + '"] span.count' ).each(
            function() {
                total += Number($(this).text());
            }
        );
        return total;
    }

    function changeRound(round) {
        setMeasure(
            $( '#round-data span[name="' + round + '"] span.measure' ).text()
        );
    }

    function watchRound() {
        changeRound($(this).val());
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

    function validate() {
        if (!$('#archer').val()) {
            alert("Archer is a required field");
            return false;
        }
        if (!$('#round').val()) {
            alert("Round is a required field");
            return false;
        }
        if ($('input[name="bow"]:checked').length == 0) {
            alert("Bow is a required field");
            return false;
        }
        if (!$('#date').val()) {
            alert("Date is a required field");
            return false;
        }
        var seenArrows = countArrows();
        var expectedArrows = totalArrowsForRound($('#round').val());
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
        $('#TenZoneChart').css('display', 'none');
        $('#FiveZoneChart').css('display', 'none');
        var round = $('#round');
        if (round.val()) {
            changeRound(round.val());
        }
        round.change(watchRound);
        everyArrow(
            function(score) {
                changeScore(score);
                score.blur(watchScore);
                return true;
            }
        )
        addUp();
        $('#edit-scorecard').submit(validate);
    }

    setup();
}

$(
    function() {
        if ($('#round-data').text()) {
            var scorer = new RHAC_Scorer();
            $( "#date" ).datepicker(
                    { dateFormat: "D, d M yy" }
                );
        }
        if ($('#datepicker-lower')) {
            $( '#datepicker-lower' ).datepicker(
                { dateFormat: "D, d M yy" }
            );
            $( '#datepicker-upper' ).datepicker(
                { dateFormat: "D, d M yy" }
            );
        }
    }
);
