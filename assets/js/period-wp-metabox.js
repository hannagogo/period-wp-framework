(function(){
    function closest(element, selector) {
        return element && element.closest(selector);
    }

    function escapeRegExp(value) {
        return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function parseGalleryIds(value) {
        if (value === '') {
            return [];
        }

        try {
            var parsed = JSON.parse(value);
        } catch (e) {
            return [];
        }

        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .map(function(item) {
                return typeof item === 'string' || typeof item === 'number' ? parseInt(item, 10) : NaN;
            })
            .filter(function(item) {
                return Number.isInteger(item) && item > 0;
            });
    }

    function updateInputValue(input, value) {
        input.value = JSON.stringify(value);
        input.dispatchEvent(new Event('change'));
    }

    function createGalleryItemAttachment(attachment) {
        var item = document.createElement('div');
        item.className = 'period-wp-metabox-gallery-item';
        item.dataset.attachmentId = String(attachment.id);

        if (attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url) {
            var image = document.createElement('img');
            image.src = attachment.sizes.thumbnail.url;
            image.alt = attachment.alt || '';
            item.appendChild(image);
        } else if (attachment.url) {
            var image = document.createElement('img');
            image.src = attachment.url;
            image.alt = attachment.alt || '';
            item.appendChild(image);
        } else {
            item.textContent = String(attachment.id);
        }

        return item;
    }

    function createPreviewItem(id) {
        var item = document.createElement('div');
        item.className = 'period-wp-metabox-gallery-item';
        item.dataset.attachmentId = String(id);
        item.textContent = String(id);
        return item;
    }

    function renderGalleryPreview(container, ids) {
        container.innerHTML = '';

        ids.forEach(function(id) {
            container.appendChild(createPreviewItem(id));
        });
    }

    function updateGalleryFromPreview(container, input) {
        var ids = [];
        container.querySelectorAll('[data-attachment-id]').forEach(function(item) {
            var attachmentId = parseInt(item.dataset.attachmentId, 10);
            if (Number.isInteger(attachmentId)) {
                ids.push(attachmentId);
            }
        });
        updateInputValue(input, ids);
    }

    function ensureSortable(container, onEnd) {
        if (typeof Sortable !== 'function' || container.dataset.sortableInitialized === '1') {
            return;
        }

        if (container.dataset.sortable !== 'true') {
            return;
        }

        Sortable.create(container, {
            animation: 150,
            onEnd: onEnd
        });

        container.dataset.sortableInitialized = '1';
    }

    function parseRepeaterValue(value) {
        if (value === '') {
            return [];
        }

        try {
            var parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) {
            return [];
        }
    }

    function buildItemObject(item, rootName) {
        var result = {};
        var selector = 'input[name], select[name], textarea[name]';
        item.querySelectorAll(selector).forEach(function(element) {
            var name = element.getAttribute('name');
            if (!name) {
                return;
            }

            var regex = new RegExp('^' + escapeRegExp(rootName) + '\\[\\d+\\]\\[(.+?)\\]$');
            var match = name.match(regex);
            if (!match) {
                return;
            }

            var key = match[1];
            if (element.type === 'checkbox') {
                result[key] = element.checked ? '1' : '';
                return;
            }

            if (element.tagName.toLowerCase() === 'select' || element.tagName.toLowerCase() === 'textarea' || element.type === 'hidden' || element.type === 'text') {
                result[key] = element.value;
            }
        });

        return result;
    }

    function syncRepeater(repeater) {
        if (!repeater) {
            return;
        }

        var rootName = repeater.dataset.fieldName;
        var input = repeater.querySelector('input[type="hidden"]');
        var items = [];

        repeater.querySelectorAll('[data-period-wp-repeater-item]').forEach(function(item) {
            items.push(buildItemObject(item, rootName));
        });

        if (input) {
            updateInputValue(input, items);
        }
    }

    function updateRepeaterIndices(repeater) {
        var rootName = repeater.dataset.fieldName;
        var items = repeater.querySelectorAll('[data-period-wp-repeater-item]');

        items.forEach(function(item, index) {
            item.dataset.itemIndex = String(index);
            item.querySelectorAll('[name]').forEach(function(element) {
                var name = element.getAttribute('name');
                if (!name) {
                    return;
                }

                var pattern = new RegExp('^' + escapeRegExp(rootName) + '\\[\\d+\\]');
                if (pattern.test(name)) {
                    element.setAttribute('name', name.replace(pattern, rootName + '[' + index + ']'));
                }
            });

            var groupLabel = item.dataset.groupLabel || '';
            var groupIndexLabel = item.dataset.groupIndexLabel !== 'false';
            var header = item.querySelector('[data-period-wp-group-header]');
            if (header) {
                if (groupIndexLabel) {
                    header.textContent = groupLabel !== '' ? groupLabel + ' ' + (index + 1) : String(index + 1);
                } else {
                    header.textContent = groupLabel;
                }
            }
        });
    }

    function createRepeaterItem(repeater) {
        var template = repeater.querySelector('template.period-wp-metabox-repeater-template');
        if (!template) {
            return null;
        }

        var content = template.content.cloneNode(true);
        var rootName = repeater.dataset.fieldName;
        var itemCount = repeater.querySelectorAll('[data-period-wp-repeater-item]').length;

        content.querySelectorAll('[name], [id], [for]').forEach(function(element) {
            ['name', 'id', 'for'].forEach(function(attribute) {
                if (!element.hasAttribute(attribute)) {
                    return;
                }

                var value = element.getAttribute(attribute);
                if (value === null) {
                    return;
                }

                value = value.replace(/__INDEX__/g, String(itemCount));
                var pattern = new RegExp('^' + escapeRegExp(rootName) + '\\[__INDEX__\\]');
                if (pattern.test(value)) {
                    element.setAttribute(attribute, value.replace(pattern, rootName + '[' + itemCount + ']'));
                } else {
                    element.setAttribute(attribute, value);
                }
            });
        });

        return content;
    }

    function initializeRepeater(repeater) {
        var itemsContainer = repeater.querySelector('[data-period-wp-repeater-items]');
        if (!itemsContainer) {
            return;
        }

        if (typeof Sortable === 'function' && repeater.dataset.sortable === 'true') {
            ensureSortable(itemsContainer, function() {
                updateRepeaterIndices(repeater);
                syncRepeater(repeater);
            });
        }
    }

    document.addEventListener('click', function(event) {
        var button = closest(event.target, '.period-wp-metabox-media-button');
        var galleryButton = closest(event.target, '.period-wp-metabox-gallery-button');
        var repeaterAdd = closest(event.target, '.period-wp-metabox-repeater-add');
        var repeaterRemove = closest(event.target, '.period-wp-metabox-repeater-remove');
        var groupHeader = closest(event.target, '[data-period-wp-group-header]');
        var clearButton = closest(event.target, '.period-wp-metabox-media-clear');
        var galleryClearButton = closest(event.target, '.period-wp-metabox-gallery-clear');

        if (button) {
            event.preventDefault();
            var container = closest(button, '.period-wp-metabox-media');
            if (!container || !window.wp || !wp.media) {
                return;
            }

            var mimeType = container.dataset.mime || '';
            var input = container.querySelector('input[type="hidden"]');
            var preview = container.querySelector('.period-wp-metabox-media-preview');

            var frame = wp.media({
                title: button.dataset.buttonLabel || 'Select Media',
                button: { text: button.dataset.buttonLabel || 'Select' },
                multiple: false,
                library: { type: mimeType }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                if (input) {
                    input.value = String(attachment.id);
                    input.dispatchEvent(new Event('change'));
                }
                if (preview) {
                    preview.textContent = attachment.url ? attachment.url : String(attachment.id);
                }
            });

            frame.open();
            return;
        }

        if (galleryButton) {
            event.preventDefault();
            var container = closest(galleryButton, '.period-wp-metabox-gallery');
            if (!container || !window.wp || !wp.media) {
                return;
            }

            var mimeType = container.dataset.mime || 'image';
            var input = container.querySelector('input[type="hidden"]');
            var preview = container.querySelector('.period-wp-metabox-gallery-preview');
            var ids = input ? parseGalleryIds(input.value) : [];

            var frame = wp.media({
                title: galleryButton.dataset.buttonLabel || 'Select Images',
                button: { text: galleryButton.dataset.buttonLabel || 'Select' },
                multiple: true,
                library: { type: mimeType }
            });

            frame.on('select', function() {
                var selection = frame.state().get('selection').toArray();
                selection.forEach(function(attachment) {
                    attachment = attachment.toJSON();
                    var attachmentId = parseInt(attachment.id, 10);
                    if (Number.isInteger(attachmentId) && ids.indexOf(attachmentId) === -1) {
                        ids.push(attachmentId);
                        if (preview) {
                            preview.appendChild(createGalleryItemAttachment(attachment));
                        }
                    }
                });
                if (input) {
                    updateInputValue(input, ids);
                }
                if (preview && typeof Sortable === 'function') {
                    ensureSortable(preview, function() {
                        updateGalleryFromPreview(preview, input);
                    });
                }
            });

            frame.open();
            return;
        }

        if (repeaterAdd) {
            event.preventDefault();
            var repeater = closest(repeaterAdd, '[data-period-wp-repeater]');
            if (!repeater) {
                return;
            }

            var max = repeater.dataset.max !== undefined && repeater.dataset.max !== '' ? parseInt(repeater.dataset.max, 10) : null;
            var items = repeater.querySelectorAll('[data-period-wp-repeater-item]').length;
            if (Number.isInteger(max) && max !== null && items >= max) {
                return;
            }

            var itemsContainer = repeater.querySelector('[data-period-wp-repeater-items]');
            var newItem = createRepeaterItem(repeater);
            if (itemsContainer && newItem) {
                itemsContainer.appendChild(newItem);
                updateRepeaterIndices(repeater);
                syncRepeater(repeater);
            }
            return;
        }

        if (repeaterRemove) {
            event.preventDefault();
            var item = closest(repeaterRemove, '[data-period-wp-repeater-item]');
            var repeater = closest(repeaterRemove, '[data-period-wp-repeater]');
            if (!item || !repeater) {
                return;
            }

            var min = repeater.dataset.min !== undefined && repeater.dataset.min !== '' ? parseInt(repeater.dataset.min, 10) : 0;
            var items = repeater.querySelectorAll('[data-period-wp-repeater-item]').length;
            if (Number.isInteger(min) && items <= min) {
                return;
            }

            item.remove();
            updateRepeaterIndices(repeater);
            syncRepeater(repeater);
            return;
        }

        if (groupHeader) {
            var item = closest(groupHeader, '[data-period-wp-repeater-item]');
            if (!item || item.dataset.groupCollapsible !== 'true') {
                return;
            }

            event.preventDefault();
            var body = item.querySelector('[data-period-wp-group-body]');
            if (!body) {
                return;
            }

            body.style.display = body.style.display === 'none' ? '' : 'none';
            return;
        }

        if (clearButton) {
            event.preventDefault();
            var container = closest(clearButton, '.period-wp-metabox-media');
            var input = container ? container.querySelector('input[type="hidden"]') : null;
            var preview = container ? container.querySelector('.period-wp-metabox-media-preview') : null;
            if (input) {
                input.value = '';
                input.dispatchEvent(new Event('change'));
            }
            if (preview) {
                preview.textContent = '';
            }
            return;
        }

        if (galleryClearButton) {
            event.preventDefault();
            var container = closest(galleryClearButton, '.period-wp-metabox-gallery');
            var input = container ? container.querySelector('input[type="hidden"]') : null;
            var preview = container ? container.querySelector('.period-wp-metabox-gallery-preview') : null;
            if (input) {
                updateInputValue(input, []);
            }
            if (preview) {
                renderGalleryPreview(preview, []);
            }
            return;
        }
    });

    document.addEventListener('change', function(event) {
        var repeater = closest(event.target, '[data-period-wp-repeater]');
        if (repeater) {
            syncRepeater(repeater);
        }
    });

    Array.prototype.forEach.call(document.querySelectorAll('[data-period-wp-repeater]'), function(repeater) {
        initializeRepeater(repeater);
    });
})();
