
/*
 * Javascripts for Engagement Council plugin
 */

var map;
jQuery(document).ready(function ($) {

    // see complete object definition:
    // https://docs.google.com/document/d/1RJRDP-tZLFzAeMUvd72Eig9QyoI7pM1XLYB7WHQa5X0/edit?usp=sharing
    var ECI = {
        map: 'impact-map',
        cssGeog: '.ecpp-geog',
        selectcssGeogID: 'filters-container-regions',
        filterGeog: '#filter-geography',
        filters: ["theme", "type", "affiliation"],
        igeog: 0,
        geoid: [],
        geog: [{
            geo_key: "050",
            layer_ids: [4, 5],
            select_ids: [6, 7]
        }, {
            geo_key: "970",
            layer_ids: [24, 25],
            select_ids: [26, 27]
        }, {
            geo_key: "610",
            layer_ids: [36, 37],
            select_ids: [38, 39]
        }, {
            geo_key: "620",
            layer_ids: [32, 33],
            select_ids: [34, 35]
        }, {
            geo_key: "500",
            layer_ids: [40, 41],
            select_ids: [42, 43]
        }]
    };

    /**
    * Send a request to an API service to get data.
    * @param {string} service - API endpoint and parameters.
    * @param {object} data - The data posted to API.
    * @param {requestCallback} callback - The callback function to execute after the API request is succefully completed.
    * @param {requestCallback} [fallback] - The callback function to execute when the API request returns an error.
    */
    function api(type, service, data, callback, fallback) {
        service = (/^http/i.test(service)) ? service : "https://services.engagementnetwork.org/" + service;

        var param = {
            type: type,
            url: service,
            dataType: "json",
            contentType: "application/json; charset=utf-8",
            crossDomain: true,
            success: callback,
            error: fallback || $.noop
        };
        if (data && typeof data !== "undefined") {
            if (type === "post") {
                param.data = JSON.stringify(data);
            } else {
                param.url += "?" + $.param(data);
            }
        }
        $.ajax(param);
    }

    /**
     * Call local WordPress REST API
     * @param {any} service
     * @param {any} data
     * @param {any} callback
     */
    function apiECI(service, data, callback) {
        var url = "https://engagements.missouri.edu/wp-json/wp/v2/";
        api("get", url + service, data, callback);
    }

    /**
    * START
    */
    if ($('#' + ECI.map).length) {
        var layerSelect, layerECI, legendECI;

        // initialize the map
        map = L.map(ECI.map, {
            attributionControl: false,
            minZoom: 7,
            maxZoom: 13,
            loadingControl: true,
            scrollWheelZoom: false
        }).setView([38.333, -92.34], 7);

        // add ESRI's World Terrace Base basemap
        L.esri.tiledMapLayer({
            url: "https://server.arcgisonline.com/ArcGIS/rest/services/World_Terrain_Base/MapServer"
        }).addTo(map);

        // get the statewide map extent
        ECI.bounds = map.getBounds();
        map.setMaxBounds(ECI.bounds);

        // custom 'zoom to Missouri' button
        var moZoomControl = L.Control.extend({
            options: {
                position: 'topleft'
            },
            onAdd: function (map) {
                var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                var aTag = L.DomUtil.create('a', 'leaflet-control-custom', container);
                aTag.href = "#";
                container.onclick = function (e) {
                    map.flyToBounds(ECI.bounds);
                    e.stopPropagation();
                }
                return container;
            },
        });
        map.addControl(new moZoomControl());

        // add ECI density, boundary and reference map layers
        addMapLayers();

        // geocode service
        var geocoder = L.esri.Geocoding.geocodeService();

        // set filters collapsible
        collapsible();

        //********************* EVENT HANDLERS ******************//

        // select change of the dropdown list in 'My Community' filter
        $("#" + ECI.selectcssGeogID).find("li").on('click', function (e) {
            $("#" + ECI.selectcssGeogID).find("li").removeClass("active");
            $(this).addClass("active");
            var i = parseInt($(this).attr("data-id"));
            loadDataActiveGeog(ECI.geog[i].layer_name);

            // update button css class
            $(ECI.cssGeog).removeClass('active');
            $(ECI.cssGeog + ":eq(" + i + ")").addClass('active');
        });

        // attach top geography bar button click event handler
        $(ECI.cssGeog).on('click', function (e) {
            $(ECI.cssGeog).removeClass('active');
            $(this).addClass('active');

            // get current active geography
            var activeGeog = $(this).html();
            activeGeog = $.trim(activeGeog);

            loadDataActiveGeog(activeGeog);

            // update 'My Community' geog list item selection
            $("#" + ECI.selectcssGeogID).find("li").removeClass("active");
            $("#" + ECI.selectcssGeogID).find("li[data-id='" + ECI.igeog + "']").addClass("active");
        });

        // attach map click event handler
        map.on('click', function (e) {
            selectFeature(e.latlng);
        });

        // attach search result
        $("#address-search").on('click', searchLocation);

        // hit return on address input field
        $("#address-input").on('keyup', function (e) {
            if (e.keyCode == 13) {
                searchLocation();
            }
        });

        // attach geography list selection event handler
        $("#list-geography").on("change", function (e) {
            var geoid = $(this).val();
            if (geoid !== "" && $.inArray(geoid, ECI.geoid) === -1) {
                layerSelect.query()             // merge with queryFeature() function???
                    .layer(ECI.geog[ECI.igeog].select_ids[0])
                    .within(ECI.bounds)
                    .where("GEOID = '" + geoid + "'")
                    .run(function (error, featureCollection) {
                        setSelectionDef(featureCollection);
                    });
            }
        });

        // attach Grid/List view toggle
        $("#list-view").on("click", function (e) {
            $("#grid-view").removeClass("active");
            $("#engage-list .single-engagement").addClass("list-group-item");
            $("#impact-list .single-engagement").addClass("list-group-item");
            $(this).addClass("active");
        });

        $("#grid-view").on("click", function (e) {
            $("#list-view").removeClass("active");
            $("#engage-list .single-engagement")
                .removeClass("list-group-item")
                .addClass("grid-group-item");
            $(this).addClass("active");
            $("#impact-list .single-engagement")
                .removeClass("list-group-item")
                .addClass("grid-group-item");
        });

        //****************** LOCAL FUNCTIONS *******************//

        /**
         * Activate a geography selection
         * @param {string} activeGeog 
         */
        function loadDataActiveGeog(activeGeog) {
            // zoom out to state extent
            map.flyToBounds(ECI.bounds);

            // set map layer to the selection
            $.each(ECI.geog, function (i, v) {
                if (v.layer_name === activeGeog) {
                    // if it's different from the current geography
                    if (ECI.igeog !== i) {
                        // reset existing geography layer definition and selection bounds
                        if (ECI.selectionBounds) {
                            delete ECI.selectionBounds;
                        }
                        $("#impact-list").empty();

                        // now set up new geography layers
                        ECI.igeog = i;
                        ECI.geoid = [];

                        // change data layers to display
                        showDensityMap();

                        // show overall in filters and charts
                        getEngagements();

                        // remove all selections
                        layerSelect.setLayerDefs(resetSelection());
                        layerSelect.setLayers(v.select_ids);

                        // remove existing selection listing
                        populateGeographyList();
                        $(ECI.filterGeog).empty();
                    }
                    return false;
                }
            });
        }

        /**
         * Add map layers as a base layer and selection layer to the map
         */
        function addMapLayers() {

            // get all geography button texts
            $(ECI.cssGeog).each(function (i, v) {
                ECI.geog[i].layer_name = $.trim($(v).html());

                // populate geography pull-down list
                $("#" + ECI.selectcssGeogID).append(
                    //$("<option />", { text: ECI.geog[i].layer_name, value: i})
                    $("<li />", {"class": "list-group-item", "data-id": i}).append(ECI.geog[i].layer_name)
                );
            });
            //$("#" + ECI.selectcssGeogID).val(ECI.igeog);
            $("#" + ECI.selectcssGeogID).find("li[data-id='" + ECI.igeog + "']").addClass("active");

            // add ECI density map
            showDensityMap();

            // show statewide summary
            getEngagements();

            // add the boundary's selection layer
            var service = "https://gis3.cares.missouri.edu/arcgis/rest/services/Dynamic/Boundary2016_ECI/MapServer";
            layerSelect = L.esri.dynamicMapLayer({
                url: service,
                layers: ECI.geog[ECI.igeog].select_ids,
                layerDefs: resetSelection(),
                format: "png32",
                opacity: 1,
                position: 'front'
            }).addTo(map);

            // add a reference layer, only available 0-13 zoom levels. Move to shadowPane so it's on the top
            var refLayer = L.esri.tiledMapLayer({
                url: 'https://server.arcgisonline.com/arcgis/rest/services/Reference/World_Reference_Overlay/MapServer',
                maxZoom: 13
            }).addTo(map);
            map.getPanes().shadowPane.appendChild(refLayer.getContainer());

            // populate geography list
            populateGeographyList();
        }

        /**
         * Set all selection layers to no selection
         */
        function resetSelection() {
            var def = {};
            $.each(ECI.geog, function (i, v) {
                if (v.select_ids) {
                    for (var k = 0; k < v.select_ids.length; k++) {
                        def[v.select_ids[k]] = "GEOID IN ('')";
                    }
                }
            });
            return def;
        }

        /**
         * Populate geography list in 'MY COMMUNITY' filter
         */
        function populateGeographyList() {
            var name = ECI.geog[ECI.igeog].layer_name;
            var key = ECI.geog[ECI.igeog].geo_key;
            api("get", "api-location/v1/geoid-list/" + key, { state: "Missouri" }, function (data) {
                if (data) {
                    name = (key === "500") ? "Cong. District" : name;
                    $("#list-geography")
                        .empty()
                        .append($("<option />", { text: "- Select " + name + " -", value: "" }));

                    $.each(data, function (i, v) {
                        $("#list-geography").append(
                            $("<option />", { value: v.geoid, text: v.name })
                        );
                    });
                }
            });
        }

        /**
         * Select a feature on map
         * @param {any} latLng - The point location on map to select geography for.
         */
        function selectFeature(latLng) {
            // get census tract number
            layerSelect.identify()
                .at(latLng)
                .on(map)
                .layers("visible:" + ECI.geog[ECI.igeog].select_ids.join(","))
                .run(function (error, featureCollection) {
                    setSelectionDef(featureCollection);
                });
        }

        /**
         * Set the geography selection definition
         * @param {any} [featureCollection] - The list of features found at mouse click on the map
         */
        function setSelectionDef(featureCollection) {
            var activeGeog = ECI.geog[ECI.igeog];

            // get selected GEOID
            if (featureCollection && featureCollection.features.length > 0) {
                var inMissouri = false;
                var style = $("#list-view").hasClass("active") ? " list-group-item" : "";
                var stockImg = getPluginPath("images");
                var pdfPath = getPluginPath("pdf") + ECI.geog[ECI.igeog].layer_name.toLowerCase() + "/";

                $.each(featureCollection.features, function (idx, feature) {
                    var dataId = feature.properties["GEOID"] || feature.properties["GeoID"];

                    // check if in Missouri and we have not already selected it
                    if (/(^29|US29)/.test(dataId) && $.inArray(dataId, ECI.geoid) === -1) {
                        ECI.geoid.push(dataId);

                        // add to filter panel
                        var name = feature.properties["Name"] || feature.properties["NAMELSAD"];

                        // list UM impact card for the GEOID
                        var $item = addItem({
                            title: name,
                            link: pdfPath + name + ".pdf",
                            image: stockImg + "um_impact.png"
                        }, dataId, style, true);
                        $("#impact-list").append($item);

                        // include the count value in the geography list
                        if (ECI.count && ECI.count[dataId]) {
                            name += " (" + ECI.count[dataId] + ")";
                        }
                        var liGeog = $("<li />", { "data-id": dataId })
                            .append($("<i />", { "class": "fa fa-times-circle fa-2x" }))
                            .append($("<span />").append(name));
                        $(ECI.filterGeog).append(liGeog);

                        // filter icon 'delete' click - remove the geography
                        liGeog.find("i").on("click", function (e) {
                            var li = $(this).parent("li");
                            var id = li.attr("data-id");
                            li.remove();

                            for (var i = 0; i < ECI.geoid.length; i++) {
                                if (ECI.geoid[i] === id) {
                                    ECI.geoid.splice(i, 1);

                                    // we've removed a geography - now need to update the bounds
                                    if (ECI.geoid.length === 0) {
                                        delete ECI.selectionBounds;
                                        map.flyToBounds(ECI.bounds);
                                    } else {
                                        var layerId = ECI.geog[ECI.igeog].select_ids[1];
                                        queryFeatures(layerId, ECI.geoid, function (featureCollection) {
                                            var geojson = L.geoJSON(featureCollection);
                                            ECI.selectionBounds = geojson.getBounds();
                                            map.flyToBounds(ECI.selectionBounds);
                                        });
                                    }

                                    // remove UM Impact card
                                    if (ECI.igeog !== 1) {
                                        $("#impact-list").find(".single-engagement[data-id='" + id + "']").parent().remove();
                                    }

                                    // update the selection on the map
                                    setSelectionDef();
                                    break;
                                }
                            }

                            // if all selection are deleted, set pulldown to first option
                            if (ECI.geoid.length === 0) {
                                $("#list-geography").val('');
                            }
                        });

                        // found geography in Missouri
                        inMissouri = true;
                    }
                });

                if (!inMissouri) return;

                getGeoidTaxonomyKey();
            }

            // set layer definition
            var query = "GEOID IN ('" + ECI.geoid.join("','") + "')";
            var def = {};
            $.each(activeGeog.select_ids, function (i, v) {
                def[v] = query;
            });

            // if selection layer has been added to map, show selection
            if (layerSelect) {
                layerSelect.setLayerDefs(def);

                // expand bounds to include the selection, and zoom to new bounds
                if (featureCollection) {
                    var geojson = L.geoJSON(featureCollection);
                    var featureBounds = geojson.getBounds();
                    if (ECI.selectionBounds) {
                        ECI.selectionBounds.extend(featureBounds);
                    } else {
                        ECI.selectionBounds = featureBounds;
                    }

                    map.flyToBounds(ECI.selectionBounds);
                }
            }

            // update the theme/type/affiliation/engagement listings and chart
            getEngagements();

            return def;
        }

        /**
         * Query the boundary layer of selected GEOID to get a collection of features.
        * @param {string} layerId - The ID of the layer to query
        * @param {callbackRequest} callback - The function to execute after query has returned fetureCollection
         */
        function queryFeatures(layerId, idList, callback) {
            var queryOption = {
                url: "https://gis3.cares.missouri.edu/arcgis/rest/services/Dynamic/Boundary2016_ECI/MapServer",
                useCors: true
            };

            var q = (idList) ? "GEOID IN ('" + idList.join("','") + "')" : "";
            L.esri.query(queryOption)
                .layer(layerId)
                .within(ECI.bounds)
                .where(q)
                .run(function (error, featureCollection) {
                    callback = callback || $.noop;
                    callback(featureCollection);
                });
        }

        /**
         * Show statewide density map
         */
        function showDensityMap() {
            // get the selected geography type
            //var geoKey = $('input[name="map-geog"]:checked').val();

            if (layerECI) {
                layerECI.remove();
                legendECI.remove();
            }

            // get GeoJSON for the geography
            queryFeatures(ECI.geog[ECI.igeog].layer_ids[1], null, function (featureCollection) {
                if (featureCollection && featureCollection.features.length) {
                    // get ECI item counts for the features
                    api("get", "api-extension/v1/eci-counts/" + ECI.geog[ECI.igeog].geo_key, null, function (response) {
                        ECI.count = response;

                        // set up colors for map
                        if (!ECI.colors) {
                            // set color ramp ends
                            ECI.colors = {
                                "ramp": [[98, 8, 82], [209, 186, 208]], //[[107, 0, 0], [255,239,204]], //[[128, 0, 38], [255, 237, 160]],
                                "grades": []
                            };
                            getClassification();

                            // interpolate color ramp so we'll have a color for each grade
                            var numColors = ECI.colors.grades.length - 1;
                            var colorStart = ECI.colors.ramp[0];
                            var colorEnd = ECI.colors.ramp[1];

                            for (var i = 1; i < numColors; i++) {
                                var rgb = [];

                                // loop through RGB
                                for (var j = 0; j < colorStart.length; j++) {
                                    var c = colorStart[j] + i / numColors * (colorEnd[j] - colorStart[j]);
                                    rgb.push(Math.round(c));
                                }

                                ECI.colors.ramp.splice(i, 0, rgb);
                            }

                            // convert corlor from [r, g, b] to RGB(r, g, b)
                            ECI.colors.ramp = $.map(ECI.colors.ramp, function (v) {
                                return "RGB(" + v.join(",") + ")"
                            });
                        } else {
                            // update classification breaks
                            getClassification();
                        }

                        // add 'count' property to each feature in featureCollection
                        $.each(featureCollection.features, function (idx, feature) {
                            var geoId = feature.properties["GEOID"] || feature.properties["GeoID"];
                            feature.properties.count = response[geoId];
                        });

                        // sort featrues by counts so those with higher counts are added to map later and boundaries shaded darker
                        featureCollection.features.sort(function (a, b) {
                            if (a.properties.count < b.properties.count) return -1;
                            if (a.properties.count > b.properties.count) return 1;
                            return 0;
                        });

                        var shadeStyle = function (feature) {
                            var color = getColor(feature.properties.count);
                            return {
                                fillColor: color,
                                fillOpacity: 0.7,
                                weight: 1,
                                color: color,
                                opacity: 1
                            };
                        };

                        layerECI = L.geoJSON(featureCollection, {
                            style: shadeStyle
                        }).addTo(map);

                        // add a custom legend control
                        legendECI = L.control({ position: 'bottomleft' });
                        legendECI.onAdd = function (map) {
                            var div = L.DomUtil.create('div', 'info legend');

                            // loop through our density intervals and generate a label with a colored square for each interval
                            var grades = ECI.colors.grades;
                            for (var i = 0; i < grades.length; i++) {
                                // get color for this grade
                                div.innerHTML += '<i style="background:' + getColor(grades[i] + 1) + '"></i>';

                                var label = (grades[i] == grades[i + 1] - 1) ? grades[i + 1] + '</span><br>' :
                                    (grades[i] + 1) + (grades[i + 1] ? '&ndash;' + grades[i + 1] + '</span><br>' : '+</span>');

                                div.innerHTML += '<span>' + label;
                            }

                            return div;
                        };

                        legendECI.addTo(map);
                    });
                }
            });
        }

        /**
         * Classify the counts using 'Jenks' natural breaks
         */
        function getClassification() {
            var series = [];
            for (var g in ECI.count) {
                series.push(ECI.count[g]);
            }
            var brew = new classyBrew();
            brew.setSeries(series);
            brew.setNumClasses(4);
            var breaks = brew.classify();

            ECI.colors.grades = [0];
            for (var i = 0; i < breaks.length - 1; i++) {
                ECI.colors.grades.push(breaks[i]);
            }

            //ECI.colors.grades = brew.classify();
            //ECI.colors.grades.splice(0, 0, 0);      // add 0 as the first element
            //ECI.colors.grades.splice(ECI.colors.grades.length - 1, 1);  // remove the last one
        }

        /**
         * Get the color to shade a GeoJSON feature
         * @param {any} d - The value of property 'count'
         */
        function getColor(d) {
            var upper = ECI.colors.ramp.length - 1;
            for (var i = upper; i >= 0; i--) {
                if (d > ECI.colors.grades[i]) {
                    return ECI.colors.ramp[upper - i];
                }
            }

            return ECI.colors.ramp[upper];
        }

        /**
         * Highlight a selected geographic feature on map
         * @param {any} e - Map click event
         */
        function highlightFeature(e) {
            var layer = e.target;

            layer.setStyle({
                weight: 2,
                color: '#ff0000',
            });

            if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
                layer.bringToFront();
            }
        }

        /**
         * Zoom map to the extent of a clicked geographic feature
         * @param {any} e - Map click event
         */
        function zoomToFeature(e) {
            map.fitBounds(e.target.getBounds());
        }

        /**
         * Event handler for map click on a GeoJSON feature --- delete???
         * @param {any} feature
         * @param {any} layer
         */
        function onEachFeature(feature, layer) {
            if (feature.properties) {
                var content = feature.properties["NAMELSAD"] || feature.properties["Name"];
                var count = feature.properties["count"];
                content = "<b>" + content + "</b>: " + count;
                layer.bindPopup(content);
            }
        }

        /**
         * Generate an element ID from the theme name by removing non-alpha-numeric values
         * @param {string} t - Theme name 
         */
        function getThemeId(t) {
            return t.replace(/ /g, '_').toLowerCase();
        }

        /**
         * Get engagement data from server based on current geography selection
         */
        function getEngagements() {
            var hasGeog = (ECI.geoid.length > 0);

            // reset engagement posts selection
            ECI.posts = {
                "engagements": {},
				"viewed": [] //has been viewed in current modal context 
            };
            $.each(ECI.filters, function (i, ft) {
                ECI.posts[ft] = {};
            });
            showFilters(ECI.posts, hasGeog);
            $("#engage-loading").toggle(hasGeog);

            // get all engagements based on current geography selection
            if (hasGeog) {
                // get the GEOIDs of all summary levels that overlap with the selected GEOIDs
                api("get", "api-extension/v1/eci-geoid-list", { geoid: ECI.geoid.join(",") }, function (data) {
                    // always include Missouri and USA
                    var list = ["04000US29", "01000US"];
                    $.merge(list, data);

                    // now get engagement entries from WP REST API with these GEOIDs
                    retrievePosts(list, 1);
                });
            } else {
                if (!ECI.summary) {
                    // get summary info from WP REST API
                    ECI.summary = {};
                    retrieveSummary(1, "muext_program_category", ECI.filters[0]);
                } else {
                    showFilters(ECI.summary);
                }
            }

			setContainerState();
		}

		/**
		 * Retrieve summary data (total count) for each theme/type/affiliation
		 * @param {any} category - The category term used in custom post type
		 * @param {any} filter - The filter keyword: theme/type/affiliation
		 */
		function retrieveSummary(iPage, category, filter) {
			// now get engagement entries from WP REST API with these GEOIDs
            var perPage = 100;
            var param = { "parent": 0, "per_page": perPage, "page": iPage, "orderby": "id" };
            if (filter === ECI.filters[0]) param.exclude = 2;      // exclude 'Advancement'

            apiECI(category, param, function (response) {
				ECI.summary[filter] = ECI.summary[filter] || {};
				$.each(response, function (i, v) {
					if (v.count > 0) ECI.summary[filter][v.name] = v.count;
				});

				if (response.length < perPage) {
					// get summary data for next filter
					iPage = 1;
				    if (ECI.summary.type) {
					    if (ECI.summary.affiliation || skipAffiliation()) {
						    // got all summary counts. Now update the filters, unless the user has already selected a geography.
						    if ( !ECI.geoid || ECI.geoid.length === 0 ) {
                                showFilters(ECI.summary);
						    }
					    } else {
						    retrieveSummary(iPage, "muext_program_affiliation", ECI.filters[2]);
					    }
				    } else {
					    retrieveSummary(iPage, "muext_program_outreach_type", ECI.filters[1]);
				    }
				} else {
					// get next batch
					iPage++;
					retrieveSummary(iPage, category, filter);
				}
			});
		}

        /**
         * Retrieve posts from WP database
         * @param {any} list - The list of GEOIDs
         * @param {any} iPage - The index of pagination
         */
        function retrievePosts(list, iPage) {
            // now get engagement entries from WP REST API with these GEOIDs
            var postsPerPage = 100;
            apiECI("muext_engagement", { "filter[muext_geoid]": list.join(","), "filter[posts_per_page]": postsPerPage, "page": iPage }, function (response) {
                console.log('muext_engagement', response);

                // update ECI filters' post ID lists
                $.each(response, function (i, v) {
                    ECI.posts.engagements[v.id] = {
                        "title": v.title.rendered,
                        "link": v.link,
                        "image": v.eng_featured_image
                    };

                    // if populating ID list for 'theme', we need to check  if this geoid is one of selected geographies. 
                    // If yes, place it on the top of list, otherwise, add to the end
                    var isTop = false;
                    $.each(v.muext_geoid, function (j, w) {
                        // check if the geoid is an item in the ECI.geoid array
                        if (ECI.geoTID[w] && $.inArray(ECI.geoTID[w], ECI.geoid) !== -1) {
                            isTop = true;
                            return false;
                        }
                    });

                    getFilterData(v.eng_theme, ECI.filters[0], v.id, isTop);
                    getFilterData(v.eng_type, ECI.filters[1], v.id);

                    if (!skipAffiliation()) {
                        getFilterData(v.eng_affiliation, ECI.filters[2], v.id);
                    }
                });

                if (response.length < postsPerPage) {
                    console.log(ECI.posts);

                    // move post IDs of the selected geographies to front in ECI.posts.theme 

                    showFilters(ECI.posts);
                } else {
                   iPage++;
                    retrievePosts(list, iPage);
                }
            });
        }


        /**
         * Populate filter properties in ECI.posts - theme, type, affiliation 
         * @param {any} postProperty - The property of the engagement post 
         * @param {any} filterType - filter type: theme, type, affiliation
         * @param {any} id - The ID of the engagement post
         */
        function getFilterData(postProperty, filterType, id, isTop) {
            var topLevel = postProperty["top-level"];

            if (topLevel && topLevel.raw) {
                ECI.posts.engagements[id][filterType] = [];

                // loop through each filter name
                for (var t = 0; t < topLevel.raw.length; t++) {
                    var name = topLevel.raw[t].name;

                    // add filter name to appropriate array
                    ECI.posts.engagements[id][filterType].push(name);

                    // store post ID in ECI.posts
                    ECI.posts[filterType][name] = ECI.posts[filterType][name] || [];

                    // if post's geoid is one of selected geographies, place them in the front, otherwise, add to the end
                    if (isTop) {
                        ECI.posts[filterType][name].splice(0, 0, id);
                    } else {
                        ECI.posts[filterType][name].push(id);
                    }
                }
            }
        }

        /**
         * TODO: are we using this? 
		 * Retrieve one post (post data and post meta data) from WP database
         * @param {any} list - The list of GEOIDs
         * @param {any} iPage - The index of pagination
         */

        function retrievePostContentMeta( post_id ) {
            // now get engagement entries from WP REST API with these GEOIDs
            var url = "https://engagements.missouri.edu/wp-json/wp/v2/engagement-contentmeta/" + post_id;
            var postsPerPage = 1;
            api(
				"get", 
				url,
				function (response) {
					console.log(response);

                }
            );
        }

        /**
         * Retrieve one post (post data and post meta data) from WP database, load into modal
         * @param {any} list - The list of GEOIDs
         * @param {any} iPage - The index of pagination
         */

        function loadSingleEngTemplate( post_id, firstView, this_theme) {

            if ( firstView && firstView == true ){
				// clear out eci.posts.viewed
				ECI.posts.viewed = [];
			}
			// add post_id to eci.posts.viewed
			ECI.posts.viewed.push( post_id );

			// if theme, we're coming from modal already
			if( this_theme ){
				// what is the 

			} else {
				// get eng object with this post_id (for appending)
				var eng_item = $(".single-engagement[data-id='" + post_id + "']");
				var this_theme = eng_item.attr("data-theme");
				console.log( eng_item );
			}

			var single_template_return = $.ajax({
				url: eci_ajax.ajax_url, 
				data: {
					action: 'ajax_single_card',
					post_id : post_id
				}, 
				method: "POST",
				dataType: "html",
				beforeSend: function() {
					// activate spinny
					//$("#status-spinny").removeClass("hidden");
				},
				success: function( html ) {
				}
			}).done(function( data ) {
				console.log( this_theme );
				// loading template into modal window

				// trim whitespace
				data = $.trim( data );
				
				// populate the modal
				var title = $(data)[0];
				var innards = $(data)[2];
				$('#single-engagement-modal .modal-title').html( title );
				$('#single-engagement-modal .modal-theme').html( "Impact Area: " + this_theme );
				$('#single-engagement-modal .modal-main').html( innards );

				// click dis- and re-enable the next/previous button
				var next_id = nextModalPostID( post_id, this_theme );
				var next_button = $("#single-engagement-modal .next-btn");
				if( next_id == 0 ){
					next_button.addClass("hidden");
				} else {
					next_button.removeClass("hidden");
					next_button.off("click");
					next_button.on("click", function( e ){
						loadSingleEngTemplate( next_id, true, this_theme );
					});
				}
				
				var prev_id = prevModalPostID( post_id, this_theme );
				var prev_button = $("#single-engagement-modal .prev-btn");
				if( prev_id == 0 ){
					prev_button.addClass("hidden");
				} else {
					prev_button.removeClass("hidden");
					prev_button.off("click");
					prev_button.on("click", function( e ){
						loadSingleEngTemplate( prev_id, true, this_theme );
					});
				}

				// show that ish
				$('#single-engagement-modal').modal('show');

			});

        }

		/**
		 * utility function to iterate to next post_id that hasn't been viewed in modal
		 *
		 * @param {int} currentPostID - current post being viewed
		 * @param {str} currentPostTheme - current post's theme being viewed
		 * @param {array} eciPostList - full list of eci posts
		 * @param {array} eciViewedList - list of eci posts view in current modal session
		 * @return {int} postID
		 **/
        function nextModalPostID(currentPostID, currentPostTheme) {
			// get current index w/in this ECI.posts.theme
            var current_index = ECI.posts.theme[currentPostTheme].indexOf( parseInt( currentPostID ) );

			// if we're not at the end of this theme in ECI.posts, stay w/in theme
            if ((current_index + 1) < ECI.posts.theme[currentPostTheme].length ){
                var next_id = ECI.posts.theme[currentPostTheme][current_index + 1]; //index++

				// if we've already viewed this post_id, go to next post_id
				if( ECI.posts.viewed.indexOf( next_id ) != -1 ){
					next_id = nextModalPostID( next_id, currentPostTheme );
				}

			} else { // we are at the end of this theme, go to next theme
                nextPostTheme = getNextKey(ECI.posts.theme, currentPostTheme);

				if( nextPostTheme ){
					//do we have any posts in this theme?
                    if (ECI.posts.theme[currentPostTheme].length == 0 ){
                        nextPostTheme = getNextKey(ECI.posts.theme, currentPostTheme);
					} else {
						//start from the beginning
                        current_id = ECI.posts.theme[nextPostTheme][0];

						// if we've already viewed this post_id, go to next post_id
						if( ECI.posts.viewed.indexOf( current_id ) != -1 ){
							next_id = nextModalPostID( current_id, nextPostTheme );
						} else {
							return current_id;
						}
					}
				} else {				
					var next_id = 0; // we are out of themes
				}
			}
			
			return next_id;

		}
		/**
		 * utility function to iterate to prev post_id 
		 *
		 * @param {int} currentPostID - current post being viewed
		 * @param {str} currentPostTheme - current post's theme being viewed
		 * @param {array} eciPostList - full list of eci posts
		 * @param {array} eciViewedList - list of eci posts view in current modal session
		 * @return {int} postID
		 **/
        function prevModalPostID(currentPostID, currentPostTheme) {
			// get current index w/in this ECI.posts.theme
            var current_index = ECI.posts.theme[currentPostTheme].indexOf( parseInt( currentPostID ) );

			// if we're not at the beginning of this theme in ECI.posts, stay w/in theme
			if( !( ( current_index - 1 ) < 0 ) ){
                var prev_id = ECI.posts.theme[currentPostTheme][current_index - 1]; //index++


			} else { // we are at the end of this theme, go to next theme
                prevPostTheme = getPreviousKey(ECI.posts.theme, currentPostTheme);

				if( prevPostTheme ){
					//do we have any posts in this theme?
                    if (ECI.posts.theme[currentPostTheme].length == 0 ){
                        prevPostTheme = getPreviousKey(ECI.posts.theme, currentPostTheme);
					} else {
						//start from the end
                        current_id = ECI.posts.theme[prevPostTheme].length;

						// if we've already viewed this post_id, go to next post_id
						//if( ECI.posts.viewed.indexOf( current_id ) != -1 ){
							//prev_id = nextModalPostID( current_id, prevPostTheme );
						//} else {
							return current_id;
						//}
					}
				} else {				
					var prev_id = 0; // we are out of themes
				}
			}
			
			return prev_id;

		}

		//Utility: NEXT KEY
		function getNextKey(o, id){
			var keys = Object.keys( o ),
				idIndex = keys.indexOf( id ),
				nextIndex = idIndex += 1;
			if(nextIndex >= keys.length){
				//we're at the end, there is no next
				return false;
			}
			var nextKey = keys[ nextIndex ]
			return nextKey;
		}
	 
		//Utility: PREVIOUS KEY
		function getPreviousKey(o, id){
			var keys = Object.keys( o ),
				idIndex = keys.indexOf( id ),
				nextIndex = idIndex -= 1;
			if(idIndex === 0){
			//we're at the beginning, there is no previous
				return false;
			}
			var nextKey = keys[ nextIndex ]
			return nextKey;
		}

        /**
         * Get GEOID taxonomy ID so we can give order priority to posts of currently selected GEOIDs
         */
        function getGeoidTaxonomyKey() {
            ECI.geoTID = ECI.geoTID || {};
            ECI.geoTID.viewed = ECI.geoTID.viewed || [];

            // only get the geoids that we don't have taxonomy keys yet
            var gList = [];
            $.each(ECI.geoid, function (i, v) {
                if ($.inArray(v, ECI.geoTID.viewed) === -1) {
                    gList.push(v);
                    ECI.geoTID.viewed.push(v);
                }
            });

            if (gList.length > 0) {
                apiECI("muext_geoid", { "slug": gList.join(","), "per_page": 100 }, function (response) {
                    if (response && response.length > 0) {
                        $.each(response, function (i, v) {
                            ECI.geoTID[v.id] = v.name;
                        });
                    }
                });
            }
        }

        /**
         * List all filters for theme, type, and affiliation
         * @param {any} data
         * @param {any} showLoading
         */
        function showFilters(data, showLoading) {
            // update theme, type, affiliation filters
            var filterCount = {};           // store counts for chart
            var hasPosts = (data.engagements);

            var iconStyle = (hasPosts) ? "fa-square-o" : "fa-circle";

            $.each(ECI.filters, function (i, v) {
                if (i === 2 && skipAffiliation()) {
                    return false;
                }

                if (data[v]) {
                    filterCount[v] = {};

                    var $filter = $("#filter-" + v);
                    $filter.empty();

                    // sort the filters by name
                    var keys = [];
                    for (var t in data[v]) {
                        keys.push(t);
                    }
                    if (hasPosts) keys.sort();

                    var hasProp = false;
                    var sorted = {};
                    var isFilterLinked = (i === 0 && hasPosts);  // filters are hyperlinked only when it's 'theme' and posts are shown

                    for (var k = 0; k < keys.length; k++) {
                        var key = keys[k];
                        if (data[v].hasOwnProperty(key)) {
                            var count = $.isArray(data[v][key]) ? data[v][key].length : data[v][key];
                            filterCount[v][key] = count;

                            var $filterText = (isFilterLinked) ? $("<a />", { "href": "javascript:void(0)" }) : $("<span />");

                            $filter.append(
                                $("<li />", { "data-id": key })
                                    .append($("<i />", { "class": "fa fa-2x " + iconStyle }))
                                    .append(
                                    $filterText.append(key + " (" + count + ")")
                                    )
                            );
                            hasProp = true;
                            sorted[key] = data[v][key];
                        }
                    }

                    // keep filters sorted by name
                    data[v] = sorted;

                    // show a loading icon
                    if (showLoading && !hasProp) {
                        $filter.append(
                            $("<li />")
                                .append($("<i />", { "class": "fa fa-spinner fa-spin fa-2x" }))
                                .append(
                                $("<span />").append(" Loading...")
                                )
                        );
                    }

                    // attach filter click event handler
                    if (hasPosts) {
                        $filter.find("i").on("click", function () {
                            // toggle icon
                            $(this).toggleClass("fa-square-o").toggleClass("fa-check-square");

                            var selectCss = "filter-active";
                            var $li = $(this).parent();
                            $li.toggleClass(selectCss);

                            // get all filters
                            var filterList = {};
                            $.each(ECI.filters, function (i, v) {
                                filterList[v] = [];

                                if (i === 2 && skipAffiliation()) {
                                    return false;
                                }

                                var $filter = $("#filter-" + v);
                                $filter.find("li." + selectCss).each(function (el) {
                                    var fValue = $(this).attr("data-id");
                                    filterList[v].push(fValue);
                                });
                            });
                            updateFilter(filterList);

                            var themeCount = {};
                            for (var f in ECI.posts.theme) {
                                themeCount[f] = ECI.posts.theme[f].length;
                            }
                            updateContent(ECI.posts, themeCount);

                            // update filter counts
                            $.each(ECI.filters, function (iType, fType) {
                                var $filter = $("#filter-" + fType);
                                var tag = (iType === 0) ? "a" : "span";
                                var countAll = ($filter.find("." + selectCss).length === 0);

                                $filter.find("li").each(function (el) {
                                    var $text = $(this).find(tag);
                                    var fValue = $(this).attr("data-id");
                                    var num = ECI.posts[fType][fValue] ? ECI.posts[fType][fValue].length : 0;
                                    if (countAll || $(this).hasClass(selectCss)) fValue += " (" + num + ")";
                                    $text.html(fValue);
                                });
                            });
                        });
                    }
                }
            });

            // now update chart and contents
            updateContent(data, filterCount.theme);

            // attach theme 'link' click
            $("#filter-theme").find("a").on("click", function (e) {
                var id = $(this).parent().attr("data-id");
                var target = $("#" + getThemeId(id));
                if (target.length) {
                    $('html,body').animate({
                        scrollTop: target.offset().top - 155
                    }, 1000);
                    return false;
                }
            });
        }


        /**
         * Update theme/type/affiliation filters, chart and engagement listing content
         * @param {any} data - The engagement data
         */
        function updateContent(data, themeCount) {
            setContainerState();

            $("#engage-loading").hide();

            // update chart

            // add to chart subtitle
            var $subtitle = $("#engage-geog");
            switch (ECI.geoid.length) {
                case 0:
                    $subtitle.html("State of Missouri");
                    break;
                case 1: case 2: case 3:
                    var names = [];
                    $("#filter-geography").find("li").each(function (i) {
                        names.push($(this).find("span").html().replace(/ *\([^)]*\) */, ''));
                    });
                    $subtitle.html(names.join(", "));
                    break;
                default:
                    $subtitle.html((ECI.igeog === 0) ?
                        "Selected Counties" :
                        "Selected " + ECI.geog[ECI.igeog].layer_name.split(" ")[0] + " Districts");
                    break;
            }

            var chartData = [];
            for (var x in themeCount) {
                if (themeCount[x] > 0) {
                    chartData.push({
                        name: x,
                        y: themeCount[x]
                    });
                }
            }
            Highcharts.chart('overview-chart', {
                chart: {
                    type: 'column',
                    style: {
                        fontFamily: 'inherit'
                    }
                },
                credits: {
                    enabled: false
                },
                title: null,
                xAxis: {
                    type: 'category',
                    labels: {
                        style: {
                            fontSize: '1.25em'
                        }
                    }
                },
                yAxis: {
                    title: {
                        text: 'Number of Engagements',
                        style: {
                            fontSize: '1.25em'
                        }
                    }
                },
                legend: {
                    enabled: false
                },
                tooltip: {
                    headerFormat: '',
                    pointFormat: '<span style="color:{point.color}">{point.name}</span>: <b>{point.y}</b>'
                },
                plotOptions: {
                    series: {
                        borderWidth: 0,
                        dataLabels: {
                            enabled: true,
                            format: '{point.y}'
                        }
                    }
                },
                series: [{
                    colorByPoint: true,
                    data: chartData
                }]
            });

            // update engagement listing
            if (data.engagements) {
                $("#engage-list").empty();

                // loop through each theme
                for (var t in data.theme) {
                    if (data.theme[t].length > 0) {
                        var tId = getThemeId(t);

                        // add theme container, and container for the items
                        $("#engage-list").append(
                            $("<div />", { "id": tId + "_container", "class": "row" })
                                .append($("<h4 />", { "id": tId, "class": "col-xs-12 modal-theme" }).append(t))
                        );

                        // show the first group of engagements
                        showEngGroup(0, t);
                    }
                }
            }
        }

        /**
         * 
         * @param {any} arrTheme
         * @param {any} arrType
         * @param {any} arrAffiliation
         */
        function updateFilter(filterList) {
            // convert ECI.posts.engagements to an array
            var temp = [];
            for (var id in ECI.posts.engagements) {
                var obj = { "id": id };
                $.each(ECI.filters, function (i, v) {
                    obj[v] = ECI.posts.engagements[id][v];
                });
                temp.push(obj);
            }

            // apply filters
            temp = temp.filter(function (obj) {
                for (var x in filterList) {
                    // if the post's themes/types/affiliations are not matching any selected themes/types/affiliations
                    if (filterList[x].length > 0 && !hasCommonItems(filterList[x], obj[x])) {
                        return false;
                    }
                }

                return true;
            });

            // assemble ID lists again
            $.each(ECI.filters, function (i, v) {
                ECI.posts[v] = {};

                // loop through each post item
                $.each(temp, function (j, item) {
                    var postId = parseInt(item.id);

                    // loop through the filter list of the item
                    $.each(item[v], function (k, z) {

                        // z - a theme/type/affiliation name
                        ECI.posts[v][z] = ECI.posts[v][z] || [];
                        if ($.inArray(item.id, ECI.posts[v][z]) === -1) {
                            ECI.posts[v][z].push(postId);
                        }
                    });
                });
            });

            // if we have theme filters, need to remove IDs from not-selected themes
            var t = ECI.filters[0];
            if (filterList[t].length > 0) {
                for (var f in ECI.posts[t]) {
                    if ($.inArray(f, filterList[t]) === -1) {
                        ECI.posts[t][f] = [];
                    }
                }
            }
        }


        /**
         * Find common elements in two arrays
         * @param {any} a
         * @param {any} b
         */
        function hasCommonItems(a, b) {
            var newArr = [];
            newArr = a.filter(function (v) {
                return b.indexOf(v) >= 0;
            }).concat(b.filter(function (v) {
                return a.indexOf(v) >= 0;
            }));

            return newArr.length > 0;
        }

        /**
         * Show a group of engagement posts
         * @param {any} startIndex - The starting index of engagement posts to show
         * @param {any} theme - The theme to show the posts
         */
        function showEngGroup(startIndex, theme) {
            var stopIndex = startIndex + 6;
            var themeId = getThemeId(theme);
            var $container = $("#" + themeId  + "_container");
            var style = $("#list-view").hasClass("active") ? " list-group-item" : "";

            // show up to 6 posts
            var postIDs = ECI.posts.theme[theme];
            var posts = ECI.posts.engagements;
            var stockImg = getPluginPath("images");
            for (var p = startIndex; p < Math.min(postIDs.length, stopIndex); p++) {
                var pId = postIDs[p];

                if (ECI.posts.engagements.hasOwnProperty(pId)) {
					var item = ECI.posts.engagements[pId];
					item = {
						title: item.title,
						link: item.link,
						image: item.image || stockImg + themeId + ".jpg"
					};
					var $item = addItem(item, pId, style, false);

					$item.on("click", function( e ){
						var post_id = $(this).attr("data-id");
						//retrievePostContentMeta( post_id );
						loadSingleEngTemplate( post_id, true, theme );
					});

                    $container.append($item);
                }
            }

            // if we have more posts, add a 'show more' icon
            if (postIDs.length > stopIndex) {
                var $more = $("<h2 />", { "class": "text-center show-more", "data-id": stopIndex, "data-theme": theme})
                    .append(
                    $("<i />", { "class": "fa fa-chevron-circle-down icon fa-2x", "title": "Show more" })
                );

                // when 'more' icon is clicked, show next batch of posts
                $more.on("click", function (e) {
                    // get new index in integer to ensure load next 6 only, otherwise all remining posts are loaded.
                    var newIndex = parseInt($(this).attr("data-id"));
                    var theme = $(this).attr("data-theme");
                    $(this).remove();
                    showEngGroup(newIndex, theme);
                });
                $container.append($more);
            }
        }
        /**
         * Add an item (a post or datasheet) to output
         * @param {any} item - The item property: link, image, title
         * @param {any} style - The display style
         */
        function addItem(item, dataId, style, linked) {
            var $item = $("<div />", {
                "class": "single-engagement col-xs-12 col-lg-4 col-md-6 col-sm-6" + style,
                "data-id": dataId
            })
                .append(
                $("<div />", { "class": "engagement-item" })
                    .append(
                    $("<div />", { "style": "background-image: url(" + item.image + ")", "class": "img-container" })
                    )
                    .append(
                    $("<div />", { "class": "row" }).append($("<div />", { "class": "text-below col-xs-12" }).append(
                        $("<h3 />").append(item.title)
                    ))
                    )
                );

            if (linked) $item = $("<a />", { "href": item.link, "target": "_blank" }).append($item);

            return $item;
        }

        function getPluginPath(dir) {
            return $("#plugin-file-path").val() + dir + "/";
        }

        /**
         * Set display state of filter, engagement, chart containers.
         */
        function setContainerState() {
            var hasGeog = (ECI.geoid.length > 0);
            var hasPosts = hasGeog && !$.isEmptyObject(ECI.posts.theme);
            $("#style-container").toggle(hasPosts);
            $("#engage-list").toggle(hasPosts);
            $("#impact-container").toggle(hasGeog && ECI.igeog !== 1);

            var hasChart = !hasGeog || hasPosts;
            $("#chart-container").toggle(hasChart);
            $("#engage-container").toggle(hasChart);
        }

        /**
         * Check if we need to skip affiliation filters
         */
        function skipAffiliation() {
            return ($("#filter-" + ECI.filters[2]).length === 0);
        }

        /**
         * Initialize all collapsible fieldsets.
         */
        function collapsible() {
            var settings = {
                collapsed: false,
                animation: true,
                speed: "medium"
            };

            $("fieldset.collapsible").each(function () {
                var $fieldset = $(this);
                var $legend = $fieldset.children("legend");
                var isCollapsed = $fieldset.hasClass("collapsed");

                $legend.click(function () {
                    collapse($fieldset, settings, !isCollapsed);
                    isCollapsed = !isCollapsed;

                    // update icon in legend
                    $(this).find("i")
                        .toggleClass("fa-chevron-down", isCollapsed)
                        .toggleClass("fa-chevron-up", !isCollapsed);
                });

                // Perform initial collapse. Don't use animation to close for initial collapse.
                if (isCollapsed) {
                    collapse($fieldset, { animation: false }, isCollapsed);
                } else {
                    collapse($fieldset, settings, isCollapsed);

                }
            });
        };

        /**
         * Collapse/uncollapse the specified fieldset.
         * @param {object} $fieldset
         * @param {object} options
         * @param {boolean} collapse
         */
        function collapse($fieldset, options, doCollapse) {
            $container = $fieldset.find("div");
            if (doCollapse) {
                if (options.animation) {
                    $container.slideUp(options.speed);
                } else {
                    $container.hide();
                }
                $fieldset.removeClass("expanded").addClass("collapsed");
            } else {
                if (options.animation) {
                    $container.slideDown(options.speed);
                } else {
                    $container.show();
                }
                $fieldset.removeClass("collapsed").addClass("expanded");
            }

        };

        /**
         * Search location with geocoding service
         */
        function searchLocation() {
            var address = $("#address-input").val();
            if (address !== "") {
                // if state is not specified, we add state
                if (!/(mo$|mo |missouri$|missouri )/i.test(address)) {
                    address += ", mo";
                }

                geocoder.geocode().text(address).run(function (error, response) {
                    if (response && response.results) {
                        selectFeature(response.results[0].latlng);
                    }
                });
            }
        }
    }
});

/**
* Generate Jenks Natural Breaks, modified from 'classybrew' github repository
* https://github.com/tannerjt/classybrew
*/
(function () {

    var classyBrew = (function () {

        return function () {
            this.series = undefined;
            this.numClasses = null;
            this.breaks = undefined;
            this.range = undefined;
            this.statMethod = undefined;
           
            // define array of values
            this.setSeries = function (seriesArr) {
                this.series = Array();
                this.series = seriesArr;
                this.series = this.series.sort(function (a, b) { return a - b });
            };

            // set number of classes
            this.setNumClasses = function (n) {
                this.numClasses = n;
            };

            /**** Classification Methods ****/
            this._classifyJenks = function () {
                var mat1 = [];
                for (var x = 0, xl = this.series.length + 1; x < xl; x++) {
                    var temp = []
                    for (var j = 0, jl = this.numClasses + 1; j < jl; j++) {
                        temp.push(0)
                    }
                    mat1.push(temp)
                }

                var mat2 = []
                for (var i = 0, il = this.series.length + 1; i < il; i++) {
                    var temp2 = []
                    for (var c = 0, cl = this.numClasses + 1; c < cl; c++) {
                        temp2.push(0)
                    }
                    mat2.push(temp2)
                }

                for (var y = 1, yl = this.numClasses + 1; y < yl; y++) {
                    mat1[0][y] = 1
                    mat2[0][y] = 0
                    for (var t = 1, tl = this.series.length + 1; t < tl; t++) {
                        mat2[t][y] = Infinity
                    }
                    var v = 0.0
                }

                for (var l = 2, ll = this.series.length + 1; l < ll; l++) {
                    var s1 = 0.0
                    var s2 = 0.0
                    var w = 0.0
                    for (var m = 1, ml = l + 1; m < ml; m++) {
                        var i3 = l - m + 1
                        var val = parseFloat(this.series[i3 - 1])
                        s2 += val * val
                        s1 += val
                        w += 1
                        v = s2 - (s1 * s1) / w
                        var i4 = i3 - 1
                        if (i4 != 0) {
                            for (var p = 2, pl = this.numClasses + 1; p < pl; p++) {
                                if (mat2[l][p] >= (v + mat2[i4][p - 1])) {
                                    mat1[l][p] = i3
                                    mat2[l][p] = v + mat2[i4][p - 1]
                                }
                            }
                        }
                    }
                    mat1[l][1] = 1
                    mat2[l][1] = v
                }

                var k = this.series.length
                var kclass = []

                for (i = 0, il = this.numClasses + 1; i < il; i++) {
                    kclass.push(0)
                }

                kclass[this.numClasses] = parseFloat(this.series[this.series.length - 1])

                kclass[0] = parseFloat(this.series[0])
                var countNum = this.numClasses
                while (countNum >= 2) {
                    var id = parseInt((mat1[k][countNum]) - 2)
                    kclass[countNum - 1] = this.series[id]
                    k = parseInt((mat1[k][countNum] - 1))

                    countNum -= 1
                }

                if (kclass[0] == kclass[1]) {
                    kclass[0] = 0
                }

                this.range = kclass;
                this.range.sort(function (a, b) { return a - b })

                return this.range; //array of breaks
            };

            /**** End classification methods ****/

            // return array of natural breaks
            this.classify = function (method, classes) {
                this.statMethod = (method !== undefined) ? method : this.statMethod;
                this.numClasses = (classes !== undefined) ? classes : this.numClasses;
                var breaks = this._classifyJenks();
                this.breaks = breaks;
                return breaks;
            };

            this.getBreaks = function () {
                // always re-classify to account for new data
                return this.breaks ? this.breaks : this.classify();
            };

            /*** Simple Math Functions ***/
            this._mean = function (arr) {
                return parseFloat(this._sum(arr) / arr.length);
            };

            this._sum = function (arr) {
                var sum = 0;
                var i;
                for (i = 0; i < arr.length; i++) {
                    sum += arr[i];
                }
                return sum;
            };

            this._variance = function (arr) {
                var tmp = 0;
                for (var i = 0; i < arr.length; i++) {
                    tmp += Math.pow((arr[i] - this._mean(arr)), 2);
                }

                return (tmp / arr.length);
            };

            this._stdDev = function (arr) {
                return Math.sqrt(this._variance(arr));
            };

            /*** END Simple math Functions ***/
        }


    })();

    // support node module and browser
    if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
        module.exports = classyBrew;
    } else {
        window.classyBrew = classyBrew;
    }

})();