<script>
    document.querySelectorAll('[data-add-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            const button = this.querySelector('[data-submit-button]');

            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
            }
        });
    });
</script>
