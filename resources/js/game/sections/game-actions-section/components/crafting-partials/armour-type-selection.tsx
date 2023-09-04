import React, {Fragment} from "react";
import Select from "react-select";
import ArmourSelectionTypeProps from "./types/armour-type-selection-props";

export default class ArmourTypeSelection extends React.Component<ArmourSelectionTypeProps, any> {

    private selectableTypes: {label: string, value: string}[]

    constructor(props: ArmourSelectionTypeProps) {
        super(props);

        this.selectableTypes = [
            {
                label: "Helmet",
                value: "helmet",
            },
            {
                label: "Body",
                value: "body",
            },
            {
                label: "Sleeves",
                value: "sleeves",
            },
            {
                label: "Gloves",
                value: "gloves",
            },
            {
                label: "Shields",
                value: "shield",
            },
            {
                label: "Feet",
                value: "feet",
            },
        ];
    }

    defaultCraftingType() {
        return { label: "Please select armour type to craft", value: "" };
    }

    render() {
        return (
            <Fragment>
                <Select
                    onChange={this.props.select_armour_type_to_craft.bind(this)}
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
                    are specialty weapons: Hammers, Staves and
                    Bows. For Weapon Crafting, you can craft ANY
                    of these types to gain levels.
                </p>
            </Fragment>
        );
    }
}
