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
var __read =
    (this && this.__read) ||
    function (o, n) {
        var m = typeof Symbol === "function" && o[Symbol.iterator];
        if (!m) return o;
        var i = m.call(o),
            r,
            ar = [],
            e;
        try {
            while ((n === void 0 || n-- > 0) && !(r = i.next()).done)
                ar.push(r.value);
        } catch (error) {
            e = { error: error };
        } finally {
            try {
                if (r && !r.done && (m = i["return"])) m.call(i);
            } finally {
                if (e) throw e.error;
            }
        }
        return ar;
    };
var __spreadArray =
    (this && this.__spreadArray) ||
    function (to, from, pack) {
        if (pack || arguments.length === 2)
            for (var i = 0, l = from.length, ar; i < l; i++) {
                if (ar || !(i in from)) {
                    if (!ar) ar = Array.prototype.slice.call(from, 0, i);
                    ar[i] = from[i];
                }
            }
        return to.concat(ar || Array.prototype.slice.call(from));
    };
import React, { Fragment } from "react";
import Select from "react-select";
import PrimaryButton from "../../ui/buttons/primary-button";
import DangerButton from "../../ui/buttons/danger-button";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
import { formatNumber } from "../../../lib/game/format-number";
import { ceil } from "lodash";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
var QueenOfHearts = (function (_super) {
    __extends(QueenOfHearts, _super);
    function QueenOfHearts(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            initial_action: null,
            buy_option: null,
            reroll_options: {
                item_selected: null,
                reroll_option: null,
                attribute: null,
            },
            move_options: {
                unique_id: null,
                item_to_move_to_id: null,
                affix_to_move: null,
            },
            preforming_action: false,
            character_uniques: [],
            character_non_uniques: [],
            reroll_cost: {
                gold_dust_dust: 0,
                shards: 0,
            },
            movement_cost: {
                gold_dust_dust: 0,
                shards: 0,
            },
            loading: true,
            error_message: null,
        };
        _this.queenOfHearts = Echo.private(
            "update-queen-of-hearts-panel-" + _this.props.user_id,
        );
        return _this;
    }
    QueenOfHearts.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute(
                "character/" + this.props.character_id + "/inventory/uniques",
            )
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        loading: false,
                        character_uniques: result.data.unique_slots,
                        character_non_uniques: result.data.non_unique_slots,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
        this.queenOfHearts.listen(
            "Game.NpcActions.QueenOfHeartsActions.Events.UpdateQueenOfHeartsPanel",
            function (event) {
                _this.setState({
                    character_uniques: event.panelData.unique_slots,
                    character_non_uniques: event.panelData.non_unique_slots,
                });
            },
        );
    };
    QueenOfHearts.prototype.setInitialOption = function (data) {
        this.setState({
            initial_action: data.value,
        });
    };
    QueenOfHearts.prototype.setBuyOption = function (data) {
        this.setState({
            buy_option: data.value,
        });
    };
    QueenOfHearts.prototype.setReRollOption = function (data) {
        var _this = this;
        this.setState(
            {
                reroll_options: __assign(
                    __assign({}, this.state.reroll_options),
                    { reroll_option: data.value },
                ),
            },
            function () {
                return _this.calculateCost();
            },
        );
    };
    QueenOfHearts.prototype.setItemForReRoll = function (data) {
        this.setState({
            reroll_options: __assign(__assign({}, this.state.reroll_options), {
                item_selected: data.value,
            }),
        });
    };
    QueenOfHearts.prototype.setAttributeToReRoll = function (data) {
        var _this = this;
        this.setState(
            {
                reroll_options: __assign(
                    __assign({}, this.state.reroll_options),
                    { attribute: data.value },
                ),
            },
            function () {
                return _this.calculateCost();
            },
        );
    };
    QueenOfHearts.prototype.setSelectedItemToMove = function (data) {
        this.setState({
            move_options: __assign(__assign({}, this.state.move_options), {
                unique_id: data.value,
            }),
        });
    };
    QueenOfHearts.prototype.setAffixTypeToMove = function (data) {
        var _this = this;
        this.setState(
            {
                move_options: __assign(__assign({}, this.state.move_options), {
                    affix_to_move: data.value,
                }),
            },
            function () {
                _this.calculateMovementCost();
            },
        );
    };
    QueenOfHearts.prototype.setItemToMove = function (data) {
        this.setState({
            move_options: __assign(__assign({}, this.state.move_options), {
                item_to_move_to_id: data.value,
            }),
        });
    };
    QueenOfHearts.prototype.initialOptions = function () {
        return [
            {
                label: "Buy Item",
                value: "buy-item",
            },
            {
                label: "Re-Roll Item",
                value: "re-roll-item",
            },
            {
                label: "Move Enchants",
                value: "move-enchants",
            },
        ];
    };
    QueenOfHearts.prototype.buyItemOptions = function () {
        return [
            {
                label: "20 Billion (Gold Cost) - Basic",
                value: "basic",
            },
            {
                label: "40 Billion (Gold Cost) - Medium",
                value: "medium",
            },
            {
                label: "80 Billion (Gold Cost) - Legendary",
                value: "legendary",
            },
        ];
    };
    QueenOfHearts.prototype.getSelectedBuyValue = function () {
        var _this = this;
        var foundSelected = this.buyItemOptions().filter(function (option) {
            return option.value === _this.state.buy_option;
        });
        if (foundSelected.length > 0) {
            return {
                label: foundSelected[0].label,
                value: foundSelected[0].value,
            };
        }
        return { label: "Please select item to purchase", value: null };
    };
    QueenOfHearts.prototype.reRollOptions = function () {
        return [
            {
                label: "Prefix",
                value: "prefix",
            },
            {
                label: "Suffix",
                value: "suffix",
            },
            {
                label: "Both",
                value: "all-enchantments",
            },
        ];
    };
    QueenOfHearts.prototype.moveEnchantOptions = function () {
        var _this = this;
        var foundSelected = this.state.character_uniques.filter(
            function (unique) {
                return unique.id === _this.state.move_options.unique_id;
            },
        );
        if (foundSelected.length === 0) {
            return [];
        }
        foundSelected = foundSelected[0];
        var options = [];
        if (foundSelected.item.item_prefix !== null) {
            options.push({
                label: "Prefix",
                value: "prefix",
            });
        }
        if (foundSelected.item.item_suffix !== null) {
            options.push({
                label: "Suffix",
                value: "suffix",
            });
        }
        if (foundSelected.item.affix_count > 1) {
            options.push({
                label: "Both",
                value: "all-enchantments",
            });
        }
        return options;
    };
    QueenOfHearts.prototype.getSelectedReRollOption = function () {
        var _this = this;
        var foundSelected = this.reRollOptions().filter(function (option) {
            return option.value === _this.state.reroll_options.reroll_option;
        });
        if (foundSelected.length > 0) {
            return {
                label: foundSelected[0].label,
                value: foundSelected[0].value,
            };
        }
        return { label: "Please select what to re-roll", value: null };
    };
    QueenOfHearts.prototype.getAffixToMove = function () {
        var _this = this;
        var foundSelected = this.moveEnchantOptions().filter(function (option) {
            return option.value === _this.state.move_options.affix_to_move;
        });
        if (foundSelected.length > 0) {
            return {
                label: foundSelected[0].label,
                value: foundSelected[0].value,
            };
        }
        return { label: "Please select what to move", value: null };
    };
    QueenOfHearts.prototype.itemsForReRoll = function () {
        return this.state.character_uniques.map(function (unique) {
            return {
                label: unique.item.affix_name,
                value: unique.id,
            };
        });
    };
    QueenOfHearts.prototype.getSelectedItem = function () {
        var _this = this;
        var foundSelected = this.state.character_uniques.filter(
            function (unique) {
                return unique.id === _this.state.reroll_options.item_selected;
            },
        );
        if (foundSelected.length > 0) {
            return {
                label: foundSelected[0].item.affix_name,
                value: foundSelected[0].id,
            };
        }
        return { label: "Please select item to re-roll", value: null };
    };
    QueenOfHearts.prototype.getAttributesForReRoll = function () {
        return [
            {
                label: "Base Details",
                value: "base",
            },
            {
                label: "Core Stats",
                value: "stats",
            },
            {
                label: "Skill Modifiers",
                value: "skills",
            },
            {
                label: "Damage Modifiers",
                value: "damage",
            },
            {
                label: "Resistances",
                value: "resistances",
            },
            {
                label: "All of it",
                value: "everything",
            },
        ];
    };
    QueenOfHearts.prototype.getSelectedAttributeOption = function () {
        var _this = this;
        var foundSelected = this.getAttributesForReRoll().filter(
            function (option) {
                return option.value === _this.state.reroll_options.attribute;
            },
        );
        if (foundSelected.length > 0) {
            return {
                label: foundSelected[0].label,
                value: foundSelected[0].value,
            };
        }
        return {
            label: "Please select what attributes to re-roll",
            value: null,
        };
    };
    QueenOfHearts.prototype.getSelectedUnique = function () {
        var _this = this;
        var foundSelected = this.state.character_uniques.filter(
            function (unique) {
                return unique.id === _this.state.move_options.unique_id;
            },
        );
        if (foundSelected.length > 0) {
            return {
                label: foundSelected[0].item.affix_name,
                value: foundSelected[0].id,
            };
        }
        return { label: "Please select unique", value: "" };
    };
    QueenOfHearts.prototype.itemsToMoveTo = function () {
        var _this = this;
        var items = __spreadArray(
            __spreadArray([], __read(this.state.character_uniques), false),
            __read(this.state.character_non_uniques),
            false,
        );
        return items
            .map(function (item) {
                return {
                    label: item.item.affix_name,
                    value: item.id,
                };
            })
            .filter(function (item) {
                return item.value !== _this.state.move_options.unique_id;
            });
    };
    QueenOfHearts.prototype.getSelectedItemToMove = function () {
        var _this = this;
        var items = __spreadArray(
            __spreadArray([], __read(this.state.character_uniques), false),
            __read(this.state.character_non_uniques),
            false,
        );
        var foundSelected = items.filter(function (unique) {
            return unique.id === _this.state.move_options.item_to_move_to_id;
        });
        if (foundSelected.length > 0) {
            return {
                label: foundSelected[0].item.affix_name,
                value: foundSelected[0].id,
            };
        }
        return { label: "Please select item", value: "" };
    };
    QueenOfHearts.prototype.uniquesToMove = function () {
        return this.state.character_uniques.map(function (unique) {
            return {
                label: unique.item.affix_name,
                value: unique.id,
            };
        });
    };
    QueenOfHearts.prototype.buyItem = function () {
        var _this = this;
        this.setState(
            {
                preforming_action: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/random-enchant/purchase",
                    )
                    .setParameters({
                        type: _this.state.buy_option,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                preforming_action: false,
                            });
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                _this.setState({
                                    preforming_action: false,
                                });
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
    QueenOfHearts.prototype.calculateCost = function () {
        var goldDustCost = 10000;
        var shards = 100;
        if (this.state.reroll_options.reroll_option === "all-enchantments") {
            goldDustCost *= 2;
            shards *= 2;
        }
        if (this.state.reroll_options.attribute === "everything") {
            goldDustCost += 500;
            shards += 250;
        } else {
            goldDustCost += 100;
            shards += 100;
        }
        this.setState({
            reroll_cost: {
                gold_dust_dust: goldDustCost,
                shards: shards,
            },
        });
    };
    QueenOfHearts.prototype.calculateMovementCost = function () {
        var _this = this;
        var goldDust = 0;
        var shards = 0;
        if (
            this.state.move_options.unique_id !== null &&
            this.state.move_options.affix_to_move !== null
        ) {
            var foundSelected = this.state.character_uniques.filter(
                function (unique) {
                    return unique.id === _this.state.move_options.unique_id;
                },
            );
            if (foundSelected.length > 0) {
                foundSelected = foundSelected[0];
            }
            if (this.state.move_options.affix_to_move === "all-enchantments") {
                if (foundSelected.item.item_prefix !== null) {
                    goldDust += foundSelected.item.item_prefix.cost;
                }
                if (foundSelected.item.item_prefix !== null) {
                    goldDust += foundSelected.item.item_suffix.cost;
                }
            } else {
                goldDust +=
                    foundSelected.item[
                        "item_" + this.state.move_options.affix_to_move
                    ].cost;
            }
        }
        if (goldDust > 0) {
            goldDust = goldDust / 1000000;
            shards = parseInt(ceil(goldDust * 0.005).toFixed(0));
        }
        this.setState({
            movement_cost: {
                gold_dust_dust: goldDust,
                shards: shards,
            },
        });
    };
    QueenOfHearts.prototype.reRoll = function () {
        var _this = this;
        this.setState(
            {
                preforming_action: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/random-enchant/reroll",
                    )
                    .setParameters({
                        selected_slot_id:
                            _this.state.reroll_options.item_selected,
                        selected_affix:
                            _this.state.reroll_options.reroll_option,
                        selected_reroll_type:
                            _this.state.reroll_options.attribute,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                preforming_action: false,
                                character_uniques: result.data.unique_slots,
                                character_non_uniques:
                                    result.data.non_unique_slots,
                            });
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    error_message: response.data.message,
                                    preforming_action: false,
                                });
                            }
                        },
                    );
            },
        );
    };
    QueenOfHearts.prototype.moveAffixes = function () {
        var _this = this;
        this.setState(
            {
                preforming_action: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/random-enchant/move",
                    )
                    .setParameters({
                        selected_slot_id: _this.state.move_options.unique_id,
                        selected_secondary_slot_id:
                            _this.state.move_options.item_to_move_to_id,
                        selected_affix: _this.state.move_options.affix_to_move,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                preforming_action: false,
                                character_uniques: result.data.unique_slots,
                                character_non_uniques:
                                    result.data.non_unique_slots,
                                move_options: {
                                    unique_id: null,
                                    item_to_move_to_id: null,
                                    affix_to_move: null,
                                },
                            });
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.clearAll();
                                _this.setState({
                                    error_message: response.data.message,
                                    preforming_action: false,
                                });
                            }
                        },
                    );
            },
        );
    };
    QueenOfHearts.prototype.clearAll = function () {
        this.setState({
            buy_option: null,
            initial_action: null,
            reroll_options: {
                reroll_option: null,
                item_selected: null,
            },
            reroll_cost: {
                gold_dust: 0,
                shards: 0,
            },
            move_options: {
                unique_id: null,
                item_to_move_to_id: null,
                affix_to_move: null,
            },
        });
    };
    QueenOfHearts.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "mt-2 grid md:grid-cols-3 gap-2 md:ml-[120px]" },
                React.createElement(
                    "div",
                    { className: "cols-start-1 col-span-2" },
                    this.state.loading
                        ? React.createElement(LoadingProgressBar, null)
                        : null,
                    this.state.error_message !== null
                        ? React.createElement(
                              DangerAlert,
                              { additional_css: "mb-4 mt-2" },
                              this.state.error_message,
                          )
                        : null,
                    this.state.initial_action === null && !this.state.loading
                        ? React.createElement(Select, {
                              onChange: this.setInitialOption.bind(this),
                              options: this.initialOptions(),
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
                                      label: "Please select option",
                                      value: "",
                                  },
                              ],
                          })
                        : null,
                    this.state.initial_action === "buy-item"
                        ? React.createElement(
                              Fragment,
                              null,
                              React.createElement(Select, {
                                  onChange: this.setBuyOption.bind(this),
                                  options: this.buyItemOptions(),
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
                                  value: this.getSelectedBuyValue(),
                              }),
                          )
                        : null,
                    this.state.initial_action === "re-roll-item"
                        ? React.createElement(
                              Fragment,
                              null,
                              React.createElement(Select, {
                                  onChange: this.setItemForReRoll.bind(this),
                                  options: this.itemsForReRoll(),
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
                                  value: this.getSelectedItem(),
                              }),
                              this.state.reroll_options.item_selected !== null
                                  ? React.createElement(
                                        "div",
                                        { className: "mt-2" },
                                        React.createElement(Select, {
                                            onChange:
                                                this.setReRollOption.bind(this),
                                            options: this.reRollOptions(),
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
                                            value: this.getSelectedReRollOption(),
                                        }),
                                    )
                                  : null,
                              this.state.reroll_options.item_selected !==
                                  null &&
                                  this.state.reroll_options.reroll_option !==
                                      null
                                  ? React.createElement(
                                        "div",
                                        { className: "mt-2" },
                                        React.createElement(Select, {
                                            onChange:
                                                this.setAttributeToReRoll.bind(
                                                    this,
                                                ),
                                            options:
                                                this.getAttributesForReRoll(),
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
                                            value: this.getSelectedAttributeOption(),
                                        }),
                                        this.state.reroll_options
                                            .reroll_option !== null
                                            ? React.createElement(
                                                  "p",
                                                  {
                                                      className:
                                                          "mt-2 text-orange-600 dark:text-orange-500",
                                                  },
                                                  React.createElement(
                                                      "strong",
                                                      null,
                                                      "Gold Dust Cost",
                                                  ),
                                                  ":",
                                                  " ",
                                                  formatNumber(
                                                      this.state.reroll_cost
                                                          .gold_dust_dust,
                                                  ),
                                                  ", ",
                                                  React.createElement(
                                                      "strong",
                                                      null,
                                                      "Shards Cost",
                                                  ),
                                                  ":",
                                                  " ",
                                                  formatNumber(
                                                      this.state.reroll_cost
                                                          .shards,
                                                  ),
                                              )
                                            : null,
                                    )
                                  : null,
                          )
                        : null,
                    this.state.initial_action === "move-enchants"
                        ? React.createElement(
                              Fragment,
                              null,
                              React.createElement(Select, {
                                  onChange:
                                      this.setSelectedItemToMove.bind(this),
                                  options: this.uniquesToMove(),
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
                                  value: this.getSelectedUnique(),
                              }),
                              this.state.move_options.unique_id !== null &&
                                  this.state.move_options.unique_id !== ""
                                  ? React.createElement(
                                        "div",
                                        { className: "mt-2" },
                                        React.createElement(Select, {
                                            onChange:
                                                this.setAffixTypeToMove.bind(
                                                    this,
                                                ),
                                            options: this.moveEnchantOptions(),
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
                                            value: this.getAffixToMove(),
                                        }),
                                    )
                                  : null,
                              this.state.move_options.affix_to_move !== null &&
                                  this.state.move_options.affix_to_move !== ""
                                  ? React.createElement(
                                        "div",
                                        { className: "mt-2" },
                                        React.createElement(Select, {
                                            onChange:
                                                this.setItemToMove.bind(this),
                                            options: this.itemsToMoveTo(),
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
                                            value: this.getSelectedItemToMove(),
                                        }),
                                    )
                                  : null,
                              this.state.movement_cost.gold_dust !== 0 &&
                                  this.state.movement_cost.shards !== 0
                                  ? React.createElement(
                                        "p",
                                        {
                                            className:
                                                "mt-2 text-orange-600 dark:text-orange-500",
                                        },
                                        React.createElement(
                                            "strong",
                                            null,
                                            "Gold Dust Cost",
                                        ),
                                        ":",
                                        " ",
                                        formatNumber(
                                            this.state.movement_cost
                                                .gold_dust_dust,
                                        ),
                                        ", ",
                                        React.createElement(
                                            "strong",
                                            null,
                                            "Shards Cost",
                                        ),
                                        ":",
                                        " ",
                                        formatNumber(
                                            this.state.movement_cost.shards,
                                        ),
                                    )
                                  : null,
                          )
                        : null,
                    this.state.preforming_action
                        ? React.createElement(LoadingProgressBar, null)
                        : null,
                ),
            ),
            React.createElement(
                "div",
                { className: "text-center lg:ml-[-100px] mt-3 mb-3" },
                this.state.initial_action === "buy-item"
                    ? React.createElement(PrimaryButton, {
                          button_label: "Purchase",
                          on_click: this.buyItem.bind(this),
                          disabled:
                              this.state.buy_option === null ||
                              this.state.preforming_action,
                      })
                    : null,
                this.state.initial_action === "re-roll-item"
                    ? React.createElement(PrimaryButton, {
                          button_label: "Re roll",
                          on_click: this.reRoll.bind(this),
                          disabled:
                              !(
                                  this.state.reroll_options.item_selected !==
                                      null &&
                                  this.state.reroll_options.reroll_option !==
                                      null &&
                                  this.state.reroll_options.attribute !== null
                              ) || this.state.preforming_action,
                      })
                    : null,
                this.state.initial_action === "move-enchants"
                    ? React.createElement(PrimaryButton, {
                          button_label: "Move Enchants",
                          on_click: this.moveAffixes.bind(this),
                          disabled:
                              !(
                                  this.state.move_options.unique_id !== null &&
                                  this.state.move_options.item_to_move_to_id !==
                                      null &&
                                  this.state.move_options.affix_to_move !== null
                              ) || this.state.preforming_action,
                      })
                    : null,
                this.state.initial_action !== null
                    ? React.createElement(PrimaryButton, {
                          button_label: "Change Action",
                          on_click: this.clearAll.bind(this),
                          disabled: this.state.preforming_action,
                          additional_css: "ml-2",
                      })
                    : null,
                React.createElement(DangerButton, {
                    button_label: "Remove Queen",
                    on_click: this.props.remove_crafting,
                    disabled: this.state.preforming_action,
                    additional_css: "ml-2",
                }),
                React.createElement(
                    "a",
                    {
                        href: "/information/random-enchants",
                        target: "_blank",
                        className: "hidden lg:block ml-2 mt-4",
                    },
                    "Help ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
            React.createElement(
                "div",
                { className: "text-center mt-4 block lg:hidden" },
                React.createElement(
                    "a",
                    {
                        href: "/information/random-enchants",
                        target: "_blank",
                        className: "block lg:hidden ml-2 mt-4",
                    },
                    "Help ",
                    React.createElement("i", {
                        className: "fas fa-external-link-alt",
                    }),
                ),
            ),
        );
    };
    return QueenOfHearts;
})(React.Component);
export default QueenOfHearts;
//# sourceMappingURL=queen-of-hearts.js.map
