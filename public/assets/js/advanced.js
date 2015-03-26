/*
 * advanced ads functions to be used directly within ad codes
 */

advads = {
    /**
     * check if the ad is displayed more than {max} times per session
     * every check increases the counter
     *
     * @param {string} name (no id needed, just any id-formated string)
     * @param {type} max number of maximum times the ad can be displayed within the period
     * @returns {bool} true if limit is reached
     */
    max_per_session: function(name, max){
        var num = 1;
        if(max === undefined || parseInt(max) === 0) max = 1;

        // check if cookie exists and get the value
        if(this.cookie_exists(name)){
            if(this.get_cookie(name) >= max) return true;
            num = num + parseInt(this.get_cookie(name));
        }
        this.set_cookie(name, num);
        return false;
    },
    /**
     * get a cookie value
     *
     * @param {str} name of the cookie
     */
    get_cookie: function (name) {
        var i, x, y, ADVcookies = document.cookie.split(";");
        for (i = 0; i < ADVcookies.length; i++)
        {
            x = ADVcookies[i].substr(0, ADVcookies[i].indexOf("="));
            y = ADVcookies[i].substr(ADVcookies[i].indexOf("=") + 1);
            x = x.replace(/^\s+|\s+$/g, "");
            if (x === name)
            {
                return unescape(y);
            }
        }
    },
    /**
     * set a cookie value
     *
     * @param {str} name of the cookie
     * @param {str} value of the cookie
     * @param {int} exdays days until cookie expires
     *  set 0 to expire cookie immidiatelly
     *  set null to expire cookie in the current session
     */
    set_cookie: function (name, value, exdays, path, domain, secure) {
        var exdate = new Date();
        exdate.setDate(exdate.getDate() + exdays);
        document.cookie = name + "=" + escape(value) +
                ((exdays == null) ? "" : "; expires=" + exdate.toUTCString()) +
                ((path == null) ? "; path=/" : "; path=" + path) +
                ((domain == null) ? "" : "; domain=" + domain) +
                ((secure == null) ? "" : "; secure");
    },
    /**
     * check if a cookie is set and contains a value
     *
     * @param {str} name of the cookie
     * @returns {bool} true, if cookie is set
     */
    cookie_exists: function (name)
    {
        var c_value = this.get_cookie(name);
        if (c_value !== null && c_value !== "" && c_value !== undefined)
        {
            return true;
        }
        return false;
    }
};