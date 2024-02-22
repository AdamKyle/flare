import React from "react";
import {shopServiceContainer} from "./container/shop-container";
import ShopAjax, {SHOP_ACTIONS} from "./ajax/shop-ajax";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import BasicCard from "../components/ui/cards/basic-card";
import Table from "../components/ui/data-tables/table";
import {watchForDarkModeChange} from "./helpers/watch-for-dark-mode-change";
import ShopTableColumns from "./shop-table/colums/shop-table-columns";
import Item from "../sections/items/item";
import DangerButton from "../components/ui/buttons/danger-button";
import PrimaryOutlineButton from "../components/ui/buttons/primary-outline-button";

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
            item_to_view: null,
        }

        this.ajax = shopServiceContainer().fetch(ShopAjax);

        this.shopColumns = shopServiceContainer().fetch(ShopTableColumns);
    }

    componentDidMount() {
        watchForDarkModeChange(this);

        this.ajax.doShopAction(this, SHOP_ACTIONS.FETCH);
    }

    viewItem(itemId: number) {
        this.setState({
            item_to_view: this.state.items.filter((item: any) => item.id === itemId)[0],
        });
    }

    closeViewItem() {
        this.setState({
            item_to_view: null,
        })
    }

    render() {
        return (
            <>
                {
                    this.state.loading || this.state.items.length <= 0 ?
                        <LoadingProgressBar />
                    : null
                }

                {
                    this.state.item_to_view !== null ?
                        <div>
                            <div className='max-w-[75%] ml-auto mr-auto'>
                                <PrimaryOutlineButton button_label={'Back To Shop'} on_click={this.closeViewItem.bind(this)} additional_css={'my-3'} />
                            </div>
                            <BasicCard additionalClasses={'max-w-[75%] mr-auto ml-auto'}>
                                <Item item={this.state.item_to_view} />
                            </BasicCard>
                        </div>
                    :
                        <BasicCard additionalClasses={'my-4'}>
                            <Table columns={this.shopColumns.buildColumns(this.viewItem.bind(this), this.state.filter_type)} data={this.state.items} dark_table={this.state.dark_tables} />
                        </BasicCard>
                }

            </>
        )
    }
}
