$('#toggle-leftbar').on('click', function(event) {
    event.preventDefault();
    Layout = $('body > .layout');
    if (Layout.attr('data-leftbar') !== 'mini') {
        Layout.attr('data-leftbar', 'mini');
        if (window.innerWidth >= 800) {
            localStorage.setItem('admin-leftbar', 'mini');
        }
    } else {
        Layout.attr('data-leftbar', 'full');
        if (window.innerWidth >= 800) {
            localStorage.setItem('admin-leftbar', 'full');
        }
    }
});