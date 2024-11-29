import React from "react";
import { formatNumber } from "../../../../lib/game/format-number";
import CraftingXPProps from "./types/crafting-xp-props";

export default class CraftingXp extends React.Component<CraftingXPProps> {
    constructor(props: CraftingXPProps) {
        super(props);
    }

    getXpPercentage(): number {
        const xpNext = this.props.skill_xp.next_level_xp;
        const currentXP = this.props.skill_xp.current_xp;

        return (currentXP / xpNext) * 100;
    }

    render() {
        return (
            <div className="my-2">
                <div className="flex justify-between mb-1">
                    <span className="font-medium text-orange-700 dark:text-white text-xs">
                        {" "}
                        {this.props.skill_xp.skill_name} Skill XP (LV:{" "}
                        {this.props.skill_xp.level})
                    </span>
                    <span className="text-xs font-medium text-orange-700 dark:text-white">
                        {formatNumber(this.props.skill_xp.current_xp)}/
                        {formatNumber(this.props.skill_xp.next_level_xp)}
                    </span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                    <div
                        className="bg-orange-600 h-1.5 rounded-full"
                        style={{ width: this.getXpPercentage() + "%" }}
                    ></div>
                </div>
            </div>
        );
    }
}
