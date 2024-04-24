import React, { Fragment } from "react";
import Crafting from "../general-crafting/crafting";
import Enchanting from "../enchanting/enchanting";
import Alchemy from "../alchemy/alchemy";
import WorkBench from "../../npc-actions/work-bench/work-bench";
import Trinketry from "../trinketry/trinketry";
import QueenOfHearts from "../../npc-actions/queen-of-hearts/queen-of-hearts";
import GemCrafting from "../gem-crafting/gem-crafting";
import SeerCamp from "../../npc-actions/seer-camp/seer-camp";
import LabyrinthOracle from "../../npc-actions/labyrinth-oracle/labyrinth-oracle";

export default class CraftingSection extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    renderCraftingSection() {
        switch (this.props.type) {
            case "craft":
                return (
                    <Crafting
                        is_small={this.props.is_small}
                        character_id={this.props.character_id}
                        remove_crafting={this.props.remove_crafting}
                        cannot_craft={this.props.cannot_craft}
                        fame_tasks={this.props.fame_tasks}
                        user_id={this.props.user_id}
                    />
                );
            case "enchant":
                return (
                    <Enchanting
                        character_id={this.props.character_id}
                        remove_crafting={this.props.remove_crafting}
                        cannot_craft={this.props.cannot_craft}
                        user_id={this.props.user_id}
                    />
                );
            case "alchemy":
                return (
                    <Alchemy
                        character_id={this.props.character_id}
                        remove_crafting={this.props.remove_crafting}
                        cannot_craft={this.props.cannot_craft}
                    />
                );
            case "workbench":
                return (
                    <WorkBench
                        character_id={this.props.character_id}
                        remove_crafting={this.props.remove_crafting}
                        cannot_craft={this.props.cannot_craft}
                    />
                );
            case "trinketry":
                return (
                    <Trinketry
                        character_id={this.props.character_id}
                        remove_crafting={this.props.remove_crafting}
                        cannot_craft={this.props.cannot_craft}
                    />
                );
            case "queen":
                return (
                    <QueenOfHearts
                        character_id={this.props.character_id}
                        remove_crafting={this.props.remove_crafting}
                        cannot_craft={this.props.cannot_craft}
                        user_id={this.props.user_id}
                    />
                );
            case "gem-crafting":
                return (
                    <GemCrafting
                        character_id={this.props.character_id}
                        remove_crafting={this.props.remove_crafting}
                        cannot_craft={this.props.cannot_craft}
                    />
                );
            case "seer-camp":
                return (
                    <SeerCamp
                        character_id={this.props.character_id}
                        leave_seer_camp={this.props.remove_crafting}
                    />
                );
            case "labyrinth-oracle":
                return (
                    <LabyrinthOracle
                        character_id={this.props.character_id}
                        remove_crafting={this.props.remove_crafting}
                        cannot_craft={this.props.cannot_craft}
                        user_id={this.props.user_id}
                    />
                );
            default:
                return null;
        }
    }

    render() {
        return this.renderCraftingSection();
    }
}
