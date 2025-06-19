function initializeHeader() {
    const userAvatarToggle = document.getElementById('userAvatarToggle');
    const userDropdown = document.getElementById('userDropdown');

    if (userAvatarToggle && userDropdown) {
        console.log('Avatar ve dropdown bulundu');

        const newUserAvatarToggle = userAvatarToggle.cloneNode(true);
        userAvatarToggle.parentNode.replaceChild(newUserAvatarToggle, userAvatarToggle);

        newUserAvatarToggle.addEventListener('click', function(event) {
            event.stopPropagation();
            userDropdown.classList.toggle('active');
            console.log('Avatar tıklandı, dropdown durumu:', userDropdown.classList.contains('active'));
        });

        const userInfo = document.querySelector('.user-info');
        if (userInfo) {
            const newUserInfo = userInfo.cloneNode(true);
            userInfo.parentNode.replaceChild(newUserInfo, userInfo);

            newUserInfo.addEventListener('click', function(event) {
                event.stopPropagation();
                userDropdown.classList.toggle('active');
            });
        }

        document.addEventListener('click', function(event) {
            if (userDropdown.classList.contains('active')) {
                if (!newUserAvatarToggle.contains(event.target) &&
                    !userDropdown.contains(event.target) &&
                    !(userInfo && userInfo.contains(event.target))) {
                    userDropdown.classList.remove('active');
                }
            }
        });
    } else {
        console.log('Avatar veya dropdown bulunamadı');
    }
}

document.addEventListener('DOMContentLoaded', initializeHeader);
document.addEventListener('turbo:load', initializeHeader);
document.addEventListener('turbo:render', initializeHeader);

if (typeof $ !== 'undefined') {
    $(document).on('ajaxComplete', function() {
        initializeHeader();
    });
}
