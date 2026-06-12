import React from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import InventoryCountBreakdownModalProps from "./types/inventory-count-breakdown-modal-props";

export default class InventoryCountBreakdownModal extends React.Component<InventoryCountBreakdownModalProps> {
    constructor(props: InventoryCountBreakdownModalProps) {
        super(props);

        this.state = {
            loading: false,
            error_message: null,
        };
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={this.props.title}
            >
                <p className="my-4">
                    Inventory counts are separated by bag type. Your main
                    inventory does not count quest items, alchemy items, or
                    gems. Each bag has its own limit based on total quantity.
                </p>
                <dl>
                    <dt>
                        Inventory:{" "}
                        {this.props.inventory_breakdown.inventory_count} /{" "}
                        {this.props.inventory_breakdown.inventory_max}
                    </dt>
                    <dt>
                        Alchemy Bag:{" "}
                        {this.props.inventory_breakdown.alchemy_bag_count} /{" "}
                        {this.props.inventory_breakdown.alchemy_bag_limit}
                    </dt>
                    <dt>
                        Gem Bag: {this.props.inventory_breakdown.gem_bag_count}{" "}
                        / {this.props.inventory_breakdown.gem_bag_limit}
                    </dt>
                    <dt>Quest Items: not counted</dt>
                </dl>
            </Dialogue>
        );
    }
}
