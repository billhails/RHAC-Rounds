jQuery(function() {
    if (jQuery('#rhac-sightmarks-in').html()) {
        var version = 'rhac-sightmarks-1.0';
        jQuery('.rhac-sightmarks-simple-dialog').dialog({
            autoOpen: false,
            modal: true,
            buttons: {
                OK: function () {
                    jQuery(this).dialog( "close" );
                }
            }
        });
        var persistance = persist(version,
                            '#rhac-sightmarks-quota-exceeded',
                            '#rhac-sightmarks-old-browser');
        var jQtableIn = jQuery('#rhac-sightmarks-in');
        var tableIn = jQtableIn.DataTable(
            {
                paging: false,
                ordering: false,
                info: false,
                filter: false
            }
        );

        var jQtableMetricOut = jQuery('#rhac-sightmarks-out-metric');
        var tableMetricOut = jQtableMetricOut.DataTable(
            {
                paging: false,
                ordering: false,
                info: false,
                filter: false
            }
        );

        var jQtableImperialOut = jQuery('#rhac-sightmarks-out-imperial');
        var tableImperialOut = jQtableImperialOut.DataTable(
            {
                paging: false,
                ordering: false,
                info: false,
                filter: false
            }
        );

        var canvas = jQuery('#rhac-sightmarks-canvas');
        var ctx = canvas[0].getContext("2d");
        var width = Number(canvas.attr('width'));
        var height = Number(canvas.attr('height'));
        var minY = 0;
        var maxY = height;

        function setYScale(y1, y2) {
            if (y1 > y2) {
                var tmp = y1;
                y1 = y2;
                y2 = tmp;
            }
            minY = y1;
            maxY = y2;
        }

        function distance(x) {
            return x * width / 100;
        }

        function yTransform(y) {
            return height - height * ((y - minY) / (maxY - minY));
        }

        function clear(ctx) {
            ctx.fillStyle = 'white';
            ctx.fillRect(0,0,width,height);
        }

        function point(ctx, x, y, color) {
            ctx.lineWidth = 0.5;
            ctx.fillStyle = color;
            ctx.beginPath();
            ctx.arc(distance(x),yTransform(y),5,0,2*Math.PI);
            ctx.fill();
            ctx.stroke();
        }

        function line(ctx, x1, y1, x2, y2) {
            ctx.lineWidth = 1;
            ctx.strokeStyle = 'blue';
            ctx.moveTo(distance(x1), yTransform(y1));
            ctx.lineTo(distance(x2), yTransform(y2));
            ctx.stroke();
        }

        var sightmarks = [];
        var distances = [];
        var counter = 0;

        jQuery('button').button();
        function initNewButtons(c) {
            var n = Number(c);

            jQuery('#rhac-measure-' + c).buttonset();

            jQuery('#rhac-trash-' + c).button({
                icons: {
                    primary: 'ui-icon-trash'
                },
                text: false
            }).click(function() {
                tableIn.row(jQuery(this).parents('tr'))
                     .remove()
                     .draw();
                     delete sightmarks[n];
                     delete distances[n];
                    showSightmarks();
            });
            jQuery('#rhac-distance-' + c).on('change', function() {
                distances[n] = Number(jQuery(this).val());
                if (isNaN(distances[n])) {
                    alert("not a valid number: " + jQuery(this).val());
                    distances[n] = undefined;
                    jQuery(this).val('');
                } else {
                    jQuery(this).val(distances[n]);
                    showSightmarks();
                }
            });
            jQuery('#rhac-sightmark-' + c).on('change', function() {
                sightmarks[n] = Number(jQuery(this).val());
                if (isNaN(sightmarks[n])) {
                    alert("not a valid number: " + jQuery(this).val());
                    sightmarks[n] = undefined;
                    jQuery(this).val('');
                } else {
                    jQuery(this).val(sightmarks[n]);
                    showSightmarks();
                }
            });
            jQuery('#rhac-measure-m-' + c).on("change", function() {
                showSightmarks();
            });
            jQuery('#rhac-measure-y-' + c).on("change", function() {
                showSightmarks();
            });
        }
        jQuery('#rhac-sightmarks-add-row').button().click(function () {
            ++counter;
            var c = String(counter);
            tableIn.row.add(
                [
                    '<input type="text" id="rhac-distance-' + c + '"></input>',
                    '<span id="rhac-measure-' + c +'">' +
                    '<input type="radio" name="rhac-measure-' + c + '" id="rhac-measure-y-' +
                    c + '" checked="checked" value="y"></input><label for="rhac-measure-y-' + c + '">y</label>' +
                    '<input type="radio" name="rhac-measure-' + c + '" id="rhac-measure-m-' +
                    c + '" value="m"></input><label for="rhac-measure-m-' + c + '">m</label>' +
                    '</span>',
                    '<input type="text" id="rhac-sightmark-' + c + '"></input>',
                    '<button id="rhac-trash-' + c + '">Delete</button>'
                ]
            ).draw();
            jQtableIn.change();
            initNewButtons(c);
        });

        function yards(y) {
            return y * 0.9144;
        }

        function showSightmarks() {
            var localSightmarks = [];
            var localDistances = [];
            var localColors = [];
            var i;
            var bestFit;
            for (i = 0; i < sightmarks.length && i < distances.length; ++i) {
                if (sightmarks[i] !== undefined && distances[i] !== undefined) {
                    var distance = distances[i];
                    var measure = jQuery('input[name=rhac-measure-' + i + ']:checked').val();
                    if (measure == 'y') {
                        distance = yards(distance);
                        localColors.push('white');
                    }
                    else {
                        localColors.push('yellow');
                    }
                    localDistances.push(distance);
                    localSightmarks.push(sightmarks[i]);
                }
            }
            clear(ctx);
            if (localDistances.length > 1) {
                bestFit = lineOfBestFit(localDistances, localSightmarks);
                setYScale(bestFit(0), bestFit(100));
            }
            for (i = 0; i < localColors.length; ++i) {
                point(ctx, localDistances[i], localSightmarks[i], localColors[i]);
            }
            if (localDistances.length > 1) {
                line(ctx, 0, bestFit(0), 100, bestFit(100));
                showImperialSightmarks(bestFit);
                showMetricSightmarks(bestFit);
            }
        }

        function showImperialSightmarks(bestFit) {
            var distances = [15, 20, 25, 30, 40, 50, 60, 80, 100];
            tableImperialOut.clear();
            for (var i = 0; i < distances.length; ++i) {
                tableImperialOut.row.add(
                    [
                        String(distances[i]) + 'y',
                        bestFit(yards(distances[i])).toFixed(1)
                    ]
                );
            }
            tableImperialOut.draw();
        }

        function showMetricSightmarks(bestFit) {
            var distances = [10, 15, 18, 20, 25, 30, 40, 50, 60, 70, 90];
            tableMetricOut.clear();
            for (var i = 0; i < distances.length; ++i) {
                tableMetricOut.row.add(
                    [
                        String(distances[i]) + 'm',
                        bestFit(distances[i]).toFixed(1)
                    ]
                );
            }
            tableMetricOut.draw();
        }
    }

});
