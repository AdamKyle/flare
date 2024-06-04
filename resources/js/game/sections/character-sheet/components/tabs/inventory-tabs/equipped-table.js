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
import { BuildInventoryTableColumns } from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../../lib/ajax/ajax";
import { isEqual } from "lodash";
import SuccessAlert from "../../../../../components/ui/alerts/simple-alerts/success-alert";
import InventoryUseDetails from "../../modals/inventory-item-details";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
var EquippedTable = (function (_super) {
    __extends(EquippedTable, _super);
    function EquippedTable(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            data: _this.props.equipped_items,
            loading: false,
            search_string: "",
            success_message: null,
            error_message: null,
            item_id: null,
            view_item: false,
        };
        return _this;
    }
    EquippedTable.prototype.componentDidUpdate = function (
        prevProps,
        prevState,
        snapshot,
    ) {
        if (
            !isEqual(prevState.data, this.props.equipped_items) &&
            this.state.search_string.length === 0
        ) {
            this.setState({
                data: this.props.equipped_items,
            });
        }
    };
    EquippedTable.prototype.viewItem = function (item) {
        this.setState({
            item_id: typeof item !== "undefined" ? item.item_id : null,
            view_item: !this.state.view_item,
        });
    };
    EquippedTable.prototype.search = function (e) {
        var value = e.target.value;
        this.setState({
            data: this.props.equipped_items.filter(function (item) {
                return (
                    item.item_name.includes(value) || item.type.includes(value)
                );
            }),
            search_string: value,
        });
    };
    EquippedTable.prototype.actions = function (row) {
        var _this = this;
        return React.createElement(DangerButton, {
            button_label: "Remove",
            on_click: function () {
                return _this.unequip(row.slot_id);
            },
            disabled:
                this.props.is_dead ||
                this.props.is_automation_running ||
                this.state.loading,
        });
    };
    EquippedTable.prototype.assignToSet = function (label) {
        var _this = this;
        this.setState(
            {
                loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/inventory/save-equipped-as-set",
                    )
                    .setParameters({
                        move_to_set: _this.props.sets[label].set_id,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                    success_message: result.data.message,
                                },
                                function () {
                                    _this.props.update_inventory(
                                        result.data.inventory,
                                    );
                                },
                            );
                        },
                        function (error) {},
                    );
            },
        );
    };
    EquippedTable.prototype.hasEmptySet = function () {
        var _this = this;
        if (this.props.is_set_equipped) {
            return false;
        }
        if (this.state.data.length === 0) {
            return false;
        }
        var dropDownLabels = Object.keys(this.props.sets);
        return (
            dropDownLabels.filter(function (key) {
                return _this.props.sets[key].items.length === 0;
            }).length > 0
        );
    };
    EquippedTable.prototype.buildMenuItems = function () {
        var _this = this;
        var dropDownLabels = Object.keys(this.props.sets);
        dropDownLabels = dropDownLabels.filter(function (key) {
            return _this.props.sets[key].items.length === 0;
        });
        return dropDownLabels.map(function (label) {
            return {
                name: label,
                icon_class: "ra ra-crossed-swords",
                on_click: function () {
                    return _this.assignToSet(label);
                },
            };
        });
    };
    EquippedTable.prototype.manageSuccessMessage = function () {
        this.setState({
            success_message: null,
        });
    };
    EquippedTable.prototype.manageErrorMessage = function () {
        this.setState({
            error_message: null,
        });
    };
    EquippedTable.prototype.unequipAll = function () {
        var _this = this;
        this.setState(
            {
                loading: true,
                success_message: null,
                error_message: null,
            },
            function () {
                _this.props.disable_tabs();
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/inventory/unequip-all",
                    )
                    .setParameters({
                        is_set_equipped: _this.props.is_set_equipped,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                    success_message: result.data.message,
                                },
                                function () {
                                    _this.props.update_inventory(
                                        result.data.inventory,
                                    );
                                    _this.props.disable_tabs();
                                },
                            );
                        },
                        function (error) {
                            var response = error.response;
                            if (response) {
                                _this.setState(
                                    {
                                        loading: false,
                                        error_message: response.data.message,
                                    },
                                    function () {
                                        _this.props.disable_tabs();
                                    },
                                );
                            }
                        },
                    );
            },
        );
    };
    EquippedTable.prototype.unequip = function (id) {
        var _this = this;
        this.setState(
            {
                loading: true,
                success_message: null,
                error_message: null,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/inventory/unequip",
                    )
                    .setParameters({
                        inventory_set_equipped: _this.props.is_set_equipped,
                        item_to_remove: id,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState(
                                {
                                    loading: false,
                                    success_message: result.data.message,
                                },
                                function () {
                                    _this.props.update_inventory(
                                        result.data.inventory,
                                    );
                                },
                            );
                        },
                        function (error) {
                            var response = error.response;
                            if (response) {
                                _this.setState(
                                    {
                                        loading: false,
                                        error_message: response.data.message,
                                    },
                                    function () {
                                        _this.props.disable_tabs();
                                    },
                                );
                            }
                        },
                    );
            },
        );
    };
    EquippedTable.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      {
                          additional_css: "mb-4 mt-4",
                          close_alert: this.manageSuccessMessage.bind(this),
                      },
                      this.state.success_message,
                  )
                : null,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      {
                          additional_css: "mb-4 mt-4",
                          close_alert: this.manageErrorMessage.bind(this),
                      },
                      this.state.error_message,
                  )
                : null,
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
                                className: "label block mb-2 mr-3",
                                htmlFor: "search",
                            },
                            "Search",
                        ),
                    ),
                    React.createElement(
                        "div",
                        null,
                        React.createElement("input", {
                            type: "text",
                            name: "search",
                            className: "form-control",
                            onChange: this.search.bind(this),
                            placeholder: "search",
                        }),
                    ),
                    React.createElement(
                        "div",
                        { className: "mt-2 sm:mt-0 sm:ml-2" },
                        React.createElement(DangerButton, {
                            button_label: "Unequip All",
                            on_click: this.unequipAll.bind(this),
                            disabled:
                                this.props.is_dead ||
                                this.state.data.length === 0 ||
                                this.props.is_automation_running ||
                                this.state.loading,
                        }),
                    ),
                    this.hasEmptySet()
                        ? React.createElement(
                              "div",
                              { className: "ml-2 md:mt-0 mt-2" },
                              React.createElement(DropDown, {
                                  menu_items: this.buildMenuItems(),
                                  button_title: "Assign to Set",
                                  disabled:
                                      this.props.is_dead ||
                                      this.props.is_automation_running ||
                                      this.state.loading,
                              }),
                          )
                        : null,
                    this.props.is_set_equipped
                        ? React.createElement(
                              "div",
                              {
                                  className:
                                      "ml-2 text-green-700 dark:text-green-500",
                              },
                              "Set Equipped.",
                          )
                        : null,
                ),
                this.state.loading
                    ? React.createElement(LoadingProgressBar, {
                          show_label: true,
                          label: "Unequipping items and recalculating your stats (this can take a few seconds) ...",
                      })
                    : null,
            ),
            this.state.view_item && this.state.item_id !== null
                ? React.createElement(InventoryUseDetails, {
                      character_id: this.props.character_id,
                      item_id: this.state.item_id,
                      is_open: this.state.view_item,
                      manage_modal: this.viewItem.bind(this),
                  })
                : null,
            React.createElement(
                "div",
                { className: "max-w-full overflow-y-hidden" },
                React.createElement(Table, {
                    data: this.state.data,
                    columns: BuildInventoryTableColumns(
                        this.props.view_port,
                        this,
                        this.viewItem.bind(this),
                        this.props.manage_skills,
                        "equipped",
                    ),
                    dark_table: this.props.dark_tables,
                }),
            ),
        );
    };
    return EquippedTable;
})(React.Component);
export default EquippedTable;
//# sourceMappingURL=equipped-table.js.map
