<?php
/*
Plugin Name: BMCR Datesetter
Plugin URI: http://dev.interactivemechanics.com/bmcr
Description: Declares a plugin that will auto-set dates on reviews.
Version: 1.0
Author: Interactive Mechanics 
Author URI: http://www.interactivemechanics.com/
License: GPLv2
*/


add_action( 'save_post', 'save_datesetter' );

function save_datesetter($post_id, $post, $update) {
    
    $post_type  = get_post_type($post_id);
    if ( $post_type !== "reviews" ){ return; }
    if ( $update ){ return; }
    

	// IF THERE ISN'T A VALUE IN date_received AND NOT PUBLISHED
	// SET TO THE CURRENT DATE
	if ( data-name="date_received" )
		input[type="text"]
			
	
	// IF THERE IS A VALUE IN date_assigned
	// SET THE REMINDER DATES 4 AND 8 MONTHS OUT
	
	// IF THERE IS A VALUE IN date_review_received
	// CLEAR OUT THE REMINDER DATES 
}

?>