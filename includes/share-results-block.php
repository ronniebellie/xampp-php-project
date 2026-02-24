<?php
// Share these results block — include inside your results container.
// Optional: set $share_title and $share_text before including for custom text; otherwise JS uses document.title.
if (!isset($share_title)) $share_title = '';
if (!isset($share_text)) $share_text = '';
$share_attr = '';
if ($share_title !== '') $share_attr .= ' data-share-title="' . htmlspecialchars($share_title) . '"';
if ($share_text !== '') $share_attr .= ' data-share-text="' . htmlspecialchars($share_text) . '"';
?>
<div class="share-results" id="shareResults" style="margin-top: 28px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center;"<?php echo $share_attr; ?>>
    <p style="margin: 0 0 12px 0; font-weight: 600; color: #4a5568;">Share these results</p>
    <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; align-items: center;">
        <button type="button" id="shareCopyLink" style="padding: 8px 16px; background: #3182ce; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Copy link</button>
        <span id="shareCopyFeedback" style="font-size: 14px; color: #38a169;"></span>
        <a href="#" id="shareEmail" style="padding: 8px 16px; background: #718096; color: white; border-radius: 6px; font-weight: 600; text-decoration: none;">Email</a>
        <a href="#" id="shareFacebook" target="_blank" rel="noopener noreferrer" style="padding: 8px 16px; background: #1877f2; color: white; border-radius: 6px; font-weight: 600; text-decoration: none;">Facebook</a>
        <a href="#" id="shareTwitter" target="_blank" rel="noopener noreferrer" style="padding: 8px 16px; background: #1da1f2; color: white; border-radius: 6px; font-weight: 600; text-decoration: none;">X (Twitter)</a>
        <a href="#" id="shareLinkedIn" target="_blank" rel="noopener noreferrer" style="padding: 8px 16px; background: #0a66c2; color: white; border-radius: 6px; font-weight: 600; text-decoration: none;">LinkedIn</a>
    </div>
</div>
