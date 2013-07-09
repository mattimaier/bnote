/**
 * Main JavaScript file.
 */

tinyMCE.init({
	mode: "exact",
	elements: "tinymce",
	language: "de",
	theme: "advanced",
	theme_advanced_buttons1: "bold, italic, underline, strikethrough, separator," +
			"justifyleft, justifycenter, justifyright, justifyfull, separator," +
			"forecolor, fontsizeselect",
	theme_advanced_buttons2: "",
	theme_advanced_buttons3: "",
	theme_advanced_buttons4: "",
	theme_advanced_toolbar_location: "top",
	theme_advanced_toolbar_align: "left"
});

tinyMCE.init({
	mode: "exact",
	elements: "tinymcefull",
	language: "de",
	theme: "advanced",
	plugins: "preview,table",
	theme_advanced_buttons1: "bold, italic, underline, strikethrough, separator," +
			"justifyleft, justifycenter, justifyright, justifyfull, separator," +
			"styleselect, fontselect, forecolor, fontsizeselect, separator, code",
	theme_advanced_buttons1_add: "preview",
	theme_advanced_buttons2: "bullist, numlist, separator, " +
			"link, unlink, image, separator",
	theme_advanced_buttons2_add: "tablecontrols",
	theme_advanced_buttons3: "",
	theme_advanced_buttons4: "",
	theme_advanced_toolbar_location: "top",
	theme_advanced_toolbar_align: "left",
	plugin_preview_width : "800",
	plugin_preview_height : "440"
});

$(function() {
	$(".dateChooser").datepicker({
		autoSize: true,
		dateFormat: 'dd.mm.yy'
	});
	
	$(".datetimeChooser").datetimepicker({
		stepMinute: 5,
		dateFormat: 'dd.mm.yy',
		hour: 19,
		minute: 30
	});
	
	$("#sortable").sortable();
	$("#sortable").disableSelection();
});
