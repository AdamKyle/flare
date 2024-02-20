import React from "react";
import {shopServiceContainer} from "./container/shop-container";
import ShopAjax, {SHOP_ACTIONS} from "./ajax/shop-ajax";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import BasicCard from "../components/ui/cards/basic-card";

export default class Shop extends React.Component<any, any> {

    private ajax: ShopAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            items: [],
        }

        this.ajax = shopServiceContainer().fetch(ShopAjax);
    }

    componentDidMount() {
        this.ajax.doShopAction(this, SHOP_ACTIONS.FETCH);
    }

    render() {
        return (
            <>
                {
                    this.state.loading || this.state.items.length <= 0 ?
                        <LoadingProgressBar />
                    : null
                }
                <BasicCard additionalClasses={'my-4'}>
                    Shop Data Here ...
                </BasicCard>
            </>
        )
    }
}
