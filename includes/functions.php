<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Shared HTML Escaping Helper
|--------------------------------------------------------------------------
| Safely escape dynamic text before rendering it in HTML.
*/

function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/*
|--------------------------------------------------------------------------
| Application Redirect Helper
|--------------------------------------------------------------------------
| Redirect to the specified application path and stop execution.
*/

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}
