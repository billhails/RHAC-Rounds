jQuery(function() {
    // arr.forEach(callback(value, index, arr)[, thisArg])
    var canvas = jQuery('canvas.targets');
    if (!canvas[0]) {
        return;
    }
    function drawTarget(x, y, mainRadius, name) {
        canvas.drawArc({
            fillStyle: 'white',
            strokeStyle: 'black',
            strokeWidth: 1,
            strokeColor: 'black',
            shadowColor: '#444',
            shadowBlur: 5,
            shadowX: 3,
            shadowY: 3,
            x: x,
            y: y,
            radius: mainRadius,
            draggable: true,
            groups: [name],
            dragGroups: [name]
        });
        ['black', 'blue', 'red', 'yellow'].forEach(function(color, index) {
            var innerRadius = mainRadius * (4.0 - index) / 5.0;
            canvas.drawArc({
                fillStyle: color,
                strokeStyle: color,
                strokeWidth: 0,
                strokeColor: color,
                x: x,
                y: y,
                radius: innerRadius,
                draggable: true,
                groups: [name],
                dragGroups: [name]
            });
        });
        canvas.drawRect({
            fillStyle: '#eee',
            x: x,
            y: y + mainRadius + 8,
            width: 100,
            height: 16,
            draggable: true,
            shadowColor: '#444',
            shadowBlur: 5,
            shadowX: 3,
            shadowY: 3,
            groups: [name],
            dragGroups: [name]
        }).drawText({
            fillStyle: 'black',
            strokeWidth: 0,
            x: x,
            y: y + mainRadius + 8,
            fontSize: 12,
            fontFamily: 'Verdana, sans-serif',
            text: name,
            draggable: true,
            groups: [name],
            dragGroups: [name]
        });
    }
    function drawTargets() {
        canvas.clearCanvas();
        var centerX = 300;
        var centerY = 300;
        var originalRadius = 100;
        // 122cm in yards
        var mainRadius = originalRadius;
        [20, 30, 40, 50, 60, 80, 100].forEach(function(yards, index) {
            var radius = mainRadius * 20.0 / yards;
            var X = 110 + index * 70;
            var Y = 20 + radius;
            drawTarget(X, Y, radius, '122cm @ ' + yards + 'y');
        });

        // 122cm in meters
        var mainRadius = originalRadius * 0.94;
        [20, 30, 40, 50, 60, 70, 90].forEach(function(meters, index) {
            var radius = mainRadius * 20.0 / meters;
            var X = 110 + index * 70;
            var Y = 330 + radius;
            drawTarget(X, Y, radius, '122cm @ ' + meters + 'm');
        });

        // 80cm in meters
        mainRadius = originalRadius * (80 / 122) * 2 * 0.94;
        [10, 15, 20, 30, 40, 50 ].forEach(function(meters, index) {
            var radius = mainRadius * 10.0 / meters;
            var X = 150 + index * 70;
            var Y = 590 + radius;
            drawTarget(X, Y, radius, '80cm @ ' + meters + 'm');
        });
    }

    jQuery('#reset').button().click(drawTargets);

    drawTargets();

});
