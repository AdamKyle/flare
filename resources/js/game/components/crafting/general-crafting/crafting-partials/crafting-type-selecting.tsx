import React, { Fragment } from "react";
import Select from "react-select";
import CraftingSelectionTypeProps from "./types/crafting-type-selection-props";
import DangerAlert from "../../../ui/alerts/simple-alerts/danger-alert";

export default class CraftingTypeSelection extends React.Component<
    CraftingSelectionTypeProps,
    any
> {
    private selectableTypes: { label: string; value: string }[];

    constructor(props: CraftingSelectionTypeProps) {
        super(props);

        this.selectableTypes = [
            {
                label: "For my class",
                value: "for-class",
            },
            {
                label: "Daggers",
                value: "dagger",
            },
            {
                label: "Swords",
                value: "sword",
            },
            {
                label: "Claws",
                value: "claw",
            },
            {
                label: "Wands",
                value: "wand",
            },
            {
                label: "Censers",
                value: "censer",
            },
            {
                label: "Staves",
                value: "stave",
            },
            {
                label: "Hammers",
                value: "hammer",
            },
            {
                label: "Bows",
                value: "bow",
            },
            {
                label: "Guns",
                value: "gun",
            },
            {
                label: "Fans",
                value: "fan",
            },
            {
                label: "Maces",
                value: "mace",
            },
            {
                label: "Scratch Awls",
                value: "scratch-awl",
            },
            {
                label: "Armour",
                value: "armour",
            },
            {
                label: "Rings",
                value: "ring",
            },
            {
                label: "Spells",
                value: "spell",
            },
        ];
    }

    defaultCraftingType() {
        return { label: "Please select type to craft", value: "" };
    }

    render() {
        return (
            <Fragment>
                <Select
                    onChange={this.props.select_type_to_craft.bind(this)}
                    options={this.selectableTypes}
                    menuPosition={"absolute"}
                    menuPlacement={"bottom"}
                    styles={{
                        menuPortal: (base) => ({
                            ...base,
                            zIndex: 9999,
                            color: "#000000",
                        }),
                    }}
                    menuPortalTarget={document.body}
                    value={this.defaultCraftingType()}
                />
                {this.props.error_message !== null && (
                    <DangerAlert additional_css={"mt-4"}>
                        {this.props.error_message}
                    </DangerAlert>
                )}
                <p className="mt-3 text-sm">
                    When it comes to weapons, selecting "For my class" will
                    return only the weapons for your class. In the case of
                    Alcoholics class, you won't have any weapons. So you'll be
                    asked to select another category as Alcoholics don't use
                    weapons. For Prisoner class you'll be asked to select any
                    category of weapon as you don't have a septic weapon type
                    tied to your class. Of course, you can craft any weapon to
                    gain Weapon Crafting experience.
                </p>
            </Fragment>
        );
    }
}
