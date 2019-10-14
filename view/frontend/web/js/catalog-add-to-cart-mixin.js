define(['jquery', 'mageUtils'], function ($, utils) {
    'use strict';

    return function (targetWidget) {
        $.widget('mage.catalogAddToCart', targetWidget, {

            algoliaParameters: [
                'queryID',
                'indexName',
                'objectID'
            ],

            submitForm: function(form) {

                this.addAlgoliaConversionData(form);

                // call parent function
                this._super(form);
            },

            addAlgoliaConversionData: function(form) {

                var self = this;
                var params = utils.getUrlParameters(window.location.href);

                $.each(params, function(key, value) {
                    if (self.algoliaParameters.indexOf(key) > -1) {
                        form.append('<input type="hidden" name="as_'+ key + '" value="' + value + '" />');
                    }
                });

            }
        });
        return $.mage.catalogAddToCart;
    }

});