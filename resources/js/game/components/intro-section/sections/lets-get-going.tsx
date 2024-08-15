import React from "react";
import BasicCard from "../../ui/cards/basic-card";

// Define an empty interface for props
interface LetsGetGoingProps {}

export default class LetsGetGoing extends React.Component<LetsGetGoingProps> {
    constructor(props: LetsGetGoingProps) {
        super(props);
    }

    render() {
        return (
            <BasicCard>
                <div className="flex items-center space-x-4 mt-8">
                    <div className="w-1/2 flex flex-col justify-center pl-4">
                        <h2 className="text-2xl font-bold dark:text-white text-black">
                            In to the world we go!
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            There is so much to explore and so much to do. the
                            game can be a bit overwhelming, but do not fret, I
                            have your back! When you start, you will be
                            presented with a modal called The Guide.
                        </p>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            The Guide is made of three parts: Requirements,
                            Story and Instructions for either mobile of desktop,
                            depending on which device you play on. Follow this
                            guide, chat in chat, ask your questions, submit your
                            bugs and suggestions and together Tlessa will grow
                            and flourish into oe of the best and ultimate PBBG's
                            out there!
                        </p>

                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Lets go adventurer!
                        </p>
                    </div>
                    <img
                        src="https://placehold.co/200x150"
                        alt="Crafting illustration"
                        className="w-1/2 h-auto object-cover"
                    />
                </div>
            </BasicCard>
        );
    }
}
