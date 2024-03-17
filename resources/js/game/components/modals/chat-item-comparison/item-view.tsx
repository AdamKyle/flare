import React from "react";
import ItemViewProps from "./types/item-view-props";
import {ItemType} from "../../items/enums/item-type";
import AlchemyItemHoly from "./item-views/alchemy-item-holy";
import AlchemyItemUsable from "./item-views/alchemy-item-usable";

export default class ItemView extends React.Component<ItemViewProps, {}> {

    constructor(props: ItemViewProps) {
        super(props);
    }

    render() {

        if (this.props.item.type === ItemType.ALCHEMY) {

            if (this.props.item.holy_level !== null) {
                return <AlchemyItemHoly item={this.props.item} />
            }

            return <AlchemyItemUsable item={this.props.item} />
        }

        return (
            'Item View'
        )
    }
}
