import React, { ChangeEvent } from "react";
import PrimaryOutlineButton from "../ui/buttons/primary-outline-button";
import BasicCard from "../ui/cards/basic-card";
import Item from "./item";
import InfoAlert from "../ui/alerts/simple-alerts/info-alert";
import Select from "react-select";
import {
    ITEM_TYPES,
    itemTypeFilter,
} from "../../../individual-components/player-components/shop/shop-table/components/build-type-filter-options";
import DangerOutlineButton from "../ui/buttons/danger-outline-button";
import Table from "../ui/data-tables/table";
import ItemDefinition from "./deffinitions/item-definition";
import { ItemType } from "./enums/item-type";
import { startCase } from "lodash";
import ItemTableProps from "./types/item-table-props";
import { watchForDarkModeChange } from "./helpers/watch-for-dark-mode-change";

export default class ItemTable extends React.Component<ItemTableProps, any> {
    private typingTimeOut: any;

    constructor(props: ItemTableProps) {
        super(props);

        this.state = {
            items: [],
            dark_tables: false,
            filter_type: undefined,
            search_term: "",
        };

        this.typingTimeOut = null;
    }

    componentDidMount() {
        watchForDarkModeChange(this);

        this.setState({
            items: this.props.items,
        });
    }

    clearFilters() {
        this.setState({
            items: this.props.items,
            filter_type: undefined,
            search_term: "",
        });
    }

    setSelectedFilterType(data: any) {
        if (data.value === "") {
            return;
        }

        let filteredItems = this.props.items.filter(
            (item: ItemDefinition) => item.type === data.value,
        );

        if (this.state.search_term !== "") {
            filteredItems = filteredItems.filter((item: ItemDefinition) => {
                return item.name
                    .toLowerCase()
                    .includes(this.state.search_term.toLowerCase());
            });
        }

        this.setState({
            filter_type: data.value,
            items: filteredItems,
        });
    }

    getSelectedFilterValue() {
        const itemType = ITEM_TYPES.filter((itemType: ItemType) => {
            return itemType === this.state.filter_type;
        });

        if (itemType.length === 0) {
            return [
                {
                    label: "Filter by type",
                    value: "",
                },
            ];
        }

        return [
            {
                label: startCase(itemType[0]),
                value: itemType[0],
            },
        ];
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
    }

    filterItems(searchTerm: string) {
        if (searchTerm === "") {
            let items = this.props.items;

            if (typeof this.state.filter_type !== "undefined") {
                items = items.filter(
                    (item: ItemDefinition) =>
                        item.type === this.state.filter_type,
                );
            }

            return this.setState({
                items: items,
            });
        }

        const filteredItems = this.state.items.filter((item: any) =>
            item.name.toLowerCase().includes(searchTerm.toLowerCase()),
        );

        this.setState({ items: filteredItems });
    }

    render() {
        return (
            <>
                {this.props.item_to_view !== null ? (
                    <div>
                        <PrimaryOutlineButton
                            button_label={this.props.close_view_item_label}
                            on_click={this.props.close_view_item_action}
                            additional_css={"my-3"}
                        />
                        <BasicCard>
                            <Item item={this.props.item_to_view} />
                        </BasicCard>
                    </div>
                ) : (
                    <BasicCard additionalClasses={"my-4"}>
                        <InfoAlert additional_css={"my-3"}>
                            <p>
                                Click the name of the item to get more info
                                about it's stats.
                            </p>
                        </InfoAlert>
                        <div className="w-full my-4 md:w-3/5">
                            <div className="grid gap-4 my-4 md:grid-cols-3">
                                <div className="flex items-center">
                                    <div className="mr-2">Search:</div>
                                    <input
                                        type="text"
                                        className="w-full px-4 text-gray-800 bg-gray-200 border-gray-300 rounded-md shadow-sm h-9 dark:text-white focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700"
                                        value={this.state.search_term}
                                        onChange={this.handleSearchInputChange.bind(
                                            this,
                                        )}
                                    />
                                </div>
                                <div>
                                    <Select
                                        onChange={this.setSelectedFilterType.bind(
                                            this,
                                        )}
                                        options={
                                            this.props.custom_filter ??
                                            itemTypeFilter()
                                        }
                                        menuPosition={"absolute"}
                                        menuPlacement={"bottom"}
                                        styles={{
                                            menuPortal: (base) => ({
                                                ...base,
                                                zIndex: 9999,
                                                color: "#000000",
                                            }),
                                        }}
                                        menuPortalTarget={document.body}
                                        value={this.getSelectedFilterValue()}
                                    />
                                </div>
                                <div>
                                    <DangerOutlineButton
                                        button_label={"Clear Filters"}
                                        on_click={this.clearFilters.bind(this)}
                                    />
                                </div>
                            </div>
                        </div>
                        <Table
                            columns={this.props.table_columns}
                            data={this.state.items}
                            dark_table={this.state.dark_tables}
                        />
                    </BasicCard>
                )}
            </>
        );
    }
}
