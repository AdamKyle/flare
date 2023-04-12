import clsx from "clsx";
import React from "react";
import ItemNameColorationTextProps from "../../lib/ui/types/item-name-coloration-text-props";

export default class ItemNameColorationText extends React.Component<ItemNameColorationTextProps, { }> {

    constructor(props: ItemNameColorationTextProps) {
        super(props);
    }

    getColorClass() {
      switch(this.props.item.type) {
          case 'alchemy':
              return 'text-pink-500 dark:text-pink-300';
          case 'quest':
              return 'text-orange-400 dark:text-orange-300';
          case 'trinket':
              return 'text-red-700 dark:text-red-400';
          default:
              return this.getColorClassFromType();

      }
    }

    getColorClassFromType() {
        const item = this.props.item;

        if (item.is_mythic) {
            return 'text-amber-600 dark:text-amber-500'
        }

        if (item.is_unique) {
            return 'text-green-700 dark:text-green-600';
        }

        if (item.holy_stacks_applied > 0) {
            return 'text-sky-700 dark:text-sky-300';
        }

        if (item.affix_count === 1) {
            return 'text-blue-500';
        }

        if (item.affix_count == 2) {
            return 'text-fuchsia-800 dark:text-fuchsia-300';
        }

        return 'text-gray-600 dark:text-white';
    }

    render() {
        return (
            <span className={this.getColorClass() + ' max-w-[75%] sm:max-w-full'}>{this.props.item.name}</span>
        )
    }
}
