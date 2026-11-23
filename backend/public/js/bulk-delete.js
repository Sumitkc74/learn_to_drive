// Bulk delete for admin content lists: the header checkbox selects every
// row on the current page, the bar shows a live selected count, and the
// Delete button stays disabled until at least one row is checked. Plain
// JavaScript only, no libraries; the form works without it too (the button
// stays enabled and the server validates the selection).
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('bulk-delete-form');
    if (!form) return;
    var rows = Array.prototype.slice.call(document.querySelectorAll('.bulk-delete-row'));
    if (rows.length === 0) return;
    var selectAll = document.querySelector('.bulk-delete-select-all');
    var count = form.querySelector('.bulk-delete-count');
    var button = form.querySelector('button[type="submit"]');

    function update() {
        var checked = rows.filter(function (row) { return row.checked; }).length;
        if (count) count.textContent = String(checked);
        if (button) button.disabled = checked === 0;
        if (selectAll) {
            selectAll.checked = checked > 0 && checked === rows.length;
            selectAll.indeterminate = checked > 0 && checked < rows.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            rows.forEach(function (row) { row.checked = selectAll.checked; });
            update();
        });
    }
    rows.forEach(function (row) {
        row.addEventListener('change', update);
    });
    update();
});
