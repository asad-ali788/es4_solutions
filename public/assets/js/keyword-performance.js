document.addEventListener("DOMContentLoaded", () => {
    if (typeof $.fn.select2 === 'undefined') {
        console.error("Select2 not loaded!");
        return;
    }

    const cfg = window.KeywordPageConfig || {};
    const filteredAsin = cfg.filteredAsin || [];

    // Init Select2 with AJAX + preloaded data
    const $asinSelect = $('.asin-select');
    $asinSelect.select2({
        placeholder: "Search ASIN",
        minimumInputLength: 0,
        ajax: {
            url: cfg.routes.asinList,
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '', page: params.page || 1 }),
            processResults: (data, params) => ({
                results: data.results,
                pagination: { more: data.pagination?.more }
            })
        },
        width: '100%',
        data: filteredAsin.map(asin => ({ id: asin, text: asin }))
    });

    // Pre-select filtered ASINs if not already in options
    filteredAsin.forEach(asin => {
        if (!$asinSelect.find(`option[value='${asin}']`).length) {
            const newOption = new Option(asin, asin, true, true);
            $asinSelect.append(newOption).trigger('change');
        }
    });
});

// Recommendation generation & polling
function generateRecommendation(id) {
    const cfg = window.KeywordPageConfig;
    const $cell = $(`#rec-${id}`);
    $cell.text("⏳ Generating...").css({ cursor: "default", color: "inherit", textDecoration: "none" }).off("click");

    $.post(cfg.routes.generate.replace(':id', id), { _token: cfg.csrfToken })
        .done(() => pollStatus(id))
        .fail(() => {
            $cell.text("⚠️ Error while generating").css({ cursor: "pointer", color: "blue", textDecoration: "underline" }).on("click", () => generateRecommendation(id));
        });
}

function pollStatus(id, attempt = 1) {
    const cfg = window.KeywordPageConfig;
    const $rec = $(`#rec-${id}`);
    const $bid = $(`#bid-${id}`);

    $.get(cfg.routes.pollStatus.replace(':id', id))
        .done(data => {
            if (data.ai_status === 'done') {
                $rec.text(data.ai_recommendation || '');
                if (data.ai_suggested_bid != null) {
                    $bid.text($.isNumeric(data.ai_suggested_bid)
                        ? `$${parseFloat(data.ai_suggested_bid).toFixed(2)}`
                        : data.ai_suggested_bid);
                }
                $rec.css({ cursor: "default", color: "inherit", textDecoration: "none", pointerEvents: "none" }).off("click");
            } else if (data.ai_status === 'failed') {
                $rec.text("⚠️ Failed, retry after few seconds")
                    .css({ cursor: "pointer", color: "blue", textDecoration: "underline" })
                    .on("click", () => generateRecommendation(id));
            } else if (attempt < 20) {
                setTimeout(() => pollStatus(id, attempt + 1), 2000);
            } else {
                $rec.text("⚠️ Timeout, click to retry")
                    .css({ cursor: "pointer", color: "blue", textDecoration: "underline" })
                    .on("click", () => generateRecommendation(id));
            }
        })
        .fail(() => console.error("Polling error for ID:", id));
}

// Clear filters
function clearFilters() {
    const form = document.getElementById('filterForm');
    const dateInput = form?.querySelector('input[name="date"]');
    const url = new URL(window.location.origin + window.location.pathname);

    url.searchParams.set('search', '');
    url.searchParams.set('country', 'all');
    url.searchParams.set('campaign', 'all');
    url.searchParams.set('date', dateInput?.max || '');

    window.location.href = url.toString();
}

const checkboxes = document.querySelectorAll('.row-checkbox');

// Function to trigger AJAX update for a single keyword
function updateKeywordAjax(checkbox) {
    const keywordId = checkbox.value;
    const runUpdate = checkbox.checked ? 1 : 0;
    const cfg = window.KeywordPageConfig;

    if (!keywordId) return;

    $.post(cfg.routes.runKeywordUpdate, {
        _token: cfg.csrfToken,
        keyword_id: keywordId,
        run_update: runUpdate
    })
        .done(response => {
            if (typeof showToast === 'function') {
                showToast(
                    response.success ? 'success' : 'error',
                    response.message || 'Keyword update completed.'
                );
            }

            // Optional: disable checkbox if status is 'done' after update
            // checkbox.disabled = (response.status === 'done');
        })
        .fail(xhr => {
            console.error("❌ AJAX Error:", xhr.responseText);
            if (typeof showToast === 'function') {
                showToast('error', 'Error updating keyword.');
            }
        });
}

// Attach change event to each checkbox
checkboxes.forEach(cb => {
    cb.addEventListener('change', () => updateKeywordAjax(cb));
});
