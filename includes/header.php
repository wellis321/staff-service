<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <meta name="description" content="People Management Service for social care providers">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo url('assets/images/favicon_io/favicon-32x32.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo url('assets/images/favicon_io/favicon-16x16.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo url('assets/images/favicon_io/apple-touch-icon.png'); ?>">
    <link rel="manifest" href="<?php echo url('assets/images/favicon_io/site.webmanifest'); ?>">
    
    <!-- Font Awesome 6 (Free) - Icon Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>?v=<?php echo filemtime(dirname(__DIR__) . '/public/assets/css/style.css'); ?>">
    
    <!-- Mobile Menu Toggle -->
    <script>
        (function() {
            function updateMenuVisibility() {
                const menuToggle = document.querySelector('.mobile-menu-toggle');
                const navLinks = document.querySelector('.nav-links');
                const isMobile = window.innerWidth <= 968;
                
                if (menuToggle) {
                    if (isMobile) {
                        menuToggle.style.display = 'block';
                    } else {
                        menuToggle.style.display = 'none';
                        menuToggle.removeAttribute('style');
                        menuToggle.style.display = 'none';
                    }
                }
                
                if (navLinks) {
                    if (isMobile) {
                        if (!navLinks.classList.contains('active')) {
                            navLinks.style.display = 'none';
                        }
                    } else {
                        navLinks.style.display = 'flex';
                        navLinks.classList.remove('active');
                    }
                }
            }
            
            function resetMenuOnPageLoad() {
                const navLinks = document.querySelector('.nav-links');
                const menuToggle = document.querySelector('.mobile-menu-toggle');
                
                updateMenuVisibility();
                
                if (navLinks) {
                    navLinks.classList.remove('active');
                    navLinks.setAttribute('data-menu-state', 'closed');
                }
                
                if (menuToggle) {
                    const icon = menuToggle.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            }
            
            resetMenuOnPageLoad();
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', resetMenuOnPageLoad);
            } else {
                resetMenuOnPageLoad();
            }
            
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(updateMenuVisibility, 100);
            });
            
            document.addEventListener('DOMContentLoaded', function() {
                updateMenuVisibility();
                
                const menuToggle = document.querySelector('.mobile-menu-toggle');
                const navLinks = document.querySelector('.nav-links');
                if (menuToggle && navLinks) {
                    menuToggle.addEventListener('click', function(e) {
                        if (window.innerWidth > 968) {
                            e.preventDefault();
                            return;
                        }
                        
                        e.preventDefault();
                        e.stopPropagation();
                        
                        navLinks.classList.toggle('active');
                        const icon = menuToggle.querySelector('i');
                        if (icon) {
                            if (navLinks.classList.contains('active')) {
                                icon.classList.remove('fa-bars');
                                icon.classList.add('fa-times');
                                navLinks.setAttribute('data-menu-state', 'open');
                                navLinks.style.display = 'flex';
                            } else {
                                icon.classList.remove('fa-times');
                                icon.classList.add('fa-bars');
                                navLinks.setAttribute('data-menu-state', 'closed');
                                navLinks.style.display = 'none';
                            }
                        }
                    });
                    
                    navLinks.querySelectorAll('a').forEach(function(link) {
                        link.addEventListener('click', function() {
                            if (window.innerWidth <= 968) {
                                navLinks.classList.remove('active');
                                navLinks.setAttribute('data-menu-state', 'closed');
                                navLinks.style.display = 'none';
                                const icon = menuToggle.querySelector('i');
                                if (icon) {
                                    icon.classList.remove('fa-times');
                                    icon.classList.add('fa-bars');
                                }
                            }
                        });
                    });
                    
                    // Handle dropdown toggles
                    const dropdowns = navLinks.querySelectorAll('.nav-dropdown');
                    dropdowns.forEach(function(dropdown) {
                        const toggle = dropdown.querySelector('.nav-dropdown-toggle');
                        if (toggle) {
                            toggle.addEventListener('click', function(e) {
                                if (window.innerWidth <= 968) {
                                    // On mobile, just toggle the dropdown
                                    e.preventDefault();
                                    dropdown.classList.toggle('active');
                                } else {
                                    // On desktop, toggle and close others
                                    e.preventDefault();
                                    e.stopPropagation();
                                    const isActive = dropdown.classList.contains('active');
                                    
                                    // Close all dropdowns
                                    dropdowns.forEach(function(d) {
                                        d.classList.remove('active');
                                    });
                                    
                                    // Open clicked dropdown if it wasn't active
                                    if (!isActive) {
                                        dropdown.classList.add('active');
                                    }
                                }
                            });
                        }
                    });
                    
                    // Close dropdowns when clicking outside
                    document.addEventListener('click', function(e) {
                        if (window.innerWidth > 968) {
                            if (!e.target.closest('.nav-dropdown')) {
                                dropdowns.forEach(function(dropdown) {
                                    dropdown.classList.remove('active');
                                });
                            }
                        }
                    });
                }
            });
        })();
    </script>
</head>
<body>
    <!-- Skip to main content link for keyboard navigation -->
    <a href="#main-content" class="skip-link" style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden;">Skip to main content</a>
    <style>
        .skip-link:focus {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 10000;
            padding: 0.75rem 1.5rem;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 0;
            font-weight: 600;
            width: auto;
            height: auto;
            overflow: visible;
        }
    </style>
    <header>
        <nav>
            <div class="container">
                <a href="<?php echo Auth::isLoggedIn() ? url('index.php') : url('landing.php'); ?>" class="logo">
                    <img src="<?php echo url('assets/images/New White Logo.png'); ?>" alt="<?php echo APP_NAME; ?>" class="logo-img">
                </a>
                <button class="mobile-menu-toggle" aria-label="Toggle menu" style="display: none;">
                    <i class="fas fa-bars"></i>
                </button>
                <?php 
                $currentPage = basename($_SERVER['PHP_SELF']);
                $isActive = function($page) use ($currentPage) {
                    return $currentPage === $page ? 'active' : '';
                };
                ?>
                <?php if (Auth::isLoggedIn()): ?>
                    <div class="nav-links" data-menu-state="closed">
                        <!-- Public/General Navigation -->
                        <a href="<?php echo url('index.php'); ?>" class="<?php echo $isActive('index.php'); ?>">Home</a>
                        <a href="<?php echo url('security.php'); ?>" class="<?php echo $isActive('security.php'); ?>">Security</a>
                        <a href="<?php echo url('docs.php'); ?>" class="<?php echo $isActive('docs.php'); ?>">Docs</a>
                        
                        <!-- Admin Navigation Dropdown -->
                        <?php if (RBAC::isOrganisationAdmin() || RBAC::isSuperAdmin()): ?>
                            <span class="nav-separator" aria-hidden="true">|</span>
                            <div class="nav-dropdown">
                                <?php 
                                $isAdminPage = strpos($_SERVER['PHP_SELF'], 'admin/') !== false || 
                                               strpos($_SERVER['PHP_SELF'], 'staff/') !== false || 
                                               strpos($_SERVER['PHP_SELF'], 'job-descriptions/') !== false || 
                                               strpos($_SERVER['PHP_SELF'], 'job-posts/') !== false;
                                ?>
                                <div class="nav-dropdown-toggle <?php echo $isAdminPage ? 'active' : ''; ?>">
                                    <i class="fas fa-cog"></i> Admin
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                                <div class="nav-dropdown-menu">
                                    <?php if (RBAC::isSuperAdmin()): ?>
                                        <a href="<?php echo url('admin/organisation-requests.php'); ?>" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'active' : ''; ?>">
                                            <i class="fas fa-shield-alt"></i> Admin
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo url('staff/index.php'); ?>" class="<?php echo strpos($_SERVER['PHP_SELF'], 'staff/') !== false ? 'active' : ''; ?>">
                                        <i class="fas fa-users"></i> Staff
                                    </a>
                                    <a href="<?php echo url('job-descriptions/index.php'); ?>" class="<?php echo strpos($_SERVER['PHP_SELF'], 'job-descriptions/') !== false ? 'active' : ''; ?>">
                                        <i class="fas fa-file-alt"></i> Job Descriptions
                                    </a>
                                    <a href="<?php echo url('job-posts/index.php'); ?>" class="<?php echo strpos($_SERVER['PHP_SELF'], 'job-posts/') !== false ? 'active' : ''; ?>">
                                        <i class="fas fa-briefcase"></i> Job Posts
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- User Navigation Dropdown -->
                        <span class="nav-separator" aria-hidden="true">|</span>
                        <div class="nav-dropdown">
                            <div class="nav-dropdown-toggle <?php echo $isActive('profile.php') ? 'active' : ''; ?>">
                                <i class="fas fa-user"></i> My Profile
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="nav-dropdown-menu">
                                <a href="<?php echo url('profile.php'); ?>" class="<?php echo $isActive('profile.php'); ?>">
                                    <i class="fas fa-user"></i> My Profile
                                </a>
                                <a href="<?php echo url('logout.php'); ?>">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <nav id="nav-links" class="nav-links" data-menu-state="closed" role="navigation" aria-label="Main navigation">
                        <a href="<?php echo url('landing.php'); ?>" class="<?php echo $isActive('landing.php'); ?>">Home</a>
                        <a href="<?php echo url('docs.php'); ?>" class="<?php echo $isActive('docs.php'); ?>">Docs</a>
                        <a href="<?php echo url('security.php'); ?>" class="<?php echo $isActive('security.php'); ?>">Security</a>
                        <a href="<?php echo url('login.php'); ?>" class="<?php echo $isActive('login.php'); ?>">Login</a>
                        <a href="<?php echo url('register.php'); ?>" class="<?php echo $isActive('register.php'); ?>">Register</a>
                    </nav>
                <?php endif; ?>
            </div>
        </nav>
    </header>
            
    <main id="main-content" class="container">

