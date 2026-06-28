<script>
(function() {
    'use strict';

    /* ─── Globals ─── */
    var NAME_ID = 'darkenate-server-name';
    var folderData = null;
    var FOLDER_KEY = 'darkenate_collapsed';

    /* ─── Helpers ─── */
    function isServerPage() { return /^\/server\//.test(window.location.pathname); }
    function isDashboardPage() { var p = window.location.pathname; return p === '/' || /^\/\?page=\d+$/.test(p); }

    function getServerName() {
        var t = document.title;
        if (!t) return null;
        var i = t.indexOf(' | ');
        return i > 0 ? t.substring(0, i) : null;
    }

    /* ─── Server Name ─── */
    function injectServerName() {
        if (!isServerPage() || document.getElementById(NAME_ID)) return;
        var name = getServerName();
        if (!name) return;
        var el = document.createElement('h1');
        el.id = NAME_ID;
        el.textContent = name;
        el.style.cssText = 'font-family:inherit;font-weight:500;font-size:1.5rem;line-height:1.75rem;color:rgb(250 250 250);margin:0.75rem 0 0.5rem;padding:0 1.5rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
        var subNav = document.querySelector('[class*="SubNavigation"]');
        if (subNav && subNav.parentNode) { subNav.parentNode.insertBefore(el, subNav.nextSibling); return; }
        var container = document.querySelector('[class*="ContentContainer"]');
        if (container && container.parentNode) container.parentNode.insertBefore(el, container);
    }

    function removeServerName() { var el = document.getElementById(NAME_ID); if (el) el.remove(); }

    /* ─── Folders ─── */
    function getCollapsed() { try { return JSON.parse(localStorage.getItem(FOLDER_KEY) || '[]'); } catch(e) { return []; } }
    function saveCollapsed(a) { localStorage.setItem(FOLDER_KEY, JSON.stringify(a)); }

    function toggleFolder(id) {
        var a = getCollapsed();
        var i = a.indexOf(id);
        i > -1 ? a.splice(i, 1) : a.push(id);
        saveCollapsed(a);
        applyCollapsed();
    }

    function applyCollapsed() {
        var a = getCollapsed();
        document.querySelectorAll('.darkenate-folder-group').forEach(function(g) {
            var id = g.getAttribute('data-folder-id');
            var body = g.querySelector('.darkenate-folder-body');
            var arrow = g.querySelector('.darkenate-folder-arrow');
            if (a.indexOf(id) > -1) {
                body.style.display = 'none';
                if (arrow) arrow.innerHTML = '&#9654;';
            } else {
                body.style.display = '';
                if (arrow) arrow.innerHTML = '&#9660;';
            }
        });
    }

    function fetchFolders() {
        return fetch('/api/client/extensions/darkenate/folders')
            .then(function(r) { return r.ok ? r.json() : []; })
            .catch(function() { return []; });
    }

    function groupServers() {
        if (!isDashboardPage() || !folderData) return;
        if (document.querySelector('.darkenate-folder-group')) return;

        var rows = document.querySelectorAll('a[href^="/server/"]');
        if (!rows.length) return;

        // Find the container that holds all server cards (direct children)
        var container = rows[0].parentElement;
        while (container && container !== document.body) {
            if (container.querySelectorAll(':scope > a[href^="/server/"], :scope > div > a[href^="/server/"]').length >= 2) break;
            container = container.parentElement;
        }
        if (container === document.body) container = rows[0].parentElement;

        // For each server link, find its direct child of the container (the card element)
        var cardById = {};
        rows.forEach(function(link) {
            var el = link;
            while (el.parentElement !== container) el = el.parentElement;
            var m = link.getAttribute('href').match(/\/server\/(\d+)/);
            if (m && !cardById[m[1]]) cardById[m[1]] = el;
        });

        var sids = Object.keys(cardById);
        if (!sids.length) return;

        var assigned = {};
        folderData.forEach(function(f) { f.servers.forEach(function(s) { assigned[String(s.id)] = true; }); });

        var frag = document.createDocumentFragment();
        var collapsed = getCollapsed();

        function makeGroup(fid, fname, ids) {
            var g = document.createElement('div');
            g.className = 'darkenate-folder-group';
            g.setAttribute('data-folder-id', fid);

            var h = document.createElement('div');
            h.className = 'darkenate-folder-header';
            h.style.cssText = 'cursor:pointer;display:flex;align-items:center;padding:0.75rem 1rem;margin-top:0.5rem;border-radius:8px;background:var(--item-color,#1f212f);color:rgb(200 200 200);user-select:none;';
            h.addEventListener('click', function() { toggleFolder(fid); });

            var arrow = document.createElement('span');
            arrow.className = 'darkenate-folder-arrow';
            arrow.style.cssText = 'margin-right:0.75rem;font-size:0.8rem;';
            arrow.innerHTML = collapsed.indexOf(fid) > -1 ? '&#9654;' : '&#9660;';

            var label = document.createElement('span');
            label.style.cssText = 'font-weight:600;font-size:1rem;';
            label.textContent = fname;

            var badge = document.createElement('span');
            badge.style.cssText = 'margin-left:0.5rem;color:rgb(150 150 150);font-size:0.85rem;';
            badge.textContent = '(' + ids.length + ')';

            h.appendChild(arrow); h.appendChild(label); h.appendChild(badge);
            g.appendChild(h);

            var body = document.createElement('div');
            body.className = 'darkenate-folder-body';
            if (collapsed.indexOf(fid) > -1) body.style.display = 'none';
            ids.forEach(function(sid) {
                var card = cardById[sid];
                if (card) { body.appendChild(card); replaced[sid] = true; }
            });
            g.appendChild(body);
            return g;
        }

        var uncat = sids.filter(function(s) { return !assigned[s]; });
        if (uncat.length) frag.appendChild(makeGroup('__uncategorized__', 'Uncategorized', uncat));

        folderData.forEach(function(f) {
            var ids = f.servers.map(function(s) { return String(s.id); }).filter(function(s) { return cardById[s]; });
            if (ids.length) frag.appendChild(makeGroup(f.id, f.name, ids));
        });

        // Find index of first card, remove all cards, insert groups at that index
        var cards = sids.map(function(sid) { return cardById[sid]; }).filter(Boolean);
        var firstIdx = -1;
        for (var i = 0; i < container.children.length; i++) {
            if (cards.indexOf(container.children[i]) !== -1) {
                firstIdx = i;
                break;
            }
        }
        cards.forEach(function(c) { if (c.parentNode) c.parentNode.removeChild(c); });
        var ref = firstIdx > -1 ? container.children[firstIdx] : null;
        if (ref) { container.insertBefore(frag, ref); } else { container.appendChild(frag); }
    }

    /* ─── Navigation ─── */
    var lastUrl = location.href;
    function onUrlChange() {
        var url = location.href;
        if (url === lastUrl) return;
        lastUrl = url;
        removeServerName();
        if (isServerPage()) setTimeout(injectServerName, 30);
    }

    window.addEventListener('popstate', onUrlChange);
    var origPush = history.pushState;
    history.pushState = function() { origPush.apply(this, arguments); onUrlChange(); };
    var origReplace = history.replaceState;
    history.replaceState = function() { origReplace.apply(this, arguments); onUrlChange(); };

    /* ─── Init ─── */
    fetchFolders().then(function(data) {
        folderData = data;
        new MutationObserver(function() {
            if (isServerPage() && !document.getElementById(NAME_ID)) injectServerName();
            if (isDashboardPage()) groupServers();
        }).observe(document.body, { childList: true, subtree: true });
        groupServers();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() { setTimeout(injectServerName, 200); });
    } else {
        setTimeout(injectServerName, 200);
    }
})();
</script>
