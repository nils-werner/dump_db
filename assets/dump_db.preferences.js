Symphony.Language.add({
	'Do you really want to overwrite your database with the contents from the file?': false
});


jQuery(document).ready(function () {
	
	// Find elements with only a single li-child.
	jQuery("button[name^='action[restore]']").click(function(index) {
		return confirm(Symphony.Language.get('Do you really want to overwrite your database with the contents from the file?'));
	});

});
