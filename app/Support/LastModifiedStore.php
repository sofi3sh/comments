<?php

namespace App\Support;

use DateTimeInterface;

class LastModifiedStore
{
    private ?int $timestamp = null;

    public function set(mixed $date): void
    {
        $timestamp = $this->toTimestamp($date);

        if ($timestamp !== null && ($this->timestamp === null || $timestamp > $this->timestamp)) {
            $this->timestamp = $timestamp;
        }
    }

    public function get(): ?int
    {
        return $this->timestamp;
    }

    private function toTimestamp(mixed $date): ?int
    {
        if ($date instanceof DateTimeInterface) {
            return $date->getTimestamp();
        }

        if (is_int($date)) {
            return $date;
        }

        if (! is_string($date) || $date === '') {
            return null;
        }

        $timestamp = strtotime($date);

        return $timestamp === false ? null : $timestamp;
    }
}
