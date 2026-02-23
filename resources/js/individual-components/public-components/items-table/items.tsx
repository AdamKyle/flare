import React from "react";
import ItemTableProps from "../items-table/types/items-table-props";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import ItemTable from "../../../game/components/items/item-table";
import ItemTableAjax from "./ajax/item-table-ajax";
import ItemTableColumns from "./columns/item-table-columns";
import { itemsTableServiceContainer } from "./container/items-container";
import DangerAlert from "../../../game/components/ui/alerts/simple-alerts/danger-alert";
import ItemTableState from "./types/item-table-state";

type TableFilterParams = {
    filter?: string | null;
    search_text?: string | null;
};

export default class Items extends React.Component<
    ItemTableProps,
    ItemTableState
> {
    private ajax: ItemTableAjax<TableFilterParams>;

    private itemsTableColumns: ItemTableColumns;

    constructor(props: ItemTableProps) {
        super(props);

        this.state = {
            loading: true,
            items: [],
            item_to_view: null,
            error_message: null,
            filter: null,
            search_text: null,
        };

        this.ajax = itemsTableServiceContainer().fetch(ItemTableAjax);

        this.itemsTableColumns =
            itemsTableServiceContainer().fetch(ItemTableColumns);
    }

    componentDidMount() {
        if (this.props.type === null) {
            this.setState({
                loading: false,
                error_message: "Type of table to render is missing.",
            });

            return;
        }

        this.setState({
            error_message: null,
        });

        this.ajax.fetchTableData(this, this.props.type);
    }

    viewItem(itemId: number) {
        this.setState({
            item_to_view: this.state.items.filter(
                (item: any) => item.id === itemId,
            )[0],
        });
    }

    closeViewSection() {
        this.setState({
            item_to_view: null,
        });
    }

    onFilterChange() {
        if (!this.props.type) {
            return;
        }

        this.ajax.fetchTableData(this, this.props.type, {
            filter: this.state.filter,
            search_text: this.state.search_text,
        });
    }

    setFilters(filters: { filter: string | null; search_text: string | null }) {
        this.setState(
            {
                filter: filters.filter,
                search_text: filters.search_text,
            },
            () => {
                this.onFilterChange();
            },
        );
    }

    render() {
        if (this.props.type === null) {
            return;
        }

        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        if (this.state.error_message !== null) {
            return (
                <DangerAlert additional_css={"my-4"}>
                    {this.state.error_message}
                </DangerAlert>
            );
        }

        return (
            <ItemTable
                items={this.state.items}
                item_to_view={this.state.item_to_view}
                close_view_item_label={"Back"}
                table_columns={this.itemsTableColumns.buildColumns(
                    this.viewItem.bind(this),
                    this.props.type,
                )}
                close_view_item_action={this.closeViewSection.bind(this)}
                set_item_filter={this.setFilters.bind(this)}
            />
        );
    }
}
