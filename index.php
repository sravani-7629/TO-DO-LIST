<?php
// Include configuration file and backend logic
require_once 'config.php';

// Fetch initial data for server-side rendering
$tasks = load_tasks();
$stats = get_statistics($tasks);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="A simple, beginner-friendly PHP To-Do List application using JSON file storage and AJAX.">
    <title>My Tasks | PHP & JSON To-Do List</title>
    <!-- Google Fonts for modern typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="app-container">
        <!-- Main Application Header -->
        <header class="app-header">
            <div class="header-content">
                <div class="logo-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20h9"></path>
                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                    </svg>
                </div>
                <div>
                    <h1 id="app-title">My Tasks</h1>
                    <p class="subtitle">Organize your daily work efficiently with PHP & AJAX</p>
                </div>
            </div>
        </header>

        <!-- Dashboard Statistics Section -->
        <section class="dashboard" aria-label="Task Statistics">
            <div class="stat-card">
                <div class="stat-icon total-icon">📋</div>
                <div class="stat-details">
                    <span class="stat-label">Total Tasks</span>
                    <span class="stat-value" id="totalTasks"><?php echo $stats['total']; ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon completed-icon">✅</div>
                <div class="stat-details">
                    <span class="stat-label">Completed</span>
                    <span class="stat-value" id="completedTasks"><?php echo $stats['completed']; ?></span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon pending-icon">⏳</div>
                <div class="stat-details">
                    <span class="stat-label">Pending</span>
                    <span class="stat-value" id="pendingTasks"><?php echo $stats['pending']; ?></span>
                </div>
            </div>

            <div class="stat-card progress-card">
                <div class="stat-details">
                    <span class="stat-label">Progress Rate</span>
                    <span class="stat-value" id="completionPercentage"><?php echo $stats['percentage']; ?>%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" id="progressBar" style="width: <?php echo $stats['percentage']; ?>%;"></div>
                </div>
            </div>
        </section>

        <!-- Alert Notification Message Box -->
        <div id="alertBox" class="alert-box hidden" role="alert"></div>

        <!-- Add Task Form Section -->
        <section class="form-section">
            <h2 class="section-title">Add New Task</h2>
            <form id="addTaskForm" autocomplete="off">
                <div class="form-group">
                    <label for="taskTitle">Task Title <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="taskTitle" 
                        name="title" 
                        placeholder="What do you need to do?" 
                        required 
                        maxlength="150"
                    >
                </div>

                <div class="form-group">
                    <label for="taskDesc">Description <span class="optional">(Optional)</span></label>
                    <textarea 
                        id="taskDesc" 
                        name="description" 
                        rows="2" 
                        placeholder="Add extra details or notes..."
                        maxlength="300"
                    ></textarea>
                </div>

                <button type="submit" id="btnSubmit" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Add Task</span>
                </button>
            </form>
        </section>

        <!-- Task List Section -->
        <section class="list-section">
            <div class="list-header">
                <h2 class="section-title">Task List</h2>
                <span class="badge" id="listBadge"><?php echo count($tasks); ?> Items</span>
            </div>

            <div id="taskList" class="task-list">
                <?php if (empty($tasks)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <h3>No Tasks Available</h3>
                        <p>Your list is currently empty. Create a task above to get started!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                        <div class="task-card <?php echo $task['completed'] ? 'is-completed' : ''; ?>" data-id="<?php echo htmlspecialchars($task['id']); ?>">
                            <div class="task-left">
                                <label class="checkbox-container">
                                    <input 
                                        type="checkbox" 
                                        class="task-toggle" 
                                        data-id="<?php echo htmlspecialchars($task['id']); ?>" 
                                        <?php echo $task['completed'] ? 'checked' : ''; ?>
                                    >
                                    <span class="checkmark"></span>
                                </label>
                                <div class="task-content">
                                    <h3 class="task-title"><?php echo htmlspecialchars($task['title']); ?></h3>
                                    <?php if (!empty($task['description'])): ?>
                                        <p class="task-desc"><?php echo htmlspecialchars($task['description']); ?></p>
                                    <?php endif; ?>
                                    <div class="task-meta">
                                        <span class="meta-item">
                                            📅 Created: <?php echo date('M d, Y h:i A', strtotime($task['created_at'])); ?>
                                        </span>
                                        <?php if (!empty($task['completed_at'])): ?>
                                            <span class="meta-item completed-date">
                                                ✅ Completed: <?php echo date('M d, Y h:i A', strtotime($task['completed_at'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="task-right">
                                <button 
                                    class="btn-delete" 
                                    data-id="<?php echo htmlspecialchars($task['id']); ?>" 
                                    title="Delete Task"
                                >
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
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Footer -->
        <footer class="app-footer">
            <p>PHP & JSON To-Do List Application &bull; Built for Beginners &amp; B.Tech Students</p>
        </footer>
    </div>

    <!-- JavaScript Application Logic -->
    <script src="js/script.js"></script>
</body>
</html>
