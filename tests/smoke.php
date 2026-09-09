<?php
$root = dirname(__DIR__);

$required = [
    'component/decarodocuments.xml',
    'component/admin/src/Service/CoreIntegrationService.php',
    'component/admin/src/Service/StorageService.php',
    'component/admin/src/Controller/DocumentController.php',
    'package/pkg_decarodocuments.xml',
    'package/script.php',
];

foreach ($required as $path) {
    if (!is_file($root . '/' . $path)) {
        fwrite(STDERR, "Missing {$path}\n");
        exit(1);
    }
}

$storage = file_get_contents($root . '/component/admin/src/Service/StorageService.php');
if (strpos($storage, 'dirname(JPATH_ROOT)') === false || strpos($storage, 'is_uploaded_file') === false) {
    fwrite(STDERR, "Storage security baseline missing\n");
    exit(1);
}

$core = file_get_contents($root . '/component/admin/src/Helper/CoreUiHelper.php');
if (strpos($core, 'xdecaro\\Core\\Asset\\AssetService') === false || strpos($core, "'1.3.0'") === false) {
    fwrite(STDERR, "Core UI baseline missing\n");
    exit(1);
}
if (strpos($core, 'Xdecaro\\Core\\') !== false) {
    fwrite(STDERR, "Legacy Core namespace remains\n");
    exit(1);
}

$installer = file_get_contents($root . '/package/script.php');
if (strpos($installer, 'final class pkg_decarodocumentsInstallerScript') === false) {
    fwrite(STDERR, "Package installer class does not match Joomla package element resolution\n");
    exit(1);
}
if (strpos($installer, "private const MINIMUM_CORE = '1.3.0';") === false || strpos($installer, 'return false;') === false) {
    fwrite(STDERR, "Mandatory Core preflight baseline missing\n");
    exit(1);
}

echo "Documents smoke OK\n";
