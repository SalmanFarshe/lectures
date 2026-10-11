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

/*
|--------------------------------------------------------------------------
| CSRF Helpers
|--------------------------------------------------------------------------
| Reusable, per-key CSRF tokens stored in the session. Each feature can use
| its own key (e.g. 'note', 'question', 'quiz') so tokens never collide.
*/

function csrf_token(string $key): string
{
    if (
        !isset($_SESSION['csrf_' . $key]) ||
        !is_string($_SESSION['csrf_' . $key]) ||
        $_SESSION['csrf_' . $key] === ''
    ) {
        $_SESSION['csrf_' . $key] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_' . $key];
}

function csrf_field(string $key): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . e(csrf_token($key)) . '">';
}

function csrf_verify(string $key): bool
{
    $submitted = $_POST['csrf_token'] ?? '';

    if (!is_string($submitted) || $submitted === '') {
        return false;
    }

    return hash_equals(csrf_token($key), $submitted);
}

/*
|--------------------------------------------------------------------------
| Sanitize Pasted HTML (note content)
|--------------------------------------------------------------------------
| Notes may be pasted from ChatGPT as HTML. We must NEVER execute pasted PHP.
| Echoing a string does not run PHP tags, but we strip them defensively so a
| stored note can never be turned into executable code. We intentionally keep
| <style>, <link>, <script> (MathJax) and normal markup so notes render fully.
*/

function sanitize_note_html(string $html): string
{
    // Remove PHP open/close blocks (full tags and short echo tags).
    $html = (string)preg_replace('/<\?(php|=)[\s\S]*?\?>/i', '', $html);
    // Remove any stray PHP open tag without a close.
    $html = (string)preg_replace('/<\?(?!xml)[\s\S]*?\z/i', '', $html);
    // Remove PHP-only short tags that may not be closed.
    $html = (string)preg_replace('/<\?[\s\S]*?\?/i', '', $html);

    return $html;
}

/*
|--------------------------------------------------------------------------
| Validate a PDF upload
|--------------------------------------------------------------------------
| The fileinfo extension is not available in this environment, so we validate
| by: PHP upload error code, is_uploaded_file(), extension, size, and the PDF
| magic bytes (%PDF-). Returns an error string, or null when valid.
*/

function validate_pdf_upload(array $file): ?string
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if ($error !== UPLOAD_ERR_OK) {
        return 'Upload failed (code ' . (int)$error . ').';
    }

    $tmp = (string)($file['tmp_name'] ?? '');

    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return 'Invalid upload.';
    }

    $size = (int)($file['size'] ?? 0);

    if ($size <= 0) {
        return 'The uploaded file is empty.';
    }

    if ($size > 20 * 1024 * 1024) {
        return 'PDF is too large (max 20 MB).';
    }

    $name = strtolower((string)($file['name'] ?? ''));

    if (substr($name, -4) !== '.pdf') {
        return 'Only .pdf files are allowed.';
    }

    // Confirm the first bytes are a real PDF header.
    $handle = fopen($tmp, 'rb');

    if ($handle === false) {
        return 'Could not read the uploaded file.';
    }

    $magic = fread($handle, 5);
    fclose($handle);

    if ($magic !== '%PDF-') {
        return 'The file does not look like a valid PDF.';
    }

    return null;
}
