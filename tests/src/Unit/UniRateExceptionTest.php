<?php

declare(strict_types=1);

namespace Drupal\Tests\unirate\Unit;

use Drupal\unirate\UniRateException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Drupal\unirate\UniRateException::class)]
final class UniRateExceptionTest extends TestCase {

  public function testIsRuntimeException(): void {
    $e = new UniRateException('test');
    self::assertInstanceOf(\RuntimeException::class, $e);
  }

  public function testIsThrowable(): void {
    $e = new UniRateException('test');
    self::assertInstanceOf(\Throwable::class, $e);
  }

  public function testMessage(): void {
    $e = new UniRateException('api failure');
    self::assertSame('api failure', $e->getMessage());
  }

  public function testCode(): void {
    $e = new UniRateException('msg', 403);
    self::assertSame(403, $e->getCode());
  }

  public function testPrevious(): void {
    $prev = new \RuntimeException('original');
    $e = new UniRateException('wrapped', 0, $prev);
    self::assertSame($prev, $e->getPrevious());
  }

}
