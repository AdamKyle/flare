Hello, {{$user->character->name}}

You recently requested your password to be reset by an administrator.

Reset Your Password: {{route('password.reset', $token)}}
