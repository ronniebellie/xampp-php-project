/**
 * Share results bar: Copy link, Email, Facebook, Twitter, LinkedIn, and native Share when available.
 * Include this script on any calculator page that has the share block (id="shareResults").
 * Optional: set data-share-title and data-share-text on #shareResults; otherwise uses document.title.
 */
(function () {
    const el = document.getElementById('shareResults');
    if (!el) return;

    const shareUrl = window.location.href;
    const shareTitle = el.getAttribute('data-share-title') || document.title || 'Calculator';
    const shareText = el.getAttribute('data-share-text') || ('Check out ' + shareTitle + ' at ronbelisle.com.');

    const copyBtn = document.getElementById('shareCopyLink');
    const copyFeedback = document.getElementById('shareCopyFeedback');
    const emailLink = document.getElementById('shareEmail');
    const facebookLink = document.getElementById('shareFacebook');
    const twitterLink = document.getElementById('shareTwitter');
    const linkedInLink = document.getElementById('shareLinkedIn');

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(shareUrl).then(function () {
                if (copyFeedback) {
                    copyFeedback.textContent = 'Copied!';
                    setTimeout(function () { copyFeedback.textContent = ''; }, 2000);
                }
            }).catch(function () {
                if (copyFeedback) copyFeedback.textContent = 'Copy failed';
            });
        });
    }

    if (emailLink) {
        emailLink.href = 'mailto:?subject=' + encodeURIComponent(shareTitle) + '&body=' + encodeURIComponent(shareText + '\n\n' + shareUrl);
    }
    if (facebookLink) {
        facebookLink.href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl);
    }
    if (twitterLink) {
        twitterLink.href = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(shareUrl) + '&text=' + encodeURIComponent(shareText);
    }
    if (linkedInLink) {
        linkedInLink.href = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(shareUrl);
    }

    if (navigator.share) {
        const shareNative = document.createElement('button');
        shareNative.type = 'button';
        shareNative.textContent = 'Share';
        shareNative.style.cssText = 'padding: 8px 16px; background: #48bb78; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;';
        shareNative.addEventListener('click', function () {
            navigator.share({ title: shareTitle, text: shareText, url: shareUrl }).catch(function () {});
        });
        const container = copyBtn ? copyBtn.parentNode : el;
        if (container) container.insertBefore(shareNative, copyBtn);
    }
})();
