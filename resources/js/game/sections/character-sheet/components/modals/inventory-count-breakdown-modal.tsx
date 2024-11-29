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
                    When it coems to inventory count, we do not count equipped
                    items nor do we count items you have stashed away in{" "}
                    <a href="/information/equipment-sets" target="_blank">
                        sets <i className="fas fa-external-link-alt"></i>
                    </a>
                    . Below you will see a breakdown of your various bags, which
                    includes your core inventory, that is things not in a set,
                    not equipped and not alchemy or quest items, then we show
                    you your usable items bag and finally your gem bag count.
                    Add it all together and thats your current inventory count.
                </p>
                <dl>
                    <dt>Inventory bag count (non set, non equipped):</dt>
                    <dd>
                        {this.props.inventory_breakdown.inventory_bag_count}
                    </dd>
                    <dt>Usable item count:</dt>
                    <dd>{this.props.inventory_breakdown.alchemy_item_count}</dd>
                    <dt>Gem count:</dt>
                    <dd>{this.props.inventory_breakdown.gem_bag_count}</dd>
                </dl>
            </Dialogue>
        );
    }
}
