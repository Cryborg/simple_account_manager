<?php
$flashMessages = getFlashMessages();
if (!empty($flashMessages)):
?>
    <div class="flash-messages">
        <?php foreach ($flashMessages as $flash): ?>
            <div class="flash-message flash-<?= htmlspecialchars($flash['type']) ?>" role="alert">
                <span class="flash-icon">
                    <?php if ($flash['type'] === 'success'): ?>✓<?php endif; ?>
                    <?php if ($flash['type'] === 'error'): ?>✕<?php endif; ?>
                    <?php if ($flash['type'] === 'info'): ?>ℹ<?php endif; ?>
                </span>
                <span class="flash-text"><?= htmlspecialchars($flash['message']) ?></span>
                <button type="button" class="flash-close" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
