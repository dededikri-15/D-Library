<?php
/**
 * Check and create pustakawan user for D-Library Laravel 12
 * 
 * This script checks if a pustakawan account exists and creates it if not.
 * Uses Laravel's Eloquent ORM with proper password hashing.
 */

// Bootstrap Laravel application
$app = require __DIR__ . '/bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Foundation\Console\Kernel::class);
$kernel->bootstrap();

// Check if user already exists
$user = App\Models\User::where('email', 'pustakawan@gmail.com')->first();

if ($user) {
    echo "User already exists!\n";
    echo "  Name: " . $user->name . "\n";
    echo "  Email: " . $user->email . "\n";
    echo "  Role: " . $user->role . "\n";
} else {
    echo "User does not exist, creating new pustakawan account...\n";
    
    // Create new user with proper hashing using Laravel's bcrypt
    $newUser = App\Models\User::create([
        'name' => 'Pustakawan Utama',
        'email' => 'pustakawan@gmail.com',
        'role' => 'pustakawan',
        'password' => bcrypt('password123'), // Laravel's bcrypt hashing
    ]);
    
    // Set email_verified_at if column exists in migration (it does based on schema)
    $newUser->email_verified_at = now();
    $newUser->save();
    
    echo "User created successfully!\n";
    echo "  ID: " . $newUser->id . "\n";
    echo "  Name: " . $newUser->name . "\n";
    echo "  Email: " . $newUser->email . "\n";
    echo "  Role: " . $newUser->role . "\n";
    echo "  email_verified_at: " . ($newUser->email_verified_at ? $newUser->email_verified_at->toDateTimeString() : 'null') . "\n";
    
    // Verify the user was created correctly
    $verifyUser = App\Models\User::where('email', 'pustakawan@gmail.com')->first();
    echo "\nVerification:\n";
    echo "  Found user: " . ($verifyUser ? 'Yes' : 'No') . "\n";
    echo "  Role: " . ($verifyUser ? $verifyUser->role : 'N/A') . "\n";
}