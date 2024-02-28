import React, {ChangeEvent} from "react";
import SuccessButton from "../../../game/components/ui/buttons/success-button";
import SuccessAlert from "../../../game/components/ui/alerts/simple-alerts/success-alert";
import ShopAjax, {SHOP_ACTIONS} from "./ajax/shop-ajax";
import {shopServiceContainer} from "./container/shop-container";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../game/components/ui/alerts/simple-alerts/danger-alert";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import InfoAlert from "../../../game/components/ui/alerts/simple-alerts/info-alert";
import {formatNumber} from "../../../game/lib/game/format-number";
import PrimaryOutlineButton from "../../../game/components/ui/buttons/primary-outline-button";
import {BuyMultipleState} from "./types/buy-multiple-state";
import BuyMultipleProps from "./types/buy-multiple-props";

export default class BuyMultiple extends React.Component<BuyMultipleProps, BuyMultipleState> {

    private ajax: ShopAjax;

    constructor(props: BuyMultipleProps) {
        super(props);

        this.state = {
            loading: false,
            success_message: null,
            error_message: null,
            amount: 1,
            cost: 0,
        }

        this.ajax = shopServiceContainer().fetch(ShopAjax);
    }

    componentDidMount() {
        this.setState({
            cost: this.props.item.cost,
        })
    }

    handleSettingAmount(event: ChangeEvent<HTMLInputElement>) {

        this.setState({
            error_message: null,
            success_message: null,
        })

        const amount = parseInt(event.target.value) || 0;

        let cost = amount * this.props.item.cost;

        if (this.props.is_merchant) {
            cost = cost - cost * 0.25;
        }

        if (cost > this.props.character_gold) {
            return this.setState({
                amount: amount,
                error_message: 'You cannot afford this many.'
            });
        }

        const newInventoryCount = amount + this.props.inventory_count;

        if (newInventoryCount > this.props.inventory_max) {
            return this.setState({
                amount: amount,
                error_message: 'You cannot fit this many in your bag.'
            });
        }

        this.setState({
            cost: cost,
            amount: amount,
        })
    };

    purchase()  {
        this.setState({
            success_message: null,
            error_message: null,
        }, () => {
            this.ajax.doShopAction(this, SHOP_ACTIONS.BUY_MANY, {
                item_id: this.props.item.id,
                amount: this.state.amount,
            });

            this.setState({
                cost: this.props.item.cost * 1,
                amount: 1,
            })
        })
    }

    render() {
        return (
            <>
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    : null
                }
                {
                    this.state.success_message !== null ?
                        <SuccessAlert additional_css={'my-4'}>
                            {this.state.success_message}
                        </SuccessAlert>
                    : null
                }
                {
                    this.state.error_message !== null ?
                        <DangerAlert additional_css={'my-4'}>
                            {this.state.error_message}
                        </DangerAlert>
                        : null
                }
                {
                    this.props.is_merchant ?
                        <InfoAlert additional_css={'my-4'}>
                            As a merchant you will receive a 25% discount on purchasing multiple items.
                        </InfoAlert>
                    : null
                }
                <PrimaryOutlineButton button_label={'Back to shop'} on_click={this.props.close_view_buy_many} />
                <BasicCard additionalClasses={'my-4'}>
                    <div>
                        <h3>Purchase multiple of: {this.props.item.name}</h3>
                    </div>
                    <div className="md:w-3/5 w-full my-4">
                        <div className="grid md:grid-cols-2 gap-4 my-4">
                            <div className="flex items-center">
                                <div className="mr-2">Amount:</div>
                                <input type="number" className="w-full h-9 text-gray-800 dark:text-white border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-gray-200 dark:bg-gray-700 px-4" value={this.state.amount} onChange={this.handleSettingAmount.bind(this)}/>
                            </div>
                            <div>
                                <SuccessButton button_label={'Purchase'} on_click={this.purchase.bind(this)} />
                            </div>
                        </div>
                    </div>
                    <div>
                        <strong>Cost:</strong> {formatNumber(this.state.cost)} Gold <strong>For Amount:</strong> {formatNumber(this.state.amount)}
                    </div>
                </BasicCard>
            </>
        )
    }

}
