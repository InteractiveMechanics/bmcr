<?php

	$bkgr_img = get_field('bkgr_img');
	$bkgr_img_url = $bkgr_img['url'];
	$bkgr_img_alt = $bkgr_img['alt'];
	$hero_title = get_field('hero_title');
	$intro_text = get_field('intro_text');
?>

<!-- check if there is a background image -->
<?php if($bkgr_img): ?>
	
<div class="jumbotron jumbotron-fluid" style="background-image: url('<?php echo $bkgr_img_url; ?>')" alt="<?php echo $bkgr_img_alt; ?>">
	
<?php else: ?>
		
<div class="jumbotron jumbotron-fluid">
	
<?php endif; ?>
			<div class="row">
				<div class="col-sm-10 offset-sm-1">
					<h1><?php echo $hero_title; ?></h1>
					<p><?php echo $intro_text; ?></p>
				</div>
			</div>
	
	
			
<?php if( have_rows('hero_btns') ): 
		
	while ( have_rows('hero_btns') ) : the_row();
	
		$hero_btn_url = get_sub_field('btn_link'); 
		$hero_btn_text = get_sub_field('button_text');
		
	?>
        
	<a href="<?php echo $hero_btn_url; ?>"><?php echo $hero_btn_text; ?></a>
	        	
	<?php endwhile; ?>
		
<? endif; ?>
		
</div><!-- /.jumbotron -->