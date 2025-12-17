/**
 * Theme Manager - Handles dynamic theme loading for both Admin and Customer
 * Include this script on any page that needs live theme updates
 */

const ThemeManager = {
    // API endpoint
    apiUrl: null,
    
    // Current section (admin, customer, rider)
    section: 'customer',
    
    // Initialize
    init: function(section = 'customer') {
        this.section = section;
        
        // Determine API URL based on current location
        const path = window.location.pathname;
        if (path.includes('/AdminSide/')) {
            this.apiUrl = '../api/settings_api.php';
        } else if (path.includes('/customer/')) {
            this.apiUrl = '../../api/get_theme.php';
        } else if (path.includes('/rider/')) {
            this.apiUrl = '../../api/get_theme.php';
        } else {
            this.apiUrl = '/EXpresso/coffee/api/get_theme.php';
        }
        
        // Listen for theme changes from other tabs
        window.addEventListener('storage', (e) => {
            if (e.key === 'themeUpdated' || e.key === 'reloadAdminPages') {
                this.loadTheme();
            }
        });
        
        return this;
    },
    
    // Load theme from server
    loadTheme: function() {
        fetch(`${this.apiUrl}?section=${this.section}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.theme) {
                    this.applyTheme(data.theme);
                }
            })
            .catch(err => {
                console.warn('Could not load theme:', err);
            });
    },
    
    // Apply theme colors to CSS variables
    applyTheme: function(theme) {
        const root = document.documentElement;
        
        root.style.setProperty('--theme-primary', theme.primary);
        root.style.setProperty('--theme-secondary', theme.secondary);
        root.style.setProperty('--theme-accent', theme.accent);
        root.style.setProperty('--theme-text-light', theme.text_light || '#f5e6d8');
        root.style.setProperty('--theme-text-dark', theme.text_dark || '#3e2723');
        
        // Update dynamic style tag if exists
        let styleTag = document.getElementById('dynamic-theme-colors');
        if (styleTag) {
            // Update the CSS variables in the existing style tag
            this.updateStyleTag(theme);
        }
        
        console.log('Theme applied:', theme);
    },
    
    // Update style tag with new theme
    updateStyleTag: function(theme) {
        let styleTag = document.getElementById('dynamic-theme-colors');
        if (!styleTag) {
            styleTag = document.createElement('style');
            styleTag.id = 'dynamic-theme-colors';
            document.head.appendChild(styleTag);
        }
        
        styleTag.textContent = `
            :root {
                --theme-primary: ${theme.primary};
                --theme-secondary: ${theme.secondary};
                --theme-accent: ${theme.accent};
                --theme-text-light: ${theme.text_light || '#f5e6d8'};
                --theme-text-dark: ${theme.text_dark || '#3e2723'};
            }
            
            /* HEADER/NAVBAR */
            .navbar-menu, .admin-navbar, header.navbar, nav.navbar {
                background: var(--theme-primary) !important;
            }
            .navbar-menu a, .navbar-menu .nav-link, .admin-navbar a, header.navbar a {
                color: var(--theme-text-light) !important;
            }
            .navbar-menu a:hover, .navbar-menu .nav-link:hover {
                color: var(--theme-accent) !important;
            }
            
            /* SIDEBAR */
            .sidebar, .admin-sidebar, #sidebar {
                background: var(--theme-primary) !important;
            }
            .sidebar a, .sidebar .nav-link, .admin-sidebar a {
                color: var(--theme-text-light) !important;
            }
            .sidebar a:hover, .sidebar .nav-link:hover, .sidebar a.active, .sidebar .nav-link.active {
                background: var(--theme-secondary) !important;
                color: var(--theme-accent) !important;
            }
            
            /* FOOTER */
            .footer, footer, .admin-footer {
                background: var(--theme-primary) !important;
                color: var(--theme-text-light) !important;
            }
            .footer a, footer a, .footer-link {
                color: var(--theme-text-light) !important;
            }
            .footer a:hover, footer a:hover, .footer-link:hover {
                color: var(--theme-accent) !important;
            }
            .footer-subtitle, .footer p i {
                color: var(--theme-accent) !important;
            }
            .social-icon {
                background: var(--theme-secondary) !important;
                color: var(--theme-accent) !important;
            }
            .social-icon:hover {
                background: var(--theme-accent) !important;
                color: var(--theme-primary) !important;
            }
            
            /* BUTTONS */
            .btn-primary, .btn-theme, .cssbuttonsIoButton {
                background: var(--theme-primary) !important;
                border-color: var(--theme-primary) !important;
                color: var(--theme-text-light) !important;
            }
            .btn-primary:hover, .btn-theme:hover, .cssbuttonsIoButton:hover {
                background: var(--theme-secondary) !important;
                border-color: var(--theme-secondary) !important;
            }
            .btn-accent {
                background: var(--theme-accent) !important;
                border-color: var(--theme-accent) !important;
                color: var(--theme-primary) !important;
            }
            .btn-outline-primary {
                border-color: var(--theme-primary) !important;
                color: var(--theme-primary) !important;
            }
            .btn-outline-primary:hover {
                background: var(--theme-primary) !important;
                color: var(--theme-text-light) !important;
            }
            
            /* FILTER BUTTONS */
            .filter-btn {
                border-color: var(--theme-primary) !important;
                color: var(--theme-primary) !important;
            }
            .filter-btn:hover, .filter-btn.active {
                background: var(--theme-primary) !important;
                color: var(--theme-text-light) !important;
            }
            
            /* ORDER TRACKER */
            .order-tracker .step.active {
                background: var(--theme-primary) !important;
                color: var(--theme-text-light) !important;
            }
            
            /* CARDS */
            .card-header {
                background: var(--theme-primary) !important;
                color: var(--theme-text-light) !important;
            }
            
            /* LOGIN BUTTON */
            .btn-login {
                background: var(--theme-accent) !important;
                color: var(--theme-primary) !important;
            }
            .btn-login:hover {
                background: var(--theme-primary) !important;
                color: var(--theme-text-light) !important;
            }
        `;
    }
};

// Auto-initialize based on page location
document.addEventListener('DOMContentLoaded', function() {
    const path = window.location.pathname;
    let section = 'customer';
    
    if (path.includes('/AdminSide/') || path.includes('/admin/')) {
        section = 'admin';
    } else if (path.includes('/rider/')) {
        section = 'rider';
    }
    
    // Only load if we want live updates (can be disabled)
    if (typeof DISABLE_THEME_MANAGER === 'undefined' || !DISABLE_THEME_MANAGER) {
        ThemeManager.init(section);
    }
});
