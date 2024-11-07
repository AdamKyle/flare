import React from "react";
import { formatNumber } from "../../../../lib/game/format-number";
import InventoryCountprops from "./types/inventory-count-props";

export default class InventoryCount extends React.Component<InventoryCountprops> {
    constructor(props: InventoryCountprops) {
        super(props);
    }

    getInventoryPercentage(): number {
        const maxInventory = this.props.inventory_count.max_inventory;
        const currentCount = this.props.inventory_count.current_count;

        return parseInt(((currentCount / maxInventory) * 100).toFixed(0)) || 0;
    }

    render() {
        return (
            <div className="my-2">
                <div className="flex justify-between mb-1">
                    <span className="font-medium text-sky-700 dark:text-white text-xs">
                        Current Inventory Count
                    </span>
                    <span className="text-xs font-medium text-sky-700 dark:text-white">
                        {formatNumber(this.props.inventory_count.current_count)}
                        /
                        {formatNumber(this.props.inventory_count.max_inventory)}
                    </span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                    <div
                        className="bg-sky-600 h-1.5 rounded-full"
                        style={{ width: this.getInventoryPercentage() + "%" }}
                    ></div>
                </div>
            </div>
        );
    }
}
