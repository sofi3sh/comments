<?php
/**
 * Stubs for Backpack Pro helpers so Psalm can analyze code without crashing.
 */

namespace {
    if (!function_exists('backpack_pro')) {
        function backpack_pro(): bool { return true; }
    }
}
