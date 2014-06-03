function cons(a, b) { return function(k) { return k(a, b); } }
function car(l) { return l(function(a, b) { return a }) }
function cdr(l) { return l(function(a, b) { return b }) }
function linkedList(arr) {
    function $list(arr, i) {
        if (i == arr.length) {
            return null;
        } else {
            return cons(arr[i], $list(arr, i + 1));
        }
    }
    return $list(arr, 0);
}
