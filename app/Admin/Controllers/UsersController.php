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

    public function resetPassword(User $user) {

        $token = app('Password')::getRepository()->create($user);

        $mailable = new ResetPasswordEmail($token);

        SendOffEmail::dispatch($user, $mailable)->delay(now()->addMinutes(1));

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

        $this->userService->silence($user, (int) $request->silence_for);

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

        $user = $user->refresh();

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

        $mailable = new GenericMail($user, 'You are now unbanned and may log in again.', 'You have been unbanned');

        SendOffEmail::dispatch($user, $mailable)->delay(now()->addMinutes(1));

        return redirect()->back()->with('success', 'User has been unbanned.');
    }

    public function ignoreUnBanRequest(Request $request, User $user) {

        $mailable = new GenericMail($user, 'This is to inform you that your request to be unbanned has been denied. All decisions are final. Future requests will be ignored.', 'Your request has been denied', true);

        SendOffEmail::dispatch($user, $mailable)->delay(now()->addMinutes(1));

        return redirect()->back()->with('success', 'User request to be unbanned was ignored. Email has been sent.');
    }

    public function forceNameChange(Request $request, User $user) {
        $this->userService->forceNameChange($user);

        return redirect()->back()->with('success', $user->character->name . ' forced to change their name.');
    }

    public function enableAutoBattle(Request $request, User $user) {
        $user->update(['can_auto_battle' => true]);

        $character = $user->character;

        foreach ($character->factions as $faction) {
            $newPointsNeeded  = $faction->points_needed * 10;
            $newCurrentPoints = $faction->current_points * 10;

            $faction->update([
                'current_points' => $newCurrentPoints,
                'points_needed'  => $newPointsNeeded,
            ]);
        }

        event(new ServerMessageEvent($user->refresh(), 'The Creator has enabled auto battle for you. Please refresh.'));

        return redirect()->back()->with('success', $user->character->name . ' can now use auto battle.');
    }

    public function disableAutoBattle(Request $request, User $user) {
        $user->update(['can_auto_battle' => false]);

        $character = $user->character;

        foreach ($character->factions as $faction) {
            $newCurrentPoints = $faction->current_points;

            $newPointsNeeded = $faction->points_needed / 10;

            if ($faction->current_points > $newPointsNeeded) {
                $newCurrentPoints = $faction->current_points / 10;
            }

            $faction->update([
                'current_points' => $newCurrentPoints,
                'points_needed'  => $newPointsNeeded,
            ]);
        }

        CharacterAutomation::where('character_id', $user->character->id)->delete();

        event(new ServerMessageEvent($user->refresh(), 'The Creator has disabled auto battle on your account. Please refresh.'));

        return redirect()->back()->with('success', $user->character->name . ' can now use auto battle.');
    }
}
