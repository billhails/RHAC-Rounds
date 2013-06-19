
function addUp() {
    var end = 0;
    for (var dozen = 1; dozen < 9; dozen++) {
        for (var even in [false, true]) {
            ++end;
            var end_total = 0;
            document.getElementsByName("end-total-" + end)[0].innerText = "0";
        }
    }
}

function setWatcher(end, dozen, even, arrow) {
    var name = "arrow-" + end + '-' + arrow;
    var input = document.getElementsByName(name)[0];
    var scoreMap = {
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
                input.setAttribute("score", 0);
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
