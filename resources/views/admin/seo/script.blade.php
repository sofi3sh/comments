<script>
if (typeof switchSeoTab === "undefined") {
    function switchSeoTab(tabId, button) {
        const container = button.closest("[data-tab-prefix]");
        const tabPrefix = container ? container.getAttribute("data-tab-prefix") : null;
        const localeKey = tabId.split("-").pop();
        
        const tabWrappers = container ? container.querySelectorAll(".tab-content-wrapper") : document.querySelectorAll(".tab-content-wrapper");
        tabWrappers.forEach(function(content) {
            if (!tabPrefix || content.getAttribute("data-tab-prefix") === tabPrefix) {
                // Use visibility and position instead of display: none to keep file inputs in DOM
                content.style.visibility = "hidden";
                content.style.position = "absolute";
                content.style.left = "-9999px";
                content.style.height = "0";
                content.style.overflow = "hidden";
            }
        });
        
        button.parentElement.querySelectorAll("button").forEach(function(btn) {
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
        }
        
        button.classList.remove("btn-outline-primary");
        button.classList.add("btn-primary", "active");
    }
}
</script>

