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
var __assign =
    (this && this.__assign) ||
    function () {
        __assign =
            Object.assign ||
            function (t) {
                for (var s, i = 1, n = arguments.length; i < n; i++) {
                    s = arguments[i];
                    for (var p in s)
                        if (Object.prototype.hasOwnProperty.call(s, p))
                            t[p] = s[p];
                }
                return t;
            };
        return __assign.apply(this, arguments);
    };
import React from "react";
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
import { startCase } from "lodash";
import { watchForDarkModeChange } from "./helpers/watch-for-dark-mode-change";
var ItemTable = (function (_super) {
    __extends(ItemTable, _super);
    function ItemTable(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            items: [],
            dark_tables: false,
            filter_type: undefined,
            search_term: "",
        };
        _this.typingTimeOut = null;
        return _this;
    }
    ItemTable.prototype.componentDidMount = function () {
        watchForDarkModeChange(this);
        this.setState({
            items: this.props.items,
        });
    };
    ItemTable.prototype.clearFilters = function () {
        this.setState({
            items: this.props.items,
            filter_type: undefined,
            search_term: "",
        });
    };
    ItemTable.prototype.setSelectedFilterType = function (data) {
        var _this = this;
        if (data.value === "") {
            return;
        }
        var filteredItems = this.props.items.filter(function (item) {
            return item.type === data.value;
        });
        if (this.state.search_term !== "") {
            filteredItems = filteredItems.filter(function (item) {
                return item.name
                    .toLowerCase()
                    .includes(_this.state.search_term.toLowerCase());
            });
        }
        this.setState({
            filter_type: data.value,
            items: filteredItems,
        });
    };
    ItemTable.prototype.getSelectedFilterValue = function () {
        var _this = this;
        var itemType = ITEM_TYPES.filter(function (itemType) {
            return itemType === _this.state.filter_type;
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
    };
    ItemTable.prototype.handleSearchInputChange = function (event) {
        var _this = this;
        var searchTerm = event.target.value;
        if (this.typingTimeOut) {
            clearTimeout(this.typingTimeOut);
        }
        this.typingTimeOut = setTimeout(function () {
            _this.filterItems(searchTerm);
        }, 500);
        this.setState({ search_term: searchTerm });
    };
    ItemTable.prototype.filterItems = function (searchTerm) {
        var _this = this;
        if (searchTerm === "") {
            var items = this.props.items;
            if (typeof this.state.filter_type !== "undefined") {
                items = items.filter(function (item) {
                    return item.type === _this.state.filter_type;
                });
            }
            return this.setState({
                items: items,
            });
        }
        var filteredItems = this.state.items.filter(function (item) {
            return item.name.toLowerCase().includes(searchTerm.toLowerCase());
        });
        this.setState({ items: filteredItems });
    };
    ItemTable.prototype.render = function () {
        var _a;
        return React.createElement(
            React.Fragment,
            null,
            this.props.item_to_view !== null
                ? React.createElement(
                      "div",
                      null,
                      React.createElement(PrimaryOutlineButton, {
                          button_label: this.props.close_view_item_label,
                          on_click: this.props.close_view_item_action,
                          additional_css: "my-3",
                      }),
                      React.createElement(
                          BasicCard,
                          null,
                          React.createElement(Item, {
                              item: this.props.item_to_view,
                          }),
                      ),
                  )
                : React.createElement(
                      BasicCard,
                      { additionalClasses: "my-4" },
                      React.createElement(
                          InfoAlert,
                          { additional_css: "my-3" },
                          React.createElement(
                              "p",
                              null,
                              "Click the name of the item to get more info about it's stats.",
                          ),
                      ),
                      React.createElement(
                          "div",
                          { className: "md:w-3/5 w-full my-4" },
                          React.createElement(
                              "div",
                              { className: "grid md:grid-cols-3 gap-4 my-4" },
                              React.createElement(
                                  "div",
                                  { className: "flex items-center" },
                                  React.createElement(
                                      "div",
                                      { className: "mr-2" },
                                      "Search:",
                                  ),
                                  React.createElement("input", {
                                      type: "text",
                                      className:
                                          "w-full h-9 text-gray-800 dark:text-white border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 bg-gray-200 dark:bg-gray-700 px-4",
                                      value: this.state.search_term,
                                      onChange:
                                          this.handleSearchInputChange.bind(
                                              this,
                                          ),
                                  }),
                              ),
                              React.createElement(
                                  "div",
                                  null,
                                  React.createElement(Select, {
                                      onChange:
                                          this.setSelectedFilterType.bind(this),
                                      options:
                                          (_a = this.props.custom_filter) !==
                                              null && _a !== void 0
                                              ? _a
                                              : itemTypeFilter(),
                                      menuPosition: "absolute",
                                      menuPlacement: "bottom",
                                      styles: {
                                          menuPortal: function (base) {
                                              return __assign(
                                                  __assign({}, base),
                                                  {
                                                      zIndex: 9999,
                                                      color: "#000000",
                                                  },
                                              );
                                          },
                                      },
                                      menuPortalTarget: document.body,
                                      value: this.getSelectedFilterValue(),
                                  }),
                              ),
                              React.createElement(
                                  "div",
                                  null,
                                  React.createElement(DangerOutlineButton, {
                                      button_label: "Clear Filters",
                                      on_click: this.clearFilters.bind(this),
                                  }),
                              ),
                          ),
                      ),
                      React.createElement(Table, {
                          columns: this.props.table_columns,
                          data: this.state.items,
                          dark_table: this.state.dark_tables,
                      }),
                  ),
        );
    };
    return ItemTable;
})(React.Component);
export default ItemTable;
//# sourceMappingURL=item-table.js.map
