<?php
/**
 * config.php - Configuration & Backend Controller
 * 
 * This file handles:
 * 1. File path configuration for data storage (tasks.json).
 * 2. Helper functions for CRUD operations (Create, Read, Update, Delete).
 * 3. Security sanitization helper functions.
 * 4. Statistics calculation helper function.
 * 5. Action-based AJAX API endpoint processing.
 */

// Define absolute or relative path to JSON storage file
define('DATA_DIR', __DIR__ . '/data');
define('TASKS_FILE', DATA_DIR . '/tasks.json');

/**
 * Ensures the data directory and tasks.json file exist.
 * Automatically creates them if missing.
 */
function ensure_data_file_exists() {
    if (!file_exists(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }
    if (!file_exists(TASKS_FILE)) {
        file_put_contents(TASKS_FILE, json_encode([], JSON_PRETTY_PRINT));
    }
}

/**
 * Reads and decodes tasks from tasks.json.
 * 
 * @return array Array of task associative arrays.
 */
function load_tasks() {
    ensure_data_file_exists();
    
    $json_content = file_get_contents(TASKS_FILE);
    $tasks = json_decode($json_content, true);
    
    // Return empty array if JSON is corrupted or invalid
    if (!is_array($tasks)) {
        return [];
    }
    
    return $tasks;
}

/**
 * Saves tasks array to tasks.json.
 * 
 * @param array $tasks Array of tasks to store.
 * @return bool True on success, false on failure.
 */
function save_tasks($tasks) {
    ensure_data_file_exists();
    
    // Re-index array keys cleanly (0, 1, 2...)
    $tasks = array_values($tasks);
    
    // Use JSON_PRETTY_PRINT for human-readable JSON storage
    $json_data = json_encode($tasks, JSON_PRETTY_PRINT);
    
    // LOCK_EX prevents race conditions during file writing
    return file_put_contents(TASKS_FILE, $json_data, LOCK_EX) !== false;
}

/**
 * Sanitizes user input to prevent XSS and unwanted formatting.
 * 
 * @param string $data Raw input string.
 * @return string Cleaned string.
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Computes dashboard statistics from tasks array.
 * 
 * @param array $tasks Array of task items.
 * @return array Statistics breakdown (total, completed, pending, percentage).
 */
function get_statistics($tasks) {
    $total = count($tasks);
    $completed = 0;
    
    foreach ($tasks as $task) {
        if (!empty($task['completed'])) {
            $completed++;
        }
    }
    
    $pending = $total - $completed;
    $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
    
    return [
        'total' => $total,
        'completed' => $completed,
        'pending' => $pending,
        'percentage' => $percentage
    ];
}

/**
 * Adds a new task to tasks.json.
 * 
 * @param string $title Task title.
 * @param string $description Task description.
 * @return array Result array with success boolean, message, and updated tasks.
 */
function add_task($title, $description = '') {
    $clean_title = sanitize_input($title);
    $clean_description = sanitize_input($description);
    
    if (empty($clean_title)) {
        return [
            'success' => false,
            'message' => 'Task title is required.'
        ];
    }
    
    $tasks = load_tasks();
    
    // Generate a unique ID using uniqid and random hex
    $new_task = [
        'id' => 'task_' . uniqid() . '_' . bin2hex(random_bytes(2)),
        'title' => $clean_title,
        'description' => $clean_description,
        'completed' => false,
        'created_at' => date('Y-m-d H:i:s'),
        'completed_at' => null
    ];
    
    // Add new task to top of list
    array_unshift($tasks, $new_task);
    
    if (save_tasks($tasks)) {
        return [
            'success' => true,
            'message' => 'Task added successfully!',
            'task' => $new_task,
            'tasks' => $tasks,
            'stats' => get_statistics($tasks)
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to save task to JSON file.'
        ];
    }
}

/**
 * Toggles completion status of a task by ID.
 * 
 * @param string $id Unique task identifier.
 * @return array Result array with success status, message, and updated data.
 */
function toggle_task($id) {
    $tasks = load_tasks();
    $found = false;
    $task_status = false;
    
    foreach ($tasks as &$task) {
        if ($task['id'] === $id) {
            $task['completed'] = !$task['completed'];
            $task['completed_at'] = $task['completed'] ? date('Y-m-d H:i:s') : null;
            $task_status = $task['completed'];
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        return [
            'success' => false,
            'message' => 'Task not found.'
        ];
    }
    
    if (save_tasks($tasks)) {
        $status_msg = $task_status ? 'Task marked as completed!' : 'Task marked as pending!';
        return [
            'success' => true,
            'message' => $status_msg,
            'tasks' => $tasks,
            'stats' => get_statistics($tasks)
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to update task status.'
        ];
    }
}

/**
 * Deletes a task from tasks.json by ID.
 * 
 * @param string $id Unique task identifier.
 * @return array Result array with success status, message, and updated data.
 */
function delete_task($id) {
    $tasks = load_tasks();
    $initial_count = count($tasks);
    
    // Filter out the task with matching ID
    $filtered_tasks = array_filter($tasks, function($task) use ($id) {
        return $task['id'] !== $id;
    });
    
    if (count($filtered_tasks) === $initial_count) {
        return [
            'success' => false,
            'message' => 'Task not found or already deleted.'
        ];
    }
    
    if (save_tasks($filtered_tasks)) {
        $updated_tasks = load_tasks();
        return [
            'success' => true,
            'message' => 'Task deleted successfully!',
            'tasks' => $updated_tasks,
            'stats' => get_statistics($updated_tasks)
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to delete task.'
        ];
    }
}


// ==========================================
// AJAX API ENDPOINT HANDLER
// ==========================================

// Check if request is sent via JSON input body
$raw_input = file_get_contents('php://input');
$json_input = json_decode($raw_input, true);

if (is_array($json_input)) {
    $_REQUEST = array_merge($_REQUEST, $json_input);
}

// Handle request if 'action' parameter is present
if (isset($_REQUEST['action'])) {
    // Set response header to JSON
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_REQUEST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];

    switch ($action) {
        case 'fetch':
            $tasks = load_tasks();
            $response = [
                'success' => true,
                'message' => 'Tasks loaded successfully',
                'tasks' => $tasks,
                'stats' => get_statistics($tasks)
            ];
            break;
            
        case 'add':
            $title = $_REQUEST['title'] ?? '';
            $description = $_REQUEST['description'] ?? '';
            $response = add_task($title, $description);
            break;
            
        case 'toggle':
            $id = $_REQUEST['id'] ?? '';
            $response = toggle_task($id);
            break;
            
        case 'delete':
            $id = $_REQUEST['id'] ?? '';
            $response = delete_task($id);
            break;

        default:
            $response = [
                'success' => false,
                'message' => 'Unknown action requested.'
            ];
            break;
    }

    echo json_encode($response);
    exit; // Terminate script execution for API response
}
