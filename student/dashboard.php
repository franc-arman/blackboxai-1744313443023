<?php
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || isAdmin()) {
    redirect('/login.php', 'Please login as a student to access the dashboard.', 'error');
}

// Get user details
$user = getUserDetails($_SESSION['user_id']);

// Check subscription status
$hasSubscription = hasActiveSubscription($_SESSION['user_id']);

// Check for subscription expiration
checkSubscriptionExpiration($_SESSION['user_id']);

// Get user's courses
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.* 
        FROM courses c 
        ORDER BY c.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get notifications
    $stmt = $db->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    $error = "An error occurred while loading the dashboard.";
}

require_once '../includes/header.php';
?>

<div class="bg-gray-100 min-h-screen">
    <!-- Welcome Banner -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900">
                Welcome back, <?php echo htmlspecialchars($user['name']); ?>!
            </h1>
        </div>
    </div>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Subscription Status -->
        <div class="mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Subscription Status</h2>
                    <?php if ($hasSubscription): ?>
                        <div class="bg-green-100 p-4 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">
                                        Your subscription is active until <?php echo formatDate($user['subscription_end']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-100 p-4 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-circle text-yellow-400 text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-yellow-800">
                                        Your subscription is inactive. Please make a payment to access courses.
                                    </p>
                                    <div class="mt-2">
                                        <a href="/student/payment-status.php" class="text-sm font-medium text-yellow-800 hover:text-yellow-700">
                                            Make Payment <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Recent Courses -->
            <div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Courses</h2>
                        <?php if (!empty($courses)): ?>
                            <div class="space-y-4">
                                <?php foreach ($courses as $course): ?>
                                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            <?php echo htmlspecialchars($course['title']); ?>
                                        </h3>
                                        <p class="mt-1 text-gray-600">
                                            <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?>
                                        </p>
                                        <a href="/student/course_detail.php?id=<?php echo $course['id']; ?>" 
                                           class="mt-2 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-500">
                                            View Course <i class="fas fa-arrow-right ml-1"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4">
                                <a href="/student/courses.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                    View All Courses <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-600">No courses available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div>
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Notifications</h2>
                        <?php if (!empty($notifications)): ?>
                            <div class="space-y-4">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="border-l-4 border-blue-400 bg-blue-50 p-4">
                                        <div class="flex justify-between">
                                            <p class="text-sm text-blue-700">
                                                <?php echo htmlspecialchars($notification['message']); ?>
                                            </p>
                                            <span class="text-sm text-blue-500">
                                                <?php echo formatDate($notification['created_at']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-600">No new notifications.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <a href="/student/courses.php" 
                           class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-book text-blue-500 text-2xl"></i>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Browse Courses</h3>
                                <p class="text-sm text-gray-500">Explore our course catalog</p>
                            </div>
                        </a>
                        <a href="/student/payment-status.php" 
                           class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-credit-card text-green-500 text-2xl"></i>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Payment Status</h3>
                                <p class="text-sm text-gray-500">View or update subscription</p>
                            </div>
                        </a>
                        <a href="#" 
                           class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-user text-purple-500 text-2xl"></i>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Profile</h3>
                                <p class="text-sm text-gray-500">Update your information</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
