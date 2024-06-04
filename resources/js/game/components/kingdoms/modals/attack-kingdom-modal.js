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
import { parseInt } from "lodash";
import React, { Fragment } from "react";
import Select from "react-select";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import Dialogue from "../../../components/ui/dialogue/dialogue";
import ComponentLoading from "../../../components/ui/loading/component-loading";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import TabPanel from "../../../components/ui/tabs/tab-panel";
import Tabs from "../../../components/ui/tabs/tabs";
import Ajax from "../../../lib/ajax/ajax";
import UnitMovement from "./partials/unit-movement";
var AttackKingdomModal = (function (_super) {
    __extends(AttackKingdomModal, _super);
    function AttackKingdomModal(props) {
        var _this = _super.call(this, props) || this;
        _this.tabs = [
            {
                key: "use-items",
                name: "Use items",
            },
            {
                key: "send-units",
                name: "Send Units",
            },
        ];
        _this.state = {
            loading: false,
            fetching_data: true,
            items_to_use: [],
            kingdoms: [],
            error_message: "",
            success_message: "",
            selected_kingdoms: [],
            selected_units: [],
            selected_items: [],
            total_damage: 0,
            total_reduction: 0,
            show_help_modal: false,
            help_type: "",
        };
        return _this;
    }
    AttackKingdomModal.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "fetch-attacking-data/" +
                    this.props.kingdom_to_attack_id +
                    "/" +
                    this.props.character_id,
            )
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        items_to_use: result.data.items_to_use,
                        kingdoms: result.data.kingdoms,
                        fetching_data: false,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    AttackKingdomModal.prototype.setAmountToMove = function (selectedUnits) {
        this.setState({
            selected_units: selectedUnits,
        });
    };
    AttackKingdomModal.prototype.setKingdoms = function (kingdomsSelected) {
        this.setState({
            selected_kingdoms: kingdomsSelected,
        });
    };
    AttackKingdomModal.prototype.attackKingdom = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                success_message: "",
                error_message: "",
            },
            function () {
                new Ajax()
                    .setRoute(
                        "attack-kingdom-with-units/" +
                            _this.props.kingdom_to_attack_id +
                            "/" +
                            _this.props.character_id,
                    )
                    .setParameters({
                        units_to_move: _this.state.selected_units,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                },
                                function () {
                                    _this.props.handle_close();
                                },
                            );
                        },
                        function (error) {
                            _this.setState({ loading: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                var message = response.data.message;
                                if (response.data.error) {
                                    message = response.data.error;
                                }
                                _this.setState({
                                    loading: false,
                                    error_message: message,
                                });
                            }
                        },
                    );
            },
        );
    };
    AttackKingdomModal.prototype.manageShowHelpDialogue = function (type) {
        this.setState({
            show_help_modal: !this.state.show_help_modal,
            help_type: type,
        });
    };
    AttackKingdomModal.prototype.useItemsOnKingdom = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                success_message: "",
                error_message: "",
            },
            function () {
                new Ajax()
                    .setRoute(
                        "drop-items-on-kingdom/" +
                            _this.props.kingdom_to_attack_id +
                            "/" +
                            _this.props.character_id,
                    )
                    .setParameters({
                        slots: _this.state.selected_items,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                loading: false,
                                success_message: result.data.message,
                            });
                        },
                        function (error) {
                            _this.setState({ loading: false });
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    success_message: response.data.message,
                                });
                            }
                            console.error(error);
                        },
                    );
            },
        );
    };
    AttackKingdomModal.prototype.buildItemsSelection = function () {
        return this.state.items_to_use.map(function (slot) {
            return {
                label: slot.item.affix_name,
                value: slot.id.toString(),
            };
        });
    };
    AttackKingdomModal.prototype.setItemsToUse = function (data) {
        var _this = this;
        var selectedItems = [];
        var damage = 0;
        data.forEach(function (selected) {
            if (selected.value !== "Please select one or more items") {
                var id_1 = parseInt(selected.value, 10) || 0;
                if (id_1 !== 0) {
                    var foundItem = _this.state.items_to_use.filter(
                        function (slot) {
                            return slot.id === id_1;
                        },
                    );
                    if (foundItem.length > 0) {
                        damage += foundItem[0].item.kingdom_damage;
                        selectedItems.push(id_1);
                    }
                }
            }
        });
        var reduction = 0;
        if (this.props.kingdom_defence > 1) {
            var defence = this.props.kingdom_defence - 1;
            reduction = defence / 5;
            if (reduction < 0.05) {
                reduction = 0.05;
            }
            damage -= reduction;
        }
        this.setState({
            selected_items: selectedItems,
            total_damage: damage,
            total_reduction: reduction,
        });
    };
    AttackKingdomModal.prototype.getSelectedItems = function () {
        var _this = this;
        var selectedItems = this.state.items_to_use.filter(function (item) {
            if (_this.state.selected_items.includes(item.id)) {
                return item;
            }
        });
        if (selectedItems.length > 0) {
            return selectedItems.map(function (slot) {
                return {
                    label: slot.item.affix_name,
                    value: slot.id.toString(),
                };
            });
        }
        return [
            {
                label: "Please select one or more items",
                value: "Please select one or more items",
            },
        ];
    };
    AttackKingdomModal.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Dialogue,
            {
                is_open: this.props.is_open,
                handle_close: this.props.handle_close,
                title: "Attack Kingdom",
                primary_button_disabled: this.state.loading,
                secondary_actions: {
                    secondary_button_disabled:
                        this.state.selected_units.length === 0 ||
                        this.state.loading,
                    secondary_button_label: "Send Units",
                    handle_action: this.attackKingdom.bind(this),
                },
                tertiary_actions: {
                    tertiary_button_disabled:
                        this.state.selected_items.length === 0 ||
                        this.state.loading,
                    tertiary_button_label: "Use items",
                    handle_action: this.useItemsOnKingdom.bind(this),
                },
            },
            this.state.fetching_data
                ? React.createElement(
                      "div",
                      { className: "py-4" },
                      React.createElement(ComponentLoading, null),
                  )
                : React.createElement(
                      Fragment,
                      null,
                      this.state.success_message !== ""
                          ? React.createElement(
                                SuccessAlert,
                                null,
                                this.state.success_message,
                            )
                          : null,
                      this.state.error_message !== ""
                          ? React.createElement(
                                DangerAlert,
                                null,
                                this.state.error_message,
                            )
                          : null,
                      React.createElement(
                          Tabs,
                          { tabs: this.tabs, disabled: this.state.loading },
                          React.createElement(
                              TabPanel,
                              { key: "use-items" },
                              React.createElement(
                                  Fragment,
                                  null,
                                  React.createElement(Select, {
                                      onChange: this.setItemsToUse.bind(this),
                                      isMulti: true,
                                      options: this.buildItemsSelection(),
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
                                      value: this.getSelectedItems(),
                                  }),
                                  React.createElement(
                                      "div",
                                      { className: "my-4" },
                                      React.createElement(
                                          "dl",
                                          null,
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Total Damage",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              React.createElement(
                                                  "div",
                                                  {
                                                      className:
                                                          "flex items-center mb-4",
                                                  },
                                                  (
                                                      this.state.total_damage *
                                                      100
                                                  ).toFixed(2),
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
                                                                  onClick:
                                                                      function () {
                                                                          return _this.manageShowHelpDialogue(
                                                                              "total_damage",
                                                                          );
                                                                      },
                                                                  className:
                                                                      "text-blue-500 dark:text-blue-300",
                                                              },
                                                              React.createElement(
                                                                  "i",
                                                                  {
                                                                      className:
                                                                          "fas fa-info-circle",
                                                                  },
                                                              ),
                                                              " ",
                                                              "Help",
                                                          ),
                                                      ),
                                                  ),
                                              ),
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Building Damage",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              React.createElement(
                                                  "div",
                                                  {
                                                      className:
                                                          "flex items-center mb-4",
                                                  },
                                                  (
                                                      (this.state.total_damage /
                                                          2) *
                                                      100
                                                  ).toFixed(2),
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
                                                                  onClick:
                                                                      function () {
                                                                          return _this.manageShowHelpDialogue(
                                                                              "building_damage",
                                                                          );
                                                                      },
                                                                  className:
                                                                      "text-blue-500 dark:text-blue-300",
                                                              },
                                                              React.createElement(
                                                                  "i",
                                                                  {
                                                                      className:
                                                                          "fas fa-info-circle",
                                                                  },
                                                              ),
                                                              " ",
                                                              "Help",
                                                          ),
                                                      ),
                                                  ),
                                              ),
                                          ),
                                          React.createElement(
                                              "dt",
                                              null,
                                              "Unit Damage",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              React.createElement(
                                                  "div",
                                                  {
                                                      className:
                                                          "flex items-center mb-4",
                                                  },
                                                  (
                                                      (this.state.total_damage /
                                                          2) *
                                                      100
                                                  ).toFixed(2),
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
                                                                  onClick:
                                                                      function () {
                                                                          return _this.manageShowHelpDialogue(
                                                                              "unit_damage",
                                                                          );
                                                                      },
                                                                  className:
                                                                      "text-blue-500 dark:text-blue-300",
                                                              },
                                                              React.createElement(
                                                                  "i",
                                                                  {
                                                                      className:
                                                                          "fas fa-info-circle",
                                                                  },
                                                              ),
                                                              " ",
                                                              "Help",
                                                          ),
                                                      ),
                                                  ),
                                              ),
                                          ),
                                          React.createElement(
                                              "dt",
                                              {
                                                  className:
                                                      "text-red-600 dark:text-red-400",
                                              },
                                              "Defending Kingdom Reduction",
                                          ),
                                          React.createElement(
                                              "dd",
                                              null,
                                              React.createElement(
                                                  "div",
                                                  {
                                                      className:
                                                          "flex items-center mb-4",
                                                  },
                                                  (
                                                      this.state
                                                          .total_reduction * 100
                                                  ).toFixed(2),
                                                  " ",
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
                                                                  onClick:
                                                                      function () {
                                                                          return _this.manageShowHelpDialogue(
                                                                              "total_reduction",
                                                                          );
                                                                      },
                                                                  className:
                                                                      "text-blue-500 dark:text-blue-300",
                                                              },
                                                              React.createElement(
                                                                  "i",
                                                                  {
                                                                      className:
                                                                          "fas fa-info-circle",
                                                                  },
                                                              ),
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
                          ),
                          React.createElement(
                              TabPanel,
                              { key: "send-units" },
                              React.createElement(UnitMovement, {
                                  update_units_selected:
                                      this.setAmountToMove.bind(this),
                                  kingdoms: this.state.kingdoms,
                                  update_kingdoms_selected:
                                      this.setKingdoms.bind(this),
                              }),
                          ),
                      ),
                      this.state.loading
                          ? React.createElement(LoadingProgressBar, null)
                          : null,
                      this.state.show_help_modal
                          ? React.createElement(KingdomHelpModal, {
                                manage_modal:
                                    this.manageShowHelpDialogue.bind(this),
                                type: this.state.help_type,
                            })
                          : null,
                  ),
        );
    };
    return AttackKingdomModal;
})(React.Component);
export default AttackKingdomModal;
//# sourceMappingURL=attack-kingdom-modal.js.map
