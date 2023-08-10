import React, { Fragment } from "react";
import DropDown from "../../../../components/ui/drop-down/drop-down";
import CraftingSectionManager from "../../../../lib/game/actions/smaller-actions-components/crafting-section-manager";
import {CraftingOptions} from "../../../../lib/game/types/actions/crafting-type-options";
import SmallCraftingSectionProps from "./types/small-crafting-section-props";
import SmallCraftingSectionState from "./types/small-crafting-section-state";
import MainCraftingSection from '../crafting-section';
import DangerButton from "../../../../components/ui/buttons/danger-button";

export default class SmallCraftingSection extends React.Component<SmallCraftingSectionProps, SmallCraftingSectionState> {

    private craftingSectionManager: CraftingSectionManager;

    constructor(props: SmallCraftingSectionProps) {
        super(props);

        this.craftingSectionManager = new CraftingSectionManager(this);

        this.state = {
            crafting_type: null,
        }
    }

    removeCraftingType() {
        this.setState({
            crafting_type: null,
        })
    }

    setCraftingType(type: CraftingOptions) {
        this.setState({
            crafting_type: type
        });
    }

    render() {
        return (
            <div className='relative'>
                {
                    this.state.crafting_type !== null ?
                        <MainCraftingSection
                            remove_crafting={this.removeCraftingType.bind(this)}
                            type={this.state.crafting_type}
                            character_id={this.props.character.id}
                            user_id={this.props.character.user_id}
                            cannot_craft={this.craftingSectionManager.cannotCraft()}
                        />
                    :
                        <div className="text-center my-4">
                            <DropDown menu_items={this.craftingSectionManager.buildCraftingList(this.setCraftingType.bind(this))}
                                      button_title={'Craft/Enchant'}
                                      disabled={this.craftingSectionManager.cannotCraft()}
                                      selected_name={this.craftingSectionManager.getSelectedCraftingOption()}
                                      show_close_button={true}
                                      close_button_action={this.props.close_crafting_section}
                            />

                        </div>
                }
            </div>

        );
    }
}
