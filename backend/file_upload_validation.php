<?php
// backend/file_upload_validation.php

/**
 * Validate PDF file content (basic check)
 */
function validatePdfContent($filePath) {
    // Check if file exists
    if (!file_exists($filePath)) {
        return false;
    }

    // Check first few bytes for PDF magic number
    $fileHandle = fopen($filePath, 'r');
    $header = fread($fileHandle, 5);
    fclose($fileHandle);

    return strpos($header, '%PDF-') === 0;
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    $filename = preg_replace("/[^A-Za-z0-9_\-\.]/", "_", $filename);
    return time() . "_" . $filename;
}