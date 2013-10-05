function rhac_score(H, fn, distances) {

    function square(x) {
        return x * x;
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
        return (
            9 - 2 * Sigma(1, 4,
                function(n) {
                    return (
                        Math.exp(
                            -square(n * D / 10 + r)
                            / sigma_r_2(R, H)
                        )
                    )
                }
            )
            - Math.exp(
                    -square(D / 2 + r)
                    / sigma_r_2(R, H)
            )
        );
    }

    function metric(D, R, H) {
        return (
            10 - Sigma(1, 10,
                function(n) {
                    return Math.exp(
                        -square(n * D / 20 + r)
                        / sigma_r_2(R, H)
                    )
                }
            )
        );
    }

    function inner_ten(D, R, H) {
        return(
            10 - Math.exp(-Math.pow(D/40-r,2)/Math.pow(sigma_r(R, H),2))
            - Sigma(2,10,function(n){
                return Math.exp(-Math.pow(n*D/20+r,2)/Math.pow(sigma_r(R,H),2))
            })
        );
    }

    var functions = {
        metric: metric,
        imperial: imperial,
        inner_ten: inner_ten
    };

    var y2m = 0.9144;

    var conversions = {
        metric: 1.0,
        imperial: y2m,
        inner_ten: 1.0
    };

    var total = 0;
    for (var i = 0; i < distances.length; i++) {
        total += distances[i].N *
            functions[fn](distances[i].D,
                          distances[i].R * conversions[fn],
                          H);
    }
    return Math.round(total);
}

var rhac_distances = new Array(); // in-line script will assign to this
var rhac_measure = ''; //ditto

jQuery(
    function() {
        if (jQuery('#handicap').val()) {
            function handicap_calc() {
                jQuery('#prediction').text(
                    String(rhac_score(
                        Number(jQuery('#handicap').val()),
                        rhac_measure,
                        rhac_distances)))
            }
            handicap_calc();
            jQuery('#handicap').change(handicap_calc);
        }
    }
);
