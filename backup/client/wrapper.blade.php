<script>
(function() {
    'use strict';

    var ID = 'darkenate-server-name';

    function isServerPage() {
        return /^\/server\//.test(window.location.pathname);
    }

    function getServerName() {
        var title = document.title;
        if (!title) return null;
        var idx = title.indexOf(' | ');
        return idx > 0 ? title.substring(0, idx) : null;
    }

    function injectServerName() {
        if (!isServerPage()) return;
        if (document.getElementById(ID)) return;

        var name = getServerName();
        if (!name) return;

        var el = document.createElement('h1');
        el.id = ID;
        el.textContent = name;
        el.style.cssText = 'font-family:inherit;font-weight:500;font-size:1.5rem;line-height:1.75rem;color:rgb(250 250 250);margin:0.75rem 0 0.5rem;padding:0 1.5rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';

        var subNav = document.querySelector('[class*="SubNavigation"]');
        if (subNav && subNav.parentNode) {
            subNav.parentNode.insertBefore(el, subNav.nextSibling);
            return;
        }

        var container = document.querySelector('[class*="ContentContainer"]');
        if (container && container.parentNode) {
            container.parentNode.insertBefore(el, container);
        }
    }

    function removeServerName() {
        var el = document.getElementById(ID);
        if (el) el.remove();
    }

    var lastUrl = location.href;
    function onUrlChange() {
        var url = location.href;
        if (url === lastUrl) return;
        lastUrl = url;
        if (isServerPage()) {
            removeServerName();
            setTimeout(injectServerName, 30);
        } else {
            removeServerName();
        }
    }

    window.addEventListener('popstate', onUrlChange);

    var origPushState = history.pushState;
    history.pushState = function() {
        origPushState.apply(this, arguments);
        onUrlChange();
    };

    var origReplaceState = history.replaceState;
    history.replaceState = function() {
        origReplaceState.apply(this, arguments);
        onUrlChange();
    };

    new MutationObserver(function() {
        if (isServerPage() && !document.getElementById(ID)) {
            injectServerName();
        }
    }).observe(document.body, { childList: true, subtree: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(injectServerName, 200);
        });
    } else {
        setTimeout(injectServerName, 200);
    }
})();
</script>
