<?php

require __DIR__.'/../vendor/autoload.php';

use Tests\Unit\Tracker\PrescriptionScheduleTest;
use Tests\Unit\Tracker\RecoveryGardenStateTest;

echo "=========================================\n";
echo "  MEDIXA MEDICINE TRACKER TEST SUITE\n";
echo "=========================================\n\n";

$testsRun = 0;
$testsPassed = 0;

function runTestClass($testClass, &$testsRun, &$testsPassed) {
    $instance = new $testClass('test');
    $ref = new ReflectionClass($testClass);
    $setUp = $ref->getMethod('setUp');
    $setUp->setAccessible(true);
    $tearDown = $ref->getMethod('tearDown');
    $tearDown->setAccessible(true);

    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $name = $method->getName();
        if (str_starts_with($name, 'test_')) {
            $testsRun++;
            try {
                $setUp->invoke($instance);
                $method->invoke($instance);
                $tearDown->invoke($instance);
                echo " [PASS] {$testClass}::{$name}\n";
                $testsPassed++;
            } catch (\Throwable $e) {
                echo " [FAIL] {$testClass}::{$name} - " . $e->getMessage() . "\n";
            }
        }
    }
}

// 1. Unit Tests
runTestClass(PrescriptionScheduleTest::class, $testsRun, $testsPassed);
runTestClass(RecoveryGardenStateTest::class, $testsRun, $testsPassed);

// 2. PWA Asset & Configuration Tests
echo "\n--- PWA MANIFEST & SERVICE WORKER CHECKS ---\n";

// Test Manifest
$testsRun++;
$manifestPath = __DIR__.'/../public/manifest-tracker.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if ($manifest && $manifest['name'] === 'Medixa Medicine Tracker' && $manifest['short_name'] === 'Medixa Tracker' && $manifest['display'] === 'standalone' && $manifest['theme_color'] === '#168071') {
        echo " [PASS] PWA Manifest: Valid metadata and standalone display mode\n";
        $testsPassed++;
    } else {
        echo " [FAIL] PWA Manifest has incorrect values\n";
    }
} else {
    echo " [FAIL] PWA Manifest file not found\n";
}

// Test Icons
$testsRun++;
$icon192 = __DIR__.'/../public/tracker-assets/icons/icon-192.svg';
$icon512 = __DIR__.'/../public/tracker-assets/icons/icon-512.svg';
$maskable = __DIR__.'/../public/tracker-assets/icons/maskable-512.svg';
if (file_exists($icon192) && file_exists($icon512) && file_exists($maskable)) {
    echo " [PASS] PWA Icons: 192px, 512px, and maskable icons exist\n";
    $testsPassed++;
} else {
    echo " [FAIL] PWA Icons missing\n";
}

// Test Service Worker
$testsRun++;
$swPath = __DIR__.'/../public/sw-tracker.js';
if (file_exists($swPath)) {
    $sw = file_get_contents($swPath);
    if (str_contains($sw, 'medixa-tracker-v1') && str_contains($sw, '/tracker/offline') && str_contains($sw, 'STATIC_ASSETS')) {
        echo " [PASS] Service Worker: Offline fallback and cache strategy configured\n";
        $testsPassed++;
    } else {
        echo " [FAIL] Service Worker missing required offline config\n";
    }
} else {
    echo " [FAIL] Service Worker file not found\n";
}

echo "\n=========================================\n";
echo "  TEST SUMMARY: {$testsPassed}/{$testsRun} Passed\n";
echo "=========================================\n";

if ($testsPassed === $testsRun && $testsRun > 0) {
    echo "SUCCESS: ALL TESTS PASSED!\n";
    exit(0);
} else {
    echo "ERROR: SOME TESTS FAILED.\n";
    exit(1);
}
