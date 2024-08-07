import React from "react";
import UnitsMovementTable from "./unit-movement/units-movement-table";
import UnitMovementProps from "./types/unit-movement-props";

export default class UnitMovement extends React.Component<
    UnitMovementProps,
    {}
> {
    constructor(props: UnitMovementProps) {
        super(props);
    }

    render() {
        return (
            <UnitsMovementTable
                units_in_movement={this.props.units_in_movement}
                dark_tables={this.props.dark_tables}
                character_id={this.props.character_id}
            />
        );
    }
}
