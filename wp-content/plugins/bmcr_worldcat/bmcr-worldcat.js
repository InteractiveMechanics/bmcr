$(function() {
    var plugin_url = '//dev.interactivemechanics.com/bmcr/wp-content/plugins/bmcr_worldcat/worldcat-search-api.php?oclc=';
    var print_url = '//dev.interactivemechanics.com/bmcr/wp-content/plugins/bmcr_worldcat/worldcat-search-api-friendly.php?oclc=';
    var $worldcat_oclc_wrapper = $('#worldcat-plugin-hook');

    var html  = '<input type="button" name="worldcat-merge" id="worldcat-merge" class="worldcat-merge button button-secondary" style="margin-right: 10px;" value="Get WorldCat Data">';
        html += '<input type="button" name="worldcat-show-result" id="worldcat-show-result" class="worldcat-show-result button button-secondary" value="Show WorldCat Response">';

    $worldcat_oclc_wrapper.find('.acf-input-wrap').append(html);
    $worldcat_oclc_wrapper.find('.acf-input-wrap input:first-of-type').css({ "width": "50%", "margin-right": "10px" });


    $('.worldcat-merge').on('click', function(){
        var $worldcat_oclc = $(this).prev().val();

        var $worldcat_title = $(this).parents('.acf-fields').find('[data-name="title"] .acf-input textarea');
        var $worldcat_series_title = $(this).parents('.acf-fields').find('[data-name="series_title"] .acf-input input');
        var $worldcat_book_author = $(this).parents('.acf-fields').find('[data-name="book_author"] .acf-input input');
        var $worldcat_publisher = $(this).parents('.acf-fields').find('[data-name="publisher"] .acf-input input');
        var $worldcat_pub_location = $(this).parents('.acf-fields').find('[data-name="pub_location"] .acf-input input');
        var $worldcat_pub_date = $(this).parents('.acf-fields').find('[data-name="pub_date"] .acf-input input');
        var $worldcat_extent = $(this).parents('.acf-fields').find('[data-name="extent"] .acf-input input');
        var $worldcat_lssn = $(this).parents('.acf-fields').find('[data-name="lssn"] .acf-input input');
        var $worldcat_issn = $(this).parents('.acf-fields').find('[data-name="issn"] .acf-input input');
        var $worldcat_isbn = $(this).parents('.acf-fields').find('[data-name="isbn"] .acf-input input');

        if ($worldcat_oclc) {
            if (!$worldcat_title.val() && 
                !$worldcat_series_title.val() && 
                !$worldcat_book_author.val() && 
                !$worldcat_publisher.val() && 
                !$worldcat_pub_location.val() && 
                !$worldcat_extent.val() && 
                !$worldcat_lssn.val() && 
                !$worldcat_issn.val() && 
                !$worldcat_isbn.val()) {
                    getWorldCatValues($(this));
            } else {
                if (confirm("Values already exist for this book. Are you sure you want to override with WorldCat data?")) {
                    getWorldCatValues($(this));
                }
            }
        } else {
            alert("Please enter a valid OCLC number.");
        }
    });

    $('.worldcat-show-result').on('click', function(){
        var $worldcat_oclc = $(this).prev().prev().val();

        if ($worldcat_oclc) {
            showWorldCatResponse($worldcat_oclc);
        } else {
            alert("Please enter a valid OCLC number.");
        }
    });

    function showWorldCatResponse($worldcat_oclc){
        window.open(print_url + $worldcat_oclc, '_blank');
    }

    function getWorldCatValues(that) {
        var $worldcat_oclc = that.prev().val();

        var $worldcat_title = that.parents('.acf-fields').find('[data-name="title"] .acf-input textarea');
        var $worldcat_series_title = that.parents('.acf-fields').find('[data-name="series_title"] .acf-input input');
        var $worldcat_book_author = that.parents('.acf-fields').find('[data-name="book_author"] .acf-input input');
        var $worldcat_publisher = that.parents('.acf-fields').find('[data-name="publisher"] .acf-input input');
        var $worldcat_pub_location = that.parents('.acf-fields').find('[data-name="pub_location"] .acf-input input');
        var $worldcat_pub_date = that.parents('.acf-fields').find('[data-name="pub_date"] .acf-input input');
        var $worldcat_extent = that.parents('.acf-fields').find('[data-name="extent"] .acf-input input');
        var $worldcat_lssn = that.parents('.acf-fields').find('[data-name="lssn"] .acf-input input');
        var $worldcat_issn = that.parents('.acf-fields').find('[data-name="issn"] .acf-input input');
        var $worldcat_isbn = that.parents('.acf-fields').find('[data-name="isbn"] .acf-input input');

        $.ajax({
            url: plugin_url + $worldcat_oclc
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

            var author = 
                ($(xml).find('datafield[tag="100"]:first subfield[code="a"]').text()) + ' ' + 
                ($(xml).find('datafield[tag="100"]:first subfield[code="e"]').text());

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

            var lssn = 
                ($(xml).find('datafield[tag="010"]:first subfield[code="a"]').text());

            var issn = 
                ($(xml).find('datafield[tag="022"]:first subfield[code="a"]').text());

            var isbn = 
                ($(xml).find('datafield[tag="020"]:first subfield[code="a"]').text());

            $worldcat_title.val(title);
            $worldcat_series_title.val(seriestitle);
            $worldcat_book_author.val(author);
            $worldcat_publisher.val(publisher);
            $worldcat_pub_location.val(pub_location);
            $worldcat_pub_date.val(pub_date);
            $worldcat_extent.val(extent);
            $worldcat_lssn.val(isbn);
            $worldcat_issn.val(isbn);
            $worldcat_isbn.val(isbn);
        });
    }
});