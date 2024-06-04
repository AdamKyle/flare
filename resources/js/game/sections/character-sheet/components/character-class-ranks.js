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
import Table from "../../../components/ui/data-tables/table";
import { formatNumber } from "../../../lib/game/format-number";
import { watchForDarkModeClassRankChange } from "../../../lib/game/dark-mode-watcher";
import Ajax from "../../../lib/ajax/ajax";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
var CharacterClassRanks = (function (_super) {
    __extends(CharacterClassRanks, _super);
    function CharacterClassRanks(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            class_ranks: [],
            dark_tables: false,
            loading: true,
            open_class_details: false,
            show_class_specialties: false,
            class_name_selected: null,
            switching_class: false,
            success_message: null,
            error_message: null,
        };
        return _this;
    }
    CharacterClassRanks.prototype.componentDidMount = function () {
        var _this = this;
        watchForDarkModeClassRankChange(this);
        new Ajax()
            .setRoute("class-ranks/" + this.props.character.id)
            .doAjaxCall(
                "get",
                function (response) {
                    _this.setState({
                        class_ranks: response.data.class_ranks,
                        loading: false,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    CharacterClassRanks.prototype.manageViewClass = function (className) {
        var classNameSelected = this.state.class_ranks.filter(function (rank) {
            return rank.class_name === className;
        })[0];
        this.setState({
            open_class_details: !this.state.open_class_details,
            class_name_selected: classNameSelected,
        });
    };
    CharacterClassRanks.prototype.switchClass = function (classId) {
        var _this = this;
        this.setState(
            {
                switching_class: true,
                success_message: null,
                error_message: null,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "switch-classes/" +
                            _this.props.character.id +
                            "/" +
                            classId,
                    )
                    .doAjaxCall(
                        "post",
                        function (response) {
                            _this.setState({
                                switching_class: false,
                                success_message: response.data.message,
                                class_ranks: response.data.class_ranks,
                            });
                        },
                        function (error) {
                            _this.setState({ switching_class: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    error_message:
                                        "Something went wrong switching classes.",
                                });
                            }
                            console.error(error);
                        },
                    );
            },
        );
    };
    CharacterClassRanks.prototype.tableColumns = function () {
        var _this = this;
        return [
            {
                name: "Class name",
                selector: function (row) {
                    return row.class_name;
                },
                cell: function (row) {
                    return React.createElement(
                        "button",
                        {
                            onClick: function () {
                                return _this.manageViewClass(row.class_name);
                            },
                            className:
                                "hover:underline text-blue-500 dark:text-blue-400",
                        },
                        row.class_name,
                    );
                },
            },
            {
                name: "Rank Level",
                selector: function (row) {
                    return row.level;
                },
                sortable: true,
            },
            {
                name: "XP",
                selector: function (row) {
                    return row.current_xp;
                },
                cell: function (row) {
                    return React.createElement(
                        "span",
                        null,
                        formatNumber(row.current_xp) +
                            "/" +
                            formatNumber(row.required_xp),
                    );
                },
            },
            {
                name: "Active",
                selector: function (row) {
                    return row.is_active;
                },
                cell: function (row) {
                    return React.createElement(
                        "span",
                        null,
                        row.is_active ? "Yes" : "No",
                    );
                },
            },
            {
                name: "Is Locked",
                selector: function (row) {
                    return row.is_locked;
                },
                cell: function (row) {
                    return React.createElement(
                        "span",
                        null,
                        row.is_locked ? "Yes" : "No",
                    );
                },
            },
            {
                name: "Action",
                selector: function (row) {
                    return row.id;
                },
                cell: function (row) {
                    return React.createElement(
                        "span",
                        null,
                        React.createElement(PrimaryButton, {
                            button_label: "Switch To",
                            on_click: function () {
                                return _this.switchClass(row.game_class_id);
                            },
                            disabled: row.is_active || row.is_locked,
                        }),
                    );
                },
            },
        ];
    };
    CharacterClassRanks.prototype.masteryTableColumns = function () {
        return [
            {
                name: "Mastery Name",
                selector: function (row) {
                    return row.mastery_name;
                },
            },
            {
                name: "Level",
                selector: function (row) {
                    return row.level;
                },
                sortable: true,
                cell: function (row) {
                    return React.createElement(
                        "span",
                        null,
                        formatNumber(row.level) + "/" + 100,
                    );
                },
            },
            {
                name: "XP",
                selector: function (row) {
                    return row.current_xp;
                },
                cell: function (row) {
                    return React.createElement(
                        "span",
                        null,
                        formatNumber(row.current_xp) +
                            "/" +
                            formatNumber(row.required_xp),
                    );
                },
            },
        ];
    };
    CharacterClassRanks.prototype.manageClassSpecialties = function () {
        this.setState({
            show_class_specialties: !this.state.show_class_specialties,
        });
    };
    CharacterClassRanks.prototype.render = function () {
        var _this = this;
        if (this.state.loading) {
            return React.createElement(
                "div",
                { className: "relative my-6 p-[20px]" },
                React.createElement(LoadingProgressBar, null),
            );
        }
        return React.createElement(
            "div",
            { className: "max-h-[375px] overflow-y-auto" },
            this.state.open_class_details &&
                this.state.class_name_selected !== null
                ? React.createElement(
                      "div",
                      null,
                      React.createElement(
                          "div",
                          {
                              className:
                                  "text-right cursor-pointer text-red-500 relative top-[10px] right-[10px]",
                          },
                          React.createElement(
                              "button",
                              {
                                  onClick: function () {
                                      return _this.manageViewClass(null);
                                  },
                              },
                              React.createElement("i", {
                                  className: "fas fa-minus-circle",
                              }),
                          ),
                      ),
                      React.createElement(
                          "h2",
                          {
                              className:
                                  "text-sky-700 dark:text-sky-500 font-bold my-4",
                          },
                          this.state.class_name_selected.class_name,
                      ),
                      React.createElement(
                          "p",
                          { className: "mb-4" },
                          "To learn more about this class, checkout",
                          " ",
                          React.createElement(
                              "a",
                              {
                                  href:
                                      "/information/class/" +
                                      this.state.class_name_selected
                                          .game_class_id,
                                  target: "_blank",
                              },
                              "the class documentation",
                              " ",
                              React.createElement("i", {
                                  className: "fas fa-external-link-alt",
                              }),
                          ),
                          " ",
                          "to learn more about special attacks and tips and tricks to play the class to the fullest advantage.",
                      ),
                      React.createElement(
                          "p",
                          { className: "mb-4" },
                          "When you switch to this class, your current class skill be hidden and you will now have an opportunity to level this classes skill in the skill section for trainable skills. Click the above link to learn more about the class.",
                      ),
                      React.createElement(
                          "div",
                          { className: "grid lg:grid-cols-2 gap-2 mb-4" },
                          React.createElement(
                              "div",
                              null,
                              React.createElement(
                                  "h3",
                                  { className: "my-3" },
                                  "Base Information",
                              ),
                              React.createElement("div", {
                                  className:
                                      "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                              }),
                              React.createElement(
                                  "dl",
                                  { className: "mb-4" },
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Base Damage Stat",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      this.state.class_name_selected.game_class
                                          .to_hit_stat,
                                  ),
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Accuracy Mod",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      "+",
                                      (
                                          this.state.class_name_selected
                                              .game_class.accuracy_mod * 100
                                      ).toFixed(2),
                                      "%",
                                  ),
                                  React.createElement(
                                      "dt",
                                      null,
                                      "Looting Mod",
                                  ),
                                  React.createElement(
                                      "dd",
                                      null,
                                      "+",
                                      (
                                          this.state.class_name_selected
                                              .game_class.accuracy_mod * 100
                                      ).toFixed(2),
                                      "%",
                                  ),
                              ),
                              this.state.class_name_selected
                                  .secondary_class_name !== null &&
                                  this.state.class_name_selected
                                      .primary_class_name !== null
                                  ? React.createElement(
                                        Fragment,
                                        null,
                                        React.createElement(
                                            "h3",
                                            { className: "my-3" },
                                            "Requirements",
                                        ),
                                        React.createElement("div", {
                                            className:
                                                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                                        }),
                                        React.createElement(
                                            "p",
                                            { className: "mb-2" },
                                            "This class may require you to level other classes before being able to switch to this class. To do so, follow the below information to unlock the class!",
                                        ),
                                        React.createElement(
                                            "dl",
                                            { className: "mb-4" },
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Primary Class Required:",
                                            ),
                                            React.createElement(
                                                "dd",
                                                null,
                                                this.state.class_name_selected
                                                    .primary_class_name,
                                            ),
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Primary Class Rank Level Required:",
                                            ),
                                            React.createElement(
                                                "dd",
                                                null,
                                                this.state.class_name_selected
                                                    .primary_class_required_level,
                                            ),
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Secondary Class Required:",
                                            ),
                                            React.createElement(
                                                "dd",
                                                null,
                                                this.state.class_name_selected
                                                    .secondary_class_name,
                                            ),
                                            React.createElement(
                                                "dt",
                                                null,
                                                "Secondary Class Rank Level Required:",
                                            ),
                                            React.createElement(
                                                "dd",
                                                null,
                                                this.state.class_name_selected
                                                    .secondary_class_required_level,
                                            ),
                                        ),
                                    )
                                  : null,
                          ),
                          React.createElement("div", {
                              className:
                                  "border-b-2 block lg:hidden border-b-gray-300 dark:border-b-gray-600 my-3",
                          }),
                          React.createElement(
                              "div",
                              null,
                              React.createElement(
                                  "h3",
                                  { className: "my-3" },
                                  "Class Masteries",
                              ),
                              React.createElement("div", {
                                  className:
                                      "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                              }),
                              React.createElement(
                                  "p",
                                  { className: "my-4" },
                                  "The more levels you put in, by using a specific weapon type, the more % bonus towards that weapon types damage.",
                              ),
                              React.createElement(Table, {
                                  data: this.state.class_name_selected
                                      .weapon_masteries,
                                  columns: this.masteryTableColumns(),
                                  dark_table: this.state.dark_tables,
                              }),
                          ),
                      ),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      this.state.switching_class
                          ? React.createElement(LoadingProgressBar, null)
                          : null,
                      this.state.error_message !== null
                          ? React.createElement(
                                DangerAlert,
                                { additional_css: "my-4" },
                                this.state.error_message,
                            )
                          : null,
                      this.state.success_message !== null
                          ? React.createElement(
                                SuccessAlert,
                                { additional_css: "my-4" },
                                this.state.success_message,
                            )
                          : null,
                      React.createElement(Table, {
                          data: this.state.class_ranks,
                          columns: this.tableColumns(),
                          dark_table: this.state.dark_tables,
                      }),
                  ),
        );
    };
    return CharacterClassRanks;
})(React.Component);
export default CharacterClassRanks;
//# sourceMappingURL=character-class-ranks.js.map
