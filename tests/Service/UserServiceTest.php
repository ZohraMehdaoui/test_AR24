<?php

namespace App\Tests\Service;

use App\DTO\UserDTO;
use App\Service\Api\ApiClient;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private ApiClient|MockObject $apiClientMock;

    protected function setUp(): void
    {
        $this->apiClientMock = $this->createMock(ApiClient::class);
        $this->userService = new UserService($this->apiClientMock);
    }

    /**
     * Test createUser with valid UserDTO
     */
    public function testCreateUserWithValidData(): void
    {
        $userDTO = $this->createMock(UserDTO::class);
        $expectedUserArray = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];
        $expectedResponse = [
            'status' => 'success',
            'user_id' => 123,
        ];

        $userDTO->expects($this->once())
            ->method('toArray')
            ->willReturn($expectedUserArray);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with('api/user/', $expectedUserArray)
            ->willReturn($expectedResponse);

        $result = $this->userService->createUser($userDTO);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getUserInfo with numeric user ID
     */
    public function testGetUserInfoWithNumericId(): void
    {
        $userId = '123';
        $expectedResponse = [
            'id_user' => 123,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->with(
                'api/user/',
                [
                    'email' => null,
                    'id_user' => '123',
                ]
            )
            ->willReturn($expectedResponse);

        $result = $this->userService->getUserInfo($userId);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getUserInfo with valid email
     */
    public function testGetUserInfoWithValidEmail(): void
    {
        $userEmail = 'john@example.com';
        $expectedResponse = [
            'id_user' => 123,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->with(
                'api/user/',
                [
                    'email' => 'john@example.com',
                    'id_user' => null,
                ]
            )
            ->willReturn($expectedResponse);

        $result = $this->userService->getUserInfo($userEmail);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getUserInfo with email containing quotes
     */
    public function testGetUserInfoTrimsEmailQuotes(): void
    {
        $userEmail = '"john@example.com"';
        $expectedResponse = [
            'id_user' => 123,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->with(
                'api/user/',
                [
                    'email' => 'john@example.com',
                    'id_user' => null,
                ]
            )
            ->willReturn($expectedResponse);

        $result = $this->userService->getUserInfo($userEmail);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getUserInfo with empty string throws InvalidArgumentException
     */
    public function testGetUserInfoWithEmptyStringThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User identifier is required');

        $this->userService->getUserInfo('');
    }

    /**
     * Test getUserInfo with whitespace only throws InvalidArgumentException
     */
    public function testGetUserInfoWithWhitespaceOnlyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User identifier is required');

        $this->userService->getUserInfo('   ');
    }

    /**
     * Test getUserInfo with invalid identifier throws InvalidArgumentException
     */
    public function testGetUserInfoWithInvalidIdentifierThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User identifier must be a valid email or numeric ID');

        // Must be neither email nor numeric
        $this->userService->getUserInfo('invalid-user@');
    }

    /**
     * Test getUserInfo with invalid identifier (special characters)
     */
    public function testGetUserInfoWithSpecialCharactersThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User identifier must be a valid email or numeric ID');

        $this->userService->getUserInfo('user@example!invalid');
    }

    /**
     * Test getUserInfo when API client throws exception
     */
    public function testGetUserInfoWhenApiClientThrowsException(): void
    {
        $userId = '123';
        $apiException = new \Exception('API connection failed');

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->willThrowException($apiException);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve user information');

        $this->userService->getUserInfo($userId);
    }

    /**
     * Test getUserInfo exception preserves previous exception
     */
    public function testGetUserInfoExceptionHasPreviousException(): void
    {
        $userId = '123';
        $apiException = new \Exception('API connection failed');

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->willThrowException($apiException);

        try {
            $this->userService->getUserInfo($userId);
        } catch (\Exception $e) {
            $this->assertSame($apiException, $e->getPrevious());
        }
    }

    /**
     * Test isEmail with valid email
     */
    public function testIsEmailWithValidEmail(): void
    {
        $this->assertTrue($this->userService->isEmail('test@example.com'));
        $this->assertTrue($this->userService->isEmail('user.name@example.co.uk'));
        $this->assertTrue($this->userService->isEmail('test+tag@example.com'));
    }

    /**
     * Test isEmail with invalid email
     */
    public function testIsEmailWithInvalidEmail(): void
    {
        $this->assertFalse($this->userService->isEmail('invalid-email'));
        $this->assertFalse($this->userService->isEmail('test@'));
        $this->assertFalse($this->userService->isEmail('@example.com'));
        $this->assertFalse($this->userService->isEmail('test @example.com'));
    }

    /**
     * Test isEmail with empty string
     */
    public function testIsEmailWithEmptyString(): void
    {
        $this->assertFalse($this->userService->isEmail(''));
    }

    /**
     * Test createUser propagates API client response
     */
    public function testCreateUserPropagatesApiResponse(): void
    {
        $userDTO = $this->createMock(UserDTO::class);
        $userData = ['name' => 'Jane Doe'];
        $apiResponse = ['id' => 456];

        $userDTO->expects($this->once())
            ->method('toArray')
            ->willReturn($userData);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->willReturn($apiResponse);

        $result = $this->userService->createUser($userDTO);

        $this->assertSame($apiResponse, $result);
    }

    /**
     * Test getUserInfo with zero as user ID
     */
    public function testGetUserInfoWithZeroAsUserId(): void
    {
        $userId = '0';
        $expectedResponse = ['id_user' => 0];

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->with(
                'api/user/',
                [
                    'email' => null,
                    'id_user' => '0',
                ]
            )
            ->willReturn($expectedResponse);

        $result = $this->userService->getUserInfo($userId);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getUserInfo with mixed whitespace and quotes
     */
    public function testGetUserInfoTrimsComplexWhitespace(): void
    {
        $userEmail = "  'john@example.com'  ";
        $expectedResponse = ['id_user' => 123];

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->with(
                'api/user/',
                [
                    'email' => 'john@example.com',
                    'id_user' => null,
                ]
            )
            ->willReturn($expectedResponse);

        $result = $this->userService->getUserInfo($userEmail);

        $this->assertEquals($expectedResponse, $result);
    }
}
