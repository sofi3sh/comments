import { initArticleViews } from './article-views';
import { initArticleRestContent } from './article-rest-content';

const LOAD_POINT_SELECTOR = '.load-point';
const READ_MORE_SELECTOR = '[data-related-article-trigger]';
const READ_MORE_BOTTOM_GAP = 10;

// Sync browser title and URL with the article marker currently visible in the reading area.
function updateLocation(point, replace = false) {
    const title = point.dataset.title;
    const url = point.dataset.url;

    if (title) {
        document.title = title;
    }

    if (url && window.location.href !== url) {
        const method = replace ? 'replaceState' : 'pushState';
        window.history[method]({ articleScroll: true }, title || document.title, url);
    }
}

// Register article markers after every AJAX append; WeakSet prevents observing the same marker twice.
function observeLoadPoints(observer, observedPoints, container = document) {
    container.querySelectorAll(LOAD_POINT_SELECTOR).forEach((point) => {
        if (observedPoints.has(point)) {
            return;
        }

        point.style.display = 'block';
        point.style.width = '1px';
        point.style.height = '1px';
        point.style.opacity = '0';
        point.style.pointerEvents = 'none';
        point.setAttribute('aria-hidden', 'true');

        observedPoints.add(point);
        observer.observe(point);
    });
}

// Fetch the ordinary article URL as AJAX; ArticleController returns only the left-column article fragment.
async function fetchRelatedArticle(url) {
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            Accept: 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        throw new Error(await response.text());
    }

    return response.text();
}

function showReadMoreLoading(trigger) {
    trigger.classList.add('article-read-more--loading');
    trigger.setAttribute('aria-busy', 'true');
    trigger.innerHTML = '<span class="article-read-more__spinner" aria-hidden="true"></span>';
}

function restoreReadMore(trigger, html) {
    trigger.classList.remove('article-read-more--loading');
    trigger.removeAttribute('aria-busy');
    trigger.innerHTML = html;
}

// Extract the article from either Laravel's AJAX fragment or a complete static page.
function articleFromResponse(html, url) {
    const responseDocument = new DOMParser().parseFromString(html, 'text/html');
    const responseArticle = responseDocument.querySelector('.article-container');

    if (!responseArticle) {
        throw new Error('Article container not found in response');
    }

    const article = document.importNode(responseArticle, true);

    // Static pages do not include the marker that AJAX fragments receive from Laravel.
    if (!article.querySelector(LOAD_POINT_SELECTOR)) {
        const point = document.createElement('span');
        const heading = article.querySelector('h1');

        point.className = LOAD_POINT_SELECTOR.slice(1);
        point.dataset.title = responseDocument.title || heading?.textContent?.trim() || document.title;
        point.dataset.url = url;
        article.prepend(point);
    }

    return article;
}

// Replace the activated read-more block with a spinner, then with the fetched article.
async function loadRelatedArticle(trigger, state) {
    if (state.loadingTriggers.has(trigger)) {
        return;
    }

    const link = trigger.querySelector('a[href]');

    if (!link) {
        return;
    }

    const url = link.href;
    const initialHtml = trigger.innerHTML;

    state.loadingTriggers.add(trigger);
    showReadMoreLoading(trigger);

    try {
        const html = await fetchRelatedArticle(url);
        const article = articleFromResponse(html, url);

        trigger.replaceWith(article);

        observeLoadPoints(state.loadPointObserver, state.observedPoints, article);
        observeReadMoreBlocks(state.readMoreObserver, state.observedReadMore, article);
        initArticleViews(article);
        initArticleRestContent(article);
    } catch (error) {
        state.loadingTriggers.delete(trigger);
        restoreReadMore(trigger, initialHtml);
        state.readMoreObserver.observe(trigger);
        console.error('RELATED ARTICLE FETCH ERROR:', error);
    }
}

// The debugging link must be fully visible before AJAX starts, with a small gap below it.
function isReadMoreReady(entry) {
    const rect = entry.boundingClientRect;
    const bottomGap = window.innerHeight - rect.bottom;

    return entry.isIntersecting
        && entry.intersectionRatio >= 1
        && rect.top >= 0
        && bottomGap > READ_MORE_BOTTOM_GAP;
}

// Register read-more blocks; each block is a normal link and only becomes AJAX when it is fully visible.
function observeReadMoreBlocks(observer, observedReadMore, container = document) {
    container.querySelectorAll(READ_MORE_SELECTOR).forEach((trigger) => {
        if (observedReadMore.has(trigger)) {
            return;
        }

        observedReadMore.add(trigger);
        observer.observe(trigger);
    });
}

// Wire marker-based history updates and read-more based article loading.
function initArticleScroll() {
    const state = {
        observedPoints: new WeakSet(),
        observedReadMore: new WeakSet(),
        loadingTriggers: new WeakSet(),
        loadPointObserver: null,
        readMoreObserver: null,
    };

    state.loadPointObserver = new IntersectionObserver((entries) => {
        entries
            .filter((entry) => entry.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio)
            .forEach((entry) => updateLocation(entry.target));
    }, {
        root: null,
        rootMargin: '-35% 0px -55% 0px',
        threshold: [0, 0.25, 0.5, 0.75, 1],
    });

    state.readMoreObserver = new IntersectionObserver((entries) => {
        entries
            .filter(isReadMoreReady)
            .forEach((entry) => {
                state.readMoreObserver.unobserve(entry.target);
                void loadRelatedArticle(entry.target, state);
            });
    }, {
        root: null,
        rootMargin: '0px 0px -10px 0px',
        threshold: 1,
    });

    const initialPoint = document.querySelector(LOAD_POINT_SELECTOR);

    if (initialPoint) {
        updateLocation(initialPoint, true);
    }

    observeLoadPoints(state.loadPointObserver, state.observedPoints, document);
    observeReadMoreBlocks(state.readMoreObserver, state.observedReadMore, document);
}

document.addEventListener('DOMContentLoaded', initArticleScroll);
