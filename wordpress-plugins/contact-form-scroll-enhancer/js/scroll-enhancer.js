document.addEventListener('DOMContentLoaded', function() {
    function addPreviousButtonListener() {
        var previousButton = document.querySelector(`input.wpcf7-form-control.wpcf7-previous[type="button"][value="${cfseSettings.buttonText}"]`);
        if (previousButton) {
            previousButton.addEventListener('click', function(event) {
                localStorage.setItem('scrollToContact', 'true');
            });
        }
    }

    addPreviousButtonListener();

    if (localStorage.getItem('scrollToContact') === 'true') {
        var contactFormPosition = document.getElementById(cfseSettings.scrollTarget);
        if (contactFormPosition) {
            var contactFormPositionTop = contactFormPosition.offsetTop;
            window.scrollTo({
                top: contactFormPositionTop,
                behavior: 'smooth'
            });
        }
        localStorage.removeItem('scrollToContact');
    }

    document.addEventListener('wpcf7mailsent', function(event) {
        addPreviousButtonListener();
    });
});
