import React from "react";
import BasicCard from "../../ui/cards/basic-card";

// Define an empty interface for props
interface WelcomeSectionProps {}

export default class WelcomeSection extends React.Component<WelcomeSectionProps> {
    constructor(props: WelcomeSectionProps) {
        super(props);
    }

    render() {
        return (
            <BasicCard>
                <div className="flex items-center space-x-4">
                    <img
                        src="https://placehold.co/200x150"
                        alt="Placeholder"
                        className="w-1/2 h-auto object-cover"
                    />
                    <div className="w-1/2 flex flex-col justify-center pl-4">
                        <h2 className="text-2xl font-bold dark:text-white text-black">
                            Welcome to Tlessa
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Planes of Tlessa is about <strong>fighting</strong>{" "}
                            monsters to gain loot to take on stronger critters.
                            The core game loop of Tlessa is simple:{" "}
                            <strong>fight</strong>, <strong>craft</strong> and{" "}
                            <strong>enchant</strong> to make better gear.
                        </p>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Tlessa also offers a variety of{" "}
                            <a href={""} target={"_blank"}>
                                features
                            </a>{" "}
                            and depth in its systems from character advancement
                            to kingdom management and more.
                        </p>

                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Tlessa also offers a{" "}
                            <a href={""} target={"_blank"}>
                                comprehensive help
                            </a>{" "}
                            section as well as the <strong>Guide system</strong>
                        </p>
                    </div>
                </div>
            </BasicCard>
        );
    }
}
