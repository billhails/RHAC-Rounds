
function addUp() {
    var end = 0;
    var doz_tot = 0;
    var total_hits = 0;
    var total_xs = 0;
    var total_golds = 0;
    var total_total = 0;
    var counts = {
        bar_X: 0,
        bar_10: 0,
        bar_9: 0,
        bar_8: 0,
        bar_7: 0,
        bar_6: 0,
        bar_5: 0,
        bar_4: 0,
        bar_3: 0,
        bar_2: 0,
        bar_1: 0,
        bar_M: 0
    };
    for (var dozen = 1; dozen < 9; dozen++) {
        var doz_hits = 0;
        var doz_xs = 0;
        var doz_golds = 0;
        var doz_doz = 0;
        var doz_empty = true;
        for (var even in [false, true]) {
            ++end;
            var end_total = 0;
            var end_empty = true;
            for (var arrow = 1; arrow < 7; ++arrow) {
                var element = document.getElementsByName("arrow-"
                                                    + end + "-" + arrow)[0];
                var score = Number(element.getAttribute("score"));
                end_total += score;
                doz_doz += score;
                doz_tot += score;
                total_total += score;

                if (score > 0) {
                    ++doz_hits;
                    ++total_hits;
                }

                if (score > 8) {
                    ++doz_golds;
                    ++total_golds;
                }

                if (element.value == "X") {
                    ++doz_xs;
                    ++total_xs;
                }
                
                if (element.value != "") {
                    end_empty = false;
                    doz_empty = false;
                    var bar_class = "bar_" + element.value;
                    counts[bar_class]++;
                }
            }
            if (!end_empty) {
                document.getElementsByName("end-total-" + end)[0].innerText =
                                                        String(end_total);
            }
        }
        if (!doz_empty) {
            document.getElementsByName("doz-hits-" + dozen)[0].innerText =
                                                        String(doz_hits);
            document.getElementsByName("doz-xs-" + dozen)[0].innerText =
                                                        String(doz_xs);
            document.getElementsByName("doz-golds-" + dozen)[0].innerText =
                                                        String(doz_golds);
            document.getElementsByName("doz-doz-" + dozen)[0].innerText =
                                                        String(doz_doz);
            document.getElementsByName("doz-tot-" + dozen)[0].innerText =
                                                        String(doz_tot);
        }
    }
    document.getElementsByName("total-hits")[0].innerText =
                                                    String(total_hits);
    document.getElementsByName("total-xs")[0].innerText =
                                                    String(total_xs);
    document.getElementsByName("total-golds")[0].innerText =
                                                    String(total_golds);
    document.getElementsByName("total-total")[0].innerText =
                                                    String(total_total);
    var max = 0;
    for (bar_class in counts) {
        if (max < counts[bar_class]) {
            max = counts[bar_class];
        }
    }
    if (max) {
        for (bar_class in counts) {
            document.getElementById(bar_class).setAttribute("height", (counts[bar_class] / max) * 100);
        }
    }
}

function setWatcher(end, dozen, even, arrow) {
    var name = "arrow-" + end + '-' + arrow;
    var input = document.getElementsByName(name)[0];
    input.setAttribute("score", "0");
    var scoreMap = {
        X: {score: "10", className: "gold", value: "X"},
        10: {score: "10", className: "gold", value: "10"},
        9: {score: "9", className: "gold", value: "9"},
        8: {score: "8", className: "red", value: "8"},
        7: {score: "7", className: "red", value: "7"},
        6: {score: "6", className: "blue", value: "6"},
        5: {score: "5", className: "blue", value: "5"},
        4: {score: "4", className: "black", value: "4"},
        3: {score: "3", className: "black", value: "3"},
        2: {score: "2", className: "white", value: "2"},
        1: {score: "1", className: "white", value: "1"},
        0: {score: "0", className: "miss", value: "M"},
        M: {score: "0", className: "miss", value: "M"},
    };
    input.addEventListener( 'blur',
        function () {
            var value = input.value.toUpperCase();
            var score = 0;
            if (scoreMap[value]) {
                input.className = "score " + scoreMap[value]["className"];
                input.value = scoreMap[value]["value"];
                input.setAttribute("score", scoreMap[value]["score"]);
            } else {
                input.className = "score";
                input.value = "";
                input.setAttribute("score", "0");
            }
            addUp();
        }
    )
}

window.onload = function() {
    var end = 0;
    for (var dozen = 1; dozen < 9; dozen++) {
        for (var even in [false, true]) {
            ++end;
            for (var arrow = 1; arrow < 7; ++arrow) {
                setWatcher(end, dozen, even, arrow);
            }
        }
    }
}
