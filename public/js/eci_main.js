
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
                var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
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
        $("#" + ECI.selectcssGeogID).on('change', function (e) {
            var i = parseInt($("#" + ECI.selectcssGeogID).val());
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
        });

        // attach map click event handler
        map.on('click', function (e) {
            selectFeature(e.latlng);
        });

        // when map container is resized via a css transition, we need to resize map
        $("#" + ECI.map + "-container").on("mresize", function (e) {
            map.invalidateSize();
            var chart = $("#overview-chart").highcharts();
            if (chart) chart.reflow();
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
                console.log(geoid)
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

                        $("#filter-help").show();
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
                    $("<option />", { text: ECI.geog[i].layer_name, value: i})
                );
            });
            $("#" + ECI.selectcssGeogID).val(ECI.igeog);

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
		 * Zoom to geographic selection, but wait for 500 ms if the map is being resized.
		 * ECI.resizing is set to true in selectFeature()
		 */
        function zoomToSelection() {
            console.log('ECI.resizing', ECI.resizing);
            if (ECI.resizing) {
                setTimeout(zoomToSelection, 500);
                delete ECI.resizing;
            } else {
                map.flyToBounds(ECI.selectionBounds);
            }
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
            // show filter pane if hidden
            if (!$(".open-leftmenu").length) {
                ECI.resizing = true;
                $("#content").addClass("open-leftmenu");
                $("#filters-container-left").show();
                $("#filter-theme").show();
                $("#logo").find(".hamburger").addClass("highlight");
            }

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
                $("#filter-help").hide();

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

                    zoomToSelection();
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

            //ECI.colors.grades = [0];
            //for (var i = 1; i < breaks.length - 1; i++) {
            //    ECI.colors.grades.push(breaks[i]);
            //}

            ECI.colors.grades = brew.classify();
            ECI.colors.grades.splice(0, 0, 0);      // add 0 as the first element
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
                "theme": {},
                "type": {},
                "affiliation": {},
                "engagements": {},
				"viewed": [] //has been viewed in current modal context 
            };
            if (ECI.filterPosts) delete ECI.filterPosts;
            updateContent(ECI.posts, hasGeog);

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
                    retrieveSummary(1, "muext_program_category", "theme");
                } else {
                    updateContent(ECI.summary);
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
			var url = "https://engagements.missouri.edu/wp-json/wp/v2/" + category;
            var perPage = 100;
            var param = { "parent": 0, "per_page": perPage, "page": iPage, "orderby": "id" };
            if (filter === "theme") param.exclude = 2;      // exclude 'Advancement'

            api("get", url, param, function (response) {
				ECI.summary[filter] = ECI.summary[filter] || {};
				$.each(response, function (i, v) {
					if (v.count > 0) ECI.summary[filter][v.name] = v.count;
				});

				if (response.length < perPage) {
					// get summary data for next filter
					iPage = 1;
				    if (ECI.summary.type) {
					    if (ECI.summary.affiliation) {
						    // got all summary counts. Now update the filters, unless the user has already selected a geography.
						    if ( !ECI.geoid || ECI.geoid.length === 0 ) {
							    updateContent(ECI.summary);
						    }
					    } else {
						    retrieveSummary(iPage, "muext_program_affiliation", "affiliation");
					    }
				    } else {
					    retrieveSummary(iPage, "muext_program_outreach_type", "type");
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
            var url = "https://engagements.missouri.edu/wp-json/wp/v2/muext_engagement";
            var postsPerPage = 100;
            api("get", url, { "filter[muext_geoid]": list.join(","), "filter[posts_per_page]": postsPerPage, "page": iPage }, function (response) {
                console.log(response);

                // update ECI
                $.each(response, function (i, v) {
                    ECI.posts.engagements[v.id] = {
                        "title": v.title.rendered,
                        "link": v.link,
                        "image": v.eng_featured_image
                    };

                    updateECIFilters(v.eng_theme["top-level"], "theme", v.id);
                    updateECIFilters(v.eng_type["top-level"], "type", v.id);
                    updateECIFilters(v.eng_affiliation["top-level"], "affiliation", v.id);
                });

                if (response.length < postsPerPage) {
                    console.log(ECI.posts);
                     updateContent(ECI.posts);
                } else {
                   iPage++;
                    retrievePosts(list, iPage);
                }
            });
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
            var posts = ECI.filterPosts || ECI.posts;

			// get current index w/in this ECI.posts.theme
			var current_index = posts.theme[currentPostTheme].indexOf( parseInt( currentPostID ) );

			// if we're not at the end of this theme in ECI.posts, stay w/in theme
			if( ( current_index + 1 ) < posts.theme[currentPostTheme].length ){
				var next_id = posts.theme[currentPostTheme][current_index + 1]; //index++

				// if we've already viewed this post_id, go to next post_id
				if( ECI.posts.viewed.indexOf( next_id ) != -1 ){
					next_id = nextModalPostID( next_id, currentPostTheme );
				}

			} else { // we are at the end of this theme, go to next theme
				nextPostTheme = getNextKey( posts.theme, currentPostTheme);

				if( nextPostTheme ){
					//do we have any posts in this theme?
					if( posts.theme[currentPostTheme].length == 0 ){
						nextPostTheme = getNextKey( posts.theme, currentPostTheme);
					} else {
						//start from the beginning
						current_id = posts.theme[nextPostTheme][0];

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
            var posts = ECI.filterPosts || ECI.posts;

			// get current index w/in this ECI.posts.theme
			var current_index = posts.theme[currentPostTheme].indexOf( parseInt( currentPostID ) );

			// if we're not at the beginning of this theme in ECI.posts, stay w/in theme
			if( !( ( current_index - 1 ) < 0 ) ){
				var prev_id = posts.theme[currentPostTheme][current_index - 1]; //index++


			} else { // we are at the end of this theme, go to next theme
				prevPostTheme = getPreviousKey( posts.theme, currentPostTheme);

				if( prevPostTheme ){
					//do we have any posts in this theme?
					if( posts.theme[currentPostTheme].length == 0 ){
						prevPostTheme = getPreviousKey( posts.theme, currentPostTheme);
					} else {
						//start from the end
						current_id = posts.theme[prevPostTheme].length;

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
		};
	 
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
		};







        /**
         * Populate filter properties in ECI.posts - theme, type, affiliation 
         * @param {any} postProperty - The property of the engagement post 
         * @param {any} filterType - filter type: theme, type, affiliation
         * @param {any} id - The ID of the engagement post
         */
        function updateECIFilters(postProperty, filterType, id) {
            if (postProperty && postProperty.raw) {
                for (var t = 0; t < postProperty.raw.length; t++) {
                    var name = postProperty.raw[t].name;

                    // store post ID in ECI.posts
                    ECI.posts[filterType][name] = ECI.posts[filterType][name] || [];
                    if ($.inArray(id, ECI.posts[filterType][name]) === -1) {
                        ECI.posts[filterType][name].push(id);
                    }
                }
            }
        }

        /**
         * Update theme/type/affiliation filters, chart and engagement listing content
         * @param {any} data - The engagement data
         */
        function updateContent(data, showLoading) {
        	setContainerState();

            // update theme, type, affiliation filters
            var filterCount = {};           // store counts for chart
            var hasPosts = (data.engagements);
            var iconStyle = (hasPosts) ? "fa-times-circle" : "fa-circle";
            var filters = ["theme", "type", "affiliation"];

            $.each(filters, function (i, v) {
                if (data[v]) {
                    filterCount[v] = {};

                    var $filter = $("#filter-" + v);
                    $filter.empty();

                    // sort the keys
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
                            var filterName = key;
                            var iconStyle2 = iconStyle;
                            var liCss = "";

                            if (count === 0) {
                                iconStyle2 = "fa-plus-circle";
                                liCss = "inactive";
                            } else {
                                filterName += " (" + count + ")";
                            }

                            var $filterText = (isFilterLinked) ? $("<a />", { "href": "javascript:void(0)" }) : $("<span />");

                            $filter.append(
                                $("<li />", { "data-id": key, "data-type": v, "class": liCss })
                                    .append($("<i />", { "class": "fa " + iconStyle2 + " fa-2x" }))
                                    .append(
                                        $filterText.append(filterName)
                                    )
                            );
                            hasProp = true;
                            sorted[key] = data[v][key];
                        }
                    }
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

                    // attach filter 'remove' or 'add' click
                    if (hasPosts) {
                        $filter.find("i").on("click", function () {
                            // use ECI.filterPosts to keep track of removed filters
                            if (!ECI.filterPosts) {
                                clonePosts();
                            }

                            var $li = $(this).parent();
                            var filterValue = $li.attr("data-id");          // e.g. 'Youth and Family', etc.
                            var filterType = $li.attr("data-type");     // e.g. 'theme', 'type', 'affiliation'

                            if ($li.hasClass("inactive")) {
                                // adding back the filter - we need to restore the filter's post ID list
                                ECI.filterPosts[filterType][filterValue] = $.extend(true, [], ECI.posts[filterType][filterValue]);
                            } else {
                                // removing the filter - just need to empty filter's post ID list
                                ECI.filterPosts[filterType][filterValue] = [];
                            }

                            // now update other filter types - first, get the post items still in the remaining filters' ID list
                            var remainPosts = [];
                            for (var x in ECI.posts[filterType]) {
                                $.merge(remainPosts, ECI.filterPosts[filterType][x]);
                            }

                            // if we just removed the last filter, we need to add all filters back
                            if (remainPosts.length === 0) {
                                // reset all filters
                                clonePosts();
                            } else {
                                // update other filter groups - remove the post item if it's not in the remaining filters' ID list
                                $.each(filters, function (j, w) {
                                    if (w !== filterType) {
                                        // loop through each filter within this type
                                        for (var x in ECI.filterPosts[w]) {
                                            // rebuild the ID list for the filter
                                            ECI.filterPosts[w][x] = [];

                                            // loop through each post ID within this filter
                                            for (var k = 0; k < ECI.posts[w][x].length; k++) {
                                                // if the ID is in the remaining post list, keep it
                                                if ($.inArray(ECI.posts[w][x][k], remainPosts) !== -1) {
                                                    ECI.filterPosts[w][x].push(ECI.posts[w][x][k]);
                                                }
                                            }
                                        }
                                    }
                                });
                            }

                            updateContent(ECI.filterPosts);
                        });
                    }

                }
            });

            // attach theme 'link' click
            $("#filter-theme").find("a").on("click", function (e) {
                var id = $(this).parent().attr("data-id");
                var target = $("#" + getThemeId(id));
                if (target.length) {
                    $('html,body').animate({
                        scrollTop: target.offset().top - 135
                    }, 1000);
                    return false;
                }
            });

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
            for (var x in data.theme) {
                if (filterCount.theme[x] > 0) {
                    chartData.push({
                        name: x,
                        y: filterCount.theme[x]
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
         * Clone ECI.posts to ECI.filterPosts
         */
        function clonePosts() {
            ECI.filterPosts = {
                "theme": $.extend(true, {}, ECI.posts.theme),
                "type": $.extend(true, {}, ECI.posts.type),
                "affiliation": $.extend(true, {}, ECI.posts.affiliation),
                "engagements": true
            };
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
            var postIDs = ECI.filterPosts ? ECI.filterPosts.theme[theme] : ECI.posts.theme[theme];
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

					//TODO: move this to Yan's flow
					$item.on("click", function( e ){
						var post_id = $(this).attr("data-id");
						//console.log( ECI.posts );
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
            var posts = ECI.filterPosts || ECI.posts;
            var hasGeog = (ECI.geoid.length > 0);
            var hasPosts = hasGeog && !$.isEmptyObject(posts.theme);
            $("#style-container").toggle(hasPosts);
            $("#engage-list").toggle(hasPosts);
            $("#impact-container").toggle(hasGeog && ECI.igeog !== 1);

            var hasChart = !hasGeog || hasPosts;
            $("#chart-container").toggle(hasChart);
            $("#engage-container").toggle(hasChart);
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
* Event based jQuery element resize - to detect a div element resize event.
* The script does not use any kind of timer(s) to detect size changes. It uses the resize event on (invincible) iframe(s)
* which makes it perform much better than other solutions which use timers to poll element size.
* The script detects size changes made from JS, CSS, animations etc. and it works on any element able to contain other elements (e.g. div, p, li etc.).
* -- source: http://manos.malihu.gr/event-based-jquery-element-resize/
*/
(function (factory) {

    if (typeof define === "function" && define.amd) {
        define(["jquery"], factory); //AMD
    } else if (typeof exports === "object") {
        module.exports = factory; //Browserify
    } else {
        factory(jQuery); //globals
    }

}(function ($) {

    $.event.special.mresize = {
        add: function () {
            var el = $(this);
            if (el.data("mresize")) return;
            if (el.css("position") === "static") el.css("position", "relative");
            el
                .append("<div class='resize' style='position:absolute; width:auto; height:auto; top:0; right:0; bottom:0; left:0; margin:0; padding:0; overflow:hidden; visibility:hidden; z-index:-1'><iframe style='width:100%; height:0; border:0; visibility:visible; margin:0' /><iframe style='width:0; height:100%; border:0; visibility:visible; margin:0' /></div>")
                .data("mresize", { "w": el.width(), "h": el.height(), t: null, throttle: 100 })
                .find(".resize iframe").each(function () {
                    $(this.contentWindow || this).on("resize", function () {
                        var d = el.data("mresize");
                        if (d.w !== el.width() || d.h !== el.height()) {
                            if (d.t) clearTimeout(d.t);
                            d.t = setTimeout(function () {
                                el.triggerHandler("mresize");
                                d.w = el.width();
                                d.h = el.height();
                            }, d.throttle);
                        }
                    });
                });
        },
        remove: function () {
            $(this).removeData("mresize").find(".resize").remove();
        }
    };

    }));


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