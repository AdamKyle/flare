import clsx from "clsx";
import React from "react";
import ItemNameColorationProps from "../../lib/ui/types/item-name-coloration-props";
import InventoryDetails from "../../lib/game/character-sheet/types/inventory/inventory-details";
import ItemNameColorationTextProps from "../../lib/ui/types/item-name-coloration-text-props";

export default class ItemNameColorationText extends React.Component<ItemNameColorationTextProps, any> {

    constructor(props: ItemNameColorationTextProps) {
        super(props);
    }

    render() {
        return (
            <span className={clsx({
                'text-red-700 dark:text-red-600': this.props.item.type === 'trinket'
            }, {
                'text-green-700 dark:text-green-600': this.props.item.is_unique && this.props.item.type !== 'trinket'
            },{
                'text-sky-700 dark:text-sky-300': this.props.item.holy_stacks_applied > 0 && !this.props.item.is_unique && this.props.item.type !== 'trinket'
            },{
                'text-gray-600': this.props.item.affix_count === 0 && !this.props.item.is_unique && this.props.item.holy_stacks_applied === 0 && this.props.item.type !== 'trinket'
            },{
                'text-blue-500': this.props.item.affix_count === 1 && !this.props.item.is_unique && this.props.item.holy_stacks_applied === 0 && this.props.item.type !== 'trinket'
            },{
                'text-fuchsia-800 dark:text-fuchsia-300': this.props.item.affix_count === 2 && !this.props.item.is_unique && this.props.item.holy_stacks_applied === 0 && this.props.item.type !== 'trinket'
            })}>{this.props.item.name}</span>
        )
    }
}
