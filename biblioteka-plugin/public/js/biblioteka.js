(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var isDesktop = window.innerWidth > 768;
        var overlay = document.querySelector('.biblioteka-popup-overlay');
        var popupContent = document.querySelector('.biblioteka-popup-content');
        var closeBtn = document.querySelector('.biblioteka-popup-close');
        var bookItems = document.querySelectorAll('.biblioteka-book-item');

        // Update mode on resize.
        window.addEventListener('resize', function () {
            isDesktop = window.innerWidth > 768;
            // Close any open mobile details when switching to desktop.
            if (isDesktop) {
                bookItems.forEach(function (item) {
                    item.classList.remove('open');
                    var details = item.querySelector('.biblioteka-book-details');
                    if (details) {
                        details.style.display = 'none';
                    }
                });
            }
            // Hide popup when switching to mobile.
            if (!isDesktop && overlay) {
                overlay.style.display = 'none';
            }
        });

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

            // Build popup content.
            var title = item.querySelector('.biblioteka-book-title');
            var titleHtml = '<div class="biblioteka-book-info"><h4>' +
                escapeHtml(title ? title.textContent : '') + '</h4>';

            // Clone info from the hidden details.
            var infoClone = details.cloneNode(true);
            popupContent.innerHTML = '';

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

            // Close all others.
            bookItems.forEach(function (other) {
                if (other !== item) {
                    other.classList.remove('open');
                    var otherDetails = other.querySelector('.biblioteka-book-details');
                    if (otherDetails) {
                        otherDetails.style.display = 'none';
                    }
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
                if (e.target === overlay) {
                    overlay.style.display = 'none';
                }
            });
        }

        // Close popup on Escape key.
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay && overlay.style.display !== 'none') {
                overlay.style.display = 'none';
            }
        });

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }
    });
})();
