jQuery(function ($) {
    var $groups = $('.groups li'),
        $capabilities = $('.capabilities li');

    // Hide all capabilities.
    $capabilities.hide();

    // Display only the general capabilities.
    $capabilities.filter('.publishpress').show();

    // Add events to allow dynamic filtering.
    $groups.on('click', function () {
        var $this = $(this);
        // Mark the current group as selected
        $groups.removeClass('selected');
        $this.addClass('selected');

        // Display the related capabilities
        $capabilities.hide();
        $capabilities.filter('.' + $this.data('group')).show();
    });
});
