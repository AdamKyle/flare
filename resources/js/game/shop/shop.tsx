import React from "react";
import {shopServiceContainer} from "./container/shop-container";
import ShopAjax, {SHOP_ACTIONS} from "./ajax/shop-ajax";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import BasicCard from "../components/ui/cards/basic-card";
import Table from "../components/ui/data-tables/table";
import {watchForDarkModeChange} from "./helpers/watch-for-dark-mode-change";
import ShopTableColumns from "./shop-table/colums/shop-table-columns";

export default class Shop extends React.Component<any, any> {

    private ajax: ShopAjax;

    private shopColumns: ShopTableColumns;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            dark_tables: false,
            items: [],
            filter_type: undefined,
        }

        this.ajax = shopServiceContainer().fetch(ShopAjax);

        this.shopColumns = shopServiceContainer().fetch(ShopTableColumns);
    }

    componentDidMount() {
        watchForDarkModeChange(this);

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
                   <Table columns={this.shopColumns.buildColumns(this.state.filter_type)} data={this.state.items} dark_table={this.state.dark_tables} />
                </BasicCard>
            </>
        )
    }
}
