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
import PledgeLoyalty from "../../../components/faction-loyalty/modals/pledge-loyalty";
import InfoAlert from "../../../components/ui/alerts/simple-alerts/info-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerButton from "../../../components/ui/buttons/danger-button";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import Table from "../../../components/ui/data-tables/table";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
import { watchForDarkModeTableChange } from "../../../lib/game/dark-mode-watcher";
import { formatNumber } from "../../../lib/game/format-number";
var CharacterFactions = (function (_super) {
    __extends(CharacterFactions, _super);
    function CharacterFactions(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            loading: true,
            factions: [],
            dark_tables: false,
            pledge_faction: null,
            success_message: null,
            pledging: false,
        };
        return _this;
    }
    CharacterFactions.prototype.componentDidMount = function () {
        var _this = this;
        watchForDarkModeTableChange(this);
        if (this.props.character_id !== null && this.props.finished_loading) {
            new Ajax()
                .setRoute(
                    "character-sheet/" + this.props.character_id + "/factions",
                )
                .doAjaxCall(
                    "get",
                    function (result) {
                        _this.setState({
                            loading: false,
                            factions: result.data.factions,
                        });
                    },
                    function (error) {
                        console.error(error);
                    },
                );
        }
    };
    CharacterFactions.prototype.handlePledge = function (pledging) {
        var _this = this;
        if (this.props.update_pledge_tab) {
            this.setState(
                {
                    pledging: true,
                },
                function () {
                    var factionId = _this.props.pledged_faction_id;
                    if (!factionId || _this.state.pledge_faction !== null) {
                        factionId = _this.state.pledge_faction.id;
                    }
                    new Ajax()
                        .setRoute(
                            "faction-loyalty/" +
                                (pledging ? "pledge" : "remove-pledge") +
                                "/" +
                                _this.props.character_id +
                                "/" +
                                factionId,
                        )
                        .doAjaxCall(
                            "post",
                            function (result) {
                                _this.closePledge();
                                _this.setState(
                                    {
                                        success_message: result.data.message,
                                        pledging: false,
                                    },
                                    function () {
                                        _this.props.update_pledge_tab(
                                            pledging,
                                            pledging ? factionId : null,
                                        );
                                        _this.props.update_faction_action_tasks(
                                            null,
                                        );
                                    },
                                );
                            },
                            function (error) {
                                _this.setState({
                                    pledging: false,
                                });
                                console.error(error);
                            },
                        );
                },
            );
        }
    };
    CharacterFactions.prototype.buildColumns = function () {
        var _this = this;
        return [
            {
                name: "Name",
                selector: function (row) {
                    return row.name;
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
                        row.map_name,
                    );
                },
            },
            {
                name: "Title",
                selector: function (row) {
                    return row.title;
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
                        row.title !== null ? row.title : "N/A",
                    );
                },
            },
            {
                name: "Level",
                selector: function (row) {
                    return row.current_level;
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
                        row.current_level,
                    );
                },
            },
            {
                name: "Points",
                selector: function (row) {
                    return row.points_needed;
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
                        formatNumber(row.current_points),
                        " /",
                        " ",
                        formatNumber(row.points_needed),
                    );
                },
            },
            {
                name: "Pledge Loyalty",
                selector: function (row) {
                    return row.id;
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
                        _this.props.is_pledged &&
                            _this.props.pledged_faction_id === row.id
                            ? React.createElement(DangerButton, {
                                  button_label: "Un-pledge",
                                  on_click: function () {
                                      return _this.handlePledge(false);
                                  },
                              })
                            : React.createElement(PrimaryButton, {
                                  button_label: "Pledge Loyalty",
                                  on_click: function () {
                                      _this.pledgeLoyalty(row);
                                  },
                                  disabled: !row.maxed,
                              }),
                    );
                },
            },
        ];
    };
    CharacterFactions.prototype.pledgeLoyalty = function (row) {
        this.setState({
            pledge_faction: row,
        });
    };
    CharacterFactions.prototype.closePledge = function () {
        this.setState({
            pledge_faction: null,
        });
    };
    CharacterFactions.prototype.render = function () {
        var _this = this;
        if (this.state.loading) {
            return React.createElement(LoadingProgressBar, null);
        }
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "my-5" },
                this.state.factions.length > 0
                    ? React.createElement(
                          InfoAlert,
                          { additional_css: "mb-4" },
                          "This tab does not update in real time. You can switch tabs to get the latest data. You can learn more about",
                          " ",
                          React.createElement(
                              "a",
                              {
                                  href: "/information/factions",
                                  target: "_blank",
                              },
                              "Factions",
                              " ",
                              React.createElement("i", {
                                  className: "fas fa-external-link-alt",
                              }),
                          ),
                          " ",
                          "in the help docs. Players who reach the max level (5) of a faction can then",
                          " ",
                          React.createElement(
                              "a",
                              {
                                  href: "/information/faction-loyalty",
                                  target: "_blank",
                              },
                              "Pledge their loyalty",
                          ),
                          ".",
                      )
                    : null,
                this.state.success_message !== null
                    ? React.createElement(
                          SuccessAlert,
                          { additional_css: "mb-4" },
                          this.state.success_message,
                      )
                    : null,
                this.state.pledge_faction === null && this.state.pledging
                    ? React.createElement(
                          "div",
                          { className: "mb-4" },
                          React.createElement(LoadingProgressBar, null),
                      )
                    : null,
                React.createElement(
                    "div",
                    {
                        className:
                            "max-w-[390px] md:max-w-full overflow-x-hidden",
                    },
                    React.createElement(Table, {
                        columns: this.buildColumns(),
                        data: this.state.factions,
                        dark_table: this.state.dark_tables,
                    }),
                ),
            ),
            this.state.pledge_faction !== null
                ? React.createElement(PledgeLoyalty, {
                      is_open: true,
                      manage_modal: this.closePledge.bind(this),
                      faction: this.state.pledge_faction,
                      handle_pledge: function () {
                          return _this.handlePledge(true);
                      },
                      pledging: this.state.pledging,
                  })
                : null,
        );
    };
    return CharacterFactions;
})(React.Component);
export default CharacterFactions;
//# sourceMappingURL=character-factions.js.map
