import React from "react";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import PrimaryOutlineButton from "../../../game/components/ui/buttons/primary-outline-button";
import ShopAjax, {SHOP_ACTIONS} from "./ajax/shop-ajax";
import {shopServiceContainer} from "./container/shop-container";
import ItemComparison from "../../../game/components/item-comparison/item-comparison";
import BuyAndCompareProps from "./types/buy-and-compare-props";
import BuyAndCompareState from "./types/buy-and-compare-state";
import DangerAlert from "../../../game/components/ui/alerts/simple-alerts/danger-alert";
import {ItemType} from "../../../game/components/items/enums/item-type";

export default class BuyAndCompare extends React.Component<BuyAndCompareProps, BuyAndCompareState> {

    private ajax: ShopAjax;

    constructor(props: BuyAndCompareProps) {
        super(props);

        this.state = {
            loading: true,
            comparison_data: null,
            error_message: null,
            success_message: null,
            is_showing_expanded_comparison: false,
        }

        this.ajax = shopServiceContainer().fetch(ShopAjax);
    }

    componentDidMount() {

        const weaponTypes = [
            ItemType.WEAPON, ItemType.GUN, ItemType.FAN, ItemType.MACE, ItemType.SCRATCH_AWL,
            ItemType.BOW, ItemType.HAMMER
        ];

        const type = weaponTypes.includes(this.props.item.type) ? ItemType.WEAPON : this.props.item.type;

        this.ajax.doShopAction(this, SHOP_ACTIONS.COMPARE, {
            item_name: this.props.item.name,
            item_type: type,
        });
    }

    buyAndReplaceItem(positionSelected?: string) {

        if (this.state.comparison_data === null) {
            return;
        }

        this.setState({
            error_message: null,
            success_message: null,
        }, () => {
            if (this.state.comparison_data === null) {
                return;
            }

            let position = this.state.comparison_data.slotPosition ?? this.state.comparison_data.itemToEquip.type;

            if (positionSelected) {
                position = positionSelected;
            }

            this.ajax.doShopAction(this, SHOP_ACTIONS.BUY_AND_REPLACE, {
                position: position,
                item_id_to_buy: this.state.comparison_data.itemToEquip.id,
                equip_type: this.state.comparison_data.itemToEquip.type,
                slot_id: this.state.comparison_data.slotId,
            });
        })

    }

    updateIsShowingExpandedLocation() {
        this.setState({
            is_showing_expanded_comparison: !this.state.is_showing_expanded_comparison,
        });
    }

    render() {

        if (this.state.comparison_data === null) {
            return <LoadingProgressBar />
        }

        return (
            <div>
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    : null
                }

                <PrimaryOutlineButton button_label={
                    this.state.is_showing_expanded_comparison ? 'Back to Comparison' : 'Back to shop'
                } on_click={
                    this.state.is_showing_expanded_comparison ? this.updateIsShowingExpandedLocation.bind(this) : this.props.close_view_buy_and_compare
                } />
                {
                    this.state.error_message !== null ?
                        <DangerAlert additional_css='my-4'>
                            {this.state.error_message}
                        </DangerAlert>
                    : null
                }
                <BasicCard additionalClasses={'my-4'}>
                    <ItemComparison comparison_info={this.state.comparison_data}
                                    is_showing_expanded_comparison={this.state.is_showing_expanded_comparison}
                                    manage_show_expanded_comparison={this.updateIsShowingExpandedLocation.bind(this)}
                                    handle_buy_and_replace={this.buyAndReplaceItem.bind(this)}
                    />
                </BasicCard>
            </div>
        )
    }
}
