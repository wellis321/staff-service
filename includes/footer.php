    </main>
    <footer>
        <div class="container">
            <div class="footer-section">
                <a href="<?php echo Auth::isLoggedIn() ? url('index.php') : url('landing.php'); ?>" class="footer-logo-link">
                    <img src="<?php echo url('assets/images/New White Logo.png'); ?>" alt="<?php echo APP_NAME; ?>" class="footer-logo">
                </a>
                <h3 style="color: white; margin-top: 0.5rem; margin-bottom: 1rem;"><?php echo APP_NAME; ?></h3>
                <p style="color: #9ca3af; line-height: 1.6;">
                    People Management Service for social care providers. 
                    Centralised staff and people management system.
                </p>
            </div>
            
            <div class="footer-section">
                <h3>Our Platform</h3>
                <ul>
                    <li><a href="<?php echo url('services.php'); ?>">All Services</a></li>
                    <?php if (defined('TEAM_SERVICE_URL') && TEAM_SERVICE_URL): ?>
                    <li><a href="<?php echo htmlspecialchars(TEAM_SERVICE_URL); ?>/landing.php" target="_blank" rel="noopener"><i class="fas fa-people-group" style="color:#2563eb;margin-right:.3rem"></i> Team Service</a></li>
                    <?php endif; ?>
                    <?php if (defined('PEOPLE_SERVICE_URL') && PEOPLE_SERVICE_URL): ?>
                    <li><a href="<?php echo htmlspecialchars(PEOPLE_SERVICE_URL); ?>/landing.php" target="_blank" rel="noopener"><i class="fas fa-heart-pulse" style="color:#7c3aed;margin-right:.3rem"></i> People Service</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo url('api/staff-data.php'); ?>">Staff API</a></li>
                    <li><a href="<?php echo url('contact.php'); ?>">Support</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Legal</h3>
                <ul>
                    <li><a href="<?php echo url('privacy.php'); ?>">Privacy Policy</a></li>
                    <li><a href="<?php echo url('terms.php'); ?>">Terms & Conditions</a></li>
                    <li><a href="<?php echo url('contact.php'); ?>">Contact</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Cookie Banner -->
    <script src="<?php echo url('assets/js/cookie-banner.js'); ?>?v=<?php echo filemtime(dirname(__DIR__) . '/public/assets/js/cookie-banner.js'); ?>"></script>
</body>
</html>

