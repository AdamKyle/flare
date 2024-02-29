import React from "react";
import {shopServiceContainer} from "./container/shop-container";
import ShopAjax, {SHOP_ACTIONS} from "./ajax/shop-ajax";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import ShopTableColumns from "./shop-table/colums/shop-table-columns";
import ItemTable from "../../../game/components/items/item-table";
import ItemDefinition from "../../../game/components/items/deffinitions/item-definition";
import BuyMultiple from "./buy-multiple";
import DangerAlert from "../../../game/components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../game/components/ui/alerts/simple-alerts/success-alert";
import BuyAndCompare from "./buy-and-compare";
import ShopProps from "./types/shop-props";
import ShopState from "./types/shop-state";
import BasicCard from "../../../game/components/ui/cards/basic-card";
import {formatNumber} from "../../../game/lib/game/format-number";
import ShopListenerDefinition from "./event-listeners/shop-listener-definition";
import ShopListener from "./event-listeners/shop-listener";

export default class Shop extends React.Component<ShopProps, ShopState> {

    private ajax: ShopAjax;

    private shopColumns: ShopTableColumns;

    private shopListener: ShopListenerDefinition;

    constructor(props: ShopProps) {
        super(props);

        this.state = {
            loading: true,
            success_message: null,
            error_message: null,
            items: [],
            item_to_view: null,
            item_to_buy_many: null,
            item_to_compare: null,
            gold: 0,
            inventory_count: 0,
            inventory_max: 0,
            is_merchant: false,
        }

        this.ajax = shopServiceContainer().fetch(ShopAjax);

        this.shopColumns = shopServiceContainer().fetch(ShopTableColumns).setComponent(this);

        this.shopListener = shopServiceContainer().fetch<ShopListenerDefinition>(ShopListener);

        this.shopListener.initialize(this, this.props.user_id);

        this.shopListener.register();
    }

    componentDidMount() {

        this.ajax.doShopAction(this, SHOP_ACTIONS.FETCH);

        this.shopListener.listen();
    }

    viewItem(itemId: number) {
        this.setState({
            item_to_view: this.state.items.filter((item: any) => item.id === itemId)[0],
        });
    }

    closeViewSection() {
        this.setState({
            item_to_view: null,
            item_to_buy_many: null,
            item_to_compare: null,
        })
    }

    viewBuyMany(item: ItemDefinition) {
        this.setState({
            item_to_buy_many: item,
        })
    }

    viewComparison(item: ItemDefinition) {
        this.setState({
            item_to_compare: item,
        })
    }

    setSuccessMessage(message: string | null) {
        this.setState({
            success_message: message,
        });
    }

    render() {

        if (this.state.items.length === 0) {
            return <LoadingProgressBar />
        }

        return (
            <>
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    : null
                }
                <BasicCard additionalClasses={'my-4'}>
                    <div>
                        <strong>Your Gold:</strong> {formatNumber(this.state.gold)}
                    </div>
                    <div className='my-3'>
                        <strong>Current inventory space:</strong> {formatNumber(this.state.inventory_count)}/{formatNumber(this.state.inventory_max)}
                    </div>
                </BasicCard>
                {
                    this.state.item_to_compare !== null ?
                        <BuyAndCompare character_id={this.props.character_id}
                                       item={this.state.item_to_compare}
                                       close_view_buy_and_compare={this.closeViewSection.bind(this)}
                                       set_success_message={this.setSuccessMessage.bind(this)}
                        />
                    :
                        this.state.item_to_buy_many !== null ?
                            <BuyMultiple
                                character_id={this.props.character_id}
                                close_view_buy_many={this.closeViewSection.bind(this)}
                                inventory_count={this.state.inventory_count}
                                inventory_max={this.state.inventory_max}
                                character_gold={this.state.gold}
                                is_merchant={this.state.is_merchant}
                                item={this.state.item_to_buy_many} />
                        :
                            <div>
                                {
                                    this.state.error_message !== null ?
                                        <DangerAlert additional_css={'my-4'}>
                                            {this.state.error_message}
                                        </DangerAlert>
                                        : null
                                }

                                {
                                    this.state.success_message !== null ?
                                        <SuccessAlert additional_css={'my-4'}>
                                            {this.state.success_message}
                                        </SuccessAlert>
                                        : null
                                }
                                <ItemTable items={this.state.items}
                                           item_to_view={this.state.item_to_view}
                                           close_view_item_action={this.closeViewSection.bind(this)}
                                           close_view_item_label={'Back to Shop'}
                                           table_columns={
                                               this.shopColumns.buildColumns(
                                                   this.viewItem.bind(this),
                                                   this.viewBuyMany.bind(this),
                                                   this.viewComparison.bind(this),
                                               )
                                           }
                                />
                            </div>
                }
            </>
        )
    }
}
