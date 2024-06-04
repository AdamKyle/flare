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
import React from "react";
import AttackButton from "../../../components/ui/buttons/attack-button";
import clsx from "clsx";
import HealthMeters from "./health-meters";
import Ajax from "../../../lib/ajax/ajax";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import BattleMesages from "./fight-section/battle-mesages";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import PrimaryLinkButton from "../../../components/ui/buttons/primary-link-button";
import RaidElementInfo from "./fight-section/modals/raid-elemental-info";
var FightSection = (function (_super) {
    __extends(FightSection, _super);
    function FightSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            battle_messages: [],
            character_current_health: 0,
            character_max_health: 0,
            monster_current_health: 0,
            monster_max_health: 0,
            monster_to_fight_id: 0,
            is_character_voided: false,
            is_monster_voided: false,
            monster_to_fight: null,
            processing_rank_battle: false,
            setting_up_rank_fight: false,
            setting_up_regular_fight: false,
            processing_regular_fight: false,
            show_clear_message: true,
            open_elemental_atonement: false,
            error_message: "",
        };
        return _this;
    }
    FightSection.prototype.componentDidMount = function () {
        this.setUpBattle();
    };
    FightSection.prototype.componentDidUpdate = function () {
        var _this = this;
        var _a, _b;
        if (
            this.props.monster_to_fight.id !== this.state.monster_to_fight_id &&
            this.state.monster_to_fight_id !== 0
        ) {
            this.setState({
                monster_to_fight_id: this.props.monster_to_fight.id,
            });
            this.setUpBattle();
        }
        if (this.props.is_same_monster) {
            this.setState({
                battle_messages: [],
            });
            this.setUpBattle();
            this.props.reset_same_monster();
        }
        if (this.props.character_revived) {
            this.setState(
                {
                    character_current_health:
                        (_a = this.props.character) === null || _a === void 0
                            ? void 0
                            : _a.health,
                    character_max_health:
                        (_b = this.props.character) === null || _b === void 0
                            ? void 0
                            : _b.health,
                    battle_messages: [],
                },
                function () {
                    _this.props.reset_revived();
                },
            );
        }
    };
    FightSection.prototype.setUpBattle = function () {
        var _this = this;
        if (this.props.character == null) {
            return;
        }
        this.setState(
            {
                setting_up_regular_fight: true,
                show_clear_message: true,
                error_message: "",
            },
            function () {
                new Ajax()
                    .setRoute(
                        "setup-monster-fight/" +
                            _this.props.character.id +
                            "/" +
                            _this.props.monster_to_fight.id,
                    )
                    .setParameters({ attack_type: "attack" })
                    .doAjaxCall(
                        "get",
                        function (result) {
                            _this.setState({
                                battle_messages: result.data.opening_messages,
                                character_current_health:
                                    result.data.health.current_character_health,
                                character_max_health:
                                    result.data.health.max_character_health,
                                monster_current_health:
                                    result.data.health.current_monster_health,
                                monster_max_health:
                                    result.data.health.max_monster_health,
                                monster_to_fight_id: result.data.monster.id,
                                setting_up_regular_fight: false,
                                monster_to_fight: result.data.monster,
                            });
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    error_message: response.data.message,
                                    setting_up_regular_fight: false,
                                });
                            }
                        },
                    );
            },
        );
    };
    FightSection.prototype.attack = function (attackType) {
        var _this = this;
        this.setState(
            {
                processing_regular_fight: true,
                error_message: "",
            },
            function () {
                new Ajax()
                    .setRoute("monster-fight/" + _this.props.character.id)
                    .setParameters({ attack_type: attackType })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                battle_messages: result.data.messages,
                                character_current_health:
                                    result.data.health
                                        .current_character_health < 0
                                        ? 0
                                        : result.data.health
                                              .current_character_health,
                                monster_current_health:
                                    result.data.health.current_monster_health <
                                    0
                                        ? 0
                                        : result.data.health
                                              .current_monster_health,
                                processing_regular_fight: false,
                            });
                        },
                        function (error) {
                            if (typeof error.response !== "undefined") {
                                var response = error.response;
                                _this.setState({
                                    error_message: response.data.message,
                                    processing_regular_fight: false,
                                });
                            }
                        },
                    );
            },
        );
    };
    FightSection.prototype.attackButtonDisabled = function () {
        var _a, _b;
        if (typeof this.state.character_current_health === "undefined") {
            return true;
        }
        return (
            this.state.monster_current_health <= 0 ||
            this.state.character_current_health <= 0 ||
            ((_a = this.props.character) === null || _a === void 0
                ? void 0
                : _a.is_dead) ||
            !((_b = this.props.character) === null || _b === void 0
                ? void 0
                : _b.can_attack)
        );
    };
    FightSection.prototype.clearBattleMessages = function () {
        this.setState({
            battle_messages: [],
            monster_max_health:
                this.state.monster_current_health <= 0
                    ? 0
                    : this.state.monster_max_health,
            show_clear_message:
                this.state.monster_current_health <= 0 ? false : true,
        });
    };
    FightSection.prototype.manageElementalAtonement = function () {
        this.setState({
            open_elemental_atonement: !this.state.open_elemental_atonement,
        });
    };
    FightSection.prototype.render = function () {
        var _this = this;
        var _a;
        if (this.state.setting_up_regular_fight) {
            return React.createElement(
                "div",
                { className: "flex items-center justify-center" },
                React.createElement(LoadingProgressBar, null),
            );
        }
        if (this.state.error_message !== "") {
            return React.createElement(
                "div",
                { className: "ml-auto mr-auto my-4 md:max-w-[75%]" },
                React.createElement(
                    DangerAlert,
                    null,
                    this.state.error_message,
                ),
            );
        }
        return React.createElement(
            "div",
            { className: clsx({ "ml-[-100px]": !this.props.is_small }) },
            ((_a = this.state.monster_to_fight) === null || _a === void 0
                ? void 0
                : _a.highest_element) !== "UNKNOWN"
                ? React.createElement(
                      "div",
                      { className: "flex items-center justify-center" },
                      React.createElement(
                          "div",
                          { className: " mt-4 mb-4 text-center" },
                          React.createElement(PrimaryLinkButton, {
                              button_label: "Elemental Atonement Info",
                              on_click:
                                  this.manageElementalAtonement.bind(this),
                          }),
                      ),
                  )
                : null,
            React.createElement(
                "div",
                {
                    className: clsx("mt-4 mb-4 text-xs text-center", {
                        hidden: this.attackButtonDisabled(),
                    }),
                },
                React.createElement(AttackButton, {
                    is_small: this.props.is_small,
                    type: "Atk",
                    additional_css: "btn-attack",
                    icon_class: "ra ra-sword",
                    on_click: function () {
                        return _this.attack("attack");
                    },
                    disabled: this.attackButtonDisabled(),
                }),
                React.createElement(AttackButton, {
                    is_small: this.props.is_small,
                    type: "Cast",
                    additional_css: "btn-cast",
                    icon_class: "ra ra-burning-book",
                    on_click: function () {
                        return _this.attack("cast");
                    },
                    disabled: this.attackButtonDisabled(),
                }),
                React.createElement(AttackButton, {
                    is_small: this.props.is_small,
                    type: "Cast & Atk",
                    additional_css: "btn-cast-attack",
                    icon_class: "ra ra-lightning-sword",
                    on_click: function () {
                        return _this.attack("cast_and_attack");
                    },
                    disabled: this.attackButtonDisabled(),
                }),
                React.createElement(AttackButton, {
                    is_small: this.props.is_small,
                    type: "Atk & Cast",
                    additional_css: "btn-attack-cast",
                    icon_class: "ra ra-lightning-sword",
                    on_click: function () {
                        return _this.attack("attack_and_cast");
                    },
                    disabled: this.attackButtonDisabled(),
                }),
                React.createElement(AttackButton, {
                    is_small: this.props.is_small,
                    type: "Defend",
                    additional_css: "btn-defend",
                    icon_class: "ra ra-round-shield",
                    on_click: function () {
                        return _this.attack("defend");
                    },
                    disabled: this.attackButtonDisabled(),
                }),
            ),
            React.createElement(
                "div",
                {
                    className: clsx("mt-1 text-xs text-center", {
                        hidden: this.attackButtonDisabled(),
                    }),
                },
                React.createElement(
                    "span",
                    { className: "w-10 mr-4 ml-4" },
                    "Atk",
                ),
                React.createElement("span", { className: "w-10 ml-6" }, "Cast"),
                React.createElement(
                    "span",
                    { className: "w-10 ml-4" },
                    "Cast & Atk",
                ),
                React.createElement(
                    "span",
                    { className: "w-10 ml-2" },
                    "Atk & Cast",
                ),
                React.createElement(
                    "span",
                    { className: "w-10 ml-2" },
                    "Defend",
                ),
            ),
            this.state.processing_rank_battle ||
                this.state.processing_regular_fight
                ? React.createElement(
                      "div",
                      { className: "w-1/2 mx-auto" },
                      React.createElement(LoadingProgressBar, null),
                  )
                : null,
            this.attackButtonDisabled() && this.state.show_clear_message
                ? React.createElement(
                      "div",
                      { className: "text-center mt-4" },
                      React.createElement(
                          "button",
                          {
                              onClick: this.clearBattleMessages.bind(this),
                              className:
                                  "text-red-500 dark:text-red-400 underline hover:text-red-600 dark:hover:text-red-500",
                          },
                          "Clear",
                      ),
                  )
                : null,
            this.state.monster_max_health > 0 && this.props.character !== null
                ? React.createElement(
                      "div",
                      { className: clsx("mb-4 max-w-md m-auto mt-4") },
                      React.createElement(HealthMeters, {
                          is_enemy: true,
                          name: this.props.monster_to_fight.name,
                          current_health: this.state.monster_current_health,
                          max_health: this.state.monster_max_health,
                      }),
                      React.createElement(HealthMeters, {
                          is_enemy: false,
                          name: this.props.character.name,
                          current_health: this.state.character_current_health,
                          max_health: this.state.character_max_health,
                      }),
                  )
                : null,
            this.state.open_elemental_atonement &&
                this.state.monster_to_fight !== null
                ? React.createElement(RaidElementInfo, {
                      element_atonements:
                          this.state.monster_to_fight.elemental_atonement,
                      highest_element:
                          this.state.monster_to_fight.highest_element,
                      monster_name: this.state.monster_to_fight.name,
                      is_open: this.state.open_elemental_atonement,
                      manage_modal: this.manageElementalAtonement.bind(this),
                  })
                : null,
            React.createElement(
                "div",
                { className: "italic text-center" },
                React.createElement(BattleMesages, {
                    battle_messages: this.state.battle_messages,
                    is_small: this.props.is_small,
                }),
            ),
        );
    };
    return FightSection;
})(React.Component);
export default FightSection;
//# sourceMappingURL=fight-section.js.map
