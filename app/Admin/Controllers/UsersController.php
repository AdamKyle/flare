<?php

namespace App\Admin\Controllers;


use App\Flare\Models\CharacterAutomation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Admin\Mail\ResetPasswordEmail;
use App\Admin\Services\UserService;
use App\Flare\Models\User;
use App\Flare\Mail\GenericMail;
use App\Flare\Jobs\SendOffEmail;
use App\Game\Messages\Events\ServerMessageEvent;

class UsersController extends Controller {

    private $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function index() {
        return view('admin.users.users');
    }

    public function show(User $user) {

        if ($user->hasRole('Admin')) {
            return redirect()->back()->with('error', 'Admins do not have characters');
        }

        return view('admin.users.user', [
            'character' => $user->character,
        ]);
    }

    public function silenceUser(Request $request, User $user) {
        $request->validate([
            'silence_for' => 'required',
        ]);

        $this->userService->silence($user, (int) $request->silence_for);

        return redirect()->back()->with('success', $user->character->name . ' Has been silenced for: ' . (int) $request->silence_for . ' minutes');
    }

    public function banUser(Request $request, User $user) {

        $request->validate([
            'for'    => 'required',
            'reason' => 'required',
        ]);

        if (!$this->userService->banUser($user, $request->all())) {
            return redirect()->back()->with('error', 'Invalid input for ban length.');
        }

        return redirect()->to(route('users.user', [
            'user' => $user->id
        ]))->with('success', 'User has been banned.');
    }

    public function unBanUser(Request $request, User $user) {

        $user->update([
            'is_banned'             => false,
            'unbanned_at'           => null,
            'un_ban_request'        => null,
            'ban_reason'            => null,
            'ignored_unban_request' => false,
        ]);

        $mailable = new GenericMail($user, 'You are now unbanned and may log in again.', 'You have been unbanned');

        SendOffEmail::dispatch($user, $mailable)->delay(now()->addMinutes(1));

        return redirect()->back()->with('success', 'User has been unbanned.');
    }

    public function ignoreUnBanRequest(Request $request, User $user) {

        $user->update([
            'ignored_unban_request' => true,
        ]);

        $mailable = new GenericMail($user, 'This is to inform you that your request to be unbanned has been denied. All decisions are final. Future requests will be ignored.', 'Your request has been denied', true);

        SendOffEmail::dispatch($user, $mailable)->delay(now()->addMinutes(1));

        return redirect()->back()->with('success', 'User request to be unbanned was ignored. Email has been sent.');
    }

    public function forceNameChange(Request $request, User $user) {
        $this->userService->forceNameChange($user);

        return redirect()->back()->with('success', $user->character->name . ' forced to change their name.');
    }
}
