import React, {Fragment} from "react";
import Select from "react-select";
import CraftingSelectionTypeProps from "./types/crafting-type-selection-props";

export default class CraftingTypeSelection extends React.Component<CraftingSelectionTypeProps, any> {

    private selectableTypes: {label: string, value: string}[]

    constructor(props: CraftingSelectionTypeProps) {
        super(props);

        this.selectableTypes = [
            {
                label: "General Weapons",
                value: "weapon",
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
                value: "guns",
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
                <p className="mt-3 text-sm">
                    When it comes to weapons there are general
                    "weapons" that any one can use, then there
                    are specialty weapons: Hammers, Staves,
                    Bows and Guns. For Weapon Crafting, you can craft ANY
                    of these types to gain levels.
                </p>
            </Fragment>
        );
    }
}
