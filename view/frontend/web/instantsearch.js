define([
    'algoliaBundle',
], function (algoliaBundle) {
    'use strict';


	var algoliaInstantsearch = {

	    isStarted: false,
        refinementAttributes: [],
        wrapperClasses: {
            resultsWrapper: '.algolia-instant-results-wrapper',
            replacedContent: '.algolia-instant-replaced-content',
            selectorResults: '.algolia-instant-selector-results'
        },
	    selectors: {
            instantsearchWrapper: '#instant_wrapper_template',
            instantsearchSearchBar: '#instant-search-bar'
        },
        instantsearchSelector: null,

        init: function(options, configuration, helper)
        {
            this.options = options;
            this.config = configuration;
            this.helper = helper;

			if (!this.enableInstantSearch()) {
				return;
			}

			this.setupHooks();
			this.setupWrapper();

			this.initializeInstantSearch();

            return this;
        },

        enableInstantSearch: function()
		{
		    var config = this.config;

			if (!config.instant.enabled) {
			    return false;
            }

            if (!(config.isSearchPage || !config.autocomplete.enabled)) {
                return false;
            }

            this.autocompleteInput = document.querySelector(config.autocomplete.selector);
            this.instantsearchInput = document.querySelector(config.instant.selector);

            if (!config.autocomplete.enabled && this.autocompleteInput.length == 0) {
                return flase;
            }

            if (this.instantsearchInput.length <= 0) {
                throw '[Algolia] Invalid instant-search selector: ' + config.instant.selector;
            }

            if (config.autocomplete.enabled && this.instantsearchInput.querySelector(config.autocomplete.selector)) {
                throw '[Algolia] You can\'t have a search input matching "' + config.autocomplete.selector +
                '" inside you instant selector "' + config.instant.selector + '"';
            }

            var findAutocomplete = config.autocomplete.enabled
                && this.instantsearchInput.querySelector('#algolia-autocomplete-container');
            if (findAutocomplete) {
                var autocompleteContainer = this.instantsearchInput.querySelector('#algolia-autocomplete-container');
                autocompleteContainer.parentNode.removeChild(autocompleteContainer);
            }

            this.findAutocomplete = findAutocomplete;

			return true;
		},

        setupHooks: function()
        {
            /** BC of old hooks **/
            if (typeof algoliaHookBeforeInstantsearchInit === 'function') {
                this.helper.registerHook('beforeInstantsearchInit', algoliaHookBeforeInstantsearchInit);
            }

            if (typeof algoliaHookBeforeWidgetInitialization === 'function') {
                this.helper.registerHook('beforeWidgetInitialization', algoliaHookBeforeWidgetInitialization);
            }

            if (typeof algoliaHookBeforeInstantsearchStart === 'function') {
                this.helper.registerHook('beforeInstantsearchStart', algoliaHookBeforeInstantsearchStart);
            }

            if (typeof algoliaHookAfterInstantsearchStart === 'function') {
                this.helper.registerHook('afterInstantsearchStart', algoliaHookAfterInstantsearchStart);
            }
        },

        setupWrapper: function()
        {
            var self = this;

            var wrapperTemplate = algoliaBundle.Hogan.compile(document.getElementById(this.selectors.instantsearchWrapper.replace('#', '')).innerHTML);
            var instantSelector = !this.config.autocomplete.enabled ? this.config.autocomplete.selector : this.selectors.instantsearchSearchBar;
            
            var resultsWrapper = document.createElement('div');
            resultsWrapper.classList.add(this.wrapperClasses.resultsWrapper.replace('.', ''));

            var selectorResults = document.createElement('div');
            selectorResults.classList.add(this.wrapperClasses.selectorResults.replace('.', ''));

            this.instantsearchInput.classList.add(this.wrapperClasses.replacedContent.replace('.', ''));

            selectorResults.innerHTML = wrapperTemplate.render({
                second_bar: self.config.autocomplete.enabled,
                findAutocomplete: self.findAutocomplete,
                config: self.config.instant,
                translations: self.config.translations
            });

            selectorResults.style.display = 'block';
            resultsWrapper.appendChild(selectorResults);


            this.instantsearchSelector = instantSelector;
        },

        initializeInstantSearch: function()
        {
            var self = this;
            var ruleContexts = this.addRuleContexts();

            var instantsearchOptions = {
                appId: this.config.applicationId,
                apiKey: this.config.apiKey,
                indexName: this.config.indexName + '_products',
                searchParameters: {
                    hitsPerPage: this.config.hitsPerPage,
                    ruleContexts: ruleContexts
                },
                searchFunction: function(helper) {
                    if (helper.state.query === '' && !self.config.isSearchPage) {
                        document.querySelector(self.wrapperClasses.replacedContent).style.display = 'block';
                        document.querySelector(self.wrapperClasses.selectorResults).style.display = 'none';
                    } else {
                        helper.search();
                        document.querySelector(self.wrapperClasses.replacedContent).style.display = 'none';
                        document.querySelector(self.wrapperClasses.selectorResults).style.display = 'block';
                    }
                },
                routing : window.routing,
            };

            if (this.config.request.path.length > 0 && window.location.hash.indexOf('categories.level0') === -1) {
                if (this.config.areCategoriesInFacets === false) {
                    instantsearchOptions.searchParameters['facetsRefinements'] = { };
                    instantsearchOptions.searchParameters['facetsRefinements']['categories.level' + this.config.request.level] =
                        [algoliaConfig.request.path];
                } else {
                    instantsearchOptions.searchParameters['hierarchicalFacetsRefinements'] = {
                        'categories.level0': [this.config.request.path]
                    }
                }
            }

            instantsearchOptions = this.helper.triggerHooks('beforeInstantsearchInit', instantsearchOptions, algoliaBundle);

            this.instantsearch = algoliaBundle.instantsearch(instantsearchOptions);
            this.instantsearch.client.addAlgoliaAgent('Magento2 integration (' + this.config.extensionVersion + ')');
            this.instantsearchOptions = instantsearchOptions;

            this.prepareSorting();
            this.setupFacets();

            this.configureWidgets();


            // this.startInstantSearch();

        },

        addRuleContexts: function()
        {
            var ruleContexts = ['']; // Empty context to keep BC for already create rules in dashboard
            if (this.config.request.categoryId.length > 0) {
                ruleContexts.push('magento-category-' + this.config.request.categoryId);
            }

            if (this.config.request.landingPageId.length > 0) {
                ruleContexts.push('magento-landingpage-' + this.config.request.landingPageId);
            }

            return ruleContexts;
        },

        prepareSorting: function()
        {
            /** Prepare sorting indicies data */
            this.config.sortingIndices.unshift({
                name: this.config.indexName + '_products',
                label: this.config.translations.relevance
            });
        },

        setupFacets: function()
        {
            /** Setup attributes for current refinements widget **/
            var attributes = [];

            for (var i = 0; i < this.config.facets.length; i++) {
                var facet = this.config.facets[i];
                var name = facet.attribute;

                if (name === 'categories') {
                    name = 'categories.level0';
                }

                if (name === 'price') {
                    name = facet.attribute + this.config.priceKey
                }

                attributes.push({
                    name: name,
                    label: facet.label ? facet.label : facet.attribute
                });

            }

            this.refinementAttributes = attributes;
        },

        configureWidgets: function()
        {

            var self = this;
            var widgetConfiguration = {
                // infiniteHits: {},
                // hits: {},
                /**
                 * Search box
                 * Docs: https://community.algolia.com/instantsearch.js/v2/widgets/searchBox.html
                 **/
                searchBox: {
                    container: this.instantsearchSelector,
                    placeholder: this.config.translations.searchFor
                },
                /**
                 * Stats
                 * Docs: https://community.algolia.com/instantsearch.js/v2/widgets/stats.html
                 **/
                stats: {
                    container: '#algolia-stats',
                    templates: {
                        body: document.getElementById('instant-stats-template').innerHTML
                    },
                    transformData: function (data) {
                        data.first = data.page * data.hitsPerPage + 1;
                        data.last = Math.min(data.page * data.hitsPerPage + data.hitsPerPage, data.nbHits);
                        data.seconds = data.processingTimeMS / 1000;

                        data.translations = window.algoliaConfig.translations;

                        return data;
                    }
                },
                /**
                 * Sorting
                 * Docs: https://community.algolia.com/instantsearch.js/v2/widgets/sortBySelector.html
                 **/
                sortBySelector: {
                    container: '#algolia-sorts',
                    indices: this.config.sortingIndices,
                    cssClass: 'form-control'
                },
                /**
                 * Widget name: Current refinements
                 * Widget displays all filters and refinements applied on query. It also let your customer to clear them one by one
                 * Docs: https://community.algolia.com/instantsearch.js/v2/widgets/currentRefinedValues.html
                 **/
                currentRefinedValues: {
                    container: '#current-refinements',
                    cssClasses: {
                        root: 'facet'
                    },
                    templates: {
                        header: '<div class="name">' + this.config.translations.selectedFilters + '</div>',
                        clearAll: this.config.translations.clearAll,
                        item: document.getElementById('current-refinements-template').innerHTML
                    },
                    attributes: this.refinementAttributes,
                    onlyListedAttributes: true
                }
            };

            this.widgetConfiguration = widgetConfiguration;

            this.addCustomWidgets();
            // this.handleHits();

            this.buildFacets();

            // this.initializeWidgets();

        },

        addCustomWidgets: function()
        {
            var self = this;
            this.widgetConfiguration['custom'] = [
                /**
                 * Custom widget - this widget is used to refine results for search page or catalog page
                 * Docs: https://community.algolia.com/instantsearch.js/v2/guides/custom-widget.html
                 **/
                {
                    getConfiguration: function () {
                        if (self.config.request.query.length > 0 && location.hash.length < 1) {
                            return {query: self.config.request.query}
                        }
                        return {};
                    },
                    init: function (data) {
                        var page = data.helper.state.page;

                        if (self.config.request.refinementKey.length > 0) {
                            data.helper.toggleRefine(self.config.request.refinementKey, self.config.request.refinementValue);
                        }

                        data.helper.addNumericRefinement((self.config.isCategoryPage ? 'visibility_catalog' : 'visibility_search'), '=', 1);
                        data.helper.setPage(page);
                    },
                    render: function (data) {
                        if (!self.config.isSearchPage) {
                            if (data.results.query.length === 0) {
                                document.querySelector(self.wrapperClasses.replacedContent).style.display = 'block';
                                document.querySelector(self.wrapperClasses.selectorResults).style.display = 'none';
                            }
                            else {
                                document.querySelector(self.wrapperClasses.replacedContent).style.display = 'none';
                                document.querySelector(self.wrapperClasses.selectorResults).style.display = 'block';
                            }
                        }
                    }
                },
                /**
                 * Custom widget - Suggestions
                 * This widget renders suggestion queries which might be interesting for your customer
                 * Docs: https://community.algolia.com/instantsearch.js/v2/guides/custom-widget.html
                 **/
                {
                    suggestions: [],
                    init: function () {
                        if (self.config.showSuggestionsOnNoResultsPage) {
                            var $this = this;
                            var popularQueries = self.config.popularQueries.slice(0, Math.min(4, self.config.popularQueries.length));

                            for (var i = 0; i < popularQueries.length; i++) {
                                var query = self.helper.sanitizeQueryHtml(popularQueries[i]);
                                $this.suggestions.push('<a href="' + self.config.baseUrl + '/catalogsearch/result/?q=' + encodeURIComponent(query) + '">' + query + '</a>');
                            }
                        }
                    },
                    render: function (data) {
                        if (data.results.hits.length === 0) {
                            var content = '<div class="no-results">';
                            content += '<div><b>' + self.config.translations.noProducts + ' "' + self.helper.sanitizeQueryHtml(data.results.query) + '</b>"</div>';
                            content += '<div class="popular-searches">';

                            if (self.config.showSuggestionsOnNoResultsPage && this.suggestions.length > 0) {
                                content += '<div>' + self.config.translations.popularQueries + '</div>' + this.suggestions.join(', ');
                            }

                            content += '</div>';
                            content += self.config.translations.or + ' <a href="' + self.config.baseUrl + '/catalogsearch/result/?q=__empty__">' + self.config.translations.seeAll + '</a>'

                            content += '</div>';

                            document.getElementById('instant-search-results-container').innerHTML = content;
                        }
                    }
                }
            ];
        },

        handleHits: function()
        {
            var self = this;

            if (this.config.instant.infiniteScrollEnabled === true) {

                /**
                 * Products' infinite hits
                 * This widget renders all products into result page
                 * Docs: https://community.algolia.com/instantsearch.js/v2/widgets/infiniteHits.html
                 **/
                this.widgetConfiguration.infiniteHits = {
                    container: '#instant-search-results-container',
                    templates: {
                        item: document.getElementById('instant-hit-template').innerHTML
                    },
                    transformData: {
                        item: function (hit) {
                            hit = transformHit(hit, self.config.priceKey, search.helper);
                            hit.isAddToCartEnabled = self.config.instant.isAddToCartEnabled;

                            hit.algoliaConfig = self.config;

                            hit.__position = hit.__hitIndex + 1;

                            return hit;
                        }
                    },
                    showMoreLabel: self.config.translations.showMore,
                    escapeHits: true
                };

                delete this.widgetConfiguration.hits;

            } else {

                /**
                 * Products' hits
                 * This widget renders all products into result page
                 * Docs: https://community.algolia.com/instantsearch.js/v2/widgets/hits.html
                 **/
                this.widgetConfiguration.hits = {
                    container: '#instant-search-results-container',
                    templates: {
                        item: document.getElementById('instant-hit-template').innerHTML
                    },
                    transformData: {
                        item: function (hit) {
                            hit = transformHit(hit, self.config.priceKey, search.helper);
                            hit.isAddToCartEnabled = self.config.instant.isAddToCartEnabled;

                            hit.algoliaConfig = self.config;

                            var state = search.helper.state;
                            hit.__position = (state.page * state.hitsPerPage) + hit.__hitIndex + 1;

                            return hit;
                        }
                    }
                };

                /**
                 * Pagination
                 * Docs: https://community.algolia.com/instantsearch.js/v2/widgets/pagination.html
                 **/
                this.widgetConfiguration.pagination = {
                    container: '#instant-search-pagination-container',
                    cssClass: 'algolia-pagination',
                    showFirstLast: false,
                    maxPages: 1000,
                    labels: {
                        previous: this.config.translations.previousPage,
                        next: this.config.translations.nextPage
                    },
                    scrollTo: 'body'
                };

                delete this.widgetConfiguration.infiniteHits;
            }
        },

        buildFacets: function()
        {

            var facetWrapper = document.getElementById('instant-search-facets-container');
            var facets = this.config.facets;

            for (var i = 0; i < facets.length; i++) {
                var facet = facets[i];

                if (facet.attribute.indexOf("price") !== -1)
                    facet.attribute = facet.attribute + this.config.priceKey;

                facet.wrapper = facetWrapper;




            }

        },

        initializeWidgets: function() {

	        this.widgetConfiguration = this.helper.triggerHooks('beforeWidgetInitialization', this.widgetConfiguration, algoliaBundle);

	        var widgetKeys = Object.keys(this.widgetConfiguration);
	        for (var i = 0; i < widgetKeys.length; i++) {
	            var widgetType = widgetKeys[i];
	            var widgetConfig = this.widgetConfiguration[widgetType];

                if (Array.isArray(widgetConfig) === true) {
                    for (var w = 0; w < widgetConfig.length; w++) {
                        this.addWidget(widgetType, widgetConfig[w]);
                    }
                } else {
                    this.addWidget(widgetType, widgetConfig);
                }
            }
        },

        addWidget: function(type, config)
        {
            if (type === 'custom') {
                this.instantsearch.addWidget(config);
                return;
            }

            this.instantsearch.addWidget(algoliaBundle.instantsearch.widgets[type](config));
        },

        startInstantSearch: function()
        {
            if (this.isStarted === true) {
                return;
            }

            this.instantsearch = this.helper.triggerHooks('beforeInstantsearchStart', this.instantsearch, algoliaBundle)

            this.instantsearch.start();

            this.instantsearch = this.helper.triggerHooks('afterInstantsearchStart', this.instantsearch, algoliaBundle);


            /* var instant_search_bar = $(instant_selector);
            if (instant_search_bar.is(":focus") === false) {
                focusInstantSearchBar(search, instant_search_bar);
            }

            if (algoliaConfig.autocomplete.enabled) {
                $('#search_mini_form').addClass('search-page');
            }

            $(document).on('click', '.ais-hierarchical-menu--link, .ais-refinement-list--checkbox', function () {
                focusInstantSearchBar(search, instant_search_bar);
            }); */

            this.isStarted = true;
        }


	};


	return function AlgoliaInstantsearch(options, config, helper) {
	    return algoliaInstantsearch.init(options, config, helper)
    };

});
