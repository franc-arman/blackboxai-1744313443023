<?php
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || isAdmin()) {
    redirect('/login.php', 'Please login as a student to access course content.', 'error');
}

// Check if course ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('/student/courses.php', 'Invalid course selected.', 'error');
}

$courseId = (int)$_GET['id'];

// Check subscription status
$hasSubscription = hasActiveSubscription($_SESSION['user_id']);

try {
    $db = getDB();
    
    // Get course details
    $stmt = $db->prepare("
        SELECT * FROM courses 
        WHERE id = ?
    ");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        redirect('/student/courses.php', 'Course not found.', 'error');
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
    error_log("Error fetching course details: " . $e->getMessage());
    $error = "An error occurred while loading the course.";
}

require_once '../includes/header.php';
?>

<div class="bg-gray-100 min-h-screen">
    <!-- Course Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?php echo htmlspecialchars($course['title']); ?>
                    </h1>
                    <p class="mt-2 text-gray-600">
                        <?php echo htmlspecialchars($course['description']); ?>
                    </p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="/student/courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Courses
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <?php if (!$hasSubscription): ?>
        <!-- Subscription Warning -->
        <div class="mb-8 bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Your subscription is inactive. Please make a payment to access course content.
                        <a href="/student/payment-status.php" class="font-medium underline text-yellow-700 hover:text-yellow-600">
                            Update your subscription
                        </a>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <!-- Course Content -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Course Content</h2>

                <?php if (empty($contents)): ?>
                <p class="text-gray-600">No content available for this course yet.</p>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($contents as $content): ?>
                    <div class="border rounded-lg p-4 <?php echo $hasSubscription ? 'hover:bg-gray-50' : 'opacity-75'; ?>">
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
                                        <?php echo ucfirst($content['content_type']); ?> Content
                                    </p>
                                </div>
                            </div>
                            
                            <?php if ($hasSubscription): ?>
                                <?php if ($content['content_type'] === 'video'): ?>
                                <button onclick="playVideo('<?php echo htmlspecialchars($content['content_link']); ?>')" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                    <i class="fas fa-play mr-2"></i>
                                    Watch Video
                                </button>
                                <?php else: ?>
                                <a href="<?php echo htmlspecialchars($content['content_link']); ?>" 
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                    <i class="fas fa-download mr-2"></i>
                                    Download
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button disabled 
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-400 cursor-not-allowed">
                                    <i class="fas fa-lock mr-2"></i>
                                    Subscribe to Access
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Video Modal -->
<div id="videoModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-4 max-w-4xl w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold">Video Content</h3>
            <button onclick="closeVideoModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="relative" style="padding-top: 56.25%">
            <iframe id="videoFrame"
                    class="absolute inset-0 w-full h-full"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
            </iframe>
        </div>
    </div>
</div>

<script>
function playVideo(videoUrl) {
    const modal = document.getElementById('videoModal');
    const videoFrame = document.getElementById('videoFrame');
    videoFrame.src = videoUrl;
    modal.classList.remove('hidden');
}

function closeVideoModal() {
    const modal = document.getElementById('videoModal');
    const videoFrame = document.getElementById('videoFrame');
    videoFrame.src = '';
    modal.classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('videoModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeVideoModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
