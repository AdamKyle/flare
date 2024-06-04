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
import BasicCard from "../../components/ui/cards/basic-card";
import KingdomDetails from "./kingdom-details";
import Select from "react-select";
import SmallBuildingsSection from "./buildings/small-buildings-section";
import SmallUnitsSection from "./units/small-units-section";
import DangerButton from "../../components/ui/buttons/danger-button";
import { serviceContainer } from "../../lib/containers/core-container";
import UpdateKingdomListeners from "../../lib/game/event-listeners/game/update-kingdom-listeners";
import Ajax from "../../lib/ajax/ajax";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import KingdomResourceTransfer from "./kingdom-resource-transfer";
var SmallKingdom = (function (_super) {
    __extends(SmallKingdom, _super);
    function SmallKingdom(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            show_kingdom_details: false,
            which_selected: null,
            kingdom: null,
            loading: true,
            error_message: null,
            show_resource_transfer_panel: false,
            should_reset_resource_transfer: false,
        };
        _this.updateKingdomListener = serviceContainer().fetch(
            UpdateKingdomListeners,
        );
        _this.updateKingdomListener.initialize(_this, _this.props.user_id);
        _this.updateKingdomListener.register();
        return _this;
    }
    SmallKingdom.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "player-kingdom/" +
                    this.props.kingdom.character_id +
                    "/" +
                    this.props.kingdom.id,
            )
            .doAjaxCall(
                "GET",
                function (result) {
                    _this.setState({
                        loading: false,
                        kingdom: result.data.kingdom,
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
        this.updateKingdomListener.listen();
    };
    SmallKingdom.prototype.manageKingdomDetails = function () {
        this.setState({
            show_kingdom_details: !this.state.show_kingdom_details,
        });
    };
    SmallKingdom.prototype.showSelected = function (data) {
        this.setState({
            which_selected: data.value,
        });
    };
    SmallKingdom.prototype.closeSelected = function () {
        this.setState({
            which_selected: null,
        });
    };
    SmallKingdom.prototype.renderSelected = function () {
        if (this.state.kingdom === null) {
            return;
        }
        switch (this.state.which_selected) {
            case "buildings":
                return React.createElement(SmallBuildingsSection, {
                    kingdom: this.state.kingdom,
                    dark_tables: this.props.dark_tables,
                    close_selected: this.closeSelected.bind(this),
                    character_gold: this.props.character_gold,
                    view_port: this.props.view_port,
                    user_id: this.props.user_id,
                });
            case "units":
                return React.createElement(SmallUnitsSection, {
                    kingdom: this.state.kingdom,
                    dark_tables: this.props.dark_tables,
                    close_selected: this.closeSelected.bind(this),
                    character_gold: this.props.character_gold,
                });
            default:
                return null;
        }
    };
    SmallKingdom.prototype.showResourceTransferPanel = function () {
        this.setState({
            show_resource_transfer_panel:
                !this.state.show_resource_transfer_panel,
            should_reset_resource_transfer:
                this.state.show_resource_transfer_panel,
        });
    };
    SmallKingdom.prototype.render = function () {
        if (this.state.loading || this.state.kingdom === null) {
            return React.createElement(LoadingProgressBar, null);
        }
        if (this.state.error_message !== null) {
            return React.createElement(
                BasicCard,
                null,
                React.createElement(
                    DangerAlert,
                    { additional_css: "my-4" },
                    this.state.error_message,
                ),
            );
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                BasicCard,
                null,
                !this.state.show_kingdom_details
                    ? React.createElement(
                          "div",
                          { className: "grid grid-cols-2" },
                          React.createElement(
                              "span",
                              null,
                              React.createElement(
                                  "strong",
                                  null,
                                  "Kingdom Details",
                              ),
                          ),
                          React.createElement(
                              "div",
                              {
                                  className:
                                      "text-right cursor-pointer text-blue-500",
                              },
                              React.createElement(
                                  "button",
                                  {
                                      onClick:
                                          this.manageKingdomDetails.bind(this),
                                  },
                                  React.createElement("i", {
                                      className: "fas fa-plus-circle",
                                  }),
                              ),
                          ),
                      )
                    : this.state.show_resource_transfer_panel
                      ? React.createElement(
                            Fragment,
                            null,
                            React.createElement(
                                "div",
                                { className: "grid grid-cols-2 mb-5" },
                                React.createElement(
                                    "span",
                                    null,
                                    React.createElement(
                                        "strong",
                                        null,
                                        "Kingdom Details",
                                    ),
                                ),
                                React.createElement(
                                    "div",
                                    {
                                        className:
                                            "text-right cursor-pointer text-red-500",
                                    },
                                    React.createElement(
                                        "button",
                                        {
                                            onClick:
                                                this.showResourceTransferPanel.bind(
                                                    this,
                                                ),
                                        },
                                        React.createElement("i", {
                                            className: "fas fa-minus-circle",
                                        }),
                                    ),
                                ),
                            ),
                            React.createElement(KingdomResourceTransfer, {
                                kingdom_id: this.props.kingdom.id,
                                character_id: this.props.kingdom.character_id,
                            }),
                        )
                      : React.createElement(
                            Fragment,
                            null,
                            React.createElement(
                                "div",
                                { className: "grid grid-cols-2 mb-5" },
                                React.createElement(
                                    "span",
                                    null,
                                    React.createElement(
                                        "strong",
                                        null,
                                        "Kingdom Details",
                                    ),
                                ),
                                React.createElement(
                                    "div",
                                    {
                                        className:
                                            "text-right cursor-pointer text-red-500",
                                    },
                                    React.createElement(
                                        "button",
                                        {
                                            onClick:
                                                this.manageKingdomDetails.bind(
                                                    this,
                                                ),
                                        },
                                        React.createElement("i", {
                                            className: "fas fa-minus-circle",
                                        }),
                                    ),
                                ),
                            ),
                            React.createElement(KingdomDetails, {
                                kingdom: this.state.kingdom,
                                character_gold: this.props.character_gold,
                                close_details: this.props.close_details,
                                show_resource_transfer_card:
                                    this.showResourceTransferPanel.bind(this),
                                reset_resource_transfer:
                                    this.state.should_reset_resource_transfer,
                            }),
                        ),
            ),
            React.createElement(
                "div",
                { className: "mt-4" },
                this.state.which_selected !== null
                    ? this.renderSelected()
                    : React.createElement(
                          Fragment,
                          null,
                          React.createElement(Select, {
                              onChange: this.showSelected.bind(this),
                              options: [
                                  {
                                      label: "Building Management",
                                      value: "buildings",
                                  },
                                  {
                                      label: "Unit Management",
                                      value: "units",
                                  },
                              ],
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
                              value: [
                                  {
                                      label: "Please Select Section",
                                      value: "",
                                  },
                              ],
                          }),
                          React.createElement(
                              "div",
                              { className: "grid gap-3" },
                              React.createElement(DangerButton, {
                                  button_label: "Close",
                                  on_click: this.props.close_details,
                                  additional_css: "mt-4",
                              }),
                          ),
                      ),
            ),
        );
    };
    return SmallKingdom;
})(React.Component);
export default SmallKingdom;
//# sourceMappingURL=small-kingdom.js.map
