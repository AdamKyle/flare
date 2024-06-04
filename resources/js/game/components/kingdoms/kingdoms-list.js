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
import { isEqual } from "lodash";
import React, { Fragment } from "react";
import DangerAlert from "../../components/ui/alerts/simple-alerts/danger-alert";
import BasicCard from "../../components/ui/cards/basic-card";
import Table from "../../components/ui/data-tables/table";
import LoadingProgressBar from "../../components/ui/progress-bars/loading-progress-bar";
import TabPanel from "../../components/ui/tabs/tab-panel";
import Tabs from "../../components/ui/tabs/tabs";
import Ajax from "../../lib/ajax/ajax";
import { watchForDarkModeTableChange } from "../../lib/game/dark-mode-watcher";
import Kingdom from "./kingdom";
import KingdomLogDetailsView from "./kingdom-log-details";
import SmallKingdom from "./small-kingdom";
import { buildKingdomsColumns } from "./table-columns/build-kingdoms-columns";
import { buildLogsColumns } from "./table-columns/build-logs-columns";
var KingdomsList = (function (_super) {
    __extends(KingdomsList, _super);
    function KingdomsList(props) {
        var _this = _super.call(this, props) || this;
        _this.tabs = [
            {
                name: "Kingdoms",
                key: "kingdoms",
            },
            {
                name: "Logs",
                key: "kingdom-logs",
                has_logs: false,
            },
        ];
        _this.state = {
            loading: true,
            dark_tables: false,
            selected_kingdom: null,
            selected_log: null,
        };
        return _this;
    }
    KingdomsList.prototype.componentDidMount = function () {
        watchForDarkModeTableChange(this);
        var self = this;
        setTimeout(function () {
            self.setState({
                loading: false,
            });
        }, 500);
        this.updateIcon();
    };
    KingdomsList.prototype.componentDidUpdate = function () {
        var _this = this;
        var foundKingdom = this.props.my_kingdoms.filter(function (kingdom) {
            if (_this.state.selected_kingdom === null) {
                return;
            }
            return kingdom.id === _this.state.selected_kingdom.id;
        });
        if (foundKingdom.length > 0) {
            var kingdom = foundKingdom[0];
            if (!isEqual(kingdom, this.state.selected_kingdom)) {
                this.setState({
                    selected_kingdom: kingdom,
                });
            }
        }
        this.updateIcon();
    };
    KingdomsList.prototype.updateIcon = function () {
        if (this.props.logs.length > 0) {
            var hasUnReadLogs = this.props.logs.filter(function (log) {
                return !log.opened;
            });
            if (hasUnReadLogs.length > 0) {
                this.tabs[this.tabs.length - 1].has_logs = true;
            } else {
                this.tabs[this.tabs.length - 1].has_logs = false;
            }
        }
    };
    KingdomsList.prototype.viewKingdomDetails = function (kingdom) {
        this.setState({
            selected_kingdom: kingdom,
        });
    };
    KingdomsList.prototype.viewLogs = function (log) {
        var _this = this;
        if (!log.opened) {
            new Ajax()
                .setRoute(
                    "kingdom/opened-log/" + log.character_id + "/" + log.id,
                )
                .doAjaxCall(
                    "post",
                    function (result) {
                        _this.setState({
                            selected_log: log,
                        });
                    },
                    function (error) {
                        console.error(error);
                    },
                );
        } else {
            this.setState({
                selected_log: log,
            });
        }
    };
    KingdomsList.prototype.deleteLog = function (log) {
        new Ajax()
            .setRoute("kingdom/delete-log/" + log.character_id + "/" + log.id)
            .doAjaxCall(
                "post",
                function (result) {},
                function (error) {
                    console.error(error);
                },
            );
    };
    KingdomsList.prototype.closeKingdomDetails = function () {
        this.setState({
            selected_kingdom: null,
        });
    };
    KingdomsList.prototype.closeLogDetails = function () {
        this.setState({
            selected_log: null,
        });
    };
    KingdomsList.prototype.render = function () {
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            Fragment,
            null,
            this.props.is_dead
                ? React.createElement(
                      DangerAlert,
                      { additional_css: "my-4" },
                      "Christ child! You are dead. Dead people cannot do a lot of things including: Manage inventory, Manage Skills - including passives, Manage Boons or even use items. And they cannot manage their kingdoms! How sad! Go resurrect child! (head to Game tab and click Revive).",
                  )
                : null,
            this.state.selected_kingdom !== null
                ? this.props.view_port < 1600
                    ? React.createElement(SmallKingdom, {
                          close_details: this.closeKingdomDetails.bind(this),
                          kingdom: this.state.selected_kingdom,
                          dark_tables: this.state.dark_tables,
                          character_gold: this.props.character_gold,
                          view_port: this.props.view_port,
                          user_id: this.props.user_id,
                          kingdoms: this.props.my_kingdoms,
                      })
                    : React.createElement(Kingdom, {
                          close_details: this.closeKingdomDetails.bind(this),
                          kingdom: this.state.selected_kingdom,
                          kingdoms: this.props.my_kingdoms,
                          dark_tables: this.state.dark_tables,
                          character_gold: this.props.character_gold,
                          view_port: this.props.view_port,
                          user_id: this.props.user_id,
                      })
                : this.state.selected_log !== null
                  ? React.createElement(KingdomLogDetailsView, {
                        close_details: this.closeLogDetails.bind(this),
                        log: this.state.selected_log,
                    })
                  : React.createElement(
                        BasicCard,
                        { additionalClasses: "overflow-x-auto" },
                        React.createElement(
                            Tabs,
                            { tabs: this.tabs, icon_key: "has_logs" },
                            React.createElement(
                                TabPanel,
                                { key: "kingdoms" },
                                this.props.my_kingdoms.length > 0
                                    ? React.createElement(
                                          "div",
                                          {
                                              className:
                                                  "max-w-[390px] md:max-w-full overflow-x-hidden",
                                          },
                                          React.createElement(Table, {
                                              data: this.props.my_kingdoms,
                                              columns: buildKingdomsColumns(
                                                  this.viewKingdomDetails.bind(
                                                      this,
                                                  ),
                                              ),
                                              dark_table:
                                                  this.state.dark_tables,
                                          }),
                                      )
                                    : React.createElement(
                                          Fragment,
                                          null,
                                          React.createElement(
                                              "p",
                                              { className: "my-4 text-center" },
                                              "No Settled Kingdoms.",
                                          ),
                                          React.createElement(
                                              "p",
                                              { className: "text-center" },
                                              React.createElement(
                                                  "a",
                                                  {
                                                      href: "/information/kingdoms",
                                                      target: "_blank",
                                                  },
                                                  "What are and how to get kingdoms.",
                                                  " ",
                                                  React.createElement("i", {
                                                      className:
                                                          "fas fa-external-link-alt",
                                                  }),
                                              ),
                                          ),
                                      ),
                            ),
                            React.createElement(
                                TabPanel,
                                { key: "kingdom-logs" },
                                this.props.logs.length > 0
                                    ? React.createElement(
                                          "div",
                                          {
                                              className:
                                                  "max-w-[390px] md:max-w-full overflow-x-hidden",
                                          },
                                          React.createElement(Table, {
                                              data: this.props.logs,
                                              columns: buildLogsColumns(
                                                  this.viewLogs.bind(this),
                                                  this.deleteLog.bind(this),
                                              ),
                                              dark_table:
                                                  this.state.dark_tables,
                                          }),
                                      )
                                    : React.createElement(
                                          "p",
                                          { className: "my-4 text-center" },
                                          "No Logs.",
                                      ),
                            ),
                        ),
                    ),
        );
    };
    return KingdomsList;
})(React.Component);
export default KingdomsList;
//# sourceMappingURL=kingdoms-list.js.map
