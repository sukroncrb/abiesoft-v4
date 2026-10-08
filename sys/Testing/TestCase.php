<?php

declare(strict_types=1);

namespace Abiesoft\System\Testing;

use Exception;

abstract class TestCase
{
    private int $assertionCount = 0;

    public function getAssertionCount(): int
    {
        return $this->assertionCount;
    }

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        $this->assertionCount++;
        if (!$condition) {
            throw new Exception($message ?: "Gagal: Harapan bernilai TRUE, tetapi menghasilkan FALSE");
        }
    }

    protected function assertFalse(bool $condition, string $message = ''): void
    {
        $this->assertionCount++;
        if ($condition) {
            throw new Exception($message ?: "Gagal: Harapan bernilai FALSE, tetapi menghasilkan TRUE");
        }
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;
        if ($expected !== $actual) {
            $expectedStr = is_scalar($expected) ? (string)$expected : json_encode($expected);
            $actualStr = is_scalar($actual) ? (string)$actual : json_encode($actual);
            throw new Exception($message ?: "Gagal: Harapan '{$expectedStr}', tetapi menghasilkan '{$actualStr}'");
        }
    }

    protected function assertNotEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;
        if ($expected === $actual) {
            $expectedStr = is_scalar($expected) ? (string)$expected : json_encode($expected);
            throw new Exception($message ?: "Gagal: Nilai tidak boleh sama dengan '{$expectedStr}'");
        }
    }

    protected function assertNull(mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;
        if ($actual !== null) {
            throw new Exception($message ?: "Gagal: Harapan bernilai NULL, tetapi bukan NULL");
        }
    }

    protected function assertNotNull(mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;
        if ($actual === null) {
            throw new Exception($message ?: "Gagal: Nilai tidak boleh bernilai NULL");
        }
    }

    protected function assertCount(int $expectedCount, array|\Countable $haystack, string $message = ''): void
    {
        $this->assertionCount++;
        $actualCount = count($haystack);
        if ($actualCount !== $expectedCount) {
            throw new Exception($message ?: "Gagal: Harapan jumlah {$expectedCount}, tetapi menghasilkan {$actualCount}");
        }
    }

    protected function assertArrayHasKey(string|int $key, array $array, string $message = ''): void
    {
        $this->assertionCount++;
        if (!array_key_exists($key, $array)) {
            throw new Exception($message ?: "Gagal: Array tidak memiliki key '{$key}'");
        }
    }
}
