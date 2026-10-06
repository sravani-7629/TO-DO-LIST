/**
 * script.js - Frontend Application Logic & AJAX Controller
 * 
 * This file handles:
 * 1. Event listeners for adding, toggling, and deleting tasks.
 * 2. Fetch API calls (AJAX) to communicate with config.php backend.
 * 3. Dynamic DOM updates for Task List and Dashboard statistics without page reloads.
 * 4. User interface alerts and client-side input validation.
 */

document.addEventListener('DOMContentLoaded', () => {

    // DOM Element Selectors
    const addTaskForm = document.getElementById('addTaskForm');
    const taskTitleInput = document.getElementById('taskTitle');
    const taskDescInput = document.getElementById('taskDesc');
    const taskListContainer = document.getElementById('taskList');
    const alertBox = document.getElementById('alertBox');
    const listBadge = document.getElementById('listBadge');

    // Dashboard Statistics Selectors
    const totalTasksEl = document.getElementById('totalTasks');
    const completedTasksEl = document.getElementById('completedTasks');
    const pendingTasksEl = document.getElementById('pendingTasks');
    const completionPercentageEl = document.getElementById('completionPercentage');
    const progressBarEl = document.getElementById('progressBar');

    /**
     * Client-side HTML Escaping function to prevent XSS attacks.
     * 
     * @param {string} str Raw string
     * @returns {string} Escaped HTML string
     */
    function escapeHTML(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Formats a date string into readable format (e.g., "Sep 07, 2026 10:30 AM").
     * 
     * @param {string} dateStr ISO or SQL date string
     * @returns {string} Formatted date string
     */
    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        
        return date.toLocaleString('en-US', {
            month: 'short',
            day: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    /**
     * Displays a auto-dismissing feedback alert message at the top of the form.
     * 
     * @param {string} message Text message to display
     * @param {string} type 'success' or 'error'
     */
    function showAlert(message, type = 'success') {
        alertBox.className = `alert-box ${type}`;
        alertBox.innerHTML = `
            <span>${type === 'success' ? '✅' : '⚠️'}</span>
            <span>${escapeHTML(message)}</span>
        `;
        
        // Remove hidden class to display
        alertBox.classList.remove('hidden');

        // Automatically hide alert after 3.5 seconds
        setTimeout(() => {
            alertBox.classList.add('hidden');
        }, 3500);
    }

    /**
     * Updates the Dashboard statistics counters and animated progress bar.
     * 
     * @param {Object} stats Object containing total, completed, pending, percentage
     */
    function updateDashboard(stats) {
        if (!stats) return;

        totalTasksEl.textContent = stats.total;
        completedTasksEl.textContent = stats.completed;
        pendingTasksEl.textContent = stats.pending;
        completionPercentageEl.textContent = `${stats.percentage}%`;
        
        // Update animated progress bar width
        progressBarEl.style.width = `${stats.percentage}%`;

        // Update list total badge
        if (listBadge) {
            listBadge.textContent = `${stats.total} Items`;
        }
    }

    /**
     * Dynamically renders the task list items into the DOM.
     * 
     * @param {Array} tasks Array of task objects from PHP
     */
    function renderTasks(tasks) {
        if (!tasks || tasks.length === 0) {
            taskListContainer.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>No Tasks Available</h3>
                    <p>Your list is currently empty. Create a task above to get started!</p>
                </div>
            `;
            return;
        }

        const taskHTML = tasks.map(task => {
            const isCompleted = task.completed ? 'is-completed' : '';
            const isChecked = task.completed ? 'checked' : '';
            const formattedCreated = formatDate(task.created_at);
            const formattedCompleted = task.completed_at ? formatDate(task.completed_at) : '';

            return `
                <div class="task-card ${isCompleted}" data-id="${escapeHTML(task.id)}">
                    <div class="task-left">
                        <label class="checkbox-container">
                            <input 
                                type="checkbox" 
                                class="task-toggle" 
                                data-id="${escapeHTML(task.id)}" 
                                ${isChecked}
                            >
                            <span class="checkmark"></span>
                        </label>
                        <div class="task-content">
                            <h3 class="task-title">${escapeHTML(task.title)}</h3>
                            ${task.description ? `<p class="task-desc">${escapeHTML(task.description)}</p>` : ''}
                            <div class="task-meta">
                                <span class="meta-item">📅 Created: ${formattedCreated}</span>
                                ${task.completed_at ? `<span class="meta-item completed-date">✅ Completed: ${formattedCompleted}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="task-right">
                        <button class="btn-delete" data-id="${escapeHTML(task.id)}" title="Delete Task">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                            <span>Delete</span>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        taskListContainer.innerHTML = taskHTML;
    }

    /**
     * Generic helper function to send AJAX requests using Fetch API.
     * 
     * @param {Object} data Data payload to send to config.php
     * @returns {Promise<Object>} JSON response from server
     */
    async function sendAjaxRequest(data) {
        try {
            const response = await fetch('config.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error(`HTTP Error! Status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('AJAX Request Error:', error);
            return {
                success: false,
                message: 'Server connection failed. Please try again.'
            };
        }
    }

    /**
     * Event Handler: Add New Task Form Submission
     */
    addTaskForm.addEventListener('submit', async (e) => {
        e.preventDefault(); // Prevent standard browser page reload

        const title = taskTitleInput.value.trim();
        const description = taskDescInput.value.trim();

        // Client-side validation
        if (!title) {
            showAlert('Please enter a task title.', 'error');
            taskTitleInput.focus();
            return;
        }

        // Send AJAX add request
        const response = await sendAjaxRequest({
            action: 'add',
            title: title,
            description: description
        });

        if (response.success) {
            // Clear input fields
            addTaskForm.reset();
            
            // Update UI elements dynamically
            renderTasks(response.tasks);
            updateDashboard(response.stats);
            showAlert(response.message, 'success');
        } else {
            showAlert(response.message || 'Failed to add task.', 'error');
        }
    });

    /**
     * Event Delegation Handler: Handles Checkbox Toggles & Delete Buttons on Task List
     */
    taskListContainer.addEventListener('click', async (e) => {
        
        // Handle Checkbox Toggle
        if (e.target.classList.contains('task-toggle')) {
            const taskId = e.target.getAttribute('data-id');
            if (!taskId) return;

            const response = await sendAjaxRequest({
                action: 'toggle',
                id: taskId
            });

            if (response.success) {
                renderTasks(response.tasks);
                updateDashboard(response.stats);
                showAlert(response.message, 'success');
            } else {
                showAlert(response.message || 'Failed to update task status.', 'error');
                // Revert checkbox state
                e.target.checked = !e.target.checked;
            }
        }

        // Handle Delete Button Click
        const deleteBtn = e.target.closest('.btn-delete');
        if (deleteBtn) {
            const taskId = deleteBtn.getAttribute('data-id');
            if (!taskId) return;

            // Optional confirmation for better UX
            if (!confirm('Are you sure you want to delete this task?')) {
                return;
            }

            const response = await sendAjaxRequest({
                action: 'delete',
                id: taskId
            });

            if (response.success) {
                renderTasks(response.tasks);
                updateDashboard(response.stats);
                showAlert(response.message, 'success');
            } else {
                showAlert(response.message || 'Failed to delete task.', 'error');
            }
        }
    });

});
