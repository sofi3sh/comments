<script>
    (() => {
        const fragment = document.currentScript.previousElementSibling;
        const url = fragment?.dataset.fragmentUrl;

        if (!url) {
            return;
        }

        fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then((response) => response.ok ? response.text() : Promise.reject(response))
            .then((content) => {
                if (content.trim() !== '') {
                    fragment.innerHTML = content;
                }
            })
            .catch((error) => console.error('FRAGMENT FETCH ERROR:', error));
    })();
</script>
