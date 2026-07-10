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
                <dl className="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                    <dt className="font-semibold">Inventory</dt>
                    <dd>
                        {this.props.inventory_breakdown.inventory_count} /{" "}
                        {this.props.inventory_breakdown.inventory_max}
                    </dd>
                    <dt className="font-semibold">Alchemy Bag</dt>
                    <dd>
                        {this.props.inventory_breakdown.alchemy_bag_count} /{" "}
                        {this.props.inventory_breakdown.alchemy_bag_limit}
                    </dd>
                    <dt className="font-semibold">Gem Bag</dt>
                    <dd>
                        {this.props.inventory_breakdown.gem_bag_count} /{" "}
                        {this.props.inventory_breakdown.gem_bag_limit}
                    </dd>
                    <dt className="font-semibold">Crafted Items Set</dt>
                    <dd>
                        {this.props.inventory_breakdown.crafted_items_set_count}{" "}
                        / {this.props.inventory_breakdown.crafted_items_set_max}
                    </dd>
                    <dt className="font-semibold">Quest Items</dt>
                    <dd>not counted</dd>
                </dl>
            </Dialogue>
        );
    }
}
