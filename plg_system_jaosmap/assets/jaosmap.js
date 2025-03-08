// json handler from mootool core
(function(){

var special = {'\b': '\\b', '\t': '\\t', '\n': '\\n', '\f': '\\f', '\r': '\\r', '"' : '\\"', '\\': '\\\\'};

var escape = function(chr){
    return special[chr] || '\\u' + ('0000' + chr.charCodeAt(0).toString(16)).slice(-4);
};

JSON.validate = function(string){
    string = string.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@').
                    replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').
                    replace(/(?:^|:|,)(?:\s*\[)+/g, '');

    return (/^[\],:{}\s]*$/).test(string);
};

JSON.encode = JSON.stringify;

// handle #location here
JSON.decode = function(string, secure){
    if (!string || typeof string != 'string') return null;

    if (secure == null) secure = JSON.secure;
    if (secure){
        if (JSON.parse) return JSON.parse(string);
        if (!JSON.validate(string)) throw new Error('JSON could not decode the input; security is enabled and the value is not secure.');
    }

    return eval('(' + string + ')');
};

})();

// ja osmap
(function(root, $) {
    var juri_root = root.Joomla.getOptions('system.paths').root + '/';

    var JAOSMAP = function() {
        this.map = null;
    };

    JAOSMAP.prototype.render = function(selector, settings) {
        var self = this;
        var marks = typeof settings.locations === 'string' ? JSON.decode(settings.locations) : settings.locations;
        if (!marks || !marks.latitude || !marks.longitude) {
            return;
        }

        var wheel = +settings.disable_scrollwheelzoom ? true : false;
        self.map = L.map(selector, {
            scrollWheelZoom: wheel,
            dragging: !L.Browser.mobile, 
            tap: !L.Browser.mobile
        });

        self.map._container.addEventListener('touchstart', onTwoFingerDrag);
        self.map._container.addEventListener('touchend', onTwoFingerDrag);

        function onTwoFingerDrag (e) {
            if (e.type === 'touchstart' && e.touches.length === 1) {
                e.currentTarget.classList.add('swiping');
            } else {
                e.currentTarget.classList.remove('swiping');
            }
        }

        if (settings.maptype == 'style' && settings.custom_style !== "" && settings.custom_style_token !== "") {
            // use custom style code for map.
            // the layer must be right after add the map.
            var gl = L.mapboxGL({
                accessToken: settings.custom_style_token,
                style: settings.custom_style
            }).addTo(self.map);
        } else {
            // use normal tile map
            var maptype = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
            if (settings.maptype == 'cycle')
                maptype = 'https://{s}.tile.thunderforest.com/cycle/{z}/{x}/{y}.png';
            if (settings.maptype == 'transport')
                maptype = 'https://{s}.tile.thunderforest.com/transport/{z}/{x}/{y}.png';
            if (settings.maptype == 'humanitarian')
                maptype = 'https://tile-{s}.openstreetmap.fr/hot/{z}/{x}/{y}.png';
            if (settings.maptype == 'custom') {
                if (settings.custom_tile !== '')
                    maptype = settings.custom_tile;
            }

            L.tileLayer(maptype, {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(self.map);
        }

        var deferreds = [];
        var pointers = [];

        $.each(marks.latitude, function(m) {
            if (marks.latitude[m] === '' 
                || marks.longitude[m] === '' 
                || isNaN(marks.latitude[m]) 
                || isNaN(marks.longitude[m])) {
                return;
            }

            var pointer = {
                latitude: marks.latitude[m] ? marks.latitude[m] : 0,
                longitude: marks.longitude[m] ? marks.longitude[m] : 0,
                info: (marks.info && marks.info[m]) ? marks.info[m] : '',
                icon: (marks.icon && marks.icon[m]) ? marks.icon[m] : '',
                location: (marks.location && marks.location[m]) ? marks.location[m] : ''
            }
        
            pointers.push(pointer);
        });
        
        if (!pointers.length) {
            return;
        }
        // choose mode: routing or normal
        if (settings.mode === 'routing') {
            var def = self.addWaypoints(self.map, pointers, settings);
            deferreds.push(def);
        } else {
            pointers.forEach(function(pointer) {
                var def = self.addMarker(self.map, pointer, settings);
                deferreds.push(def);
            });
        }

        var first = pointers[0];
        var last = pointers[pointers.length - 1];
        var zoom = settings.zoom > 18 ? 18 : settings.zoom;

        if (settings.center === 'all') {
            $.when.apply($, deferreds).then(function() {
                var points = pointers.map(function(item) {
                    return [item.latitude, item.longitude];
                })

                self.map.fitBounds(points);
            })
        } else {
            var view = settings.center === 'first' ? first : last;
            self.map.setView( L.latLng(view.latitude, view.longitude), zoom );
        }
        return self.map;
    }

    JAOSMAP.prototype.addWaypoints = function(map, pointers, settings) {
        var self = this;
        return $.Deferred(function(defer) {
            var config = {};
            var langs = {
                'ar-aa': 'ar',
                'da-dk': 'da',
                'de-de': 'de',
                'en-gb': 'en',
                'eo-xx': 'eo',
                'es-es': 'es-ES',
                'es-co': 'es',
                'fi-fi': 'fi',
                'fr-fr': 'fr',
                'he-il': 'he',
                'id-id': 'id',
                'it-it': 'it',
                'ko-kr': 'ko',
                'my-my': 'my',
                'nl-nl': 'no',
                'no-nb': 'no',
                'pl-pl': 'pl',
                'pt-br': 'pt-BR',
                'pt-pt': 'pt-PT',
                'ro-ro': 'ro',
                'ru-ru': 'ru',
                'sl-si': 'sl',
                'sv-se': 'sv',
                'tr-tr': 'tr',
                'uk-ua': 'uk',
                'vi-vn': 'vi',
                'zh-cn': 'zh-Hans'
            }

            var language = langs[settings.routing_language] ? langs[settings.routing_language] : 'en';
            var options = {
                language: language
            }

            if (settings.route === 'mapbox') {
                config.router = L.Routing.mapbox(settings.mapbox_access_token, options);
            } else {
                config.router = L.Routing.osrmv1(options);
            }

            var waypoints = pointers.map(function(item) {
                return L.latLng(item.latitude, item.longitude);
            });

            var deferreds = [];
            pointers.forEach(function(item) {
                if (item.icon) {
                    var def = self.getCustomIcon(item);
                    deferreds.push(def);
                } else {
                    deferreds.push(false);
                }
            })

            $.when.apply($, deferreds).then(function() {
                var markers = arguments;
                L.Routing.control(L.extend(config, {
                    waypoints: waypoints,
                    routeWhileDragging: true,
                    reverseWaypoints: true,
                    showAlternatives: true,
                    createMarker: function(i, wp) {
                        var pointer = pointers[i];
                        var marker;
                        if (markers[i]) {
                            marker = markers[i];
                        } else {
                            marker = L.marker([wp.latLng.lat, wp.latLng.lng]);
                        }

                        if (pointer.info !== '') {
                            if (settings.popup_type == 'click')
                                marker.bindPopup(pointer.info);
                            if (settings.popup_type == 'hover')
                                marker.bindTooltip(pointer.info);
                        }

                        return marker;
                    },
                    show: false,
                    collapsible: true
                })).addTo(map);
                defer.resolve();
            })
        })
    }

    JAOSMAP.prototype.addMarker = function(map, pointer, settings) {
        var self = this;
        return $.Deferred(function(defer) {
            var latitude = pointer.latitude;
            var longitude = pointer.longitude;
            var def = new $.Deferred();
            var marker;

            if (pointer.icon) {
                self.getCustomIcon(pointer).then(function(_marker) {
                    marker = _marker;
                    marker.addTo(map);
                    def.resolve(marker)
                })
            } else {
                var marker = L.marker([latitude, longitude]).addTo(map);
                def.resolve(marker);
            }

            def.then(function() {
                if (pointer.info !== '') {
                    if (settings.popup_type == 'click')
                        marker.bindPopup(pointer.info);
                    if (settings.popup_type == 'hover')
                        marker.bindTooltip(pointer.info);
                }
                defer.resolve();
            })
        })
    }

    JAOSMAP.prototype.getCustomIcon = function(pointer) {
        return $.Deferred(function(defer) {
            var imgWidth = 0;
            var imgHeight = 0;
            var MAX = 40;
            var regex = /^(https|http):\/\//g;
            var url = regex.test(pointer.icon) ? pointer.icon : juri_root + pointer.icon;

            $(document).ready(function() {
                var tmpImg = new Image() ;
                tmpImg.src = url;
                tmpImg.onload = function() {
                    // Run onload code.
                    if (this.width < MAX || this.height < MAX) {
                        imgWidth = this.width;
                        imgHeight = this.height;
                    } else if (this.width < this.height) {
                        imgWidth = MAX;
                        imgHeight = (this.height * imgWidth) / this.width;
                    } else {
                        imgHeight = MAX;
                        imgWidth = (this.width * imgHeight) / this.height;
                    }

                    var icon = L.icon({
                        iconUrl: url,
                        iconSize: [imgWidth, imgHeight],
                        iconAnchor: [imgWidth / 2, imgHeight],
                        popupAnchor: [0, (imgHeight / 4) - imgHeight],
                        tooltipAnchor: [0, (imgHeight / 4) - imgHeight]
                    });

                    var marker = L.marker(
                        [pointer.latitude, pointer.longitude],
                        {icon: icon}
                    );
                    defer.resolve(marker);
                } ;
            }) ;
        })
    }
    root.JAOSMAP = JAOSMAP;
})(window, jQuery)