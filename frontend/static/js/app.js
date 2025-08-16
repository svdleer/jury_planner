// Water Polo Jury Planner - Main JavaScript

class JuryPlannerApp {
    constructor() {
        this.apiBase = '/api';
        this.currentSection = 'dashboard';
        this.teams = [];
        this.matches = [];
        this.rules = [];
        this.ruleTemplates = {};
        
        this.init();
    }
    
    init() {
        this.setupNavigation();
        this.loadDashboard();
        this.setupEventListeners();
    }
    
    setupNavigation() {
        document.querySelectorAll('.nav-link[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.getAttribute('data-section');
                this.showSection(section);
            });
        });
    }
    
    setupEventListeners() {
        // Planning form
        const planningForm = document.getElementById('planning-form');
        if (planningForm) {
            planningForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.runPlanning();
            });
        }
        
        // Team form
        const teamForm = document.getElementById('team-form');
        if (teamForm) {
            teamForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveTeam();
            });
        }
    }
    
    showSection(sectionName) {
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(section => {
            section.style.display = 'none';
        });
        
        // Show target section
        const targetSection = document.getElementById(`${sectionName}-section`);
        if (targetSection) {
            targetSection.style.display = 'block';
            this.currentSection = sectionName;
        }
        
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        const activeLink = document.querySelector(`.nav-link[data-section="${sectionName}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
        
        // Load section data
        this.loadSectionData(sectionName);
    }
    
    loadSectionData(sectionName) {
        switch(sectionName) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'teams':
                this.loadTeams();
                break;
            case 'schedule':
                this.loadSchedule();
                break;
            case 'rules':
                this.loadRules();
                break;
            case 'planning':
                this.loadPlanning();
                break;
        }
    }
    
    async loadDashboard() {
        try {
            const response = await fetch(`${this.apiBase}/dashboard/stats`);
            const stats = await response.json();
            
            if (response.ok) {
                document.getElementById('total-teams').textContent = stats.total_teams;
                document.getElementById('total-matches').textContent = stats.total_matches;
                document.getElementById('planned-matches').textContent = stats.planned_matches;
                document.getElementById('active-rules').textContent = stats.active_rules;
                
                this.renderRecentSessions(stats.recent_sessions);
            } else {
                this.showError('Failed to load dashboard statistics');
            }
        } catch (error) {
            console.error('Error loading dashboard:', error);
            this.showError('Failed to load dashboard');
        }
    }
    
    renderRecentSessions(sessions) {
        const container = document.getElementById('recent-sessions');
        
        if (!sessions || sessions.length === 0) {
            container.innerHTML = '<p class="text-muted">No recent planning sessions.</p>';
            return;
        }
        
        let html = '<div class="list-group">';
        sessions.forEach(session => {
            const statusClass = `status-${session.status}`;
            const statusIcon = this.getStatusIcon(session.status);
            const executionTime = session.execution_time ? 
                `${session.execution_time.toFixed(2)}s` : 'N/A';
            
            html += `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">${session.name}</h6>
                            <p class="mb-1 text-muted small">
                                Created: ${new Date(session.created_at).toLocaleDateString()}
                            </p>
                        </div>
                        <div class="text-end">
                            <span class="badge ${statusClass}">
                                ${statusIcon} ${session.status.toUpperCase()}
                            </span>
                            <small class="d-block text-muted">${executionTime}</small>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        container.innerHTML = html;
    }
    
    getStatusIcon(status) {
        const icons = {
            'pending': '<i class="fas fa-clock"></i>',
            'running': '<i class="fas fa-spinner fa-spin"></i>',
            'completed': '<i class="fas fa-check-circle"></i>',
            'failed': '<i class="fas fa-exclamation-triangle"></i>'
        };
        return icons[status] || '<i class="fas fa-question-circle"></i>';
    }
    
    async loadTeams() {
        try {
            const response = await fetch(`${this.apiBase}/teams`);
            const teams = await response.json();
            
            if (response.ok) {
                this.teams = teams;
                this.renderTeamsTable(teams);
                this.populateTeamSelects(teams);
            } else {
                this.showError('Failed to load teams');
            }
        } catch (error) {
            console.error('Error loading teams:', error);
            this.showError('Failed to load teams');
        }
    }
    
    renderTeamsTable(teams) {
        const container = document.getElementById('teams-table');
        
        if (!teams || teams.length === 0) {
            container.innerHTML = '<p class="text-muted">No teams found. Add a team to get started.</p>';
            return;
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Weight</th>
                            <th>Contact</th>
                            <th>Dedicated To</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        teams.forEach(team => {
            const weightClass = this.getWeightClass(team.weight);
            const statusBadge = team.is_active ? 
                '<span class="badge bg-success">Active</span>' : 
                '<span class="badge bg-secondary">Inactive</span>';
            
            html += `
                <tr>
                    <td>
                        <strong>${team.name}</strong>
                    </td>
                    <td>
                        <span class="${weightClass}">${team.weight}</span>
                    </td>
                    <td>
                        ${team.contact_person || '-'}<br>
                        <small class="text-muted">${team.email || ''}</small>
                    </td>
                    <td>
                        ${team.dedicated_to_team_name || '-'}
                    </td>
                    <td>
                        ${statusBadge}
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="app.editTeam(${team.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="app.deleteTeam(${team.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        container.innerHTML = html;
    }
    
    getWeightClass(weight) {
        if (weight >= 1.5) return 'weight-high';
        if (weight >= 1.0) return 'weight-medium';
        return 'weight-low';
    }
    
    populateTeamSelects(teams) {
        const selects = document.querySelectorAll('#filter-team');
        selects.forEach(select => {
            // Clear existing options except "All Teams"
            while (select.children.length > 1) {
                select.removeChild(select.lastChild);
            }
            
            teams.forEach(team => {
                const option = document.createElement('option');
                option.value = team.id;
                option.textContent = team.name;
                select.appendChild(option);
            });
        });
    }
    
    async loadSchedule() {
        const filters = this.getScheduleFilters();
        const queryParams = new URLSearchParams(filters);
        
        try {
            const response = await fetch(`${this.apiBase}/matches?${queryParams}`);
            const matches = await response.json();
            
            if (response.ok) {
                this.matches = matches;
                this.renderScheduleTable(matches);
            } else {
                this.showError('Failed to load schedule');
            }
        } catch (error) {
            console.error('Error loading schedule:', error);
            this.showError('Failed to load schedule');
        }
    }
    
    getScheduleFilters() {
        const filters = {};
        
        const startDate = document.getElementById('filter-start-date')?.value;
        const endDate = document.getElementById('filter-end-date')?.value;
        const teamId = document.getElementById('filter-team')?.value;
        
        if (startDate) filters.start_date = startDate;
        if (endDate) filters.end_date = endDate;
        if (teamId) filters.home_team_id = teamId;
        
        return filters;
    }
    
    renderScheduleTable(matches) {
        const container = document.getElementById('schedule-table');
        
        if (!matches || matches.length === 0) {
            container.innerHTML = '<p class="text-muted">No matches found for the selected criteria.</p>';
            return;
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Match</th>
                            <th>Location</th>
                            <th>Jury Assignments</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        matches.forEach(match => {
            const statusBadge = match.is_planned ? 
                '<span class="badge bg-success">Planned</span>' : 
                '<span class="badge bg-warning">Pending</span>';
            
            let assignmentsList = '';
            if (match.jury_assignments && match.jury_assignments.length > 0) {
                assignmentsList = match.jury_assignments.map(assignment => {
                    const dutyClass = `duty-${assignment.duty_type}`;
                    return `
                        <span class="badge ${dutyClass} me-1">
                            ${assignment.jury_team.name} (${assignment.duty_type.replace('_', ' ')})
                        </span>
                    `;
                }).join('');
            } else {
                assignmentsList = '<span class="text-muted">No assignments</span>';
            }
            
            html += `
                <tr>
                    <td>${match.date}</td>
                    <td>${match.time}</td>
                    <td>
                        <strong>${match.home_team.name}</strong> vs <strong>${match.away_team.name}</strong><br>
                        <small class="text-muted">${match.competition || ''}</small>
                    </td>
                    <td>${match.location || '-'}</td>
                    <td>${assignmentsList}</td>
                    <td>${statusBadge}</td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        container.innerHTML = html;
    }
    
    async loadRules() {
        try {
            const [rulesResponse, templatesResponse] = await Promise.all([
                fetch(`${this.apiBase}/rules`),
                fetch(`${this.apiBase}/rules/templates`)
            ]);
            
            const rules = await rulesResponse.json();
            const templates = await templatesResponse.json();
            
            if (rulesResponse.ok && templatesResponse.ok) {
                this.rules = rules;
                this.ruleTemplates = templates;
                this.renderRulesTable(rules);
            } else {
                this.showError('Failed to load rules');
            }
        } catch (error) {
            console.error('Error loading rules:', error);
            this.showError('Failed to load rules');
        }
    }
    
    renderRulesTable(rules) {
        const container = document.getElementById('rules-table');
        
        if (!rules || rules.length === 0) {
            container.innerHTML = '<p class="text-muted">No rules configured. Add rules to customize planning behavior.</p>';
            return;
        }
        
        let html = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Weight</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        rules.forEach(rule => {
            const typeClass = `rule-type-${rule.rule_type.replace('_', '-')}`;
            const statusBadge = rule.is_active ? 
                '<span class="badge bg-success">Active</span>' : 
                '<span class="badge bg-secondary">Inactive</span>';
            
            html += `
                <tr>
                    <td><strong>${rule.name}</strong></td>
                    <td>
                        <span class="badge ${typeClass}">
                            ${rule.rule_type.replace('_', ' ').toUpperCase()}
                        </span>
                    </td>
                    <td>${rule.weight}</td>
                    <td>
                        <span class="text-truncate" style="max-width: 200px; display: inline-block;" 
                              title="${rule.description}">
                            ${rule.description}
                        </span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="app.editRule(${rule.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="app.deleteRule(${rule.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        container.innerHTML = html;
    }
    
    async runPlanning() {
        const form = document.getElementById('planning-form');
        const formData = new FormData(form);
        
        const data = {
            name: document.getElementById('planning-name').value,
            start_date: document.getElementById('planning-start-date').value,
            end_date: document.getElementById('planning-end-date').value
        };
        
        const statusDiv = document.getElementById('planning-status');
        statusDiv.innerHTML = `
            <div class="alert alert-info">
                <div class="loading-spinner"></div>
                Running planning algorithm...
            </div>
        `;
        
        try {
            const response = await fetch(`${this.apiBase}/planning/run`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (response.ok) {
                statusDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        ${result.message}
                    </div>
                    <div class="mt-3">
                        <h6>Results Summary:</h6>
                        <ul class="list-unstyled">
                            <li><strong>Session ID:</strong> ${result.session_id}</li>
                            <li><strong>Status:</strong> ${result.result.status}</li>
                            <li><strong>Execution Time:</strong> ${result.result.solve_time.toFixed(2)}s</li>
                            <li><strong>Assignments Created:</strong> ${result.result.assignments.length}</li>
                        </ul>
                    </div>
                `;
                
                // Refresh dashboard and schedule
                if (this.currentSection === 'dashboard') {
                    this.loadDashboard();
                }
                
                form.reset();
            } else {
                statusDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        ${result.error}
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error running planning:', error);
            statusDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Failed to run planning algorithm.
                </div>
            `;
        }
    }
    
    showAddTeamModal() {
        const modal = new bootstrap.Modal(document.getElementById('addTeamModal'));
        modal.show();
    }
    
    async saveTeam() {
        const teamData = {
            name: document.getElementById('team-name').value,
            weight: parseFloat(document.getElementById('team-weight').value),
            contact_person: document.getElementById('team-contact').value,
            email: document.getElementById('team-email').value,
            phone: document.getElementById('team-phone').value
        };
        
        try {
            const response = await fetch(`${this.apiBase}/teams`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(teamData)
            });
            
            const result = await response.json();
            
            if (response.ok) {
                this.showSuccess('Team created successfully');
                const modal = bootstrap.Modal.getInstance(document.getElementById('addTeamModal'));
                modal.hide();
                document.getElementById('team-form').reset();
                this.loadTeams();
            } else {
                this.showError(result.error || 'Failed to create team');
            }
        } catch (error) {
            console.error('Error saving team:', error);
            this.showError('Failed to save team');
        }
    }
    
    clearFilters() {
        document.getElementById('filter-start-date').value = '';
        document.getElementById('filter-end-date').value = '';
        document.getElementById('filter-team').value = '';
        document.getElementById('filter-duty').value = '';
    }
    
    async exportSchedule(format) {
        const filters = this.getScheduleFilters();
        const queryParams = new URLSearchParams(filters);
        
        try {
            const response = await fetch(`${this.apiBase}/export/${format}?${queryParams}`);
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `jury_schedule.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.showSuccess(`Schedule exported as ${format.toUpperCase()}`);
            } else {
                this.showError('Failed to export schedule');
            }
        } catch (error) {
            console.error('Error exporting schedule:', error);
            this.showError('Failed to export schedule');
        }
    }
    
    showSuccess(message) {
        this.showToast(message, 'success');
    }
    
    showError(message) {
        this.showToast(message, 'danger');
    }
    
    showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container') || this.createToastContainer();
        
        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                        data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    
    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
    
    loadPlanning() {
        // Set default dates
        const today = new Date();
        const nextWeek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
        
        document.getElementById('planning-start-date').value = today.toISOString().split('T')[0];
        document.getElementById('planning-end-date').value = nextWeek.toISOString().split('T')[0];
        document.getElementById('planning-name').value = `Planning ${today.toLocaleDateString()}`;
    }
}

// Initialize the application
const app = new JuryPlannerApp();

// Global functions for onclick handlers
window.showSection = (section) => app.showSection(section);
window.showAddTeamModal = () => app.showAddTeamModal();
window.saveTeam = () => app.saveTeam();
window.loadSchedule = () => app.loadSchedule();
window.clearFilters = () => app.clearFilters();
window.exportSchedule = (format) => app.exportSchedule(format);

// Make app globally available for debugging
window.app = app;
