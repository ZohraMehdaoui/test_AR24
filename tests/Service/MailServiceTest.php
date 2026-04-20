<?php

namespace App\Tests\Service;

use App\DTO\MailDTO;
use App\Service\MailService;
use InvalidArgumentException;
use App\Service\Api\ApiClient;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class MailServiceTest extends TestCase
{
    private MailService $mailService;
    private ApiClient|MockObject $apiClientMock;

    protected function setUp(): void
    {
        $this->apiClientMock = $this->createMock(ApiClient::class);
        $this->mailService = new MailService($this->apiClientMock);
    }

    /**
     * Test sendMail with valid MailDTO
     */
    public function testSendMailWithValidData(): void
    {
        $mailDTO = $this->createMock(MailDTO::class);
        $mailData = [
            'id_user' => '123',
            'to_firstname' => 'John',
            'to_lastname' => 'Doe',
            'to_email' => 'john@example.com',
            'dest_statut' => 'active',
            'content' => 'This is a test email',
            'to_company' => 'Test Corp',
            'attachment' => null,
        ];
        $expectedResponse = [
            'status' => 'success',
            'mail_id' => 'mail_123',
        ];

        $mailDTO->expects($this->once())
            ->method('toArray')
            ->willReturn($mailData);

        $expectedRequestData = array_merge($mailData, ['eidas' => 0]);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with('api/mail', $expectedRequestData)
            ->willReturn($expectedResponse);

        $result = $this->mailService->sendMail($mailDTO);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test sendMail adds eidas flag
     */
    public function testSendMailAddsEidasFlag(): void
    {
        $mailDTO = $this->createMock(MailDTO::class);
        $mailData = [
            'id_user' => '123',
            'to_firstname' => 'Jane',
            'to_lastname' => 'Smith',
            'to_email' => 'jane@example.com',
            'dest_statut' => 'pending',
            'content' => 'Test content',
            'to_company' => null,
            'attachment' => null,
        ];

        $mailDTO->expects($this->once())
            ->method('toArray')
            ->willReturn($mailData);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with(
                'api/mail',
                $this->callback(function ($data) {
                    return isset($data['eidas']) && $data['eidas'] === 0;
                })
            )
            ->willReturn(['status' => 'sent']);

        $this->mailService->sendMail($mailDTO);
    }

    /**
     * Test sendMail with attachments
     */
    public function testSendMailWithAttachments(): void
    {
        $mailDTO = $this->createMock(MailDTO::class);
        $mailData = [
            'id_user' => '456',
            'to_firstname' => 'Bob',
            'to_lastname' => 'Johnson',
            'to_email' => 'bob@example.com',
            'dest_statut' => 'inactive',
            'content' => 'Email with attachments',
            'to_company' => 'Another Corp',
            'attachment' => ['file1.pdf', 'file2.docx'],
        ];

        $mailDTO->expects($this->once())
            ->method('toArray')
            ->willReturn($mailData);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with('api/mail', $this->isType('array'))
            ->willReturn(['status' => 'success', 'mail_id' => 'mail_456']);

        $result = $this->mailService->sendMail($mailDTO);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
    }

    /**
     * Test getMailInfo with valid mail ID
     */
    public function testGetMailInfoWithValidId(): void
    {
        $mailId = 'mail_123';
        $expectedResponse = [
            'id' => 'mail_123',
            'to_email' => 'john@example.com',
            'status' => 'delivered',
            'sent_at' => '2026-04-20 10:30:00',
        ];

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->with('api/mail', ['id' => $mailId])
            ->willReturn($expectedResponse);

        $result = $this->mailService->getMailInfo($mailId);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getMailInfo with numeric mail ID
     */
    public function testGetMailInfoWithNumericId(): void
    {
        $mailId = '999';
        $expectedResponse = [
            'id' => '999',
            'status' => 'pending',
        ];

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->with('api/mail', ['id' => '999'])
            ->willReturn($expectedResponse);

        $result = $this->mailService->getMailInfo($mailId);

        $this->assertEquals($expectedResponse, $result);
    }

    /**
     * Test getMailInfo with empty mail ID throws InvalidArgumentException
     */
    public function testGetMailInfoWithEmptyIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mail ID is required');

        $this->mailService->getMailInfo('');
    }

    /**
     * Test getMailInfo with whitespace only throws InvalidArgumentException
     */
    public function testGetMailInfoWithWhitespaceOnlyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mail ID is required');

        $this->mailService->getMailInfo('   ');
    }

    /**
     * Test getMailInfo when API client throws exception
     */
    public function testGetMailInfoWhenApiClientThrowsException(): void
    {
        $mailId = 'mail_invalid';
        $apiException = new \Exception('API error: Mail not found');

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->willThrowException($apiException);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API error: Mail not found');

        $this->mailService->getMailInfo($mailId);
    }

    /**
     * Test sendMail propagates API client response
     */
    public function testSendMailPropagatesApiResponse(): void
    {
        $mailDTO = $this->createMock(MailDTO::class);
        $mailData = [
            'id_user' => '789',
            'to_firstname' => 'Alice',
            'to_lastname' => 'Wonder',
            'to_email' => 'alice@example.com',
            'dest_statut' => 'active',
            'content' => 'Wonderland mail',
            'to_company' => null,
            'attachment' => null,
        ];
        $apiResponse = [
            'status' => 'queued',
            'mail_id' => 'mail_789',
            'timestamp' => '2026-04-20T10:00:00Z',
        ];

        $mailDTO->expects($this->once())
            ->method('toArray')
            ->willReturn($mailData);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->willReturn($apiResponse);

        $result = $this->mailService->sendMail($mailDTO);

        $this->assertSame($apiResponse, $result);
        $this->assertEquals('queued', $result['status']);
    }

    /**
     * Test sendMail when API client throws exception
     */
    public function testSendMailWhenApiClientThrowsException(): void
    {
        $mailDTO = $this->createMock(MailDTO::class);
        $mailData = [
            'id_user' => '999',
            'to_firstname' => 'Error',
            'to_lastname' => 'Test',
            'to_email' => 'error@example.com',
            'dest_statut' => 'active',
            'content' => 'This email will fail',
            'to_company' => null,
            'attachment' => null,
        ];
        $apiException = new \Exception('SMTP connection failed');

        $mailDTO->expects($this->once())
            ->method('toArray')
            ->willReturn($mailData);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->willThrowException($apiException);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('SMTP connection failed');

        $this->mailService->sendMail($mailDTO);
    }

    /**
     * Test sendMail with empty MailDTO data
     */
    public function testSendMailWithEmptyDto(): void
    {
        $mailDTO = $this->createMock(MailDTO::class);
        $mailData = [
            'id_user' => '',
            'to_firstname' => '',
            'to_lastname' => '',
            'to_email' => '',
            'dest_statut' => '',
            'content' => '',
            'to_company' => null,
            'attachment' => null,
        ];

        $mailDTO->expects($this->once())
            ->method('toArray')
            ->willReturn($mailData);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->with('api/mail', $this->isType('array'))
            ->willReturn(['status' => 'error', 'message' => 'Invalid mail data']);

        $result = $this->mailService->sendMail($mailDTO);

        $this->assertEquals('error', $result['status']);
    }

    /**
     * Test getMailInfo returns complete response
     */
    public function testGetMailInfoReturnsCompleteResponse(): void
    {
        $mailId = 'complex_mail_id_123';
        $expectedResponse = [
            'id' => 'complex_mail_id_123',
            'id_user' => '100',
            'to_firstname' => 'John',
            'to_lastname' => 'Doe',
            'to_email' => 'john@example.com',
            'status' => 'delivered',
            'sent_at' => '2026-04-20 14:30:00',
            'delivered_at' => '2026-04-20 14:31:15',
            'content' => 'Original email content',
            'attachments' => ['file.pdf'],
        ];

        $this->apiClientMock->expects($this->once())
            ->method('getRequest')
            ->with('api/mail', ['id' => $mailId])
            ->willReturn($expectedResponse);

        $result = $this->mailService->getMailInfo($mailId);

        $this->assertCount(10, $result);
        $this->assertEquals('john@example.com', $result['to_email']);
        $this->assertEquals('delivered', $result['status']);
    }

    /**
     * Test sendMail with special characters in content
     */
    public function testSendMailWithSpecialCharactersInContent(): void
    {
        $mailDTO = $this->createMock(MailDTO::class);
        $mailData = [
            'id_user' => '555',
            'to_firstname' => 'José',
            'to_lastname' => 'García',
            'to_email' => 'jose@example.com',
            'dest_statut' => 'active',
            'content' => 'Content with special chars: @#$%&*()[]{}',
            'to_company' => 'Société Générale',
            'attachment' => null,
        ];

        $mailDTO->expects($this->once())
            ->method('toArray')
            ->willReturn($mailData);

        $this->apiClientMock->expects($this->once())
            ->method('postRequest')
            ->willReturn(['status' => 'success', 'mail_id' => 'mail_555']);

        $result = $this->mailService->sendMail($mailDTO);

        $this->assertEquals('success', $result['status']);
    }
}
