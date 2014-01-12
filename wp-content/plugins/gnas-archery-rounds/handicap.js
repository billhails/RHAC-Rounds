function rhac_score(H, fn, units, distances) {

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

    var r = 0.357;

    function sigma_r_2(R, H) {
        return square(sigma_r(R, H));
    }

    function imperial(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            9 - 2 * Sigma(1, 4,
                function(n) {
                    return exp(-square(n * D / 10 + r) / sr2)
                }
            )
            - exp(-square(D / 2 + r) / sr2)
        );
    }

    function metric(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            10 - Sigma(1, 10,
                function(n) {
                    return exp(-square(n * D / 20 + r) / sr2)
                }
            )
        );
    }

    function metric_inner_ten(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return(
            10 - exp(-square(D / 40 + r) / sr2)
            - Sigma(2, 10,
                function(n) {
                    return exp(-square(n * D / 20 + r) / sr2)
                }
            )
        );
    }

    function vegas(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            10 - Sigma(1, 4,
                function(n) {
                    return exp(-square(n * D / 20 + r) / sr2)
                }
            )
            - 6 * exp(-square(5 * D / 20 + r) / sr2)
        );
    }

    function vegas_inner_ten(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            10 - exp(-square(D / 40 + r) / sr2)
            - Sigma(2, 4,
                function(n) {
                    return exp(-square(n * D / 20 + r) / sr2)
                }
            )
            - 6 * exp(-square(5 * D / 20 + r) / sr2)
        );
    }

    function worcester(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            5 - Sigma(1, 5,
                function(n) {
                    return exp(-square(n * D / 10 + r) / sr2)
                }
            )
        );
    }

    function fita_six_zone(D, R, H) {
        var sr2 = sigma_r_2(R, H);
        return (
            10 - Sigma(1, 5,
                exp(-square(n * D / 20 + r) / sr2)
            )
            - 5 * exp(-square(6 * D / 20 + r) / sr2)
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
                                    distances)));
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
                    jQuery('#prediction').text(
                        String(rhac_score(
                            Number(val),
                            scoring,
                            rhac_units,
                            rhac_distances)))
                }
            }
            handicap_calc();
            jQuery('#handicap').change(handicap_calc);
            jQuery('#compound_scoring').change(handicap_calc);
        }
    }
);
