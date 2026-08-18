<?php
return [
    'admin'=>[
        'title_in_singular'=>'User',
        'title_in_plural'=>'Users',
    ],

    'fields'=>[
        'name' => 'Name',
        'surname' => 'Surname',
        'email' => 'Email',
        'avatar' => 'Avatar',
        'password' => 'Password',
        'password_confirmation' => 'Password Confirmation',
        'phone' => 'Phone',
        'facebook_url' => 'Facebook',
        'linkedin_url' => 'LinkedIn',
        'twitter_url' => 'Twitter / X',
        'bio' => 'About me',
        'roles' => 'Roles',
        'role' => 'Role',
        'status' => 'Status',
        'created_at' => __('admin.date.created_at'),
        'updated_at' => __('admin.date.updated_at'),
    ],

    'statuses' => [
        'active' => 'Active',
        'blocked' => 'Blocked',
    ],

    'actions' => [
        'block' => 'Block',
        'unblock' => 'Unblock',
    ],

    'messages' => [
        'blocked' => 'User has been blocked.',
        'unblocked' => 'User has been unblocked.',
    ],

    'validation'=> [
        'name_required' => 'Name is required',
        'name_string' => 'Name must be a string',
        'name_max' => 'Name must be less than 255 characters',
        
        'email_required' => 'Email is required',
        'email_email' => 'Email must be a valid email',
        'email_regex' => 'Email must contain @ and domain (e.g. name@example.com)',
        'email_max' => 'Email must be less than 255 characters',
        'email_unique' => 'Email already exists',

        'phone_required' => 'Phone number is required',
        'phone_regex' => 'Only digits, spaces, + and hyphen are allowed',
        'phone_format' => 'Ukrainian format: 0970000000, 380970000000 or +380970000000',
        'phone_max' => 'Phone number must be at most 20 characters',
        
        'password_required' => 'Password is required',
        'password_min' => 'Password must be at least 8 characters',
        'password_confirmed' => 'Passwords do not match',
        
        'roles_array' => 'Roles must be an array',
        'role_required' => 'Role is required',
        'role_integer' => 'Role must be an integer',
        'role_exists' => 'Selected role does not exist',
    ],

];
