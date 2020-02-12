define(['jquery', 'algoliaAnalytics'], function ($, algoliaAnalytics) {

    var algoliaPersonalisation = {

        config: null,
        eventMap: {
            clickedObjectIDs: 'click',
        },

        track: function(algoliaConfig) {

            console.log('tracking');
            this.config = algoliaConfig;

            this.addSearchParameters();
            this.observeEvents();
        },

        addSearchParameters: function() {

            console.log('addSearchParameters');

            algolia.registerHook('beforeWidgetInitialization', function (allWidgetConfiguration) {
                allWidgetConfiguration.configure = allWidgetConfiguration.configure || {};
                allWidgetConfiguration.configure.enablePersonalization = true;
                // allWidgetConfiguration.configure.userToken = '123456';
                return allWidgetConfiguration;
            });

        },

        observeEvents: function() {

            console.log('observeEvents');

            var self = this,
                selector = this.config.personalization;

            var keys = Object.keys(selector);
            for (var i = 0; i < keys.length; i++) {
                self.mapEvent(keys[i], selector[keys[i]]);
            }

        },

        mapEvent: function(key, selector) {

            if (selector.selector) {
                var eventName = this.eventMap[selector.method];
                $(document).on(eventName, selector.selector, function() {
                    console.log('your mom');
                });
            }

        }

    };

    return algoliaPersonalisation;

});