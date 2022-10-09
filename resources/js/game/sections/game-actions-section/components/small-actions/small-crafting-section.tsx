import React, { Fragment } from "react";
import DropDown from "../../../../components/ui/drop-down/drop-down";
import CraftingSectionManager from "../../../../lib/game/actions/smaller-actions-components/crafting-section-manager";
import {CraftingOptions} from "../../../../lib/game/types/actions/crafting-type-options";
import SmallCraftingSectionProps from "../../../../lib/game/types/actions/components/smaller-actions/small-crafting-section-props";
import SmallCraftingSectionState from "../../../../lib/game/types/actions/components/smaller-actions/small-crafting-section-state";
import MainCraftingSection from '../crafting-section';

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
                <button type='button' onClick={this.props.close_crafting_section}
                        className='text-red-600 dark:text-red-500 absolute right-[-20px] top-[-25px]'
                >
                    <i className="fas fa-times-circle"></i>
                </button>

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
                        <Fragment>
                            <DropDown menu_items={this.craftingSectionManager.buildCraftingList(this.setCraftingType.bind(this))}
                                      button_title={'Craft/Enchant'}
                                      disabled={this.craftingSectionManager.cannotCraft()}
                                      selected_name={this.craftingSectionManager.getSelectedCraftingOption()}/>
                        </Fragment>
                }
            </div>

        );
    }
}
