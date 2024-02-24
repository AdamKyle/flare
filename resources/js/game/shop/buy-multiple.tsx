import React, {ChangeEvent} from "react";
import SuccessButton from "../components/ui/buttons/success-button";
import SuccessAlert from "../components/ui/alerts/simple-alerts/success-alert";
import ShopAjax, {SHOP_ACTIONS} from "./ajax/shop-ajax";
import {shopServiceContainer} from "./container/shop-container";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../components/ui/alerts/simple-alerts/danger-alert";

export default class BuyMultiple extends React.Component<any, any> {

    private typingTimeOut: any;

    private ajax: ShopAjax;

    constructor(props: any) {
        super(props);

        this.typingTimeOut = null;

        this.state = {
            loading: false,
            success_message: null,
            error_message: null,
            amount: 1,
        }

        this.ajax = shopServiceContainer().fetch(ShopAjax);
    }

    handleSettingAmount(event: ChangeEvent<HTMLInputElement>) {

        this.setState({
            error_message: null,
            success_message: null,
        })

        const amount = parseInt(event.target.value) || 0;

        const cost = amount * this.props.cost;

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
            error_message: null
        }, () => {
            this.ajax.doShopAction(this, SHOP_ACTIONS.BUY_MANY, {
                amount: this.state.amount,
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
            </>
        )
    }

}
