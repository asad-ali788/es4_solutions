let itrToastContainer = null;

function getItrToastContainer() {
    if (!itrToastContainer) {
        itrToastContainer = document.createElement("div");
        itrToastContainer.className = "itr-toast-container";
        document.body.appendChild(itrToastContainer);
    }
    return itrToastContainer;
}

function showToast(type, message, delay = 6000) {
    const container = getItrToastContainer();

    const icons = {
        success: "bx bxs-check-circle text-success",
        error: "bx bxs-x-circle text-danger",
        warning: "bx bxs-error text-warning",
        info: "bx bxs-info-circle text-info"
    };

    const titles = {
        success: "Success",
        error: "Error",
        warning: "Warning",
        info: "Info"
    };

    const selectedIcon = icons[type] || icons.info;
    const selectedTitle = titles[type] || titles.info;

    const toastEl = document.createElement("div");
    toastEl.className = "itr-toast shadow";

    toastEl.innerHTML = `
        <div class="d-flex align-items-start">
            <i class="${selectedIcon} fs-4 me-2"></i>
            <div class="flex-grow-1">
                <strong>${selectedTitle}</strong><br>
                ${message}
            </div>
            <button type="button" class="btn-close ms-3"></button>
        </div>
    `;

    container.appendChild(toastEl);

    // animate in
    requestAnimationFrame(() => {
        toastEl.classList.add("show");
    });

    // hide function
    const hide = () => {
        toastEl.classList.remove("show");
        toastEl.classList.add("hide");
        toastEl.addEventListener("transitionend", () => {
            toastEl.remove();
        }, { once: true });
    };

    // close button
    toastEl.querySelector(".btn-close").addEventListener("click", hide);

    // auto close
    if (delay > 0) {
        setTimeout(hide, delay);
    }
}

// // Disable all submit buttons inside the submitted form
// $(document).ready(function () {
//     $('form').on('submit', function () {
//         $(this).find('button[type="submit"]').prop('disabled', true);
//     });
// });

// PSD Time in Dashboard
function updatePSTClock() {
    const options = {
        timeZone: 'America/Los_Angeles', // PST/PDT timezone
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    };
    const dateOptions = {
        timeZone: 'America/Los_Angeles',
        weekday: 'short', // Wed
        month: 'short',    // April
        day: 'numeric',   // 23
    };

    const now = new Date();
    const pstTime = now.toLocaleTimeString('en-US', options);
    const pstDate = now.toLocaleDateString('en-US', dateOptions);
    // Update all PST time elements
    document.querySelectorAll('.pst-clock').forEach(el => {
        el.textContent = `${pstTime}`;
    });

    // Update all PST date elements
    document.querySelectorAll('.pst-date').forEach(el => {
        el.textContent = pstDate;
    });


}

// Update every second
setInterval(updatePSTClock, 1000);
updatePSTClock(); // initial call


function toggleClearButton(name) {
    const input = document.getElementById('search-' + name);
    const clearBtn = document.getElementById('clear-' + name);
    clearBtn.style.display = input.value.trim() ? 'block' : 'none';
}

function clearSearch(name) {
    const input = document.getElementById('search-' + name);
    input.value = '';
    input.form.submit(); // optional — removes `search=` param and refreshes table
    toggleClearButton(name);
}

// Initialize state on page load
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[id^="search-"]').forEach(input => {
        toggleClearButton(input.name);
    });
});

// Animate the currency and value

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".count-animate").forEach(counter => {
        const raw = counter.getAttribute("data-target");
        const prefix = counter.getAttribute("data-prefix") || "";
        const suffix = counter.getAttribute("data-suffix") || "";
        const target = parseFloat(raw);

        // No animation for invalid values (N/A, empty, null)
        if (isNaN(target)) return;

        const decimals = (raw.split('.')[1] || "").length;
        let count = 0;
        const duration = 900;
        const step = target / (duration / 16);

        const animate = () => {
            count += step;

            if (count >= target) count = target;
            else requestAnimationFrame(animate);

            counter.innerText =
                prefix +
                count.toFixed(decimals)
                    .replace(/\B(?=(\d{3})+(?!\d))/g, ",") +
                suffix;
        };

        animate();
    });
});

// back to top js
document.addEventListener("DOMContentLoaded", function () {
    const backToTopButton = document.getElementById("back-to-top");

    window.addEventListener("scroll", function () {
        if (window.scrollY > 400) {
            backToTopButton.style.display = "block";
        } else {
            backToTopButton.style.display = "none";
        }
    });

    backToTopButton.addEventListener("click", function () {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    });
});

window.copyToClipboard = async function (text, el = null) {
    if (!text) return false;

    try {
        // Try modern clipboard first (most reliable)
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            await navigator.clipboard.writeText(text);
            animateCopy(el);
            return true;
        }
    } catch (e) {
        // will fallback below
        console.warn('Clipboard API failed, falling back...', e);
    }

    // Fallback: execCommand (works on HTTP and older browsers)
    try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        textarea.style.top = '0';
        textarea.style.opacity = '0';

        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();

        const ok = document.execCommand('copy');
        document.body.removeChild(textarea);

        if (ok) {
            animateCopy(el);
            return true;
        }

        throw new Error('document.execCommand("copy") returned false');
    } catch (e) {
        console.error('Copy failed (both methods).', e);
        return false;
    }
};

function animateCopy(el) {
    if (!el) return;

    // store original once (so it works with <i> tags too)
    if (!el.dataset.copyOriginalHtml) {
        el.dataset.copyOriginalHtml = el.innerHTML;
    }

    el.classList.add('text-success', 'copy-pulse');
    el.innerHTML = '<i class="mdi mdi-check"></i>';

    setTimeout(() => {
        el.innerHTML = el.dataset.copyOriginalHtml || '';
        el.classList.remove('text-success', 'copy-pulse');
    }, 900);
}


// Column hide and show global function it stores the value array in the local storage to keep tracl

(function () {
    'use strict';

    const ColumnVisibility = {
        checkboxSelector: 'input[type="checkbox"][data-column-toggle][data-col]',
        applyButtonSelector: '#columnFilterPopupSubmit',
        modalId: 'columnFilterPopupModal',
        storagePrefix: 'cv:',

        tableCellSelector: 'th[data-col], td[data-col]',
        tableHeaderSelector: 'th[data-col]',

        init() {
            this.applyInitialState();
            this.syncCheckboxes();
            this.bindApply();
            this.bindModalSync();
        },

        /* ---------------- Storage ---------------- */

        storageKey() {
            return this.storagePrefix + location.pathname;
        },

        saveToStorage(visibleKeys) {
            try {
                localStorage.setItem(this.storageKey(), JSON.stringify([...visibleKeys]));
            } catch (_) { }
        },

        loadFromStorage() {
            try {
                const raw = localStorage.getItem(this.storageKey());
                if (!raw) return null;

                const parsed = JSON.parse(raw);
                return Array.isArray(parsed) ? new Set(parsed.map(String)) : null;
            } catch (_) {
                return null;
            }
        },

        /* ---------------- Target table ---------------- */

        getTargetTable() {
            return (
                document.querySelector('table:has(th[data-col], td[data-col])') ||
                document.querySelector('table')
            );
        },

        /* ---------------- Discovery (TABLE ONLY) ---------------- */

        tableColumnKeys(table) {
            const keys = new Set();
            const root = table || document;

            root.querySelectorAll(this.tableHeaderSelector).forEach(el => {
                const k = (el.getAttribute('data-col') || '').trim();
                if (k) keys.add(k);
            });

            if (!keys.size) {
                root.querySelectorAll('td[data-col]').forEach(el => {
                    const k = (el.getAttribute('data-col') || '').trim();
                    if (k) keys.add(k);
                });
            }

            return keys;
        },

        representativeEl(colKey) {
            return document.querySelector(
                `th[data-col="${CSS.escape(colKey)}"], td[data-col="${CSS.escape(colKey)}"]`
            );
        },

        /* ---------------- Core Logic ---------------- */

        applyVisibility(table, visibleKeys) {
            const root = table || document;

            root.querySelectorAll(this.tableCellSelector).forEach(el => {
                const key = (el.getAttribute('data-col') || '').trim();
                if (!key) return;

                el.style.display = visibleKeys.has(key) ? '' : 'none';
            });

            this._current = new Set(visibleKeys);
        },

        applyInitialState() {
            const table = this.getTargetTable();
            if (!table) return;

            const tableKeys = this.tableColumnKeys(table);
            let visible = this.loadFromStorage();

            if (!visible && Array.isArray(window.VISIBLE_COLUMNS)) {
                visible = new Set(window.VISIBLE_COLUMNS.map(String));
            }

            if (!visible) return;

            visible = new Set([...visible].filter(v => tableKeys.has(v)));

            this.applyVisibility(table, visible);
            this.saveToStorage(visible);
        },

        currentVisible(table) {
            const t = table || this.getTargetTable();
            if (!t) return new Set();

            const visible = new Set();
            const keys = this.tableColumnKeys(t);

            keys.forEach(col => {
                const el = t.querySelector(
                    `th[data-col="${CSS.escape(col)}"], td[data-col="${CSS.escape(col)}"]`
                );
                if (el && el.style.display !== 'none') {
                    visible.add(col);
                }
            });

            return visible;
        },

        syncCheckboxes() {
            const table = this.getTargetTable();
            if (!table) return;

            const visible = this.currentVisible(table);

            document.querySelectorAll(this.checkboxSelector).forEach(cb => {
                const key = (cb.getAttribute('data-col') || '').trim();
                if (!key) return;

                cb.checked = visible.has(key);
            });
        },

        /* ---------------- Events ---------------- */

        bindApply() {
            const btn = document.querySelector(this.applyButtonSelector);
            if (!btn) return;

            btn.addEventListener('click', () => {
                const table = this.getTargetTable();
                if (!table) return;

                const tableKeys = this.tableColumnKeys(table);
                const visible = new Set();
                // console.log(tableKeys);
                // console.log(this.checkboxSelector);
                
                document.querySelectorAll(this.checkboxSelector).forEach(cb => {
                    const key = (cb.getAttribute('data-col') || '').trim();
                    if (!key || !tableKeys.has(key)) return;

                    if (cb.checked) {
                        visible.add(key);
                    }
                });
                // console.log(visible);
                
                this.applyVisibility(table, visible);
                this.saveToStorage(visible);

                // accessibility-safe close
                btn.blur();
                const modalEl = document.getElementById(this.modalId);
                const modal = bootstrap?.Modal?.getInstance(modalEl);
                modal?.hide();
            });
        },

        bindModalSync() {
            document.addEventListener('shown.bs.modal', e => {
                if (e.target && e.target.id === this.modalId) {
                    this.syncCheckboxes();
                }
            });
        }
    };

    window.ColumnVisibility = ColumnVisibility;

    document.addEventListener('DOMContentLoaded', () => {
        ColumnVisibility.init();
    });
})();
