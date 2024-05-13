import React from "react";
import BaseInventoryActionConfirmationModalProps from "../types/modals/base-inventory-action-confirmation-modal-props";
import InventoryActionConfirmationModal from "../../../../sections/character-sheet/components/modals/inventory-action-confirmation-modal";
import SectionBuilder from "./sections/section-builder";

export default class BaseInventoryActionConfirmationModal extends React.Component<
    BaseInventoryActionConfirmationModalProps,
    {}
> {
    constructor(props: BaseInventoryActionConfirmationModalProps) {
        super(props);
    }

    render() {
        return (
            <InventoryActionConfirmationModal
                is_open={this.props.is_open}
                manage_modal={this.props.manage_modal}
                title={this.props.title}
                url={this.props.data.url}
                ajax_params={this.props.data.params}
                update_inventory={this.props.update_inventory}
                set_success_message={this.props.set_success_message}
            >
                <SectionBuilder
                    type={this.props.type}
                    item_names={this.props.selected_item_names}
                />
            </InventoryActionConfirmationModal>
        );
    }
}
