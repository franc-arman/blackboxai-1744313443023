<?php
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || isAdmin()) {
    redirect('/login.php', 'Please login as a student to access courses.', 'error');
}

// Check subscription status
$hasSubscription = hasActiveSubscription($_SESSION['user_id']);

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
            <h1 class="text-3xl font-bold text-gray-900">Available Courses</h1>
            <p class="mt-2 text-gray-600">Explore our comprehensive collection of courses designed to help you succeed.</p>
        </div>

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

        <!-- Course Grid -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($courses as $course): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Course Image -->
                <div class="relative h-48">
                    <img class="w-full h-full object-cover" 
                         src="<?php echo htmlspecialchars($course['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($course['title']); ?>">
                    <div class="absolute top-0 right-0 p-2 bg-blue-600 text-white text-sm font-semibold">
                        <?php echo $course['content_count']; ?> Lessons
                    </div>
                </div>

                <!-- Course Details -->
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                        <?php echo htmlspecialchars($course['title']); ?>
                    </h3>
                    <p class="text-gray-600 mb-4">
                        <?php echo htmlspecialchars(substr($course['description'], 0, 150)) . '...'; ?>
                    </p>

                    <!-- Course Meta -->
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <span>Added <?php echo formatDate($course['created_at']); ?></span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <?php if ($hasSubscription): ?>
                    <a href="/student/course_detail.php?id=<?php echo $course['id']; ?>" 
                       class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-150">
                        <i class="fas fa-play-circle mr-2"></i>Start Learning
                    </a>
                    <?php else: ?>
                    <button disabled 
                            class="block w-full text-center bg-gray-300 text-gray-500 py-2 px-4 rounded-md cursor-not-allowed">
                        <i class="fas fa-lock mr-2"></i>Subscribe to Access
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($courses)): ?>
        <div class="text-center py-12">
            <div class="text-gray-500">
                <i class="fas fa-book-open text-4xl mb-4"></i>
                <p class="text-xl">No courses available at the moment.</p>
                <p class="mt-2">Please check back later for new courses.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
