{{$user->character->name}} Requesting to be unbanned

Original Reason:
---------------

{{$user->banned_reason}}

Request:
---------------

{{$user->un_ban_request}}

---------------

If you would like to deal with this please login:

{{route('login')}}