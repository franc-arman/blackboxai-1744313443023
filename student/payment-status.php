<?php
require_once '../includes/functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || isAdmin()) {
    redirect('/login.php', 'Please login as a student to access payment status.', 'error');
}

// Get user details
$user = getUserDetails($_SESSION['user_id']);
$hasSubscription = hasActiveSubscription($_SESSION['user_id']);

// Handle payment proof upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please select a payment proof file to upload.");
        }

        // Upload the payment proof
        $uploadDir = '../uploads/payments/';
        $proofPath = uploadFile($_FILES['payment_proof'], $uploadDir);

        // Save payment record
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO payments (user_id, proof_path) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $proofPath]);

        redirect('/student/payment-status.php', 'Payment proof uploaded successfully. Please wait for admin verification.', 'success');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get payment history
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM payments 
        WHERE user_id = ? 
        ORDER BY submitted_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching payment history: " . $e->getMessage());
    $error = "An error occurred while loading payment history.";
}

require_once '../includes/header.php';
?>

<div class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Subscription Status</h1>
            <p class="mt-2 text-gray-600">Manage your subscription and payment details</p>
        </div>

        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <!-- Current Status -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Current Status</h2>
                <?php if ($hasSubscription): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                Your subscription is active until <?php echo formatDate($user['subscription_end']); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Your subscription is currently inactive. Please make a payment to access courses.
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment Information</h2>
                
                <!-- Bank Details -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Bank Transfer Details</h3>
                    <div class="bg-gray-50 p-4 rounded-md">
                        <p class="text-gray-600">Bank: Banco de Angola</p>
                        <p class="text-gray-600">Account: 1234-5678-9012</p>
                        <p class="text-gray-600">Name: AO Courses Ltd</p>
                    </div>
                </div>

                <!-- Multicaixa Express -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Multicaixa Express</h3>
                    <div class="bg-gray-50 p-4 rounded-md">
                        <p class="text-gray-600">Number: 923 456 789</p>
                        <p class="text-gray-600">Name: AO Courses</p>
                    </div>
                </div>

                <!-- Upload Payment Proof -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Upload Payment Proof</h3>
                    <form action="/student/payment-status.php" method="POST" enctype="multipart/form-data">
                        <div class="mt-1">
                            <input type="file" name="payment_proof" id="payment_proof" 
                                   accept=".jpg,.jpeg,.png,.pdf"
                                   class="block w-full text-sm text-gray-500
                                          file:mr-4 file:py-2 file:px-4
                                          file:rounded-md file:border-0
                                          file:text-sm file:font-semibold
                                          file:bg-blue-50 file:text-blue-700
                                          hover:file:bg-blue-100">
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Upload your payment receipt (JPG, JPEG, PNG or PDF, max 5MB)
                        </p>
                        <button type="submit" 
                                class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-upload mr-2"></i>
                            Upload Payment Proof
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment History</h2>
                <?php if (empty($payments)): ?>
                <p class="text-gray-600">No payment history available.</p>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Proof
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($payment['submitted_at']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusClass = '';
                                    switch($payment['status']) {
                                        case 'pending':
                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                            break;
                                        case 'approved':
                                            $statusClass = 'bg-green-100 text-green-800';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'bg-red-100 text-red-800';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="/uploads/payments/<?php echo $payment['proof_path']; ?>" 
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-500">
                                        <i class="fas fa-file-alt mr-1"></i>
                                        View Proof
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

<?php require_once '../includes/footer.php'; ?>
