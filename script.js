function jw_lighttwitterwidget (){
	this.init();
};

jw_lighttwitterwidget.prototype.init = function() {
	jQuery.ajax({
		url: jw_lighttwitterwidget_ajaxobj.ajaxurl,
		cache: false,
		dataType: "json",
		type: "POST",
		data: {
			action: 'jw_lighttwitterwidget_twitterresponse',
			nonce: jw_lighttwitterwidget_ajaxobj.nonce
		},
		beforeSend: function(x) {
			if (x && x.overrideMimeType) {
				x.overrideMimeType("application/json;charset=UTF-8");
			}
		},
		success: function(data) {
			if(data.ok)
				jQuery("#jw_lighttwitterwidget").html(data.fulltext);
			else
				jQuery("#jw_lighttwitterwidget").html(jw_lightcontactform_ajaxobj.servererror);
		},
		error: function(e, xhr) {
			jQuery("#jw_lighttwitterwidget").html(jw_lightcontactform_ajaxobj.servererror);
		}
	});
};

jQuery(document).ready(function() {
	if(jQuery("#jw_lighttwitterwidget").length > 0)
		new jw_lighttwitterwidget(); 
});