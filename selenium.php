<?php
require_once('vendor/autoload.php');

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;

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

// Create a unique Chrome profile directory under home
$profileDir = getenv("HOME") . "/chrome-profile-" . uniqid();
if (!is_dir($profileDir)) {
    mkdir($profileDir, 0777, true);
}

// Configure ChromeOptions
$options = new ChromeOptions();
$options->addArguments([
    '--headless=new',
    '--no-sandbox',
    '--disable-dev-shm-usage',
    '--disable-gpu',
    '--remote-debugging-port=9222',
    '--user-data-dir=' . $profileDir
]);

$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

// Create session
try {
    $driver = RemoteWebDriver::create($serverUrl, $capabilities);
} catch (Exception $e) {
    echo "Failed to create Selenium session.\n";
    exit(1);
}

// PIN setup or verification
if (!file_exists($pinFile)) {
    // First run: create PIN
    echo "Enter new PIN: ";
    $newPin = trim(fgets(STDIN));
    echo "Confirm new PIN: ";
    $confirmPin = trim(fgets(STDIN));

    if ($newPin !== $confirmPin) {
        echo "PIN mismatch. Exiting.\n";
        $driver->quit();
        exit(1);
    }

    file_put_contents($pinFile, password_hash($newPin, PASSWORD_DEFAULT));
    echo "PIN created successfully.\n";
}

// Load stored PIN hash
$storedHash = file_get_contents($pinFile);

// Login loop
$attempts = 0;
while ($attempts < $maxAttempts) {
    echo "Enter PIN to continue: ";
    $loginPin = trim(fgets(STDIN));

    if (password_verify($loginPin, $storedHash)) {
        echo "Access granted. Opening Google...\n";
        $driver->get("https://www.google.com");
        sleep(5);

        // Verify page title
        $title = $driver->getTitle();
        if ($title === "Google") {
            echo "Success: Page title is '$title'.\n";
        } else {
            echo "Warning: Unexpected page title '$title'.\n";
        }

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
