<?php 
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angola Online Course Platform</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="text-2xl font-bold text-blue-600">
                            AO Courses
                        </a>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex items-center space-x-4">
                    <?php if (!isLoggedIn()): ?>
                        <a href="/login.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                        <a href="/register.php" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">
                            <i class="fas fa-user-plus mr-2"></i>Register
                        </a>
                    <?php elseif (isAdmin()): ?>
                        <a href="/admin/dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a href="/admin/manage-courses.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-book mr-2"></i>Courses
                        </a>
                        <a href="/admin/pending-payments.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-money-bill mr-2"></i>Payments
                        </a>
                        <a href="/admin/manage-students.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-users mr-2"></i>Students
                        </a>
                        <a href="/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    <?php else: ?>
                        <a href="/student/dashboard.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a href="/student/courses.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-book mr-2"></i>My Courses
                        </a>
                        <a href="/student/payment-status.php" class="text-gray-700 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-credit-card mr-2"></i>Subscription
                        </a>
                        <a href="/logout.php" class="bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button class="mobile-menu-button">
                        <i class="fas fa-bars text-gray-500 text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="mobile-menu hidden md:hidden">
            <?php if (!isLoggedIn()): ?>
                <a href="/login.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
                <a href="/register.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-user-plus mr-2"></i>Register
                </a>
            <?php elseif (isAdmin()): ?>
                <a href="/admin/dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="/admin/manage-courses.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-book mr-2"></i>Courses
                </a>
                <a href="/admin/pending-payments.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-money-bill mr-2"></i>Payments
                </a>
                <a href="/admin/manage-students.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-users mr-2"></i>Students
                </a>
                <a href="/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            <?php else: ?>
                <a href="/student/dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="/student/courses.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-book mr-2"></i>My Courses
                </a>
                <a href="/student/payment-status.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-credit-card mr-2"></i>Subscription
                </a>
                <a href="/logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="max-w-7xl mx-auto px-4 mt-4">
        <?php echo displayMessage(); ?>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">
