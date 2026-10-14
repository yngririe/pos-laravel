// Laravel CSRF + JSON handling for the existing fetch() calls.
// Adds X-CSRF-TOKEN / Accept headers to same-origin requests so none of the
// page scripts need to change. Requires <meta name="csrf-token"> in <head>.
(function () {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta || !window.fetch) return;

    const token = meta.getAttribute('content');
    const nativeFetch = window.fetch.bind(window);

    window.fetch = function (input, init) {
        init = init || {};
        const url = typeof input === 'string' ? input : (input && input.url) || '';
        let sameOrigin = true;
        try { sameOrigin = new URL(url, window.location.href).origin === window.location.origin; } catch (e) {}

        if (sameOrigin) {
            const method  = (init.method || (input && input.method) || 'GET').toUpperCase();
            const headers = new Headers(init.headers || (input && input.headers) || {});
            if (!headers.has('Accept'))           headers.set('Accept', 'application/json');
            if (!headers.has('X-Requested-With')) headers.set('X-Requested-With', 'XMLHttpRequest');
            if (method !== 'GET' && method !== 'HEAD' && !headers.has('X-CSRF-TOKEN')) {
                headers.set('X-CSRF-TOKEN', token);
            }
            init.headers = headers;
        }
        return nativeFetch(input, init);
    };
})();
