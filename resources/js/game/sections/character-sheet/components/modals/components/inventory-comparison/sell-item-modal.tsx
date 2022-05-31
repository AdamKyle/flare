import React from "react";
import Dialogue from "../../../../../../components/ui/dialogue/dialogue";
import ItemNameColorationText from "../../../../../../components/ui/item-name-coloration-text";
import SellModalProps from "../../../../../../lib/game/character-sheet/types/modal/sell-modal-props";
import {formatNumber} from "../../../../../../lib/game/format-number";

export default class SellItemModal extends React.Component<SellModalProps, any> {

    constructor(props: SellModalProps) {
        super(props);
    }

    sellItem() {
        this.props.sell_item();

        this.props.manage_modal();
    }

    getCost() {
        return formatNumber(this.props.item.cost)
    }

    render() {
        return (
            <Dialogue is_open={this.props.is_open}
                      handle_close={this.props.manage_modal}
                      title={'Sell item'}
                      secondary_actions={{
                          secondary_button_disabled: false,
                          secondary_button_label: 'sell',
                          handle_action: this.sellItem.bind(this)
                      }}
            >
                <h3 className='mb-3'>
                    Sell <ItemNameColorationText item={{
                        name: this.props.item.affix_name,
                        type: this.props.item.type,
                        affix_count: this.props.item.affix_count,
                        is_unique: this.props.item.is_unique,
                        is_mythic: this.props.item.is_mythic,
                        holy_stacks_applied: this.props.item.holy_stacks_applied,
                }} />
                </h3>

                <p className='mb-4 mt-4 text-orange-700 dark:text-orange-500'>Item will sell for: {this.getCost()} gold, after 5% tax (rounded down).</p>

                <p className='mb-4 mt-4'><strong>Note</strong>: This will not take into account prices for Holy Items and Uniques.
                    In those cases you only get the base item cost, even in the case of holy items, if there are affixes attached.
                    These items are best sold on the market to make your gold invested and time invested worth it.
                </p>
                <p className='mb-4'>
                    Finally, if the either affix cost attached the item is greater then 2 Billion gold, you will only get 2 billion gold for that affix.
                    For example, on a 10 gold item that has two 12 billion enchants on it, you would only make 4,000,000,010 gold before taxes.
                </p>
            </Dialogue>
        );
    }
}
