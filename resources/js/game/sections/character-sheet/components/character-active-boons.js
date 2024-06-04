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
import Ajax from "../../../lib/ajax/ajax";
import { watchForDarkModeTableChange } from "../../../lib/game/dark-mode-watcher";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import { DateTime } from "luxon";
import Table from "../../../components/ui/data-tables/table";
import { formatNumber } from "../../../lib/game/format-number";
import InventoryUseDetails from "./modals/inventory-use-details";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
var CharacterActiveBoons = (function (_super) {
    __extends(CharacterActiveBoons, _super);
    function CharacterActiveBoons(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            boons: [],
            dark_tables: false,
            show_usable_details: false,
            item_to_use: null,
            removing_boon: false,
            error_message: null,
            success_message: null,
        };
        return _this;
    }
    CharacterActiveBoons.prototype.componentDidMount = function () {
        var _this = this;
        watchForDarkModeTableChange(this);
        if (this.props.finished_loading && this.props.character_id !== null) {
            new Ajax()
                .setRoute(
                    "character-sheet/" +
                        this.props.character_id +
                        "/active-boons",
                )
                .doAjaxCall(
                    "get",
                    function (result) {
                        _this.setState({
                            loading: false,
                            boons: result.data.active_boons,
                        });
                    },
                    function (error) {
                        console.error(error);
                    },
                );
        }
    };
    CharacterActiveBoons.prototype.manageBoon = function (row) {
        this.setState({
            show_usable_details: !this.state.show_usable_details,
            item_to_use: typeof row !== "undefined" ? row.boon_applied : null,
        });
    };
    CharacterActiveBoons.prototype.removeBoon = function (boonId) {
        var _this = this;
        this.setState(
            {
                removing_boon: true,
                success_message: null,
                error_message: null,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character-sheet/" +
                            _this.props.character_id +
                            "/remove-boon/" +
                            boonId,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                removing_boon: false,
                                boons: result.data.boons,
                                success_message: result.data.message,
                            });
                        },
                        function (error) {
                            var message = "UNKNOWN ERROR - CHECK CONSOLE!";
                            if (error.response !== undefined) {
                                var response = error.response;
                                message = response.data.message;
                            }
                            _this.setState({
                                removing_boon: false,
                                error_message: message,
                            });
                            console.error(error.response);
                        },
                    );
            },
        );
    };
    CharacterActiveBoons.prototype.buildColumns = function () {
        var _this = this;
        return [
            {
                name: "Name",
                selector: function (row) {
                    return row.boon_applied.name;
                },
                sortable: true,
                cell: function (row) {
                    return React.createElement(
                        "span",
                        {
                            key:
                                row.id +
                                "-" +
                                (Math.random() + 1).toString(36).substring(7),
                        },
                        React.createElement(
                            "button",
                            {
                                onClick: function () {
                                    return _this.manageBoon(row);
                                },
                                className: "text-sky-600 dark:text-sky-300",
                            },
                            row.boon_applied.name,
                        ),
                    );
                },
            },
            {
                name: "Amount Used",
                selector: function (row) {
                    return row.amount_used;
                },
                sortable: true,
            },
            {
                name: "Time Remaining",
                selector: function (row) {
                    return row.started;
                },
                sortable: true,
                cell: function (row) {
                    return React.createElement(
                        "span",
                        {
                            key:
                                row.started +
                                "-" +
                                (Math.random() + 1).toString(36).substring(7),
                        },
                        _this.getLabel(row.started, row.complete),
                    );
                },
            },
            {
                name: "Actions",
                selector: function (row) {
                    return row.boon_applied.id;
                },
                sortable: true,
                cell: function (row) {
                    return React.createElement(
                        "span",
                        {
                            key:
                                row.boon_applied.id +
                                "-" +
                                (Math.random() + 1).toString(36).substring(7),
                        },
                        React.createElement(DangerButton, {
                            button_label: "Remove Boon",
                            on_click: function () {
                                return _this.removeBoon(row.id);
                            },
                        }),
                    );
                },
            },
        ];
    };
    CharacterActiveBoons.prototype.getLabel = function (
        startedAt,
        completedAt,
    ) {
        var label = "seconds";
        var started = DateTime.now();
        var completed = DateTime.fromISO(completedAt);
        var time = completed.diff(started, ["seconds"]).toObject().seconds;
        if (typeof time === "undefined") {
            return "Error";
        }
        if (time / 3600 >= 1) {
            label = formatNumber(time / 3600) + " hour(s)";
        } else if (time / 60 >= 1) {
            label = formatNumber(time / 60) + " minute(s)";
        }
        return label;
    };
    CharacterActiveBoons.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "my-5" },
                this.state.boons.length > 0
                    ? React.createElement(
                          InfoAlert,
                          null,
                          "This tab does not update in real time. You can switch tabs to get the latest data.",
                      )
                    : null,
                this.state.removing_boon
                    ? React.createElement(LoadingProgressBar, null)
                    : null,
                this.state.success_message !== null
                    ? React.createElement(
                          SuccessAlert,
                          { additional_css: "my-4" },
                          React.createElement(
                              "p",
                              null,
                              this.state.success_message,
                          ),
                      )
                    : null,
                this.state.error_message !== null
                    ? React.createElement(
                          DangerAlert,
                          { additional_css: "my-4" },
                          React.createElement(
                              "p",
                              null,
                              this.state.error_message,
                          ),
                      )
                    : null,
                React.createElement(
                    "p",
                    { className: "my-4 text-center" },
                    React.createElement(
                        "a",
                        { href: "/information/alchemy", target: "_blank" },
                        "What are boons and how do I get them?",
                        " ",
                        React.createElement("i", {
                            className: "fas fa-external-link-alt",
                        }),
                    ),
                ),
                this.state.boons.length > 0
                    ? React.createElement(
                          "div",
                          {
                              className:
                                  "max-w-[390px] md:max-w-full overflow-x-hidden",
                          },
                          React.createElement(Table, {
                              columns: this.buildColumns(),
                              data: this.state.boons,
                              dark_table: this.state.dark_tables,
                          }),
                      )
                    : React.createElement(
                          "p",
                          { className: "my-4 text-center" },
                          "No Active Boons.",
                      ),
            ),
            this.state.show_usable_details && this.state.item_to_use !== null
                ? React.createElement(InventoryUseDetails, {
                      is_open: this.state.show_usable_details,
                      manage_modal: this.manageBoon.bind(this),
                      item: this.state.item_to_use,
                  })
                : null,
        );
    };
    return CharacterActiveBoons;
})(React.Component);
export default CharacterActiveBoons;
//# sourceMappingURL=character-active-boons.js.map
