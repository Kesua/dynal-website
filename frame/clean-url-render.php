<?php
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);

if (!isset($cleanUrlPage)) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $cleanUrlPath = preg_replace('#/index\.php$#', '.php', $scriptName);
    $cleanUrlPage = $_SERVER['DOCUMENT_ROOT'] . $cleanUrlPath;
}

$cleanUrlPage = realpath($cleanUrlPage);

if ($documentRoot === false || $cleanUrlPage === false || strpos($cleanUrlPage, $documentRoot . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(404);
    exit;
}

$pageDir = dirname($cleanUrlPage);
$basePath = trim(str_replace('\\', '/', substr($pageDir, strlen($documentRoot))), '/');
$baseHref = $basePath === '' ? '/' : '/' . $basePath . '/';
$previousCwd = getcwd();

chdir($pageDir);
ob_start();
include $cleanUrlPage;
$html = ob_get_clean();
chdir($previousCwd);

if (stripos($html, '<base ') === false) {
    $baseTag = '<base href="' . htmlspecialchars($baseHref, ENT_QUOTES, 'UTF-8') . '">';
    $html = preg_replace('/<head([^>]*)>/i', '<head$1>' . "\n" . '   ' . $baseTag, $html, 1);
}

echo $html;
