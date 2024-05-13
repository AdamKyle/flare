import React from "react";
import InventoryActionConfirmationModalTypeProps from "../types/modals/inventory-action-confirmation-modal-type-props";
import { InventoryActionConfirmationType } from "../helpers/enums/inventory-action-confirmation-type";
import BaseInventoryActionConfirmationModal from "./base-inventory-action-confirmation-modal";

export default class InventoryActionConfirmationModalType extends React.Component<
    InventoryActionConfirmationModalTypeProps,
    any
> {
    constructor(props: InventoryActionConfirmationModalTypeProps) {
        super(props);
    }

    render() {
        switch (this.props.type) {
            case InventoryActionConfirmationType.DISENCHANT_ALL:
                return (
                    <BaseInventoryActionConfirmationModal
                        type={InventoryActionConfirmationType.DESTROY_ALL}
                        is_open={this.props.is_open}
                        manage_modal={this.props.manage_modal}
                        title={"Destroy All"}
                        update_inventory={this.props.update_inventory}
                        set_success_message={this.props.set_success_message}
                        data={this.props.data}
                        selected_item_names={this.props.selected_item_names}
                    />
                );
            default:
                return null;
        }
    }
}
