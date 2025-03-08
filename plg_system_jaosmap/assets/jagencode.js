(function($) {
	JAElementGenCode = function() {
		this.code = '{jaosmap ';
		this.prefix = 'jform[params]';
		this.objText = '[name="jform[params][code_container]"]';
		this.objCheckboxes = this.prefix + '[list_params][]';
		this.mapPreviewId = 'jaMapPreview';
		this.form = document.adminForm;
		
		this.mapHolder = 'map-preview-container';
		this.mapId = 'ja-widget-map';
		this.objMap = null;
		this.aUserSetting = {};
		//
		this.scanItem();
		this.getUserSetting();
	}
	
	JAElementGenCode.prototype.getUserSetting = function() {
		this.aUserSetting = {};
		//get user setting
		var sConfig = $(this.objText).val();
		settings = sConfig.trim();
		// turn string to json type
		settings = settings.replace('{jaosmap ', '{');
		settings = settings.replace('{/jaosmap}', '');
		settings = settings.replace(/([a-z0-9_]+)=/g, ', "$1":');
		settings = settings.replace(/^\{,/, '{');
		settings = JSON.decode(settings);
		this.aUserSetting = settings ? settings : {};
	}

	//======================================================================
	// handle userConfigs in Checkbox general config
	//======================================================================
	JAElementGenCode.prototype.getFormData = function() {
		var frmData = $(this.form).serializeObject();

		var data = {};
		for(var property in frmData) {
			var prop = property;
			if(prop.indexOf(this.prefix) == 0) {
				prop = prop.substr(this.prefix.length);
				prop = prop.split(/\]\[/i);//E.g:jform[params][locations][location][0]

				var cdata = data;
				for(var i=0; i<prop.length; i++) {
					var sp = prop[i].replace(/[\[\]]+/g, '');

					if(i<prop.length - 1) {
						if(typeof(cdata[sp]) == 'undefined') {
							cdata[sp] = {};
						}

						cdata = cdata[sp];
					} else {
						cdata[sp] = frmData[property];
					}
				}

			}
		}
		// fix image path in joomla 4
		for (var key in data.locations.icon){
			if (data.locations.icon[key].includes('#joomlaImage:')){
				data.locations.icon[key] = data.locations.icon[key].split('#joomlaImage:')[0];
			}
		}
		return data;
	}
	
	JAElementGenCode.prototype.genCode = function() {
		this.scanItem();
		this.getUserSetting();
		//
		var str = this.code,
			data = this.getFormData();
		for(var i=0; i < this.form.elements[this.objCheckboxes].length; i++) {
			var item = this.form.elements[this.objCheckboxes][i];
			if(item.checked && !item.disabled) {
				var e = item.value,
					value = '';

				if(typeof(data[e]) != 'undefined') {
					value = data[e];
					if(typeof(value) == 'object') {
						value = JSON.encode(value);
					}
				}

				//check user setting
				if(this.aUserSetting[item.value]) {
					value = this.aUserSetting[item.value];
				}
				
				str += item.value + "='" + this.addslashes(String(value)) + "' ";
			}
		}
		str += '}{/jaosmap}';
		
		$(this.objText).val(str);
		
		//reset user setting
		this.getUserSetting();
	};
	/**
	 * Scan for check item is enable or diabled
	*/
	JAElementGenCode.prototype.scanItem = function() {
		var i;
		for(i=0; i < this.form.elements[this.objCheckboxes].length; i++) {
			var item = this.form.elements[this.objCheckboxes][i];
			if(item.alt) {
				var disabled = (!item.checked || item.disabled) ? true : false;
				this.setChildren(item.alt, disabled);
			}
		}
	};
	
	JAElementGenCode.prototype.setChildren = function(children, disabled) {
		aChild = children.split(',');
		var i;
		var j;
		for(j=0; j<aChild.length; j++) {
			for(i=0; i < this.form.elements[this.objCheckboxes].length; i++) {
				var item = this.form.elements[this.objCheckboxes][i];
				if(item.value == aChild[j]) {
					item.disabled = disabled;
					var label = item.id + '-label';
					if($('#'+label)) {
						if(disabled)
							$('#'+label).addClass('item_disable');
						else
							$('#'+label).removeClass('item_disable');
					}
					break;
				}
			}
			
		}
	};
	
	JAElementGenCode.prototype.previewMap = function() {
		var aParams = this.getFormData();
		this.getUserSetting();
		
		for(key in this.aUserSetting) {
			aParams[key] = this.aUserSetting[key];
		}
		
		aParams['context_menu'] = 0;
		aParams["map_width"] = parseInt(aParams["map_width"]);
		aParams["map_height"] = parseInt(aParams["map_height"]);
		aParams["maptype_control_display"] = parseInt(aParams["maptype_control_display"]);
		aParams["toolbar_control_display"] = parseInt(aParams["toolbar_control_display"]);
		aParams["display_scale"] = parseInt(aParams["display_scale"]);
		aParams["display_overview"] = parseInt(aParams["display_overview"]);
		aParams["zoom"] = parseInt(aParams["zoom"]);
	
		this.createMap(aParams);
	};
	
	
	JAElementGenCode.prototype.createMap = function(aParams){
		var map_container = this.mapId + '-container';
		var $container = $('#'+map_container);
		$container.height(aParams.map_height);
		$('.tingle-modal-box').width(aParams.map_width);

		var map = new JAOSMAP;
		map.render(map_container, aParams);
	};
	
	JAElementGenCode.prototype.addslashes = function(str) {
		//str=str.replace(/\\/g,'\\\\');
		str=str.replace(/\'/g,'\\\'');
		//str=str.replace(/\"/g,'\\"');
		//str=str.replace(/\0/g,'\\0');
		return str;
	};
	
	JAElementGenCode.prototype.stripslashes = function(str) {
		str=str.replace(/\\'/g,'\'');
		//str=str.replace(/\\"/g,'"');
		//str=str.replace(/\\0/g,'\0');
		//str=str.replace(/\\\\/g,'\\');
		return str;
	};

	window.CopyToClipboard = function(obj)
	{
		$('[name="'+obj+'"]').focus().select();
		document.execCommand("Copy");
	}

	$.fn.serializeObject = function(){
	var obj = {};

	$.each( this.serializeArray(), function(i,o){
		var n = o.name,
		v = o.value;

		obj[n] = obj[n] === undefined ? v
			: $.isArray( obj[n] ) ? obj[n].concat( v )
			: [ obj[n], v ];
		});

		return obj;
	};


	$(document).ready(function(){
		var objGencode = new JAElementGenCode();
		var i;
		for(i=0; i < objGencode.form.elements[objGencode.objCheckboxes].length; i++) {
			$(objGencode.form.elements[objGencode.objCheckboxes][i]).on('click', function() {
				objGencode.genCode();
			});
		}

		$('#'+objGencode.mapPreviewId).on('click', function(e) {
			e.preventDefault();
			
			var modal = new tingle.modal({
				stickyFooter: false,
				closeMethods: ['overlay', 'button', 'escape'],
				onClose: function() {
					$('.tingle-modal').remove();
				}
			});

			modal.setContent('<div id="ja-widget-map-container"></div>');
			modal.open();
			
			objGencode.previewMap();
		});
	});
})(jQuery);
