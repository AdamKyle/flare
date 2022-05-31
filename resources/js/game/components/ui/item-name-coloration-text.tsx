import clsx from "clsx";
import React from "react";
import ItemNameColorationTextProps from "../../lib/ui/types/item-name-coloration-text-props";

export default class ItemNameColorationText extends React.Component<ItemNameColorationTextProps, any> {

    constructor(props: ItemNameColorationTextProps) {
        super(props);
    }

    render() {
        console.log(this.props.item.is_unique && this.props.item.type !== 'trinket' && !this.props.item.is_mythic);
        return (
            <span className={clsx({
                'text-red-700 dark:text-red-600': this.props.item.type === 'trinket' && !this.props.item.is_mythic
            }, {
                'text-green-700 dark:text-green-600': this.props.item.is_unique && this.props.item.type !== 'trinket' && !this.props.item.is_mythic
            },{
                'text-sky-700 dark:text-sky-300': this.props.item.holy_stacks_applied > 0 && !this.props.item.is_unique && this.props.item.type !== 'trinket' && !this.props.item.is_mythic
            },{
                'text-gray-600 dark:text-white': this.props.item.affix_count === 0 && !this.props.item.is_unique && this.props.item.holy_stacks_applied === 0 && this.props.item.type !== 'trinket' && !this.props.item.is_mythic
            },{
                'text-blue-500': this.props.item.affix_count === 1 && !this.props.item.is_unique && this.props.item.holy_stacks_applied === 0 && this.props.item.type !== 'trinket' && !this.props.item.is_mythic
            },{
                'text-fuchsia-800 dark:text-fuchsia-300': this.props.item.affix_count === 2 && !this.props.item.is_unique && this.props.item.holy_stacks_applied === 0 && this.props.item.type !== 'trinket' && !this.props.item.is_mythic
            }, {
                'text-amber-600 dark:text-amber-500': this.props.item.is_mythic
            })}>{this.props.item.name}</span>
        )
    }
}
