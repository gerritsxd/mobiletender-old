import $ from 'jquery';

/**
 * Waiter table selection: instant filter over the table tiles.
 */
$(function () {
    const $filter = $('#table-filter');
    if (!$filter.length) {
        return;
    }

    $filter.on('input', function () {
        const query = String($(this).val() || '').trim().toLowerCase();
        let visible = 0;
        $('#tables-grid .table-tile').each(function () {
            const match = query === '' || String($(this).data('table-name')).indexOf(query) !== -1;
            $(this).toggleClass('hidden', !match);
            if (match) {
                visible++;
            }
        });
        $('#tables-no-match').toggleClass('hidden', visible > 0);
    });
});
