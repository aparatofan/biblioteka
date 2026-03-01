(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var isDesktop = window.innerWidth > 768;
        var overlay = document.querySelector('.biblioteka-popup-overlay');
        var popupContent = document.querySelector('.biblioteka-popup-content');
        var closeBtn = document.querySelector('.biblioteka-popup-close');
        var bookItems = document.querySelectorAll('.biblioteka-book-item');
        var categories = document.querySelectorAll('.biblioteka-category');

        // Search elements.
        var searchTitle = document.getElementById('biblioteka-search-title');
        var searchAuthor = document.getElementById('biblioteka-search-author');
        var searchCategory = document.getElementById('biblioteka-search-category');
        var noResults = document.querySelector('.biblioteka-no-results');

        // Update mode on resize.
        window.addEventListener('resize', function () {
            isDesktop = window.innerWidth > 768;
            if (isDesktop) {
                bookItems.forEach(function (item) {
                    item.classList.remove('open');
                    var details = item.querySelector('.biblioteka-book-details');
                    if (details) details.style.display = 'none';
                });
            }
            if (!isDesktop && overlay) {
                overlay.style.display = 'none';
            }
        });

        // Search / filter logic.
        function filterBooks() {
            var titleQuery = (searchTitle ? searchTitle.value : '').toLowerCase().trim();
            var authorQuery = (searchAuthor ? searchAuthor.value : '').toLowerCase().trim();
            var categoryQuery = searchCategory ? searchCategory.value : '';

            var totalVisible = 0;

            categories.forEach(function (catSection) {
                var items = catSection.querySelectorAll('.biblioteka-book-item');
                var visibleInCategory = 0;

                items.forEach(function (item) {
                    var matchTitle = !titleQuery || item.getAttribute('data-title').indexOf(titleQuery) !== -1;
                    var matchAuthor = !authorQuery || item.getAttribute('data-author').indexOf(authorQuery) !== -1;
                    var matchCategory = !categoryQuery || item.getAttribute('data-category') === categoryQuery;

                    if (matchTitle && matchAuthor && matchCategory) {
                        item.style.display = '';
                        visibleInCategory++;
                    } else {
                        item.style.display = 'none';
                        item.classList.remove('open');
                        var details = item.querySelector('.biblioteka-book-details');
                        if (details) details.style.display = 'none';
                    }
                });

                catSection.style.display = visibleInCategory > 0 ? '' : 'none';
                totalVisible += visibleInCategory;
            });

            if (noResults) {
                noResults.style.display = totalVisible === 0 ? 'block' : 'none';
            }
        }

        if (searchTitle) searchTitle.addEventListener('input', filterBooks);
        if (searchAuthor) searchAuthor.addEventListener('input', filterBooks);
        if (searchCategory) searchCategory.addEventListener('change', filterBooks);

        // Handle clicks on book headers.
        bookItems.forEach(function (item) {
            var header = item.querySelector('.biblioteka-book-header');
            if (!header) return;

            header.addEventListener('click', function () {
                if (isDesktop) {
                    openPopup(item);
                } else {
                    toggleMobileExpand(item);
                }
            });
        });

        // Desktop: open popup with book details.
        function openPopup(item) {
            if (!overlay || !popupContent) return;

            var details = item.querySelector('.biblioteka-book-details-inner');
            if (!details) return;

            popupContent.innerHTML = '';

            var title = item.querySelector('.biblioteka-book-title');

            // Clone info from the hidden details.
            var infoClone = details.cloneNode(true);

            // Add title as heading inside info.
            var infoDiv = infoClone.querySelector('.biblioteka-book-info');
            if (infoDiv) {
                var h4 = document.createElement('h4');
                h4.textContent = title ? title.textContent : '';
                infoDiv.insertBefore(h4, infoDiv.firstChild);
            }

            popupContent.appendChild(infoClone);
            overlay.style.display = 'flex';
        }

        // Mobile: toggle inline expand.
        function toggleMobileExpand(item) {
            var details = item.querySelector('.biblioteka-book-details');
            if (!details) return;

            var isOpen = item.classList.contains('open');

            bookItems.forEach(function (other) {
                if (other !== item) {
                    other.classList.remove('open');
                    var otherDetails = other.querySelector('.biblioteka-book-details');
                    if (otherDetails) otherDetails.style.display = 'none';
                }
            });

            if (isOpen) {
                item.classList.remove('open');
                details.style.display = 'none';
            } else {
                item.classList.add('open');
                details.style.display = 'block';
            }
        }

        // Close popup.
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                if (overlay) overlay.style.display = 'none';
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) overlay.style.display = 'none';
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay && overlay.style.display !== 'none') {
                overlay.style.display = 'none';
            }
        });
    });
})();
