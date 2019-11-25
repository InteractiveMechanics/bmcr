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
add_action('send_single_reminder', 'send_single_reminder', 10, 4);


//sends reminders to each address in the recipients array
function schedule_reminders( $recipients, $subject, $message, $message_headers = '', $time_offset = 1, $date ) {
  $recipients = (array) $recipients;

  $send_time = $date;

  foreach ( $recipients as $recipient ) {
    wp_schedule_single_event( $send_time, 'send_single_reminder',
      [ $recipient, $subject, $message, $message_headers ] );
    $send_time += $time_offset;
  }
}

//call to send a single reminder
function send_single_reminder( $to, $subject, $message, $message_headers = '' ) {
	wp_mail( $to, $subject, $message, $message_headers );
}

function save_datesetter($post_id, $post, $update) {

    $post_type  = get_post_type($post_id);
    if ( $post_type !== "reviews" ){ return; };
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


  //get reviewers
  $reviewers = get_post_meta($post_id, 'reviewers', true);
  $recipients = [];

  if ($reviewers) {
    for ($i=0; $i<$reviewers; $i++) {
      $meta_key = 'reviewers_'.$i.'_reviewer_email';
      $sub_field_value = get_post_meta($post_id, $meta_key, true);
      array_push($recipients, $sub_field_value);
    }
  }


  $date_assigned =  get_field( 'date_assigned');
  $first_reminder =  get_field('first_reminder_date');
  $second_reminder =  get_field( 'second_reminder_date');
  $date_review_received =  get_field('date_review_received');


  //only set reminders and schedule emails if no reminder dates set, date must be assigned first
  if ( $date_assigned && !$first_reminder && !$second_reminder ) {

    $first_reminder_date = date('F j, Y', strtotime('+1 day', strtotime($date_assigned)));
    update_post_meta( $post_id, 'first_reminder_date', $first_reminder_date);
    $second_reminder_date = date('F j, Y', strtotime('+2 day', strtotime($date_assigned)));
    update_post_meta( $post_id, 'second_reminder_date', $second_reminder_date);




    //render date_time for email set to noon eastern (1600 UST)
    $first_reminder_date_time = strtotime('16:00', strtotime( $first_reminder_date));
    //schedule first reminder to all reviewers
    schedule_reminders( $recipients, 'BMCR *TEST* Reviewer Reminder', 'This is a test email generated by the new BMCR website. Please ignore at this time.', '', 1, $first_reminder_date_time );

    //render date_time for email set to noon eastern (1600 UST)
    $second_reminder_date_time = strtotime('16:00', strtotime( $second_reminder_date));
    //schedule second reminder to all reviewers
    schedule_reminders( $recipients, 'BMCR *TEST* Reviewer Reminder', 'This is a test email generated by the new BMCR website. Please ignore at this time.', '', 1, $second_reminder_date_time );
  }

	// IF THERE IS A VALUE IN date_review_received
	// CLEAR OUT THE REMINDER DATES
  if ( $date_review_received ) {
    delete_post_meta( $post_id, 'first_reminder_date');
    delete_post_meta( $post_id, 'second_reminder_date');

    //delete any scheduled reminders #91
    // if ($first_reminder_date){
    //   $first_reminder_date_time = strtotime('16:00', strtotime( $first_reminder_date));

    //the specific event can be removed with the correct array, should make a function to unschedule multiple emails
    //   wp_unschedule_event( $first_reminder_date_time, 'send_single_reminder',
    //     [ $recipient, $subject, $message, '' ]);
    // }


  }


}

//move to another plugin, bmcr_ninjaforms?
add_filter( 'ninja_forms_render_options', function($options,$settings){
   if( $settings['key'] == 'reviews' ){
       $args = array(
           'post_type' => 'reviews',
           'orderby' => 'menu_order',
           'order' => 'ASC',
           'posts_per_page' => 20,
           'post_status' => 'pitch'
       );
       $the_query = new WP_Query( $args );
       if ( $the_query->have_posts() ){
           global $post;
           while ( $the_query->have_posts() ){
               $the_query->the_post();
               $options[] = array('label' => get_the_title( ), 'value' => get_the_title( ),
               'calc' => 0
           );
           }

       }

       // If viewing a submission get the submitted value in case it is no longer an option in the form.
		if ( is_admin() && array_key_exists( 'post', $_GET ) ) {
			$post_id = absint( $_GET[ 'post' ] );
			$selected_value = get_post_meta( $post_id, '_field_13', true );
      //echo print_r(get_post_meta( $post_id));

			// Check whether the selected value is already in $options (either part of the form or added above).
			$key = array_search( $selected_value, array_column( $options, 'value' ) );
			// Only add the selected value if it's not present.
			if ( false === $key ) {
				$options[] = [
					'label' => $selected_value,  // The original display label is not available.
					'value' => $selected_value,
					'calc' => 0,
					'selected' => true,
				];
			}
		}
        //wp_reset_postdata();
   }


   return $options;
},10,2);

?>
