@extends('layouts.app')

@section('content')
    <div class="w-full md:max-w-[65ch] ml-auto mr-auto">
        <x-core.page-title
            title="Planes of Tlessa Donations"
            route="{{url()->previous()}}"
            link="Back"
            color="primary"
        ></x-core.page-title>

        <x-core.cards.card>
            <div class="prose dark:prose my-4">
                <p>
                    Tlessa needs your help. Developed by a single person, responsible for every aspect of the game, including new features and addressing issues.
                </p>
                <p>
                    Tlessa is completely free, with no cash shops or pay-to-win options. However, without generating income, sustaining the game solely depends on support. If I, The Creator, lose my job, I can only maintain the game for a limited time.
                </p>
                <p>
                    So, I'm reaching out to you. If you enjoy Tlessa and want to ensure its survival and ongoing development, please consider donating any amount you can.
                </p>
                <p>
                    For context, the game server costs $52 CAD per month. While I don't expect that level of generosity, it provides insight into the game's expenses.
                </p>
            </div>
            <div class="text-center text-4xl">
                <form action="https://www.paypal.com/donate" method="post" target="_top">
                    <input type="hidden" name="hosted_button_id" value="S2QDQHV83DUH6" />
                    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
                    <img alt="" border="0" src="https://www.paypal.com/en_CA/i/scr/pixel.gif" width="1" height="1" />
                </form>
            </div>
            <div class="prose dark:prose my-4">
                <p>
                    Clicking the button above will take you to a safe and secure site operated and owned by Paypal. You will see a giant zero.
                    Tap or click the $0 to enter a custom amount.
                </p>
                <p>
                    Paypal may offer the option to cover associated fees with your donation, but you're not obligated to click the checkbox.
                </p>
                <p>
                    Importantly, Planes of Tlessa doesn't gather any information from Paypal, such as your email or credit card details. You also have the option, though not obligatory, to contribute the same amount monthly.
                </p>
                <p>
                    Donating doesn't provide any in-game benefits like items or unlocked content, but earns you a heartfelt thank you from me.
                </p>
            </div>
        </x-core.cards.card>
    </div>
@endsection
