import React from "react";
import { InventoryActionConfirmationType } from "../../helpers/enums/inventory-action-confirmation-type";
import DestroyInformation from "./destroy-information";
import SectionBuilderProps from "../../types/modals/sections/section-builder-props";
import DestroySelectedInformation from "./destroy-selected-information";

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
            case InventoryActionConfirmationType.DESTROY_SELECTED:
                return (
                    <DestroySelectedInformation
                        item_names={
                            this.props.item_names ? this.props.item_names : []
                        }
                    />
                );
            default:
                return null;
        }
    }
}
