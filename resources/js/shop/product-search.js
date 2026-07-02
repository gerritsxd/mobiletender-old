import $ from 'jquery';

/**
 * Smart product search on the order page.
 * Loads the catalog once (/order/catalog.json), then filters instantly on
 * the client: accent-insensitive, multi-word, ranked (name start > word
 * start > substring > category match). No dropdowns — results render as
 * the same product cards with one-tap add.
 */
let catalog = null;
let catalogPromise = null;
let debounceTimer = null;

function normalize(text) {
    return String(text || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '');
}

function loadCatalog() {
    if (catalogPromise) {
        return catalogPromise;
    }
    catalogPromise = $.getJSON('/order/catalog.json').then(function (data) {
        catalog = (data.products || []).map(function (p) {
            return {
                id: p.id,
                name: p.name,
                price: p.price,
                category: p.category,
                nName: normalize(p.name),
                nCategory: normalize(p.category),
            };
        });
        return catalog;
    });
    return catalogPromise;
}

function scoreProduct(product, tokens) {
    let score = 0;
    for (const token of tokens) {
        const inName = product.nName.indexOf(token);
        const inCategory = product.nCategory.indexOf(token);
        if (inName === -1 && inCategory === -1) {
            return -1; // every token must match somewhere
        }
        if (inName === 0) {
            score += 0; // starts the name
        } else if (inName > 0 && product.nName[inName - 1] === ' ') {
            score += 1; // starts a word
        } else if (inName > 0) {
            score += 2; // substring
        } else {
            score += 3; // category-only match
        }
    }
    return score;
}

function renderResults(results, query) {
    const $grid = $('#search-results-grid');
    const $count = $('#search-results-count');
    const $empty = $('#search-no-results');
    $grid.empty();

    if (!results.length) {
        $count.text('');
        $empty.removeClass('hidden');
        return;
    }
    $empty.addClass('hidden');
    $count.text(results.length + ' ' + (results.length === 1 ? 'resultado' : 'resultados') + ' — "' + query + '"');

    results.forEach(function (p) {
        const $li = $('<li class="card-tw product-card card-tw-product flex flex-col gap-2 p-2 sm:flex-row sm:gap-3 sm:p-3"></li>');
        const $img = $('<img class="img-drag h-24 w-full shrink-0 cursor-pointer rounded-lg object-cover sm:h-20 sm:w-20" alt="" loading="lazy">')
            .attr('src', '/dbimage/' + p.id + '.png')
            .attr('alt', p.name)
            .on('error', function () { $(this).css('visibility', 'hidden'); })
            .on('click', function () { window.addProduct(p.id); });
        const $body = $('<div class="flex min-w-0 flex-1 flex-col"></div>');
        $body.append($('<h3 class="text-sm font-semibold leading-tight text-slate-900"></h3>').text(p.name));
        if (p.category) {
            $body.append($('<p class="text-xs text-slate-400"></p>').text(p.category));
        }
        $body.append($('<p class="mt-1 text-base font-bold text-slate-900"></p>').text(Number(p.price).toFixed(2) + '€'));
        const $btn = $('<button type="button" class="btn-primary add-to-cart mt-auto flex-1 text-sm sm:flex-none">Añadir</button>')
            .on('click', function () { window.addProduct(p.id); });
        $body.append($('<div class="flex pt-2"></div>').append($btn));
        $li.append($img).append($body);
        $grid.append($li);
    });
}

function runSearch(rawQuery) {
    const query = rawQuery.trim();
    const $results = $('#search-results');
    const $browse = $('#category-browse');
    const $clear = $('#product-search-clear');

    $clear.toggleClass('hidden', query.length === 0);

    if (query.length < 2) {
        $results.addClass('hidden');
        $browse.removeClass('hidden');
        return;
    }

    loadCatalog().then(function (products) {
        const tokens = normalize(query).split(/\s+/).filter(Boolean);
        const scored = [];
        products.forEach(function (p) {
            const score = scoreProduct(p, tokens);
            if (score >= 0) {
                scored.push([score, p]);
            }
        });
        scored.sort(function (a, b) {
            return a[0] - b[0] || a[1].nName.localeCompare(b[1].nName);
        });
        renderResults(scored.slice(0, 30).map(function (s) { return s[1]; }), query);
        $browse.addClass('hidden');
        $results.removeClass('hidden');
    });
}

$(function () {
    const $input = $('#product-search');
    if (!$input.length) {
        return;
    }

    // Warm the catalog as soon as the user shows intent.
    $input.one('focus', loadCatalog);

    $input.on('input', function () {
        const value = $(this).val();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { runSearch(value); }, 150);
    });

    $('#product-search-clear').on('click', function () {
        $input.val('').trigger('focus');
        runSearch('');
    });

    // ESC clears the search.
    $input.on('keydown', function (e) {
        if (e.key === 'Escape') {
            $input.val('');
            runSearch('');
        }
    });
});
