{{-- resources/views/emails/user-invitation-text.blade.php --}}
Hello {{ $user->first_name }},

You have been invited to join {{ $companyName }} as a {{ ucfirst($user->role) }}.

Your account has been created with the following details:
- Email: {{ $user->email }}
- Role: {{ ucfirst($user->role) }}
- Temporary Password: {{ $temporaryPassword }}

To access your account, please visit: {{ $loginUrl }}

Please log in using your email address and the temporary password provided above. 
You will be prompted to change your password on your first login.

If you have any questions, please contact our support team at {{ $supportEmail }}.

Best regards,
{{ $companyName }} Team