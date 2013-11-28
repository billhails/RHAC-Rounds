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

    function scorecardHTML(data) {
        var html = '<div class="scorecard">';
        html += '<span class="headers">';
        html += '<span class="header"><b>Archer:</b> ';
        html += data.archer;
        html += ',</span>';
        html += '<span class="header"><b>Bow:</b> ';
        html += data.bow;
        html += ',</span>';
        html += '<span class="header"><b>Round:</b> ';
        html += data.round;
        html += ',</span>';
        html += '<span class="header"><b>Date:</b> '
        html += data.date;
        html += '.</span>';
        html += '.</span>';
        html += '<table class="scorecard">';
        html += '<thead>';
        html += '<tr>';
        html += '<th>dist</th>';
        for (var lr = 0; lr < 2; ++lr) {
            for (var arrow = 1; arrow < 7; ++arrow) {
                html += '<th class="arrow">' + arrow + '</th>';
            }
            html += '<th class="totals">end</th>';
            html += '<th class="pad"></th>';
        }
        html += '<th class="totals">hits</th>';
        html += '<th class="totals">Xs</th>';
        html += '<th class="totals">golds</th>';
        html += '<th class="totals">doz</th>';
        html += '<th class="totals">tot</th>';
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        var right = false;
        var totals = {
            end: 0,
            hits: 0, total_hits: 0,
            xs: 0, total_xs: 0,
            golds: 0, total_golds: 0,
            doz: 0, total: 0,
            total_tens: 0,
            total_nines: 0,
            total_eights: 0,
            total_sevens: 0,
            total_sixes: 0,
            total_fives: 0,
            total_fours: 0,
            total_threes: 0,
            total_twos: 0,
            total_ones: 0,
            total_misses: 0,
            total_arrows: 0
        };
        data.ends.each(function(index, end) {
            totals.end = 0;
            if (!right) {
                html += '<tr>';
                totals.hits = 0;
                totals.xs = 0;
                totals.golds = 0;
                totals.doz = 0;
            }
            html += oneEnd(end, totals);
            if (right) {
                html += endTotal(totals);
            }
            right = !right;
        });
        if (right) {
            html += emptyEnd();
            html += endTotal(totals);
        }
        html += '</tbody>';
        html += '<tfoot>';
        html += '<tr>';
        html += '<td class="totals" colspan="16">Totals:</td>';
        html += '<td class="total-hits">' + totals.total_hits + '</td>';
        html += '<td class="total-Xs">' + totals.total_xs + '</td>';
        html += '<td class="total-golds">' + totals.total_golds + '</td>';
        html += '<td class="total-doz"></td>';
        html += '<td class="total-total">' + totals.total + '</td>';
        html += '</tr>';
        html += '</tfoot>';
        html += '</table>';
        if (totals.total_arrows > 0) {
            if ('imperial' == data.measure) {
                html += imperialBarchart(totals);
            }
            else {
                html += metricBarchart(totals);
            }
            html += '<span><b>Average:</b> ' +
                (totals.total / totals.total_arrows).toFixed(2) +
                '</span>';
        }
        html += '</div>';
        return html;
    }

    function metricBarChart(totals) {
        var width = 30;
        var height = 100;
        var html = '';
        html += '<table>';
        html += '<tr class="bars">';
        html += '<td class="bar"><img src="gold.png" height="' +
            (height * totals.total_xs / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="gold.png" height="' +
            (height * totals.total_tens / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="gold.png" height="' +
            (height * totals.total_nines / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="red.png" height="' +
            (height * totals.total_eights / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="red.png" height="' +
            (height * totals.total_sevens / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="blue.png" height="' +
            (height * totals.total_sixes / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="blue.png" height="' +
            (height * totals.total_fives / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="black.png" height="' +
            (height * totals.total_fours / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="black.png" height="' +
            (height * totals.total_threes / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="white.png" height="' +
            (height * totals.total_twos / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="white.png" height="' +
            (height * totals.total_ones / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="green.png" height="' +
            (height * totals.total_misses / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '</tr>';
        html += '<tr class="values">';
        html += '<td class="value">' + totals.total_xs + '</td>';
        html += '<td class="value">' + totals.total_tens + '</td>';
        html += '<td class="value">' + totals.total_nines + '</td>';
        html += '<td class="value">' + totals.total_eights + '</td>';
        html += '<td class="value">' + totals.total_sevens + '</td>';
        html += '<td class="value">' + totals.total_sixes + '</td>';
        html += '<td class="value">' + totals.total_fives + '</td>';
        html += '<td class="value">' + totals.total_fours + '</td>';
        html += '<td class="value">' + totals.total_threes + '</td>';
        html += '<td class="value">' + totals.total_twos + '</td>';
        html += '<td class="value">' + totals.total_ones + '</td>';
        html += '<td class="value">' + totals.total_misses + '</td>';
        html += '</tr>';
        html += '<tr class="labels">';
        html += '<th class="label">Xs</th>';
        html += '<th class="label">10s</th>';
        html += '<th class="label">9s</th>';
        html += '<th class="label">8s</th>';
        html += '<th class="label">7s</th>';
        html += '<th class="label">6s</th>';
        html += '<th class="label">5s</th>';
        html += '<th class="label">4s</th>';
        html += '<th class="label">3s</th>';
        html += '<th class="label">2s</th>';
        html += '<th class="label">1s</th>';
        html += '<th class="label">Ms</th>';
        html += '</tr>';
        html += '</table>';
        return html;
    }

    function imperialBarChart(totals) {
        var width = 50;
        var height = 100;
        var html = '';
        html += '<table>';
        html += '<tr class="bars">';
        html += '<td class="bar"><img src="gold.png" height="' +
            (height * totals.total_nines / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="red.png" height="' +
            (height * totals.total_sevens / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="blue.png" height="' +
            (height * totals.total_fives / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="black.png" height="' +
            (height * totals.total_threes / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="white.png" height="' +
            (height * totals.total_ones / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '<td class="bar"><img src="green.png" height="' +
            (height * totals.total_misses / totals.total_arrows).toFixed() +
            '" width="' + width + '"/></td>';
        html += '</tr>';
        html += '<tr class="values">';
        html += '<td class="value">' + totals.total_nines + '</td>';
        html += '<td class="value">' + totals.total_sevens + '</td>';
        html += '<td class="value">' + totals.total_fives + '</td>';
        html += '<td class="value">' + totals.total_threes + '</td>';
        html += '<td class="value">' + totals.total_ones + '</td>';
        html += '<td class="value">' + totals.total_misses + '</td>';
        html += '</tr>';
        html += '<tr class="labels">';
        html += '<th class="label">9s</th>';
        html += '<th class="label">7s</th>';
        html += '<th class="label">5s</th>';
        html += '<th class="label">3s</th>';
        html += '<th class="label">1s</th>';
        html += '<th class="label">Ms</th>';
        html += '</tr>';
        html += '</table>';
        return html;
    }

    function endTotal(totals) {
        var html = '';
        html += '<td class="hits">' + totals.hits + '</td>';
        html += '<td class="Xs">' + totals.xs + '</td>';
        html += '<td class="golds">' + totals.golds + '</td>';
        html += '<td class="doz">' + totals.doz + '</td>';
        html += '<td class="tot">' + totals.total + '</td>';
        html += '</tr>';
        return html;
    }

    function emptyEnd() {
        var html = '';
        for (var i = 0; i < 6; ++i) {
            html += '<td class="empty"></td>';
        }
        html += '<td class="pad"></td>';
        return html;
    }

    function oneEnd(end, totals) {
        var html = '';
        html += oneArrow(end.arrow_1, totals);
        html += oneArrow(end.arrow_2, totals);
        html += oneArrow(end.arrow_3, totals);
        html += oneArrow(end.arrow_4, totals);
        html += oneArrow(end.arrow_5, totals);
        html += oneArrow(end.arrow_6, totals);
        html += '<td class="pad"></td>';
        return html;
    }

    function oneArrow(arrow, totals) {
        var css_class = '';
        totals.total_arrows++;
        switch(arrow) {
            case 'X':
                totals.xs++;
                totals.total_xs++;
                totals.golds++;
                totals.total_golds++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 10;
                totals.doz += 10;
                totals.total += 10;
                css_class = 'gold';
                break;
            case 10:
                totals.total_tens++;
                totals.golds++;
                totals.total_golds++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 10;
                totals.doz += 10;
                totals.total += 10;
                css_class = 'gold';
                break;
            case 9:
                totals.total_nines++;
                totals.golds++;
                totals.total_golds++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 9;
                totals.doz += 9;
                totals.total += 9;
                css_class = 'gold';
                break;
            case 8:
                totals.total_eights++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 8;
                totals.doz += 8;
                totals.total += 8;
                css_class = 'red';
                break;
            case 7:
                totals.total_sevens++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 7;
                totals.doz += 7;
                totals.total += 7;
                css_class = 'red';
                break;
            case 6:
                totals.total_sixes++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 6;
                totals.doz += 6;
                totals.total += 6;
                css_class = 'blue';
                break;
            case 5:
                totals.total_fives++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 5;
                totals.doz += 5;
                totals.total += 5;
                css_class = 'blue';
                break;
            case 4:
                totals.total_fours++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 4;
                totals.doz += 4;
                totals.total += 4;
                css_class = 'black';
                break;
            case 3:
                totals.total_threes++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 3;
                totals.doz += 3;
                totals.total += 3;
                css_class = 'black';
                break;
            case 2:
                totals.total_twos++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 2;
                totals.doz += 2;
                totals.total += 2;
                css_class = 'white';
                break;
            case 1:
                totals.total_ones++;
                totals.hits++;
                totals.total_hits++;
                totals.end += 1;
                totals.doz += 1;
                totals.total += 1;
                css_class = 'white';
                break;
            case 'M':
                totals.total_misses++;
                css_class = 'green';
                break;
        }
        return '<td class="' + css_class + '">' + arrow + '</td>';
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
                if (end_empty) {
                    jQuery("#end-total-" + counts.end).text("");
                } else {
                    jQuery("#end-total-" + counts.end).text(String(end_total));
                }
            }
            if (doz_empty) {
                jQuery("#doz-hits-" + dozen).text("");
                jQuery("#doz-xs-" + dozen).text("");
                jQuery("#doz-golds-" + dozen).text("");
                jQuery("#doz-doz-" + dozen).text("");
                jQuery("#doz-tot-" + dozen).text("");
            } else {
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
                                        (zoneCounts[bar_class] / max) * 425);
            }
        }
        if (arrow_count > 0) {
            jQuery('#average').text((total_total / arrow_count).toFixed(2));
        }
    }

    var TAB = 9;
    var BS = 8;

    function watchScore(e) {
        var jqthis = jQuery(this);
        changeScore(jqthis);
        if (jqthis.val() == "") {
            if (e.keyCode == BS) {
                var current = focusables.index(this);
                var prev = focusables.eq(current-1);
                if (prev.length == 0) {
                    prev = focusables.eq(0);
                }
                prev.focus();
            }
        } else {
            if (e.keyCode == TAB) {
            } else if (e.keyCode == BS) {
                jqthis.val("");
            } else {
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

    function confirm_delete() {
        return confirm("Are you sure you want to delete this scorecard?");
    }

    function toggleHelp() {
        var help_text = jQuery('#help-text');
        if (help_text.css('display') == 'none') {
            help_text.css('display', 'block');
        } else {
            help_text.css('display', 'none');
        }
    }

    function setup() {
        jQuery('#TenZoneChart').css('display', 'none');
        jQuery('#FiveZoneChart').css('display', 'none');
        jQuery('#help-button').click(toggleHelp);
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
        jQuery('#delete-scorecard').submit(confirm_delete);
    }

    setup();
}

jQuery(
    function() {
        function confirm_merge() {
            return confirm("Are you sure you want to merge these two archers?");
        }

        if (jQuery('#round-data').text()) {
            new RHAC_Scorer();
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
        if (jQuery('#merge-archers')) {
            jQuery('#merge-archers').submit(confirm_merge);
        }
    }
);
