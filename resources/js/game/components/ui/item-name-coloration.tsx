import clsx from "clsx";
import React from "react";
import ItemNameColorationProps from "../../lib/ui/types/item-name-coloration-props";

export default class ItemNameColoration extends React.Component<ItemNameColorationProps, any> {

    constructor(props: ItemNameColorationProps) {
        super(props);
    }

    render() {
        return (
            <span className={clsx( {
                'text-green-700 dark:text-green-600': this.props.item.is_unique
            },{
                'text-sky-700 dark:text-sky-300': this.props.item.has_holy_stacks_applied > 0 && !this.props.item.is_unique,
            },{
                'text-orange-400 dark:text-orange-300': this.props.item.type === 'quest' && !this.props.item.is_unique
            },{
                'text-pink-500 dark:text-pink-300': this.props.item.type === 'alchemy' && !this.props.item.is_unique
            },{
                'text-gray-600': this.props.item.attached_affixes_count === 0 && !this.props.item.is_unique && this.props.item.type !== 'alchemy' && this.props.item.type !== 'quest' && this.props.item.has_holy_stacks_applied === 0
            },{
                'text-blue-500': this.props.item.attached_affixes_count === 1 && !this.props.item.is_unique && this.props.item.type !== 'alchemy' && this.props.item.type !== 'quest' && this.props.item.has_holy_stacks_applied === 0
            },{
                'text-fuchsia-800 dark:text-fuchsia-300': this.props.item.attached_affixes_count === 2 && !this.props.item.is_unique && this.props.item.type !== 'alchemy' && this.props.item.type !== 'quest' && this.props.item.has_holy_stacks_applied === 0
            })}>{this.props.item.item_name}</span>
        )
    }
}
