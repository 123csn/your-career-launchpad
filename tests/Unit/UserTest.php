<?php

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../../models/User.php';

class UserTest extends TestCase
{
    private $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testUserLogin()
    {
        // Test user login with valid credentials
        $email = 'student@test.com';
        $password = 'student123';
        
        $result = $this->user->login($email, $password);
        $this->assertTrue($result);
    }

    public function testInvalidLogin()
    {
        // Test user login with invalid credentials
        $email = 'invalid@test.com';
        $password = 'wrongpassword';
        
        $result = $this->user->login($email, $password);
        $this->assertFalse($result);
    }

    public function testUpdateProfile()
    {
        // Test updating user profile with valid data
        $userData = [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'phone' => '1234567890'
        ];
        
        $result = $this->user->updateProfile($userData);
        $this->assertTrue($result);
        
        // Verify the updated data
        $userInfo = $this->user->getUserInfo();
        $this->assertEquals($userData['name'], $userInfo['name']);
        $this->assertEquals($userData['email'], $userInfo['email']);
        $this->assertEquals($userData['phone'], $userInfo['phone']);
    }

    public function testInvalidProfileUpdate()
    {
        // Test updating profile with invalid email
        $userData = [
            'name' => 'Test Name',
            'email' => 'invalid-email',
            'phone' => '1234567890'
        ];
        
        $result = $this->user->updateProfile($userData);
        $this->assertFalse($result);
    }

    public function testChangePassword()
    {
        // Test password change functionality
        $oldPassword = 'currentPassword123';
        $newPassword = 'newPassword123';
        
        $result = $this->user->changePassword($oldPassword, $newPassword);
        $this->assertTrue($result);
        
        // Verify login works with new password
        $loginResult = $this->user->login($this->user->getEmail(), $newPassword);
        $this->assertTrue($loginResult);
    }

    public function testInvalidPasswordChange()
    {
        // Test password change with incorrect old password
        $oldPassword = 'wrongPassword';
        $newPassword = 'newPassword123';
        
        $result = $this->user->changePassword($oldPassword, $newPassword);
        $this->assertFalse($result);
    }
} 