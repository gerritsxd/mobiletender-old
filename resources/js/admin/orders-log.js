import $ from 'jquery';

/**
 * All-orders log with infinite scroll and a station (printer) filter.
 * Pulls /orderslog/feed.json page by page as the sentinel scrolls into view.
 */
const state = {
    page: 1,
    printer: 'all',
    loading: false,
    done: false,
};

function statusBadge(status) {
    const map = {
        pending: 'bg-slate-100 text-slate-600',
        preparing: 'bg-amber-100 text-amber-800',
        ready: 'bg-emerald-100 text-emerald-800',
        delivered: 'bg-slate-100 text-slate-500',
    };
    const cls = map[status.key] || map.pending;
    return $('<span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold ' + cls + '"></span>').text(status.label);
}

function tableLabel(table) {
    const n = parseInt(table, 10);
    if (!isNaN(n) && n > 100) return 'Pedido ' + table;
    return 'Mesa ' + table;
}

function money(n) {
    return Number(n || 0).toFixed(2) + '€';
}

function renderOrder(o) {
    const $row = $('<div class="card-tw"></div>');
    const $body = $('<div class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:gap-4"></div>');

    // Left: when + who + table
    const $meta = $('<div class="flex min-w-0 flex-col sm:w-56 sm:shrink-0"></div>');
    $meta.append($('<p class="text-sm font-semibold text-slate-900"></p>').text(tableLabel(o.table)));
    $meta.append($('<p class="text-xs text-slate-500"></p>')
        .text((o.sent_label || '') + ' · ' + (o.ordered_by || 'Cliente')));
    $body.append($meta);

    // Middle: items
    const $items = $('<div class="min-w-0 flex-1"></div>');
    const $chips = $('<div class="flex flex-wrap gap-1.5"></div>');
    (o.items || []).forEach(function (it) {
        $chips.append(
            $('<span class="inline-flex items-center gap-1 rounded-md bg-slate-50 px-2 py-0.5 text-xs text-slate-700"></span>')
                .append($('<b class="tabular-nums text-slate-900"></b>').text(it.qty + '×'))
                .append(document.createTextNode(' ' + it.name))
        );
    });
    $items.append($chips);
    $body.append($items);

    // Right: total + status
    const $right = $('<div class="flex items-center justify-between gap-3 sm:w-40 sm:shrink-0 sm:justify-end"></div>');
    $right.append(statusBadge(o.status));
    $right.append($('<span class="text-base font-bold tabular-nums text-slate-900"></span>').text(money(o.total)));
    $body.append($right);

    $row.append($body);
    return $row;
}

function loadNext() {
    if (state.loading || state.done) return;
    state.loading = true;
    $('#orderslog-loading').removeClass('hidden');

    $.getJSON('/orderslog/feed.json', { page: state.page, printer: state.printer })
        .done(function (res) {
            const rows = (res && res.data) || [];
            const $list = $('#orderslog-list');
            rows.forEach(function (o) { $list.append(renderOrder(o)); });

            if (state.page === 1 && rows.length === 0) {
                $('#orderslog-empty').removeClass('hidden');
            }

            if (res && res.has_more) {
                state.page = res.next_page;
            } else {
                state.done = true;
                if ($list.children().length > 0) {
                    $('#orderslog-end').removeClass('hidden');
                }
            }
        })
        .always(function () {
            state.loading = false;
            $('#orderslog-loading').addClass('hidden');
        });
}

function reset() {
    state.page = 1;
    state.done = false;
    state.loading = false;
    $('#orderslog-list').empty();
    $('#orderslog-empty').addClass('hidden');
    $('#orderslog-end').addClass('hidden');
    loadNext();
}

$(function () {
    if ($('body').data('page') !== 'admin-orderslog') return;

    // Station filter chips
    $('[data-orderslog-filters]').on('click', '[data-printer]', function () {
        const $btn = $(this);
        $('[data-orderslog-filters] [data-printer]').removeClass('chip-active');
        $btn.addClass('chip-active');
        state.printer = String($btn.data('printer'));
        reset();
    });

    // Infinite scroll
    const sentinel = document.getElementById('orderslog-sentinel');
    if (sentinel && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting) loadNext();
        }, { rootMargin: '400px' });
        io.observe(sentinel);
    } else {
        // Fallback: scroll listener
        $(window).on('scroll', function () {
            if ($(window).scrollTop() + $(window).height() > $(document).height() - 500) loadNext();
        });
    }

    loadNext();
});
