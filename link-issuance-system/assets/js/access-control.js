/**
 * Access Control Client Library
 * 
 * A lightweight JavaScript library for integrating access control into browser-based games
 * 
 * Usage:
 * 1. Include this script in your game's HTML:
 *    <script src="https://yourdomain.com/Link Issuance System/assets/js/access-control.min.js"></script>
 * 
 * 2. Initialize the library:
 *    AccessControl.init({
 *      onExpired: function() {
 *        // Handle session expiration (e.g., show message, redirect)
 *        alert('Your session has expired');
 *        window.location.href = 'https://yourdomain.com';
 *      },
 *      checkInterval: 60 // Check session every 60 seconds (default)
 *    });
 * 
 * 3. The library will automatically validate the session and monitor it
 */

(function(window) {
    'use strict';
    
    // Default configuration
    const defaultConfig = {
        onExpired: function() {
            alert('Your session has expired');
            window.location.href = window.location.origin;
        },
        checkInterval: 60, // seconds
        debug: false
    };
    
    // AccessControl object
    const AccessControl = {
        config: { ...defaultConfig },
        sessionToken: null,
        expiresAt: null,
        checkIntervalId: null,
        initialized: false,
        
        /**
         * Initialize the access control library
         * 
         * @param {Object} config Configuration options
         */
        init: function(config = {}) {
            // Prevent multiple initializations
            if (this.initialized) {
                this.log('AccessControl already initialized');
                return;
            }
            
            // Merge configuration
            this.config = { ...defaultConfig, ...config };
            
            this.log('Initializing AccessControl');
            
            // Get session token from cookie
            this.sessionToken = this.getCookie('session_token');
            
            if (!this.sessionToken) {
                this.log('No session token found');
                this.redirectToAccessGateway();
                return;
            }
            
            // Validate session
            this.validateSession()
                .then(valid => {
                    if (valid) {
                        this.log('Session validated successfully');
                        this.startSessionMonitoring();
                    } else {
                        this.log('Session validation failed');
                        this.handleExpiredSession();
                    }
                })
                .catch(error => {
                    this.log('Error validating session:', error);
                    this.handleExpiredSession();
                });
            
            this.initialized = true;
        },
        
        /**
         * Validate the current session
         * 
         * @returns {Promise<boolean>} Promise resolving to true if session is valid
         */
        validateSession: function() {
            return new Promise((resolve, reject) => {
                // Determine API endpoint
                const apiBase = this.getApiBase();
                const endpoint = `${apiBase}/check-session`;
                
                // Make API request
                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        session_token: this.sessionToken
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update expiry time
                        if (data.expires_at) {
                            this.expiresAt = new Date(data.expires_at).getTime();
                        }
                        resolve(true);
                    } else {
                        resolve(false);
                    }
                })
                .catch(error => {
                    this.log('API request error:', error);
                    reject(error);
                });
            });
        },
        
        /**
         * Start monitoring the session
         */
        startSessionMonitoring: function() {
            // Clear any existing interval
            if (this.checkIntervalId) {
                clearInterval(this.checkIntervalId);
            }
            
            // Set up interval to check session
            this.checkIntervalId = setInterval(() => {
                this.validateSession()
                    .then(valid => {
                        if (!valid) {
                            this.handleExpiredSession();
                        }
                    })
                    .catch(error => {
                        this.log('Error validating session:', error);
                        // Don't expire session on network errors
                    });
            }, this.config.checkInterval * 1000);
            
            // Also check expiry time if available
            if (this.expiresAt) {
                const checkExpiryInterval = setInterval(() => {
                    const now = new Date().getTime();
                    if (now >= this.expiresAt) {
                        clearInterval(checkExpiryInterval);
                        this.handleExpiredSession();
                    }
                }, 1000);
            }
            
            this.log('Session monitoring started');
        },
        
        /**
         * Handle expired session
         */
        handleExpiredSession: function() {
            // Clear monitoring interval
            if (this.checkIntervalId) {
                clearInterval(this.checkIntervalId);
                this.checkIntervalId = null;
            }
            
            // Clear session cookie
            this.deleteCookie('session_token');
            
            // Call onExpired callback
            if (typeof this.config.onExpired === 'function') {
                this.config.onExpired();
            }
        },
        
        /**
         * Redirect to access gateway
         */
        redirectToAccessGateway: function() {
            const gatewayUrl = this.getAccessGatewayUrl();
            window.location.href = gatewayUrl;
        },
        
        /**
         * Get the API base URL
         * 
         * @returns {string} API base URL
         */
        getApiBase: function() {
            // Try to determine API base from script src
            const scripts = document.getElementsByTagName('script');
            for (let i = 0; i < scripts.length; i++) {
                const src = scripts[i].src;
                if (src.includes('access-control.js') || src.includes('access-control.min.js')) {
                    return src.substring(0, src.lastIndexOf('/assets/js/')) + '/api';
                }
            }
            
            // Fallback: assume API is at /api relative to the current origin
            return window.location.origin + '/api';
        },
        
        /**
         * Get the access gateway URL
         * 
         * @returns {string} Access gateway URL
         */
        getAccessGatewayUrl: function() {
            // Try to determine gateway URL from script src
            const scripts = document.getElementsByTagName('script');
            for (let i = 0; i < scripts.length; i++) {
                const src = scripts[i].src;
                if (src.includes('access-control.js') || src.includes('access-control.min.js')) {
                    return src.substring(0, src.lastIndexOf('/assets/js/')) + '/access/';
                }
            }
            
            // Fallback: assume gateway is at /access relative to the current origin
            return window.location.origin + '/access/';
        },
        
        /**
         * Get a cookie value by name
         * 
         * @param {string} name Cookie name
         * @returns {string|null} Cookie value or null if not found
         */
        getCookie: function(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        },
        
        /**
         * Delete a cookie
         * 
         * @param {string} name Cookie name
         */
        deleteCookie: function(name) {
            document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        },
        
        /**
         * Log a message if debug is enabled
         */
        log: function(...args) {
            if (this.config.debug) {
                console.log('[AccessControl]', ...args);
            }
        }
    };
    
    // Expose to window
    window.AccessControl = AccessControl;
    
})(window);
