<?php
require_once 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = getDB();
        
        // Validate input
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception("All fields are required.");
        }
        
        if (!validateEmail($email)) {
            throw new Exception("Invalid email format.");
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception("Passwords do not match.");
        }
        
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Email already registered.");
        }
        
        // Handle payment proof upload if provided
        $paymentProofPath = null;
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $paymentProofPath = uploadFile($_FILES['payment_proof'], 'uploads/payments/');
        }
        
        // Create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword]);
        $userId = $db->lastInsertId();
        
        // Create payment record if proof was uploaded
        if ($paymentProofPath) {
            $stmt = $db->prepare("INSERT INTO payments (user_id, proof_path) VALUES (?, ?)");
            $stmt->execute([$userId, $paymentProofPath]);
        }
        
        redirect('login.php', 'Registration successful! Please login after your payment is verified.', 'success');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Create your account
        </h2>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="register.php" method="POST" enctype="multipart/form-data">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Full Name
                    </label>
                    <div class="mt-1">
                        <input id="name" name="name" type="text" required 
                            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email address
                    </label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" required
                            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" required
                            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                        Confirm Password
                    </label>
                    <div class="mt-1">
                        <input id="confirm_password" name="confirm_password" type="password" required
                            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="bg-gray-50 p-4 rounded-md">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Information</h3>
                    
                    <!-- Bank Details -->
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Bank Transfer Details:</h4>
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <p class="text-sm text-gray-600">Bank: Banco de Angola</p>
                            <p class="text-sm text-gray-600">Account: 1234-5678-9012</p>
                            <p class="text-sm text-gray-600">Name: AO Courses Ltd</p>
                        </div>
                    </div>

                    <!-- Multicaixa Express -->
                    <div class="mb-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Multicaixa Express:</h4>
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <p class="text-sm text-gray-600">Number: 923 456 789</p>
                            <p class="text-sm text-gray-600">Name: AO Courses</p>
                        </div>
                    </div>

                    <!-- Payment Proof Upload -->
                    <div class="mt-4">
                        <label for="payment_proof" class="block text-sm font-medium text-gray-700">
                            Upload Payment Proof
                        </label>
                        <div class="mt-1">
                            <input id="payment_proof" name="payment_proof" type="file" accept=".jpg,.jpeg,.png,.pdf"
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Upload your payment receipt (JPG, PNG, or PDF, max 5MB)
                        </p>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Register
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            Already have an account? <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500">Login</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
