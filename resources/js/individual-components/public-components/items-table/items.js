var __extends =
    (this && this.__extends) ||
    (function () {
        var extendStatics = function (d, b) {
            extendStatics =
                Object.setPrototypeOf ||
                ({ __proto__: [] } instanceof Array &&
                    function (d, b) {
                        d.__proto__ = b;
                    }) ||
                function (d, b) {
                    for (var p in b)
                        if (Object.prototype.hasOwnProperty.call(b, p))
                            d[p] = b[p];
                };
            return extendStatics(d, b);
        };
        return function (d, b) {
            if (typeof b !== "function" && b !== null)
                throw new TypeError(
                    "Class extends value " +
                        String(b) +
                        " is not a constructor or null",
                );
            extendStatics(d, b);
            function __() {
                this.constructor = d;
            }
            d.prototype =
                b === null
                    ? Object.create(b)
                    : ((__.prototype = b.prototype), new __());
        };
    })();
import React from "react";
import LoadingProgressBar from "../../../game/components/ui/progress-bars/loading-progress-bar";
import ItemTable from "../../../game/components/items/item-table";
import ItemTableAjax from "./ajax/item-table-ajax";
import ItemTableColumns from "./columns/item-table-columns";
import { itemsTableServiceContainer } from "./container/items-container";
import DangerAlert from "../../../game/components/ui/alerts/simple-alerts/danger-alert";
var Items = (function (_super) {
    __extends(Items, _super);
    function Items(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            items: [],
            item_to_view: null,
            error_message: null,
        };
        _this.ajax = itemsTableServiceContainer().fetch(ItemTableAjax);
        _this.itemsTableColumns =
            itemsTableServiceContainer().fetch(ItemTableColumns);
        return _this;
    }
    Items.prototype.componentDidMount = function () {
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
    };
    Items.prototype.viewItem = function (itemId) {
        this.setState({
            item_to_view: this.state.items.filter(function (item) {
                return item.id === itemId;
            })[0],
        });
    };
    Items.prototype.closeViewSection = function () {
        this.setState({
            item_to_view: null,
        });
    };
    Items.prototype.render = function () {
        if (this.props.type === null) {
            return;
        }
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        if (this.state.error_message !== null) {
            return React.createElement(
                DangerAlert,
                { additional_css: "my-4" },
                this.state.error_message,
            );
        }
        return React.createElement(ItemTable, {
            items: this.state.items,
            item_to_view: this.state.item_to_view,
            close_view_item_label: "Back",
            table_columns: this.itemsTableColumns.buildColumns(
                this.viewItem.bind(this),
                this.props.type,
            ),
            close_view_item_action: this.closeViewSection.bind(this),
        });
    };
    return Items;
})(React.Component);
export default Items;
//# sourceMappingURL=items.js.map
