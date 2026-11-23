/**
 * Upload dropzone enhancement.
 *
 * Progressive enhancement for <x-upload-field>: drag & drop, a selected-files
 * list with image thumbnails, per-file removal, and client-side type/size
 * hints that mirror the input's `accept` attribute and the server limits.
 * Server-side validation stays untouched and authoritative.
 *
 * With JavaScript disabled the component degrades to a normal labelled file
 * input: the dropzone label still opens the file picker and the form submits
 * normally.
 */
(function () {
    'use strict';

    function formatSize(bytes) {
        if (bytes >= 1024 * 1024) {
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
        if (bytes >= 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }
        return bytes + ' B';
    }

    function acceptTokens(input) {
        var accept = input.getAttribute('accept');
        if (!accept) {
            return [];
        }
        return accept.split(',').map(function (token) {
            return token.trim().toLowerCase();
        }).filter(function (token) {
            return token.length > 0;
        });
    }

    function fileMatchesAccept(file, tokens) {
        if (tokens.length === 0) {
            return true;
        }
        var name = file.name.toLowerCase();
        var type = (file.type || '').toLowerCase();
        for (var i = 0; i < tokens.length; i++) {
            var token = tokens[i];
            if (token.charAt(0) === '.') {
                if (name.endsWith(token)) {
                    return true;
                }
            } else if (token.slice(-2) === '/*') {
                if (type.indexOf(token.slice(0, -1)) === 0) {
                    return true;
                }
            } else if (type === token) {
                return true;
            }
        }
        return false;
    }

    function showError(field, message) {
        var error = field.querySelector('[data-file-error]');
        if (!error) {
            return;
        }
        error.textContent = message || '';
        error.hidden = !message;
    }

    function buildFileList(field, input) {
        var list = field.querySelector('[data-file-list]');
        if (!list) {
            return;
        }
        while (list.firstChild) {
            list.removeChild(list.firstChild);
        }
        var files = input.files ? Array.prototype.slice.call(input.files) : [];
        list.hidden = files.length === 0;
        if (files.length === 0) {
            return;
        }
        var removeLabel = input.getAttribute('data-msg-remove') || 'Remove';
        files.forEach(function (file, index) {
            var item = document.createElement('li');
            item.className = 'ltd-upload__file';

            if (file.type && file.type.indexOf('image/') === 0) {
                var thumb = document.createElement('img');
                thumb.className = 'ltd-upload__thumb';
                thumb.alt = '';
                var reader = new FileReader();
                reader.addEventListener('load', function () {
                    thumb.src = reader.result;
                });
                reader.readAsDataURL(file);
                item.appendChild(thumb);
            } else {
                var icon = document.createElement('span');
                icon.className = 'ltd-upload__file-icon';
                icon.setAttribute('aria-hidden', 'true');
                icon.textContent = '▣';
                item.appendChild(icon);
            }

            var meta = document.createElement('span');
            meta.className = 'ltd-upload__file-meta';
            var name = document.createElement('span');
            name.className = 'ltd-upload__file-name';
            name.textContent = file.name;
            var size = document.createElement('span');
            size.className = 'ltd-upload__file-size';
            size.textContent = formatSize(file.size);
            meta.appendChild(name);
            meta.appendChild(size);
            item.appendChild(meta);

            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'ltd-upload__remove';
            remove.textContent = removeLabel;
            remove.setAttribute('aria-label', removeLabel + ': ' + file.name);
            remove.addEventListener('click', function () {
                var transfer = new DataTransfer();
                Array.prototype.slice.call(input.files).forEach(function (kept, keptIndex) {
                    if (keptIndex !== index) {
                        transfer.items.add(kept);
                    }
                });
                input.files = transfer.files;
                showError(field, '');
                buildFileList(field, input);
            });
            item.appendChild(remove);

            list.appendChild(item);
        });
    }

    function addFiles(field, input, droppedFiles) {
        var tokens = acceptTokens(input);
        var maxSizeKb = parseInt(input.getAttribute('data-max-size-kb') || '', 10);
        var multiple = input.hasAttribute('multiple');
        var transfer = new DataTransfer();
        Array.prototype.slice.call(input.files || []).forEach(function (file) {
            transfer.items.add(file);
        });
        var added = 0;
        Array.prototype.slice.call(droppedFiles).forEach(function (file) {
            if (!multiple && (transfer.files.length > 0 || added > 0)) {
                return;
            }
            if (!fileMatchesAccept(file, tokens)) {
                showError(field, input.getAttribute('data-msg-invalid-type') || '');
                return;
            }
            if (!isNaN(maxSizeKb) && file.size > maxSizeKb * 1024) {
                showError(field, input.getAttribute('data-msg-too-large') || '');
                return;
            }
            transfer.items.add(file);
            added += 1;
        });
        if (added > 0) {
            showError(field, '');
            input.files = transfer.files;
            buildFileList(field, input);
            if (input.hasAttribute('data-autosubmit')) {
                var form = input.closest('form');
                if (form) {
                    form.submit();
                }
            }
        }
    }

    function enhance(field) {
        field.classList.add('ltd-upload--enhanced');
        var input = field.querySelector('input[type="file"]');
        if (!input) {
            return;
        }

        var dropzone = field.querySelector('[data-dropzone]');
        if (dropzone) {
            dropzone.addEventListener('click', function () {
                showError(field, '');
            });
            dropzone.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    input.click();
                }
            });
            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropzone.classList.add('is-dragover');
                });
            });
            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (event) {
                    event.preventDefault();
                    dropzone.classList.remove('is-dragover');
                });
            });
            dropzone.addEventListener('drop', function (event) {
                if (event.dataTransfer && event.dataTransfer.files) {
                    addFiles(field, input, event.dataTransfer.files);
                }
            });
        }

        var trigger = field.querySelector('[data-upload-trigger]');
        if (trigger) {
            trigger.addEventListener('click', function () {
                input.click();
            });
            var avatarImg = field.querySelector('[data-avatar-img]');
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (file && avatarImg && file.type && file.type.indexOf('image/') === 0) {
                    var reader = new FileReader();
                    reader.addEventListener('load', function () {
                        avatarImg.src = reader.result;
                    });
                    reader.readAsDataURL(file);
                }
            });
        }

        input.addEventListener('change', function () {
            var tokens = acceptTokens(input);
            var maxSizeKb = parseInt(input.getAttribute('data-max-size-kb') || '', 10);
            var valid = [];
            var transfer = new DataTransfer();
            Array.prototype.slice.call(input.files || []).forEach(function (file) {
                if (!fileMatchesAccept(file, tokens)) {
                    showError(field, input.getAttribute('data-msg-invalid-type') || '');
                    return;
                }
                if (!isNaN(maxSizeKb) && file.size > maxSizeKb * 1024) {
                    showError(field, input.getAttribute('data-msg-too-large') || '');
                    return;
                }
                valid.push(file);
                transfer.items.add(file);
            });
            if (valid.length > 0) {
                showError(field, '');
                input.files = transfer.files;
            } else if ((input.files || []).length > 0) {
                input.value = '';
            }
            buildFileList(field, input);
            if (valid.length > 0 && input.hasAttribute('data-autosubmit')) {
                var form = input.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    }

    document.querySelectorAll('[data-upload-field]').forEach(enhance);
})();
