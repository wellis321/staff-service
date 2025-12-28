<?php
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Staff Service - Take Control of Your Staff Data';

// Don't require login for landing page
include INCLUDES_PATH . '/header.php';
?>

<style>
.hero {
    background: white;
    padding: 4rem 0;
    margin: 6rem 0 3rem 0;
}

.hero-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: stretch;
    min-height: 600px;
}

.hero-text {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    justify-content: center;
    height: 600px;
}

.hero h1 {
    font-size: 3rem;
    margin-bottom: 0;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: #4b5563;
    line-height: 1.6;
}

.hero-features {
    list-style: none;
    padding: 0;
    margin: 1.5rem 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.hero-features li {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: #374151;
    font-size: 1rem;
    line-height: 1.6;
}

.hero-features li i {
    color: #2563eb;
    font-size: 1.25rem;
    margin-top: 0.125rem;
    flex-shrink: 0;
}

.hero-features li:nth-child(2) i,
.hero-features li:nth-child(4) i {
    color: #10b981;
}

.hero-cta {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.hero-image {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    overflow: hidden;
    height: 600px;
}

.hero-image-img {
    width: 100%;
    height: 600px;
    object-fit: cover;
    border-radius: 1rem;
}

.btn-hero {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 0.5rem;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}

.btn-hero-primary {
    background: white;
    color: #2563eb;
}

.btn-hero-primary:hover {
    background: #f3f4f6;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-hero-green {
    background: #10b981;
    color: white;
}

.btn-hero-green:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-hero-secondary {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.btn-hero-secondary:hover {
    background: rgba(255,255,255,0.1);
    transform: translateY(-2px);
}

.features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    margin: 4rem 0;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 20px;
}

/* Feature Slider Styles */
.feature-slider-wrapper {
    max-width: 1200px;
    margin: 4rem auto;
    padding: 0 20px;
}

.feature-slider {
    position: relative;
    width: 100%;
    height: 600px;
    overflow: hidden;
    border-radius: 0.75rem;
}

.feature-slides {
    display: flex;
    width: 300%;
    height: 100%;
    transition: transform 0.5s ease-in-out;
    will-change: transform;
}

.feature-slide {
    width: 33.333333%;
    height: 100%;
    position: relative;
    flex-shrink: 0;
    background-size: cover;
    background-position: center center;
    background-repeat: no-repeat;
    overflow: hidden;
}

.feature-slide::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(30, 64, 175, 0.3) 0%, rgba(37, 99, 235, 0.25) 100%);
    z-index: 1;
}

.feature-slide-content {
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

.feature-slide h3 {
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    color: white;
    font-weight: 700;
}

.feature-slide p {
    color: rgba(255, 255, 255, 0.95);
    line-height: 1.8;
    font-size: 1.125rem;
    margin-bottom: 1rem;
}

.feature-slide p:last-child {
    margin-bottom: 0;
}

.feature-nav {
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

.feature-nav:hover {
    background: white;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.feature-nav.prev {
    left: 2rem;
}

.feature-nav.next {
    right: 2rem;
}

.feature-dots {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.75rem;
    z-index: 10;
}

.feature-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    border: 2px solid white;
    cursor: pointer;
    transition: all 0.3s;
}

.feature-dot.active {
    background: white;
    transform: scale(1.2);
}

.feature-card {
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.feature-icon {
    font-size: 3rem;
    color: #2563eb;
    margin-bottom: 1rem;
}

.feature-card:nth-child(2) .feature-icon,
.feature-card:nth-child(5) .feature-icon {
    color: #10b981;
}

.feature-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #1f2937;
}

.feature-card p {
    color: #6b7280;
    line-height: 1.7;
}

.comparison-section {
    background: white;
    padding: 4rem 0;
    margin: 2rem 0;
}

.feature-hero-section {
    background: white;
    padding: 5rem 0;
    margin: 0;
}

.feature-hero-section:nth-child(even) {
    background: #f9fafb;
}

.feature-hero-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: stretch;
}

.feature-hero-text {
    order: 1;
}

.feature-hero-image {
    order: 2;
}

.feature-hero-reverse .feature-hero-text {
    order: 2;
}

.feature-hero-reverse .feature-hero-image {
    order: 1;
}

.feature-hero-text {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    justify-content: center;
    min-height: 500px;
    height: auto;
    overflow: visible;
}

.feature-hero-text h2 {
    font-size: 2.5rem;
    margin: 0;
    color: #1f2937;
    font-weight: 700;
}

.feature-hero-text p {
    color: #4b5563;
    line-height: 1.8;
    font-size: 1.125rem;
    margin: 0;
    overflow-wrap: break-word;
    word-wrap: break-word;
}

.feature-hero-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 500px;
}

.feature-hero-img {
    width: 100%;
    max-width: 500px;
    height: 500px;
    object-fit: cover;
    border-radius: 1rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.gdpr-section .gdpr-content {
    display: block;
}

.gdpr-section h2 {
    font-size: 2.5rem;
    margin: 0 0 2rem 0;
    color: #1f2937;
    font-weight: 700;
}

.gdpr-image-wrapper {
    float: right;
    margin: 0 0 1.5rem 2rem;
    width: 400px;
    max-width: 40%;
}

.gdpr-float-image {
    width: 100%;
    height: auto;
    border-radius: 1rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.gdpr-text-content {
    overflow: hidden;
}

.gdpr-text-content p {
    color: #4b5563;
    line-height: 1.8;
    font-size: 1.125rem;
    margin-bottom: 1.5rem;
}

.gdpr-text-content p:last-child {
    margin-bottom: 0;
}

.comparison-section-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.comparison-section h2 {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 3rem;
    margin-top: 0;
    color: #1f2937;
}

.comparison-item {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: start;
    margin-bottom: 4rem;
}

.comparison-item:last-child {
    margin-bottom: 0;
}

.comparison-item.reverse {
    direction: rtl;
}

.comparison-item.reverse > * {
    direction: ltr;
}

.comparison-item-image {
    width: 100%;
    min-height: 500px;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.comparison-item-img {
    width: 100%;
    min-height: 500px;
    height: auto;
    object-fit: cover;
    border-radius: 0.75rem;
}

.comparison-item-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    justify-content: flex-start;
}

.comparison-item-content h3 {
    font-size: 2rem;
    margin: 0;
    color: #1f2937;
    font-weight: 700;
}

.comparison-item-content p {
    color: #4b5563;
    line-height: 1.8;
    font-size: 1.125rem;
    margin: 0;
    overflow-wrap: break-word;
    word-wrap: break-word;
}

.cta-section {
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    color: white;
    padding: 4rem 0;
    text-align: center;
    margin: 4rem 0;
}

.cta-section h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.cta-section p {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.95;
}

@media (max-width: 968px) {
    .hero-content {
        grid-template-columns: 1fr;
        gap: 2rem;
        min-height: auto;
    }
    
    .hero-text {
        height: auto;
    }
    
    .hero-image {
        order: -1;
        height: auto;
        min-height: 300px;
    }
    
    .hero-image-img {
        height: auto;
        min-height: 300px;
    }
    
    .hero h1 {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.125rem;
    }
    
    .comparison-item {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .comparison-item.reverse {
        direction: ltr;
    }
    
    .comparison-item-image {
        height: auto;
        min-height: 250px;
    }
    
    .comparison-item-img {
        height: auto;
        min-height: 250px;
    }
    
    .comparison-item-content {
        height: auto;
    }
    
    .comparison-item-content h3 {
        font-size: 1.75rem;
    }
    
    .comparison-item-content p {
        font-size: 1rem;
    }
    
    .feature-hero-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .feature-hero-text {
        height: auto;
    }
    
    .feature-hero-image {
        height: auto;
    }
    
    .feature-hero-img {
        height: auto;
        min-height: 300px;
    }
    
    .feature-hero-reverse .feature-hero-content {
        direction: ltr;
    }
    
    .feature-hero-text h2 {
        font-size: 2rem;
    }
    
    .feature-hero-text p {
        font-size: 1rem;
    }
    
    .feature-hero-img {
        height: 300px;
    }
    
    .gdpr-image-wrapper {
        float: none;
        margin: 0 0 2rem 0;
        width: 100%;
        max-width: 100%;
    }
    
    .gdpr-section h2 {
        font-size: 2rem;
    }
    
    .gdpr-text-content p {
        font-size: 1rem;
    }
    
    .hero-cta {
        flex-direction: column;
    }
    
    .btn-hero {
        width: 100%;
        text-align: center;
    }
    
    .cta-section h2 {
        font-size: 2rem;
    }
    
    .cta-section p {
        font-size: 1.125rem;
    }
}

@media (max-width: 968px) {
    .feature-slider-wrapper {
        padding: 0 20px;
    }
    
    .feature-slider {
        height: 500px;
    }
    
    .feature-slide-content {
        padding: 2rem;
    }
    
    .feature-slide h3 {
        font-size: 2rem;
    }
    
    .feature-slide p {
        font-size: 1rem;
    }
    
    .feature-nav {
        width: 40px;
        height: 40px;
        font-size: 1.25rem;
    }
    
    .feature-nav.prev {
        left: 1rem;
    }
    
    .feature-nav.next {
        right: 1rem;
    }
}

@media (max-width: 768px) {
    .features {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .feature-slider {
        height: 400px;
    }
    
    .feature-slide-content {
        padding: 1.5rem;
        margin-left: 0;
    }
    
    .feature-slide h3 {
        font-size: 1.5rem;
    }
    
    .feature-slide p {
        font-size: 0.9rem;
    }
    
    .feature-nav {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
    
    .feature-nav.prev {
        left: 0.5rem;
    }
    
    .feature-nav.next {
        right: 0.5rem;
    }
}

@media (max-width: 480px) {
    .hero h1 {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1rem;
    }
    
    .comparison-section h2 {
        font-size: 2rem;
    }
    
    .comparison-item-content h3 {
        font-size: 1.5rem;
    }
    
    .feature-hero-text h2 {
        font-size: 1.75rem;
    }
    
    .gdpr-section h2 {
        font-size: 1.75rem;
    }
    
    .cta-section {
        padding: 3rem 1rem;
    }
    
    .cta-section h2 {
        font-size: 1.75rem;
    }
    
    .cta-section p {
        font-size: 1rem;
    }
    
    .hero {
        padding: 2rem 0;
        margin: 1rem 0 2rem 0;
    }
    
    .feature-hero-section {
        padding: 3rem 1rem;
    }
}
</style>

<div class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <h1>Staff Service for Social Care Providers</h1>
            <p class="hero-subtitle">Centralised staff management designed for organisations where data ownership is critical. <strong>You're in control</strong> - set up your workflows and systems to meet your needs, not the other way around. Take control of your staff information and feed other systems without duplication or vendor lock-in. Integrate with existing HR, rota, and recruitment systems via API or MCP for seamless data synchronisation.</p>
            
            <ul class="hero-features">
                <li>
                    <i class="fas fa-database"></i>
                    <span><strong>Single source of truth</strong> - One central database you own and control</span>
                </li>
                <li>
                    <i class="fas fa-plug"></i>
                    <span><strong>API & MCP integration</strong> - Connect with existing systems without duplication</span>
                </li>
                <li>
                    <i class="fas fa-shield-alt"></i>
                    <span><strong>Complete data ownership</strong> - Your data stays yours, no vendor lock-in</span>
                </li>
                <li>
                    <i class="fas fa-cogs"></i>
                    <span><strong>Workflows that work for you</strong> - Configure systems to meet your needs, not the other way around</span>
                </li>
                <li>
                    <i class="fas fa-sync"></i>
                    <span><strong>Bidirectional sync</strong> - Keep all systems in sync automatically</span>
                </li>
                <li>
                    <i class="fas fa-link"></i>
                    <span><strong>Persistent learning history</strong> - Link staff records to preserve training and skills across role changes</span>
                </li>
            </ul>
            
            <div class="hero-cta">
                <?php if (Auth::isLoggedIn()): ?>
                    <a href="<?php echo url('index.php'); ?>" class="btn-hero btn-hero-green">Go to Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo url('request-access.php'); ?>" class="btn-hero btn-hero-green">Request Organisation Access</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="hero-image">
            <img src="<?php echo url('assets/images/home/staff.jpeg'); ?>" alt="Staff management" class="hero-image-img">
        </div>
    </div>
</div>

<div class="container">
    <section class="comparison-section">
        <div class="comparison-section-content">
            <h2>The Challenge and The Solution</h2>
            
            <div class="comparison-item old-way">
                <div class="comparison-item-image">
                    <img src="<?php echo url('assets/images/home/pexels-tima-miroshnichenko-6549342.jpeg'); ?>" alt="Fragmented staff data" class="comparison-item-img">
                </div>
                <div class="comparison-item-content">
                    <h3>The Challenge: Systems Dictating Your Workflows</h3>
                    <p>For many organisations, staff information lives scattered across multiple systems. Your HR system has one version of employee data, your rota system has another, and your recruitment platform holds yet another set of information. Each system thinks it's the source of truth, but none of them have the complete picture.</p>
                    <p>This fragmentation creates real problems. When someone's details change, you find yourself updating the same information in three or four different places. There's no single place to see everything about your workforce, and you're often locked into vendor-specific systems where your data becomes trapped.</p>
                    <p>Perhaps most frustratingly, you find yourself bending your workflows to fit what the systems require, rather than having systems that adapt to your needs. You spend time maintaining duplicate records and struggling to get a clear view of your workforce. The data exists, but it's spread out and controlled by systems that don't understand your unique requirements.</p>
                </div>
            </div>
            
            <div class="comparison-item new-way reverse">
                <div class="comparison-item-image">
                    <img src="<?php echo url('assets/images/home/colour-storage-documents.jpeg'); ?>" alt="Central staff database" class="comparison-item-img">
                </div>
                <div class="comparison-item-content">
                    <h3>The Solution: Workflows That Work For You</h3>
                    <p>The Staff Service changes this completely. Instead of your data being scattered across multiple third-party systems, you maintain one central database that you own and control. This becomes your single source of truth for all staff information.</p>
                    <p><strong>Most importantly, you're in control.</strong> You set up your workflows and systems to meet your organisation's specific needs, not the other way around. The Staff Service adapts to how you work. You maintain complete ownership of your data with no vendor lock-in, and you can integrate with any system you need to build the workflows that work best for your organisation.</p>
                    <p>When you need to update someone's details, you do it once in the Staff Service. That change then flows automatically to your other systems via API or MCP integration. Your HR system, rota system, and recruitment platform all consume data from your central database, so they're always in sync without any manual duplication.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="feature-slider-wrapper">
        <div class="feature-slider">
            <div class="feature-slides" id="featureSlides">
                <div class="feature-slide" style="background-image: url('<?php echo url('assets/images/home/slider/new-slide1.jpeg'); ?>');">
                    <div class="feature-slide-content">
                        <h3>Centralised Staff Database</h3>
                        <p>Maintain a complete, up-to-date record of all your staff in one place. From personal details to employment history, qualifications, and registrations.</p>
                        <p>Your single source of truth for all staff information, giving you complete control and visibility over your workforce data.</p>
                    </div>
                </div>
                
                <div class="feature-slide" style="background-image: url('<?php echo url('assets/images/home/slider/new-slide2.jpeg'); ?>');">
                    <div class="feature-slide-content">
                        <h3>API & MCP Integration</h3>
                        <p>Connect seamlessly with your existing systems. Use our API or MCP (Model Context Protocol) to sync data without manual duplication or file transfers.</p>
                        <p>Integrate with HR systems, rota platforms, recruitment tools, and more. One central database feeds all your systems automatically.</p>
                    </div>
                </div>
                
                <div class="feature-slide" style="background-image: url('<?php echo url('assets/images/home/slider/new-slide3.jpeg'); ?>');">
                    <div class="feature-slide-content">
                        <h3>Data Ownership</h3>
                        <p>Your data stays yours. No vendor lock-in, no external dependencies. You control who has access and how your information is used.</p>
                        <p>Complete ownership means you can export your data at any time, integrate with any system, and maintain full control over your staff information.</p>
                    </div>
                </div>
            </div>
            
            <button class="feature-nav prev" onclick="changeFeatureSlide(-1)" aria-label="Previous slide">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="feature-nav next" onclick="changeFeatureSlide(1)" aria-label="Next slide">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <div class="feature-dots">
                <span class="feature-dot active" onclick="goToFeatureSlide(0)"></span>
                <span class="feature-dot" onclick="goToFeatureSlide(1)"></span>
                <span class="feature-dot" onclick="goToFeatureSlide(2)"></span>
            </div>
        </div>
    </section>

    <section class="feature-hero-section">
        <div class="feature-hero-content">
            <div class="feature-hero-text">
                <h2>Staff Self-Service with Verification Workflows</h2>
                <p>Empower your staff to keep their information up to date while maintaining data accuracy and compliance. Staff can update their own details directly through their profile - from names and addresses to bank details and contact information.</p>
                <p>Every change is submitted for verification through built-in workflows that automatically flag updates requiring review. Administrators can approve or request changes, ensuring your data stays current, accurate, and compliant without the administrative burden of manual updates.</p>
            </div>
            <div class="feature-hero-image">
                <img src="<?php echo url('assets/images/home/pexels-diva-plavalaguna-6937667.jpeg'); ?>" alt="Staff self-service" class="feature-hero-img">
            </div>
        </div>
    </section>

    <section class="feature-hero-section feature-hero-reverse">
        <div class="feature-hero-content">
            <div class="feature-hero-image">
                <img src="<?php echo url('assets/images/home/signature.jpg'); ?>" alt="Digital signature capture" class="feature-hero-img">
            </div>
            <div class="feature-hero-text">
                <h2>Digital Signature Capture</h2>
                <p>Capture and store staff signatures digitally for contracts, agreements, and compliance documentation. Staff can upload a signature image or draw their signature directly using our built-in signature pad.</p>
                <p>Signatures are securely stored and can be easily retrieved when needed. Perfect for remote onboarding, digital document workflows, and maintaining a complete audit trail of signed agreements.</p>
            </div>
        </div>
    </section>

    <section class="feature-hero-section">
        <div class="feature-hero-content">
            <div class="feature-hero-text">
                <h2>Registration & Compliance Alerts</h2>
                <p>Never miss a critical deadline with automatic monitoring of professional registrations, qualifications, and compliance requirements. The system tracks expiration dates and renewal timelines, sending timely notifications about upcoming lapses.</p>
                <p>Stay ahead of compliance issues with proactive alerts that give you and your staff plenty of time to renew registrations, update qualifications, and maintain compliance. Reduce risk and ensure your workforce stays qualified and registered.</p>
            </div>
            <div class="feature-hero-image">
                <img src="<?php echo url('assets/images/home/messy-calendar.jpeg'); ?>" alt="Registration and compliance alerts" class="feature-hero-img">
            </div>
        </div>
    </section>

    <section class="features">
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-sync"></i>
            </div>
            <h3>Bidirectional Sync</h3>
            <p>Keep your staff data in sync across all your systems. Update once in the Staff Service, and changes flow to your HR, rota, and recruitment systems.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3>Complete Staff Profiles</h3>
            <p>Store everything you need: personal information, employment details, qualifications, registrations, leave records, contracts, and more.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-link"></i>
            </div>
            <h3>Persistent Learning History</h3>
            <p>Link staff records to preserve training and skills across role changes. Never lose valuable learning data when staff change posts or return to your organisation.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3>Workforce Insights</h3>
            <p>Get a complete picture of your workforce. Track roles, history, qualifications, and compliance all from one central location.</p>
        </div>
    </section>

    <section class="feature-hero-section feature-hero-reverse">
        <div class="feature-hero-content">
            <div class="feature-hero-image">
                <img src="<?php echo url('assets/images/home/microsoft.jpeg'); ?>" alt="Microsoft Entra and 365 integration" class="feature-hero-img">
            </div>
            <div class="feature-hero-text">
                <h2>Microsoft Entra & 365 Integration</h2>
                <p>Seamlessly integrate with Microsoft Entra (formerly Azure AD) and Microsoft 365 to sync user accounts, manage access, and keep your identity management in perfect sync. Connect your staff data with your existing Microsoft ecosystem effortlessly.</p>
                <p>Our flexible API and MCP integration makes connecting with other systems straightforward, whether it's HR platforms, rota systems, recruitment tools, or custom applications. One central database feeds all your systems without duplication or manual data entry.</p>
            </div>
        </div>
    </section>

    <section class="feature-hero-section">
        <div class="feature-hero-content">
            <div class="feature-hero-text">
                <h2>Persistent Learning & Skills History</h2>
                <p>Never lose valuable training and skills data when staff change roles or return to your organisation. The Staff Service allows you to link staff records, preserving learning history and qualifications across different employee numbers and employment periods.</p>
                <p>When a staff member changes post, leaves and rejoins, or gets a new employee number, their previous learning records, qualifications, and skills remain accessible. Administrators can search for learning records by employee reference and link old and new profiles, ensuring complete training history is maintained without duplication.</p>
            </div>
            <div class="feature-hero-image">
                <img src="<?php echo url('assets/images/home/learning.jpeg'); ?>" alt="Persistent learning and skills history" class="feature-hero-img">
            </div>
        </div>
    </section>

    <section class="feature-hero-section gdpr-section">
        <div class="feature-hero-content gdpr-content">
            <h2>GDPR Compliance & Data Retention</h2>
            <div class="gdpr-image-wrapper">
                <img src="<?php echo url('assets/images/security/gdpr.jpeg'); ?>" alt="GDPR compliance and data retention" class="gdpr-float-image">
            </div>
            <div class="gdpr-text-content">
                <p>The Staff Service helps you maintain GDPR compliance with built-in data retention policies and automatic countdown timers. When content is created, the system automatically tracks retention periods based on the type of data, ensuring you know exactly when information should be reviewed, archived, or deleted. Different types of staff data have different retention requirements under GDPR - from employment contracts and disciplinary records to training certificates and performance reviews - and you'll receive timely notifications before retention periods expire.</p>
                <p>Stay compliant with confidence. The Staff Service tracks retention periods for all types of content, giving you clear visibility into what data needs attention and when. Reduce the risk of non-compliance while maintaining the flexibility to extend retention periods when legally required.</p>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <h2>Ready to Take Control?</h2>
        <p>Start managing your staff data the right way - with you in control.</p>
        <?php if (Auth::isLoggedIn()): ?>
            <a href="<?php echo url('index.php'); ?>" class="btn-hero btn-hero-green">Go to Dashboard</a>
        <?php else: ?>
            <a href="<?php echo url('request-access.php'); ?>" class="btn-hero btn-hero-green">Request Organisation Access</a>
        <?php endif; ?>
    </section>
</div>

<script>
// Feature Slider Functionality
let currentFeatureSlide = 0;
const totalFeatureSlides = 3;
let featureAutoAdvanceInterval = null;
let isFeaturePaused = false;

function updateFeatureSlider() {
    const slides = document.getElementById('featureSlides');
    const dots = document.querySelectorAll('.feature-dot');
    
    slides.style.transform = `translateX(-${currentFeatureSlide * 33.333333}%)`;
    
    dots.forEach((dot, index) => {
        if (index === currentFeatureSlide) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}

function changeFeatureSlide(direction) {
    currentFeatureSlide += direction;
    
    if (currentFeatureSlide < 0) {
        currentFeatureSlide = totalFeatureSlides - 1;
    } else if (currentFeatureSlide >= totalFeatureSlides) {
        currentFeatureSlide = 0;
    }
    
    updateFeatureSlider();
    // Pause auto-advance when user manually navigates
    pauseFeatureAutoAdvance();
    // Resume after 20 seconds of inactivity
    setTimeout(() => {
        if (!isFeaturePaused) {
            startFeatureAutoAdvance();
        }
    }, 20000);
}

function goToFeatureSlide(index) {
    currentFeatureSlide = index;
    updateFeatureSlider();
    // Pause auto-advance when user manually navigates
    pauseFeatureAutoAdvance();
    // Resume after 20 seconds of inactivity
    setTimeout(() => {
        if (!isFeaturePaused) {
            startFeatureAutoAdvance();
        }
    }, 20000);
}

function startFeatureAutoAdvance() {
    pauseFeatureAutoAdvance(); // Clear any existing interval
    featureAutoAdvanceInterval = setInterval(() => {
        if (!isFeaturePaused) {
            changeFeatureSlide(1);
        }
    }, 15000);
}

function pauseFeatureAutoAdvance() {
    if (featureAutoAdvanceInterval) {
        clearInterval(featureAutoAdvanceInterval);
        featureAutoAdvanceInterval = null;
    }
}

// Pause on hover
const featureSlider = document.querySelector('.feature-slider');
if (featureSlider) {
    featureSlider.addEventListener('mouseenter', () => {
        isFeaturePaused = true;
        pauseFeatureAutoAdvance();
    });
    
    featureSlider.addEventListener('mouseleave', () => {
        isFeaturePaused = false;
        startFeatureAutoAdvance();
    });
}

// Start auto-advance on page load
startFeatureAutoAdvance();
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>

