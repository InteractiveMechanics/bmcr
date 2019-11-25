$(function() {
    var plugin_url = '//dev.interactivemechanics.com/bmcr/wp-content/plugins/bmcr_worldcat/worldcat-search-api.php?oclc=';
    var print_url = '//dev.interactivemechanics.com/bmcr/wp-content/plugins/bmcr_worldcat/worldcat-search-api-friendly.php?oclc=';
    var $worldcat_oclc_wrapper = $('.worldcat-plugin-hook');

    var html  = '<input type="button" name="worldcat-merge" id="worldcat-merge" class="worldcat-merge button button-secondary" style="margin-right: 10px;" value="Get WorldCat Data">';
        html += '<input type="button" name="worldcat-show-result" id="worldcat-show-result" class="worldcat-show-result button button-secondary" value="Show WorldCat Response">';

    $worldcat_oclc_wrapper.each(function(i){
      console.log($(this).find('.acf-input-wrap'));
      $(this).find('.acf-input-wrap').append(html);
    });

    $worldcat_oclc_wrapper.find('.acf-input-wrap input:first-of-type').css({ "width": "50%", "margin-right": "10px" });


    $(document).on('click tap', '.worldcat-merge', function(){
        var $worldcat_isbn = $(this).prev().val();

        var $worldcat_title = $(this).parents('td.acf-fields').find('[data-name="title"] .acf-input textarea');
        var $worldcat_series_title = $(this).parents('td.acf-fields').find('[data-name="series_title"] .acf-input input');
        var $worldcat_book_author_0_full = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="0"] [data-name="author_full_name"] .acf-input input');
        var $worldcat_book_author_0_first = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="0"] [data-name="author_first_name"] .acf-input input');
        var $worldcat_book_author_0_last = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="0"] [data-name="author_last_name"] .acf-input input');
        var $worldcat_book_author_1_full = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="1"] [data-name="author_full_name"] .acf-input input');
        var $worldcat_book_author_1_first = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="1"] [data-name="author_first_name"] .acf-input input');
        var $worldcat_book_author_1_last = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="1"] [data-name="author_last_name"] .acf-input input');
        var $worldcat_book_author_2_full = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="2"] [data-name="author_full_name"] .acf-input input');
        var $worldcat_book_author_2_first = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="2"] [data-name="author_first_name"] .acf-input input');
        var $worldcat_book_author_2_last = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="2"] [data-name="author_last_name"] .acf-input input');
        var $worldcat_book_author_3_full = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="3"] [data-name="author_full_name"] .acf-input input');
        var $worldcat_book_author_3_first = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="3"] [data-name="author_first_name"] .acf-input input');
        var $worldcat_book_author_3_last = $(this).parents('td.acf-fields').find('[data-name="authors"] [data-id="3"] [data-name="author_last_name"] .acf-input input');
        var $worldcat_publisher = $(this).parents('td.acf-fields').find('[data-name="publisher"] .acf-input input');
        var $worldcat_pub_location = $(this).parents('td.acf-fields').find('[data-name="pub_location"] .acf-input input');
        var $worldcat_pub_date = $(this).parents('td.acf-fields').find('[data-name="pub_date"] .acf-input input');
        var $worldcat_extent = $(this).parents('td.acf-fields').find('[data-name="extent"] .acf-input input');
        var $worldcat_lccn = $(this).parents('td.acf-fields').find('[data-name="lccn"] .acf-input input');
        var $worldcat_issn = $(this).parents('td.acf-fields').find('[data-name="issn"] .acf-input input');
        var $worldcat_oclc = $(this).parents('td.acf-fields').find('[data-name="oclc_number"] .acf-input input');
        var $worldcat_language = $(this).parents('td.acf-fields').find('[data-name="language"] .acf-input input');

        if ($worldcat_isbn) {
            if (!$worldcat_title.val() &&
                !$worldcat_series_title.val() &&
                !$worldcat_book_author_0_full.val() &&
                !$worldcat_book_author_0_first.val() &&
                !$worldcat_book_author_0_last.val() &&
                !$worldcat_publisher.val() &&
                !$worldcat_pub_location.val() &&
                !$worldcat_extent.val() &&
                !$worldcat_lccn.val() &&
                !$worldcat_issn.val() &&
                !$worldcat_oclc.val() &&
                !$worldcat_language.val()) {
                    getWorldCatValues($(this));
            } else {
                if (confirm("Values already exist for this title. Are you sure you want to override with WorldCat data?")) {
                    getWorldCatValues($(this));
                }
            }
        } else {
            alert("Please enter a valid ISBN number.");
        }
    });

    $(document).on('click tap', '.worldcat-show-result', function(){
        var $worldcat_oclc = $(this).prev().prev().val();

        if ($worldcat_oclc) {
            showWorldCatResponse($worldcat_oclc);
        } else {
            alert("Please enter a valid ISBN number.");
        }
    });

    function showWorldCatResponse($worldcat_oclc){
        window.open(print_url + $worldcat_oclc, '_blank');
    }

    function getWorldCatValues(that) {

        var $worldcat_isbn = that.prev().val();

        var $worldcat_title = that.parents('td.acf-fields').find('[data-name="title"] .acf-input textarea');
        var $worldcat_series_title = that.parents('td.acf-fields').find('[data-name="series_title"] .acf-input input');
        var $worldcat_book_author_0_full = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="0"] [data-name="author_full_name"] .acf-input input');
        var $worldcat_book_author_0_first = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="0"] [data-name="author_first_name"] .acf-input input');
        var $worldcat_book_author_0_last = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="0"] [data-name="author_last_name"] .acf-input input');
        var $worldcat_book_author_1_full = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="1"] [data-name="author_full_name"] .acf-input input');
        var $worldcat_book_author_1_first = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="1"] [data-name="author_first_name"] .acf-input input');
        var $worldcat_book_author_1_last = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="1"] [data-name="author_last_name"] .acf-input input');
        var $worldcat_book_author_2_full = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="2"] [data-name="author_full_name"] .acf-input input');
        var $worldcat_book_author_2_first = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="2"] [data-name="author_first_name"] .acf-input input');
        var $worldcat_book_author_2_last = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="2"] [data-name="author_last_name"] .acf-input input');
        var $worldcat_book_author_3_full = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="3"] [data-name="author_full_name"] .acf-input input');
        var $worldcat_book_author_3_first = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="3"] [data-name="author_first_name"] .acf-input input');
        var $worldcat_book_author_3_last = that.parents('td.acf-fields').find('[data-name="authors"] [data-id="3"] [data-name="author_last_name"] .acf-input input');
        var $worldcat_publisher = that.parents('td.acf-fields').find('[data-name="publisher"] .acf-input input');
        var $worldcat_pub_location = that.parents('td.acf-fields').find('[data-name="pub_location"] .acf-input input');
        var $worldcat_pub_date = that.parents('td.acf-fields').find('[data-name="pub_date"] .acf-input input');
        var $worldcat_extent = that.parents('td.acf-fields').find('[data-name="extent"] .acf-input input');
        var $worldcat_lccn = that.parents('td.acf-fields').find('[data-name="lccn"] .acf-input input');
        var $worldcat_issn = that.parents('td.acf-fields').find('[data-name="issn"] .acf-input input');
        var $worldcat_oclc = that.parents('td.acf-fields').find('[data-name="oclc_number"] .acf-input input');
        var $worldcat_language = that.parents('td.acf-fields').find('[data-name="language"] .acf-input input');

        $.ajax({
            url: plugin_url + $worldcat_isbn
        }).done(function(data) {
            var xml = $.parseXML(data);

            // concatenate fields
            var title =
                ($(xml).find('datafield[tag="245"]:first subfield[code="a"]').text()) + ' ' +
                ($(xml).find('datafield[tag="245"]:first subfield[code="b"]').text()) + ' ' +
                ($(xml).find('datafield[tag="245"]:first subfield[code="c"]').text());

            var seriestitle =
                ($(xml).find('datafield[tag="490"]:first subfield[code="a"]').text()) + ' ' +
                ($(xml).find('datafield[tag="490"]:first subfield[code="v"]').text());

			// SPLIT THIS


            var author0 =
                ($(xml).find('datafield[tag="100"]:first subfield[code="a"]').text()) + ' ' +
                ($(xml).find('datafield[tag="100"]:first subfield[code="e"]').text());
            //console.log(author0);

            //splits at combinations of commas and spaces
            //0 is last name, 1 is first
            var author_0_name_array = author0.split(/[ ,]+/)
            var author_0_first_name = author_0_name_array[1];
            var author_0_last_name = author_0_name_array[0];
            //reorder for display
            var author_0_full_name = author_0_first_name + ' ' + author_0_last_name;


            //additional authors here

            var author1 =
                ($(xml).find('datafield[tag="700"]:eq(0) subfield[code="a"]').text()) + ' ' +
                ($(xml).find('datafield[tag="700"]:eq(0) subfield[code="e"]').text());
            //console.log(author1);

            //splits at combinations of commas and spaces
            //0 is last name, 1 is first
            var author_1_name_array = author1.split(/[ ,]+/)
            var author_1_first_name = author_1_name_array[1];
            var author_1_last_name = author_1_name_array[0];
            //reorder for display
            var author_1_full_name = author_1_first_name + ' ' + author_1_last_name;

            var author2 =
                ($(xml).find('datafield[tag="700"]:eq(1) subfield[code="a"]').text()) + ' ' +
                ($(xml).find('datafield[tag="700"]:eq(1) subfield[code="e"]').text());
            console.log(author2);

            //splits at combinations of commas and spaces
            //0 is last name, 1 is first
            var author_2_name_array = author2.split(/[ ,]+/)
            var author_2_first_name = author_2_name_array[1];
            var author_2_last_name = author_2_name_array[0];
            //reorder for display
            var author_2_full_name = author_2_first_name + ' ' + author_2_last_name;

            var author3 =
                ($(xml).find('datafield[tag="700"]:eq(2) subfield[code="a"]').text()) + ' ' +
                ($(xml).find('datafield[tag="700"]:eq(2) subfield[code="e"]').text());
            //console.log(author3);

            //splits at combinations of commas and spaces
            //0 is last name, 1 is first
            var author_3_name_array = author3.split(/[ ,]+/)
            var author_3_first_name = author_3_name_array[1];
            var author_3_last_name = author_3_name_array[0];
            //reorder for display
            var author_3_full_name = author_3_first_name + ' ' + author_3_last_name;


            var publisher =
                ($(xml).find('datafield[tag="264"]:first subfield[code="b"]').text());

            var pub_location =
                ($(xml).find('datafield[tag="264"]:first subfield[code="a"]').text());

            var pub_date =
                ($(xml).find('datafield[tag="264"]:first subfield[code="c"]').text());

            var extent =
                ($(xml).find('datafield[tag="300"]:first subfield[code="a"]').text()) + ' ' +
                ($(xml).find('datafield[tag="300"]:first subfield[code="b"]').text()) + ' ' +
                ($(xml).find('datafield[tag="300"]:first subfield[code="c"]').text());

            var lccn =
                ($(xml).find('datafield[tag="010"]:first subfield[code="a"]').text());

            var issn =
                ($(xml).find('datafield[tag="022"]:first subfield[code="a"]').text());

            var oclc =
                ($(xml).find('controlfield[tag="001"]:first').text());

            //
            var languageRaw =
                ($(xml).find('controlfield[tag="008"]:first').text());

            var language = languageRaw.substr(35,3);

            $worldcat_title.val(title);
            $worldcat_series_title.val(seriestitle);
            $worldcat_book_author_0_full.val(author_0_full_name);
            $worldcat_book_author_0_first.val(author_0_first_name);
            $worldcat_book_author_0_last.val(author_0_last_name);
            $worldcat_book_author_1_full.val(author_1_full_name);
            $worldcat_book_author_1_first.val(author_1_first_name);
            $worldcat_book_author_1_last.val(author_1_last_name);
            $worldcat_book_author_2_full.val(author_2_full_name);
            $worldcat_book_author_2_first.val(author_2_first_name);
            $worldcat_book_author_2_last.val(author_2_last_name);
            $worldcat_book_author_3_full.val(author_3_full_name);
            $worldcat_book_author_3_first.val(author_3_first_name);
            $worldcat_book_author_3_last.val(author_3_last_name);
            $worldcat_publisher.val(publisher);
            $worldcat_pub_location.val(pub_location);
            $worldcat_pub_date.val(pub_date);
            $worldcat_extent.val(extent);
            $worldcat_lccn.val(lccn);
            $worldcat_issn.val(issn);
            $worldcat_oclc.val(oclc);
            $worldcat_language.val(language);
        });
    }
});
