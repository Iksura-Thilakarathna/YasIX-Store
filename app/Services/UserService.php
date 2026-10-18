<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Models\User;
use App\Validators\Validator;

class UserService extends BaseService
{
    private UserRepository $userRepository;
    private Validator $validator;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->validator = new Validator();
    }

    public function register(array $data): array
    {
        $rules = [
            'full_name' => 'required|min:2|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'required|phone',
            'password' => 'required|min:8',
            'confirm_password' => 'required|match:password'
        ];

        $labels = [
            'full_name' => 'Full Name',
            'confirm_password' => 'Confirm Password'
        ];

        if (!$this->validator->validate($data, $rules, $labels)) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors()
            ];
        }

        $email = strtolower(trim($data['email']));
        if ($this->userRepository->emailExists($email)) {
            return [
                'success' => false,
                'errors' => ['email' => ['This email address is already registered. Please login or use a different email.']]
            ];
        }

        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $userId = $this->userRepository->createUser([
            'role_id' => 2, // CUSTOMER
            'name' => trim($data['full_name']),
            'email' => $email,
            'password_hash' => $passwordHash,
            'phone' => trim($data['phone']),
            'status' => 'ACTIVE'
        ]);

        return [
            'success' => true,
            'user_id' => $userId
        ];
    }

    public function login(string $email, string $password): array
    {
        if (empty(trim($email)) || empty($password)) {
            return [
                'success' => false,
                'error' => 'Invalid email or password.'
            ];
        }

        $userData = $this->userRepository->findByEmail($email);
        if (!$userData) {
            return [
                'success' => false,
                'error' => 'Invalid email or password.'
            ];
        }

        $user = new User($userData);

        if (!$user->verifyPassword($password)) {
            return [
                'success' => false,
                'error' => 'Invalid email or password.'
            ];
        }

        if (!$user->isActive()) {
            return [
                'success' => false,
                'error' => 'Your account is currently inactive or suspended. Please contact support.'
            ];
        }

        return [
            'success' => true,
            'user' => $user
        ];
    }

    public function getUserById(int $userId): ?User
    {
        $userData = $this->userRepository->findById($userId);
        return $userData ? new User($userData) : null;
    }

    public function updateProfile(int $userId, array $data): array
    {
        $rules = [
            'full_name' => 'required|min:2|max:100',
            'phone' => 'required|phone'
        ];

        $labels = ['full_name' => 'Full Name'];

        if (!$this->validator->validate($data, $rules, $labels)) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors()
            ];
        }

        $success = $this->userRepository->updateProfile($userId, $data['full_name'], $data['phone']);

        return [
            'success' => $success,
            'errors' => $success ? [] : ['profile' => ['Failed to update profile details.']]
        ];
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|match:new_password'
        ];

        $labels = [
            'current_password' => 'Current Password',
            'new_password' => 'New Password',
            'confirm_password' => 'Confirm New Password'
        ];

        if (!$this->validator->validate([
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
            'confirm_password' => $confirmPassword
        ], $rules, $labels)) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors()
            ];
        }

        $user = $this->getUserById($userId);
        if (!$user || !$user->verifyPassword($currentPassword)) {
            return [
                'success' => false,
                'errors' => ['current_password' => ['Your current password is incorrect.']]
            ];
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $success = $this->userRepository->updatePassword($userId, $newHash);

        return [
            'success' => $success,
            'errors' => $success ? [] : ['password' => ['Failed to update password.']]
        ];
    }

    public function getAdminCustomers(array $params): array
    {
        $page = isset($params['page']) && is_numeric($params['page']) ? max(1, (int)$params['page']) : 1;
        $perPage = 15;

        $filters = [
            'search' => trim($params['search'] ?? ''),
            'status' => trim($params['status'] ?? ''),
            'role' => trim($params['role'] ?? 'CUSTOMER')
        ];

        $total = $this->userRepository->getAdminCustomersCount($filters);
        $totalPages = (int)ceil($total / $perPage);
        if ($totalPages > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        $customers = $this->userRepository->getAdminCustomers($filters, $page, $perPage);

        return [
            'customers' => $customers,
            'filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_customers' => $total,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    }

    public function getCustomerDetails(int $userId): ?array
    {
        return $this->userRepository->getCustomerDetails($userId);
    }

    public function setCustomerStatus(int $targetUserId, string $newStatus, int $adminUserId): array
    {
        if ($targetUserId === $adminUserId) {
            return [
                'success' => false,
                'error' => 'Security Violation: Administrators are strictly prohibited from suspending their own administrative credentials.'
            ];
        }

        $targetUser = $this->userRepository->findById($targetUserId);
        if (!$targetUser) {
            return [
                'success' => false,
                'error' => 'User account not found.'
            ];
        }

        if (strtoupper($targetUser['role_name'] ?? '') === 'ADMIN') {
            return [
                'success' => false,
                'error' => 'Security Policy: Administrator accounts cannot be modified or suspended through the customer management terminal.'
            ];
        }

        $validStatuses = ['ACTIVE', 'SUSPENDED', 'INACTIVE'];
        $cleanStatus = in_array(strtoupper($newStatus), $validStatuses, true) ? strtoupper($newStatus) : 'ACTIVE';

        $this->userRepository->updateCustomerStatus($targetUserId, $cleanStatus);

        return [
            'success' => true,
            'status' => $cleanStatus
        ];
    }
}
