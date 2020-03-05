requirejs([
    'jquery',
    'algoliaAnalytics',
    'algoliaBundle',
], function ($, algoliaAnalytics, algoliaBundle) {

    var algoliaInsights = {
        config: null,
        defaultIndexName: null,

        track: function(algoliaConfig) {

            this.config = algoliaConfig;
            this.defaultIndexName = algoliaConfig.indexName + '_products';

            if (algoliaConfig.ccAnalytics.enabled
                || algoliaConfig.personalization.enabled) {

                this.initializeAnalytics();
                this.addSearchParameters();
                this.bindData();
                this.bindEvents();
            }
        },

        initializeAnalytics: function() {
            algoliaAnalytics.init({
                appId: this.config.applicationId,
                apiKey: this.config.apiKey
            });

            var userAgent = 'insights-js-in-magento (' + this.config.extensionVersion + ')';
            algoliaAnalytics.addAlgoliaAgent(userAgent);

            var userToken = getCookie('aa-search');
            if (userToken && userToken != '') algoliaAnalytics.setUserToken(userToken);

        },

        addSearchParameters: function() {

            var self = this;
            algolia.registerHook('beforeWidgetInitialization', function (allWidgetConfiguration) {
                allWidgetConfiguration.configure = allWidgetConfiguration.configure || {};
                if (algoliaConfig.ccAnalytics.enabled) {
                    allWidgetConfiguration.configure.clickAnalytics = true;
                }

                if (algoliaConfig.personalization.enabled) {
                    allWidgetConfiguration.configure.enablePersonalization = true;
                    allWidgetConfiguration.configure.userToken = algoliaAnalytics.userToken;
                }

                return allWidgetConfiguration;
            });
        },

        bindData: function() {

            var persoConfig = this.config.personalization;

            if (persoConfig.enabled && persoConfig.clickedEvents.productRecommended.enabled) {
                $(persoConfig.clickedEvents.productRecommended.selector).each(function (index, element) {
                    if ($(element).find('[data-role="priceBox"]').length) {
                        var objectId = $(element).find('[data-role="priceBox"]').data('product-id');
                        $(element).attr('data-objectid', objectId);
                    }
                });
            }
        },

        bindEvents: function () {

            this.bindClickedEvents();
            this.bindViewedEvents();
        },

        bindClickedEvents: function() {

            var self = this;

            if (this.config.ccAnalytics.enabled || this.config.personalization.enabled) {

                // "Click" in autocomplete
                $(this.config.autocomplete.selector).each(function() {
                    $(this).on('autocomplete:selected', function (e, suggestion) {
                        var eventData = self.buildEventData(
                            'Clicked', suggestion.objectId, suggestion.__indexName, suggestion.__position, suggestion.queryID
                        );

                        self.trackClick(eventData);
                    });
                });

                $(document).on('click', this.config.ccAnalytics.ISSelector, function() {
                    var $this = $(this);
                    var eventData = self.buildEventData(
                        'Clicked', $this.data('objectid'), $this.data('indexname'), $this.data('position'), $this.data('queryid')
                    );

                    self.trackClick(eventData);
                });

            }

            if (this.config.personalization.enabled) {

                // Clicked Events
                var clickEvents = Object.keys(this.config.personalization.clickedEvents);

                for (var i = 0; i < clickEvents.length; i++) {
                    var clickEvent = this.config.personalization.clickedEvents[clickEvents[i]];
                    if (clickEvent.enabled && clickEvent.method == 'clickedObjectIDs') {
                        $(document).on('click', clickEvent.selector, function(e) {
                            var $this = $(this);
                            var event = self.getClickedEventBySelector(e.handleObj.selector);

                            var eventData = self.buildEventData(
                                event.eventName,
                                $this.data('objectid'),
                                $this.data('indexname') ? $this.data('indexname') : self.defaultIndexName
                            );

                            self.trackClick(eventData);
                        });
                    }
                }

                // Filter Clicked
                if (this.config.personalization.filterClicked.enabled) {
                    var facets = this.config.facets;
                    var containers = [];
                    for (var i = 0; i < facets.length; i++) {
                        var elem = createISWidgetContainer(facets[i].attribute);
                        containers.push('.' + elem.className);
                    }

                    algolia.registerHook('afterInstantsearchStart', function (search, algoliaBundle) {
                        var selectors = document.querySelectorAll(containers.join(', '));
                        selectors.forEach(function (e) {
                            e.addEventListener('click', function (event) {
                                var attribute = this.dataset.attr;
                                var elem = event.target;
                                if (elem.matches("input[type=checkbox]") && elem.checked) {
                                    var filter = attribute + ':' + elem.value;
                                    self.trackFilterClick([filter]);
                                }
                            });
                        });

                        return search;
                    });
                }
            }
        },

        getClickedEventBySelector: function(selector) {

            var events = this.config.personalization.clickedEvents,
                keys = Object.keys(events);

            for (var i = 0; i < keys.length; i++) {
                if (events[keys[i]].selector == selector) {
                    return events[keys[i]];
                }
            }

            return {};
        },

        bindViewedEvents: function() {

            var self = this;

            if (this.config.personalization.viewedEvents.viewProduct.enabled) {
                $(document).ready(function () {
                    if ($('body').hasClass('catalog-product-view')) {
                        var objectId = $('#product_addtocart_form').find('input[name="product"]').val();
                        if (objectId) {
                            var viewData = self.buildEventData('Viewed Product', objectId, self.defaultIndexName);
                            self.trackView(viewData);
                        }
                    }
                });
            }
        },

        buildEventData: function(eventName, objectId, indexName, position = null, queryId = null) {

            var eventData = {
                eventName: eventName,
                objectIDs: [objectId + ''],
                index: indexName
            };

            if (position) {
                eventData.positions = [parseInt(position)];
            }

            if (queryId) {
                eventData.queryID = queryId;
            }

            return eventData;
        },

        trackClick: function(eventData) {
            if (eventData.queryID) {
                algoliaAnalytics.clickedObjectIDsAfterSearch(eventData);
            } else {
                algoliaAnalytics.clickedObjectIDs(eventData);
            }
        },

        trackFilterClick: function(filters) {

            var eventData = {
                index: this.defaultIndexName,
                eventName: 'Filter Clicked',
                filters: filters
            };

            algoliaAnalytics.clickedFilters(eventData);
        },

        trackView: function(eventData) {
            algoliaAnalytics.viewedObjectIDs(eventData);
        },

        trackConversion: function(eventData) {
            if (eventData.queryID) {
                algoliaAnalytics.convertedObjectIDsAfterSearch(eventData);
            } else {
                algoliaAnalytics.convertedObjectIDs(eventData);
            }
        }

    };

    algoliaBundle.$(function ($) {
        algoliaInsights.track(algoliaConfig);
    });

});