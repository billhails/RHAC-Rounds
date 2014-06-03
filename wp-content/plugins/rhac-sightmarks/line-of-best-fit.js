function lineOfBestFit(ax, ay) {
    var xs = linkedList(ax);
    var ys = linkedList(ay);

    function sum(l) {
        if (l === null) { return 0; }
        return car(l) + sum(cdr(l));
    }

    function length(l) {
        if (l === null) { return 0; }
        return 1 + length(cdr(l));
    }

    function mean(a) {
        return sum(a) / length(a);
    }

    function sumOfProducts(xs, ys) {
        if (xs === null) { return 0; }
        return car(xs) * car(ys) + sumOfProducts(cdr(xs), cdr(ys));
    }

    function square(x) {
        return x * x;
    }

    function sumOfSquares(xs) {
        if (xs === null) {
            return 0;
        }
        return square(car(xs)) + sumOfSquares(cdr(xs));
    }

    var n = length(xs);

    if (n < 1 || n != length(ys)) {
        return function(x) { return 0; }
    }

    var sumX = sum(xs);
    var sumY = sum(ys);
    var m = (sumOfProducts(xs, ys) - (sumX * sumY) / n)
            /
            (sumOfSquares(xs) - (sumX * sumX) / n);
    var c = sumY / n - m * sumX / n;

    return function(x) {
        return m * x + c;
    }
}
