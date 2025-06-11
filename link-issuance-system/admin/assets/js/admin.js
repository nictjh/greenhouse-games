/**
 * Admin Dashboard JavaScript
 * 
 * Handles all functionality for the admin dashboard
 */

// API endpoints
const API = {
    // Get the base path from the current script location
    getBasePath: function() {
        // Get the current script path (admin.js)
        const scripts = document.getElementsByTagName('script');
        let adminJsPath = '';
        
        for (let i = 0; i < scripts.length; i++) {
            if (scripts[i].src.includes('admin.js')) {
                adminJsPath = scripts[i].src;
                break;
            }
        }
        
        // Extract the base path (up to the admin directory)
        if (adminJsPath) {
            // Remove everything after and including /admin/assets/js/admin.js
            return adminJsPath.substring(0, adminJsPath.indexOf('/admin/assets/js/admin.js'));
        }
        
        // Fallback: use the current location and go up two levels from admin
        const currentPath = window.location.href;
        const adminIndex = currentPath.indexOf('/admin/');
        
        if (adminIndex !== -1) {
            return currentPath.substring(0, adminIndex);
        }
        
        // Last resort fallback
        return window.location.origin;
    },
    
    // Use relative paths that work in any directory structure
    get list() { 
        return this.getBasePath() + '/api/list'; 
    },
    get stats() { 
        return this.getBasePath() + '/api/stats'; 
    },
    get generate() { 
        return this.getBasePath() + '/api/generate'; 
    },
    get revoke() { 
        return this.getBasePath() + '/api/revoke'; 
    },
    get saveGames() {
        return this.getBasePath() + '/api/save-games';
    },
    get deleteLink() {
        return this.getBasePath() + '/api/delete-link';
    }
};

// For debugging
console.log('Current script path:', document.currentScript ? document.currentScript.src : 'Unknown');
console.log('Base path:', API.getBasePath());
console.log('API endpoints:', {
    list: API.list,
    stats: API.stats,
    generate: API.generate,
    revoke: API.revoke
});

// DOM elements
const elements = {
    // Navigation
    navItems: document.querySelectorAll('.nav-item'),
    pageTitle: document.getElementById('page-title'),
    pages: document.querySelectorAll('.page'),
    refreshButton: document.getElementById('refresh-button'),
    
    // Dashboard
    activeLinks: document.getElementById('active-links'),
    activeSessions: document.getElementById('active-sessions'),
    totalLinks: document.getElementById('total-links'),
    totalSessions: document.getElementById('total-sessions'),
    linkStatusChart: document.getElementById('link-status-chart'),
    gameUsageChart: document.getElementById('game-usage-chart'),
    recentLinksTable: document.getElementById('recent-links-table'),
    
    // Links
    linksTable: document.getElementById('links-table'),
    createLinkButton: document.getElementById('create-link-button'),
    
    // Games
    gamesGrid: document.getElementById('games-grid'),
    editGamesButton: document.getElementById('edit-games-button'),
    
    // Settings
    settingsForm: document.querySelector('.settings-form'),
    saveSettingsButton: document.getElementById('save-settings-button'),
    
    // Modals
    createLinkModal: document.getElementById('create-link-modal'),
    linkDetailsModal: document.getElementById('link-details-modal'),
    editGamesModal: document.getElementById('edit-games-modal'),
    closeButtons: document.querySelectorAll('.close-button'),
    
    // Create Link Form
    gameCheckboxes: document.getElementById('game-checkboxes'),
    durationDays: document.getElementById('duration-days'),
    durationHours: document.getElementById('duration-hours'),
    durationMinutes: document.getElementById('duration-minutes'),
    linkConcurrentLimit: document.getElementById('link-concurrent-limit'),
    linkName: document.getElementById('link-name'),
    linkNote: document.getElementById('link-note'),
    createLinkSubmit: document.getElementById('create-link-submit'),
    
    // Link Details
    linkDetails: document.querySelector('.link-details'),
    copyLinkButton: document.getElementById('copy-link-button'),
    revokeLinkButton: document.getElementById('revoke-link-button'),
    
    // Games Editor
    gamesEditor: document.querySelector('.games-editor'),
    saveGamesButton: document.getElementById('save-games-button')
};

// Chart instances
let linkStatusChartInstance = null;
let gameUsageChartInstance = null;

// Current data
let currentLinks = [];
let currentGames = [];
let currentStats = null;
let currentLinkDetails = null;
let archivedLinks = []; // For storing links in the bin

// Admin token
const getAdminToken = () => {
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'admin_token') {
            return value;
        }
    }
    return '';
};

// API request helper
const apiRequest = async (endpoint, method = 'GET', data = null) => {
    // Get admin token
    const token = getAdminToken();
    
    // Debug information
    console.log('API Request:', {
        endpoint,
        method,
        token: token ? token.substring(0, 10) + '...' : 'No token',
        hasData: !!data
    });
    
    // Set up request options
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json'
        },
        // Add credentials to include cookies in the request
        credentials: 'include'
    };
    
    // Add Authorization header if token exists
    if (token) {
        options.headers['Authorization'] = `Bearer ${token}`;
    }
    
    // Add body if data exists
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        // Make the request
        console.log(`Fetching ${endpoint}...`);
        const response = await fetch(endpoint, options);
        
        // Log response status
        console.log(`Response status: ${response.status}`);
        
        // Check if response is ok
        if (!response.ok) {
            // Try to get error details from response
            let errorDetails = '';
            try {
                const errorJson = await response.json();
                errorDetails = errorJson.message || JSON.stringify(errorJson);
            } catch (e) {
                // If we can't parse JSON, use text
                try {
                    errorDetails = await response.text();
                } catch (e2) {
                    errorDetails = 'No error details available';
                }
            }
            
            throw new Error(`API request failed: ${response.status} - ${errorDetails}`);
        }
        
        // Parse response as JSON
        const responseData = await response.json();
        console.log('API Response:', responseData);
        return responseData;
    } catch (error) {
        console.error('API request error:', error);
        showNotification('Error', error.message, 'error');
        return { success: false, message: error.message };
    }
};

// Navigation
const initNavigation = () => {
    elements.navItems.forEach(item => {
        item.addEventListener('click', () => {
            // Update active nav item
            elements.navItems.forEach(navItem => navItem.classList.remove('active'));
            item.classList.add('active');
            
            // Update page title
            elements.pageTitle.textContent = item.querySelector('.text').textContent;
            
            // Show active page
            const targetPage = item.dataset.target;
            elements.pages.forEach(page => {
                page.classList.remove('active');
                if (page.id === targetPage) {
                    page.classList.add('active');
                }
            });
            
            // Load page data
            loadPageData(targetPage);
        });
    });
    
    // Refresh button
    elements.refreshButton.addEventListener('click', () => {
        const activePage = document.querySelector('.page.active').id;
        loadPageData(activePage);
    });
};

// Load page data
const loadPageData = (page) => {
    switch (page) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'links':
            loadLinks();
            break;
        case 'bin':
            loadBin();
            break;
        case 'games':
            loadGames();
            break;
        case 'settings':
            loadSettings();
            break;
    }
};

// Dashboard
const loadDashboard = async () => {
    // Load stats
    const statsResponse = await apiRequest(API.stats);
    
    if (statsResponse.success) {
        currentStats = statsResponse.stats;
        updateDashboardStats(currentStats);
        updateDashboardCharts(currentStats);
    }
    
    // Load recent links
    const linksResponse = await apiRequest(API.list);
    
    if (linksResponse.success) {
        currentLinks = linksResponse.links;
        updateRecentLinks(currentLinks.slice(0, 5));
    }
};

const updateDashboardStats = (stats) => {
    elements.activeLinks.textContent = stats.links.active;
    elements.activeSessions.textContent = stats.sessions.active;
    elements.totalLinks.textContent = stats.links.total;
    elements.totalSessions.textContent = stats.sessions.total;
};

const updateDashboardCharts = (stats) => {
    // Link status chart
    const linkStatusData = {
        labels: ['Active', 'Pending', 'Expired', 'Revoked'],
        datasets: [{
            data: [
                stats.links.active,
                stats.links.pending,
                stats.links.expired,
                stats.links.revoked
            ],
            backgroundColor: [
                '#4caf50',
                '#ff9800',
                '#9e9e9e',
                '#f44336'
            ]
        }]
    };
    
    if (linkStatusChartInstance) {
        linkStatusChartInstance.destroy();
    }
    
    linkStatusChartInstance = new Chart(elements.linkStatusChart, {
        type: 'doughnut',
        data: linkStatusData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    
    // Game usage chart
    const gameNames = stats.games.map(game => game.name);
    const gameActiveSessions = stats.games.map(game => game.active_sessions);
    
    const gameUsageData = {
        labels: gameNames,
        datasets: [{
            label: 'Active Sessions',
            data: gameActiveSessions,
            backgroundColor: '#4a6fa5'
        }]
    };
    
    if (gameUsageChartInstance) {
        gameUsageChartInstance.destroy();
    }
    
    gameUsageChartInstance = new Chart(elements.gameUsageChart, {
        type: 'bar',
        data: gameUsageData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
};

const updateRecentLinks = (links) => {
    const tbody = elements.recentLinksTable.querySelector('tbody');
    tbody.innerHTML = '';
    
    if (links.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `<td colspan="7" class="loading">No links found</td>`;
        tbody.appendChild(row);
        return;
    }
    
    links.forEach(link => {
        const row = document.createElement('tr');
        
        // Format games
        const games = link.game_ids.length > 1 
            ? `${link.game_ids.length} games` 
            : getGameNameById(link.game_ids[0]);
        
        // Create status badge
        const statusBadge = `<span class="badge ${link.status}">${link.status}</span>`;
        
        // Add session name if available
        const sessionName = link.name ? `<div><strong>Session:</strong> ${link.name}</div>` : '';
        
        row.innerHTML = `
            <td>${link.id.substring(0, 8)}...</td>
            <td>${games}</td>
            <td>${link.duration_formatted}</td>
            <td>${link.concurrent_limit}</td>
            <td>${statusBadge}</td>
            <td>${link.created_at}</td>
            <td>
                <button class="button secondary action-button view-link-button" data-id="${link.id}">
                    <span class="icon">👁️</span>
                    <span class="text">View</span>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Add event listeners to view buttons
    const viewButtons = tbody.querySelectorAll('.view-link-button');
    viewButtons.forEach(button => {
        button.addEventListener('click', () => {
            const linkId = button.dataset.id;
            showLinkDetails(linkId);
        });
    });
};

// Links
const loadLinks = async () => {
    const response = await apiRequest(API.list);
    
    if (response.success) {
        currentLinks = response.links;
        
        // Filter active and non-active links
        const activeLinks = currentLinks.filter(link => 
            link.status !== 'expired' && link.status !== 'revoked'
        );
        
        archivedLinks = currentLinks.filter(link => 
            link.status === 'expired' || link.status === 'revoked'
        );
        
        updateLinksTable(activeLinks);
    }
};

// Bin
const loadBin = async () => {
    const response = await apiRequest(API.list);
    
    if (response.success) {
        currentLinks = response.links;
        
        // Filter expired and revoked links
        archivedLinks = currentLinks.filter(link => 
            link.status === 'expired' || link.status === 'revoked'
        );
        
        updateBinTable(archivedLinks);
    }
};

const updateBinTable = (links) => {
    const binTable = document.getElementById('bin-table');
    if (!binTable) return;
    
    const tbody = binTable.querySelector('tbody');
    tbody.innerHTML = '';
    
    if (links.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `<td colspan="9" class="loading">No archived links found</td>`;
        tbody.appendChild(row);
        return;
    }
    
    links.forEach(link => {
        const row = document.createElement('tr');
        
        // Format games
        const games = link.game_ids.length > 1 
            ? `${link.game_ids.length} games` 
            : getGameNameById(link.game_ids[0]);
        
        // Create status badge
        const statusBadge = `<span class="badge ${link.status}">${link.status}</span>`;
        
        // Add session name if available
        const sessionName = link.name ? `<div><strong>Session:</strong> ${link.name}</div>` : '';
        
        row.innerHTML = `
            <td>${link.id.substring(0, 8)}...</td>
            <td>${games}</td>
            <td>${sessionName ? link.name : 'N/A'}</td>
            <td>${link.duration_formatted}</td>
            <td>${statusBadge}</td>
            <td>${link.created_at}</td>
            <td>${link.expires_at || 'Not activated'}</td>
            <td>
                <button class="button secondary action-button view-link-button" data-id="${link.id}">
                    <span class="icon">👁️</span>
                    <span class="text">View</span>
                </button>
                <button class="button danger action-button delete-link-button" data-id="${link.id}">
                    <span class="icon">🗑️</span>
                    <span class="text">Delete</span>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Add event listeners to buttons
    const viewButtons = binTable.querySelectorAll('.view-link-button');
    viewButtons.forEach(button => {
        button.addEventListener('click', () => {
            const linkId = button.dataset.id;
            showLinkDetails(linkId);
        });
    });
    
    const deleteButtons = binTable.querySelectorAll('.delete-link-button');
    deleteButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const linkId = button.dataset.id;
            if (confirm('Are you sure you want to permanently delete this link? This action cannot be undone.')) {
                await deleteLink(linkId);
            }
        });
    });
};

const updateLinksTable = (links) => {
    const tbody = elements.linksTable.querySelector('tbody');
    tbody.innerHTML = '';
    
    if (links.length === 0) {
        const row = document.createElement('tr');
        row.innerHTML = `<td colspan="10" class="loading">No links found</td>`;
        tbody.appendChild(row);
        return;
    }
    
    links.forEach(link => {
        const row = document.createElement('tr');
        
        // Format games
        const games = link.game_ids.length > 1 
            ? `${link.game_ids.length} games` 
            : getGameNameById(link.game_ids[0]);
        
        // Create status badge
        const statusBadge = `<span class="badge ${link.status}">${link.status}</span>`;
        
        // Create a row with a bin icon at the top right
        const rowStyle = `position: relative;`;
        
        // Add delete button as a bin icon at the top right of the row
        const deleteButton = `
            <button class="button danger action-button delete-link-button" data-id="${link.id}" style="position: absolute; top: 5px; right: 5px; padding: 5px; min-width: auto; border-radius: 50%; z-index: 10; display: ${link.status === 'expired' || link.status === 'revoked' ? 'block' : 'none'};">
                <span class="icon">🗑️</span>
            </button>`;
        
        row.style = rowStyle;
        row.innerHTML = `
            ${deleteButton}
            <td>${link.id.substring(0, 8)}...</td>
            <td>${games}</td>
            <td>${link.name || 'N/A'}</td>
            <td>${link.duration_formatted}</td>
            <td>${link.concurrent_limit}</td>
            <td>${link.active_sessions}</td>
            <td>${statusBadge}</td>
            <td>${link.created_at}</td>
            <td>${link.expires_at || 'Not activated'}</td>
            <td>
                <button class="button secondary action-button view-link-button" data-id="${link.id}">
                    <span class="icon">👁️</span>
                    <span class="text">View</span>
                </button>
                ${link.status !== 'revoked' ? `
                <button class="button danger action-button revoke-link-button" data-id="${link.id}">
                    <span class="icon">🚫</span>
                    <span class="text">Revoke</span>
                </button>
                ` : ''}
                <button class="button warning action-button move-to-bin-button" data-id="${link.id}">
                    <span class="icon">🗑️</span>
                    <span class="text">Move to Bin</span>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Add event listeners to buttons
    const viewButtons = tbody.querySelectorAll('.view-link-button');
    viewButtons.forEach(button => {
        button.addEventListener('click', () => {
            const linkId = button.dataset.id;
            showLinkDetails(linkId);
        });
    });
    
    const revokeButtons = tbody.querySelectorAll('.revoke-link-button');
    revokeButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const linkId = button.dataset.id;
            if (confirm('Are you sure you want to revoke this link? This action cannot be undone.')) {
                await revokeLink(linkId);
            }
        });
    });
    
    const moveToBinButtons = tbody.querySelectorAll('.move-to-bin-button');
    moveToBinButtons.forEach(button => {
        button.addEventListener('click', async () => {
            const linkId = button.dataset.id;
            if (confirm('Are you sure you want to move this link to the bin?')) {
                // Move to bin (just a UI change, the link is still in the database)
                const link = currentLinks.find(l => l.id === linkId);
                if (link) {
                    archivedLinks.push(link);
                    loadLinks(); // Refresh the links table
                }
            }
        });
    });
};

// Games
const loadGames = async () => {
    const response = await apiRequest(API.stats);
    
    if (response.success) {
        currentGames = response.stats.games;
        updateGamesGrid(currentGames);
    }
};

const updateGamesGrid = (games) => {
    elements.gamesGrid.innerHTML = '';
    
    if (games.length === 0) {
        elements.gamesGrid.innerHTML = '<div class="loading">No games found</div>';
        return;
    }
    
    games.forEach(game => {
        const gameCard = document.createElement('div');
        gameCard.className = 'game-card';
        
        gameCard.innerHTML = `
            <h4>${game.name}</h4>
            <span class="game-type">${game.type}</span>
            <p>${game.description}</p>
            <div class="game-stats">
                <div>Active Links: <strong>${game.active_links}</strong></div>
                <div>Active Sessions: <strong>${game.active_sessions}</strong></div>
                <div>Total Links: <strong>${game.total_links}</strong></div>
            </div>
            <div class="game-url">${game.url}</div>
        `;
        
        elements.gamesGrid.appendChild(gameCard);
    });
};

// Settings
const loadSettings = () => {
    // Settings are already loaded in PHP
};

// Create Link
const initCreateLink = () => {
    elements.createLinkButton.addEventListener('click', () => {
        showCreateLinkModal();
    });
    
    elements.createLinkSubmit.addEventListener('click', async () => {
        await createLink();
    });
};

const showCreateLinkModal = async () => {
    // Load games for checkboxes
    const response = await apiRequest(API.stats);
    
    if (response.success) {
        currentGames = response.stats.games;
        updateGameCheckboxes(currentGames);
    }
    
    // Reset form
    elements.durationDays.value = '1';
    elements.durationHours.value = '0';
    elements.durationMinutes.value = '0';
    elements.linkConcurrentLimit.value = '1';
    elements.linkName.value = '';
    elements.linkNote.value = '';
    
    // Show modal
    elements.createLinkModal.classList.add('active');
};

const updateGameCheckboxes = (games) => {
    elements.gameCheckboxes.innerHTML = '';
    
    games.forEach(game => {
        const checkboxItem = document.createElement('div');
        checkboxItem.className = 'checkbox-item';
        
        checkboxItem.innerHTML = `
            <input type="checkbox" id="game-${game.id}" name="game-${game.id}" value="${game.id}">
            <label for="game-${game.id}">${game.name}</label>
        `;
        
        elements.gameCheckboxes.appendChild(checkboxItem);
    });
};

const createLink = async () => {
    // Get selected games
    const selectedGames = [];
    const checkboxes = elements.gameCheckboxes.querySelectorAll('input[type="checkbox"]:checked');
    
    checkboxes.forEach(checkbox => {
        selectedGames.push(checkbox.value);
    });
    
    if (selectedGames.length === 0) {
        alert('Please select at least one game');
        return;
    }
    
    // Calculate duration in seconds
    const days = parseInt(elements.durationDays.value) || 0;
    const hours = parseInt(elements.durationHours.value) || 0;
    const minutes = parseInt(elements.durationMinutes.value) || 0;
    
    const duration = (days * 86400) + (hours * 3600) + (minutes * 60);
    
    if (duration <= 0) {
        alert('Please set a valid duration');
        return;
    }
    
    // Get concurrent limit
    const concurrentLimit = parseInt(elements.linkConcurrentLimit.value) || 1;
    
    if (concurrentLimit <= 0) {
        alert('Concurrent limit must be at least 1');
        return;
    }
    
    // Get session name and note
    const name = elements.linkName.value.trim();
    const note = elements.linkNote.value;
    
    // Validate session name (now required)
    if (!name) {
        alert('Session name is required');
        return;
    }
    
    // Create link
    const data = {
        game_ids: selectedGames,
        duration,
        concurrent_limit: concurrentLimit,
        name,
        note
    };
    
    const response = await apiRequest(API.generate, 'POST', data);
    
    if (response.success) {
        // Hide modal
        elements.createLinkModal.classList.remove('active');
        
        // Show link details
        showLinkDetails(response.link.id);
        
        // Reload links
        loadLinks();
        loadDashboard();
    }
};

// Link Details
const showLinkDetails = (linkId) => {
    // Find link in either current links or archived links
    let link = currentLinks.find(l => l.id === linkId);
    
    if (!link) {
        link = archivedLinks.find(l => l.id === linkId);
    }
    
    if (!link) {
        alert('Link not found');
        return;
    }
    
    currentLinkDetails = link;
    
    // Update modal content
    elements.linkDetails.innerHTML = `
        <div class="detail-group">
            <h4>Access URL</h4>
            <div class="access-url">${window.location.origin}/Link-Issuance-System/direct-access.php?token=${link.token}</div>
        </div>
        
        <div class="detail-group">
            <h4>Link ID</h4>
            <div class="detail-value">${link.id}</div>
        </div>
        
        <div class="detail-group">
            <h4>Status</h4>
            <div class="detail-value">
                <span class="badge ${link.status}">${link.status}</span>
            </div>
        </div>
        
        <div class="detail-group">
            <h4>Session Name</h4>
            <div class="detail-value">${link.name || 'N/A'}</div>
        </div>
        
        <div class="detail-group">
            <h4>Games</h4>
            <div class="detail-value">
                ${link.game_ids.map(id => getGameNameById(id)).join(', ')}
            </div>
        </div>
        
        <div class="detail-group">
            <h4>Duration</h4>
            <div class="detail-value">${link.duration_formatted}</div>
        </div>
        
        <div class="detail-group">
            <h4>Concurrent Device Limit</h4>
            <div class="detail-value">${link.concurrent_limit}</div>
        </div>
        
        <div class="detail-group">
            <h4>Created</h4>
            <div class="detail-value">${link.created_at}</div>
        </div>
        
        ${link.activated_at ? `
        <div class="detail-group">
            <h4>Activated</h4>
            <div class="detail-value">${link.activated_at}</div>
        </div>
        ` : ''}
        
        ${link.expires_at ? `
        <div class="detail-group">
            <h4>Expires</h4>
            <div class="detail-value">${link.expires_at}</div>
        </div>
        ` : ''}
        
        ${link.note ? `
        <div class="detail-group">
            <h4>Note</h4>
            <div class="detail-value">${link.note}</div>
        </div>
        ` : ''}
        
        ${link.active_sessions > 0 ? `
        <div class="sessions-list">
            <h4>Active Sessions (${link.active_sessions})</h4>
            <div class="detail-value">
                ${link.active_sessions} device${link.active_sessions !== 1 ? 's' : ''} currently connected
            </div>
        </div>
        ` : ''}
    `;
    
    // Update button states
    elements.copyLinkButton.disabled = false;
    elements.revokeLinkButton.disabled = link.status === 'revoked';
    
    // Show modal
    elements.linkDetailsModal.classList.add('active');
};

const initLinkDetails = () => {
    elements.copyLinkButton.addEventListener('click', () => {
        if (!currentLinkDetails) return;
        
        // Generate direct access URL with subdirectory
        const directAccessUrl = `${window.location.origin}/Link-Issuance-System/direct-access.php?token=${currentLinkDetails.token}`;
        
        // Copy link to clipboard
        navigator.clipboard.writeText(directAccessUrl)
            .then(() => {
                alert('Link copied to clipboard');
            })
            .catch(err => {
                console.error('Could not copy text: ', err);
                alert('Failed to copy link');
            });
    });
    
    elements.revokeLinkButton.addEventListener('click', async () => {
        if (!currentLinkDetails) return;
        
        if (confirm('Are you sure you want to revoke this link? This action cannot be undone.')) {
            await revokeLink(currentLinkDetails.id);
            elements.linkDetailsModal.classList.remove('active');
        }
    });
};

const revokeLink = async (linkId) => {
    const response = await apiRequest(API.revoke, 'POST', { link_id: linkId });
    
    if (response.success) {
        // Reload links
        loadLinks();
        loadDashboard();
    }
};

const deleteLink = async (linkId) => {
    try {
        // Call the delete-link API endpoint
        const response = await apiRequest(API.deleteLink, 'POST', { link_id: linkId });
        
        if (response.success) {
            // Remove from UI
            const index = archivedLinks.findIndex(link => link.id === linkId);
            if (index !== -1) {
                archivedLinks.splice(index, 1);
                updateBinTable(archivedLinks);
            }
            
            showNotification('Success', 'Link deleted successfully', 'success');
        } else {
            throw new Error(response.message || 'Failed to delete link');
        }
    } catch (error) {
        console.error('Error deleting link:', error);
        showNotification('Error', error.message, 'error');
    }
};

// Games Editor
const initGamesEditor = () => {
    // Add event listener for Edit Games button
    if (elements.editGamesButton) {
        console.log('Initializing Edit Games button');
        // Remove any existing event listeners
        elements.editGamesButton.removeEventListener('click', showGamesEditorModal);
        // Add new event listener
        elements.editGamesButton.addEventListener('click', showGamesEditorModal);
        console.log('Edit Games button event listener attached');
    } else {
        console.error('Edit Games button not found in the DOM');
    }
    
    // Add event listener for Save Games button
    if (elements.saveGamesButton) {
        // Remove any existing event listeners
        elements.saveGamesButton.removeEventListener('click', saveGames);
        // Add new event listener
        elements.saveGamesButton.addEventListener('click', async () => {
            await saveGames();
        });
    } else {
        console.error('Save Games button not found in the DOM');
    }
};

const showGamesEditorModal = async () => {
    console.log('Showing Games Editor Modal');
    
    // Show loading state
    elements.gamesEditor.innerHTML = '<div class="loading">Loading games...</div>';
    
    try {
        // Get games from API
        const response = await apiRequest(API.stats);
        
        if (!response.success) {
            throw new Error(response.message || 'Failed to load games');
        }
        
        const games = response.stats.games;
        console.log('Loaded games:', games);
        
        // Create games editor form
        let html = '<div class="games-list">';
        
        games.forEach((game, index) => {
            html += `
                <div class="game-editor-item" data-index="${index}" data-game-id="${game.id}">
                    <div class="form-group">
                        <label for="game-name-${index}">Game Name</label>
                        <input type="text" id="game-name-${index}" name="game-name-${index}" value="${game.name}">
                    </div>
                    
                    <div class="form-group">
                        <label for="game-type-${index}">Game Type</label>
                        <input type="text" id="game-type-${index}" name="game-type-${index}" value="${game.type}">
                    </div>
                    
                    <div class="form-group">
                        <label for="game-url-${index}">Game URL</label>
                        <input type="text" id="game-url-${index}" name="game-url-${index}" value="${game.url}">
                    </div>
                    
                    <div class="form-group">
                        <label for="game-image-url-${index}">Image URL</label>
                        <input type="text" id="game-image-url-${index}" name="game-image-url-${index}" value="${game.image_url || ''}">
                        <small>URL to an image for this game (displayed on the game card)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="game-external-url-${index}">External URL (Optional)</label>
                        <input type="text" id="game-external-url-${index}" name="game-external-url-${index}" value="${game.external_url || ''}">
                        <small>URL to an external page about this game (for "Other Games You Might Like" section)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="game-description-${index}">Description</label>
                        <textarea id="game-description-${index}" name="game-description-${index}" rows="3">${game.description}</textarea>
                    </div>
                    
                    <button type="button" class="button danger remove-game-button" data-index="${index}">
                        <span class="icon">🗑️</span>
                        <span class="text">Remove</span>
                    </button>
                </div>
            `;
        });
        
        html += '</div>';
        
        // Add button to add new game
        html += `
            <div class="add-game-container">
                <button type="button" class="button primary add-game-button">
                    <span class="icon">➕</span>
                    <span class="text">Add Game</span>
                </button>
            </div>
        `;
        
        // Update games editor content
        elements.gamesEditor.innerHTML = html;
        
        // Add event listeners for remove buttons
        const removeButtons = elements.gamesEditor.querySelectorAll('.remove-game-button');
        removeButtons.forEach(button => {
            button.addEventListener('click', () => {
                const index = button.dataset.index;
                const gameItem = elements.gamesEditor.querySelector(`.game-editor-item[data-index="${index}"]`);
                
                if (gameItem) {
                    if (confirm('Are you sure you want to remove this game?')) {
                        gameItem.remove();
                    }
                }
            });
        });
        
        // Add event listener for add button
        const addButton = elements.gamesEditor.querySelector('.add-game-button');
        if (addButton) {
            addButton.addEventListener('click', () => {
                const gamesList = elements.gamesEditor.querySelector('.games-list');
                const newIndex = gamesList.children.length;
                
                const newGameItem = document.createElement('div');
                newGameItem.className = 'game-editor-item';
                newGameItem.dataset.index = newIndex;
                
                newGameItem.innerHTML = `
                    <div class="form-group">
                        <label for="game-name-${newIndex}">Game Name</label>
                        <input type="text" id="game-name-${newIndex}" name="game-name-${newIndex}" value="">
                    </div>
                    
                    <div class="form-group">
                        <label for="game-type-${newIndex}">Game Type</label>
                        <input type="text" id="game-type-${newIndex}" name="game-type-${newIndex}" value="">
                    </div>
                    
                    <div class="form-group">
                        <label for="game-url-${newIndex}">Game URL</label>
                        <input type="text" id="game-url-${newIndex}" name="game-url-${newIndex}" value="">
                    </div>
                    
                    <div class="form-group">
                        <label for="game-image-url-${newIndex}">Image URL</label>
                        <input type="text" id="game-image-url-${newIndex}" name="game-image-url-${newIndex}" value="">
                        <small>URL to an image for this game (displayed on the game card)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="game-description-${newIndex}">Description</label>
                        <textarea id="game-description-${newIndex}" name="game-description-${newIndex}" rows="3"></textarea>
                    </div>
                    
                    <button type="button" class="button danger remove-game-button" data-index="${newIndex}">
                        <span class="icon">🗑️</span>
                        <span class="text">Remove</span>
                    </button>
                `;
                
                gamesList.appendChild(newGameItem);
                
                // Add event listener for new remove button
                const removeButton = newGameItem.querySelector('.remove-game-button');
                removeButton.addEventListener('click', () => {
                    if (confirm('Are you sure you want to remove this game?')) {
                        newGameItem.remove();
                    }
                });
            });
        }
        
        // Show modal
        elements.editGamesModal.classList.add('active');
        
    } catch (error) {
        console.error('Error loading games:', error);
        elements.gamesEditor.innerHTML = `<div class="error">Error loading games: ${error.message}</div>`;
        showNotification('Error', `Failed to load games: ${error.message}`, 'error');
    }
};

const saveGames = async () => {
    try {
        // Get all game items
        const gameItems = elements.gamesEditor.querySelectorAll('.game-editor-item');
        const games = [];
        
        gameItems.forEach(item => {
            const index = item.dataset.index;
            const gameId = item.dataset.gameId;
            
            const name = document.getElementById(`game-name-${index}`).value;
            const type = document.getElementById(`game-type-${index}`).value;
            const url = document.getElementById(`game-url-${index}`).value;
            const imageUrl = document.getElementById(`game-image-url-${index}`)?.value || '';
            const externalUrl = document.getElementById(`game-external-url-${index}`)?.value || '';
            const description = document.getElementById(`game-description-${index}`).value;
            
            // Validate required fields
            if (!name || !url) {
                throw new Error('Game name and URL are required');
            }
            
            // Use existing ID if available, otherwise generate a new one
            const id = gameId || `game-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
            
            games.push({
                id,
                name,
                type: type || 'Unknown',
                url,
                image_url: imageUrl,
                external_url: externalUrl,
                description: description || ''
            });
        });
        
        // Create games config
        const gamesConfig = {
            games
        };
        
        // Save games using the API endpoint
        const response = await apiRequest(API.saveGames, 'POST', gamesConfig);
        
        if (response.success) {
            // Hide modal
            elements.editGamesModal.classList.remove('active');
            
            // Show success notification
            showNotification('Success', 'Games saved successfully', 'success');
            
            // Reload games
            loadGames();
        } else {
            throw new Error(response.message || 'Failed to save games');
        }
        
    } catch (error) {
        console.error('Error saving games:', error);
        showNotification('Error', `Failed to save games: ${error.message}`, 'error');
    }
};

// Modal handling
const initModals = () => {
    elements.closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            if (modal) {
                modal.classList.remove('active');
            }
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    });
};

// Initialize
const init = () => {
    initNavigation();
    initCreateLink();
    initLinkDetails();
    initGamesEditor();
    initModals();
    
    // Load dashboard by default
    loadDashboard();
};

// Helper function to get game name by ID
const getGameNameById = (gameId) => {
    const game = currentGames.find(g => g.id === gameId);
    return game ? game.name : 'Unknown Game';
};

// Start the app
document.addEventListener('DOMContentLoaded', init);
