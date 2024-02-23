import React, {ChangeEvent} from "react";
import {shopServiceContainer} from "./container/shop-container";
import ShopAjax, {SHOP_ACTIONS} from "./ajax/shop-ajax";
import LoadingProgressBar from "../components/ui/progress-bars/loading-progress-bar";
import BasicCard from "../components/ui/cards/basic-card";
import Table from "../components/ui/data-tables/table";
import {watchForDarkModeChange} from "./helpers/watch-for-dark-mode-change";
import ShopTableColumns from "./shop-table/colums/shop-table-columns";
import Item from "../sections/items/item";
import PrimaryOutlineButton from "../components/ui/buttons/primary-outline-button";
import Select from "react-select";
import {ITEM_TYPES, itemTypeFilter} from "./shop-table/components/build-type-filter-options";
import {ItemType} from "../sections/items/enums/item-type";
import {startCase} from "lodash";
import DangerOutlineButton from "../components/ui/buttons/danger-outline-button";
import ItemDefinition from "../sections/items/deffinitions/item-definition";
import InfoAlert from "../components/ui/alerts/simple-alerts/info-alert";

export default class Shop extends React.Component<any, any> {

    private ajax: ShopAjax;

    private shopColumns: ShopTableColumns;

    private typingTimeOut: any;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            dark_tables: false,
            items: [],
            original_items: [],
            filter_type: undefined,
            item_to_view: null,
            search_term: '',
        }

        this.ajax = shopServiceContainer().fetch(ShopAjax);

        this.shopColumns = shopServiceContainer().fetch(ShopTableColumns);

        this.typingTimeOut = null;
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

    clearFilters() {
        this.setState({
            items: this.state.original_items,
            filter_type: undefined,
            search_term: '',
        })
    }

    setSelectedFilterType(data: any) {
        if (data.value === '') {
            return;
        }

        this.setState({
            filter_type: data.value,
            items: this.state.items.filter((item: ItemDefinition) => item.type === data.value),
        });
    }

    getSelectedFilterValue() {
        const itemType = ITEM_TYPES.filter((itemType: ItemType) => {
            return itemType === this.state.filter_type
        });

        if (itemType.length === 0) {
            return [{
                label: 'Filter by type',
                value: '',
            }];
        }

        return [{
            label: startCase(itemType[0]),
            value: itemType[0],
        }]
    }

    handleSearchInputChange(event: ChangeEvent<HTMLInputElement>) {
        const searchTerm = event.target.value;

        if (this.typingTimeOut) {
            clearTimeout(this.typingTimeOut);
        }

        this.typingTimeOut = setTimeout(() => {
            this.filterItems(searchTerm);
        }, 500);

        this.setState({ search_term: searchTerm });
    };

    filterItems(searchTerm: string) {
        if (searchTerm === '') {

            let items = this.state.original_items;

            if (typeof this.state.filter_type !== 'undefined') {
                items = items.filter((item: ItemDefinition) => item.type === this.state.filter_type);
            }

            return this.setState({
                items: items,
            });
        }

        const filteredItems = this.state.items.filter((item: any) =>
            item.name.toLowerCase().includes(searchTerm.toLowerCase())
        );

        this.setState({ items: filteredItems });
    };

    render() {
        return (
            <>
                {
                    this.state.loading || this.state.original_items.length <= 0 ?
                        <LoadingProgressBar />
                    : null
                }

                {
                    this.state.item_to_view !== null ?
                        <div>
                            <PrimaryOutlineButton button_label={'Back To Shop'} on_click={this.closeViewItem.bind(this)} additional_css={'my-3'} />
                            <BasicCard>
                                <Item item={this.state.item_to_view} />
                            </BasicCard>
                        </div>
                    :
                        <BasicCard additionalClasses={'my-4'}>
                            <InfoAlert additional_css={'my-3'}>
                                <p>Click an item name to learn more info.</p>
                            </InfoAlert>
                            <div className="md:w-3/5 sm:w-full my-4">
                                <div className="grid md:grid-cols-3 gap-4 my-4">
                                    <div className="flex items-center">
                                        <div className="mr-2">Search:</div>
                                        <input type="text" className="w-full h-9 text-gray-800 dark:text-white border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-gray-200 dark:bg-gray-700 px-4" value={this.state.search_term} onChange={this.handleSearchInputChange.bind(this)}/>
                                    </div>
                                    <div>
                                        <Select
                                            onChange={this.setSelectedFilterType.bind(this)}
                                            options={itemTypeFilter()}
                                            menuPosition={'absolute'}
                                            menuPlacement={'bottom'}
                                            styles={{menuPortal: (base) => ({...base, zIndex: 9999, color: '#000000'})}}
                                            menuPortalTarget={document.body}
                                            value={this.getSelectedFilterValue()}
                                        />
                                    </div>
                                    <div>
                                        <DangerOutlineButton button_label={'Clear Filters'} on_click={this.clearFilters.bind(this)} />
                                    </div>
                                </div>
                            </div>
                            <Table columns={this.shopColumns.buildColumns(this.viewItem.bind(this), this.state.filter_type)} data={this.state.items} dark_table={this.state.dark_tables} />
                        </BasicCard>
                }

            </>
        )
    }
}
