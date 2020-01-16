jQuery(document).ready(function($){$('#tease_post_types-forum').change(function(){if($(this).val()!='0'){$('#topics-teaserdef').closest('tr').show();$('#topics-teaserdef').show();}else{$('#topics-teaserdef').hide();$('#topics-teaserdef').closest('tr').hide();}});$('#teaser_usage-post select[name="topics_teaser"]').change(function(){if($(this).val()=='0'){$('#topics-teaserdef').hide();$('#topics-teaserdef').closest('tr').hide();}else{$('#topics-teaserdef').closest('tr').show();$('#topics-teaserdef').show();if($(this).val()=='tease_replies'){$('#topic_teaserdef').hide();$('#reply_teaserdef').show();}else{$('#topic_teaserdef').show();$('#reply_teaserdef').show();}}
if($('#teaser-pvt-post input:visible').length)
$('#teaser-pvt-post').show();else
$('#teaser-pvt-post').hide();});$('#topics_teaser').click(function(){if($(this).val()=="0")
$('#pp_bbp_forum_teaser_template_notice').show();else
$('#pp_bbp_forum_teaser_template_notice').hide();});});