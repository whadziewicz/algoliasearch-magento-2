define(['jquery', 'algoliaAnalytics'], function ($, algoliaAnalytics) {

    var algoliaPersonalisation = {

        config: null,
        eventMap: {
            clickedObjectIDs: 'click',
        },

        track: function(algoliaConfig) {

            this.config = algoliaConfig;
            this.addSearchParameters();
            this.observeEvents();
        },

        addSearchParameters: function() {
            algolia.registerHook('beforeWidgetInitialization', function (allWidgetConfiguration) {
                allWidgetConfiguration.configure = allWidgetConfiguration.configure || {};
                allWidgetConfiguration.configure.enablePersonalization = true;
                // allWidgetConfiguration.configure.userToken = '123456';

                return allWidgetConfiguration;
            });
        },

        observeEvents: function() {
            var self = this,
                events = this.config.personalization;

            var keys = Object.keys(events);
            for (var i = 0; i < keys.length; i++) {
                self.mapEvent(keys[i], events[keys[i]]);
            }
        },

        getEventBySelector: function(selector) {

            var events = this.config.personalization,
                keys = Object.keys(events);

            for (var i = 0; i < keys.length; i++) {
                if (events[keys[i]].selector == selector) {
                    return events[keys[i]];
                }
            }

            return {};
        },

        mapEvent: function(key, event) {

            var self = this;
            if (event.enabled && event.selector) {
                var eventName = this.eventMap[event.method];
                $(document).on(eventName, event.selector, function(e) {

                    var obj = e.handleObj;
                    var event = self.getEventBySelector(obj.selector);

                    var objectId = $(this).data('objectid');
                    var indexName = $(this).data('indexname');

                    self.trackClick(event, objectId, indexName);
                });
            }

        },

        trackClick: function(event, objectId, indexName) {

            var eventName = 'Clicked Object';

            var data = {
                eventName: event.eventName ? event.eventName : eventName,
                objectIDs: [objectId + ''],
                index: indexName
            };

            algoliaAnalytics.clickedObjectIDs(data);
        }

    };

    return algoliaPersonalisation;

});