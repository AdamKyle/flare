import React from "react";
import BasicCard from "../../ui/cards/basic-card";

// Define an empty interface for props
interface CraftingEnchantingProps {}

export default class CraftingEnchanting extends React.Component<CraftingEnchantingProps> {
    constructor(props: CraftingEnchantingProps) {
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
                            Crafting and Enchanting
                        </h2>
                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            While fighting, players will find randomly enchanted
                            gear, similar to what you would get in other games
                            like Diablo or Path of Exile. As you start
                            progressing, you will want to craft your own gear,
                            starting with shop gear and advancing beyond what
                            the shop sells around level 200 of the crafting
                            type.
                        </p>

                        <p className="mt-4 dark:text-gray-300 text-gray-700">
                            From there, you will want to enchant your gear with
                            specific enchantments, which you can see{" "}
                            <a
                                href="#"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                here
                            </a>
                            . As you progress through these systems, you will be
                            able to take on stronger and tougher creatures,
                            gaining more rewards, gold, and other currencies for
                            mid and end-game crafting.
                        </p>
                    </div>
                </div>
            </BasicCard>
        );
    }
}
