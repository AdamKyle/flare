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
import React, { Fragment } from "react";
import Dialogue from "../../../ui/dialogue/dialogue";
import ItemNameColorationText from "../../../items/item-name/item-name-coloration-text";
import { MarketBoardLineChart } from "../../../ui/charts/line-chart";
import Ajax from "../../../../lib/ajax/ajax";
import ComponentLoading from "../../../ui/loading/component-loading";
import { DateTime } from "luxon";
import InfoAlert from "../../../ui/alerts/simple-alerts/info-alert";
import { formatNumber } from "../../../../lib/game/format-number";
var ListItemModal = (function (_super) {
    __extends(ListItemModal, _super);
    function ListItemModal(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            listed_value: 0,
            data: [],
        };
        return _this;
    }
    ListItemModal.prototype.componentDidMount = function () {
        var _this = this;
        if (this.props.item.min_cost > 0) {
            this.setState({
                listed_value: this.props.item.min_cost,
            });
        }
        new Ajax()
            .setRoute("market-board/items")
            .setParameters({
                item_id: this.props.item.item_id,
            })
            .doAjaxCall(
                "get",
                function (result) {
                    var data = result.data.items.map(function (item) {
                        return {
                            date: DateTime.fromISO(item.listed_at)
                                .toLocaleString({
                                    weekday: "short",
                                    month: "short",
                                    day: "2-digit",
                                    hour: "2-digit",
                                    minute: "2-digit",
                                    second: "2-digit",
                                })
                                .toString(),
                            price: item.listed_price,
                        };
                    });
                    var now = DateTime.now()
                        .toLocaleString({
                            weekday: "short",
                            month: "short",
                            day: "2-digit",
                            hour: "2-digit",
                            minute: "2-digit",
                            second: "2-digit",
                        })
                        .toString();
                    if (data.length === 0) {
                        data.push({
                            date: now,
                            price: 0,
                        });
                    }
                    var chartData = {
                        label: "Listed for (Gold)",
                        color: "#441414",
                        data: data,
                    };
                    _this.setState({
                        loading: false,
                        data: [chartData],
                    });
                },
                function (error) {},
            );
    };
    ListItemModal.prototype.listItem = function () {
        this.props.list_item(this.state.listed_value);
        this.props.manage_modal();
    };
    ListItemModal.prototype.setListedPrice = function (e) {
        var value = parseInt(e.target.value) || 0;
        if (this.props.item.min_cost > value) {
            value = this.props.item.min_cost;
        }
        if (value > 2000000000000000) {
            value = 2000000000000000;
        }
        this.setState({
            listed_value: value,
        });
    };
    ListItemModal.prototype.render = function () {
        var _a;
        var _this = this;
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.manage_modal,
                title: "List item on market",
                secondary_actions: {
                    secondary_button_disabled:
                        this.state.listed_value <= 0 ||
                        this.state.listed_value === "",
                    secondary_button_label: "List item",
                    handle_action: function () {
                        return _this.listItem();
                    },
                },
            },
            this.state.loading
                ? React.createElement(
                      "div",
                      { className: "p-5 mb-2" },
                      React.createElement(ComponentLoading, null),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      React.createElement(
                          "h3",
                          { className: "mb-4 mt-4" },
                          React.createElement(ItemNameColorationText, {
                              custom_width: false,
                              item: __assign(
                                  __assign({}, this.props.item),
                                  ((_a = {}),
                                  (_a["name"] = this.props.item.affix_name),
                                  _a),
                              ),
                          }),
                      ),
                      React.createElement(MarketBoardLineChart, {
                          dark_chart: this.props.dark_charts,
                          data: this.state.data,
                          key_for_value: "price",
                      }),
                      React.createElement(
                          "p",
                          {
                              className:
                                  "text-xs text-gray-700 dark:text-gray-500 mb-4",
                          },
                          "If the chart above states 0, then this item has never been listed before or there is no current listing for it.",
                      ),
                      this.props.item.min_cost > 0
                          ? React.createElement(
                                InfoAlert,
                                null,
                                "Item has a min value of:",
                                " ",
                                formatNumber(this.props.item.min_cost),
                                " Gold.",
                            )
                          : null,
                      React.createElement(
                          "div",
                          { className: "mb-5 mt-5" },
                          React.createElement(
                              "label",
                              {
                                  className: "label block mb-2",
                                  htmlFor: "list-for",
                              },
                              "List For",
                          ),
                          React.createElement("input", {
                              id: "list-for",
                              type: "number",
                              className: "form-control",
                              name: "list-for",
                              value: this.state.listed_value,
                              autoFocus: true,
                              onChange: this.setListedPrice.bind(this),
                          }),
                          React.createElement(
                              "p",
                              {
                                  className:
                                      "text-xs text-gray-700 dark:text-gray-500",
                              },
                              "If the value is set for you, this means the item cannot be sold for less that listed price.",
                          ),
                      ),
                  ),
        );
    };
    return ListItemModal;
})(React.Component);
export default ListItemModal;
//# sourceMappingURL=list-item-modal.js.map
