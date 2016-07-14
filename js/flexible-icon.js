jQuery(document).ready(function($) {
	$("div[data-type=flexible_content]").each(function() {
		$(this).find("tr[data-name=fc_layout]").each(function() {
			handle_tr( this );
		});
	});

	function format( icon ) {
		var original = icon.element;

		return '<i class="fa ' + $(original).text() + '"></i> ' + $(original).text();
	}

	function handle_tr( $el ) {
		var id = $($el).closest("div[data-type=flexible_content]").data("id");
		var layout_id = $($el).data("id");

		$.ajax({
			method: "POST",
			url: ajaxurl,
			data: {
				action: "acf_fi_get_select_box",
				id: id,
				layout_id: layout_id
			},
			dataType: "json"
		}).done(function( response ) {			
			var field = '<li class="acf-fi-select-box"><div class="acf-input-prepend">' + acf_flexible_icon.layout_icon + '</div><div class="acf-input-wrap"><select id="acf_fields-' + id + '-layouts-' + layout_id + '-visibility" class="acf-is-prepended acf-fi-' + layout_id + ' acf-flexible-icon-select" name="acf_fields[' + id + '][layouts][' + layout_id + '][visibilities]" style="width: 100%;"><option>' + acf_flexible_icon.acf_flexible_icon + '</option><option></option>';

			$.each(response.data.options, function( key, value ) {
				field = field + '<option value="'+ value +'"';

				var icon = response.data.icon;

				if ( "undefined" !== typeof icon ) {
					if ( icon == key ) {
						field = field + ' selected="selected"';
					}
				}

				field = field + '>' + key + '</option>';
			});

			var icon = response.data.icon;

			if ( "undefined" !== typeof icon ) {
				var icon_string = icon;
			}
			else {
				var icon_string = "";
			}

			field = field + '</select><input type="hidden" class="acf-fi-hidden-' + layout_id + '" name="acf_fields[' + id + '][layouts][' + layout_id + '][icon]" value="' + icon_string + '"></div></li>';

			$($el).find("ul.acf-fc-meta").append( field );

			$("select.acf-flexible-icon-select").select2({
				width: "100%",
				formatResult: format
			});

			$("select.acf-flexible-icon-select.acf-fi-" + layout_id ).on("change", function(e) {
				var value = e.val;
				value = value.split(" ");
				value = value[ ( value.length - 1 ) ];

				console.log( value );

				$("input.acf-fi-hidden-" + layout_id).val( value );
			});
		});
	}
});