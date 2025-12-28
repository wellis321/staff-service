    </main>
    <footer>
        <div class="container">
            <div class="footer-section">
                <img src="<?php echo url('assets/images/New White Logo.png'); ?>" alt="<?php echo APP_NAME; ?>" class="footer-logo">
                <h3 style="color: white; margin-top: 0.5rem; margin-bottom: 1rem;"><?php echo APP_NAME; ?></h3>
                <p style="color: #9ca3af; line-height: 1.6;">
                    People Management Service for social care providers. 
                    Centralised staff and people management system.
                </p>
            </div>
            
            <div class="footer-section">
                <h3>Resources</h3>
                <ul>
                    <li><a href="<?php echo url('docs.php'); ?>">Documentation</a></li>
                    <li><a href="<?php echo url('api/staff-data.php'); ?>">API Documentation</a></li>
                    <li><a href="<?php echo url('security.php'); ?>">Security & Privacy</a></li>
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

