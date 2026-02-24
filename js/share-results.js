/**
 * Share results bar: Copy link, Email, Facebook, Twitter, LinkedIn, and native Share when available.
 * Include this script on any calculator page that has the share block (id="shareResults").
 *
 * Behavior:
 * - If #shareResults has data-share-url, that URL is used for all share actions.
 * - Otherwise, window.location.href is used.
 * - This allows calculators to set data-share-url to a URL that encodes the current scenario/results.
 */
(function () {
    const el = document.getElementById('shareResults');
    if (!el) return;

    const shareTitle = el.getAttribute('data-share-title') || document.title || 'Calculator';
    const shareText = el.getAttribute('data-share-text') || ('Check out ' + shareTitle + ' at ronbelisle.com.');

    function getShareUrl() {
        const override = el.getAttribute('data-share-url');
        if (override && override.trim() !== '') return override;
        return window.location.href;
    }

    const copyBtn = document.getElementById('shareCopyLink');
    const copyFeedback = document.getElementById('shareCopyFeedback');
    const emailLink = document.getElementById('shareEmail');
    const facebookLink = document.getElementById('shareFacebook');
    const twitterLink = document.getElementById('shareTwitter');
    const linkedInLink = document.getElementById('shareLinkedIn');

    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            const shareUrl = getShareUrl();
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
        emailLink.addEventListener('click', function (e) {
            e.preventDefault();
            const shareUrl = getShareUrl();
            const mailto = 'mailto:?subject=' + encodeURIComponent(shareTitle) +
                '&body=' + encodeURIComponent(shareText + '\n\n' + shareUrl);
            window.location.href = mailto;
        });
    }

    if (facebookLink) {
        facebookLink.addEventListener('click', function (e) {
            e.preventDefault();
            const shareUrl = getShareUrl();
            const url = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl);
            window.open(url, '_blank', 'noopener');
        });
    }

    if (twitterLink) {
        twitterLink.addEventListener('click', function (e) {
            e.preventDefault();
            const shareUrl = getShareUrl();
            const url = 'https://twitter.com/intent/tweet?url=' + encodeURIComponent(shareUrl) +
                '&text=' + encodeURIComponent(shareText);
            window.open(url, '_blank', 'noopener');
        });
    }

    if (linkedInLink) {
        linkedInLink.addEventListener('click', function (e) {
            e.preventDefault();
            const shareUrl = getShareUrl();
            const url = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(shareUrl);
            window.open(url, '_blank', 'noopener');
        });
    }

    if (navigator.share) {
        const shareNative = document.createElement('button');
        shareNative.type = 'button';
        shareNative.textContent = 'Share';
        shareNative.style.cssText = 'padding: 8px 16px; background: #48bb78; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;';
        shareNative.addEventListener('click', function () {
            const shareUrl = getShareUrl();
            navigator.share({ title: shareTitle, text: shareText, url: shareUrl }).catch(function () {});
        });
        const container = copyBtn ? copyBtn.parentNode : el;
        if (container) container.insertBefore(shareNative, copyBtn);
    }
})();
