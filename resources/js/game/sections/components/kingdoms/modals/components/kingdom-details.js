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
import Ajax from "../../../../../lib/ajax/ajax";
import { formatNumber, percent } from "../../../../../lib/game/format-number";
import KingdomHelpModal from "../kingdom-help-modal";
import KingdomTopSection from "./kingdom-top-section";
import ComponentLoading from "../../../../../components/ui/loading/component-loading";
import PrimaryOutlineButton from "../../../../../components/ui/buttons/primary-outline-button";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
import DangerOutlineButton from "../../../../../components/ui/buttons/danger-outline-button";
import AttackKingdomModal from "../../../../../components/kingdoms/modals/attack-kingdom-modal";
var KingdomDetails = (function (_super) {
    __extends(KingdomDetails, _super);
    function KingdomDetails(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            kingdom_details: null,
            action_loading: false,
            error_message: "",
            show_attack_dialogue: false,
        };
        return _this;
    }
    KingdomDetails.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "kingdom/" +
                    this.props.kingdom_id +
                    "/" +
                    this.props.character_id,
            )
            .doAjaxCall(
                "get",
                function (result) {
                    var kingdomData = result.data;
                    _this.setState(
                        {
                            kingdom_details: kingdomData,
                        },
                        function () {
                            _this.props.update_loading(kingdomData);
                        },
                    );
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    KingdomDetails.prototype.manageAttackKingdom = function () {
        this.setState({
            show_attack_dialogue: !this.state.show_attack_dialogue,
        });
    };
    KingdomDetails.prototype.purchaseKingdom = function () {
        var _this = this;
        this.setState(
            {
                action_loading: true,
                error_message: "",
            },
            function () {
                _this.props.update_action_in_progress();
            },
        );
        new Ajax()
            .setRoute(
                "kingdoms/" + this.props.character_id + "/purchase-npc-kingdom",
            )
            .setParameters({
                kingdom_id: this.props.kingdom_id,
            })
            .doAjaxCall(
                "post",
                function (result) {
                    _this.setState({ action_loading: false }, function () {
                        _this.props.update_action_in_progress();
                        _this.props.close_modal();
                    });
                },
                function (error) {
                    _this.setState({ action_loading: false }, function () {
                        _this.props.update_action_in_progress();
                    });
                    if (typeof error.response !== "undefined") {
                        var response = error.response;
                        if (response.status === 422) {
                            _this.setState({
                                error_message: response.data.message,
                            });
                        }
                    }
                    console.error(error);
                },
            );
    };
    KingdomDetails.prototype.manageHelpDialogue = function (type) {
        this.setState({
            show_help: !this.state.show_help,
            help_type: type,
        });
    };
    KingdomDetails.prototype.render = function () {
        var _this = this;
        if (this.state.kingdom_details === null) {
            return React.createElement(
                "div",
                { className: "h-40" },
                React.createElement(ComponentLoading, null),
            );
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                null,
                this.props.show_top_section
                    ? React.createElement(KingdomTopSection, {
                          kingdom: this.state.kingdom_details,
                      })
                    : null,
                React.createElement(
                    "div",
                    { className: "lg:grid lg:grid-cols-2" },
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Wall Defence"),
                            React.createElement(
                                "dd",
                                null,
                                React.createElement(
                                    "div",
                                    { className: "flex items-center mb-4" },
                                    percent(
                                        this.state.kingdom_details
                                            .walls_defence,
                                    ),
                                    "%",
                                    React.createElement(
                                        "div",
                                        null,
                                        React.createElement(
                                            "div",
                                            { className: "ml-2" },
                                            React.createElement(
                                                "button",
                                                {
                                                    type: "button",
                                                    onClick: function () {
                                                        return _this.manageHelpDialogue(
                                                            "wall_defence",
                                                        );
                                                    },
                                                    className:
                                                        "text-blue-500 dark:text-blue-300",
                                                },
                                                React.createElement("i", {
                                                    className:
                                                        "fas fa-info-circle",
                                                }),
                                                " ",
                                                "Help",
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            React.createElement("dt", null, "Treas. Defence"),
                            React.createElement(
                                "dd",
                                null,
                                React.createElement(
                                    "div",
                                    { className: "flex items-center mb-4" },
                                    percent(
                                        this.state.kingdom_details
                                            .treasury_defence,
                                    ),
                                    "%",
                                    React.createElement(
                                        "div",
                                        null,
                                        React.createElement(
                                            "div",
                                            { className: "ml-2" },
                                            React.createElement(
                                                "button",
                                                {
                                                    type: "button",
                                                    onClick: function () {
                                                        return _this.manageHelpDialogue(
                                                            "treas_defence",
                                                        );
                                                    },
                                                    className:
                                                        "text-blue-500 dark:text-blue-300",
                                                },
                                                React.createElement("i", {
                                                    className:
                                                        "fas fa-info-circle",
                                                }),
                                                " ",
                                                "Help",
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            React.createElement("dt", null, "GB. Defence"),
                            React.createElement(
                                "dd",
                                null,
                                React.createElement(
                                    "div",
                                    { className: "flex items-center mb-4" },
                                    percent(
                                        this.state.kingdom_details
                                            .gold_bars_defence,
                                    ),
                                    "%",
                                    React.createElement(
                                        "div",
                                        null,
                                        React.createElement(
                                            "div",
                                            { className: "ml-2" },
                                            React.createElement(
                                                "button",
                                                {
                                                    type: "button",
                                                    onClick: function () {
                                                        return _this.manageHelpDialogue(
                                                            "gb_defence",
                                                        );
                                                    },
                                                    className:
                                                        "text-blue-500 dark:text-blue-300",
                                                },
                                                React.createElement("i", {
                                                    className:
                                                        "fas fa-info-circle",
                                                }),
                                                " ",
                                                "Help",
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            React.createElement("dt", null, "Passive Defence"),
                            React.createElement(
                                "dd",
                                null,
                                React.createElement(
                                    "div",
                                    { className: "flex items-center mb-4" },
                                    percent(
                                        this.state.kingdom_details
                                            .passive_defence,
                                    ),
                                    "%",
                                    React.createElement(
                                        "div",
                                        null,
                                        React.createElement(
                                            "div",
                                            { className: "ml-2" },
                                            React.createElement(
                                                "button",
                                                {
                                                    type: "button",
                                                    onClick: function () {
                                                        return _this.manageHelpDialogue(
                                                            "passive_defence",
                                                        );
                                                    },
                                                    className:
                                                        "text-blue-500 dark:text-blue-300",
                                                },
                                                React.createElement("i", {
                                                    className:
                                                        "fas fa-info-circle",
                                                }),
                                                " ",
                                                "Help",
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            React.createElement("dt", null, "Total Defence"),
                            React.createElement(
                                "dd",
                                null,
                                React.createElement(
                                    "div",
                                    { className: "flex items-center mb-4" },
                                    percent(
                                        this.state.kingdom_details
                                            .defence_bonus,
                                    ),
                                    "%",
                                    React.createElement(
                                        "div",
                                        null,
                                        React.createElement(
                                            "div",
                                            { className: "ml-2" },
                                            React.createElement(
                                                "button",
                                                {
                                                    type: "button",
                                                    onClick: function () {
                                                        return _this.manageHelpDialogue(
                                                            "total_defence",
                                                        );
                                                    },
                                                    className:
                                                        "text-blue-500 dark:text-blue-300",
                                                },
                                                React.createElement("i", {
                                                    className:
                                                        "fas fa-info-circle",
                                                }),
                                                " ",
                                                "Help",
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        React.createElement("div", {
                            className:
                                "border-b-2 block border-b-gray-300 dark:border-b-gray-600 my-3 md:hidden",
                        }),
                        React.createElement(
                            "div",
                            null,
                            React.createElement(
                                "dl",
                                null,
                                React.createElement(
                                    "dt",
                                    null,
                                    "Item Resistance",
                                ),
                                React.createElement(
                                    "dd",
                                    null,
                                    React.createElement(
                                        "div",
                                        { className: "flex items-center mb-4" },
                                        percent(
                                            this.state.kingdom_details
                                                .item_resistance_bonus,
                                        ),
                                        "%",
                                        React.createElement(
                                            "div",
                                            null,
                                            React.createElement(
                                                "div",
                                                { className: "ml-2" },
                                                React.createElement(
                                                    "button",
                                                    {
                                                        type: "button",
                                                        onClick: function () {
                                                            return _this.manageHelpDialogue(
                                                                "item_resistance",
                                                            );
                                                        },
                                                        className:
                                                            "text-blue-500 dark:text-blue-300",
                                                    },
                                                    React.createElement("i", {
                                                        className:
                                                            "fas fa-info-circle",
                                                    }),
                                                    " ",
                                                    "Help",
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    React.createElement(
                        "div",
                        null,
                        React.createElement(
                            "dl",
                            null,
                            React.createElement("dt", null, "Treasure:"),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(
                                    this.state.kingdom_details.treasury,
                                ),
                            ),
                            React.createElement("dt", null, "Gold Bars:"),
                            React.createElement(
                                "dd",
                                null,
                                formatNumber(
                                    this.state.kingdom_details.gold_bars,
                                ),
                            ),
                        ),
                        this.props.allow_purchase
                            ? React.createElement(
                                  "div",
                                  { className: "mt-4 text-center" },
                                  React.createElement(PrimaryOutlineButton, {
                                      button_label: "Purchase Kingdom",
                                      on_click: this.purchaseKingdom.bind(this),
                                  }),
                              )
                            : null,
                        this.props.can_attack_kingdom
                            ? React.createElement(
                                  "div",
                                  { className: "mt-4 text-center" },
                                  React.createElement(DangerOutlineButton, {
                                      button_label: "Attack Kingdom",
                                      on_click:
                                          this.manageAttackKingdom.bind(this),
                                  }),
                              )
                            : null,
                    ),
                ),
            ),
            this.state.show_help
                ? React.createElement(KingdomHelpModal, {
                      manage_modal: this.manageHelpDialogue.bind(this),
                      type: this.state.help_type,
                  })
                : null,
            this.state.show_attack_dialogue
                ? React.createElement(AttackKingdomModal, {
                      is_open: true,
                      handle_close: this.manageAttackKingdom.bind(this),
                      kingdom_to_attack_id: this.props.kingdom_id,
                      character_id: this.props.character_id,
                      kingdom_defence: this.state.kingdom_details.defence_bonus,
                  })
                : null,
            this.state.error_message !== ""
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-4" },
                      this.state.error_message,
                  )
                : null,
            this.state.action_loading
                ? React.createElement(LoadingProgressBar, null)
                : null,
        );
    };
    return KingdomDetails;
})(React.Component);
export default KingdomDetails;
//# sourceMappingURL=kingdom-details.js.map
