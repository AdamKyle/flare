import React from "react";
import BasicCard from "../../ui/cards/basic-card";

// Define an empty interface for props
interface FightingProps {}

export default class Fighting extends React.Component<FightingProps> {
    constructor(props: FightingProps) {
        super(props);
    }

    render() {
        return (
            <BasicCard>
                <div className="flex items-center space-x-4">
                    <div className="w-1/2 flex flex-col justify-center pl-4">
                        <h2 className="text-2xl font-bold dark:text-white text-black">
                            Fighting
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            Fighting is done by selecting a monster and clicking
                            attack to initiate the battle. From here we can
                            select one of the five attack types: Attack, Cast,
                            Cast and Attack, Attack and Cast or Defend. When you
                            are new, you should use Attack, this attacks with
                            your weapons, later on you can change up your attack
                            based on your class, for example: Heretics will
                            eventually want one of the Cast attacks: Cast, Cast
                            and Attack or Attack and Cast.
                        </p>

                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            You can learn more about the various attack types{" "}
                            <a href={""} target={"_blank"}>
                                here
                            </a>
                            .
                        </p>
                    </div>
                    <img
                        src="https://placehold.co/200x150"
                        alt="Placeholder"
                        className="w-1/2 h-auto object-cover"
                    />
                </div>
            </BasicCard>
        );
    }
}
