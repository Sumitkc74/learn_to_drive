(() => {
    const container = document.getElementById('school-map');
    const status = document.getElementById('school-map-status');
    if (!container || !window.L) return;
    const map = L.map(container, {scrollWheelZoom: false}).setView([28.3949, 84.1240], 7);
    const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    const fallback = status.textContent;
    tiles.on('tileerror', () => { status.textContent = fallback; });
    const points = [];
    document.querySelectorAll('[data-school-marker][data-lat][data-lng]').forEach(card => {
        const point = [Number(card.dataset.lat), Number(card.dataset.lng)];
        if (!point.every(Number.isFinite) || Math.abs(point[0]) > 90 || Math.abs(point[1]) > 180) return;
        const name = card.querySelector('[data-school-name]').textContent;
        // School names are text, never popup HTML.
        const popup = document.createElement('strong');
        popup.textContent = name;
        const marker = L.marker(point, {title: name, alt: name}).addTo(map).bindPopup(popup);
        const button = card.querySelector('[data-show-school]');
        button.hidden = false;
        button.addEventListener('click', () => {
            map.setView(point, 15);
            marker.openPopup();
            container.scrollIntoView({behavior: 'smooth', block: 'center'});
        });
        points.push(point);
    });
    if (points.length) map.fitBounds(points, {padding: [35, 35], maxZoom: 15});
    status.textContent = points.length ? status.dataset.ready : status.dataset.empty;
})();
