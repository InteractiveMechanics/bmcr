jQuery(function($){
    var notes = $('div[type="notes"] note');

    if (notes.length){
        $('div[type="notes"]').prepend('<h2>Notes</h2>');
    
        notes.each(function(index){
	        if ($(this).attr('xml:id')){
            	var id = $(this).attr('xml:id');
            } else {
	            var id = $(this).attr('ns0_id');
            }
    
            $(this).prepend('<a href="#' + id + '" name="' + id + '-footer">' + (index + 1) + '.</a>&nbsp;');
            $('.entry-content').find('ptr[target="' + id + '"]').html('<a name="' + id + '" href="#' + id + '-footer"><sup>[' + (index + 1) + ']</sup></a>');
        });
    }
    
});
