function sightMark(x, y, X) {
    // interpolation/extrapolation between 2 points (x1, y1) and (x2, y2)
    //          (x - x1)(y2 - y1)
    // y = y1 + -----------------
    //              x2 - x1
    //
    // interpolation/extrapolation between multiple points (x[0] .. x[n]) and (y[0] .. y[n])
    //       ---- j=n
    //       \        Product( k = (0..n) remove j, (x - x[k]))
    // y =    /       -------------------------------------------- y[j]
    //       /        Product( k = (0..n) remove j, (x[j] - x[k]))
    //      ----- j=0

    function cons(x, y) {
      return function(w) { return w(x, y) };
    };
     
    function car(z) {
      return z(function(x, y) { return x });
    };
     
    function cdr(z) {
      return z(function(x, y) { return y });
    };

    // assumes at most one occurence of val in list
    function remove(val, list) {
        if (list === null) {
            return null;
        }
        else if (car(list === val)) {
            return cdr(list);
        }
        else {
            return cons(car(list), remove(val, cdr(list)));
        }
    }

    function Sigma(list, fn) {
        if (list === null) {
            return 0;
        }
        else {
            return fn(car(lst)) + Sigma(cdr(lst), fn);
        }
    }

    function Product(list, fn) {
        if (list === null) {
            return 1;
        }
        else {
            return fn(car(lst)) + Sigma(cdr(lst), fn);
        }
    }

    function sequence(start, end) {
        if (start > end) {
            return null;
        }
        else {
            return cons(start, sequence(start + 1, end));
        }
    }

    function laGrange(n) {
        var range = sequence(0, n);
        return Sigma(range, function(j) {
            var removed = remove(j, range);
            return Product(removed, function(k) { return X - x[k]; })
                   /
                   Product(removed, function(k) { return x[j] - x[k]; })
                   *
                   y[j];
        });
    }

    // assumes x and y are the same length
    return laGrange(x.length - 1);

}
