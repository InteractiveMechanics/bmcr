jQuery(document).ready(function ($) {
    var presspermitHideSpinners = function () {
        $("#pp-install .waiting").hide();
        $("#pp-install .button-secondary").prop('disabled', false);
        $("#pp-install div.pp-key-hint span").hide();
    }

    var presspermitRedrawActStatus = function (data, txtStatus) {
        presspermitHideSpinners();

        var msg = '';
        var captions = jQuery.parseJSON(ppSettings.keyStatus.replace(/&quot;/g, '"'));

        if (typeof data != 'object' || typeof data['license'] == 'undefined') {
            msg = ppSettings.errCaption;
            $("#pp-install .pp-key-active").hide();
            $("#pp-install .pp-key-expired").hide();
        } else if (!jQuery.inArray(data['license'], captions)) {
            msg = ppSettings.errCaption;
        } else {
            msg = captions[data['license']];

            if (('valid' == data['license'])) {
                ppSettings.activated = 1;
                $("#pp-install #activation-button").html(ppSettings.deactivateCaption);
                $("#pp-install #renewal-button").hide();
                $("#pp-install #edd_key").hide();
                $("#pp-install .pp-key-inactive").hide();
                $("#pp-install .pp-key-active").show();
                $("#pp-install .pp-key-expired").hide();
                $("#pp-install .pp-update-link").show();
            } else if ('expired' == data['license']) {
                ppSettings.activated = 1;
                ppSettings.expired = 1;
                $("#pp-install #activation-button").html(ppSettings.deactivateCaption);
                $("#pp-install #renewal-button").show();
                $("#pp-install #edd_key").show();
                $("#pp-install .pp-key-active").hide();
                $("#pp-install .pp-key-expired").show();
                $("#pp-install .pp-update-link").show();
                $("#pp-install .pp-key-inactive").show();
            } else {
                ppSettings.activated = 0;
                $("#pp-install #activation-button").html(ppSettings.activateCaption);
                $("#pp-install #edd_key").show();
                $("#pp-install #edd_key").val('');
                $("span.pp-key-active").hide();
                $("span.pp-key-expired").hide();
                $("span.pp-key-warning").hide();
                $("span.pp-update-link").hide();
                $("#pp-install .pp-key-inactive").show();
            }
        }

        $("#pp-install #activation-status").html(msg).show();

        if ('valid' == data['license'])
            $("#pp-install #activation-reload").show();
    }

    var presspermitAjaxConnectFailure = function (data, txtStatus) {
        presspermitHideSpinners();
        $("#pp-install #activation-status").html(ppSettings.noConnectCaption);
        return;
    }

    // click handlers for activate / deactivate button
    $('#pp-install_table #activation-button').bind('click', function (e) {
        $(this).closest('td').find('.waiting').show();
        $(this).prop('disabled', true);

        e.preventDefault();
        e.stopPropagation();

        if (1 == ppSettings.activated) {
            var data = {'pp_ajax_settings': 'deactivate_key'};
            $.ajax({
                url: ppSettings.deactivateURL,
                data: data,
                dataType: "json",
                cache: false,
                success: presspermitRedrawActStatus,
                error: presspermitAjaxConnectFailure
            });
        } else {
            var key = jQuery.trim($("#pp-install #edd_key").val());

            if (!key) {
                $("#pp-install #activation-status").html(ppSettings.noEntryCaption);
                presspermitHideSpinners();
                return;
            }

            var data = {'pp_ajax_settings': 'activate_key', 'key': key};
            $.ajax({
                url: ppSettings.activateURL,
                data: data,
                dataType: "json",
                cache: false,
                success: presspermitRedrawActStatus,
                error: presspermitAjaxConnectFailure
            });
        }
    });

    $('#pp-install_table #renewal-button').bind('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var data = {'pp_ajax_settings': 'refresh_version'};
        $.ajax({
            url: ppSettings.renewURL,
            data: data,
            dataType: "json",
            success: presspermitRedrawActStatus,
            error: presspermitAjaxConnectFailure
        });
    });

    var presspermitNoFunc = function (data, txtStatus) {
        return;
    }

    $('input[name="pp_submit"]').bind('click', function (e) {
        if ($('a.pp-install').closest('li').hasClass("agp-selected_agent")) {
            $('#pp_support_waiting').show();
            var data = {'pp_ajax_settings': 'refresh_version'};
            $.ajax({
                url: ppSettings.refreshURL,
                data: data,
                async: false,
                dataType: "json",
                cache: false,
                success: presspermitNoFunc,
                error: presspermitNoFunc
            });
            presspermitHideSpinners();
        }
    });

    $('#pp_support_data_all').click(function (e) {
        $('#pp-install_table div.support_data input[disabled!="disabled"]').prop('checked', $(this).is(':checked'));
    });

    $('div.support_data input[type="checkbox"]').change(function () {
        $('li.upload-config a, li.pp-support-forum a').bind('click', false).css('color', '#777').css('text-decoration', 'none');
        $('li.upload-config a').html(ppSettings.supportOptChanged);
    });
});