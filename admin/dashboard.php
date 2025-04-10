<?php
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php', 'Please login as an admin to access the dashboard.', 'error');
}

// Get total counts for dashboard
try {
    $db = getDB();
    
    // Count total students
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
    $stmt->execute();
    $totalStudents = $stmt->fetchColumn();

    // Count total courses
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM courses");
    $stmt->execute();
    $totalCourses = $stmt->fetchColumn();

    // Count total pending payments
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'");
    $stmt->execute();
    $totalPendingPayments = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    $error = "An error occurred while loading the dashboard.";
}

require_once '../includes/header.php';
?>

<div class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
            <p class="mt-2 text-gray-600">Manage the platform effectively.</p>
        </div>

        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900">Total Students</h2>
                <p class="text-3xl font-bold text-blue-600"><?php echo $totalStudents; ?></p>
            </div>
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900">Total Courses</h2>
                <p class="text-3xl font-bold text-blue-600"><?php echo $totalCourses; ?></p>
            </div>
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900">Pending Payments</h2>
                <p class="text-3xl font-bold text-blue-600"><?php echo $totalPendingPayments; ?></p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <a href="/admin/manage-students.php" 
                   class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-users text-blue-500 text-2xl"></i>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Manage Students</h3>
                        <p class="text-sm text-gray-500">View and manage student accounts</p>
                    </div>
                </a>
                <a href="/admin/manage-courses.php" 
                   class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-book text-green-500 text-2xl"></i>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Manage Courses</h3>
                        <p class="text-sm text-gray-500">Add, edit, or remove courses</p>
                    </div>
                </a>
                <a href="/admin/pending-payments.php" 
                   class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-money-bill text-yellow-500 text-2xl"></i>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Manage Payments</h3>
                        <p class="text-sm text-gray-500">Review and approve payments</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
