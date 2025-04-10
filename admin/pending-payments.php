<?php
require_once '../includes/functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/login.php', 'Please login as an admin to manage payments.', 'error');
}

// Handle payment approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
    try {
        $db = getDB();
        
        $paymentId = (int)$_POST['payment_id'];
        $action = $_POST['action'];
        $userId = (int)$_POST['user_id'];
        
        if (!in_array($action, ['approve', 'reject'])) {
            throw new Exception("Invalid action.");
        }
        
        // Start transaction
        $db->beginTransaction();
        
        // Update payment status
        $stmt = $db->prepare("
            UPDATE payments 
            SET status = ?, reviewed_at = NOW() 
            WHERE id = ?
        ");
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt->execute([$status, $paymentId]);
        
        if ($action === 'approve') {
            // Update user subscription
            $stmt = $db->prepare("
                UPDATE users 
                SET subscription_end = DATE_ADD(GREATEST(COALESCE(subscription_end, NOW()), NOW()), INTERVAL 30 DAY)
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Create notification
            createNotification($userId, "Your payment has been approved. Your subscription is now active.");
        } else {
            // Create rejection notification
            createNotification($userId, "Your payment has been rejected. Please submit a new payment proof.");
        }
        
        $db->commit();
        redirect('/admin/pending-payments.php', 'Payment ' . $status . ' successfully.', 'success');
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error processing payment: " . $e->getMessage());
        $error = "Failed to process payment.";
    }
}

// Get pending payments
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, u.name as student_name, u.email as student_email 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.status = 'pending' 
        ORDER BY p.submitted_at DESC
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching payments: " . $e->getMessage());
    $error = "An error occurred while loading the payments.";
}

require_once '../includes/header.php';
?>

<div class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Pending Payments</h1>
            <p class="mt-2 text-gray-600">Review and process student payment submissions.</p>
        </div>

        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
        <?php endif; ?>

        <!-- Payments List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <?php if (empty($payments)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-check-circle text-green-500 text-4xl mb-4"></i>
                    <p class="text-xl text-gray-600">No pending payments to review.</p>
                </div>
                <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($payments as $payment): ?>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                            <div class="mb-4 md:mb-0">
                                <h3 class="text-lg font-medium text-gray-900">
                                    Payment from <?php echo htmlspecialchars($payment['student_name']); ?>
                                </h3>
                                <p class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($payment['student_email']); ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Submitted: <?php echo formatDate($payment['submitted_at']); ?>
                                </p>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <form action="/admin/pending-payments.php" method="POST" class="inline">
                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $payment['user_id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" 
                                            onclick="return confirm('Are you sure you want to approve this payment?')"
                                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                        <i class="fas fa-check mr-2"></i>
                                        Approve
                                    </button>
                                </form>
                                
                                <form action="/admin/pending-payments.php" method="POST" class="inline">
                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $payment['user_id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" 
                                            onclick="return confirm('Are you sure you want to reject this payment?')"
                                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                        <i class="fas fa-times mr-2"></i>
                                        Reject
                                    </button>
                                </form>
                                
                                <a href="/uploads/payments/<?php echo $payment['proof_path']; ?>" 
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-eye mr-2"></i>
                                    View Proof
                                </a>
                            </div>
                        </div>
                        
                        <!-- Payment Proof Preview -->
                        <div class="mt-4">
                            <?php
                            $fileExtension = strtolower(pathinfo($payment['proof_path'], PATHINFO_EXTENSION));
                            if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])):
                            ?>
                            <img src="/uploads/payments/<?php echo $payment['proof_path']; ?>" 
                                 alt="Payment Proof" 
                                 class="max-w-lg rounded-lg shadow">
                            <?php else: ?>
                            <div class="bg-gray-100 p-4 rounded-lg inline-block">
                                <i class="fas fa-file-pdf text-red-500 text-2xl mr-2"></i>
                                PDF Document
                            </div>
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

<?php require_once '../includes/footer.php'; ?>
