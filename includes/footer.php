<footer class="site-footer d-none d-md-block">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="footer-brand"><?= getSetting('site_name', 'Local Link') ?></h5>
                <p class="footer-desc">Your one-stop shop for premium digital products. Quality templates, software, graphics, and courses.</p>
                <div class="footer-social">
                    <a href="#"><i class="bi bi-twitter-x"></i></a>
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            <div class="col-md-2">
                <h6>Quick Links</h6>
                <ul class="footer-links">
                    <li><a href="<?= SITE_URL ?>/">Home</a></li>
                    <li><a href="<?= SITE_URL ?>/products.php">Products</a></li>
                    <li><a href="<?= SITE_URL ?>/faq.php">FAQ</a></li>
                    <li><a href="<?= SITE_URL ?>/contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6>Account</h6>
                <ul class="footer-links">
                    <li><a href="<?= SITE_URL ?>/login.php">Login</a></li>
                    <li><a href="<?= SITE_URL ?>/signup.php">Sign Up</a></li>
                    <li><a href="<?= SITE_URL ?>/orders.php">My Orders</a></li>
                    <li><a href="<?= SITE_URL ?>/profile.php">Profile</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Newsletter</h6>
                <p class="footer-desc">Get updates on new products and exclusive deals.</p>
                <form class="newsletter-form" onsubmit="return false;">
                    <input type="email" placeholder="Enter your email" class="form-control">
                    <button class="btn btn-primary" type="submit">Subscribe</button>
                </form>
            </div>
        </div>
        <hr>
        <div class="footer-bottom">
            <p><?= getSetting('footer_text', '© 2026 Local Link. All rights reserved.') ?></p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
