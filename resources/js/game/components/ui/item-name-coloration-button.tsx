import clsx from "clsx";
import React from "react";
import ItemNameColorationProps from "../../lib/ui/types/item-name-coloration-props";
import InventoryDetails from "../../lib/game/character-sheet/types/inventory/inventory-details";

export default class ItemNameColorationButton extends React.Component<ItemNameColorationProps, any> {

    constructor(props: ItemNameColorationProps) {
        super(props);
    }

    viewItem() {
        if (typeof this.props.on_click !== 'undefined') {
            return this.props.on_click(this.props.item);
        }

        return;
    }

    render() {
        return (
            <button className={clsx({
                'text-red-700 dark:text-red-600': this.props.item.type === 'trinket'
            }, {
                'text-green-700 dark:text-green-600': this.props.item.is_unique && this.props.item.type !== 'trinket'
            },{
                'text-sky-700 dark:text-sky-300': this.props.item.has_holy_stacks_applied > 0 && !this.props.item.is_unique && this.props.item.type !== 'trinket'
            },{
                'text-orange-400 dark:text-orange-300': this.props.item.type === 'quest' && !this.props.item.is_unique
            },{
                'text-pink-500 dark:text-pink-300': this.props.item.type === 'alchemy' && !this.props.item.is_unique
            },{
                'text-gray-600': this.props.item.attached_affixes_count === 0 && !this.props.item.is_unique && this.props.item.type !== 'alchemy' && this.props.item.type !== 'quest' && this.props.item.has_holy_stacks_applied === 0 && this.props.item.type !== 'trinket'
            },{
                'text-blue-500': this.props.item.attached_affixes_count === 1 && !this.props.item.is_unique && this.props.item.type !== 'alchemy' && this.props.item.type !== 'quest' && this.props.item.has_holy_stacks_applied === 0 && this.props.item.type !== 'trinket'
            },{
                'text-fuchsia-800 dark:text-fuchsia-300': this.props.item.attached_affixes_count === 2 && !this.props.item.is_unique && this.props.item.type !== 'alchemy' && this.props.item.type !== 'quest' && this.props.item.has_holy_stacks_applied === 0 && this.props.item.type !== 'trinket'
            })} onClick={() => this.viewItem()}>{this.props.item.item_name}</button>
        )
    }
}
