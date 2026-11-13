<?php

if (! function_exists('cents')) {
    function cents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
