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
//add_action( 'save_post', 'schedule_reminders', 10, 3 );

//similar to schudule_emails() in publishpress/notifications/notifications.
function schedule_reminder( $recipient, $subject, $message, $message_headers = '', $date ) {

  wp_schedule_single_event( $date, 'send_single_reminder',[ $recipient, $subject, $message, $message_headers ] );

}

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
  $date_assigned =  get_field('date_assigned');
  $reviewers = get_field('reviewers');
  if ( $date_assigned ) {

    $first_reminder_date = date('F j, Y', strtotime('+4 month', strtotime($date_assigned)));
    update_post_meta( $post_id, 'first_reminder_date', $first_reminder_date);
    $second_reminder_date = date('F j, Y', strtotime('+8 month', strtotime($date_assigned)));
    update_post_meta( $post_id, 'second_reminder_date', $second_reminder_date);


    //render date_time for email set to noon)
    $first_reminder_date_time = strtotime('19:35', strtotime('11-18-2019'));
    schedule_reminder( 'mattlovedesign@gmail.com', 'test reminder 10 minutes after save', $first_reminder_date, '', 1, $first_reminder_date_time);


    // $second_reminder_date_time = date('F j, Y H:i:s', strtotime('noon', strtotime($second_reminder_date)));
    // schedule_reminder( 'mattlovedesign@gmail.com', 'test reminder 2 delayed 2 mins', 'test reminder 2 body', '', 1, $second_reminder_date_time);


  }

	// IF THERE IS A VALUE IN date_review_received
	// CLEAR OUT THE REMINDER DATES
  $date_review_received =  get_field('date_review_received');
  if ( $date_review_received ) {
    delete_post_meta( $post_id, 'first_reminder_date');
    delete_post_meta( $post_id, 'second_reminder_date');

    //delete any scheduled reminders #91

  }


}

//move to another plugin
add_filter( 'ninja_forms_render_options', function($options,$settings){
   if( $settings['key'] == 'reviews' ){
       $args = array(
           'post_type' => 'reviews',
           'orderby' => 'menu_order',
           'order' => 'ASC',
           'posts_per_page' => 100,
           'post_status' => 'title-added'
       );
       $the_query = new WP_Query( $args );
       if ( $the_query->have_posts() ){
           global $post;
           while ( $the_query->have_posts() ){
               $the_query->the_post();
               $options[] = array('label' => get_the_title( ), 'value' => get_the_title( ));
           }
           wp_reset_postdata();
       }
   }
   return $options;
},10,2);

?>
