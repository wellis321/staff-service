<?php
require_once dirname(__DIR__) . '/config/config.php';

$pageTitle = 'Staff Service - Take Control of Your Staff Data';

// Don't require login for landing page
include INCLUDES_PATH . '/header.php';

$landingCssPath = dirname(__DIR__) . '/public/assets/css/landing-reloca.css';
$landingCssV    = file_exists($landingCssPath) ? filemtime($landingCssPath) : time();
?>

<link rel="stylesheet" href="<?php echo url('assets/css/landing-reloca.css'); ?>?v=<?php echo $landingCssV; ?>">

<div class="landing-reloca">

    <section class="lr-hero lr-hero--reloca landing-reloca__bleed" aria-labelledby="lr-hero-title">
        <div class="landing-reloca__inner">
            <div class="lr-hero__grid">
                <div class="lr-hero__copy">
                    <p class="lr-hero__eyebrow">
                        <span class="lr-hero__eyebrow-dot" aria-hidden="true"></span>
                        <span>One system. Your data.</span>
                    </p>
                    <h1 id="lr-hero-title" class="lr-hero__title">
                        <span class="lr-hero__title-line">Staff information with confidence.</span>
                        <span class="lr-hero__title-line">Every other system</span>
                        <span class="lr-hero__title-line">in sync.</span>
                    </h1>
                    <p class="lr-hero__lead">Personalised support for care and support providers: one staff record you own, with HR, rota, and recruitment connected by API — no duplication, no lock-in.</p>

                    <div class="lr-hero__cta-row">
                        <div class="lr-hero__cta-btns">
                            <?php if (Auth::isLoggedIn()): ?>
                                <a href="<?php echo url('index.php'); ?>" class="lr-btn lr-btn--reloca lr-btn--hero">Go to dashboard</a>
                            <?php else: ?>
                                <a href="<?php echo url('request-access.php'); ?>" class="lr-btn lr-btn--reloca lr-btn--hero">Request organisation access</a>
                                <a href="<?php echo url('login.php'); ?>" class="lr-btn lr-btn--reloca-outline lr-btn--hero">Log in</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="lr-hero__visual">
                    <div class="lr-hero__figure">
                        <img src="<?php echo url('assets/images/home/staff.jpeg'); ?>" alt="" width="800" height="1000" loading="eager">
                    </div>
                    <span class="lr-hero__float lr-hero__float--tl"><i class="fas fa-shield-alt" aria-hidden="true"></i> GDPR-ready</span>
                    <span class="lr-hero__float lr-hero__float--tr"><i class="fas fa-plug" aria-hidden="true"></i> API-first</span>
                    <span class="lr-hero__float lr-hero__float--br"><i class="fas fa-heart-pulse" aria-hidden="true"></i> Social care</span>
                    <div class="lr-hero__social-pill">
                        <span class="lr-hero__avatars" aria-hidden="true">
                            <span class="lr-hero__avatar">A</span>
                            <span class="lr-hero__avatar">B</span>
                            <span class="lr-hero__avatar">C</span>
                        </span>
                        <span class="lr-hero__social-text">Trusted by growing care organisations</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lr-hero-pills landing-reloca__bleed" aria-label="Key capabilities">
        <div class="landing-reloca__inner">
            <ul class="lr-hero__bullets lr-hero__bullets--grid">
                <li><i class="fas fa-database" aria-hidden="true"></i><span><strong>Single source of truth</strong> — one central database you own</span></li>
                <li><i class="fas fa-plug" aria-hidden="true"></i><span><strong>API &amp; integrations</strong> — connect without copying data everywhere</span></li>
                <li><i class="fas fa-shield-alt" aria-hidden="true"></i><span><strong>Your data, your rules</strong> — no lock-in; export and integrate freely</span></li>
                <li><i class="fas fa-cogs" aria-hidden="true"></i><span><strong>Workflows that fit you</strong> — not the other way around</span></li>
                <li><i class="fas fa-sync" aria-hidden="true"></i><span><strong>Bidirectional sync</strong> — updates propagate where you need them</span></li>
                <li><i class="fas fa-link" aria-hidden="true"></i><span><strong>Persistent learning history</strong> — training and skills across role changes</span></li>
            </ul>
        </div>
    </section>

    <section class="lr-trust landing-reloca__bleed" aria-label="Highlights">
        <div class="landing-reloca__inner lr-trust__row">
            <div class="lr-trust__stats" role="list">
                <div class="lr-trust__stat" role="listitem">
                    <strong>API-first</strong>
                    <span>Feed downstream systems</span>
                </div>
                <div class="lr-trust__stat" role="listitem">
                    <strong>Verification</strong>
                    <span>Controlled profile changes</span>
                </div>
                <div class="lr-trust__stat" role="listitem">
                    <strong>Compliance-ready</strong>
                    <span>Retention &amp; registrations</span>
                </div>
            </div>
        </div>
    </section>

    <section class="lr-about-reloca landing-reloca__bleed" aria-labelledby="lr-about-reloca-statement">
        <div class="landing-reloca__inner">
            <div class="lr-about-reloca__top">
                <p class="lr-about-reloca__eyebrow">
                    <span class="lr-about-reloca__eyebrow-dot" aria-hidden="true"></span>
                    About us
                </p>
                <div class="lr-about-reloca__top-body">
                    <h2 class="lr-about-reloca__statement" id="lr-about-reloca-statement">Whether you run specialist care, supported living, or multi-site operations, we provide <span class="lr-about-reloca__accent">a single staff record you own</span> — with integrations, verification workflows, and compliance tooling built for social care.</h2>
                    <a class="lr-about-reloca__btn" href="<?php echo url('services.php'); ?>">More about Staff Service</a>
                </div>
            </div>

            <div class="lr-about-reloca__lower">
                <div class="lr-about-reloca__lower-text">
                    <p class="lr-about-reloca__subline">We support you through every step of your staff data journey.</p>
                    <div class="lr-about-reloca__stat" role="group" aria-label="Key outcome">
                        <span class="lr-about-reloca__stat-num">100%</span>
                        <span class="lr-about-reloca__stat-label">Your data stays under your organisation&rsquo;s control</span>
                    </div>
                </div>
                <div class="lr-about-reloca__photos">
                    <figure class="lr-about-reloca__fig">
                        <img src="<?php echo url('assets/images/home/pexels-diva-plavalaguna-6937667.jpeg'); ?>" alt="Staff working with digital tools" width="600" height="720" loading="lazy">
                    </figure>
                    <figure class="lr-about-reloca__fig">
                        <img src="<?php echo url('assets/images/home/learning.jpeg'); ?>" alt="Learning and professional development" width="600" height="720" loading="lazy">
                    </figure>
                </div>
            </div>
        </div>
    </section>

    <section class="lr-compare" id="challenge">
        <div class="landing-reloca__inner">
            <header class="lr-section-head">
                <span class="lr-kicker">The challenge &amp; the answer</span>
                <h2>From fragmented tools to one place you trust</h2>
                <p>When every system claims to be the source of truth, teams waste time reconciling data. Staff Service is designed to sit at the centre — owned by you.</p>
            </header>

            <div class="lr-compare__item">
                <div class="lr-compare__media">
                    <img src="<?php echo url('assets/images/home/pexels-tima-miroshnichenko-6549342.jpeg'); ?>" alt="Colleagues working with information across multiple systems" width="800" height="600" loading="lazy">
                </div>
                <div class="lr-compare__copy">
                    <h3>The challenge: tools dictating your workflows</h3>
                    <p>Staff information often lives in HR, rota, recruitment, and line-of-business apps — each with a partial record. Updates get repeated; nobody sees the full picture; vendors hold the keys.</p>
                    <p>You end up bending processes to fit software, instead of software supporting how your organisation actually works.</p>
                </div>
            </div>

            <div class="lr-compare__item lr-compare__item--flip">
                <div class="lr-compare__media">
                    <img src="<?php echo url('assets/images/home/colour-storage-documents.jpeg'); ?>" alt="Organised documents representing a single staff record" width="800" height="600" loading="lazy">
                </div>
                <div class="lr-compare__copy">
                    <h3>The solution: one database you control</h3>
                    <p>Staff Service is your <strong>single source of truth</strong>. Update once; sync to other systems by API. You define access, retention, and workflows — without losing ownership of the data.</p>
                    <p>Connect HR, rota, and recruitment platforms so they consume from the centre, staying aligned without manual duplication.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="lr-services" id="capabilities">
        <div class="lr-svc-top landing-reloca__bleed" aria-labelledby="lr-svc-top-title">
            <div class="landing-reloca__inner">
                <div class="lr-svc-top__wrap">
                    <p class="lr-svc-top__eyebrow">
                        <span class="lr-svc-top__eyebrow-dot" aria-hidden="true"></span>
                        Services
                    </p>
                    <div class="lr-svc-top__body">
                        <h2 class="lr-svc-top__title" id="lr-svc-top-title">Everything you need to run staff data with confidence.</h2>
                        <p class="lr-svc-top__lead">From employment records and professional registrations to API integrations and compliance tooling, we guide you every step of the way — built for social care and support providers.</p>
                        <a class="lr-svc-top__btn" href="<?php echo url('services.php'); ?>">View all services</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="lr-services__cards">
            <div class="landing-reloca__inner">
                <div class="lr-services__grid">
                <article class="lr-svc-card lr-svc-card--pastel-1">
                    <div class="lr-svc-card__layout">
                        <div class="lr-svc-card__body">
                            <span class="lr-svc-card__tag lr-svc-card__tag--blue"><span class="lr-svc-card__tag-dot" aria-hidden="true"></span>Integrations</span>
                            <h3>Bidirectional Sync</h3>
                            <p>Keep your staff data in sync across all your systems. Update once in the Staff Service, and changes flow to your HR, rota, and recruitment systems.</p>
                            <a class="lr-svc-card__link" href="<?php echo url('docs.php'); ?>"><span class="lr-svc-card__link-label">Discover more</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        </div>
                        <div class="lr-svc-card__art">
                            <img src="<?php echo url('assets/images/abstract-2.avif'); ?>" alt="" width="480" height="600" loading="eager" decoding="async">
                        </div>
                    </div>
                </article>
                <article class="lr-svc-card lr-svc-card--pastel-2">
                    <div class="lr-svc-card__layout">
                        <div class="lr-svc-card__body">
                            <span class="lr-svc-card__tag lr-svc-card__tag--slate"><span class="lr-svc-card__tag-dot" aria-hidden="true"></span>Profiles</span>
                            <h3>Complete Staff Profiles</h3>
                            <p>Store everything you need: personal information, employment details, qualifications, registrations, leave records, contracts, and more.</p>
                            <a class="lr-svc-card__link" href="<?php echo url('docs.php'); ?>"><span class="lr-svc-card__link-label">Discover more</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        </div>
                        <div class="lr-svc-card__art">
                            <img src="<?php echo url('assets/images/abstract-3.avif'); ?>" alt="" width="480" height="600" loading="lazy" decoding="async">
                        </div>
                    </div>
                </article>
                <article class="lr-svc-card lr-svc-card--pastel-3">
                    <div class="lr-svc-card__layout">
                        <div class="lr-svc-card__body">
                            <span class="lr-svc-card__tag lr-svc-card__tag--green"><span class="lr-svc-card__tag-dot" aria-hidden="true"></span>Learning</span>
                            <h3>Persistent Learning History</h3>
                            <p>Link staff records to preserve training and skills across role changes. Never lose valuable learning data when staff change posts or return to your organisation.</p>
                            <a class="lr-svc-card__link" href="<?php echo url('services.php'); ?>"><span class="lr-svc-card__link-label">Discover more</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        </div>
                        <div class="lr-svc-card__art">
                            <img src="<?php echo url('assets/images/abstract-1.avif'); ?>" alt="" width="480" height="600" loading="lazy" decoding="async">
                        </div>
                    </div>
                </article>
                <article class="lr-svc-card lr-svc-card--pastel-4">
                    <div class="lr-svc-card__layout">
                        <div class="lr-svc-card__body">
                            <span class="lr-svc-card__tag lr-svc-card__tag--violet"><span class="lr-svc-card__tag-dot" aria-hidden="true"></span>Insights</span>
                            <h3>Workforce Insights</h3>
                            <p>Get a complete picture of your workforce. Track roles, history, qualifications, and compliance all from one central location.</p>
                            <a class="lr-svc-card__link" href="<?php echo url('docs.php'); ?>"><span class="lr-svc-card__link-label">Discover more</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                        </div>
                        <div class="lr-svc-card__art">
                            <img src="<?php echo url('assets/images/abstract-4.avif'); ?>" alt="" width="480" height="600" loading="lazy" decoding="async">
                        </div>
                    </div>
                </article>
                </div>
            </div>
        </div>
    </section>

    <section class="lr-slider-wrap landing-reloca__bleed" aria-label="Feature highlights">
        <div class="landing-reloca__inner">
            <div class="lr-slider" id="lrFeatureSlider">
                <div class="lr-slider__track" id="lrFeatureSlides">
                    <div class="lr-slide">
                        <img class="lr-slide__photo" src="<?php echo url('assets/images/home/slider/new-slide1.jpeg'); ?>" alt="" width="1200" height="800" loading="eager" decoding="async">
                        <div class="lr-slide__inner">
                            <h3>Centralised staff database</h3>
                            <p>Keep a complete, current record for every member of staff — employment, qualifications, and registrations in one place.</p>
                            <p>Administrators get a clear view; integrations read from the same canonical data.</p>
                        </div>
                    </div>
                    <div class="lr-slide">
                        <img class="lr-slide__photo" src="<?php echo url('assets/images/home/slider/new-slide2.jpeg'); ?>" alt="" width="1200" height="800" loading="lazy" decoding="async">
                        <div class="lr-slide__inner">
                            <h3>API &amp; integration-friendly</h3>
                            <p>Connect HR, rota, recruitment, and internal tools without maintaining parallel spreadsheets.</p>
                            <p>Design your architecture so Staff Service remains the hub — other systems stay in sync automatically.</p>
                        </div>
                    </div>
                    <div class="lr-slide">
                        <img class="lr-slide__photo" src="<?php echo url('assets/images/home/slider/new-slide3.jpeg'); ?>" alt="" width="1200" height="800" loading="lazy" decoding="async">
                        <div class="lr-slide__inner">
                            <h3>Data stays yours</h3>
                            <p>No lock-in: export, integrate, and change tools while preserving a durable record of people and skills.</p>
                            <p>Access and usage stay under your organisation’s control.</p>
                        </div>
                    </div>
                </div>
                <button type="button" class="lr-slider__nav lr-slider__nav--prev" onclick="lrChangeSlide(-1)" aria-label="Previous slide">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button type="button" class="lr-slider__nav lr-slider__nav--next" onclick="lrChangeSlide(1)" aria-label="Next slide">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
                <div class="lr-slider__dots" role="tablist" aria-label="Slide indicators">
                    <button type="button" class="lr-slider__dot is-active" onclick="lrGoToSlide(0)" aria-label="Slide 1" aria-current="true"></button>
                    <button type="button" class="lr-slider__dot" onclick="lrGoToSlide(1)" aria-label="Slide 2"></button>
                    <button type="button" class="lr-slider__dot" onclick="lrGoToSlide(2)" aria-label="Slide 3"></button>
                </div>
            </div>
        </div>
    </section>

    <section class="lr-band">
        <div class="landing-reloca__inner">
            <div class="lr-band__grid">
                <div class="lr-band__text">
                    <span class="lr-kicker">Self-service</span>
                    <h2>Profiles staff can update — with verification</h2>
                    <p>Staff keep contact, bank, and profile details fresh through their own flow. Sensitive changes can be routed for approval so quality and compliance stay intact.</p>
                    <p>Less chasing across teams; clearer audit of who changed what, and when.</p>
                </div>
                <div class="lr-band__media">
                    <img src="<?php echo url('assets/images/home/pexels-diva-plavalaguna-6937667.jpeg'); ?>" alt="Professional at a laptop updating information" width="700" height="500" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <section class="lr-band lr-band--alt lr-band--flip">
        <div class="landing-reloca__inner">
            <div class="lr-band__grid">
                <div class="lr-band__text">
                    <span class="lr-kicker">Signatures</span>
                    <h2>Digital signature capture</h2>
                    <p>Capture signatures for contracts and policies with image upload or an on-screen pad — stored against the staff record for retrieval and audit.</p>
                    <p>Supports remote onboarding and paper-light processes without losing evidence.</p>
                </div>
                <div class="lr-band__media">
                    <img src="<?php echo url('assets/images/home/signature.jpg'); ?>" alt="Signing a document digitally" width="700" height="500" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <section class="lr-band">
        <div class="landing-reloca__inner">
            <div class="lr-band__grid">
                <div class="lr-band__text">
                    <span class="lr-kicker">Compliance</span>
                    <h2>Registration &amp; deadline alerts</h2>
                    <p>Monitor professional registrations and key dates. Notifications highlight what is due so managers and staff can renew before lapses become incidents.</p>
                </div>
                <div class="lr-band__media">
                    <img src="<?php echo url('assets/images/home/messy-calendar.jpeg'); ?>" alt="Calendar and planning for compliance dates" width="700" height="500" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <section class="lr-features" aria-label="At a glance">
        <div class="landing-reloca__inner lr-features__inner">
            <div class="lr-feat">
                <div class="lr-feat__icon"><i class="fas fa-sync" aria-hidden="true"></i></div>
                <h3>Bidirectional sync</h3>
                <p>Update the centre; connected systems reflect the change — fewer errors and less double entry.</p>
            </div>
            <div class="lr-feat">
                <div class="lr-feat__icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                <h3>Complete profiles</h3>
                <p>Hold what you need for operations, HR, and assurance in one structured record.</p>
            </div>
            <div class="lr-feat">
                <div class="lr-feat__icon"><i class="fas fa-link" aria-hidden="true"></i></div>
                <h3>Persistent learning history</h3>
                <p>Link records so training and skills survive role changes and returns.</p>
            </div>
            <div class="lr-feat">
                <div class="lr-feat__icon"><i class="fas fa-chart-line" aria-hidden="true"></i></div>
                <h3>Workforce visibility</h3>
                <p>See roles, history, and compliance signals from one place.</p>
            </div>
        </div>
    </section>

    <section class="lr-band lr-band--alt lr-band--flip">
        <div class="landing-reloca__inner">
            <div class="lr-band__grid">
                <div class="lr-band__text">
                    <span class="lr-kicker">Microsoft</span>
                    <h2>Entra &amp; Microsoft 365</h2>
                    <p>Align people data with Entra and 365 where your organisation uses Microsoft for identity and productivity.</p>
                    <p>Combine with API integrations for the rest of your stack — one hub, many consumers.</p>
                </div>
                <div class="lr-band__media">
                    <img src="<?php echo url('assets/images/home/microsoft.jpeg'); ?>" alt="Microsoft 365 and cloud identity" width="700" height="500" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <section class="lr-band">
        <div class="landing-reloca__inner">
            <div class="lr-band__grid">
                <div class="lr-band__text">
                    <span class="lr-kicker">Learning</span>
                    <h2>Persistent learning &amp; skills</h2>
                    <p>When someone changes post or returns with a new employee number, link records so training and qualifications stay discoverable.</p>
                    <p>Reduces repeated induction and protects organisational memory.</p>
                </div>
                <div class="lr-band__media">
                    <img src="<?php echo url('assets/images/home/learning.jpeg'); ?>" alt="Learning and professional development" width="700" height="500" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <section class="lr-quote-section landing-reloca__bleed" aria-labelledby="lr-quote-heading">
        <div class="landing-reloca__inner">
            <header class="lr-section-head">
                <span class="lr-kicker">Teams using Staff Service</span>
                <h2 id="lr-quote-heading">Real workflows. Real control.</h2>
                <p>Illustrative feedback reflecting how operations and HR leads use a central staff hub.</p>
            </header>
            <div class="lr-quotes">
                <blockquote class="lr-quote">
                    <p class="lr-quote__text">“We stopped reconciling three systems every month. One record, clear approvals, and the rotas finally match HR.”</p>
                    <footer class="lr-quote__meta">
                        <div class="lr-quote__avatar" aria-hidden="true">OP</div>
                        <div class="lr-quote__who">
                            <strong>Operations lead</strong>
                            <span>Social care provider, UK</span>
                        </div>
                    </footer>
                </blockquote>
                <blockquote class="lr-quote">
                    <p class="lr-quote__text">“Registration renewals used to live in spreadsheets. Now we get ahead of expiry dates instead of discovering them in an audit.”</p>
                    <footer class="lr-quote__meta">
                        <div class="lr-quote__avatar" aria-hidden="true">HR</div>
                        <div class="lr-quote__who">
                            <strong>HR manager</strong>
                            <span>Support services organisation</span>
                        </div>
                    </footer>
                </blockquote>
            </div>
        </div>
    </section>

    <section class="lr-gdpr">
        <div class="landing-reloca__inner">
            <span class="lr-kicker">Data protection</span>
            <h2>GDPR-aware retention &amp; oversight</h2>
            <div class="lr-gdpr__float">
                <img src="<?php echo url('assets/images/security/gdpr.jpeg'); ?>" alt="Data protection and secure handling of information" width="400" height="300" loading="lazy">
            </div>
            <div class="lr-gdpr__text">
                <p>Staff Service supports retention thinking with policies and countdowns aligned to how long different categories of data should be held. You get visibility before items need review, archive, or deletion — helping teams stay aligned with GDPR expectations.</p>
                <p>Different data types carry different retention needs; the aim is clarity and timely prompts, not surprise discoveries during a request or inspection.</p>
            </div>
        </div>
    </section>

    <section class="lr-cta landing-reloca__bleed" aria-labelledby="lr-cta-title">
        <div class="landing-reloca__inner">
            <h2 id="lr-cta-title">Ready to put staff data back in your hands?</h2>
            <p>Request access for your organisation, or sign in if you already have an account.</p>
            <?php if (Auth::isLoggedIn()): ?>
                <a href="<?php echo url('index.php'); ?>" class="lr-btn lr-btn--primary">Go to dashboard</a>
            <?php else: ?>
                <a href="<?php echo url('request-access.php'); ?>" class="lr-btn lr-btn--primary">Request organisation access</a>
            <?php endif; ?>
        </div>
    </section>

</div>

<script>
(function () {
    var lrCurrentSlide = 0;
    var lrTotalSlides = 3;
    var lrAutoInterval = null;
    var lrPaused = false;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function lrUpdateSlider() {
        var slides = document.getElementById('lrFeatureSlides');
        if (!slides) return;
        slides.style.transform = 'translateX(-' + (lrCurrentSlide * 33.333333) + '%)';
        var dots = document.querySelectorAll('.lr-slider__dot');
        for (var i = 0; i < dots.length; i++) {
            var on = i === lrCurrentSlide;
            dots[i].classList.toggle('is-active', on);
            dots[i].setAttribute('aria-current', on ? 'true' : 'false');
        }
    }

    function lrTick() {
        if (lrPaused || reduceMotion) return;
        lrCurrentSlide = (lrCurrentSlide + 1) % lrTotalSlides;
        lrUpdateSlider();
    }

    function lrStartAuto() {
        if (reduceMotion) return;
        lrPauseAuto();
        lrAutoInterval = setInterval(lrTick, 15000);
    }

    function lrPauseAuto() {
        if (lrAutoInterval) {
            clearInterval(lrAutoInterval);
            lrAutoInterval = null;
        }
    }

    window.lrChangeSlide = function (direction) {
        lrCurrentSlide += direction;
        if (lrCurrentSlide < 0) lrCurrentSlide = lrTotalSlides - 1;
        else if (lrCurrentSlide >= lrTotalSlides) lrCurrentSlide = 0;
        lrUpdateSlider();
    };

    window.lrGoToSlide = function (index) {
        lrCurrentSlide = index;
        lrUpdateSlider();
    };

    var sliderEl = document.getElementById('lrFeatureSlider');
    if (sliderEl) {
        sliderEl.addEventListener('mouseenter', function () {
            lrPaused = true;
        });
        sliderEl.addEventListener('mouseleave', function () {
            lrPaused = false;
        });
    }

    lrUpdateSlider();
    lrStartAuto();
})();
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
