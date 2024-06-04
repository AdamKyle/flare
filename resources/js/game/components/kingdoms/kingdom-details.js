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
import { formatNumber } from "../../lib/game/format-number";
import PrimaryOutlineButton from "../../components/ui/buttons/primary-outline-button";
import DangerOutlineButton from "../../components/ui/buttons/danger-outline-button";
import SkyOutlineButton from "../../components/ui/buttons/sky-outline-button";
import ChangeNameModal from "./modals/change-name-modal";
import BuyPopulationModal from "./modals/buy-population-modal";
import GoblinBankModal from "./modals/goblin-bank-modal";
import AbandonKingdomModal from "./modals/abadnon-kingdom-modal";
import ManageTreasuryModal from "./modals/manage-treasury-modal";
import SuccessOutlineButton from "../../components/ui/buttons/success-outline-button";
import CallForReinforcements from "./modals/call-for-reinforcements ";
import SmelterModal from "./modals/smelter-modal";
import SpecialtyActionsHelpModal from "./modals/specialty-actions-help-modal";
import OrangeOutlineButton from "../ui/buttons/orange-outline-button";
import MakeCityACapitalModal from "./modals/make-city-a-capital-modal";
import SuccessButton from "../ui/buttons/success-button";
var KingdomDetails = (function (_super) {
    __extends(KingdomDetails, _super);
    function KingdomDetails(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            goblin_bank_building: null,
            show_change_name_modal: false,
            show_buy_pop_modal: false,
            show_goblin_bank: false,
            show_abandon_kingdom: false,
            show_manage_treasury: false,
            show_call_for_reinforcements: false,
            show_smelter: false,
            show_specialty_help: false,
            show_resource_transfer: false,
            show_make_capital_city: false,
        };
        CallForReinforcements;
        return _this;
    }
    KingdomDetails.prototype.componentDidUpdate = function (
        prevProps,
        prevState,
        snapshot,
    ) {
        if (
            this.state.show_resource_transfer &&
            this.props.reset_resource_transfer
        ) {
            this.setState({
                show_resource_transfer: false,
            });
        }
    };
    KingdomDetails.prototype.showChangeName = function () {
        this.setState({
            show_change_name_modal: !this.state.show_change_name_modal,
        });
    };
    KingdomDetails.prototype.showBuyPop = function () {
        this.setState({
            show_buy_pop_modal: !this.state.show_buy_pop_modal,
        });
    };
    KingdomDetails.prototype.showGoblinBank = function () {
        var building = this.props.kingdom.buildings.filter(function (building) {
            return building.name === "Goblin Coin Bank";
        })[0];
        this.setState({
            show_goblin_bank: !this.state.show_goblin_bank,
            goblin_bank_building: building,
        });
    };
    KingdomDetails.prototype.showAbandonKingdom = function () {
        this.setState({
            show_abandon_kingdom: !this.state.show_abandon_kingdom,
        });
    };
    KingdomDetails.prototype.showManageTreasury = function () {
        this.setState({
            show_manage_treasury: !this.state.show_manage_treasury,
        });
    };
    KingdomDetails.prototype.showRequestServices = function () {
        var _this = this;
        this.setState(
            {
                show_resource_transfer: !this.state.show_resource_transfer,
            },
            function () {
                _this.props.show_resource_transfer_card();
            },
        );
    };
    KingdomDetails.prototype.showCallForReinforcements = function () {
        this.setState({
            show_call_for_reinforcements:
                !this.state.show_call_for_reinforcements,
        });
    };
    KingdomDetails.prototype.showSmelter = function () {
        this.setState({
            show_smelter: !this.state.show_smelter,
        });
    };
    KingdomDetails.prototype.canManageGoldBars = function () {
        var bankBuilding = this.props.kingdom.buildings.filter(
            function (building) {
                return building.name === "Goblin Coin Bank";
            },
        );
        if (bankBuilding.length === 0) {
            return false;
        }
        bankBuilding = bankBuilding[0];
        return bankBuilding.is_locked;
    };
    KingdomDetails.prototype.showSpecialtyHelpModal = function () {
        this.setState({
            show_specialty_help: !this.state.show_specialty_help,
        });
    };
    KingdomDetails.prototype.showMakeCapitalCityModal = function () {
        this.setState({
            show_make_capital_city: !this.state.show_make_capital_city,
        });
    };
    KingdomDetails.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(
                "div",
                { className: "grid md:grid-cols-2 gap-4" },
                React.createElement(
                    "div",
                    null,
                    React.createElement("h3", null, "Basics"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Name"),
                        React.createElement(
                            "dd",
                            null,
                            this.props.kingdom.name,
                        ),
                        React.createElement("dt", null, "Morale"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.kingdom.current_morale * 100).toFixed(
                                2,
                            ) + "/100%",
                        ),
                        React.createElement("dt", null, "Treasury"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.treasury),
                        ),
                        React.createElement("dt", null, "Gold Bars"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.gold_bars),
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement("h3", null, "Resources"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Stone"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.current_stone) +
                                "/" +
                                formatNumber(this.props.kingdom.max_stone),
                        ),
                        React.createElement("dt", null, "Clay"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.current_clay) +
                                "/" +
                                formatNumber(this.props.kingdom.max_clay),
                        ),
                        React.createElement("dt", null, "Wood"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.current_wood) +
                                "/" +
                                formatNumber(this.props.kingdom.max_wood),
                        ),
                        React.createElement("dt", null, "Iron"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.current_iron) +
                                "/" +
                                formatNumber(this.props.kingdom.max_iron),
                        ),
                        React.createElement("dt", null, "Steel"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(this.props.kingdom.current_steel) +
                                "/" +
                                formatNumber(this.props.kingdom.max_steel),
                        ),
                        React.createElement("dt", null, "Population"),
                        React.createElement(
                            "dd",
                            null,
                            formatNumber(
                                this.props.kingdom.current_population,
                            ) +
                                "/" +
                                formatNumber(this.props.kingdom.max_population),
                        ),
                    ),
                ),
            ),
            React.createElement("div", {
                className:
                    "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
            }),
            React.createElement(
                "div",
                { className: "grid md:grid-cols-3 gap-2" },
                React.createElement(
                    "div",
                    null,
                    React.createElement("h3", null, "Defence Break Down"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Wall Defence"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.kingdom.walls_defence * 100).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Treasury Defence"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.kingdom.treasury_defence * 100).toFixed(
                                2,
                            ),
                            "%",
                        ),
                        React.createElement("dt", null, "Gold Bars Defence"),
                        React.createElement(
                            "dd",
                            null,
                            (
                                this.props.kingdom.gold_bars_defence * 100
                            ).toFixed(2),
                            "%",
                        ),
                        React.createElement("dt", null, "Passive Defence"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.kingdom.passive_defence * 100).toFixed(
                                2,
                            ),
                            "%",
                        ),
                        React.createElement("dt", null, "Total Defence Bonus"),
                        React.createElement(
                            "dd",
                            null,
                            (this.props.kingdom.defence_bonus * 100).toFixed(2),
                            "%",
                        ),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                    }),
                    React.createElement("h3", null, "Item Resistance"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                    }),
                    React.createElement(
                        "dl",
                        null,
                        React.createElement("dt", null, "Item Resistance"),
                        React.createElement(
                            "dd",
                            null,
                            (
                                this.props.kingdom.item_resistance_bonus * 100
                            ).toFixed(2),
                            "%",
                        ),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement("h3", null, "Kingdom Actions"),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                    }),
                    React.createElement(
                        "div",
                        { className: "grid md:grid-cols-1 gap-4" },
                        React.createElement(PrimaryOutlineButton, {
                            button_label: "Change Name",
                            on_click: this.showChangeName.bind(this),
                        }),
                        React.createElement(SuccessOutlineButton, {
                            button_label: "Call for Reinforcements",
                            on_click: this.showCallForReinforcements.bind(this),
                        }),
                        React.createElement(PrimaryOutlineButton, {
                            button_label: "Buy Population",
                            on_click: this.showBuyPop.bind(this),
                        }),
                        React.createElement(SkyOutlineButton, {
                            button_label: "Manage Treasury",
                            on_click: this.showManageTreasury.bind(this),
                        }),
                        React.createElement(DangerOutlineButton, {
                            button_label: "Abandon Kingdom",
                            on_click: this.showAbandonKingdom.bind(this),
                        }),
                    ),
                ),
                React.createElement("div", {
                    className:
                        "border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6",
                }),
                React.createElement(
                    "div",
                    null,
                    React.createElement(
                        "h3",
                        null,
                        "Specialty Actions",
                        " ",
                        React.createElement(
                            "button",
                            { onClick: this.showSpecialtyHelpModal.bind(this) },
                            React.createElement("i", {
                                className:
                                    "fas fa-info-circle text-blue-500 dark:text-blue-400",
                            }),
                        ),
                    ),
                    React.createElement("div", {
                        className:
                            "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6",
                    }),
                    React.createElement(
                        "div",
                        { className: "grid md:grid-cols-1 gap-4" },
                        React.createElement(PrimaryOutlineButton, {
                            button_label:
                                this.props.kingdom.smelting_time_left > 0
                                    ? React.createElement(
                                          Fragment,
                                          null,
                                          React.createElement("i", {
                                              className:
                                                  "far fa-clock text-yellow-700 dark:text-yellow-500 mr-2",
                                          }),
                                          " ",
                                          "Smelter",
                                      )
                                    : "Smelter",
                            on_click: this.showSmelter.bind(this),
                            disabled: !this.props.kingdom.can_access_smelter,
                        }),
                        React.createElement(SkyOutlineButton, {
                            button_label: "Manage Gold Bars",
                            on_click: this.showGoblinBank.bind(this),
                            disabled: this.canManageGoldBars(),
                        }),
                        React.createElement(SuccessOutlineButton, {
                            button_label: "Request Resources",
                            on_click: this.showRequestServices.bind(this),
                            disabled:
                                !this.props.kingdom.can_access_resource_request,
                        }),
                        !this.props.kingdom.is_capital
                            ? React.createElement(OrangeOutlineButton, {
                                  button_label: "Make Capital City",
                                  on_click:
                                      this.showMakeCapitalCityModal.bind(this),
                              })
                            : React.createElement(SuccessButton, {
                                  button_label: "Small Council",
                                  on_click:
                                      this.showMakeCapitalCityModal.bind(this),
                              }),
                    ),
                ),
            ),
            this.state.show_change_name_modal
                ? React.createElement(ChangeNameModal, {
                      name: this.props.kingdom.name,
                      kingdom_id: this.props.kingdom.id,
                      is_open: true,
                      handle_close: this.showChangeName.bind(this),
                  })
                : null,
            this.state.show_buy_pop_modal
                ? React.createElement(BuyPopulationModal, {
                      kingdom: this.props.kingdom,
                      is_open: true,
                      handle_close: this.showBuyPop.bind(this),
                      gold: this.props.character_gold,
                  })
                : null,
            this.state.show_goblin_bank &&
                this.state.goblin_bank_building !== null
                ? React.createElement(GoblinBankModal, {
                      is_open: true,
                      handle_close: this.showGoblinBank.bind(this),
                      character_gold: this.props.character_gold,
                      gold_bars: this.props.kingdom.gold_bars,
                      kingdom_id: this.props.kingdom.id,
                      goblin_bank: this.state.goblin_bank_building,
                  })
                : null,
            this.state.show_abandon_kingdom
                ? React.createElement(AbandonKingdomModal, {
                      is_open: true,
                      handle_close: this.showAbandonKingdom.bind(this),
                      handle_kingdom_close: this.props.close_details,
                      kingdom_id: this.props.kingdom.id,
                  })
                : null,
            this.state.show_manage_treasury
                ? React.createElement(ManageTreasuryModal, {
                      is_open: true,
                      handle_close: this.showManageTreasury.bind(this),
                      character_gold: this.props.character_gold,
                      treasury: this.props.kingdom.treasury,
                      morale: this.props.kingdom.current_morale,
                      kingdom_id: this.props.kingdom.id,
                      character_id: this.props.kingdom.character_id,
                  })
                : null,
            this.state.show_call_for_reinforcements
                ? React.createElement(CallForReinforcements, {
                      is_open: true,
                      kingdom_id: this.props.kingdom.id,
                      handle_close: this.showCallForReinforcements.bind(this),
                      character_id: this.props.kingdom.character_id,
                  })
                : null,
            this.state.show_smelter
                ? React.createElement(SmelterModal, {
                      is_open: true,
                      kingdom_id: this.props.kingdom.id,
                      max_steel: this.props.kingdom.max_steel,
                      iron: this.props.kingdom.current_iron,
                      handle_close: this.showSmelter.bind(this),
                      character_id: this.props.kingdom.character_id,
                      smelting_time_reduction:
                          this.props.kingdom.smelting_time_reduction,
                      smelting_time_left: this.props.kingdom.smelting_time_left,
                      smelting_completed_at:
                          this.props.kingdom.smelting_completed_at,
                      smelting_amount: this.props.kingdom.smelting_amount,
                  })
                : null,
            this.state.show_specialty_help
                ? React.createElement(SpecialtyActionsHelpModal, {
                      is_open: true,
                      handle_close: this.showSpecialtyHelpModal.bind(this),
                  })
                : null,
            this.state.show_make_capital_city
                ? React.createElement(MakeCityACapitalModal, {
                      is_open: true,
                      handle_close: this.showMakeCapitalCityModal.bind(this),
                  })
                : null,
        );
    };
    return KingdomDetails;
})(React.Component);
export default KingdomDetails;
//# sourceMappingURL=kingdom-details.js.map
