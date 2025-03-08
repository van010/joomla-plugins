(function ($){
    var JAList = function (element) {
        var $element = this.$element = $(element);

        // bind click event for button
        $element.bindActions ('.action', this);

        // make textarea auto height
        $element.elasticTextarea('delete_row clone_row updated');

        // make all field as ignore save
        $element.find ('input, textarea, select').not('.acm-object').data('ignoresave', 1);

        // reset index
        //$element.data('index', 0);

        // trigger updated event for element after built
        setTimeout(function(){$element.trigger('updated')}, 100);
    };

    // Actions
    JAList.prototype.delete_row = function (btn) {
        var $btn = $(btn),
            $row = $btn.parents('div.ja-item').first();
        if (!$row.hasClass('first')) {
            $row.remove();
        }
    };

    JAList.prototype.clone_row = function (btn) {
        var $btn = $(btn),
            $row = $btn.parents('div.ja-item').first(),
            idx = this.$element.data('index');
        this.$element.data('index', ++idx);
        jaTools.fixCloneObject($row.jaclone(idx), true);

		// fix media field joomla 3.7
		$('.jalist div.ja-item').each(function() {
			let $media = $(this).find('.field-media-wrapper').clone(false, false);
			let $td = $(this).find('.field-media-wrapper').parent();
			$(this).find('.field-media-wrapper').remove();
			$td.append($media);

			$media.fieldMedia && $media.fieldMedia();
			$media.find(".hasTooltip").tooltip({"html": true,"container": "body"});
			// need to attach data-name again after refresh new row.
			$media.find('input').each(function () {
				if ($(this).data('name') == undefined && $(this).attr('name') != undefined) {
					$(this).data('name', $(this).attr('name').replace(/\[\d+\]$/, ''));
				}
			});
		});
		// end fix media field joomla 3.7
    };

    function Plugin() {
        return new JAList(this);
    }

    $.fn.jalist             = Plugin;
    $.fn.jalist.Constructor = JAList;

	$(window).on('load', function() {
		$('#jform_params_maptype').on('change', function(){
			if ($(this).val() == 'custom')
				$('#jform_params_custom_tile').addClass('required');
			else
				$('#jform_params_custom_tile').removeClass('required');
		});
		if ($('#jform_params_maptype').val() == 'custom')
			$('#jform_params_custom_tile').addClass('required');
		else
			$('#jform_params_custom_tile').removeClass('required');
	});
})(jQuery);