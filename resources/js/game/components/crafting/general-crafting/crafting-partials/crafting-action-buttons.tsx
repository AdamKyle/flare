import React, { Fragment } from "react";
import PrimaryButton from "../../../ui/buttons/primary-button";
import DangerButton from "../../../ui/buttons/danger-button";
import CraftingActionButtonsProps from "./types/crafting-action-buttons-props";
import SuccessButton from "../../../ui/buttons/success-button";
import OrangeButton from "../../../ui/buttons/orange-button";

export default class CraftingActionButtons extends React.Component<
    CraftingActionButtonsProps,
    any
> {
    constructor(props: CraftingActionButtonsProps) {
        super(props);
    }

    render() {
        return (
            <Fragment>
                <PrimaryButton
                    additional_css="mb-2"
                    button_label={"Craft"}
                    on_click={() => this.props.craft(false, false)}
                    disabled={this.props.can_craft}
                />
                {this.props.show_craft_for_npc ? (
                    <SuccessButton
                        additional_css={"lg:ml-2 mb-2"}
                        button_label={"Craft for NPC"}
                        on_click={() => this.props.craft(true, false)}
                        disabled={this.props.can_craft}
                    />
                ) : null}
                {this.props.show_craft_for_event ? (
                    <OrangeButton
                        additional_css={"lg:ml-2 mb-2"}
                        button_label={"Craft for Event"}
                        on_click={() => this.props.craft(false, true)}
                        disabled={this.props.can_craft}
                    />
                ) : null}
                <PrimaryButton
                    button_label={"Change Type"}
                    on_click={this.props.change_type}
                    disabled={this.props.can_change_type}
                    additional_css={"lg:ml-2 mb-2"}
                />
                <DangerButton
                    button_label={"Close"}
                    on_click={this.props.clear_crafting}
                    additional_css={"lg:ml-2"}
                    disabled={this.props.can_close}
                />
                <a
                    href="/information/crafting"
                    target="_blank"
                    className="relative top-[20px] md:top-[0px] ml-2"
                >
                    Help <i className="fas fa-external-link-alt"></i>
                </a>
            </Fragment>
        );
    }
}
