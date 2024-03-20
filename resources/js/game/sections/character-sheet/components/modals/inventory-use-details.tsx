import React, { Fragment } from "react";
import Dialogue from "../../../../components/ui/dialogue/dialogue";
import AlchemyItemHoly from "../../../../components/modals/item-details/item-views/alchemy-item-holy";
import AlchemyItemUsable from "../../../../components/modals/item-details/item-views/alchemy-item-usable";

export default class InventoryUseDetails extends React.Component<any, any> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <Dialogue
                is_open={this.props.is_open}
                handle_close={this.props.manage_modal}
                title={
                    <span className="text-pink-500 dark:text-pink-300">
                        {this.props.item.item_name}
                    </span>
                }
            >
                <div className="mb-5">
                    {this.props.item.usable ||
                    this.props.item.damages_kingdoms ? (
                        <AlchemyItemUsable item={this.props.item} />
                    ) : (
                        <AlchemyItemHoly item={this.props.item} />
                    )}
                </div>
            </Dialogue>
        );
    }
}
