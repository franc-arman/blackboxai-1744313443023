<?php
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php', 'Please login as an admin to manage course content.', 'error');
}

// Check if course ID is provided
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    redirect('/admin/manage-courses.php', 'Invalid course selected.', 'error');
}

$courseId = (int)$_GET['course_id'];

// Handle content creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        
        $title = sanitize($_POST['title']);
        $contentType = sanitize($_POST['content_type']);
        $contentLink = sanitize($_POST['content_link']);
        $position = (int)$_POST['position'];
        
        if (empty($title) || empty($contentType) || empty($contentLink)) {
            throw new Exception("All fields are required.");
        }
        
        if (!in_array($contentType, ['video', 'pdf', 'ebook'])) {
            throw new Exception("Invalid content type.");
        }
        
        // Check if we're editing or creating
        if (isset($_POST['content_id']) && !empty($_POST['content_id'])) {
            // Update existing content
            $stmt = $db->prepare("
                UPDATE course_content 
                SET title = ?, content_type = ?, content_link = ?, position = ? 
                WHERE id = ? AND course_id = ?
            ");
            $stmt->execute([$title, $contentType, $contentLink, $position, $_POST['content_id'], $courseId]);
            $message = "Content updated successfully.";
        } else {
            // Create new content
            $stmt = $db->prepare("
                INSERT INTO course_content (course_id, title, content_type, content_link, position) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$courseId, $title, $contentType, $contentLink, $position]);
            $message = "Content added successfully.";
        }
        
        redirect("/admin/manage-content.php?course_id=$courseId", $message, 'success');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle content deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM course_content WHERE id = ? AND course_id = ?");
        $stmt->execute([$_GET['delete'], $courseId]);
        redirect("/admin/manage-content.php?course_id=$courseId", 'Content deleted successfully.', 'success');
    } catch (PDOException $e) {
        $error = "Failed to delete content.";
    }
}

// Get course details and content
try {
    $db = getDB();
    
    // Get course details
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        redirect('/admin/manage-courses.php', 'Course not found.', 'error');
    }
    
    // Get course content
    $stmt = $db->prepare("
        SELECT * FROM course_content 
        WHERE course_id = ? 
        ORDER BY position ASC
    ");
    $stmt->execute([$courseId]);
    $contents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching course data: " . $e->getMessage());
    $error = "An error occurred while loading the course data.";
}

require_once '../includes/header.php';
?>

<div class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Manage Course Content
                    </h1>
                    <p class="mt-2 text-gray-600">
                        <?php echo htmlspecialchars($course['title']); ?>
                    </p>
                </div>
                <a href="/admin/manage-courses.php" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Courses
                </a>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <!-- Add Content Form -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Add New Content</h2>
                <form action="/admin/manage-content.php?course_id=<?php echo $courseId; ?>" method="POST">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">
                                Content Title
                            </label>
                            <input type="text" name="title" id="title" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label for="content_type" class="block text-sm font-medium text-gray-700">
                                Content Type
                            </label>
                            <select name="content_type" id="content_type" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="video">Video</option>
                                <option value="pdf">PDF</option>
                                <option value="ebook">eBook</option>
                            </select>
                        </div>
                        
                        <div class="sm:col-span-2">
                            <label for="content_link" class="block text-sm font-medium text-gray-700">
                                Content Link
                            </label>
                            <input type="url" name="content_link" id="content_link" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <p class="mt-2 text-sm text-gray-500">
                                For videos, use embedded video links (e.g., YouTube embed URL). For PDFs and eBooks, provide download links.
                            </p>
                        </div>
                        
                        <div>
                            <label for="position" class="block text-sm font-medium text-gray-700">
                                Position
                            </label>
                            <input type="number" name="position" id="position" required min="1"
                                   value="<?php echo count($contents) + 1; ?>"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus mr-2"></i>
                                Add Content
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Content List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Course Content</h2>
                
                <?php if (empty($contents)): ?>
                <p class="text-gray-600">No content available for this course.</p>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($contents as $content): ?>
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <?php 
                                $icon = '';
                                switch($content['content_type']) {
                                    case 'video':
                                        $icon = 'fa-play-circle text-blue-500';
                                        break;
                                    case 'pdf':
                                        $icon = 'fa-file-pdf text-red-500';
                                        break;
                                    case 'ebook':
                                        $icon = 'fa-book text-green-500';
                                        break;
                                }
                                ?>
                                <i class="fas <?php echo $icon; ?> text-2xl mr-3"></i>
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <?php echo htmlspecialchars($content['title']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        Type: <?php echo ucfirst($content['content_type']); ?> | 
                                        Position: <?php echo $content['position']; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-3">
                                <a href="#" 
                                   onclick="editContent(<?php echo htmlspecialchars(json_encode($content)); ?>)"
                                   class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="#" 
                                   onclick="confirmDelete(<?php echo $content['id']; ?>, '<?php echo htmlspecialchars($content['title']); ?>')"
                                   class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Content Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-lg w-full mx-4">
        <h2 class="text-xl font-bold mb-4">Edit Content</h2>
        <form action="/admin/manage-content.php?course_id=<?php echo $courseId; ?>" method="POST">
            <input type="hidden" name="content_id" id="edit_content_id">
            
            <div class="mb-4">
                <label for="edit_title" class="block text-sm font-medium text-gray-700">Content Title</label>
                <input type="text" name="title" id="edit_title" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div class="mb-4">
                <label for="edit_content_type" class="block text-sm font-medium text-gray-700">Content Type</label>
                <select name="content_type" id="edit_content_type" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="video">Video</option>
                    <option value="pdf">PDF</option>
                    <option value="ebook">eBook</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label for="edit_content_link" class="block text-sm font-medium text-gray-700">Content Link</label>
                <input type="url" name="content_link" id="edit_content_link" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div class="mb-4">
                <label for="edit_position" class="block text-sm font-medium text-gray-700">Position</label>
                <input type="number" name="position" id="edit_position" required min="1"
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
function editContent(content) {
    document.getElementById('edit_content_id').value = content.id;
    document.getElementById('edit_title').value = content.title;
    document.getElementById('edit_content_type').value = content.content_type;
    document.getElementById('edit_content_link').value = content.content_link;
    document.getElementById('edit_position').value = content.position;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(contentId, contentTitle) {
    if (confirm(`Are you sure you want to delete "${contentTitle}"? This action cannot be undone.`)) {
        window.location.href = `/admin/manage-content.php?course_id=<?php echo $courseId; ?>&delete=${contentId}`;
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
