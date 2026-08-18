const ARTICLE_VIEWS_COUNTER_SELECTOR = '[data-article-views-counter]';
const initializedCounters = new WeakSet();



// Resolve the CSRF token for POST view registration, preferring the token rendered on the counter.
function csrfToken(counter) {
    return counter.dataset.csrfToken
        || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || '';
}

// Read and normalize the delay before the article view registration request is sent.
function registerDelay(counter) {
    const delay = Number.parseInt(counter.dataset.registerDelay, 10);

    return Number.isFinite(delay) && delay >= 0 ? delay : 0;
}

// Show the current views count only when the API returns a positive value.
function updateViewsCounter(counter, views) {
    if (views > 0) {
        counter.textContent = views;
        counter.hidden = false;
        return;
    }

    counter.hidden = true;
}

// Load the already aggregated views count for one article counter and update its visible state.
async function loadViews(counter) {
    if (!counter.dataset.getUrl) {
        counter.hidden = true;
        return;
    }

    try {
        const response = await fetch(counter.dataset.getUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(await response.text());
        }

        const { views } = await response.json();

        updateViewsCounter(counter, Number(views));
    } catch (error) {
        counter.hidden = true;
        console.error('ARTICLE VIEWS LOAD ERROR:', error);
    }
}

// Schedule the delayed POST request that records a view for the article represented by this counter.
function registerView(counter) {
    if (!counter.dataset.setUrl) {
        return;
    }

    window.setTimeout(() => {
        fetch(counter.dataset.setUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(counter),
                'X-Requested-With': 'XMLHttpRequest',
            },
            keepalive: true,
        }).catch((error) => {
            console.error('ARTICLE VIEW REGISTER ERROR:', error);
        });
    }, registerDelay(counter));
}

// Collect article view counters inside a document or newly inserted AJAX fragment.
function articleViewCounters(container) {
    const counters = Array.from(container.querySelectorAll(ARTICLE_VIEWS_COUNTER_SELECTOR));

    if (container.matches?.(ARTICLE_VIEWS_COUNTER_SELECTOR)) {
        counters.unshift(container);
    }

    return counters;
}

// Initialize all unprocessed counters in the given container; used on page load and after scroll AJAX inserts.
export function initArticleViews(container = document) {
    articleViewCounters(container).forEach((counter) => {
        if (initializedCounters.has(counter)) {
            return;
        }

        initializedCounters.add(counter);
        void loadViews(counter);
        registerView(counter);
    });
}

document.addEventListener('DOMContentLoaded', () => initArticleViews(document));

/**
 * ============================================================================
 * Article Views Counter
 * ============================================================================
 *
 * Алгоритм работы:
 *
 * 1. После загрузки страницы (DOMContentLoaded) вызывается initArticleViews().
 *
 * 2. Функция ищет все элементы с атрибутом:
 *      data-article-views-counter
 *
 * 3. Каждый найденный счетчик инициализируется только один раз.
 *    Для этого используется WeakSet initializedCounters, который защищает
 *    от повторной обработки уже существующих элементов (например при
 *    бесконечном скролле или повторном вызове initArticleViews()).
 *
 * 4. Для каждого нового счетчика:
 *      • выполняется GET-запрос для получения текущего количества просмотров;
 *      • отображается полученное значение (или счетчик скрывается, если
 *        просмотров нет);
 *      • через заданную задержку отправляется POST-запрос для регистрации
 *        нового просмотра.
 *
 * 5. Все параметры работы (URL запросов, CSRF, задержка регистрации и т.д.)
 *    берутся из data-* атрибутов конкретного элемента, поэтому модуль может
 *    одновременно обслуживать любое количество статей на странице.
 *
 * 6. После динамической подгрузки статьи (Ajax, Infinite Scroll и т.п.)
 *    достаточно вызвать:
 *
 *        initArticleViews(newArticleElement);
 *
 *    Будут обработаны только новые счетчики, уже инициализированные элементы
 *    повторно обрабатываться не будут.
 * ============================================================================
 */