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
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import Select from "react-select";
import { formatNumber } from "../../../lib/game/format-number";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import PrimaryButton from "../../ui/buttons/primary-button";
import DangerButton from "../../ui/buttons/danger-button";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import { serviceContainer } from "../../../lib/containers/core-container";
import Ajax from "../../../lib/ajax/ajax";
var LabyrinthOracle = (function (_super) {
    __extends(LabyrinthOracle, _super);
    function LabyrinthOracle(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            transferring: false,
            item_to_transfer_from: null,
            item_to_transfer_to: null,
            inventory: [],
            error_message: null,
            success_message: null,
        };
        _this.ajax = serviceContainer().fetch(Ajax);
        _this.labyrinthOracle = Echo.private(
            "update-labyrinth-oracle-" + _this.props.user_id,
        );
        return _this;
    }
    LabyrinthOracle.prototype.componentDidMount = function () {
        var _this = this;
        this.ajax
            .setRoute(
                "character/" + this.props.character_id + "/labyrinth-oracle",
            )
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        loading: false,
                        inventory: result.data.inventory,
                    });
                },
                function (error) {
                    _this.setState({ loading: false });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        _this.setState({
                            error_message: response.data.message,
                        });
                    }
                },
            );
        this.labyrinthOracle.listen(
            "Game.NpcActions.LabyrinthOracle.Events.LabyrinthOracleUpdate",
            function (event) {
                _this.setState({
                    inventory: event.inventory,
                });
            },
        );
    };
    LabyrinthOracle.prototype.transferItems = function () {
        return this.state.inventory.map(function (inventoryItem) {
            return {
                label: inventoryItem.affix_name,
                value: "".concat(inventoryItem.id),
            };
        });
    };
    LabyrinthOracle.prototype.setSelectedTransferFrom = function (data) {
        if (data.value === "") {
            return;
        }
        this.setState({
            item_to_transfer_from: data.value,
        });
    };
    LabyrinthOracle.prototype.setSelectedTransferTo = function (data) {
        if (data.value === "") {
            return;
        }
        this.setState({
            item_to_transfer_to: data.value,
        });
    };
    LabyrinthOracle.prototype.selectedTransfer = function (key) {
        var _this = this;
        var isFrom = key === "item_to_transfer_from";
        if (this.state[key] === null) {
            return {
                label: "Please select transfer " + (isFrom ? "from" : "to"),
                value: "",
            };
        }
        var foundSelectedItem = this.state.inventory.filter(function (item) {
            return item.id === parseInt(_this.state[key]);
        });
        if (foundSelectedItem.length === 0) {
            return {
                label: "Please select transfer " + (isFrom ? "from" : "to"),
                value: "",
            };
        }
        return {
            label: foundSelectedItem[0].affix_name,
            value: foundSelectedItem[0].id,
        };
    };
    LabyrinthOracle.prototype.transfer = function () {
        var _this = this;
        this.setState(
            {
                success_message: null,
                error_message: null,
                transferring: true,
            },
            function () {
                _this.ajax
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/transfer-attributes",
                    )
                    .setParameters({
                        item_id_from: _this.state.item_to_transfer_from,
                        item_id_to: _this.state.item_to_transfer_to,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                transferring: false,
                                inventory: result.data.inventory,
                                success_message: result.data.message,
                                item_to_transfer_from: null,
                                item_to_transfer_to: null,
                            });
                        },
                        function (error) {
                            _this.setState({ transferring: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    LabyrinthOracle.prototype.render = function () {
        return React.createElement(
            React.Fragment,
            null,
            React.createElement(
                "div",
                {
                    className:
                        "mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]",
                },
                React.createElement(
                    "div",
                    { className: "lg:cols-start-1 lg:col-span-2" },
                    this.state.loading && this.state.inventory.length === 0
                        ? React.createElement(LoadingProgressBar, null)
                        : React.createElement(
                              React.Fragment,
                              null,
                              React.createElement(
                                  "div",
                                  { className: "my-2" },
                                  React.createElement(Select, {
                                      onChange:
                                          this.setSelectedTransferFrom.bind(
                                              this,
                                          ),
                                      options: this.transferItems(),
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
                                      value: this.selectedTransfer(
                                          "item_to_transfer_from",
                                      ),
                                  }),
                              ),
                              React.createElement(Select, {
                                  onChange:
                                      this.setSelectedTransferTo.bind(this),
                                  options: this.transferItems(),
                                  menuPosition: "absolute",
                                  menuPlacement: "bottom",
                                  styles: {
                                      menuPortal: function (base) {
                                          return __assign(__assign({}, base), {
                                              zIndex: 9999,
                                              color: "#000000",
                                          });
                                      },
                                  },
                                  menuPortalTarget: document.body,
                                  value: this.selectedTransfer(
                                      "item_to_transfer_to",
                                  ),
                              }),
                              this.state.item_to_transfer_from !== null &&
                                  this.state.item_to_transfer_to !== null
                                  ? React.createElement(
                                        "div",
                                        { className: "mt-4 mb-2" },
                                        React.createElement(
                                            "dl",
                                            null,
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Gold Cost",
                                            ),
                                            React.createElement(
                                                "dl",
                                                null,
                                                formatNumber(100000000),
                                            ),
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Shards Cost",
                                            ),
                                            React.createElement(
                                                "dl",
                                                null,
                                                formatNumber(5000),
                                            ),
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Gold Dust Cost",
                                            ),
                                            React.createElement(
                                                "dl",
                                                null,
                                                formatNumber(2500),
                                            ),
                                        ),
                                    )
                                  : null,
                              this.state.transferring
                                  ? React.createElement(
                                        LoadingProgressBar,
                                        null,
                                    )
                                  : null,
                          ),
                ),
            ),
            this.state.error_message !== null
                ? React.createElement(
                      "div",
                      {
                          className:
                              "mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]",
                      },
                      React.createElement(
                          "div",
                          { className: "lg:cols-start-1 lg:col-span-2" },
                          React.createElement(
                              DangerAlert,
                              null,
                              this.state.error_message,
                          ),
                      ),
                  )
                : null,
            this.state.success_message !== null
                ? React.createElement(
                      "div",
                      {
                          className:
                              "mt-2 lg:grid lg:grid-cols-3 lg:gap-2 lg:ml-[120px]",
                      },
                      React.createElement(
                          "div",
                          { className: "lg:cols-start-1 lg:col-span-2" },
                          React.createElement(
                              SuccessAlert,
                              null,
                              this.state.success_message,
                          ),
                      ),
                  )
                : null,
            React.createElement(
                "div",
                { className: "text-center lg:ml-[-100px] mt-3 mb-3" },
                React.createElement(PrimaryButton, {
                    button_label: "Transfer",
                    on_click: this.transfer.bind(this),
                    disabled:
                        this.state.loading ||
                        this.state.item_to_transfer_from === null ||
                        this.state.item_to_transfer_to === null ||
                        this.props.cannot_craft ||
                        this.state.is_transfering,
                }),
                React.createElement(DangerButton, {
                    button_label: "Close",
                    on_click: this.props.remove_crafting,
                    additional_css: "ml-2",
                    disabled:
                        this.state.loading ||
                        this.props.cannot_craft ||
                        this.state.is_transfering,
                }),
                React.createElement(
                    "a",
                    {
                        href: "/information/labyrinth-oracle",
                        target: "_blank",
                        className: "relative top-[0px] ml-2",
                    },
                    "Help ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    return LabyrinthOracle;
})(React.Component);
export default LabyrinthOracle;
//# sourceMappingURL=labyrinth-oracle.js.map
