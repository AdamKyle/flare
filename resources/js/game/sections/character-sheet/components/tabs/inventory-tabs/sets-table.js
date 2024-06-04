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
import DropDown from "../../../../../components/ui/drop-down/drop-down";
import DangerButton from "../../../../../components/ui/buttons/danger-button";
import LoadingProgressBar from "../../../../../components/ui/progress-bars/loading-progress-bar";
import Ajax from "../../../../../lib/ajax/ajax";
import SuccessAlert from "../../../../../components/ui/alerts/simple-alerts/success-alert";
import { isEqual } from "lodash";
import WarningAlert from "../../../../../components/ui/alerts/simple-alerts/warning-alert";
import RenameSetModal from "../../modals/rename-set-modal";
import clsx from "clsx";
import InventoryUseDetails from "../../modals/inventory-item-details";
import DangerAlert from "../../../../../components/ui/alerts/simple-alerts/danger-alert";
var SetsTable = (function (_super) {
    __extends(SetsTable, _super);
    function SetsTable(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            data: [],
            drop_down_labels: [],
            selected_set: null,
            selected_set_index: null,
            loading: false,
            success_message: null,
            show_rename_set: false,
            search_string: "",
            item_id: null,
            view_item: false,
            loading_label: null,
            show_loading_label: false,
            error_message: null,
        };
        return _this;
    }
    SetsTable.prototype.componentDidMount = function () {
        this.setSetData(this.props.sets);
    };
    SetsTable.prototype.componentDidUpdate = function (
        prevProps,
        prevState,
        snapshot,
    ) {
        if (
            this.state.selected_set !== null &&
            this.state.search_string.length === 0
        ) {
            if (
                !isEqual(
                    this.props.sets[this.state.selected_set].items,
                    this.state.data,
                )
            ) {
                this.setState({
                    data: this.props.sets[this.state.selected_set].items,
                });
            }
        }
    };
    SetsTable.prototype.setSetData = function (sets) {
        var setKeys = Object.keys(sets);
        var data = sets[setKeys[0]].items;
        var setIndex =
            this.state.selected_set_index === null
                ? 0
                : this.state.selected_set_index;
        var selectedSet = "";
        if (this.state.selected_set === null) {
            selectedSet = setKeys[0];
            setIndex = 0;
            var _loop_1 = function (i) {
                if (sets[setKeys[i]].equipped) {
                    setIndex = setKeys.findIndex(function (setKey) {
                        return setKey === setKeys[i];
                    });
                    selectedSet = setKeys[setIndex];
                }
            };
            for (var i = 0; i < setKeys.length; i++) {
                _loop_1(i);
            }
        } else {
            selectedSet = setKeys[setIndex];
        }
        this.setState({
            data: data,
            drop_down_labels: setKeys,
            selected_set: selectedSet,
            selected_set_index: setIndex,
        });
    };
    SetsTable.prototype.actions = function (row) {
        var _this = this;
        return React.createElement(DangerButton, {
            button_label: "Remove",
            on_click: function () {
                return _this.removeFromSet(row.slot_id);
            },
            disabled: this.buttonsDisabled(),
        });
    };
    SetsTable.prototype.emptySet = function () {
        var _this = this;
        var setId = this.props.sets[this.state.selected_set].set_id;
        this.setState(
            {
                loading: true,
                error_message: null,
                success_message: null,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/inventory-set/" +
                            setId +
                            "/remove-all",
                    )
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
                            _this.setState({ loading: false });
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
    SetsTable.prototype.equipSet = function () {
        var _this = this;
        var setId = this.props.savable_sets.filter(function (set) {
            return set.name === _this.state.selected_set;
        });
        if (setId.length > 0) {
            setId = setId[0].id;
        }
        this.setState(
            {
                loading: true,
                show_loading_label: true,
                loading_label:
                    "Equipping set and recalculating your stats (this can take a few seconds) ...",
                error_message: null,
                success_message: null,
            },
            function () {
                _this.props.disable_tabs();
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/inventory-set/equip/" +
                            setId,
                    )
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
                            _this.setState({ loading: false });
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
    SetsTable.prototype.removeFromSet = function (id) {
        var _this = this;
        if (this.state.selected_set !== null) {
            var setId_1 = this.props.sets[this.state.selected_set].set_id;
            this.setState(
                {
                    loading: true,
                },
                function () {
                    new Ajax()
                        .setRoute(
                            "character/" +
                                _this.props.character_id +
                                "/inventory-set/remove",
                        )
                        .setParameters({
                            inventory_set_id: setId_1,
                            slot_id: id,
                        })
                        .doAjaxCall(
                            "post",
                            function (result) {
                                _this.setState(
                                    {
                                        loading: false,
                                        success_message: result.data.message,
                                        search_string: "",
                                    },
                                    function () {
                                        _this.props.update_inventory(
                                            result.data.inventory,
                                        );
                                    },
                                );
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
                },
            );
        }
    };
    SetsTable.prototype.renameSet = function (name) {
        var _this = this;
        var setNames = Object.keys(this.props.sets);
        var foundName = setNames.filter(function (name) {
            return name === _this.state.selected_set;
        });
        var setId = this.props.sets[foundName[0]].set_id;
        this.setState(
            {
                loading: true,
                error_message: null,
                success_message: null,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/inventory-set/rename-set",
                    )
                    .setParameters({
                        set_id: setId,
                        set_name: name,
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
                                    _this.setSetData(
                                        result.data.inventory.sets,
                                    );
                                    _this.props.update_inventory(
                                        result.data.inventory,
                                    );
                                },
                            );
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    loading: false,
                                    error_message: response.data.message,
                                });
                            }
                        },
                    );
            },
        );
    };
    SetsTable.prototype.switchTable = function (set) {
        var data = this.props.sets[set].items;
        var keys = Object.keys(this.props.sets);
        var index = keys.indexOf(set, 0);
        this.setState({
            data: data,
            selected_set: set,
            selected_set_index: index !== -1 ? index : 0,
        });
    };
    SetsTable.prototype.buildMenuItems = function () {
        var _this = this;
        return this.state.drop_down_labels.map(function (label) {
            return {
                name: label,
                icon_class: clsx("ra ra-crossed-swords", {
                    "text-yellow-600": _this.cannotEquipSet(label),
                }),
                on_click: function () {
                    return _this.switchTable(label);
                },
            };
        });
    };
    SetsTable.prototype.manageRenameSet = function () {
        this.setState({
            show_rename_set: !this.state.show_rename_set,
        });
    };
    SetsTable.prototype.buildActionsDropDown = function () {
        var _this = this;
        var actions = [];
        actions.push({
            name: "Rename set",
            icon_class: "fas fa-edit",
            on_click: function () {
                return _this.manageRenameSet();
            },
        });
        if (this.state.selected_set !== null) {
            if (
                this.state.selected_set !== this.props.set_name_equipped &&
                this.props.sets[this.state.selected_set].items.length > 0
            ) {
                actions.push({
                    name: "Empty set",
                    icon_class: "fas fa-eraser",
                    on_click: function () {
                        return _this.emptySet();
                    },
                });
                if (
                    !this.cannotEquipSet() &&
                    !this.props.is_automation_running
                ) {
                    actions.push({
                        name: "Equip set",
                        icon_class: "ra ra-muscle-fat",
                        on_click: function () {
                            return _this.equipSet();
                        },
                    });
                }
            }
        }
        return actions;
    };
    SetsTable.prototype.search = function (e) {
        var value = e.target.value;
        var data = this.props.sets[this.state.selected_set].items.filter(
            function (item) {
                return (
                    item.item_name.includes(value) || item.type.includes(value)
                );
            },
        );
        this.setState({
            data: data,
            search_string: value,
        });
    };
    SetsTable.prototype.buttonsDisabled = function () {
        if (this.state.selected_set !== null) {
            return (
                this.props.sets[this.state.selected_set].equipped ||
                this.props.is_dead ||
                this.props.is_automation_running ||
                this.state.loading
            );
        }
        return true;
    };
    SetsTable.prototype.cannotEquipSet = function (setName) {
        if (this.state.selected_set !== null) {
            if (typeof setName !== "undefined") {
                return !this.props.sets[setName].equippable;
            }
            return !this.props.sets[this.state.selected_set].equippable;
        }
        return false;
    };
    SetsTable.prototype.clearSuccessMessage = function () {
        this.setState({
            success_message: null,
        });
    };
    SetsTable.prototype.clearErrorMessage = function () {
        this.setState({
            error_message: null,
        });
    };
    SetsTable.prototype.buildSetTitle = function () {
        if (this.state.selected_set !== null) {
            return "Viewing: " + this.state.selected_set + ".";
        }
        return null;
    };
    SetsTable.prototype.viewItem = function (item) {
        this.setState({
            item_id: typeof item !== "undefined" ? item.item_id : null,
            view_item: !this.state.view_item,
        });
    };
    SetsTable.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      {
                          close_alert: this.clearSuccessMessage.bind(this),
                          additional_css: "mt-4 mb-4",
                      },
                      this.state.success_message,
                  )
                : null,
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      {
                          close_alert: this.clearErrorMessage.bind(this),
                          additional_css: "mt-4 mb-4",
                      },
                      this.state.error_message,
                  )
                : null,
            this.cannotEquipSet()
                ? React.createElement(
                      WarningAlert,
                      { additional_css: "mb-4" },
                      "Cannot equip set because it violates the",
                      " ",
                      React.createElement(
                          "a",
                          {
                              href: "/information/equipment-sets",
                              target: "_blank",
                          },
                          "set ",
                          React.createElement("i", {
                              className: "fas fa-external-link-alt",
                          }),
                      ),
                      " ",
                      "rules. You can still treat this set like a stash tab.",
                  )
                : null,
            this.buildSetTitle() !== null
                ? React.createElement(
                      "div",
                      null,
                      React.createElement(
                          "h4",
                          { className: "text-orange-500 dark:text-orange-400" },
                          this.buildSetTitle(),
                      ),
                  )
                : null,
            React.createElement(
                "div",
                { className: "flex flex-row flex-wrap items-center" },
                React.createElement(
                    "div",
                    null,
                    React.createElement(DropDown, {
                        menu_items: this.buildMenuItems(),
                        button_title: "Sets",
                        selected_name: this.state.selected_set,
                        secondary_selected: this.props.set_name_equipped,
                        disabled: this.props.is_dead || this.state.loading,
                    }),
                ),
                React.createElement(
                    "div",
                    { className: "ml-2" },
                    React.createElement(DropDown, {
                        menu_items: this.buildActionsDropDown(),
                        button_title: "Actions",
                        disabled: this.props.is_dead || this.state.loading,
                    }),
                ),
                React.createElement(
                    "div",
                    {
                        className:
                            "sm:ml-4 md:ml-0 my-4 md:my-0 md:absolute md:right-[10px]",
                    },
                    React.createElement("input", {
                        type: "text",
                        name: "search",
                        className: "form-control",
                        onChange: this.search.bind(this),
                        placeholder: "Search",
                        value: this.state.search_string,
                    }),
                ),
            ),
            this.state.loading
                ? React.createElement(LoadingProgressBar, {
                      show_label: this.state.show_loading_label,
                      label: this.state.loading_label,
                  })
                : null,
            this.state.show_rename_set && this.state.selected_set !== null
                ? React.createElement(RenameSetModal, {
                      is_open: this.state.show_rename_set,
                      manage_modal: this.manageRenameSet.bind(this),
                      title: "Rename Set",
                      current_set_name: this.state.selected_set,
                      rename_set: this.renameSet.bind(this),
                  })
                : null,
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
                    ),
                    dark_table: this.props.dark_tables,
                }),
            ),
        );
    };
    return SetsTable;
})(React.Component);
export default SetsTable;
//# sourceMappingURL=sets-table.js.map
