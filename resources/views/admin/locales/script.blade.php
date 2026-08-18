@vite('resources/js/articles/article-auto-translate.js')
@vite('resources/js/articles/article-content-uniqueness.js')
@vite('resources/js/articles/article-character-counter.js')
<script>
// Switch locale tab function
    if (typeof switchLocaleTab === "undefined") {
        window.switchLocaleTab = function (tabId, button) {
            const container = button.closest("[data-tab-prefix]");
            const tabPrefix = container ? container.getAttribute("data-tab-prefix") : null;

            const tabWrappers = container
                ? container.querySelectorAll(".tab-content-wrapper")
                : document.querySelectorAll(".tab-content-wrapper");

            tabWrappers.forEach(function (content) {
                if (!tabPrefix || content.getAttribute("data-tab-prefix") === tabPrefix) {
                    content.style.visibility = "hidden";
                    content.style.position = "absolute";
                    content.style.left = "-9999px";
                    content.style.height = "0";
                    content.style.overflow = "hidden";
                }
            });

            button.parentElement.querySelectorAll("button").forEach(function (btn) {
                btn.classList.remove("active", "btn-primary");
                btn.classList.add("btn-outline-primary");
            });

            const targetContent = document.getElementById(tabId + "-content");
            if (targetContent) {
                targetContent.style.visibility = "visible";
                targetContent.style.position = "relative";
                targetContent.style.left = "auto";
                targetContent.style.height = "auto";
                targetContent.style.overflow = "visible";
                
                // Update button texts when switching locale
                const selectButtons = targetContent.querySelectorAll('.gallery-select-button');
                selectButtons.forEach(function(btn) {
                    const selectText = btn.getAttribute('data-text-select');
                    const changeText = btn.getAttribute('data-text-change');
                    if (selectText && changeText) {
                        // Check if preview is visible to determine which text to show
                        const previewId = btn.getAttribute('data-preview-id');
                        const preview = previewId ? document.getElementById(previewId) : null;
                        const hasPreview = preview && preview.style.display !== 'none';
                        
                        // Update button text using DOM API
                        const buttonText = btn.querySelector('.gallery-button-text');
                        const buttonIcon = btn.querySelector('i');
                        
                        if (buttonText && buttonIcon) {
                            if (hasPreview) {
                                buttonText.textContent = ' ' + changeText;
                                buttonIcon.className = 'la la-edit';
                            } else {
                                buttonText.textContent = ' ' + selectText;
                                buttonIcon.className = 'la la-image';
                            }
                        }
                    }
                });
                
                setTimeout(function () {
                    if (window.ArticleEditorJs && typeof window.ArticleEditorJs.initContainer === 'function') {
                        window.ArticleEditorJs.initContainer(targetContent);
                    }

                    const contentInput = targetContent.querySelector('input[type="hidden"][name$="[content]"]');
                    if (
                        contentInput
                        && contentInput.value
                        && window.ArticleEditorJs
                        && typeof window.ArticleEditorJs.setData === 'function'
                    ) {
                        window.ArticleEditorJs.setData(contentInput.id, contentInput.value);
                    }
                }, 100);
            }

            button.classList.remove("btn-outline-primary");
            button.classList.add("btn-primary", "active");

            if (container) {
                container.dispatchEvent(new CustomEvent('locale-tab-switched', {
                    bubbles: true,
                    detail: {
                        tabId: tabId,
                        locale: button.getAttribute('data-locale-key'),
                    },
                }));
            }
        };
    }

</script>
