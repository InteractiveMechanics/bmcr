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


add_action( 'save_post', 'save_datesetter', 10 , 3);

function save_datesetter($post_id, $post, $update) {

    $post_type  = get_post_type($post_id);
    if ( $post_type !== "reviews" ){ return; }
    //if ( $update ){ return; }


	// IF THERE ISN'T A VALUE IN date_received AND NOT PUBLISHED
	// SET TO THE CURRENT DATE
  $post_status =  get_post_status($post_id);
  $date_received =  get_field('date_received');
	if ( (!$date_received || empty($date_received)) && $post_status !== 'publish') {
    $current_date = get_the_date('F j, Y');
    update_post_meta( $post_id, 'date_received', $current_date);
  }

	// IF THERE IS A VALUE IN date_assigned
	// SET THE REMINDER DATES 4 AND 8 MONTHS OUT
  $date_assigned =  get_field('date_assigned');
  if ( $date_assigned ) {
    $first_reminder_date = date('F j, Y', strtotime('+4 month', strtotime($date_assigned)));
    update_post_meta( $post_id, 'first_reminder_date', $first_reminder_date);

    $second_reminder_date = date('F j, Y', strtotime('+8 month', strtotime($date_assigned)));
    update_post_meta( $post_id, 'second_reminder_date', $second_reminder_date);
  }

	// IF THERE IS A VALUE IN date_review_received
	// CLEAR OUT THE REMINDER DATES
  $date_review_received =  get_field('date_review_received');
  if ( $date_review_received ) {
    delete_post_meta( $post_id, 'first_reminder_date');
    delete_post_meta( $post_id, 'second_reminder_date');
  }
}

?>
