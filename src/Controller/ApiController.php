<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use App\Entity\User;
use App\Entity\PhoneVerification;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provide endpoint to process sms verification.
 */
#[Route('/api/auth')]
class ApiController extends AbstractController
{

  private $redis;

  /**
   * Init EntityManagerInterface and Redis.
   *
   * @param EntityManagerInterface $em
   */
  public function __construct(
    private  EntityManagerInterface $em
  ) {
    $this->redis = RedisAdapter::createConnection($_ENV['REDIS_URL'] ?? 'redis://redis:6379');
  }

  /**
   * Logic for sms code sending process.
   *
   * @param Request $request
   * @param ValidatorInterface $validator
   * @return JsonResponse
   */
  #[Route('/send-code', name: 'send_code', methods: ['POST'])]
  public function sendVerificationCode(Request $request, ValidatorInterface $validator): JsonResponse
  {
    $data = json_decode($request->getContent(), true);

    $constraints = new Assert\Collection([
      'username' => [new Assert\NotBlank(), new Assert\Length(['min' => 3])],
      'phone_number' => [
        new Assert\NotBlank(),
        new Assert\Regex(['pattern' => '/^\+?[0-9]{10,15}$/'])
      ]
    ]);

    $errors = $validator->validate($data, $constraints);
    if (count($errors) > 0) {
      return $this->json(['error' => (string)$errors], 400);
    }

    // Define cache keys.
    $phone = $data['phone_number'];
    $cacheKey = "verify_code:$phone";
    $attemptsKey = "attempts:$phone";
    $lastSentKey = "last_sent:$phone";
    $blockKey = "block:$phone";

    // Check blocked user.
    $blockedUntil = $this->redis->get($blockKey);
    if ($blockedUntil && $blockedUntil > time()) {
      return $this->json([
        'error' => 'Too many attempts. Blocked until ' . date('Y-m-d H:i:s', $blockedUntil),
        'retry_after' => $blockedUntil - time(),
        'blocked_until' => $blockedUntil,
      ], 429);
    }

    // Check last sent time, resend existing code .
    $lastSent = $this->redis->get($lastSentKey);
    $currentTime = time();
    if ($lastSent && ($currentTime - $lastSent) < 60) {
      $existingCode = $this->redis->get($cacheKey);

      return $this->json([
        'message' => 'Verification code already sent',
        'code' => $existingCode,
        'retry_after' => 60 - ($currentTime - $lastSent)
      ]);
    }

    // Check for attempts, send error message.
    $attempts = $this->redis->get("attempts:$phone") ?: 0;
    if ($attempts >= 3) {
      $blockDuration = 3600;
      $this->redis->setex($blockKey, $blockDuration, time() + $blockDuration);

      return $this->json([
        'error' => 'Too many attempts. Number blocked for 1 hour.',
        'blocked_until' => time() + $blockDuration
      ], 429);
    }

    // Prepare and send. 4-digit code
    $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

    $this->redis->setex($cacheKey, 900, $code);
    $this->redis->setex($lastSentKey, 60, $currentTime);
    $this->redis->incr($attemptsKey);
    $this->redis->expire($attemptsKey, 900); // Counter attempts for 15 mins.

    $this->sendSms($phone, "Your verification code: $code");

    return $this->json([
      'message' => "Verification code sent: $code",
      'attempts_left' => 3 - ($attempts + 1),
      'expire_in' => 900
    ]);
  }

  /**
   * Endpoint to verify sms.
   *
   * @param Request $request
   * @return JsonResponse
   */
  #[Route('/verify-code', name: 'verify_code', methods: ['POST'])]
  public function verifyCode(Request $request): JsonResponse
  {
    // Prepare data.
    $data = json_decode($request->getContent(), true);
    $phone = $data['phone_number'] ?? '';
    $code = $data['code'] ?? '';

    $cacheKey = "verify_code:$phone";
    $storedCode = $this->redis->get($cacheKey);

    if (!$storedCode || $storedCode !== $code) {
      return $this->json([
        'error' => 'Invalid verification code'
      ], 400);
    }

    $user = $this->em->getRepository(User::class)
      ->findOneBy(['phone' => $phone]);

    $isNewUser = false;

    // Create user if not exist. (Registratino process).
    if (!$user) {
      $user = new User();
      $user->setPhone($phone);
      $user->setUsername('user_' . substr($phone, -4));

      $this->em->persist($user);
      $isNewUser = true;
    }

    // Save verification information.
    $verification = new PhoneVerification();
    $verification->setUser($user)
      ->setPhone($phone)
      ->setVerifiedAt(new \DateTimeImmutable());

    $this->em->persist($verification);
    $this->em->flush();

    // Clear cache.
    $this->redis->del($cacheKey);
    $this->redis->del("attempts:$phone");

    return $this->json([
      'message' => $isNewUser
        ? 'Registration successful'
        : 'Authorization successful',
      'user_id' => $user->getId(),
      'is_new_user' => $isNewUser
    ]);
  }

  // Send sms logic.
  private function sendSms(string $phone, string $message): bool
  {
    // Send sms provider call.
    return true;
  }
}
