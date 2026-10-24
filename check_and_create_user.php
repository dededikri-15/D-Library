<?php
/**
 * Standalone script to check and create pustakawan user
 * Uses Laravel's Eloquent ORM with proper bootstrapping
 */

// Check if Laravel is already bootstrapped
if (!file_exists('vendor/autoload.php')) {
    die("Error: vendor/autoload.php not found. Run 'composer install' first.\n");
}

require 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require __DIR__ . '/bootstrap/app.php';

// Bootstrap the application fully
$kernel = $app->make(Illuminate\Foundation\Console\Kernel::class);
$kernel->bootstrap();

// Now check for the user
$user = App\Models\User::where('email', 'pustakawan@gmail.com')->first();

if ($user) {
    echo "✅ User already exists!\n";
    echo "  Name: " . $user->name . "\n";
    echo "  Email: " . $user->email . "\n";
    echo "  Role: " . $user->role . "\n";
    echo "  Email verified at: " . ($user->email_verified_at ? $user->email_verified_at->toDateTimeString() : 'null') . "\n";
    
    // Check if role is correct
    if ($user->role !== 'pustakawan') {
        echo "\n⚠️  WARNING: User exists but with role '" . $user->role . "' instead of 'pustakawan'.\n";
        echo "  No auto-modification will be performed.\n";
    }
} else {
    echo "🔍 User does not exist, creating new pustakawan account...\n";
    
    // Create new user with proper hashing using Laravel's bcrypt
    $newUser = App\Models\User::create([
        'name' => 'Pustakawan Utama',
        'email' => 'pustakawan@gmail.com',
        'role' => 'pustakawan',
        'password' => bcrypt('password123'), // Laravel's bcrypt hashing
    ]);
    
    // Set email_verified_at (the migration has this column)
    $newUser->email_verified_at = now();
    $newUser->save();
    
    echo "✅ User created successfully!\n";
    echo "  ID: " . $newUser->id . "\n";
    echo "  Name: " . $newUser->name . "\n";
    echo "  Email: " . $newUser->email . "\n";
    echo "  Role: " . $newUser->role . "\n";
    echo "  email_verified_at: " . ($newUser->email_verified_at ? $newUser->email_verified_at->toDateTimeString() : 'null') . "\n";
    
    // Verify the user was created correctly
    echo "\n🔍 Verification:\n";
    $verifyUser = App\Models\User::where('email', 'pustakawan@gmail.com')->first();
    echo "  Found user: " . ($verifyUser ? 'Yes' : 'No') . "\n";
    echo "  Role: " . ($verifyUser ? $verifyUser->role : 'N/A') . "\n";
}