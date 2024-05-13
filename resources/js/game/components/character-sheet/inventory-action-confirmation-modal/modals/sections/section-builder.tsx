import React from "react";
import { InventoryActionConfirmationType } from "../../helpers/enums/inventory-action-confirmation-type";
import DestroyInformation from "./destroy-information";
import SectionBuilderProps from "../../types/modals/sections/section-builder-props";

export default class SectionBuilder extends React.Component<
    SectionBuilderProps,
    {}
> {
    constructor(props: SectionBuilderProps) {
        super(props);
    }

    render() {
        switch (this.props.type) {
            case InventoryActionConfirmationType.DESTROY_ALL:
                return <DestroyInformation />;
            default:
                return null;
        }
    }
}
