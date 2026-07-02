<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Authentication & User Messages (English)
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'failed'   => 'Invalid username or password.',
        'banned'   => 'Your account has been banned from the system.',
        'inactive' => 'Your account is not activated yet.',
        'success'  => 'Login successful!',
        'logout'   => 'Logged out successfully, access token revoked.',
        'forbidden'   => 'You do not have permission to perform this action.',
        'current_password_incorrect' => 'The current password is incorrect.',
        'change_password_success' => 'Account password changed successfully.',
        'locked_temp' => 'Your account is temporarily locked. Please try again in :minutes minutes.',
        'locked_permanently' => 'Your account has been permanently locked due to too many failed login attempts. Please contact support.',
        'locked_permanently_11' => 'Your account has been permanently locked due to 11 failed login attempts.',
        'wrong_password_lock_warning' => 'Too many failed login attempts. Account temporarily locked for :minutes minutes.',
        'wrong_password_attempts_left' => 'Incorrect password. You have :attempts attempts left before your account is temporarily locked.',
        'account_not_found' => 'No account found associated with this information.',
        'otp_sent' => 'OTP verification code has been sent successfully.',
        'otp_invalid' => 'Invalid OTP verification request.',
        'otp_expired' => 'The OTP code has expired.',
        'otp_incorrect' => 'The OTP code is incorrect.',
        'reset_success' => 'Your password has been successfully updated.',
        'register_success' => 'Account registered successfully!',
    ],
    'user' => [
        'created'   => 'Account registered successfully.',
        'updated'   => 'Profile updated successfully.',
        'not_found' => 'User not found in our records.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Game Settings & Beatmaps Messages
    |--------------------------------------------------------------------------
    */
    'setting' => [
        'updated'   => 'Game settings updated successfully.',
        'not_found' => 'User settings record could not be found.',
    ],
    'beatmap' => [
        'created'        => 'Beatmap created successfully.',
        'updated'        => 'Beatmap updated successfully.',
        'deleted'        => 'Beatmap deleted successfully.',
        'not_found'      => 'The requested beatmap was not found.',
        'import_success' => 'Beatmap data imported successfully from JSON chart structure.',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Scores Messages
    |--------------------------------------------------------------------------
    */
    'score' => [
        'saved'     => 'Your score has been saved successfully!',
        'not_found' => 'The requested score data was not found.',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Input Form Validation Messages
    |--------------------------------------------------------------------------
    */
    'validation' => [
        // Auth & Users
        'username_required' => 'The username field is required.',
        'password_required' => 'The password field is required.',
        'email_required'    => 'The email field is required.',
        'email_unique'      => 'This email address has already been taken.',
        'identifier_required' => 'Please enter your Email or Phone number.',
        'email_invalid' => 'Invalid Email format.',
        'otp_required' => 'Please enter the OTP code.',
        'password_min' => 'The password must be at least 6 characters.',
        'password_confirmed' => 'The password confirmation does not match.',
        'phone_unique' => 'The phone number is already registered in the system.',
        'current_password_required' => 'Please enter your current password.',
        'password_different' => 'The new password must be different from the current password.',

        // Beatmaps
        'name_required'     => 'The beatmap name field is required.',
        'url_required'      => 'The music file download link is required.',
        'url_invalid'       => 'The music link format is invalid. Must be a valid URL.',
        'beats_required'    => 'The beats chart data is required.',
        'beats_array'       => 'The beats data must be sent as a valid array format.',
        'json_invalid'      => 'The provided data fields or date format in the JSON file is invalid.',

        // Scores
        'beatmap_id_required' => 'The beatmap ID field is required.',
        'beatmap_id_exists'   => 'The selected beatmap does not exist.',
        'score_required'      => 'The score field is required.',
        'score_integer'       => 'The gameplay score must be a positive integer.',
    ]
];
