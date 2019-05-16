define([
    'algoliaBundle',
], function (algoliaBundle) {
    'use strict';

    var algoliaAutocomplete = {

        options: {},
        config: {},
        client: null,
        helper: null,
		sources: [],
        selectors: {
            dropdownMenu: '#menu-template',
            dropdownMenuContainer: '.aa-dropdown-menu',
            autocompleteContainer: '#algolia-autocomplete-container',
            clearQueryToggle: '.clear-query-autocomplete',
            magnifyingGlass: '.magnifying-glass'
        },

        init: function(options, configuration, helper)
        {
            this.options = options;
            this.config = configuration;
            this.helper = helper;

            if (!this.config.autocomplete.enabled) {
                return false;
            }

            this.setTemplates();
            this.initClient();

            this.addRequiredSections();
            this.setupAutocompleteSearch();

            this.observeInputEvents();

            return this;
        },

        setTemplates: function()
        {
            this.config.autocomplete.templates = {
                suggestions: algoliaBundle.Hogan.compile(this._getTemplateHtml(this.options.suggestionsTemplate)),
                products: algoliaBundle.Hogan.compile(this._getTemplateHtml(this.options.productsTemplate)),
                categories: algoliaBundle.Hogan.compile(this._getTemplateHtml(this.options.categoriesTemplate)),
                pages: algoliaBundle.Hogan.compile(this._getTemplateHtml(this.options.pagesTemplate)),
                additionalSection: algoliaBundle.Hogan.compile(this._getTemplateHtml(this.options.additionalSectionTemplate))
            };
        },

        initClient: function()
        {
            var algolia_client = algoliaBundle.algoliasearch(this.config.applicationId, this.config.apiKey);
            algolia_client.addAlgoliaAgent('Magento2 integration (' + this.config.extensionVersion + ')');

            this.client = algolia_client;
        },

        addRequiredSections: function()
        {
            /** Add products and categories that are required sections **/
            /** Add autocomplete menu sections **/
            if (this.config.autocomplete.nbOfProductsSuggestions > 0) {
                this.config.autocomplete.sections.unshift({
                    hitsPerPage: this.config.autocomplete.nbOfProductsSuggestions,
                    label: this.config.translations.products, name: "products"
                });
            }

            if (this.config.autocomplete.nbOfCategoriesSuggestions > 0) {
                this.config.autocomplete.sections.unshift({
                    hitsPerPage: this.config.autocomplete.nbOfCategoriesSuggestions,
                    label: this.config.translations.categories, name: "categories"
                });
            }

            if (this.config.autocomplete.nbOfQueriesSuggestions > 0) {
                this.config.autocomplete.sections.unshift({
                    hitsPerPage: this.config.autocomplete.nbOfQueriesSuggestions,
                    label: '', name: "suggestions"
                });
            }

            this.setAutocompleteSources();

        },

        setAutocompleteSources: function()
        {
            /** Setup autocomplete data sources **/
            var sources = [],
                i = 0,
                self = this;

            var sections = this.config.autocomplete.sections;
            var fixedSections = ['products','categories', 'pages', 'suggestions'];

            for (var c = 0; c < sections.length; c++) {

                var section = sections[c];
                var source = this.getAutocompleteSource(section, i);

                if (source) {
                    sources.push(source);
                }

                if (fixedSections.indexOf(section.name) === -1) {
                    i++;
                }
			}

            this.sources = sources;
        },

		getAutocompleteSource: function(section, i)
		{
			if (section.hitsPerPage <= 0)
				return null;

			var self = this;

			var options = {
				hitsPerPage: section.hitsPerPage,
                analyticsTags: 'autocomplete',
                clickAnalytics: true
            };

			// create source object with name of section
            var source = {
                name: section.name,
                indexName: self.client.initIndex(algoliaConfig.indexName + "_" + section.name)
            };

			if (section.name === 'products') {

			    var additionalOptions = {
                    facets: ['categories.level0'],
                    numericFilters: 'visibility_search=1',
                    ruleContexts: ['magento_filters', '']
                };

				this.setProductSourceTemplates(section, source);

			} else if (section.name === 'categories' || section.name === 'pages') {

                if (section.name === 'categories'
                    && !self.config.showCatsNotIncludedInNavigation) {
                    options.numericFilters = 'include_in_menu=1';
                }

                source.templates = {
                    empty: function() {

                        var emptyHtml = '<div class="aa-no-results">';
                        emptyHtml += self.config.translations.noResults;
                        emptyHtml += '</div>';

                        return emptyHtml;
                    },
                    suggestion: function (hit, payload) {
                        if (section.name === 'categories') {
                            hit.displayKey = hit.path;
                        }

                        if (hit._snippetResult
                            && hit._snippetResult.content
                            && hit._snippetResult.content.value.length > 0) {

                            hit.content = hit._snippetResult.content.value;
                            if (hit.content.charAt(0).toUpperCase() !== hit.content.charAt(0)) {
                                hit.content = '&#8230; ' + hit.content;
                            }

                            if (['.', '!', '?'].indexOf(hit.content.charAt(hit.content.length - 1)) != -1) {
                                hit.content = hit.content + ' &#8230;';
                            }

                            if (hit.content.indexOf('<em>') === -1) {
                                hit.content = '';
                            }
                        }

                        hit.displayKey = hit.displayKey || hit.name;

                        hit.__queryID = payload.queryID;
                        hit.__position = payload.hits.indexOf(hit) + 1;

                        return self.config.autocomplete.templates[section.name].render(hit);
                    }
                }

            } else if (section.name === 'suggestions') {

                this.setSuggestionsSource(section, source, options);

            } else {

                /** additional section **/
                var indexName = self.client.initIndex(algoliaConfig.indexName + "_section_" + section.name);

                var additionalSource = {
                    indexName: indexName,
                    source: algoliaBundle.autocomplete.sources.hits(indexName, options),
                    displayKey: 'value',
                    name: i,
                    templates: {
                        suggestion: function (hit, payload) {
                            hit.url = self.config.baseUrl + '/catalogsearch/result/?q=' + hit.value + '&refinement_key=' + section.name;

                            hit.__queryID = payload.queryID;
                            hit.__position = payload.hits.indexOf(hit) + 1;

                            return self.config.autocomplete.templates.additionalSection.render(hit);
                        }
                    }
                };

                source = Object.assign(source, additionalSource);

            }

            if (section.name !== 'suggestions' && section.name !== 'products') {
                source.templates.header = '<div class="category">'
                    + (section.label ? section.label : section.name)
                    + '</div>';
            }

            // Merge additional options
			if (typeof additionalOptions != 'undefined') {
			    options = Object.assign(options, additionalOptions);
            }

            if (typeof source.source == 'undefined') {
                // Add source with unique options per section
                source.source = algoliaBundle.autocomplete.sources.hits(
                    self.client.initIndex(algoliaConfig.indexName + "_" + section.name), options);

            }

            return source;
		},

        setProductSourceTemplates: function(section, source)
        {
            var self = this;

            source.templates = {
                empty: function (query) {

                    var template = '<div class="aa-no-results-products">';
                    template += '<div class="title">' + self.config.translations.noProducts
                        + ' "' + self.helper.sanitizeQueryHtml(query.query) + '"'
                        + '</div>';


                    var suggestions = [];
                    if (self.config.showSuggestionsOnNoResultsPage && self.config.popularQueries.length > 0) {

                        var popularQueries = algoliaConfig.popularQueries.slice(0, Math.min(3, algoliaConfig.popularQueries.length));
                        for (var pq = 0; pq < popularQueries.length; popularQueries++) {

                            var query = self.helper.sanitizeQueryHtml(popularQueries[pq]);
                            suggestions.push('<a href="' + algoliaConfig.baseUrl + '/catalogsearch/result/?q=' + encodeURIComponent(query) + '">' + query + '</a>');

                        }

                        template += '<div class="suggestions"><div>' + algoliaConfig.translations.popularQueries + '</div>';
                        template += '<div>' + suggestions.join(', ') + '</div>';
                        template += '</div>';
                    }

                    template += '<div class="see-all">'
                        + (suggestions.length > 0 ? self.config.translations.or + ' ' : '')
                        + '<a href="' + self.config.baseUrl + '/catalogsearch/result/?q=__empty__">'
                        + self.config.translations.seeAll
                        + '</a>'
                        + '</div>';

                    template += '</div>';

                    return template;
                },
                suggestion: function(hit, payload) {
                    hit = transformHit(hit, self.config.priceKey);

                    hit.displayKey = hit.displayKey || hit.name;

                    hit.__queryID = payload.queryID;
                    hit.__position = payload.hits.indexOf(hit) + 1;

                    return self.config.autocomplete.templates[section.name].render(hit);
                },
                footer: function(query, content) {

                    var keys = [];
                    for (var i = 0; i<self.config.facets.length; i++) {
                        if (self.config.facets[i].attribute == "categories") {
                            for (var key in content.facets['categories.level0']) {
                                var url = self.config.baseUrl + '/catalogsearch/result/?q=' + encodeURIComponent(query.query) + '#q=' + encodeURIComponent(query.query) + '&hFR[categories.level0][0]=' + encodeURIComponent(key) + '&idx=' + self.config.indexName + '_products';
                                keys.push({
                                    key: key,
                                    value: content.facets['categories.level0'][key],
                                    url: url
                                });
                            }
                        }
                    }

                    keys.sort(function (a, b) {
                        return b.value - a.value;
                    });

                    var ors = '';

                    if (keys.length > 0) {
                        var orsTab = [];
                        for (var i = 0; i < keys.length && i < 2; i++) {
                            orsTab.push('<span><a href="' + keys[i].url + '">' + keys[i].key + '</a></span>');
                        }
                        ors = orsTab.join(', ');
                    }

                    var allUrl = algoliaConfig.baseUrl + '/catalogsearch/result/?q=' + encodeURIComponent(query.query);
                    var returnFooter = '<div id="autocomplete-products-footer">' + algoliaConfig.translations.seeIn + ' <span><a href="' + allUrl +  '">' + algoliaConfig.translations.allDepartments + '</a></span> (' + content.nbHits + ')';

                    if (ors && algoliaConfig.instant.enabled) {
                        returnFooter += ' ' + algoliaConfig.translations.orIn + ' ' + ors;
                    }

                    returnFooter += '</div>';

                    return returnFooter;
                }
            }

        },

        setSuggestionsSource: function(section, source, options)
        {
            var self = this;

            var additionalSource = {
                source: algoliaBundle.autocomplete.sources.popularIn(
                    self.client.initIndex(self.config.indexName + "_suggestions"),
                    options,
                    {
                        source: 'query',
                        index: self.client.initIndex(self.config.indexName + "_products"),
                        facets: ['categories.level0'],
                        hitsPerPage: 0,
                        typoTolerance: false,
                        maxValuesPerFacet: 1,
                        analytics: false
                    }, {
                        includeAll: true,
                        allTitle: self.config.translations.allDepartments
                    }
                ),
                displayKey: 'query',
                templates: {
                    suggestion: function (hit, payload) {
                        if (hit.facet) {
                            hit.category = hit.facet.value;
                        }

                        if (hit.facet && hit.facet.value !== self.config.translations.allDepartments) {
                            hit.url = self.config.baseUrl + '/catalogsearch/result/?q=' + hit.query + '#q=' + hit.query + '&hFR[categories.level0][0]=' + encodeURIComponent(hit.category) + '&idx=' + self.config.indexName + '_products';
                        } else {
                            hit.url = self.config.baseUrl + '/catalogsearch/result/?q=' + hit.query;
                        }

                        var toEscape = hit._highlightResult.query.value;
                        hit._highlightResult.query.value = algoliaBundle.autocomplete.escapeHighlightedString(toEscape);

                        hit.__queryID = payload.queryID;
                        hit.__position = payload.hits.indexOf(hit) + 1;

                        return self.config.autocomplete.templates.suggestions.render(hit);
                    }
                }
            };

            source = Object.assign(source, additionalSource);

        },

        setupAutocompleteSearch: function()
        {
            var self = this,
                options = {
                    hint: false,
                    templates: {
                        dropdownMenu: self.selectors.dropdownMenu
                    },
                    dropdownMenuContainer: self.selectors.autocompleteContainer,
                    debug: self.config.autocomplete.isDebugEnabled
                };

            if (isMobile() === true) {
                // Set debug to true, to be able to remove keyboard and be able to scroll in autocomplete menu
                options.debug = true;
            }

            if (self.config.removeBranding === false) {
                options.templates.footer = '<div class="footer_algolia"><a href="https://www.algolia.com/?utm_source=magento&utm_medium=link&utm_campaign=magento_autocompletion_menu" title="Search by Algolia" target="_blank"><img src="'  +algoliaConfig.urls.logo + '"  alt="Search by Algolia" /></a></div>';
            }

            var sources = self.helper.triggerHooks('beforeAutocompleteSources', this.sources, self.client, algoliaBundle);
            options = self.helper.triggerHooks('beforeAutocompleteOptions', options);

            // Keep for backward compatibility
            if (typeof algoliaHookBeforeAutocompleteStart === 'function') {
                console.warn('Deprecated! You are using an old API for Algolia\'s front end hooks. ' +
                    'Please, replace your hook method with new hook API. ' +
                    'More information you can find on https://www.algolia.com/doc/integration/magento-2/customize/custom-front-end-events/');

                var hookResult = algoliaHookBeforeAutocompleteStart(sources, options, self.client);

                sources = hookResult.shift();
                options = hookResult.shift();
            }

            var inputAutocomplete = algoliaBundle.autocomplete(self.config.autocomplete.selector, options, sources)
                .on('autocomplete:updated', function (e) {
                    self.fixAutocompleteCssSticky(e.srcElement);
                    self.fixAutocompleteCssHeight();
                }).on('autocomplete:selected', function (e, suggestion, dataset) {
                    location.assign(suggestion.url);
                });


            this.iosOnClick(inputAutocomplete);
            this.inputAutocomplete = inputAutocomplete;

            window.addEventListener("resize", function() {
                self.fixAutocompleteCssSticky(self.inputAutocomplete[0]);
            });

            var inputContainer = document.getElementsByClassName(self.config.autocomplete.selector.replace('.', ''));
            for (var i = 0; i < inputContainer.length; i++) {
                var parentContainer = inputContainer[i].parentNode;
                parentContainer.setAttribute('id', 'algolia-autocomplete-tt');
            }

        },

        iosOnClick: function(element)
        {
            var data = element.data('aaAutocomplete')

            var dropdown = data.dropdown;
            var suggestionClass = '.' + dropdown.cssClasses.prefix + dropdown.cssClasses.suggestion;

            var touchmoved;
            dropdown.$menu.on('touchend', suggestionClass, function (e) {
                if (touchmoved === false) {
                    e.preventDefault();
                    e.stopPropagation();

                    var url = element.find('a').attr('href');
                    location.assign(url);
                }
            }).on('touchmove', function (){
                touchmoved = true;
            }).on('touchstart', function(){
                touchmoved = false;
            });
        },

        fixAutocompleteCssSticky: function(menu)
        {
            var autocompleteContainer = document.getElementById(this.selectors.autocompleteContainer.replace('#', ''));
            var dropdownMenu = autocompleteContainer.querySelector(this.selectors.dropdownMenuContainer);

            var menuDimensions = menu.getBoundingClientRect();
            var containerDimensions = autocompleteContainer.getBoundingClientRect();

            autocompleteContainer.classList.remove('reverse');

            /** Reset computation **/
            dropdownMenu.style.top = '0px';

            /** Stick menu vertically to the input **/
            var targetOffset = Math.round(menuDimensions.top + menu.offsetHeight);
            var currentOffset = Math.round(containerDimensions.top);

            dropdownMenu.style.top = (targetOffset - currentOffset) + 'px';

            if (menuDimensions.left + menu.offsetWidth / 2 > document.body.clientWidth / 2) {
                /** Stick menu horizontally align on right to the input **/
                dropdownMenu.style.right = '0px';
                dropdownMenu.style.left = 'auto';

                var targetOffset = Math.round(menuDimensions.left + menu.offsetWidth);
                var currentOffset = Math.round(containerDimensions.left + autocompleteContainer.offsetWidth);

                dropdownMenu.style.right = (currentOffset - targetOffset) + 'px';
            }
            else {
                /** Stick menu horizontally align on left to the input **/
                dropdownMenu.style.left = 'auto';
                dropdownMenu.style.right = '0px';
                autocompleteContainer.classList.add('reverse');

                var targetOffset = Math.round(menuDimensions.left);
                var currentOffset = Math.round(containerDimensions.left);

                dropdownMenu.style.left = (targetOffset - currentOffset) + 'px';
            }
        },

        fixAutocompleteCssHeight: function()
        {
            if (document.body.clientWidth > 768) {

                var container = document.getElementById(this.selectors.autocompleteContainer.replace('#', ''));
                var otherSection = container.querySelector('.other-sections');
                var productSection = container.querySelector('.aa-dataset-products');

                var height = Math.max(otherSection.offsetHeight, productSection.offsetHeight);

                productSection.style.minHeight = height + 'px';
            }
        },

        observeInputEvents: function()
        {
            var self = this,
                autocompleteInputs = document.getElementsByClassName(this.config.autocomplete.selector.replace('.', ''));

            for (var i = 0; i < autocompleteInputs.length; i++) {
                var input = autocompleteInputs[i];
                var searchForm = this._closeParentTag(input, 'form');

                searchForm.addEventListener('submit', function(e) {

                    var searchInput = this.querySelector(self.config.autocomplete.selector);
                    var query  = searchInput.value;

                    query = encodeURIComponent(query);

                    if (self.config.instant.enabled && query === '')
                        query = '__empty__';

                    window.location = this.getAttribute('action') + '?q=' + query;
                    return false;
                });

                input.addEventListener('input', function(e) {
                    self._handleInputCrossAutocomplete(this);
                });

            }

            var clearToggle = document.querySelector(this.selectors.clearQueryToggle);
            clearToggle.addEventListener('click', function(e) {
                var searchInput = self._closeParentTag(this, 'form').querySelector('input');

                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));

                self._handleInputCrossAutocomplete(searchInput);
            });

        },

        _getTemplateHtml: function(templateId)
        {
            var element = document.getElementById(templateId.replace('#', ''));
            return element.innerHTML;
        },

        _handleInputCrossAutocomplete: function(input)
        {
            var searchForm = this._closeParentTag(input, 'form');
            if (input.value.length > 0) {
                searchForm.querySelector(this.selectors.clearQueryToggle).style.display = 'block';
                searchForm.querySelector(this.selectors.magnifyingGlass).style.display = 'none';
            } else {
                searchForm.querySelector(this.selectors.clearQueryToggle).style.display = 'none';
                searchForm.querySelector(this.selectors.magnifyingGlass).style.display = 'block';
            }
        },

        _closeParentTag: function(element, tag)
        {
            while (element.tagName.toLowerCase() != tag) {
                element = element.parentNode;
                if (!element) {
                    return null;
                }
            }

            return element;
        }

    };

    return function AlgoliaAutocomplete(options, config, helper) {
        return algoliaAutocomplete.init(options, config, helper);
    };


});