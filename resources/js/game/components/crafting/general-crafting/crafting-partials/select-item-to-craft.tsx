import React from "react";
import Select from "react-select";
import SelectItemToCraftProps from "./types/select-item-to-craft-props";

export default class SelectItemToCraft extends React.Component<
    SelectItemToCraftProps,
    any
> {
    constructor(props: SelectItemToCraftProps) {
        super(props);
    }

    render() {
        return (
            <Select
                onChange={this.props.set_item_to_craft}
                options={this.props.items}
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
                value={this.props.default_item}
            />
        );
    }
}
