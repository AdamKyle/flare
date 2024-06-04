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
import React, { Fragment } from "react";
import Table from "../../../../../components/ui/data-tables/table";
import { buildLimitedColumns } from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import InventoryUseDetails from "../../modals/inventory-item-details";
var QuestItemsTable = (function (_super) {
    __extends(QuestItemsTable, _super);
    function QuestItemsTable(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            data: _this.props.quest_items,
            item_id: null,
            view_item: false,
        };
        return _this;
    }
    QuestItemsTable.prototype.search = function (e) {
        var value = e.target.value;
        this.setState({
            data: this.props.quest_items.filter(function (item) {
                return (
                    item.item_name.includes(value) ||
                    item.description.includes(value)
                );
            }),
        });
    };
    QuestItemsTable.prototype.viewItem = function (item) {
        this.setState({
            item_id: typeof item !== "undefined" ? item.item_id : null,
            view_item: !this.state.view_item,
        });
    };
    QuestItemsTable.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "mb-5" },
                React.createElement(
                    "div",
                    { className: "flex flex-row flex-wrap items-center" },
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "label",
                            {
                                className: "label block mt-2 md:mt-0 mb-2 mr-3",
                                htmlFor: "search",
                            },
                            "Search",
                        ),
                    ),
                    React.createElement(
                        "div",
                        { className: "w-1/2" },
                        React.createElement("input", {
                            type: "text",
                            name: "search",
                            className: "form-control",
                            onChange: this.search.bind(this),
                        }),
                    ),
                    React.createElement(
                        "div",
                        { className: "mt-2 sm:mt-[-5px] ml-2 md:ml-0 md:mt-0" },
                        React.createElement(
                            "a",
                            {
                                href: "/information/quests",
                                target: "_blank",
                                className: "sm:ml-2",
                            },
                            "Quests help",
                            " ",
                            React.createElement("i", {
                                className: "fas fa-external-link-alt",
                            }),
                        ),
                    ),
                ),
            ),
            React.createElement(
                "div",
                { className: "max-w-[390px] md:max-w-full overflow-y-hidden" },
                React.createElement(Table, {
                    data: this.state.data,
                    columns: buildLimitedColumns(
                        this.props.view_port,
                        undefined,
                        this.viewItem.bind(this),
                    ),
                    dark_table: this.props.dark_table,
                }),
            ),
            this.state.view_item && this.state.item_id !== null
                ? React.createElement(InventoryUseDetails, {
                      character_id: this.props.character_id,
                      item_id: this.state.item_id,
                      is_open: this.state.view_item,
                      manage_modal: this.viewItem.bind(this),
                  })
                : null,
        );
    };
    return QuestItemsTable;
})(React.Component);
export default QuestItemsTable;
//# sourceMappingURL=quest-items-table.js.map
