import $ from 'jquery';

/**
 * Admin flash offers page: live countdowns on active offers.
 */
$(function () {
    if ($('body').data('page') !== 'admin-flashoffers-index') {
        return;
    }

    $('[data-countdown]').each(function () {
        $(this).data('deadline', Date.now() + Number($(this).attr('data-countdown')) * 1000);
    });

    setInterval(function () {
        $('[data-countdown]').each(function () {
            const $el = $(this);
            const secondsLeft = Math.round(($el.data('deadline') - Date.now()) / 1000);
            if (secondsLeft <= 0) {
                $el.text('00:00:00').removeClass('badge-success').addClass('badge-tw');
                return;
            }
            const h = String(Math.floor(secondsLeft / 3600)).padStart(2, '0');
            const m = String(Math.floor((secondsLeft % 3600) / 60)).padStart(2, '0');
            const s = String(secondsLeft % 60).padStart(2, '0');
            $el.text(h + ':' + m + ':' + s);
        });
    }, 1000);
});
