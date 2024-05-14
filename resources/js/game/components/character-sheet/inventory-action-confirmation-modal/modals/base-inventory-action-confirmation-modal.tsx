import React from "react";
import BaseInventoryActionConfirmationModalProps from "../types/modals/base-inventory-action-confirmation-modal-props";
import InventoryActionConfirmationModal from "../../../../sections/character-sheet/components/modals/inventory-action-confirmation-modal";
import SectionBuilder from "./sections/section-builder";
import { InventoryActionConfirmationType } from "../helpers/enums/inventory-action-confirmation-type";

export default class BaseInventoryActionConfirmationModal extends React.Component<
    BaseInventoryActionConfirmationModalProps,
    any
> {
    constructor(props: BaseInventoryActionConfirmationModalProps) {
        super(props);

        this.state = {
            params: props.data.params,
        };
    }

    componentDidMount() {
        console.log("props", this.props);
    }

    updateParams(params: any) {
        this.setState({
            params: { ...this.state.params, ...params },
        });
    }

    render() {
        return (
            <InventoryActionConfirmationModal
                is_open={this.props.is_open}
                manage_modal={this.props.manage_modal}
                title={this.props.title}
                url={this.props.data.url}
                ajax_params={this.state.params}
                update_inventory={this.props.update_inventory}
                set_success_message={this.props.set_success_message}
                is_large_modal={
                    this.props.type ===
                    InventoryActionConfirmationType.MOVE_SELECTED
                }
            >
                <SectionBuilder
                    type={this.props.type}
                    item_names={this.props.selected_item_names}
                    usable_sets={this.props.usable_sets}
                    update_api_params={this.updateParams.bind(this)}
                />
            </InventoryActionConfirmationModal>
        );
    }
}
