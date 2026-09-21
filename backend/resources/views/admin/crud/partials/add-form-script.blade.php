<script>
    document.querySelectorAll('[data-image-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            const preview = document.querySelector(this.dataset.imageInput);
            const file = this.files && this.files[0];

            if (!preview || !file) {
                return;
            }

            preview.src = URL.createObjectURL(file);
            preview.classList.add('has-image');
        });
    });

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
