@extends('layouts.app')

@section('content')
    <div class="w-full md:max-w-[65ch] ml-auto mr-auto">
        <x-core.page.title
            title="Planes of Tlessa Donations"
            route="{{url()->previous()}}"
            link="Back"
            color="primary"
        ></x-core.page.title>

        <x-core.cards.card>
            <div class="prose dark:prose my-4">
                <p>
                    Tlessa needs your help. At a cost of $52 CAD, Tlessa cannot
                    sustain its server costs on its own. Tlessa is a free game,
                    one where there are no cash shops, no ways to buy power.
                    It's a place where your accomplishments are entirely your
                    own, where we as a community come together to shape a world
                    we all want to play in, to call home, to quest in.
                </p>

                <p>
                    It is for this reason I am here today, before you, asking
                    you to donate whatever you can, as little or as much as you
                    please. Tlessa wants to stay around, to give its players the
                    community they desire and the voice they need when it comes
                    to shaping this living, breathing world of exploration and
                    dark fantasy.
                </p>

                <p>
                    There is no other game like Tlessa, and there never will be
                    another game like it. So please, help Tlessa stay alive and
                    be the place you call home!
                </p>
            </div>
            <div class="text-center text-4xl">
                <form
                    action="https://www.paypal.com/donate"
                    method="post"
                    target="_top"
                >
                    <input
                        type="hidden"
                        name="hosted_button_id"
                        value="S2QDQHV83DUH6"
                    />
                    <input
                        type="image"
                        src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif"
                        border="0"
                        name="submit"
                        title="PayPal - The safer, easier way to pay online!"
                        alt="Donate with PayPal button"
                    />
                    <img
                        alt=""
                        border="0"
                        src="https://www.paypal.com/en_CA/i/scr/pixel.gif"
                        width="1"
                        height="1"
                    />
                </form>
            </div>
        </x-core.cards.card>
    </div>
@endsection
