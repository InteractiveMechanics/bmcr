jQuery(document).ready(function ($) {
    // Advanced Custom Fields compat
    if (typeof acf != 'undefined') {
        if (typeof acf.add_filter == 'function') { // ACF 5 API
            acf.add_filter('validation_complete', function (json, $form) {
                // remove disabled classes
                $('#submitdiv_pps').find('.disabled').removeClass('disabled');
                $('#submitdiv_pps').find('.button-disabled').removeClass('button-disabled');
                $('#submitdiv_pps').find('.button-primary-disabled').removeClass('button-primary-disabled');

                // remove spinner
                $('#submitdiv_pps .spinner').hide();

                return json;
            });
        } else {
            $(document).on('submit', '#post', function () {
                // hide ajax stuff on submit button
                if ($('#submitdiv_pps').exists()) {

                    // remove disabled classes
                    $('#submitdiv_pps').find('.disabled').removeClass('disabled');
                    $('#submitdiv_pps').find('.button-disabled').removeClass('button-disabled');
                    $('#submitdiv_pps').find('.button-primary-disabled').removeClass('button-primary-disabled');

                    // remove spinner
                    $('#submitdiv_pps .spinner').hide();
                }
            });
        }
    }
});

function updateStatusDropdownElements() {
    jQuery(document).ready(function ($) {
        var postStatus = $('#post_status'), optPublish = $('option[value=publish]', postStatus);
        var status_val = $('input:radio:checked', '#post-visibility-select').val();

        var is_private = false;
        var pvt_stati = jQuery.parseJSON(ppObjEdit.pvtStati.replace(/&quot;/g, '"'));

        $(pvt_stati).each(function (i) {
            if (pvt_stati[i].name == status_val) {
                is_private = true;
            }
        });

        if (is_private) {
            $('#publish').val(postL10n.update);

            if (optPublish.length == 0) {
                postStatus.append('<option value="publish">' + postL10n.privatelyPublished + '</option>');
            } else {
                optPublish.html(postL10n.privatelyPublished);
            }

            $('option[value="publish"]', postStatus).prop('selected', true);
            $('.edit-post-status', '#misc-publishing-actions').hide();
        } else {
            $('#publish').val(postL10n.publish);

            if ($('#original_post_status').val() == 'future' || $('#original_post_status').val() == 'draft') {
                if (optPublish.length) {
                    optPublish.remove();
                    postStatus.val($('#hidden_post_status').val());
                }
            } else {
                optPublish.html(postL10n.published);
            }
            if (postStatus.is(':hidden'))
                $('.edit-post-status', '#misc-publishing-actions').show();
        }

        return true;
    });
}

// set "Status:" caption; show/hide Save As button and set caption
function updateStatusCaptions() {
    jQuery(document).ready(function ($) {
        postStatus = $('#post_status');
        var status_val = $('option:selected', postStatus).val();

        $('#post-status-display').html($('option:selected', postStatus).text());

        var status_type = '';
        var save_as = '';
        var pub_stati = jQuery.parseJSON(ppObjEdit.pubStati.replace(/&quot;/g, '"'));
        var pvt_stati = jQuery.parseJSON(ppObjEdit.pvtStati.replace(/&quot;/g, '"'));
        var mod_stati = jQuery.parseJSON(ppObjEdit.modStati.replace(/&quot;/g, '"'));

        $(mod_stati).each(function (i) {
            if (mod_stati[i].name == status_val) {
                status_type = 'moderation';
                save_as = mod_stati[i].save_as;
            }
        });

        $(pub_stati).each(function (i) {
            if (pub_stati[i].name == status_val) {
                status_type = 'public';
            }
        });

        $(pvt_stati).each(function (i) {
            if (pvt_stati[i].name == status_val) {
                status_type = 'private';
            }
        });

        switch (status_type) {
            case 'public':
            case 'private':
                $('#save-post').hide();
                break;

            case 'moderation':
                $('#save-post').show().val(save_as);
                break;

            default :
                $('#save-post').show().val(ppObjEdit.draftSaveAs);
        }
    });
}

jQuery(document).ready(function ($) {
    var stamp = $('#timestamp').html();
    var orig_visibility_html = $('#post-visibility-display').html();

    if (typeof ppObjEdit != 'undefined')
        var pvt_stati = jQuery.parseJSON(ppObjEdit.pvtStati.replace(/&quot;/g, '"'));
    else
        var pvt_stati = [];

    // to retain last OK'd selection:
    //var selected_vis = $('#hidden-post-visibility').val(), selected_password = $('#hidden_post_password').val(), selected_sticky = $('#hidden-post-sticky').is(':checked');

    $('.save-post-status', '#post-status-select').click(function (e) {
        updateStatusCaptions();
        $('#post-status-select').siblings('a.edit-post-status').show();
        e.preventDefault();
        //return false;
    });

    function ppRefreshVisibilityUI() {
        var pvSelect = $('#post-visibility-select');

        if ($('input:radio:checked', pvSelect).val() != 'public') {
            $('#sticky').prop('checked', false);
            $('#sticky-span').hide();
        } else {
            $('#sticky-span').show();
        }

        if ($('input:radio:checked', pvSelect).val() != 'password') {
            $('#password-span').hide();
        } else {
            $('#password-span').show();
        }
    }

    function ppUpdateText() {
        var attemptedDate, originalDate, currentDate, publishOn,
            postStatus = $('#post_status'), optPublish = $('option[value=publish]', postStatus), aa = $('#aa').val(),
            mm = $('#mm').val(), jj = $('#jj').val(), hh = $('#hh').val(), mn = $('#mn').val();

        attemptedDate = new Date(aa, mm - 1, jj, hh, mn);
        originalDate = new Date($('#hidden_aa').val(), $('#hidden_mm').val() - 1, $('#hidden_jj').val(), $('#hidden_hh').val(), $('#hidden_mn').val());
        currentDate = new Date($('#cur_aa').val(), $('#cur_mm').val() - 1, $('#cur_jj').val(), $('#cur_hh').val(), $('#cur_mn').val());

        if (attemptedDate.getFullYear() != aa || (1 + attemptedDate.getMonth()) != mm || attemptedDate.getDate() != jj || attemptedDate.getMinutes() != mn) {
            $('.timestamp-wrap', '#timestampdiv').addClass('form-invalid');
            return false;
        } else {
            $('.timestamp-wrap', '#timestampdiv').removeClass('form-invalid');
        }

        if (attemptedDate > currentDate && $('#original_post_status').val() != 'future') {
            publishOn = postL10n.publishOnFuture;
            $('#publish').val(postL10n.schedule);
        } else if (attemptedDate <= currentDate && $('#original_post_status').val() != 'publish') {
            publishOn = postL10n.publishOn;
            $('#publish').val(postL10n.publish);
        } else {
            publishOn = postL10n.publishOnPast;
            $('#publish').val(postL10n.update);
        }

        if (originalDate.toUTCString() == attemptedDate.toUTCString()) { //hack
            $('#timestamp').html(stamp);
        } else {
            $('#timestamp').html(
                publishOn + ' <b>' +
                $('option[value=' + $('#mm').val() + ']', '#mm').text() + ' ' +
                jj + ', ' +
                aa + ' @ ' +
                hh + ':' +
                mn + '</b> '
            );
        }

        var val = $('input:radio:checked', '#post-visibility-select').val();

        var is_private = false;
        $(pvt_stati).each(function (i) {
            if (pvt_stati[i].name == val) {
                is_private = true;
            }
        });

        if (is_private) {
            if (attemptedDate <= currentDate) {
                $('#publish').val(postL10n.update);
            }

            $('#publish').val(postL10n.update);
            if (optPublish.length == 0) {
                postStatus.append('<option value="publish">' + postL10n.privatelyPublished + '</option>');
            } else {
                optPublish.html(postL10n.privatelyPublished);
            }
            $('option[value="publish"]', postStatus).prop('selected', true);
            $('.edit-post-status', '#misc-publishing-actions').hide();
        } else {
            if ($('#original_post_status').val() == 'future' || $('#original_post_status').val() == 'draft') {
                if (optPublish.length) {
                    optPublish.remove();
                    postStatus.val($('#hidden_post_status').val());
                }
            } else {
                optPublish.html(postL10n.published);
            }
            if (postStatus.is(':hidden'))
                $('.edit-post-status', '#misc-publishing-actions').show();
        }

        return true;
    }

    $('.cancel-post-visibility', '#post-visibility-select').click(function (e) {
        $('#post-visibility-select').slideUp("fast");

        $('#visibility-radio-' + $('#hidden-post-visibility').val()).prop('checked', true);

        $('#post_password').val($('#hidden_post_password').val());

        $('#sticky').prop('checked', $('#hidden_post_sticky').val());

        $('#post-visibility-display').html(orig_visibility_html);

        $('#post_status').val($('#hidden_post_status').val());

        $('.edit-visibility', '#visibility').show();
        $('.visibility-customize').hide();

        ppUpdateText();
        updateStatusCaptions();

        updateStatusDropdownElements();

        e.preventDefault();

        return false;
    });

    $('.save-post-visibility', '#post-visibility-select').click(function (e) {
        var pvSelect = $('#post-visibility-select');

        selected_vis = $('input:radio:checked', pvSelect).val();
        selected_sticky = $('#sticky').is(':checked');
        selected_password = $('#post_password').val();

        pvSelect.slideUp("fast");
        $('.edit-visibility', '#visibility').show();

        if (selected_sticky) {
            var sticky = 'Sticky';
        } else {
            var sticky = '';
        }

        $('#post-visibility-display').html(postL10n[selected_vis + sticky]);
        //$('.visibility-customize').hide();

        updateStatusDropdownElements();
        updateStatusCaptions();

        if ($('#pp-propagate-privacy').is(':checked')) {
            if ('public' == selected_vis)
                selected_vis = '';

            var optid = $('#ch_post-visibility-select input[name="ch_visibility"][value="' + selected_vis + '"]').attr('id');
            if (optid) {
                $('#' + optid).prop('checked', true);
                $('#ch_post-visibility-display').html($('#' + optid).next('label').html());
                $('#ch_visibility').show();
            }
        }

        e.preventDefault();

        return false;
    });

    $('.ch_edit-visibility', '#ch_visibility').click(function () {
        if ($('#ch_post-visibility-select').is(":hidden")) {
            $('#ch_post-visibility-select').slideDown("fast");
            $(this).hide();
        }
        return false;
    });

    $('.ch_save-post-visibility', '#ch_post-visibility-select').click(function () {
        var pvSelect = $('#ch_post-visibility-select');

        pvSelect.slideUp("fast");
        $('.ch_edit-visibility', '#ch_visibility').show();

        var status = $('input:radio:checked', pvSelect).val();
        if (!status)
            status = '_manual';

        $('#ch_post-visibility-display').html(postL10n[status]);
        return false;
    });

    $('input:radio', '#post-visibility-select').change(function () {
        ppRefreshVisibilityUI();
    });

    $('.pp-edit-ch-visibility').click(function () {
        $('#ch_visibility').show();
        $('.ch_edit-visibility').click();
        return false;
    });

    $('.cancel-timestamp', '#timestampdiv').click(function () {
        $('#timestampdiv').slideUp("fast");
        $('#mm').val($('#hidden_mm').val());
        $('#jj').val($('#hidden_jj').val());
        $('#aa').val($('#hidden_aa').val());
        $('#hh').val($('#hidden_hh').val());
        $('#mn').val($('#hidden_mn').val());
        $('#timestampdiv').siblings('a.edit-timestamp').show();
        ppUpdateText();
        return false;
    });

    $('.save-timestamp', '#timestampdiv').click(function () { // crazyhorse - multiple ok cancels
        if (ppUpdateText()) {
            $('#timestampdiv').slideUp("fast");
            $('#timestampdiv').siblings('a.edit-timestamp').show();
        }
        return false;
    });

    if (!$('#timestampdiv a.now-timestamp').length) {
        $('#timestampdiv a.cancel-timestamp').after('<a href="#timestamp_now" class="now-timestamp now-pp-statuses hide-if-no-js button-now">' + ppObjEdit.nowCaption + '</a>');
    }

    $('#timestampdiv a.now-pp-statuses').click(function () {
        var nowDate = new Date();
        var month = nowDate.getMonth() + 1;
        if (month.toString().length < 2) {
            month = '0' + month;
        }
        $('#mm').val(month);
        $('#jj').val(nowDate.getDate());
        $('#aa').val(nowDate.getFullYear());
        $('#hh').val(nowDate.getHours());

        var minutes = nowDate.getMinutes();
        if (minutes.toString().length < 2) {
            minutes = '0' + minutes;
        }
        $('#mn').val(minutes);
    });

    $('.save-post-status', '#post-status-select').click(function () {
        $('#post-status-select').slideUp("fast");
        $('#post-status-select').siblings('a.edit-post-status').show();
        return false;
    });

    $('.cancel-post-status', '#post-status-select').click(function (e) {
        $('#post-status-select').slideUp("fast");

        $('#post_status option').removeAttr('selected');
        $('#post_status option[value="' + $('#hidden_post_status').val() + '"]').attr('selected', 'selected');

        $('#post-status-select').siblings('a.edit-post-status').show();
        updateStatusCaptions();

        return false;
    });

    $('.edit-visibility', '#visibility').click(function () {
        if ($('#post-visibility-select').is(":hidden")) {
            ppRefreshVisibilityUI();
            $('#post-visibility-select').slideDown('fast');
            $(this).hide();
        }
        return false;
    });

    $('#timestampdiv').siblings('a.edit-timestamp').click(function () {
        if ($('#timestampdiv').is(":hidden")) {
            $('#timestampdiv').slideDown('fast');
            $(this).hide();
        }
        return false;
    });

    $('#post-status-select').siblings('a.edit-post-status').click(function () {
        if ($('#post-status-select').is(":hidden")) {
            $('#post-status-select').slideDown('fast');
            $(this).hide();
        }
        return false;
    });

    $('input[name="ch_visibility"]').click(function () {
        $('input[name="pp_propagate_visibility"]').parent().toggle($(this).val() != '');
    });
});