define(['jquery', 'algoliaAnalytics'], function ($, algoliaAnalytics) {

    var algoliaPersonalisation = {

        config: null,
        indexName: null,
        eventMap: {
            clickedObjectIDs: 'click',
        },

        track: function(algoliaConfig) {

            this.config = algoliaConfig;
            this.indexName = algoliaConfig.indexName + '_products';

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
            if (!event.enabled) {
                return;
            }

            var eventName = this.eventMap[event.method];
            this.setDataAttrs(key, event);

            if (key == 'viewProduct') {
                this.trackProductView(event);
            }

            if (key == 'filterClicked') {
                this.trackFilters(event);
            }

            if (event.selector) {
                $(document).on(eventName, event.selector, function(e) {
                    var event = self.getEventBySelector(e.handleObj.selector);

                    var objectId = $(this).data('objectid');
                    var indexName = $(this).data('indexname');

                    self.trackClick(event, objectId, indexName);
                });
            }
        },

        setDataAttrs: function(key, event) {

            if (key == 'wishlistAdd') {
                if ($(document).find(event.selector).length) {
                    $(event.selector).each(function (index, element) {
                        var params = $(element).data('post');
                        $(element).attr('data-objectid', params.data.product);
                    });
                }
            } else if (key == 'productRecommended') {
                if ($(document).find(event.selector).length) {
                    $(event.selector).each(function (index, element) {
                        if ($(element).find('[data-role="priceBox"]').length) {
                            var objectId = $(element).find('[data-role="priceBox"]').data('product-id');
                            $(element).attr('data-objectid', objectId);
                        }
                    });
                }
            }
        },

        trackProductView: function(event) {

            if ($('body').hasClass('catalog-product-view')) {
                var objectId  = $('#product_addtocart_form').find('input[name="product"]').val();
                if (objectId) {
                    var viewData = {
                        eventName: event.eventName,
                        objectIDs: [objectId + ''],
                        index: this.indexName
                    };

                    algoliaAnalytics.viewedObjectIDs(viewData);
                }
            }
        },

        trackFilters: function(event) {



        },

        trackClick: function(event, objectId, indexName) {

            var eventName = 'Clicked Object';

            var data = {
                eventName: event.eventName || eventName,
                objectIDs: [objectId + ''],
                index: indexName || this.indexName
            };

            algoliaAnalytics.clickedObjectIDs(data);
        },

    };

    return algoliaPersonalisation;

});