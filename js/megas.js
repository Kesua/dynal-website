// SLIDE TO DOWNLOAD
$(function () {
    $('a[href*="#"]:not([href="#"])').click(function () {
        if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
            var target = $(this.hash); target = target.length ? target : $('[name=' + this.hash.slice(1) + ']'); if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top
                }, 1000); return false;
            }
        }
    });
});

function openNav() { document.getElementById("myNav").style.height = "100%"; }
function closeNav() { document.getElementById("myNav").style.height = "0%"; }   

// Open PDF catalogs/profile sheets outside the page instead of inline.
(function () {
    function closestLink(element) {
        while (element && element.tagName) {
            if (element.tagName.toLowerCase() === 'a') {
                return element;
            }
            element = element.parentNode;
        }
        return null;
    }

    function handleClick(event) {
        event = event || window.event;
        if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button === 1) {
            return;
        }

        var link = closestLink(event.target || event.srcElement);
        if (!link || !link.href || !/\.pdf(\?|#|$)/i.test(link.href)) {
            return;
        }

        if (event.preventDefault) {
            event.preventDefault();
        } else {
            event.returnValue = false;
        }

        window.open(link.href, '_blank', 'noopener');
    }

    if (document.addEventListener) {
        document.addEventListener('click', handleClick, false);
    } else if (document.attachEvent) {
        document.attachEvent('onclick', handleClick);
    }
}());


