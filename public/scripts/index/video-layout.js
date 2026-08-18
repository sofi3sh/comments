function initVideoLayout(container = document) {
    container.querySelectorAll('[data-youtube-thumbnail]').forEach(function (image) {
        if (image.dataset.youtubeThumbnailInitialized === 'true') {
            return;
        }

        image.dataset.youtubeThumbnailInitialized = 'true';
        image.addEventListener('error', function () {
            const fallbackUrl = image.dataset.thumbnailFallback;

            if (!fallbackUrl || image.dataset.thumbnailFallbackApplied === 'true') {
                return;
            }

            image.dataset.thumbnailFallbackApplied = 'true';
            image.src = fallbackUrl;
        });
    });

    container.querySelectorAll('[data-video-player]').forEach(function (player) {
        if (player.dataset.videoPlayerInitialized === 'true') {
            return;
        }

        player.dataset.videoPlayerInitialized = 'true';
        const button = player.querySelector('button');
        const videoUrl = player.dataset.videoUrl;

        if (!button || !videoUrl) {
            return;
        }

        button.addEventListener('click', function () {
            const iframe = document.createElement('iframe');
            iframe.src = videoUrl;
            iframe.title = 'YouTube video player';
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            iframe.referrerPolicy = 'strict-origin-when-cross-origin';
            iframe.allowFullscreen = true;

            player.replaceChildren(iframe);
        }, { once: true });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initVideoLayout();
});

document.addEventListener('article:inserted', function (event) {
    initVideoLayout(event.detail?.container);
});
