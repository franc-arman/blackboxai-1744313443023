<?php
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php', 'Please login as an admin to manage courses.', 'error');
}

// Handle course creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $imageUrl = sanitize($_POST['image_url']);
        
        if (empty($title) || empty($description) || empty($imageUrl)) {
            throw new Exception("All fields are required.");
        }
        
        // Check if we're editing or creating
        if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
            // Update existing course
            $stmt = $db->prepare("
                UPDATE courses 
                SET title = ?, description = ?, image_url = ? 
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $imageUrl, $_POST['course_id']]);
            $message = "Course updated successfully.";
        } else {
            // Create new course
            $stmt = $db->prepare("
                INSERT INTO courses (title, description, image_url) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$title, $description, $imageUrl]);
            $message = "Course created successfully.";
        }
        
        redirect('/admin/manage-courses.php', $message, 'success');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle course deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $db = getDB();
        
        // First delete all course content
        $stmt = $db->prepare("DELETE FROM course_content WHERE course_id = ?");
        $stmt->execute([$_GET['delete']]);
        
        // Then delete the course
        $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        
        redirect('/admin/manage-courses.php', 'Course deleted successfully.', 'success');
    } catch (PDOException $e) {
        $error = "Failed to delete course.";
    }
}

// Get all courses
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.*, 
            (SELECT COUNT(*) FROM course_content WHERE course_id = c.id) as content_count 
        FROM courses c 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $error = "An error occurred while loading the courses.";
}

require_once '../includes/header.php';
?>

<div class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Manage Courses</h1>
            <p class="mt-2 text-gray-600">Add, edit, or remove courses from the platform.</p>
        </div>

        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <!-- Add New Course Form -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Add New Course</h2>
                <form action="/admin/manage-courses.php" method="POST">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">
                                Course Title
                            </label>
                            <input type="text" name="title" id="title" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">
                                Description
                            </label>
                            <textarea name="description" id="description" rows="3" required
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                        
                        <div>
                            <label for="image_url" class="block text-sm font-medium text-gray-700">
                                Course Image URL
                            </label>
                            <input type="url" name="image_url" id="image_url" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-2 text-sm text-gray-500">
                                Provide a URL for the course thumbnail image
                            </p>
                        </div>
                        
                        <div>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus mr-2"></i>
                                Add Course
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Courses List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Existing Courses</h2>
                
                <?php if (empty($courses)): ?>
                <p class="text-gray-600">No courses available.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Course
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Content Count
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <img class="h-10 w-10 rounded object-cover" 
                                                 src="<?php echo htmlspecialchars($course['image_url']); ?>" 
                                                 alt="">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($course['title']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo $course['content_count']; ?> items
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($course['created_at']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="/admin/manage-content.php?course_id=<?php echo $course['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i> Content
                                    </a>
                                    <a href="#" 
                                       onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)"
                                       class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-pencil-alt"></i> Edit
                                    </a>
                                    <a href="#" 
                                       onclick="confirmDelete(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['title']); ?>')"
                                       class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-lg w-full mx-4">
        <h2 class="text-xl font-bold mb-4">Edit Course</h2>
        <form action="/admin/manage-courses.php" method="POST">
            <input type="hidden" name="course_id" id="edit_course_id">
            
            <div class="mb-4">
                <label for="edit_title" class="block text-sm font-medium text-gray-700">Course Title</label>
                <input type="text" name="title" id="edit_title" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div class="mb-4">
                <label for="edit_description" class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" id="edit_description" rows="3" required
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="mb-4">
                <label for="edit_image_url" class="block text-sm font-medium text-gray-700">Image URL</label>
                <input type="url" name="image_url" id="edit_image_url" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editCourse(course) {
    document.getElementById('edit_course_id').value = course.id;
    document.getElementById('edit_title').value = course.title;
    document.getElementById('edit_description').value = course.description;
    document.getElementById('edit_image_url').value = course.image_url;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(courseId, courseTitle) {
    if (confirm(`Are you sure you want to delete the course "${courseTitle}"? This action cannot be undone.`)) {
        window.location.href = `/admin/manage-courses.php?delete=${courseId}`;
    }
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
