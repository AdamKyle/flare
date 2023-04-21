<?php

namespace App\Http\Controllers\Auth;

use Exception;
use Illuminate\Contracts\Validation\Validator as RequestValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use App\Flare\Events\CreateCharacterEvent;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\User;
use App\Flare\Jobs\RegisterMessage;
use App\Flare\Services\CanUserEnterSiteService;

class RegisterController extends Controller {

    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default, this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * @var CanUserEnterSiteService $canUserEnterSiteService
     */
    private CanUserEnterSiteService $canUserEnterSiteService;

    /**
     * @var string
     */
    protected string $redirectTo = '/';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(CanUserEnterSiteService $canUserEnterSiteService) {

        $this->canUserEnterSiteService = $canUserEnterSiteService;

        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return RequestValidator
     */
    protected function validator(array $data): RequestValidator {
        return Validator::make($data, [
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'     => ['required', 'string', 'min:10', 'confirmed'],
            'name'         => ['required', 'string', 'min:5', 'max:15', 'unique:characters', 'regex:/^[a-zA-Z0-9]+$/', 'unique:characters'],
            'race'         => ['required'],
            'class'        => ['required'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @param string $ip
     * @return User
     * @throws Exception
     */
    protected function create(array $data, string $ip): User {

        $user = User::where('ip_address', $ip)->where('is_banned', true)->first();

        if ($user) {
            $until = !is_null($user->unbanned_at) ? $user->unbanned_at->format('l jS \\of F Y h:i:s A') . ' ' . $user->unbanned_at->timezoneName . '.' : 'Forever.';

            throw new Exception('You have been banned until: ' . $until);
        }

        // Allows characters to create 10 accounts.
        if (User::where('ip_address', $ip)->count() >= 10 && env('APP_ENV') !== 'local') {
            throw new Exception('You cannot register anymore characters.');
        }

        return User::create([
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'ip_address'       => $ip,
            'last_logged_in'   => now(),
            'guide_enabled'    => isset($data['guide_enabled'])
        ]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function register(Request $request): RedirectResponse {
        $map = GameMap::where('default', true)->first();

        if (is_null($map)) {
            return redirect()->back()->with('error', 'No game map has been set as default or created. Registration is disabled.');
        }

        $this->validator($request->all())->validate();

        if (!$this->canUserEnterSiteService->canUserEnterSite($request->email)) {
            return redirect()->back()->with('error', 'I am sorry, right now the Registration and Login has been disabled while server maintenance and stability testing is taking place. We will be back up and running soon!');
        }

        try {
            $user = $this->create($request->all(), $request->ip());
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($user->guide_enabled) {
            Cache::put('user-show-guide-initial-message-' . $user->id, 'true');
        }

        event(new Registered($user));

        event(new CreateCharacterEvent($user, $map, $request));

        $this->guard()->login($user);

        RegisterMessage::dispatch($user->character)->delay(now()->addSeconds(5));

        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }

    /**
     * Show the application registration form.
     *
     * @return View
     */
    public function showRegistrationForm(): View {


        return view('auth.register', [
            'races'   => GameRace::pluck('name', 'id'),
            'classes' => GameClass::whereNull('primary_required_class_id')
                                  ->whereNull('secondary_required_class_id')
                                  ->pluck('name', 'id'),
        ]);
    }
}
