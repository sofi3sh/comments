<?php

namespace App\Http\Controllers\Traits;

trait ValidatesPhoneNumber
{
    /**
     * Validate the phone number
     *
     * @param string $phone
     * @return bool
     */
    protected function isValidPhoneNumber(string $phone): bool
    {
        $phone = preg_replace('/[\s\-]/', '', $phone);

        return preg_match('/^(0\d{9}|380\d{9}|\+380\d{9})$/', $phone) === 1;
    }

    /**
     * Normalize the phone number
     *
     * @param string $phone
     * @return string
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[\s\-]/', '', $phone);

        if (str_starts_with($phone, '+380')) {
            return $phone;
        }

        if (str_starts_with($phone, '380')) {
            return '+' . $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+380' . substr($phone, 1);
        }

        return $phone;
    }
}

