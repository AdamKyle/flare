import React from "react";
import ChatItemComparisonModalTitleProps from "./types/chat-item-comparison-modal-title-props";
import ItemNameColorationText from "../../items/item-name/item-name-coloration-text";
import {capitalize} from "lodash";
import {ItemType} from "../../items/enums/item-type";
import ChatItemComparisonAjax from "./ajax/chat-item-comparison-ajax";
import {serviceContainer} from "../../../lib/containers/core-container";

export default class ChatItemComparisonModalTitle extends React.Component<ChatItemComparisonModalTitleProps, {  }> {

    constructor(props: ChatItemComparisonModalTitleProps) {
        super(props);
    }

    buildItemDetailsForTitle() {
        return {
            name: this.props.itemToEquip.affix_name,
            type: this.props.itemToEquip.type,
            affix_count: this.props.itemToEquip.affix_count,
            is_unique: this.props.itemToEquip.is_unique,
            is_mythic: this.props.itemToEquip.is_mythic,
            holy_stacks_applied: this.props.itemToEquip.holy_stacks_applied,
        }
    }

    return () {
        return (
            <div className="grid grid-cols-2 gap-2">
                {this.props.itemToEquip.type === ItemType.GEM ? (
                    <span className="text-lime-600 dark:text-lime-500">
                        {this.props.itemToEquip.item.gem.name}
                    </span>
                ) : (
                    <ItemNameColorationText
                        custom_width={false}
                        item={this.buildItemDetailsForTitle()}
                    />
                )}

                <div className="absolute right-[-30px] md:right-0">
                    <span className="pl-3 text-right mr-[70px]">
                        (Type:{" "}
                        {capitalize(
                            this.props.itemToEquip.type
                        )
                            .split("-")
                            .join(" ")}
                        )
                    </span>
                </div>
            </div>
        )
    }

}
