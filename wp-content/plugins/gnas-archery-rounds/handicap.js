/**
 * Predict a score based on handicap and a description of the round being shot.
 *
 * See http://www.roystonarchery.org/new/wp-content/uploads/2013/09/Graduated-Handicap-Tables.pdf
 *
 * parameters:
 *   H - integer handicap
 *   fn - string function name, one of:
 *      "ten zone"
 *      "five zone"
 *      "metric inner ten"
 *      "vegas"
 *      "vegas inner ten"
 *      "worcester"
 *      "fita six zone"
 *   units - string name of the units being used, one of:
 *      "metric"
 *      "imperial"
 *   distances - array of objects describing the distances. Each object contains:
 *      N - integer Number of arrows shot at that distance.
 *      D - integer Diameter of the target in cm.
 *      R - integer Range (distance) to the target in the specified units (yards or meters)
 *   radius - float arrow radius in cm
 *
 * Example, predict  the score for a handicap of 30 shooting a Fita Gents (3 doz at 90m, 122cm face;
 * 3 doz at 70m, 122cm face; 3 doz at 50m, 80cm face; 3 doz at 30m, 80cm face) assuming the standard
 * 0.357 cm radius of an 1864 arrow:
 *
 *   score = rhac_score(
 *        30,
 *        "ten-zone",
 *        "metric",
 *        [
 *            {
 *                N: 3 * 12,
 *                D: 122,
 *                R: 90
 *            },
 *            {
 *                N: 3 * 12,
 *                D: 122,
 *                R: 70
 *            },
 *            {
 *                N: 3 * 12,
 *                D: 80,
 *                R: 50
 *            },
 *            {
 *                N: 3 * 12,
 *                D: 80,
 *                R: 30
 *            },
 *        ],
 *        0.357
 *   );
 */
function rhac_score(H, fn, units, distances, radius) {

    function square(x) {
        return x * x;
    }

    function exp(x) {
        return Math.exp(x);
    }

    function sigma_theta(H) {
        return Math.pow(1.036, H + 12.9) * 5e-4;
    }

    function K(H) {
        return 1.429e-6 * Math.pow(1.07, H + 4.3);
    }

    function F(R, H) {
        return 1 + K(H) * square(R);
    }

    function Sigma(lower, upper, fn) {
        if (lower > upper) {
            return 0;
        }
        else {
            return fn(lower) + Sigma(lower + 1, upper, fn);
        }
    }

    function sigma_r(R, H) {
        return 100 * R * sigma_theta(H) * F(R, H);
    }

    function sigma_r_2(R, H) {
        return square(sigma_r(R, H));
    }

    function imperial(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            9 - 2 * Sigma(1, 4,
                function(n) {
                    return exp(-square(n * D / 10 + radius) / sr2)
                }
            )
            - exp(-square(D / 2 + radius) / sr2)
        );
    }

    function metric(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            10 - Sigma(1, 10,
                function(n) {
                    return exp(-square(n * D / 20 + radius) / sr2)
                }
            )
        );
    }

    function metric_inner_ten(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return(
            10 - exp(-square(D / 40 + radius) / sr2)
            - Sigma(2, 10,
                function(n) {
                    return exp(-square(n * D / 20 + radius) / sr2)
                }
            )
        );
    }

    function vegas(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            10 - Sigma(1, 4,
                function(n) {
                    return exp(-square(n * D / 20 + radius) / sr2)
                }
            )
            - 6 * exp(-square(5 * D / 20 + radius) / sr2)
        );
    }

    function vegas_inner_ten(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            10 - exp(-square(D / 40 + radius) / sr2)
            - Sigma(2, 4,
                function(n) {
                    return exp(-square(n * D / 20 + radius) / sr2)
                }
            )
            - 6 * exp(-square(5 * D / 20 + radius) / sr2)
        );
    }

    function worcester(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            5 - Sigma(1, 5,
                function(n) {
                    return exp(-square(n * D / 10 + radius) / sr2)
                }
            )
        );
    }

    function fita_six_zone(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            10 - Sigma(1, 5,
                exp(-square(n * D / 20 + radius) / sr2)
            )
            - 5 * exp(-square(6 * D / 20 + radius) / sr2)
        );
    }

    var functions = {
        "ten zone": metric,
        "five zone": imperial,
        "metric inner ten": metric_inner_ten,
        "vegas": vegas,
        "vegas inner ten": vegas_inner_ten,
        "worcester": worcester,
        "fita six zone": fita_six_zone
    };

    var y2m = 0.9144;

    var conversions = {
        "metric": 1.0,
        "imperial": y2m,
    };

    var total = 0;
    for (var i = 0; i < distances.length; i++) {
        total += distances[i].N *
            functions[fn](distances[i].D,
                          distances[i].R * conversions[units],
                          H);
    }
    return Math.round(total);
}

var rhac_distances = new Array(); // in-line script will assign to this
var rhac_scoring = ''; //ditto
var rhac_compound_scoring = ''; //ditto
var rhac_units = ''; //ditto

jQuery(
    function() {
        if (jQuery('#handicap').val()) {
            var handicap_calc;
            if (jQuery('#predictions').text()) {
                handicap_calc = function() {
                    var val = jQuery('#handicap').val();
                    var arrow_diameter;
                    // if (jQuery('input[type=radio]:checked').val().equals('indoor')) {
                        arrow_diameter = Number(jQuery('#arrow_diameter').val());
                    // } else {
                        // arrow_diameter = 18;
                    // }
                    var radius = (arrow_diameter / 64.0) * 2.54 / 2;
                    jQuery('#predictions tbody tr').each(
                        function () {
                            var jqthis = jQuery(this);
                            var scoring = jqthis.attr('data-scoring');
                            var units = jqthis.attr('data-units');
                            var distances = jQuery.parseJSON(jqthis.attr('data-distances'));
                            jqthis.find('td.prediction').text(
                                String(rhac_score(
                                    Number(val),
                                    scoring,
                                    units,
                                    distances,
                                    radius)));
                        }
                    );
                };
            } else if (jQuery('#handicap-copy').text()) {
                handicap_calc = function() {
                    var val = jQuery('#handicap').val();
                    var scoring = rhac_scoring;
                    if (jQuery('#compound_scoring').attr('checked')) {
                        scoring = rhac_compound_scoring;
                    }
                    jQuery('#handicap-copy').text(val);
                    var radius = (18 / 64) * 2.54 / 2;
                    jQuery('#prediction').text(
                        String(rhac_score(
                            Number(val),
                            scoring,
                            rhac_units,
                            rhac_distances,
                            radius)))
                }
            }
            handicap_calc();
            jQuery('#handicap').change(handicap_calc);
            jQuery('#compound_scoring').change(handicap_calc);
            jQuery('#arrow_diameter').change(handicap_calc);
        }
    }
);
