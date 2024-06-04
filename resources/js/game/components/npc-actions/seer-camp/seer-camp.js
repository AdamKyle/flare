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
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import Select from "react-select";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import ManageItemSockets from "./actions/manage-item-sockets";
import ManageItemSocketsActions from "./actions/manage-item-sockets-actions";
import DangerButton from "../../ui/buttons/danger-button";
import SeerActions from "./ajax/seer-actions";
import ManageItemSocketsCost from "./actions/manage-item-sockets-cost";
import AddGemsToItem from "./actions/add-gems-to-item";
import AddGemsToItemActions from "./actions/add-gems-to-item-actions";
import ManageGems from "../../../sections/components/gems/manage-gems";
import RemoveGem from "./actions/remove-gem";
var SeerCamp = (function (_super) {
    __extends(SeerCamp, _super);
    function SeerCamp(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            items: [],
            gems: [],
            seer_actions: [
                {
                    label: "Please Select",
                    value: "base",
                },
                {
                    label: "Create/ReRoll Sockets",
                    value: "manage-sockets",
                },
                {
                    label: "Attach Gems",
                    value: "attach-gem",
                },
                {
                    label: "Remove Gem",
                    value: "remove-gem",
                },
            ],
            socket_cost: 2000,
            attach_gem: 500,
            remove_gem: 10,
            item_selected: 0,
            gem_selected: 0,
            is_loading: true,
            trading_with_seer: false,
            error_message: null,
            success_message: null,
            selected_seer_action: null,
            manage_gems_on_item: false,
            manage_remove_gem: false,
        };
        return _this;
    }
    SeerCamp.prototype.componentDidMount = function () {
        SeerActions.handleInitialFetch(this);
    };
    SeerCamp.prototype.doAction = function (action) {
        var _this = this;
        if (action === "close-seer-action") {
            this.setState({
                item_selected: 0,
                gem_selected: 0,
                error_message: null,
                success_message: null,
                selected_seer_action: null,
            });
        }
        if (action === "roll-sockets") {
            this.setState(
                {
                    error_message: null,
                    success_message: null,
                    trading_with_seer: true,
                },
                function () {
                    SeerActions.manageSocketsOnItem(
                        _this,
                        _this.state.item_selected,
                    );
                },
            );
        }
        if (action === "attach-gem") {
            this.setState({
                manage_gems_on_item: true,
            });
        }
        if (action === "remove-gem") {
            this.setState({
                manage_remove_gem: true,
            });
        }
    };
    SeerCamp.prototype.isManageSocketsDisabled = function () {
        if (this.state.selected_seer_action === null) {
            return true;
        }
        if (this.state.item_selected === 0) {
            return true;
        }
        return false;
    };
    SeerCamp.prototype.isAddGemsDisabled = function () {
        if (this.state.selected_seer_action == null) {
            return true;
        }
        if (this.state.item_selected === 0) {
            return true;
        }
        if (this.state.gem_selected === 0) {
            return true;
        }
        return false;
    };
    SeerCamp.prototype.isLoading = function () {
        return this.state.trading_with_seer;
    };
    SeerCamp.prototype.setSeerAction = function (data) {
        if (data.value === "base") {
            this.setState({
                selected_seer_action: null,
            });
            return;
        }
        this.setState({
            selected_seer_action: data.value,
        });
    };
    SeerCamp.prototype.seerActions = function () {
        return this.state.seer_actions;
    };
    SeerCamp.prototype.seerAction = function () {
        var _this = this;
        if (this.state.selected_seer_action === null) {
            return { label: "Please select action", value: "base" };
        }
        return this.state.seer_actions.filter(function (action) {
            return action.value === _this.state.selected_seer_action;
        })[0];
    };
    SeerCamp.prototype.updateParent = function (value, property) {
        this.setState(function (prevState) {
            var _a;
            return __assign(
                __assign({}, prevState),
                ((_a = {}), (_a[property] = value), _a),
            );
        });
    };
    SeerCamp.prototype.getItemInfo = function (key) {
        var _this = this;
        var item = this.state.items.filter(function (item) {
            return item.slot_id === _this.state.item_selected;
        });
        if (item.length > 0) {
            var value = item[0][key];
            return value === null ? 0 : value;
        }
        return null;
    };
    SeerCamp.prototype.buildGemDialogueTitle = function (gemSlotId) {
        var gemSlot = this.state.gems.filter(function (gem) {
            return gem.slot_id === gemSlotId;
        });
        if (gemSlot.length > 0) {
            var gem = gemSlot[0];
            return React.createElement(
                "span",
                { className: "text-lime-600 dark:text-lime-500" },
                gem.name,
            );
        }
        return null;
    };
    SeerCamp.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Fragment,
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
                    this.state.is_loading
                        ? React.createElement(LoadingProgressBar, null)
                        : React.createElement(
                              Fragment,
                              null,
                              React.createElement(Select, {
                                  onChange: this.setSeerAction.bind(this),
                                  options: this.seerActions(),
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
                                  value: this.seerAction(),
                              }),
                              this.state.selected_seer_action ===
                                  "manage-sockets"
                                  ? React.createElement(
                                        "div",
                                        { className: "mt-3 mb-2" },
                                        React.createElement(ManageItemSockets, {
                                            items: this.state.items,
                                            update_parent:
                                                this.updateParent.bind(this),
                                        }),
                                    )
                                  : null,
                              this.state.selected_seer_action === "attach-gem"
                                  ? React.createElement(
                                        "div",
                                        { className: "mt-3 mb-2" },
                                        React.createElement(AddGemsToItem, {
                                            items: this.state.items,
                                            gems: this.state.gems,
                                            update_parent:
                                                this.updateParent.bind(this),
                                            item_selected:
                                                this.state.item_selected,
                                            gem_selected:
                                                this.state.gem_selected,
                                        }),
                                    )
                                  : null,
                              this.state.selected_seer_action === "remove-gem"
                                  ? React.createElement(
                                        "div",
                                        { className: "mt-3 mb-2" },
                                        React.createElement(RemoveGem, {
                                            character_id:
                                                this.props.character_id,
                                            update_parent:
                                                this.updateParent.bind(this),
                                        }),
                                    )
                                  : null,
                              this.state.item_selected !== 0 &&
                                  this.state.selected_seer_action ===
                                      "manage-sockets"
                                  ? React.createElement(ManageItemSocketsCost, {
                                        socket_cost: this.state.socket_cost,
                                        get_item_info:
                                            this.getItemInfo.bind(this),
                                    })
                                  : null,
                              this.state.trading_with_seer
                                  ? React.createElement(
                                        LoadingProgressBar,
                                        null,
                                    )
                                  : null,
                              this.state.error_message !== null
                                  ? React.createElement(
                                        DangerAlert,
                                        { additional_css: "mt-4 mb-4" },
                                        this.state.error_message,
                                    )
                                  : null,
                              this.state.success_message !== null
                                  ? React.createElement(
                                        SuccessAlert,
                                        { additional_css: "mt-4 mb-4" },
                                        this.state.success_message,
                                    )
                                  : null,
                          ),
                ),
            ),
            React.createElement(
                "div",
                null,
                React.createElement(
                    "div",
                    { className: "text-center lg:ml-[-100px] mt-3 mb-3" },
                    this.state.selected_seer_action === "manage-sockets"
                        ? React.createElement(
                              Fragment,
                              null,
                              React.createElement(
                                  ManageItemSocketsActions,
                                  {
                                      do_action: this.doAction.bind(this),
                                      is_disabled:
                                          this.isManageSocketsDisabled(),
                                      is_loading: this.state.trading_with_seer,
                                  },
                                  React.createElement(DangerButton, {
                                      button_label: "Leave Seer Camp",
                                      on_click: this.props.leave_seer_camp,
                                      additional_css: "ml-2",
                                      disabled: this.state.trading_with_seer,
                                  }),
                              ),
                          )
                        : null,
                    this.state.selected_seer_action === "attach-gem"
                        ? React.createElement(
                              Fragment,
                              null,
                              React.createElement(
                                  AddGemsToItemActions,
                                  {
                                      do_action: this.doAction.bind(this),
                                      is_disabled: this.isAddGemsDisabled(),
                                      is_loading: this.state.trading_with_seer,
                                  },
                                  React.createElement(DangerButton, {
                                      button_label: "Leave Seer Camp",
                                      on_click: this.props.leave_seer_camp,
                                      additional_css: "ml-2",
                                      disabled: this.state.trading_with_seer,
                                  }),
                              ),
                          )
                        : null,
                    this.state.selected_seer_action === null ||
                        this.state.selected_seer_action === "remove-gem"
                        ? React.createElement(DangerButton, {
                              button_label: "Leave Seer Camp",
                              on_click: this.props.leave_seer_camp,
                              additional_css: "ml-2",
                              disabled: this.state.is_loading,
                          })
                        : null,
                ),
            ),
            this.state.manage_gems_on_item
                ? React.createElement(ManageGems, {
                      character_id: this.props.character_id,
                      selected_item: this.state.item_selected,
                      selected_gem: this.state.gem_selected,
                      cost: this.state.attach_gem,
                      manage_model: function () {
                          return _this.setState({ manage_gems_on_item: false });
                      },
                      update_parent: this.updateParent.bind(this),
                      is_open: true,
                  })
                : null,
        );
    };
    return SeerCamp;
})(React.Component);
export default SeerCamp;
//# sourceMappingURL=seer-camp.js.map
