import React, { Fragment } from "react";
import DropDown from "../../ui/drop-down/drop-down";
import CraftingSectionManager from "../../../lib/game/actions/smaller-actions-components/crafting-section-manager";
import {CraftingOptions} from "../base-components/types/crafting-type-options";
import SmallCraftingSectionProps from "./types/small-crafting-section-props";
import SmallCraftingSectionState from "./types/small-crafting-section-state";
import MainCraftingSection from '../base-components/crafting-section';
import DangerButton from "../../ui/buttons/danger-button";
import Select from "react-select";

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
                            fame_tasks={this.props.fame_tasks}
                            is_small={true}
                        />
                    :
                        <div className="text-center my-4">
                            <Select
                                onChange={(value) => this.craftingSectionManager.setCraftingTypeForSmallerActionsList(value)}
                                options={this.craftingSectionManager.smallCraftingList()}
                                menuPosition={"absolute"}
                                menuPlacement={"bottom"}
                                styles={{
                                    menuPortal: (base: any) => ({
                                        ...base,
                                        zIndex: 9999,
                                        color: "#000000",
                                    }),
                                }}
                                menuPortalTarget={document.body}
                                value={this.craftingSectionManager.getSelectedCraftingTypeForSmallerActionsList()}
                            />

                            <DangerButton button_label={'Close'} on_click={this.props.close_crafting_section} additional_css={'my-4 w-full'} />

                        </div>
                }
            </div>

        );
    }
}
