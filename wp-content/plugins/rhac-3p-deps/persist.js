function persist(version, qe_dialog, ob_dialog) {
    function Persist(prefix) {
        var cache = localStorage.getItem(prefix);
        if (cache === null) {
            cache = {};
        } else {
            cache = JSON.parse(cache);
        }

        function copy(data) {
            return JSON.parse(JSON.stringify(data));
        }

        function stash() {
            try {
                localStorage.setItem(prefix, JSON.stringify(cache));
            } catch (e) {
                if (e === QUOTA_EXCEEDED_ERR) {
                    jQuery(qe_dialog).dialog( 'open' );
                }
            }
        }

        this.set = function (key, value) {
            cache[key] = copy(value);
            stash();
        };

        this.get = function (key) {
            return copy(cache[key]);
        };

        this.has = function (key) {
            return cache.hasOwnProperty(key);
        };

        this.data = function () {
            return cache;
        };

        this.remove = function (key) {
            delete cache[key];
            stash();
        };
    }

    function Semi_persist() {
        var cache = {};

        this.set = function (key, value) {
            cache[key] = value;
        };

        this.get = function (key) {
            return cache[key];
        };

        this.has = function (key) {
            return cache.hasOwnProperty(key);
        };

        this.data = function () {
            return cache;
        };

        this.remove = function (key) {
            delete cache[key];
        };
    }


    if (Storage === undefined) {
        jQuery(ob_dialog).dialog( "open" );
        return new Semi_persist();
    }
    else {
        return new Persist(version);
    }
}
