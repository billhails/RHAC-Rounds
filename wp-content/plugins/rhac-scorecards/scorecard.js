function notExecuted() {
    document.getElementById("MyElement").className = "MyClass";
    document.getElementById("MyElement").addEventListener( 'click' , changeClass );
}

function setWatcher(end, dozen, even, arrow) {
    var name = "arrow-" + end + '-' + arrow;
    var input = document.getElementsByName(name)[0];
    input.addEventListener( 'blur',
        if (input.getValue()
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
