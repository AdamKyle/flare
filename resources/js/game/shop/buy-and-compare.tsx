import React from "react";
import BasicCard from "../components/ui/cards/basic-card";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import PrimaryOutlineButton from "../components/ui/buttons/primary-outline-button";
import ShopAjax, {SHOP_ACTIONS} from "./ajax/shop-ajax";
import {shopServiceContainer} from "./container/shop-container";
import ItemComparison from "../item-comparison/item-comparison";
import BuyAndCompareProps from "./types/buy-and-compare-props";
import BuyAndCompareState from "./types/buy-and-compare-state";

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

        this.ajax.doShopAction(this, SHOP_ACTIONS.COMPARE, {
            item_name: this.props.item.name,
            item_type: this.props.item.type,
        });
    }

    buyAndReplaceItem() {

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

            this.ajax.doShopAction(this, SHOP_ACTIONS.BUY_AND_REPLACE, {
                position: this.state.comparison_data.slotPosition ?? this.state.comparison_data.itemToEquip.type,
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
