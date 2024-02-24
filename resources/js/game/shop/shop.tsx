import React from "react";
import {shopServiceContainer} from "./container/shop-container";
import ShopAjax, {SHOP_ACTIONS} from "./ajax/shop-ajax";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import ShopTableColumns from "./shop-table/colums/shop-table-columns";
import ItemTable from "../sections/items/item-table";
import ItemDefinition from "../sections/items/deffinitions/item-definition";
import BuyMultiple from "./buy-multiple";

export default class Shop extends React.Component<any, any> {

    private ajax: ShopAjax;

    private shopColumns: ShopTableColumns;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            items: [],
            item_to_view: null,
            item_to_buy_many: null,
        }

        this.ajax = shopServiceContainer().fetch(ShopAjax);

        this.shopColumns = shopServiceContainer().fetch(ShopTableColumns).setComponent(this);
    }

    componentDidMount() {

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

    viewBuyMany(item: ItemDefinition) {
        this.setState({
            item_to_buy_many: item,
        })
    }

    render() {
        return (
            <>
                {
                    this.state.loading || this.state.items.length <= 0 ?
                        <LoadingProgressBar />
                    :
                        <>
                            {
                                this.state.item_to_buy_many !== null ?
                                    <BuyMultiple inventory_count={50} inventory_max={75} character_gold={250000}/>
                                :
                                    <ItemTable items={this.state.items}
                                               item_to_view={this.state.item_to_view}
                                               close_view_item_action={this.closeViewItem.bind(this)}
                                               close_view_item_label={'Back to Shop'}
                                               table_columns={
                                                   this.shopColumns.buildColumns(
                                                       this.viewItem.bind(this),
                                                       this.viewBuyMany.bind(this),
                                                       this.state.filter_type
                                                   )
                                               }
                                    />
                            }
                        </>
                }

            </>
        )
    }
}
