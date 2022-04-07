import React, {Fragment} from "react";
import Crafting from "./crafting-sections/crafting";
import Enchanting from "./crafting-sections/enchanting";

export default class CraftingSection extends React.Component<any, any> {

    constructor(props: any) {
        super(props);
    }

    renderCraftingSection() {
        switch (this.props.type) {
            case 'craft':
                return <Crafting character_id={this.props.character_id} remove_crafting={this.props.remove_crafting} cannot_craft={this.props.cannot_craft} />
            case 'enchant':
                return <Enchanting character_id={this.props.character_id} remove_crafting={this.props.remove_crafting} cannot_craft={this.props.cannot_craft} />
            default:
                null;
        }
    }

    render() {
        return this.renderCraftingSection();
    }

}
