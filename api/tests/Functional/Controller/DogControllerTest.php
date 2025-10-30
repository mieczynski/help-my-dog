<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DogControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testCreateDogSuccess(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        $client->request('POST', '/api/dogs', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Rex',
            'breed' => 'German Shepherd',
            'ageMonths' => 24,
            'gender' => 'male',
            'weightKg' => 35.5,
            'energyLevel' => 'high',
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertSame('Rex', $responseData['name']);
        $this->assertSame('German Shepherd', $responseData['breed']);
        $this->assertSame(24, $responseData['ageMonths']);
        $this->assertSame('male', $responseData['gender']);
        $this->assertSame(35.5, $responseData['weightKg']);
        $this->assertSame('high', $responseData['energyLevel']);
        $this->assertArrayHasKey('createdAt', $responseData);
        $this->assertArrayHasKey('updatedAt', $responseData);
        $this->assertArrayNotHasKey('deletedAt', $responseData);
    }

    public function testCreateDogValidationError(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        $client->request('POST', '/api/dogs', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => '',
            'breed' => 'German Shepherd',
            'ageMonths' => 500,
            'gender' => 'invalid',
            'weightKg' => 250.0,
            'energyLevel' => 'invalid',
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('validation_failed', $responseData['error']);
        $this->assertSame('Invalid input data', $responseData['message']);
        $this->assertArrayHasKey('violations', $responseData);
        $this->assertIsArray($responseData['violations']);
        $this->assertNotEmpty($responseData['violations']);
    }

    public function testCreateDogUnauthorized(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/dogs', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Rex',
            'breed' => 'German Shepherd',
            'ageMonths' => 24,
            'gender' => 'male',
            'weightKg' => 35.5,
            'energyLevel' => 'high',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateDogInvalidToken(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/dogs', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid_token_here',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Rex',
            'breed' => 'German Shepherd',
            'ageMonths' => 24,
            'gender' => 'male',
            'weightKg' => 35.5,
            'energyLevel' => 'high',
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * Helper method to get authentication token for testing.
     */
    private function getAuthToken($client): string
    {
        // Create a test user
        $container = static::getContainer();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('test@example.com');
        $hashedPassword = $passwordHasher->hashPassword($user, 'password123');
        $user->setPasswordHash($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Generate JWT token using LexikJWTAuthenticationBundle
        $jwtManager = $container->get('lexik_jwt_authentication.jwt_manager');

        return $jwtManager->create($user);
    }
}
