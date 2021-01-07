<?php

namespace App\Admin\Controllers;

use Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Flare\Jobs\UpdateSilencedUserJob;
use App\Flare\Models\User;
use App\Flare\Events\ServerMessageEvent;
use App\Admin\Events\ForceNameChangeEvent;
use App\Admin\Mail\GenericMail;
use App\Admin\Mail\ResetPasswordEmail;
use App\Admin\Services\UserService;

class UsersController extends Controller {

    private $userService;

    public function __construct(UserService $userService) {
        $this->userService = $userService;
    }

    public function index() {
        return view('admin.users.users');
    }

    public function resetPassword(User $user) {

        $password = Str::random(80);

        $user->update([
            'password' => Hash::make($password)
        ]);

        $token = app('Password')::getRepository()->create($user);

        Mail::to($user->email)->send(new ResetPasswordEmail($user, $token));

        return redirect()->back()->with('success', $user->character->name . ' password reset email sent.');
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

        $canSpeakAgainAt = now()->addMinutes((int) $request->silence_for);

        $user->update([
            'is_silenced' => true,
            'can_speak_again_at' => $canSpeakAgainAt,
        ]);

        $user   = $user->refresh();

        $message = 'The creator has silenced you until: ' . $canSpeakAgainAt->format('Y-m-d H:i:s') . ' ('.(int) $request->silence_for.' Minutes server time) Making accounts to get around this is a bannable offense.';
        
        event(new ServerMessageEvent($user, 'silenced', $message));

        UpdateSilencedUserJob::dispatch($user)->delay($canSpeakAgainAt);

        return redirect()->back()->with('success', $user->character->name . ' Has been silenced for: ' . (int) $request->silence_for . ' minutes');
    }

    public function banUser(Request $request, User $user) {
        if (!$request->has('ban_for')) {
            return redirect()->back()->with('error', 'Invalid input.');
        }

        return redirect()->to(route('ban.reason', [
            'user' => $user,
            'for'  => $request->ban_for,
        ]));
    }

    public function banReason(User $user, string $for) {
        return view('admin.users.user-ban-reason', [
            'user' => $user,
            'for'  => $for,
        ]);
    }

    public function submitBanReason(Request $request, User $user) {

        $request->validate([
            'for'    => 'required',
            'reason' => 'required',
        ]);

        $unBanAt = null;

        if ($request->for !== 'perm') {
            $unBanAt = $this->userService->fetchUnBanAt($user, $request->for);

            if (is_null($unBanAt)) {
                redirect()->back()->with('error', 'Invalid input for ban length.');
            }
        } else {
            $this->userService->broadCastAdminMessage($user);
        }

        $user->update([
            'is_banned'   => true,
            'unbanned_at' => $unBanAt,
            'banned_reason' => $request->reason,
        ]);
        
        $this->userService->sendUserMail($user, $unBanAt);
        
        return redirect()->to(route('users.user', [
            'user' => $user->id
        ]))->with('success', 'User has been banned.');
    }

    public function unBanUser(Request $request, User $user) {
        
        $user->update([
            'is_banned'      => false,
            'unbanned_at'    => null,
            'un_ban_request' => null,
            'ban_reason'     => null,
        ]);

        Mail::to($user->email)->send(new GenericMail($user, 'You are now unbanned and may log in again.', 'You have been unbanned'));

        return redirect()->back()->with('success', 'User has been unbanned.');
    }

    public function ignoreUnBanRequest(Request $request, User $user) {
        Mail::to($user->email)->send(new GenericMail($user, 'This is to inform you that your request to be unbanned has been denied. All decisions are final. Future requests will be ignored.', 'Your request has been denied', true));

        return redirect()->back()->with('success', 'User request to be unbanned was ignored. Email has been sent.');
    }

    public function forceNameChange(Request $request, User $user) {
        $user->character->update([
            'force_name_change' => true
        ]);

        event(new ForceNameChangeEvent($user->character));

        return redirect()->back()->with('success', $user->character->name . ' forced to change their name.');
    }
}
