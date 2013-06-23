function ZoneMap() {}

ZoneMap.prototype.score=function(input) {
    if (this.map[input]) {
        return this.map[input].score;
    }
    else {
        return 0;
    }
}

ZoneMap.prototype.classes=function(input) {
    if (this.map[input]) {
        return "score " + this.map[input].className;
    }
    else {
        return "score";
    }
}

ZoneMap.prototype.value=function(input) {
    if (this.map[input]) {
        return this.map[input].value;
    }
    else {
        return "";
    }
}

function TenZoneMap() {
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
        return { tbar_X: 0, tbar_10: 0, tbar_9: 0, tbar_8: 0, tbar_7: 0, tbar_6: 0,
                 tbar_5: 0, tbar_4: 0, tbar_3: 0, tbar_2: 0, tbar_1: 0, tbar_M: 0 };
    };
    this.bar_prefix = 'tbar_';
}
TenZoneMap.prototype = new ZoneMap();
TenZoneMap.prototype.constructor = TenZoneMap;

function FiveZoneMap() {
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
        return { fbar_9: 0, fbar_7: 0, fbar_5: 0, fbar_3: 0, fbar_1: 0, fbar_M: 0 };
    };
    this.bar_prefix = 'fbar_';
}

FiveZoneMap.prototype = new ZoneMap();
FiveZoneMap.prototype.constructor = FiveZoneMap;

function Scorer() {
    var zoneMap = new TenZoneMap();
    var me = this;

    this.setMeasure = function(measure) {
        if (measure == "imperial") {
            zoneMap = new FiveZoneMap();
            $('#TenZoneChart').css('display', 'none');
            $('#FiveZoneChart').css('display', 'inline');
        } else if (measure == "metric") {
            zoneMap = new TenZoneMap();
            $('#TenZoneChart').css('display', 'inline');
            $('#FiveZoneChart').css('display', 'none');
        } else {
            alert("unrecognised measure: " + measure);
        }
    }

    this.watchScore = function() {
        var value = $(this).val().toUpperCase();
        this.className = zoneMap.classes(value);
        $(this).val(zoneMap.value(value));
        $(this).data("score", zoneMap.score(value));
        me.addUp();
    }

    this.addUp = function() {
        var counts = { end: 0, doz_tot: 0, total_hits: 0, total_xs: 0, total_golds: 0};
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
        $("#total-xs").text(String(counts.total_xs));
        $("#total-golds").text(String(counts.total_golds));
        $("#total-total").text(String(total_total));
        var max = 0;
        for (bar_class in zoneCounts) {
            if (max < zoneCounts[bar_class]) {
                max = zoneCounts[bar_class];
            }
        }
        if (max) {
            for (bar_class in zoneCounts) {
                $('#' + bar_class).attr("height", (zoneCounts[bar_class] / max) * 300);
            }
        }
    }

    this.changeRound = function() {
        me.setMeasure($( '#round-data span[name="' + $(this).val() + '"] span.measure' ).text());
    }
}

$(function() {
    var end = 0;
    var scorer = new Scorer();
    $( "#datepicker" ).datepicker({
        dateFormat: "D, d M yy"
    });
    $('#TenZoneChart').css('display', 'inline');
    $('#FiveZoneChart').css('display', 'none');
    $("#round").change(scorer.changeRound);
    for (var dozen = 1; dozen < 13; dozen++) {
        for (var even in [false, true]) {
            ++end;
            for (var arrow = 1; arrow < 7; ++arrow) {
                $( '#arrow-' + end + '-' + arrow).blur(scorer.watchScore);
            }
        }
    }
});
