=== Relevanssi Premium - A Better Search ===
Contributors: msaari
Donate link: https://www.relevanssi.com/
Tags: search, relevance, better search
Requires at least: 5.0.7
Tested up to: 5.3
Stable tag: 2.5.2

Relevanssi Premium replaces the default search with a partial-match search that sorts results by relevance. It also indexes comments and shortcode content.

== Description ==

Relevanssi replaces the standard WordPress search with a better search engine, with lots of features and configurable options. You'll get better results, better presentation of results - your users will thank you.

= Key features =
* Search results sorted in the order of relevance, not by date.
* Fuzzy matching: match partial words, if complete words don't match.
* Find documents matching either just one search term (OR query) or require all words to appear (AND query).
* Search for phrases with quotes, for example "search phrase".
* Create custom excerpts that show where the hit was made, with the search terms highlighted.
* Highlight search terms in the documents when user clicks through search results.
* Search comments, tags, categories and custom fields.

= Advanced features =
* Adjust the weighting for titles, tags and comments.
* Log queries, show most popular queries and recent queries with no hits.
* Restrict searches to categories and tags using a hidden variable or plugin settings.
* Index custom post types and custom taxonomies.
* Index the contents of shortcodes.
* Google-style "Did you mean?" suggestions based on successful user searches.
* Automatic support for [WPML multi-language plugin](http://wpml.org/).
* Automatic support for [s2member membership plugin](http://www.s2member.com/).
* Advanced filtering to help hacking the search results the way you want.
* Search result throttling to improve performance on large databases.
* Disable indexing of post content and post titles with a simple filter hook.
* Multisite support.

= Premium features (only in Relevanssi Premium) =
* PDF content indexing.
* Search result throttling to improve performance on large databases.
* Improved spelling correction in "Did you mean?" suggestions.
* Searching over multiple subsites in one multisite installation.
* Indexing and searching user profiles.
* Weights for post types, including custom post types.
* Limit searches with custom fields.
* Index internal links for the target document (sort of what Google does).
* Search using multiple taxonomies at the same time.

Relevanssi is available in two versions, regular and Premium. Regular Relevanssi is and will remain free to download and use. Relevanssi Premium comes with a cost, but will get all the new features. Standard Relevanssi will be updated to fix bugs, but new features will mostly appear in Premium. Also, support for standard Relevanssi depends very much on my mood and available time. Premium pricing includes support.

= Relevanssi in Facebook =
You can find [Relevanssi in Facebook](https://www.facebook.com/relevanssi). Become a fan to follow the development of the plugin, I'll post updates on bugs, new features and new versions to the Facebook page.

= Other search plugins =
Relevanssi owes a lot to [wpSearch](https://wordpress.org/extend/plugins/wpsearch/) by Kenny Katzgrau. Relevanssi was built to replace wpSearch, when it started to fail.

Search Unleashed is a popular search plugin, but it hasn't been updated since 2010. Relevanssi is in active development and does what Search Unleashed does.



== Installation ==

1. Extract all files from the ZIP file, and then upload the plugin's folder to /wp-content/plugins/.
1. If your blog is in English, skip to the next step. If your blog is in other language, rename the file *stopwords* in the plugin directory as something else or remove it. If there is *stopwords.yourlanguage*, rename it to *stopwords*.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Go to the plugin settings and build the index following the instructions there.

To update your installation, simply overwrite the old files with the new, activate the new version and if the new version has changes in the indexing, rebuild the index.

= Note on updates =
If it seems the plugin doesn't work after an update, the first thing to try is deactivating and reactivating the plugin. If there are changes in the database structure, those changes do not happen without a deactivation, for some reason.

= Changes to templates =
None necessary! Relevanssi uses the standard search form and doesn't usually need any changes in the search results template.

If the search does not bring any results, your theme probably has a query_posts() call in the search results template. That throws Relevanssi off. For more information, see [The most important Relevanssi debugging trick](http://www.relevanssi.com/knowledge-base/query_posts/).

= How to index =
Check the options to make sure they're to your liking, then click "Save indexing options and build the index". If everything's fine, you'll see the Relevanssi options screen again with a message "Indexing successful!"

If something fails, usually the result is a blank screen. The most common problem is a timeout: server ran out of time while indexing. The solution to that is simple: just return to Relevanssi screen (do not just try to reload the blank page) and click "Continue indexing". Indexing will continue. Most databases will get indexed in just few clicks of "Continue indexing". You can follow the process in the "State of the Index": if the amount of documents is growing, the indexing is moving along.

If the indexing gets stuck, something's wrong. I've had trouble with some plugins, for example Flowplayer video player stopped indexing. I had to disable the plugin, index and then activate the plugin again. Try disabling plugins, especially those that use shortcodes, to see if that helps. Relevanssi shows the highest post ID in the index - start troubleshooting from the post or page with the next highest ID. Server error logs may be useful, too.

= Using custom search results =
If you want to use the custom search results, make sure your search results template uses `the_excerpt()` to display the entries, because the plugin creates the custom snippet by replacing the post excerpt.

If you're using a plugin that affects excerpts (like Advanced Excerpt), you may run into some problems. For those cases, I've included the function `relevanssi_the_excerpt()`, which you can use instead of `the_excerpt()`. It prints out the excerpt, but doesn't apply `wp_trim_excerpt()` filters (it does apply `the_content()`, `the_excerpt()`, and `get_the_excerpt()` filters).

To avoid trouble, use the function like this:

`<?php if (function_exists('relevanssi_the_excerpt')) { relevanssi_the_excerpt(); }; ?>`

See Frequently Asked Questions for more instructions on what you can do with Relevanssi.

= The advanced hacker option =
If you're doing something unusual with your search and Relevanssi doesn't work, try using `relevanssi_do_query()`. See [Knowledge Base](http://www.relevanssi.com/knowledge-base/relevanssi_do_query/).

= Uninstalling =
To uninstall the plugin remove the plugin using the normal WordPress plugin management tools (from the Plugins page, first Deactivate, then Delete). If you remove the plugin files manually, the database tables and options will remain.

= Combining with other plugins =
Relevanssi doesn't work with plugins that rely on standard WP search. Those plugins want to access the MySQL queries, for example. That won't do with Relevanssi. [Search Light](http://wordpress.org/extend/plugins/search-light/), for example, won't work with Relevanssi.

Some plugins cause problems when indexing documents. These are generally plugins that use shortcodes to do something somewhat complicated. One such plugin is [MapPress Easy Google Maps](http://wordpress.org/extend/plugins/mappress-google-maps-for-wordpress/). When indexing, you'll get a white screen. To fix the problem, disable either the offending plugin or shortcode expansion in Relevanssi while indexing. After indexing, you can activate the plugin again.

== Frequently Asked Questions ==

= Where is the Relevanssi search box widget? =
There is no Relevanssi search box widget.

Just use the standard search box.

= Where are the user search logs? =
See the top of the admin menu. There's 'User searches'. There. If the logs are empty, please note showing the results needs at least MySQL 5.

= Displaying the number of search results found =

The typical solution to showing the number of search results found does not work with Relevanssi. However, there's a solution that's much easier: the number of search results is stored in a variable within $wp_query. Just add the following code to your search results template:

`<?php echo 'Relevanssi found ' . $wp_query->found_posts . ' hits'; ?>`

= Advanced search result filtering =

If you want to add extra filters to the search results, you can add them using a hook. Relevanssi searches for results in the _relevanssi table, where terms and post_ids are listed. The various filtering methods work by listing either allowed or forbidden post ids in the query WHERE clause. Using the `relevanssi_where` hook you can add your own restrictions to the WHERE clause.

These restrictions must be in the general format of ` AND doc IN (' . {a list of post ids, which could be a subquery} . ')`

For more details, see where the filter is applied in the `relevanssi_search()` function. This is stricly an advanced hacker option for those people who're used to using filters and MySQL WHERE clauses and it is possible to break the search results completely by doing something wrong here.

There's another filter hook, `relevanssi_hits_filter`, which lets you modify the hits directly. The filter passes an array, where index 0 gives the list of hits in the form of an array of post objects and index 1 has the search query as a string. The filter expects you to return an array containing the array of post objects in index 0 (`return array($your_processed_hit_array)`).

= Direct access to query engine =
Relevanssi can't be used in any situation, because it checks the presence of search with the `is_search()` function. This causes some unfortunate limitations and reduces the general usability of the plugin.

You can now access the query engine directly. There's a new function `relevanssi_do_query()`, which can be used to do search queries just about anywhere. The function takes a WP_Query object as a parameter, so you need to store all the search parameters in the object (for example, put the search terms in `$your_query_object->query_vars['s']`). Then just pass the WP_Query object to Relevanssi with `relevanssi_do_query($your_wp_query_object);`.

Relevanssi will process the query and insert the found posts as `$your_query_object->posts`. The query object is passed as reference and modified directly, so there's no return value. The posts array will contain all results that are found.

= Sorting search results =
If you want something else than relevancy ranking, you can use orderby and order parameters. Orderby accepts $post variable attributes and order can be "asc" or "desc". The most relevant attributes here are most likely "post_date" and "comment_count".

If you want to give your users the ability to sort search results by date, you can just add a link to http://www.yourblogdomain.com/?s=search-term&orderby=post_date&order=desc to your search result page.

Order by relevance is either orderby=relevance or no orderby parameter at all.

= Filtering results by date =
You can specify date limits on searches with `by_date` search parameter. You can use it your search result page like this: http://www.yourblogdomain.com/?s=search-term&by_date=1d to offer your visitor the ability to restrict their search to certain time limit (see [RAPLIQ](http://www.rapliq.org/) for a working example).

The date range is always back from the current date and time. Possible units are hour (h), day (d), week (w), month (m) and year (y). So, to see only posts from past week, you could use by_date=7d or by_date=1w.

Using wrong letters for units or impossible date ranges will lead to either defaulting to date or no results at all, depending on case.

Thanks to Charles St-Pierre for the idea.

= Displaying the relevance score =
Relevanssi stores the relevance score it uses to sort results in the $post variable. Just add something like

`echo $post->relevance_score`

to your search results template inside a PHP code block to display the relevance score.

= Did you mean? suggestions =
To use Google-style "did you mean?" suggestions, first enable search query logging. The suggestions are based on logged queries, so without good base of logged queries, the suggestions will be odd and not very useful.

To use the suggestions, add the following line to your search result template, preferably before the have_posts() check:

`<?php if (function_exists('relevanssi_didyoumean')) { relevanssi_didyoumean(get_search_query(), "<p>Did you mean: ", "?</p>", 5); }?>`

The first parameter passes the search term, the second is the text before the result, the third is the text after the result and the number is the amount of search results necessary to not show suggestions. With the default value of 5, suggestions are not shown if the search returns more than 5 hits.

= Search shortcode =
Relevanssi also adds a shortcode to help making links to search results. That way users can easily find more information about a given subject from your blog. The syntax is simple:

`[search]John Doe[/search]`

This will make the text John Doe a link to search results for John Doe. In case you want to link to some other search term than the anchor text (necessary in languages like Finnish), you can use:

`[search term="John Doe"]Mr. John Doe[/search]`

Now the search will be for John Doe, but the anchor says Mr. John Doe.

One more parameter: setting `[search phrase="on"]` will wrap the search term in quotation marks, making it a phrase. This can be useful in some cases.

= Restricting searches to categories and tags =
Relevanssi supports the hidden input field `cat` to restrict searches to certain categories (or tags, since those are pretty much the same). Just add a hidden input field named `cat` in your search form and list the desired category or tag IDs in the `value` field - positive numbers include those categories and tags, negative numbers exclude them.

This input field can only take one category or tag id (a restriction caused by WordPress, not Relevanssi). If you need more, use `cats` and use a comma-separated list of category IDs.

You can also set the restriction from general plugin settings (and then override it in individual search forms with the special field). This works with custom taxonomies as well, just replace `cat` with the name of your taxonomy.

If you want to restrict the search to categories using a dropdown box on the search form, use a code like this:

`<form method="get" action="<?php bloginfo('url'); ?>">
	<div><label class="screen-reader-text" for="s">Search</label>
	<input type="text" value="<?php the_search_query(); ?>" name="s" id="s" />
<?php
	wp_dropdown_categories(array('show_option_all' => 'All categories'));
?>
	<input type="submit" id="searchsubmit" value="Search" />
	</div>
</form>`

This produces a search form with a dropdown box for categories. Do note that this code won't work when placed in a Text widget: either place it directly in the template or use a PHP widget plugin to get a widget that can execute PHP code.

= Restricting searches with taxonomies =

You can use taxonomies to restrict search results to posts and pages tagged with a certain taxonomy term. If you have a custom taxonomy of "People" and want to search entries tagged "John" in this taxonomy, just use `?s=keyword&people=John` in the URL. You should be able to use an input field in the search form to do this, as well - just name the input field with the name of the taxonomy you want to use.

It's also possible to do a dropdown for custom taxonomies, using the same function. Just adjust the arguments like this:

`wp_dropdown_categories(array('show_option_all' => 'All people', 'name' => 'people', 'taxonomy' => 'people'));`

This would do a dropdown box for the "People" taxonomy. The 'name' must be the keyword used in the URL, while 'taxonomy' has the name of the taxonomy.

= Automatic indexing =
Relevanssi indexes changes in documents as soon as they happen. However, changes in shortcoded content won't be registered automatically. If you use lots of shortcodes and dynamic content, you may want to add extra indexing. Here's how to do it:

`if (!wp_next_scheduled('relevanssi_build_index')) {
	wp_schedule_event( time(), 'daily', 'relevanssi_build_index' );
}`

Add the code above in your theme functions.php file so it gets executed. This will cause WordPress to build the index once a day. This is an untested and unsupported feature that may cause trouble and corrupt index if your database is large, so use at your own risk. This was presented at [forum](http://wordpress.org/support/topic/plugin-relevanssi-a-better-search-relevanssi-chron-indexing?replies=2).

= Highlighting terms =
Relevanssi search term highlighting can be used outside search results. You can access the search term highlighting function directly. This can be used for example to highlight search terms in structured search result data that comes from custom fields and isn't normally highlighted by Relevanssi.

Just pass the content you want highlighted through `relevanssi_highlight_terms()` function. The content to highlight is the first parameter, the search query the second. The content with highlights is then returned by the function. Use it like this:

`if (function_exists('relevanssi_highlight_terms')) {
    echo relevanssi_highlight_terms($content, get_search_query());
}
else { echo $content; }`

= Multisite searching =
To search multiple blogs in the same WordPress network, use the `searchblogs` argument. You can add a hidden input field, for example. List the desired blog ids as the value. For example, searchblogs=1,2,3 would search blogs 1, 2, and 3.

The features are very limited in the multiblog search, none of the advanced filtering works, and there'll probably be fairly serious performance issues if searching common words from multiple blogs.

= What is tf * idf weighing? =

It's the basic weighing scheme used in information retrieval. Tf stands for *term frequency* while idf is *inverted document frequency*. Term frequency is simply the number of times the term appears in a document, while document frequency is the number of documents in the database where the term appears.

Thus, the weight of the word for a document increases the more often it appears in the document and the less often it appears in other documents.

= What are stop words? =

Each document database is full of useless words. All the little words that appear in just about every document are completely useless for information retrieval purposes. Basically, their inverted document frequency is really low, so they never have much power in matching. Also, removing those words helps to make the index smaller and searching faster.

== Known issues and To-do's ==
* Known issue: In general, multiple Loops on the search page may cause surprising results. Please make sure the actual search results are the first loop.
* Known issue: Relevanssi doesn't necessarily play nice with plugins that modify the excerpt. If you're having problems, try using relevanssi_the_excerpt() instead of the_excerpt().
* Known issue: When a tag is removed, Relevanssi index isn't updated until the post is indexed again.

== Thanks ==
* Cristian Damm for tag indexing, comment indexing, post/page exclusion and general helpfulness.
* Marcus Dalgren for UTF-8 fixing.
* Warren Tape.
* Mohib Ebrahim for relentless bug hunting.
* John Blackbourn for amazing internal link feature and other fixes.
* John Calahan for extensive 2.0 beta testing.

== Changelog ==
= 2.5.2 =
* Major fix: Makes Relevanssi Premium compatible with WP 4.9.

= 2.5.1 =
* Major fix: Returns the missing stopwords.

= 2.5.0 =
* New feature: You can now edit the read attachment content on the attachment edit pages.
* New feature: It's now possible to exclude image attachments from the index with a simple setting on the indexing settings page.
* New feature: You can now redirect empty searches to a specific page instead of the search results template. You can choose the URL from the Redirect settings page.
* New feature: Relevanssi now has a shiny new Gutenberg sidebar that replaces the old Relevanssi Post Controls metabox when using the block editor.
* New feature: When new attachments are uploaded and Relevanssi is set to automatically read new attachments, that reading is now done as an asynchronous background process, so it won't slow down the media upload.
* New feature: Page builder short codes are now removed in Relevanssi indexing. This should reduce the amount of garbage data indexed for posts in Divi, Avada and other page builder themes.
* Changed behaviour: The `relevanssi_page_builder_shortcodes` filter hook is now applied both in indexing and excerpts, and has a second parameter that will inform you of the current context.
* Minor fix: Post type archive indexing doesn't stop the WP CLI indexing if there are no post type archives to index.
* Minor fix: Did you mean could occasionally cause long delays in searches. This was especially problematic in some object cache situations, when the transient didn't last as long as it should've been. Updating the Did you mean word list, which is a slow process, now happens asynchronously.
* Minor fix: Stopwords weren't case insensitive like they should. They are now. Also, stopwords are no longer stored in the `wp_relevanssi_stopwords` database table, but are now stored in the `relevanssi_stopwords` option.
* Minor fix: A comma at the end of the custom field indexing setting made Relevanssi index all custom fields. This doesn't happen anymore and trailing commas are automatically removed, too.
* Minor fix: Post type archive indexing could ran into problems when post types are added or removed. This should fix some of those problems.
* Minor fix: Accessibility improvements all around the admin interface. Screen reader support should be better, feel free to report any further ways to make this better.
* Minor fix: Doing searches without search terms and with the throttle disabled could cause problems. Relevanssi now makes sure throttle is always on when doing termless searches.
* Minor fix: Untokenized search terms are used for building excerpts, which makes highlighting in excerpts work better.

= 2.4.4 =
* New feature: You can now give Gutenberg blocks a CSS class `relevanssi_noindex` to exclude them from being indexed. Relevanssi will not index Gutenberg blocks that have the class.
* New feature: You can now target specific parts of the post with search terms like `{post_tag:cat}`, `{title:word}`, `{author:mikko}`, `{customfield_name:value}` and so on. See [this Knowledge Base entry for more information](https://www.relevanssi.com/knowledge-base/search-targets/).
* New feature: Relevanssi automatically skips some custom fields from common plugins that only contain unnecessary metadata.
* New feature: Related posts keywords can now be restricted by taxonomy, so tags will only match to tags and not other parts of the post. This may lead to increased precision.
* New feature: The search results breakdown is added to the post objects and can be found in $post->relevanssi_hits. The data also includes new fields and the breakdown from the excerpt settings page can now show author, excerpt, custom field and MySQL column hits.
* New feature: Relevanssi can now index Ninja Tables table content. This is something of an experimental feature right now, feedback is welcome.
* New feature: New filter hook `relevanssi_indexing_query` filters the indexing query and is mostly interesting for debugging reasons.
* Minor fix: Deleted and trashed comment contents were not deindexed when the comment was removed. That has been corrected now.
* Minor fix: Phrase matching is now applied to attachments as well, including the attachments indexed for parent post.
* Minor fix: Phrase matching only looks at custom fields that are indexed by Relevanssi.
* Minor fix: Exact match bonus now uses the original query without synonyms added for the exact match check.
* Minor fix: Paid Membership Pro filtering is only applied to published posts to prevent drafts from showing up in the search results.
* Minor fix: Indexing internal links for target documents could cause documents to go unindexed. This has now been fixed. If you use internal link indexing, rebuild the index after you update.
* Minor fix: Relevanssi could stop plugin information retrieval for other plugins fail. This has been fixed.

= 2.4.3 =
* Major fix: Disabling `update_post_metadata_cache` seemed like a good optimization move for related posts, but it turns out it disables related posts thumbnails. We'll take weaker performance with working images.
* Major fix: Importing options caused WordPress to crash, because related posts and redirect settings were handled incorrectly in the import.
* Minor fix: The Polylang compatibility filter didn't return correct post objects if fields was set to `ids` or `id=>parent`. Now the filter function returns correct type of result.
* Minor fix: Enabling the related posts checkbox did not activate the number of months setting.

= 2.4.2 =
* New feature: New filter hook `relevanssi_indexing_adjust` can be used to stop Relevanssi from adjusting the number of posts indexed at once during the indexing.
* New feature: New filter hook `relevanssi_acf_field_value` filters ACF field values before they are indexed.
* New feature: New filter hook `relevanssi_disabled_shortcodes` filters the array containing shortcodes that are disabled when indexing.
* Removed feature: The `relevanssi_indexing_limit` option wasn't really used anymore, so it has been removed.
* Changed behaviour: Indexing exclusions from Relevanssi settings, Yoast SEO and SEOPress are applied in a different way in the indexing, making for a smoother indexing process.
* Changed behaviour: WP Table Reloaded support has been removed; you really shouldn't be using WP Table Reloaded anymore.
* Changed behaviour: Related posts doesn't even try doing an AND search anymore, as most of the time it was a waste of time. If you need that, using the `relevanssi_related_args` filter hook to swap the operator is still possible.
* Major fix: Related posts generation performance has been improved.
* Major fix: Related posts didn't work if multisite searching was enabled. That error has been eliminated. The related posts will come from the same subsite as the original post.
* Minor fix: Relevanssi won't choke on ACF fields with array or object values anymore.
* Minor fix: While you could set the Related posts to show random posts from the same category, the setting wouldn't appear correctly on the settings page. That has been fixed.
* Minor fix: The settings export now includes couple of missing parts, like the related posts settings.
* Minor fix: Relevanssi uninstall process left couple of Relevanssi options in the database.
* Minor fix: WPML language filter didn't work when `fields` was set to `ids` or `id=>parent`.

= 2.4.1 =
* New feature: SEOPress support, posts marked "noindex" in SEOPress are no longer indexed by Relevanssi by default.
* Removed feature: Multi-taxonomy restrictions with `&taxonomy=post_tag|category&term=tag_term|cat_term` format has stopped working, apparently long time ago. Looks like nobody missed it.
* Changed behaviour: Membership plugin compatibility is removed from `relevanssi_default_post_ok` function and has been moved to individual compatibility functions for each supported membership plugin. This makes it much easier to for example disable the membership plugin features if required.
* Minor fix: The `searchform` shortcode now works better with different kinds of search forms.
* Minor fix: Yoast SEO compatibility won't block indexing of posts with explicitly allowed indexing.
* Minor fix: The `relevanssi_the_tags()` function printed out plain text, not HTML code like it should. The function now also accepts the post ID as a parameter.
* Minor fix: Excerpt creation and highlighting have been improved a little.

= 2.4.0 =
* New feature: Multi-phrase searches now respect AND and OR operators. If multiple phrases are included in an OR search, any posts with at least one phrase will be included. In AND search, all phrases must be included.
* New feature: Admin search has been improved: there's a post type dropdown and the search is triggered when you press enter. The debug information has a `div` tag around it with the id `debugging`, so you can hide them with CSS if you want to. The numbering of results also makes more sense.
* New feature: The date parameters (`year`, `monthnum`, `w`, `day`, `hour`, `minute`, `second`, `m`) are now supported.
* New feature: New filter hook `relevanssi_file_content` filters the file content before it's saved in the `_relevanssi_pdf_content` custom field.
* New feature: New filter hook `relevanssi_related_args` filters the related posts search arguments.
* New feature: You can now set a month restriction to show only recent posts in the related posts. For more fine-grained date control, use the `relevanssi_related_args` filter hook.
* New feature: Instead of fully random posts, you can choose to get random posts from the same category if no proper matches are found for related posts.
* New feature: New filter hook `relevanssi_indexing_limit` filters the default number of posts to index (10). If you have issues with indexing timing out, you can try adjusting this to a smaller number like 5 or 1.
* New feature: Support for Paid Membership Pro added.
* New feature: WordPress SEO support, posts marked "noindex" in WordPress SEO are no longer indexed by Relevanssi by default.
* Removed feature: qTranslate is no longer supported.
* Major fix: Updates work when API key is in single site settings in a multisite environment. In 2.3.0, it is required that the key is in the multisite settings, otherwise updates won't work in multisite.
* Major fix: Tax query searching had some bugs in it, manifesting especially into Polylang not limiting the languages correctly. Some problems with the test suites were found and fixed, and similar problems won't happen again.
* Minor fix: Admin search only shows pinning and editing options to users with enough capabilities to use them.
* Minor fix: Phrase searching now uses filterable post statuses instead of a hard-coded set of post statuses.
* Minor fix: When saving an API key on a single site in multisite environment, the key appears to be saved. (In 2.3.0 it was saved, but it didn't look like it.)
* Minor fix: The plugin action links were missing on the Plugins page list, they're back now.
* Minor fix: Synonym indexing has been fixed, it could prevent saving of posts when synonym list was empty.
* Minor fix: Setting the `post_type` parameter to `post_type` to get post type archive pages now works.
* Minor fix: In some cases, Relevanssi might complain when uploading files.
* Minor fix: Missing action links were returned to the plugins list.
* Minor fix: Wrinkles in the upgrade process from free to Premium were ironed out.
* Minor fix: Search terms with slashes won't cause errors anymore.
* Minor fix: Relevanssi admin pages have been examined for accessibility and form labels have been improved in many places.
* Deprecated: `relevanssi_get_term_taxonomy()` function is deprecated and will be removed at some point in the future.

= 2.3.0 =
* New feature: Content stopwords are just like regular stopwords, but they are only applied to post content. They are not applied to titles, custom fields or other places.
* New feature: The search form shortcode has a new parameter `dropdown` which can be used to add a category dropdown, like this: `[searchform dropdown="category"]`.
* New feature: Relevanssi can now use the contents of the PDF files indexed with WP File Download.
* New feature: Related posts can now be used in a different way. Instead of storing rendered HTML in the transients, Relevanssi can now store just the post objects for the related posts.
* New feature: Related posts WP CLI command has new parameters: just_objects activates the post object transients, post_type only generates the related posts for a particular post type.
* New feature: You can now pin and unpin posts from the Admin search. Just do a search and you can then easily pin posts for that search term.
* New feature: Relevanssi now supports User Access Manager permission controls.
* New filter: `relevanssi_indexing_tokens` can be used to filter the tokens (individual words) before they are indexed.
* Removed filter: `relevanssi_default_meta_query_relation` did not have any effect anymore.
* Changed behaviour: The default taxonomy relation was set to AND in 2.2.5, but wasn't properly applied before. Now it is really switched.
* Changed behaviour: Relevanssi now uses the `relevanssi_indexing_tokens` filter hook to add synonyms, which means adding lots of synonyms is much faster than before, and synonyms are now applied to all indexed content.
* Changed behaviour: New post types have been added to list of forbidden post types Relevanssi won't show as indexing options (ACF, TablePress and WooCommerce).
* Changed behaviour: Related posts templates are no longer generated when posts are saved. There are cases where generating the templates in admin context cause problems. The templates will be generated the first time the post is opened.
* Major fix: Tax query processing has been completely refactored, eliminating all sorts of bugs, especially with various edge cases.
* Major fix: Gutenberg block indexing only worked with the Gutenberg plugin enabled. It now works with WP 5.0 built-in Gutenberg as well. If you use Gutenberg blocks, reindex to get all the block content in the index.
* Major fix: Excerpt-building and highlighting did not respect the "Keyword matching" setting. They do now, and the excerpts should be better now.
* Major fix: AND searches needed queries that could get too long for the database to handle. This has been fixed and optimized.
* Major fix: Post exclusion (negative pinning) didn't work properly in multisite context.
* Major fix: Taxonomy term subquery relations didn't work; now they are applied.
* Major fix: Saving related posts exclusion settings wasn't possible.
* Minor fix: Authors can search for their own private posts.
* Minor fix: API key setting field behaviour has been improved.
* Minor fix: iOS uses curly quotes by default, and that didn't work as a phrase operator. Now phrase operator works with curly quotes and straight quotes.
* Minor fix: The free version Did you mean broke with search terms longer than 255 characters.
* Minor fix: Relevanssi won't create empty pinning meta fields anymore.
* Minor fix: Phrases with numbers and one word like "team 17" didn't work, because numbers weren't counted as words.
* Minor fix: $post->relevanssi_pinned wasn't set correctly for pinned posts.
* Minor fix: Relevanssi handles errors better in multisite searching when you search for a taxonomy that doesn't exist on the current site.

== Upgrade notice ==
= 2.5.2 =
* Relevanssi is once again compatible with WP 4.9.

= 2.5.1 =
* Fixes missing stopwords problem.

= 2.5.0 =
* Improved attachment reading features.

= 2.4.4 =
* Comment indexing fixes, compatibility improvements and new search features.

= 2.4.3 =
* Major bug fixes in related posts and option importing.

= 2.4.2 =
* Bug fixes and performance improvements.

= 2.4.1 =
* Yoast SEO compatibility fix, minor fixes.

= 2.4.0 =
* Major bug fixes and new features.

= 2.3.0 =
* Several bug fixes and new features.