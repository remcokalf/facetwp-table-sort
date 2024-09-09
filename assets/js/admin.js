(function() {

    var default_label = 'Sort label';
    var placeholder = 'Add a sort label';

    // Replace the default "New option" label after adding the sort option of a Table Sort facet.
    // Use the capture phase to run before Vue's click handler, which may re-create the whole settings panel (e.g. for a saved facet).
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.facetwp-content.type-table_sort .qb-wrap .facetwp-btn');
        if (null === btn) {
            return;
        }

        // Wait until Vue has rendered the new row.
        // Find the input again, because the settings panel may have been re-created.
        setTimeout(function() {
            var input = document.querySelector('.facetwp-content.type-table_sort .qb-row > div > input[type="text"]');
            if (null !== input && 'New option' === input.value) {
                input.value = default_label;
                input.dispatchEvent(new Event('input')); // Update the v-model
            }
        }, 0);
    }, true);

    // Set when the facet type is changed to Table Sort, or to Sort
    var add_pending = false;
    var sort_pending = false;

    // Use the capture phase to set the flags before Vue's v-model handler re-renders the settings panel
    document.addEventListener('change', function(e) {
        // The facet type select is the only select with a "table_sort" option
        if ('SELECT' === e.target.tagName && null !== e.target.querySelector('option[value="table_sort"]')) {
            add_pending = ('table_sort' === e.target.value);
            sort_pending = ('sort' === e.target.value);
        }
    }, true);

    // Add the sort option automatically when the facet type is changed to Table Sort.
    // Not when opening an existing facet, so the sort option can be removed for a heading-only column.
    var observer = new MutationObserver(function() {
        document.querySelectorAll('.facetwp-content.type-table_sort .qb-wrap').forEach(function(wrap) {
            if (add_pending) {
                add_pending = false;
                var input = wrap.querySelector('.qb-row > div > input[type="text"]');

                // No sort option yet: add one (the click listener sets its label)
                if (null === input) {
                    var btn = wrap.querySelector('.facetwp-btn');
                    if (null !== btn) {
                        btn.click();
                    }
                }
                // Existing sort option with the untouched Sort facet default (e.g. after switching from a Sort facet)
                else if ('New option' === input.value) {
                    input.value = default_label;
                    input.dispatchEvent(new Event('input')); // Update the v-model
                }
            }

            // Show an instruction as placeholder, for when the label field is emptied
            wrap.querySelectorAll('.qb-row > div > input[type="text"]').forEach(function(input) {
                if (input.placeholder !== placeholder) {
                    input.placeholder = placeholder;
                }
            });
        });

        // Restore core's default label when switching from a Table Sort facet to a Sort facet
        if (sort_pending) {
            var sort_wrap = document.querySelector('.facetwp-content.type-sort .qb-wrap');
            if (null !== sort_wrap) {
                sort_pending = false;
                var sort_input = sort_wrap.querySelector('.qb-row > div > input[type="text"]');
                if (null !== sort_input && default_label === sort_input.value) {
                    sort_input.value = 'New option';
                    sort_input.dispatchEvent(new Event('input')); // Update the v-model
                }
            }
        }
    });

    observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });

})();
