import React, {Fragment} from "react";
import Crafting from "./crafting-sections/crafting";
import Enchanting from "./crafting-sections/enchanting";
import Alchemy from "./crafting-sections/alchemy";
import WorkBench from "./crafting-sections/work-bench";
import Trinketry from "./crafting-sections/trinketry";
import QueenOfHearts from "./crafting-sections/queen-of-hearts";
import GemCrafting from "./crafting-sections/gem-crafting";
import SeerCamp from "./crafting-sections/seer-camp";

export default class CraftingSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    renderCraftingSection() {
        switch (this.props.type) {
            case 'craft':
                return <Crafting is_small={this.props.is_small} character_id={this.props.character_id} remove_crafting={this.props.remove_crafting} cannot_craft={this.props.cannot_craft} />
            case 'enchant':
                return <Enchanting character_id={this.props.character_id} remove_crafting={this.props.remove_crafting} cannot_craft={this.props.cannot_craft} />
            case 'alchemy':
                return <Alchemy character_id={this.props.character_id} remove_crafting={this.props.remove_crafting} cannot_craft={this.props.cannot_craft} />
            case 'workbench':
                return <WorkBench character_id={this.props.character_id} remove_crafting={this.props.remove_crafting} cannot_craft={this.props.cannot_craft} />
            case 'trinketry':
                return <Trinketry character_id={this.props.character_id} remove_crafting={this.props.remove_crafting} cannot_craft={this.props.cannot_craft} />
            case 'queen':
                return <QueenOfHearts character_id={this.props.character_id} remove_crafting={this.props.remove_crafting} cannot_craft={this.props.cannot_craft} user_id={this.props.user_id} />
            case 'gem-crafting':
                return <GemCrafting character_id={this.props.character_id} remove_crafting={this.props.remove_crafting} cannot_craft={this.props.cannot_craft} />
            case 'seer-camp':
                return <SeerCamp character_id={this.props.character_id} leave_seer_camp={this.props.remove_crafting} />
            default:
                return null;
        }
    }

    render() {
        return this.renderCraftingSection();
    }

}
