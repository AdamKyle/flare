import React from "react";
import BasicCard from "../../ui/cards/basic-card";

// Define an empty interface for props
interface QuestProps {}

export default class Quests extends React.Component<QuestProps> {
    constructor(props: QuestProps) {
        super(props);
    }

    render() {
        return (
            <BasicCard>
                <div className="flex items-center space-x-4 mt-8">
                    <div className="w-1/2 flex flex-col justify-center pl-4">
                        <h2 className="text-2xl font-bold dark:text-white text-black">
                            Quests to unlock progression
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Tlessa does not gate things behind cashops, all
                            players are free to earn all features and aspects of
                            the game in as litle or as much time as they choose
                            depeding o their play style.
                        </p>

                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            For this reason, Tlessa offers{" "}
                            <a
                                href="#"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                quests
                            </a>
                            . Which not only progress the main story line of the
                            game, but also help to unlock features such as
                            leveling beyond level 1,000, different plane access,
                            ability to walk on some of the planes water or
                            liquid surfaces and more.
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
