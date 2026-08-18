function appendContent(articleContentEl, content) {
    if (content.trim() === '') {
        return;
    }

    articleContentEl.insertAdjacentHTML('beforeend', content);
}

function privatePlaceholder(articleContentEl) {
    return articleContentEl
        .closest('.article-container')
        ?.querySelector('[data-article-private-placeholder]');
}

function showPrivatePlaceholder(articleContentEl) {
    privatePlaceholder(articleContentEl)?.removeAttribute('hidden');
}

function removePrivatePlaceholder(articleContentEl) {
    privatePlaceholder(articleContentEl)?.remove();
}

async function fetchStaticRestContent(articleContentEl) {
    const response = await fetch(articleContentEl.dataset.restUrl);

    if (!response.ok) {
        const error = new Error(await response.text());
        error.status = response.status;
        throw error;
    }

    return response.text();
}

async function loadArticleRestContent(articleContentEl) {
    try {
        appendContent(articleContentEl, await fetchStaticRestContent(articleContentEl));
        removePrivatePlaceholder(articleContentEl);
    } catch (error) {
       if (error.status === 401 || error.status === 403) {
           showPrivatePlaceholder(articleContentEl);
           return;
       }

       console.error('FETCH ERROR:', error);
    }
}

export function initArticleRestContent(container = document) {
    container
        .querySelectorAll('[data-article-rest="1"]')
        .forEach(loadArticleRestContent);
}

document.addEventListener('DOMContentLoaded', () => {
    initArticleRestContent();
});
