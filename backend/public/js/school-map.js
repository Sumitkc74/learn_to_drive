(() => {
    const container = document.getElementById('school-map');
    const status = document.getElementById('school-map-status');
    if (!container) return;
    if (!window.L) {
        if (status) status.textContent = 'Map could not load. Use the school list below.';
        return;
    }
    const map = L.map(container, {scrollWheelZoom: false}).setView([28.3949, 84.1240], 7);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    const points = [];
    document.querySelectorAll('[data-school-marker][data-lat][data-lng]').forEach(row => {
        const point = [Number(row.dataset.lat), Number(row.dataset.lng)];
        if (!point.every(Number.isFinite) || Math.abs(point[0]) > 90 || Math.abs(point[1]) > 180) return;
        const name = row.querySelector('[data-school-name]').textContent;
        const schoolId = row.dataset.schoolId;
        // School names are text, never popup HTML. The popup shows the name
        // and opens the same detail dialog as the name in the list below.
        const popup = document.createElement('div');
        const popupName = document.createElement('strong');
        popupName.textContent = name;
        popup.appendChild(popupName);
        const viewButton = document.createElement('button');
        viewButton.type = 'button';
        viewButton.className = 'button small';
        viewButton.textContent = 'View details';
        viewButton.addEventListener('click', () => {
            map.closePopup();
            if (window.openSchoolDialog) window.openSchoolDialog(schoolId);
        });
        popup.appendChild(viewButton);
        const icon = L.divIcon({className: 'school-marker', html: '<span></span>', iconSize: [28, 28], iconAnchor: [14, 14], popupAnchor: [0, -14]});
        const marker = L.marker(point, {title: name, alt: name, icon: icon}).addTo(map).bindPopup(popup);
        // The "Show on map" button lives in the school's dialog now, not in
        // the list row: close the dialog first so the map is visible.
        const button = document.querySelector('[data-show-school][data-school-id="' + schoolId + '"]');
        if (button) {
            button.hidden = false;
            button.addEventListener('click', () => {
                const dialog = button.closest('dialog');
                if (dialog) dialog.close();
                map.setView(point, 15);
                marker.openPopup();
                container.scrollIntoView({behavior: 'smooth', block: 'center'});
            });
        }
        points.push(point);
    });
    if (points.length) map.fitBounds(points, {padding: [35, 35], maxZoom: 15});
    status.textContent = points.length ? status.dataset.ready : status.dataset.empty;
})();
