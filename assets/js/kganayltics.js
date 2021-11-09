var Kganayltics = (function ($) {
    var _this = {functions: {}};

    _this.functions.setClientId = function (clientId) {
        if (clientId != rex.kga.clientId) {
            if (rex.kga.debug) {
                console.debug('Sending clientId to kga_api');
            }
            $.post(rex.kga.apiUrls.setClientId, {clientId: clientId});
        }
        if (rex.kga.debug) {
            console.debug('GA4.clientId = ' + rex.kga.clientId);
        }
    };

    return _this.functions;
})(jQuery);