import React from "react";
import BasicCard from "../../ui/cards/basic-card";

// Define an empty interface for props
interface EventsProps {}

export default class Events extends React.Component<EventsProps> {
    constructor(props: EventsProps) {
        super(props);
    }

    render() {
        return (
            <BasicCard>
                <div className="flex items-center space-x-4 mt-8">
                    <img
                        src="https://placehold.co/200x150"
                        alt="Crafting illustration"
                        className="w-1/2 h-auto object-cover"
                    />
                    <div className="w-1/2 flex flex-col justify-center pl-4">
                        <h2 className="text-2xl font-bold dark:text-white text-black">
                            Raids, Temporary Planes and Weekly Events!
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Tlessa also offers its players a variety of
                            activities to participte in such as events that
                            unlock new planes of existance for all levels of
                            players to gain new and epic loot and participtae in
                            quests that help further the story of the game.
                            These events repeat so no worries on missing out!
                        </p>
                    </div>
                </div>
            </BasicCard>
        );
    }
}
