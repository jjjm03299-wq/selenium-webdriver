<?php
require_once('vendor/autoload.php');

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

// Selenium server URL
$serverUrl = 'http://localhost:4444';

// Lock files
$pinFile = __DIR__ . '/pin.lock';
$lockoutFile = __DIR__ . '/pin.lockout';

// Max attempts
$maxAttempts = 3;
$lockoutHours = 8;

// Check lockout
if (file_exists($lockoutFile)) {
    $lockoutTime = file_get_contents($lockoutFile);
    $elapsed = time() - intval($lockoutTime);
    if ($elapsed < $lockoutHours * 3600) {
        echo "Too many failed attempts. Try again in 8 hours.\n";
        exit(1);
    } else {
        unlink($lockoutFile); // reset lockout
    }
}

// Create session
try {
    $driver = RemoteWebDriver::create($serverUrl, DesiredCapabilities::chrome());
} catch (Exception $e) {
    echo "Failed to create Selenium session.\n";
    exit(1);
}

// Prompt for PIN
echo "Enter new PIN: ";
$newPin = trim(fgets(STDIN));
echo "Confirm new PIN: ";
$confirmPin = trim(fgets(STDIN));

if ($newPin !== $confirmPin) {
    echo "PIN mismatch. Exiting.\n";
    $driver->quit();
    exit(1);
}

// Save PIN
file_put_contents($pinFile, $newPin);

// Login loop
$attempts = 0;
while ($attempts < $maxAttempts) {
    echo "Enter PIN to continue: ";
    $loginPin = trim(fgets(STDIN));

    if ($loginPin === $newPin) {
        echo "Access granted. Opening Google...\n";
        $driver->get("https://www.google.com");
        sleep(5);
        $driver->quit();
        exit(0);
    } else {
        $attempts++;
        echo "Incorrect PIN. Attempts left: " . ($maxAttempts - $attempts) . "\n";
    }
}

// Lockout
file_put_contents($lockoutFile, time());
echo "Too many failed attempts. Locked out for 8 hours.\n";
$driver->quit();
exit(1);
