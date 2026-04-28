<?php
require_once 'includes/config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $error = 'Name, email, and message are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $subject, $message])) {
            $success = 'Message sent successfully! We will get back to you soon.';
        } else {
            $error = 'Failed to send message. Please try again.';
        }
    }
}

$pageTitle = 'Contact';
include 'includes/header.php';
?>

<div class="main-content">
    <div class="container" style="padding-top:20px;padding-bottom:40px;max-width:900px;">
        <div class="section-header">
            <span class="section-badge"><i class="bi bi-chat-dots-fill me-1"></i> Support</span>
            <h2 class="section-title">Contact Us</h2>
            <p class="section-subtitle">We'd love to hear from you</p>
        </div>

        <div class="row g-4">
            <div class="col-md-5">
                <div class="contact-info-item">
                    <div class="contact-icon"><i class="bi bi-envelope-fill"></i></div>
                    <div>
                        <strong>Email</strong><br>
                        <span style="color:var(--text-secondary);">support@ybtdigital.com</span>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-icon"><i class="bi bi-clock-fill"></i></div>
                    <div>
                        <strong>Response Time</strong><br>
                        <span style="color:var(--text-secondary);">Within 24 hours</span>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-icon"><i class="bi bi-question-circle-fill"></i></div>
                    <div>
                        <strong>FAQ</strong><br>
                        <a href="<?= SITE_URL ?>/faq.php" style="color:var(--text-secondary);">Check our FAQ page</a>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="contact-card">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? ($_SESSION['user_name'] ?? '')) ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? ($_SESSION['user_email'] ?? '')) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Subject</label>
                                <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-2"></i>Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
