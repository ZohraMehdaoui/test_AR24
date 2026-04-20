<?php

namespace App\Tests\Service;

use App\Service\Api\ApiClient;
use PHPUnit\Framework\TestCase;
use App\Service\AttachmentService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AttachmentServiceTest extends TestCase
{
    private AttachmentService $attachmentService;
    private ApiClient|MockObject $apiClientMock;

    protected function setUp(): void
    {
        $this->apiClientMock = $this->createMock(ApiClient::class);
        $this->attachmentService = new AttachmentService($this->apiClientMock);
    }

    /**
     * Helper method to create a request with file path
     */
    private function createRequestWithFile(string $userId): Request
    {
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        // Create an UploadedFile instance
        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $request = Request::create(
            '/upload',
            'POST',
            ['id_user' => $userId]
        );

        $request->files->set('filePath', $uploadedFile);

        return $request;
    }

    /**
     * Test uploadAttachment with valid request
     */
    public function testUploadAttachmentWithValidRequest(): void
    {
        $request = $this->createRequestWithFile('user_456');

        $expectedResponse = [
            'status' => 'success',
            'attachment_id' => 'attach_123',
            'filename' => 'document.pdf',
        ];

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with(
                'api/attachment/',
                $this->callback(function ($params) {
                    return isset($params['id_user']) && $params['id_user'] === 'user_456' &&
                        isset($params['file']);
                })
            )
            ->willReturn($expectedResponse);

        $result = $this->attachmentService->uploadAttachment($request);

        $this->assertEquals($expectedResponse, $result);
        $this->assertEquals('success', $result['status']);
    }

    /**
     * Test uploadAttachment extracts user ID from request
     */
    public function testUploadAttachmentExtractsUserIdFromRequest(): void
    {
        $userId = 'user_789';
        $request = $this->createRequestWithFile($userId);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with(
                'api/attachment/',
                $this->callback(function ($params) use ($userId) {
                    return $params['id_user'] === $userId;
                })
            )
            ->willReturn(['status' => 'success']);

        $result = $this->attachmentService->uploadAttachment($request);

        $this->assertEquals('success', $result['status']);
    }

    /**
     * Test uploadAttachment passes file resource to API client
     */
    public function testUploadAttachmentPassesFileResourceToApi(): void
    {
        $request = $this->createRequestWithFile('user_123');

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with(
                'api/attachment/',
                $this->callback(function ($params) {
                    return is_resource($params['file']) || (is_object($params['file']) && get_class($params['file']) === 'SplFileObject');
                })
            )
            ->willReturn(['status' => 'success']);

        $this->attachmentService->uploadAttachment($request);
    }

    /**
     * Test uploadAttachment with API client exception
     */
    public function testUploadAttachmentWhenApiClientThrowsException(): void
    {
        $request = $this->createRequestWithFile('user_999');

        $apiException = new \Exception('API connection failed');

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->willThrowException($apiException);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to upload attachment');

        $this->attachmentService->uploadAttachment($request);
    }

    /**
     * Test uploadAttachment exception preserves previous exception
     */
    public function testUploadAttachmentExceptionPreservesPreviousException(): void
    {
        $request = $this->createRequestWithFile('user_001');

        $apiException = new \Exception('File not found');

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->willThrowException($apiException);

        try {
            $this->attachmentService->uploadAttachment($request);
        } catch (\Exception $e) {
            $this->assertSame($apiException, $e->getPrevious());
        }
    }

    /**
     * Test uploadAttachment with null user ID
     */
    public function testUploadAttachmentWithNullUserId(): void
    {
        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $request = Request::create('/upload', 'POST', []);
        $request->files->set('filePath', $uploadedFile);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with(
                'api/attachment/',
                $this->callback(function ($params) {
                    return $params['id_user'] === null;
                })
            )
            ->willReturn(['status' => 'success']);

        $this->attachmentService->uploadAttachment($request);
    }

    /**
     * Test uploadAttachment propagates API response
     */
    public function testUploadAttachmentPropagatesApiResponse(): void
    {
        $request = $this->createRequestWithFile('user_555');

        $apiResponse = [
            'status' => 'uploaded',
            'attachment_id' => 'attach_555',
            'filename' => 'invoice.pdf',
            'size' => 245678,
            'mime_type' => 'application/pdf',
            'url' => 'https://api.example.com/attachments/attach_555',
        ];

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->willReturn($apiResponse);

        $result = $this->attachmentService->uploadAttachment($request);

        $this->assertSame($apiResponse, $result);
        $this->assertCount(6, $result);
        $this->assertEquals('attach_555', $result['attachment_id']);
    }

    /**
     * Test uploadAttachment uses correct API endpoint
     */
    public function testUploadAttachmentUsesCorrectApiEndpoint(): void
    {
        $request = $this->createRequestWithFile('user_endpoint_test');

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with('api/attachment/', $this->isType('array'))
            ->willReturn(['status' => 'success']);

        $this->attachmentService->uploadAttachment($request);
    }

    /**
     * Test uploadAttachment with different user IDs
     */
    public function testUploadAttachmentWithDifferentUserIds(): void
    {
        $userIds = [
            'user_123',
            'user_456',
            'admin_789',
        ];

        $this->apiClientMock->expects($this->exactly(3))
            ->method('postRequest')
            ->willReturn(['status' => 'success']);

        foreach ($userIds as $userId) {
            $request = $this->createRequestWithFile($userId);
            $result = $this->attachmentService->uploadAttachment($request);
            $this->assertEquals('success', $result['status']);
        }
    }

    /**
     * Test uploadAttachment with special characters in user ID
     */
    public function testUploadAttachmentWithSpecialCharactersInUserId(): void
    {
        $userIdWithSpecialChars = 'user_@#$%_123';
        $request = $this->createRequestWithFile($userIdWithSpecialChars);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with(
                'api/attachment/',
                $this->callback(function ($params) use ($userIdWithSpecialChars) {
                    return $params['id_user'] === $userIdWithSpecialChars;
                })
            )
            ->willReturn(['status' => 'success']);

        $this->attachmentService->uploadAttachment($request);
    }

    /**
     * Test uploadAttachment with empty string user ID
     */
    public function testUploadAttachmentWithEmptyStringUserId(): void
    {
        $request = $this->createRequestWithFile('');

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with(
                'api/attachment/',
                $this->callback(function ($params) {
                    return $params['id_user'] === '';
                })
            )
            ->willReturn(['status' => 'success']);

        $this->attachmentService->uploadAttachment($request);
    }

    /**
     * Test uploadAttachment handles API error response
     */
    public function testUploadAttachmentHandlesApiErrorResponse(): void
    {
        $request = $this->createRequestWithFile('user_error');

        $errorResponse = [
            'status' => 'error',
            'error_code' => 'UPLOAD_FAILED',
            'message' => 'File size exceeds maximum allowed size',
        ];

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->willReturn($errorResponse);

        $result = $this->attachmentService->uploadAttachment($request);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('UPLOAD_FAILED', $result['error_code']);
    }

    /**
     * Test uploadAttachment with multiple parameters
     */
    public function testUploadAttachmentWithMultiplePostParameters(): void
    {
        $postData = [
            'id_user' => 'user_multi',
            'description' => 'Test attachment',
            'tags' => 'important,urgent',
        ];

        // Create a temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $request = Request::create('/upload', 'POST', $postData);
        $request->files->set('filePath', $uploadedFile);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with(
                'api/attachment/',
                $this->callback(function ($params) {
                    return $params['id_user'] === 'user_multi';
                })
            )
            ->willReturn(['status' => 'success']);

        $result = $this->attachmentService->uploadAttachment($request);

        $this->assertEquals('success', $result['status']);
    }
}
