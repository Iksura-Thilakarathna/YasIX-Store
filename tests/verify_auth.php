<?php

// Day 2 Authentication Verification Suite
// Execution context setup
chdir(__DIR__ . '/..');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Services\UserService;
use App\Repositories\UserRepository;
use App\Validators\Validator;
use App\Helpers\Session;
use App\Models\User;

echo "==================================================\n";
echo "   YASIX STORE - DAY 2 AUTHENTICATION VERIFICATION \n";
echo "==================================================\n";

$userService = new UserService();
$userRepo = new UserRepository();

// 1. Test Registration Validation Failure (Short password / mismatch)
$invalidReg = $userService->register([
    'full_name' => 'T',
    'email' => 'invalid-email',
    'phone' => '123',
    'password' => '123',
    'confirm_password' => '456'
]);

if (!$invalidReg['success'] && !empty($invalidReg['errors'])) {
    echo "[PASS] Registration validation correctly rejected invalid inputs.\n";
} else {
    echo "[FAIL] Registration failed to reject invalid inputs.\n";
    exit(1);
}

// 2. Test Successful User Registration
$testEmail = 'test_user_' . time() . '@yasix.com';
$validReg = $userService->register([
    'full_name' => 'Cyber Tester',
    'email' => $testEmail,
    'phone' => '+94779998877',
    'password' => 'SecurePass123!',
    'confirm_password' => 'SecurePass123!'
]);

if ($validReg['success'] && isset($validReg['user_id'])) {
    echo "[PASS] New customer registration successful (User ID: {$validReg['user_id']}).\n";
} else {
    echo "[FAIL] Registration failed for valid user.\n";
    print_r($validReg);
    exit(1);
}

$newUserId = (int)$validReg['user_id'];

// 3. Test Duplicate Email Rejection
$duplicateReg = $userService->register([
    'full_name' => 'Cyber Clone',
    'email' => $testEmail,
    'phone' => '+94779998877',
    'password' => 'SecurePass123!',
    'confirm_password' => 'SecurePass123!'
]);

if (!$duplicateReg['success'] && isset($duplicateReg['errors']['email'])) {
    echo "[PASS] Duplicate email registration successfully blocked.\n";
} else {
    echo "[FAIL] Duplicate email registration was not blocked.\n";
    exit(1);
}

// 4. Test Login with Wrong Password
$failedLogin = $userService->login($testEmail, 'WrongPassword!');
if (!$failedLogin['success']) {
    echo "[PASS] Login rejected incorrect password.\n";
} else {
    echo "[FAIL] Login accepted wrong password.\n";
    exit(1);
}

// 5. Test Login with Correct Credentials
$successfulLogin = $userService->login($testEmail, 'SecurePass123!');
if ($successfulLogin['success'] && $successfulLogin['user'] instanceof User) {
    echo "[PASS] Login successful with password_verify() (Role: {$successfulLogin['user']->role_name}).\n";
} else {
    echo "[FAIL] Login failed for correct credentials.\n";
    exit(1);
}

// 6. Test Profile Update
$profileUpdate = $userService->updateProfile($newUserId, [
    'full_name' => 'Updated Cyber Tester',
    'phone' => '+94771112233'
]);

if ($profileUpdate['success']) {
    $updatedUser = $userService->getUserById($newUserId);
    if ($updatedUser && $updatedUser->name === 'Updated Cyber Tester' && $updatedUser->phone === '+94771112233') {
        echo "[PASS] User profile updated successfully in database.\n";
    } else {
        echo "[FAIL] User profile values did not match expected updates.\n";
        exit(1);
    }
} else {
    echo "[FAIL] Profile update failed.\n";
    exit(1);
}

// 7. Test Password Change
$pwdChange = $userService->changePassword($newUserId, 'SecurePass123!', 'NewSecurePass456!', 'NewSecurePass456!');
if ($pwdChange['success']) {
    // Verify old password fails
    $oldLoginTest = $userService->login($testEmail, 'SecurePass123!');
    // Verify new password succeeds
    $newLoginTest = $userService->login($testEmail, 'NewSecurePass456!');

    if (!$oldLoginTest['success'] && $newLoginTest['success']) {
        echo "[PASS] Password changed successfully and old password invalidated.\n";
    } else {
        echo "[FAIL] Password update verification failed.\n";
        exit(1);
    }
} else {
    echo "[FAIL] Password change service call failed.\n";
    print_r($pwdChange);
    exit(1);
}

// 8. Test Seed Admin User Authentication
$adminLogin = $userService->login('admin@yasix.com', 'AdminPass123!');
if ($adminLogin['success'] && $adminLogin['user']->isAdmin()) {
    echo "[PASS] Seed Admin user authentication verified (admin@yasix.com).\n";
} else {
    echo "[FAIL] Seed Admin login failed.\n";
    exit(1);
}

// Clean up test user created during verification
$db = \App\Database\Connection::getInstance();
$stmt = $db->prepare("DELETE FROM users WHERE id = :id");
$stmt->execute(['id' => $newUserId]);
echo "[PASS] Cleaned up test user record from database.\n";

echo "==================================================\n";
echo " RESULT: ALL DAY 2 AUTHENTICATION CHECKS PASSED 🚀 \n";
echo "==================================================\n";
