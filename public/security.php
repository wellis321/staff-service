<?php
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Security & Privacy';

include INCLUDES_PATH . '/header.php';
?>

<style>
.security-hero {
    background: white;
    padding: 4rem 0;
    margin: 2rem 0 3rem 0;
    width: 100%;
}

.security-hero-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

.security-hero-text {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.security-hero-label {
    font-size: 0.875rem;
    color: #2563eb;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.5rem;
}

.security-hero h1 {
    font-size: 3rem;
    margin-bottom: 0;
    color: #1e40af;
    font-weight: 700;
    line-height: 1.2;
}

.security-hero p {
    font-size: 1.125rem;
    color: #4b5563;
    line-height: 1.7;
    margin: 0;
}

.security-hero-cta {
    margin-top: 1rem;
}

.security-hero-cta a {
    display: inline-block;
    background: #10b981;
    color: white;
    padding: 0.875rem 2rem;
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    transition: background 0.2s;
}

.security-hero-cta a:hover {
    background: #059669;
}

.security-hero-image {
    width: 100%;
    height: 500px;
    object-fit: cover;
    border-radius: 0.75rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

@media (max-width: 968px) {
    .security-hero {
        padding: 2rem 0;
        margin: 2rem 0 2rem 0;
    }
    
    .security-hero-content {
        grid-template-columns: 1fr;
        gap: 2rem;
        padding: 0 20px;
    }
    
    .security-hero h1 {
        font-size: 2.25rem;
    }
    
    .security-hero p {
        font-size: 1rem;
    }
    
    .security-hero-image {
        height: 300px;
    }
    
    .security-container {
        padding: 0 20px;
    }
    
    .security-section {
        padding: 1.5rem;
    }
    
    .security-section h2 {
        font-size: 1.75rem;
    }
    
    .security-features-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

.security-container {
    max-width: 1000px;
    margin: 0 auto;
}

.security-section {
    margin-bottom: 3rem;
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.security-section h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: #1f2937;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e5e7eb;
}

.security-section h3 {
    font-size: 1.5rem;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: #374151;
}

.security-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin: 1.5rem 0;
}

.security-feature {
    background: white;
    padding: 1.5rem;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s;
}

.security-feature:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.security-feature h4 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.security-feature h4 i {
    color: #2563eb;
}

.security-feature p {
    color: #4b5563;
    line-height: 1.6;
    margin: 0;
}

.security-badge {
    display: inline-block;
    background: #10b981;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.security-content-block {
    margin-bottom: 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.security-content-block p {
    color: #4b5563;
    line-height: 1.8;
    font-size: 1.125rem;
    margin-bottom: 1rem;
}

.security-content-block p:last-child {
    margin-bottom: 0;
}

.security-image-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: stretch;
    margin: 2rem 0;
}

.security-image-section.reverse {
    direction: rtl;
}

.security-image-section.reverse > * {
    direction: ltr;
}

.security-image-section img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.security-image-wrapper {
    display: flex;
    align-items: stretch;
    min-height: 100%;
}

.security-image-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

@media (max-width: 968px) {
    .security-image-section {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .security-image-section.reverse {
        direction: ltr;
    }
    
    .security-image-placeholder {
        height: 250px;
        font-size: 3rem;
    }
    
    .security-section-hero-image {
        height: 250px;
    }
    
    .security-org-slider {
        height: 500px;
    }
    
    .security-org-slide-content {
        padding: 2rem;
    }
    
    .security-org-slide h3 {
        font-size: 2rem;
    }
    
    .security-org-slide p {
        font-size: 1rem;
    }
    
    .security-org-nav {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
    
    .security-org-nav.prev {
        left: 1rem;
    }
    
    .security-org-nav.next {
        right: 1rem;
    }
    
    .security-hero-cta a {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
    }
    
    .contact-cta {
        margin: 2rem 1rem 3rem 1rem;
        padding: 1.5rem;
    }
    
    .contact-cta h3 {
        font-size: 1.5rem;
    }
    
    .contact-cta p {
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    .security-hero h1 {
        font-size: 1.75rem;
    }
    
    .security-hero p {
        font-size: 0.9rem;
    }
    
    .security-hero-image {
        height: 250px;
    }
    
    .security-section h2 {
        font-size: 1.5rem;
    }
    
    .security-section h3 {
        font-size: 1.25rem;
    }
    
    .security-org-slider {
        height: 400px;
    }
    
    .security-org-slide-content {
        padding: 1.5rem;
        margin-left: 0;
    }
    
    .security-org-slide h3 {
        font-size: 1.5rem;
    }
    
    .security-org-slide p {
        font-size: 0.9rem;
    }
    
    .security-org-nav {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
    
    .security-org-nav.prev {
        left: 0.5rem;
    }
    
    .security-org-nav.next {
        right: 0.5rem;
    }
    
    .security-org-dots {
        bottom: 1rem;
    }
}

.security-image-placeholder {
    width: 100%;
    height: 300px;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2563eb;
    font-size: 4rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.security-section-hero-image {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 0.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.security-org-slider {
    position: relative;
    width: 100%;
    height: 600px;
    overflow: hidden;
    border-radius: 0.75rem;
    margin-top: 2rem;
}

.security-org-slides {
    display: flex;
    width: 300%;
    height: 100%;
    transition: transform 0.5s ease-in-out;
}

.security-org-slide {
    width: 33.333%;
    height: 100%;
    position: relative;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}

.security-org-slide::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(30, 64, 175, 0.3) 0%, rgba(37, 99, 235, 0.25) 100%);
    z-index: 1;
}

.security-org-slide-content {
    position: relative;
    z-index: 2;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 4rem 4rem 4rem 6rem;
    color: white;
    max-width: 800px;
    margin-left: 30px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 0.75rem;
    backdrop-filter: blur(3px);
}

.security-org-slide h3 {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    color: white;
    font-weight: 700;
}

.security-org-slide p {
    color: rgba(255, 255, 255, 0.95);
    line-height: 1.8;
    font-size: 1.125rem;
    margin-bottom: 1rem;
}

.security-org-slide p:last-child {
    margin-bottom: 0;
}

.security-org-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #2563eb;
    z-index: 10;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.security-org-nav:hover {
    background: white;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.security-org-nav.prev {
    left: 2rem;
}

.security-org-nav.next {
    right: 2rem;
}

.security-org-dots {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.75rem;
    z-index: 10;
}

.security-org-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    border: 2px solid white;
    cursor: pointer;
    transition: all 0.3s;
}

.security-org-dot.active {
    background: white;
    transform: scale(1.2);
}

.contact-cta {
    background: #eff6ff;
    border: 2px solid #2563eb;
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    margin-top: 3rem;
    margin-bottom: 4rem;
}

.contact-cta h3 {
    color: #1e40af;
    margin-bottom: 1rem;
}

.contact-cta p {
    color: #4b5563;
    margin-bottom: 1.5rem;
}

.contact-cta a {
    display: inline-block;
    background: #2563eb;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 0.375rem;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.2s;
}

.contact-cta a:hover {
    background: #1d4ed8;
}
</style>

<div class="security-hero">
    <div class="security-hero-content">
        <div class="security-hero-text">
            <div class="security-hero-label">Security</div>
            <h1>Enterprise-Grade Security for Your Staff Data</h1>
            <p>Your data security and privacy are our top priorities. The Staff Service is built with enterprise-grade security features to protect your organisation's sensitive information. We implement industry-standard security practices to ensure your data remains safe, secure, and under your control.</p>
            <div class="security-hero-cta">
                <a href="#core-features">Explore Security Features</a>
            </div>
        </div>
        <div>
            <img src="<?php echo url('assets/images/security/enterprise-grade.jpeg'); ?>" alt="Enterprise-grade security" class="security-hero-image">
        </div>
    </div>
</div>

<div class="security-container">
    <section class="security-section" id="core-features">
        <h2>Core Security Features</h2>
        
        <div class="security-features-grid">
            <div class="security-feature">
                <h4><i class="fas fa-shield-alt"></i> Enterprise Authentication</h4>
                <p>Strong password requirements, secure password storage using bcrypt, and secure session management with HttpOnly cookies.</p>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-lock"></i> Multi-Tenant Isolation</h4>
                <p>Complete data separation between organisations. Zero cross-organisation access, even for administrators.</p>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-ban"></i> CSRF Protection</h4>
                <p>All forms protected against Cross-Site Request Forgery attacks with token-based validation.</p>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-database"></i> SQL Injection Prevention</h4>
                <p>All database queries use parameterized prepared statements, eliminating SQL injection risks.</p>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-code"></i> XSS Protection</h4>
                <p>All user-generated content properly escaped and validated to prevent Cross-Site Scripting attacks.</p>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-file-upload"></i> Secure File Uploads</h4>
                <p>File type validation, size limits, directory traversal protection, and access control for all uploads.</p>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-key"></i> API Security</h4>
                <p>Secure API key authentication with key hashing, expiration support, and usage tracking.</p>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-user-shield"></i> Role-Based Access</h4>
                <p>Granular permissions ensure users only access what they need based on their role.</p>
            </div>
            
            <div class="security-feature">
                <h4><i class="fas fa-tachometer-alt"></i> Rate Limiting</h4>
                <p>Protection against brute force attacks and API abuse with intelligent rate limiting on authentication endpoints and API calls.</p>
            </div>
        </div>
    </section>

    <section class="security-section">
        <h2>Data Ownership & Control</h2>
        
        <div class="security-image-section">
            <div class="security-content-block">
                <p>You maintain complete ownership and control over all your data. There's no vendor lock-in, no external dependencies that could compromise your information. When you use the Staff Service, your data stays yours - you can export it at any time in standard formats, and you have the option to deploy the system on your own infrastructure for even greater control.</p>
                <p>Every organisation's data is completely isolated at the database level. This means that even if you're sharing the same system with other organisations, there's zero possibility of cross-organisation data access. Staff from one organisation cannot see, access, or modify data from another organisation, even if they're administrators. This isolation is enforced at the database query level, not just in the application interface, providing an extra layer of security.</p>
            </div>
            <div class="security-image-wrapper">
                <img src="<?php echo url('assets/images/security/dat-ownershipp-and-control.jpeg'); ?>" alt="Data ownership and control" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            </div>
        </div>
    </section>

    <section class="security-section">
        <h2>Audit & Compliance</h2>
        
        <div class="security-image-section reverse">
            <div class="security-image-wrapper">
                <img src="<?php echo url('assets/images/security/audit and complaince.jpeg'); ?>" alt="Audit and compliance" style="width: 100%; height: 100%; object-fit: cover; border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            </div>
            <div class="security-content-block">
                <p>We understand that compliance and audit requirements are critical for organisations handling sensitive staff data. That's why the Staff Service includes comprehensive tracking and logging capabilities. Every change to a staff profile is tracked, creating a complete audit trail that shows what was changed, when it was changed, and who made the change.</p>
                <p>Access logging ensures you can track who accessed what data and when, which is essential for compliance reporting and security audits. API usage is also logged, allowing you to monitor how external systems are accessing your data. The system is designed with GDPR principles in mind, helping you meet your data protection obligations while maintaining the flexibility to work the way your organisation needs.</p>
            </div>
        </div>
    </section>

    <section class="security-section">
        <h2>Security Best Practices</h2>
        
        <img src="<?php echo url('assets/images/security/Security Best Practices.jpeg'); ?>" alt="Security best practices" class="security-section-hero-image">
        
        <div class="security-content-block">
            <p>The Staff Service implements security best practices aligned with industry standards, providing protection against the most common web application vulnerabilities identified in the OWASP Top 10. This means you're protected against SQL injection, cross-site scripting, cross-site request forgery, and other common attack vectors that could compromise your data.</p>
            <p>Security is built into the system from the ground up, with environment-based security settings that automatically adapt based on whether you're running in development or production. Secure defaults are applied throughout, so even if you don't configure every setting, you're still protected. All user inputs are validated on the server side, and all outputs are properly encoded based on their context - whether that's HTML, JSON, or other formats.</p>
            <p>Error handling is designed to be secure as well. In production, detailed error messages are hidden from users to prevent information disclosure that could aid attackers. Instead, errors are logged server-side where they can be reviewed by administrators, while users see friendly, generic messages that don't reveal system internals.</p>
        </div>
    </section>

    <section class="security-section">
        <h2>For Your Organisation</h2>
        
        <div class="security-org-slider">
            <div class="security-org-slides" id="orgSlides">
                <div class="security-org-slide" style="background-image: url('<?php echo url('assets/images/security/slider/slide-1.jpeg'); ?>');">
                    <div class="security-org-slide-content">
                        <h3>IT & Technical Teams</h3>
                        <p>For IT and technical teams, the Staff Service reduces the security burden by providing built-in security features that would otherwise require custom implementation. Instead of spending time building authentication systems, CSRF protection, and input validation from scratch, you can focus on configuring the system to meet your organisation's specific needs.</p>
                        <p>The security features are designed to support regulatory compliance requirements, making it easier to demonstrate that you're meeting your obligations. Comprehensive logging and access controls mean you're always audit-ready, with detailed records of who accessed what data and when. This makes security audits straightforward, as all the information you need is already being tracked.</p>
                    </div>
                </div>
                
                <div class="security-org-slide" style="background-image: url('<?php echo url('assets/images/security/slider/slide 2.jpeg'); ?>');">
                    <div class="security-org-slide-content">
                        <h3>Data Protection Officers</h3>
                        <p>Data Protection Officers will appreciate the complete data isolation between organisations, which directly supports data protection requirements. The granular access controls allow you to demonstrate that you have appropriate measures in place to protect personal data, with clear audit trails showing who has access to what information.</p>
                        <p>Comprehensive logging provides the documentation you need for compliance reporting, showing exactly how data is being accessed and used. The system is designed with GDPR principles in mind, helping you meet your data protection obligations while maintaining the operational flexibility your organisation needs.</p>
                    </div>
                </div>
                
                <div class="security-org-slide" style="background-image: url('<?php echo url('assets/images/security/slider/slide 3.jpeg'); ?>');">
                    <div class="security-org-slide-content">
                        <h3>End Users</h3>
                        <p>For staff members using the system, security means peace of mind. Strong password requirements protect your account from unauthorised access, while privacy protection ensures you can only see and edit your own data. This means you don't have to worry about accidentally seeing someone else's information, or about others seeing yours.</p>
                        <p>When you upload photos or signatures, you can be confident that the system validates the files to ensure they're safe. The file upload process includes type checking, size limits, and security scanning to prevent malicious files from being uploaded. Your data is protected at every step, giving you confidence that your personal information is secure.</p>
                    </div>
                </div>
            </div>
            
            <button class="security-org-nav prev" onclick="changeOrgSlide(-1)" aria-label="Previous slide">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="security-org-nav next" onclick="changeOrgSlide(1)" aria-label="Next slide">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <div class="security-org-dots">
                <span class="security-org-dot active" onclick="goToOrgSlide(0)"></span>
                <span class="security-org-dot" onclick="goToOrgSlide(1)"></span>
                <span class="security-org-dot" onclick="goToOrgSlide(2)"></span>
            </div>
        </div>
    </section>
    
    <script>
    let currentOrgSlide = 0;
    const totalOrgSlides = 3;
    let autoAdvanceInterval = null;
    let isPaused = false;
    
    function updateOrgSlider() {
        const slides = document.getElementById('orgSlides');
        const dots = document.querySelectorAll('.security-org-dot');
        
        slides.style.transform = `translateX(-${currentOrgSlide * 33.333}%)`;
        
        dots.forEach((dot, index) => {
            if (index === currentOrgSlide) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }
    
    function changeOrgSlide(direction) {
        currentOrgSlide += direction;
        
        if (currentOrgSlide < 0) {
            currentOrgSlide = totalOrgSlides - 1;
        } else if (currentOrgSlide >= totalOrgSlides) {
            currentOrgSlide = 0;
        }
        
        updateOrgSlider();
        // Pause auto-advance when user manually navigates
        pauseAutoAdvance();
        // Resume after 20 seconds of inactivity
        setTimeout(() => {
            if (!isPaused) {
                startAutoAdvance();
            }
        }, 20000);
    }
    
    function goToOrgSlide(index) {
        currentOrgSlide = index;
        updateOrgSlider();
        // Pause auto-advance when user manually navigates
        pauseAutoAdvance();
        // Resume after 20 seconds of inactivity
        setTimeout(() => {
            if (!isPaused) {
                startAutoAdvance();
            }
        }, 20000);
    }
    
    function startAutoAdvance() {
        if (autoAdvanceInterval) {
            clearInterval(autoAdvanceInterval);
        }
        autoAdvanceInterval = setInterval(() => {
            if (!isPaused) {
                changeOrgSlide(1);
            }
        }, 15000);
    }
    
    function pauseAutoAdvance() {
        if (autoAdvanceInterval) {
            clearInterval(autoAdvanceInterval);
            autoAdvanceInterval = null;
        }
    }
    
    // Pause on hover
    const slider = document.querySelector('.security-org-slider');
    if (slider) {
        slider.addEventListener('mouseenter', () => {
            isPaused = true;
            pauseAutoAdvance();
        });
        
        slider.addEventListener('mouseleave', () => {
            isPaused = false;
            startAutoAdvance();
        });
    }
    
    // Start auto-advance on page load
    startAutoAdvance();
    </script>

    <div class="contact-cta">
        <h3>Have Security Questions?</h3>
        <p>We believe in security transparency. If you have questions about our security practices or need more information, please get in touch.</p>
        <a href="<?php echo url('contact.php'); ?>">Contact Us</a>
    </div>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>

