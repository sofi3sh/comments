const SEARCH_URL = window.SEARCH_URL;

let debounceTimer = null;
let currentRequestId = 0;
let currentFilter = 'all';

async function load(page = 1) {
    const q            = document.getElementById('search_input')?.value?.trim() || '';
    const sortOrder    = document.getElementById('sort_order')?.value || 'desc';
    const resultsEl    = document.getElementById('results');
    const paginationEl = document.getElementById('pagination');
    const titleEl      = document.getElementById('search_title');
    const filterEl     = document.getElementById('search_filter');
    const searchForm   = document.getElementById('search_form');

    if (!searchForm) return;

    const tr = {
        min: searchForm.dataset.min,
        error: searchForm.dataset.error,
    };

    if (filterEl) {
        filterEl.classList.remove('search__filter--hidden');
    }

    if (!resultsEl || !paginationEl) return;

    if (q.length < 2) {
        resultsEl.innerHTML = `<p>${tr.min}</p>`;
        paginationEl.innerHTML = '';
        return;
    }

    const params = new URLSearchParams({
        q,
        page,
        sort: sortOrder,
        filter: currentFilter,
    });

    const requestId = ++currentRequestId;

    try {
        const res = await fetch(`${SEARCH_URL}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!res.ok) throw new Error();

        const data = await res.json();

        if (requestId !== currentRequestId) return;

        // ===== TITLE =====
        if (titleEl) {
            const queryEls = titleEl.querySelectorAll('.search__title-query');
            const resultsBlock = titleEl.querySelector('.search__title-text--results');
            const emptyBlock = titleEl.querySelector('.search__title-text--empty');
            const metaEl = titleEl.querySelector('.search__title-meta');

            const t = {
                all: titleEl.dataset.all,
                recent: titleEl.dataset.recent,
            };

            queryEls.forEach(el => el.textContent = q);

            if (!data.data || data.data.length === 0) {
                resultsBlock.style.display = 'none';
                emptyBlock.style.display = 'block';
            } else {
                const filterLabel = currentFilter === 'recent' ? t.recent : t.all;

                if (metaEl) {
                    metaEl.textContent = `(${filterLabel}, ${data.total})`;
                }

                resultsBlock.style.display = 'block';
                emptyBlock.style.display = 'none';
            }
        }

        // ===== RESULTS =====
        resultsEl.innerHTML = (data.data || [])
            .map(item => item.html)
            .join('');

        // ===== PAGINATION =====
        const currentPage = data.current_page || 1;
        const lastPage = data.last_page || 1;

        let paginationHtml = '';

        if (lastPage > 1) {
            if (currentPage > 1) {
                paginationHtml += '<button type="button" data-page="1">«</button>';
                paginationHtml += `<button type="button" data-page="${currentPage - 1}">‹</button>`;
            }

            const maxPages = 10;
            const start = Math.max(1, currentPage - 5);
            const end = Math.min(lastPage, start + maxPages - 1);

            for (let i = start; i <= end; i++) {
                paginationHtml += `<button type="button" data-page="${i}" ${i === currentPage ? 'disabled' : ''}>${i}</button>`;
            }

            if (currentPage < lastPage) {
                paginationHtml += `<button type="button" data-page="${currentPage + 1}">›</button>`;
                paginationHtml += `<button type="button" data-page="${lastPage}">»</button>`;
            }
        }

        paginationEl.innerHTML = paginationHtml;

    } catch (e) {
        console.error(e);
        resultsEl.innerHTML = `<p>${tr.error}</p>`;
        paginationEl.innerHTML = '';
    }
}

function debounceLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => load(1), 300);
}


document.addEventListener('DOMContentLoaded', function() {

    const searchButton = document.querySelector('.header__main-search');
    const searchIconOpen = document.querySelector('.header__main-search-icon');
    const searchIconClose = document.querySelector('.header__main-search-icon-close');
    const searchContainer = document.querySelector('.search');
    const searchOverlay = document.querySelector('.search__overlay');

    const searchInput = document.getElementById('search_input');
    const sortOrder   = document.getElementById('sort_order');

    function openSearch() {
        searchIconOpen.classList.replace('header__main-search-icon--visible', 'header__main-search-icon--hide');
        searchIconClose.classList.replace('header__main-search-icon-close--hide', 'header__main-search-icon-close--visible');
        searchContainer.classList.replace('search--hide', 'search--show');
        document.body.style.overflow = 'hidden';
    }

    function closeSearch() {
        searchIconOpen.classList.replace('header__main-search-icon--hide', 'header__main-search-icon--visible');
        searchIconClose.classList.replace('header__main-search-icon-close--visible', 'header__main-search-icon-close--hide');
        searchContainer.classList.replace('search--show', 'search--hide');
        document.body.style.overflow = '';
    }

    if (searchButton) {
        searchButton.addEventListener('click', () => {
            const isOpen = searchContainer.classList.contains('search--show');
            isOpen ? closeSearch() : openSearch();
        });
    }

    if (searchOverlay) {
        searchOverlay.addEventListener('click', closeSearch);
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && searchContainer.classList.contains('search--show')) {
            closeSearch();
        }
    });

    // ===== SEARCH EVENTS =====
    if (searchInput) {
        searchInput.addEventListener('input', debounceLoad);
    }

    if (sortOrder) {
        sortOrder.addEventListener('change', () => load(1));
    }

    document.getElementById('pagination')?.addEventListener('click', (e) => {
        const button = e.target.closest('button[data-page]');
        if (!button || button.disabled) return;

        load(Number(button.dataset.page));
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.search__filter-btn');
        if (!btn) return;

        currentFilter = btn.dataset.filter;

        document.querySelectorAll('.search__filter-btn').forEach(b => {
            b.classList.remove('search__filter-btn--active');
        });

        btn.classList.add('search__filter-btn--active');

        load(1);
    });

});
