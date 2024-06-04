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
import InfoAlert from "../../../../../components/ui/alerts/simple-alerts/info-alert";
import Table from "../../../../../components/ui/data-tables/table";
import { buildGemColumns } from "../../../../../lib/game/character-sheet/helpers/inventory/build-inventory-table-columns";
import Ajax from "../../../../../lib/ajax/ajax";
import PrimaryButton from "../../../../../components/ui/buttons/primary-button";
import CharacterGem from "../../modals/character-gem";
import ActionDialogue from "../../../../../components/ui/dialogue/action-dialogue";
import SuccessAlert from "../../../../../components/ui/alerts/simple-alerts/success-alert";
var GemBagTable = (function (_super) {
    __extends(GemBagTable, _super);
    function GemBagTable(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            viewGem: false,
            slot_id: 0,
            gem_slots: [],
            view_sell_all: false,
            view_sell: false,
            gem_to_sell: {},
            is_selling: false,
            success_message: null,
        };
        return _this;
    }
    GemBagTable.prototype.componentDidMount = function () {
        var _this = this;
        new Ajax()
            .setRoute("character/" + this.props.character_id + "/gem-bag")
            .doAjaxCall(
                "get",
                function (result) {
                    _this.setState({
                        gem_slots: result.data.gem_slots,
                    });
                },
                function (error) {
                    console.error(error);
                },
            );
    };
    GemBagTable.prototype.showSellConfirmation = function (gemSlotId) {
        this.setState({
            gem_to_sell: this.state.gem_slots.find(function (gemSlot) {
                return gemSlot.id === gemSlotId;
            }),
            slot_id: gemSlotId,
            view_sell: true,
        });
    };
    GemBagTable.prototype.showSellAllConfirmation = function () {
        this.setState({
            view_sell_all: true,
        });
    };
    GemBagTable.prototype.sellGem = function () {
        var _this = this;
        this.setState(
            {
                is_selling: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/sell-gem/" +
                            _this.state.slot_id,
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                gem_to_sell: {},
                                slot_id: 0,
                                view_sell: false,
                                gem_slots: result.data.gems,
                                success_message: result.data.message,
                                is_selling: false,
                            });
                        },
                        function (error) {
                            _this.setState({
                                gem_to_sell: {},
                                slot_id: 0,
                                view_sell: false,
                                is_selling: false,
                            });
                            console.error(error);
                        },
                    );
            },
        );
    };
    GemBagTable.prototype.sellAllGems = function () {
        var _this = this;
        this.setState(
            {
                is_selling: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "character/" +
                            _this.props.character_id +
                            "/sell-all-gems",
                    )
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                view_sell_all: false,
                                gem_slots: result.data.gems,
                                success_message: result.data.message,
                                is_selling: false,
                            });
                        },
                        function (error) {
                            _this.setState({
                                view_sell_all: false,
                                is_selling: false,
                            });
                            console.error(error);
                        },
                    );
            },
        );
    };
    GemBagTable.prototype.viewItem = function (gemSlot) {
        this.setState({
            view_gem: true,
            slot_id: gemSlot.id,
        });
    };
    GemBagTable.prototype.closeViewGem = function () {
        this.setState({
            view_gem: false,
            slot_id: 0,
        });
    };
    GemBagTable.prototype.gemActions = function (data) {
        var _this = this;
        return React.createElement(PrimaryButton, {
            button_label: "Sell",
            on_click: function () {
                return _this.showSellConfirmation(data.id);
            },
        });
    };
    GemBagTable.prototype.buildGemDialogueTitle = function (gemSlotId) {
        var gemSlot = this.state.gem_slots.filter(function (gemSlot) {
            return gemSlot.id === gemSlotId;
        });
        if (gemSlot.length > 0) {
            gemSlot = gemSlot[0];
        }
        return React.createElement(
            "span",
            { className: "text-lime-600 dark:text-lime-500" },
            gemSlot.name,
        );
    };
    GemBagTable.prototype.render = function () {
        var _this = this;
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                InfoAlert,
                { additional_css: "mt-4 mb-4" },
                "Click the item name to get additional actions.",
            ),
            React.createElement(PrimaryButton, {
                button_label: "Sell all Gems",
                on_click: this.showSellAllConfirmation.bind(this),
                additional_css: "my-3",
            }),
            this.state.success_message !== null
                ? React.createElement(
                      SuccessAlert,
                      { additional_css: "my-4" },
                      this.state.success_message,
                  )
                : null,
            React.createElement(
                "div",
                { className: "max-w-[390px] md:max-w-full overflow-x-hidden" },
                React.createElement(Table, {
                    data: this.state.gem_slots,
                    columns: buildGemColumns(this, this.viewItem.bind(this)),
                    dark_table: this.props.dark_table,
                }),
            ),
            this.state.view_gem
                ? React.createElement(CharacterGem, {
                      slot_id: this.state.slot_id,
                      is_open: this.state.view_gem,
                      title: this.buildGemDialogueTitle(this.state.slot_id),
                      character_id: this.props.character_id,
                      manage_modal: this.closeViewGem.bind(this),
                  })
                : null,
            this.state.view_sell
                ? React.createElement(
                      ActionDialogue,
                      {
                          is_open: this.state.view_sell,
                          manage_modal: function () {
                              _this.setState({
                                  slot_id: 0,
                                  view_sell: false,
                                  gem_to_sell: {},
                              });
                          },
                          title: React.createElement(
                              "span",
                              null,
                              "Selling:",
                              " ",
                              React.createElement(
                                  "span",
                                  {
                                      className:
                                          "text-lime-600 dark:text-lime-500",
                                  },
                                  " ",
                                  this.state.gem_to_sell.name,
                                  " ",
                              ),
                              " ",
                              "(Tier: ",
                              this.state.gem_to_sell.tier,
                              ")",
                          ),
                          loading: this.state.is_selling,
                          do_action: this.sellGem.bind(this),
                      },
                      React.createElement(
                          "p",
                          { className: "my-4" },
                          React.createElement("strong", null, "Are you sure?"),
                          " By selling this gem, you get 15% of the currencies required to make a gem of their tier back.",
                      ),
                      React.createElement(
                          "p",
                          { className: "my-4" },
                          React.createElement(
                              "strong",
                              null,
                              "This action cannot be undone.",
                          ),
                      ),
                  )
                : null,
            this.state.view_sell_all
                ? React.createElement(
                      ActionDialogue,
                      {
                          is_open: this.state.view_sell_all,
                          manage_modal: function () {
                              _this.setState({
                                  view_sell_all: false,
                              });
                          },
                          title: "Sell All Gems",
                          loading: this.state.is_selling,
                          do_action: this.sellAllGems.bind(this),
                      },
                      React.createElement(
                          "p",
                          { className: "my-4" },
                          React.createElement("strong", null, "Are you sure?"),
                          " By selling all gems, you get 15% of the currencies required to make a gem of their tier back.",
                      ),
                      React.createElement(
                          "p",
                          { className: "my-4" },
                          React.createElement(
                              "strong",
                              null,
                              "This action cannot be undone.",
                          ),
                      ),
                  )
                : null,
        );
    };
    return GemBagTable;
})(React.Component);
export { GemBagTable };
//# sourceMappingURL=gem-bag-table.js.map
