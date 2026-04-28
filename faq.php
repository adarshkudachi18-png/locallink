<?php
require_once 'includes/config.php';

$stmt = $pdo->query("SELECT * FROM faqs WHERE status = 'active' ORDER BY sort_order, id");
$faqs = $stmt->fetchAll();

$pageTitle = 'FAQ';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container" style="padding-top:20px;padding-bottom:40px;max-width:720px;">
        <div class="section-header">
            <span class="section-badge"><i class="bi bi-question-circle-fill me-1"></i> Help</span>
            <h2 class="section-title">Frequently Asked Questions</h2>
            <p class="section-subtitle">Find answers to common questions</p>
        </div>

        <?php if (count($faqs) > 0): ?>
            <?php foreach ($faqs as $faq): ?>
            <div class="faq-item">
                <div class="faq-question">
                    <span><?= htmlspecialchars($faq['question']) ?></span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-inner"><?= htmlspecialchars($faq['answer']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-question-circle"></i>
                <h4>No FAQs Yet</h4>
                <p>FAQs will be added soon.</p>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <p style="color:var(--text-secondary);">Still have questions?</p>
            <a href="<?= SITE_URL ?>/contact.php" class="btn btn-primary"><i class="bi bi-envelope me-2"></i>Contact Support</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
