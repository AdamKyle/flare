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
import React from "react";
import Select from "react-select";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import Ajax from "../../../lib/ajax/ajax";
import clsx from "clsx";
import AttackButton from "../../../components/ui/buttons/attack-button";
import HealthMeters from "./health-meters";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import DangerButton from "../../../components/ui/buttons/danger-button";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import BattleMesages from "./fight-section/battle-mesages";
var DuelPlayer = (function (_super) {
    __extends(DuelPlayer, _super);
    function DuelPlayer(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            character_id: 0,
            defender_id: 0,
            show_attack_section: false,
            preforming_action: false,
            attacker_max_health: 0,
            attacker_health: 0,
            defender_max_health: 0,
            defender_health: 0,
            battle_messages: [],
            error_message: null,
            defender_atonement: "N/A",
            attacker_atonement: "N/A",
        };
        return _this;
    }
    DuelPlayer.prototype.componentDidMount = function () {
        var _this = this;
        if (this.props.duel_data !== null) {
            this.setState(
                {
                    character_id: this.props.duel_data.attacker_id,
                    attacker_max_health:
                        this.props.duel_data.health_object.attacker_max_health,
                    attacker_health:
                        this.props.duel_data.health_object.attacker_health,
                    defender_max_health:
                        this.props.duel_data.health_object.defender_max_health,
                    defender_health:
                        this.props.duel_data.health_object.defender_health,
                    defender_id: this.props.duel_data.defender_id,
                    battle_messages: this.props.duel_data.messages,
                    defender_atonement: this.props.duel_data.defender_atonement,
                    attacker_atonement: this.props.duel_data.attacker_atonement,
                },
                function () {
                    _this.props.reset_duel_data();
                },
            );
        }
    };
    DuelPlayer.prototype.componentDidUpdate = function () {
        var _this = this;
        if (this.props.duel_data !== null) {
            this.setState(
                {
                    character_id: this.props.duel_data.attacker_id,
                    attacker_max_health:
                        this.props.duel_data.health_object.attacker_max_health,
                    attacker_health:
                        this.props.duel_data.health_object.attacker_health,
                    defender_max_health:
                        this.props.duel_data.health_object.defender_max_health,
                    defender_health:
                        this.props.duel_data.health_object.defender_health,
                    defender_id: this.props.duel_data.defender_id,
                    battle_messages: this.props.duel_data.messages,
                    defender_atonement: this.props.duel_data.defender_atonement,
                    attacker_atonement: this.props.duel_data.attacker_atonement,
                },
                function () {
                    _this.props.reset_duel_data();
                },
            );
        }
    };
    DuelPlayer.prototype.buildCharacters = function () {
        var selectedCharacter = this.props.character;
        var filteredCharacters = this.props.characters.filter(
            function (character) {
                return character.id !== selectedCharacter.id;
            },
        );
        return filteredCharacters.map(function (character) {
            return {
                label: character.name,
                value: character.id,
            };
        });
    };
    DuelPlayer.prototype.setCharacterToFight = function (data) {
        this.setState({
            character_id: data.value !== "" ? data.value : 0,
        });
    };
    DuelPlayer.prototype.defaultCharacter = function () {
        var _this = this;
        var foundCharacter = this.props.characters.filter(function (character) {
            return character.id === _this.state.character_id;
        });
        if (foundCharacter.length > 0) {
            return {
                label: foundCharacter[0].name,
                value: foundCharacter[0].id,
            };
        }
        return {
            label: "Please select target",
            value: "",
        };
    };
    DuelPlayer.prototype.defenderName = function () {
        var _this = this;
        var foundCharacter = this.props.characters.filter(function (character) {
            return character.id === _this.state.defender_id;
        });
        if (foundCharacter.length === 0) {
            return "Error...";
        }
        return foundCharacter[0].name;
    };
    DuelPlayer.prototype.fight = function () {
        var _this = this;
        this.setState(
            {
                preforming_action: true,
                error_message: null,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "attack-player/get-health/" + _this.props.character.id,
                    )
                    .setParameters({
                        defender_id: _this.state.character_id,
                    })
                    .doAjaxCall("get", function (result) {
                        _this.setState({
                            attacker_max_health:
                                result.data.attacker_max_health,
                            attacker_health: result.data.attacker_health,
                            defender_max_health:
                                result.data.defender_max_health,
                            defender_health: result.data.defender_health,
                            character_id: result.data.attacker_id,
                            defender_id: result.data.defender_id,
                            preforming_action: false,
                            attacker_atonement: result.data.attacker_atonement,
                            defender_atonement: result.data.defender_atonement,
                        });
                    });
            },
        );
    };
    DuelPlayer.prototype.attackHidden = function () {
        return (
            this.state.attacker_max_health === 0 ||
            this.state.defender_max_health === 0 ||
            this.props.characters.length === 0
        );
    };
    DuelPlayer.prototype.attack = function (type) {
        var _this = this;
        this.setState(
            {
                preforming_action: true,
                error_message: null,
            },
            function () {
                new Ajax()
                    .setRoute("attack-player/" + _this.props.character.id)
                    .setParameters({
                        defender_id: _this.state.defender_id,
                        attack_type: type,
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
                                var data = error.response.data;
                                _this.setState({
                                    error_message: data.message,
                                    preforming_action: false,
                                });
                            }
                        },
                    );
            },
        );
    };
    DuelPlayer.prototype.revive = function () {
        var _this = this;
        this.setState(
            {
                preforming_action: true,
            },
            function () {
                new Ajax()
                    .setRoute("pvp/revive/" + _this.props.character.id)
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                preforming_action: false,
                            });
                        },
                        function (error) {},
                    );
            },
        );
    };
    DuelPlayer.prototype.render = function () {
        var _this = this;
        return React.createElement(
            "div",
            { className: "mt-2 md:ml-[120px]" },
            this.state.error_message !== null
                ? React.createElement(
                      DangerAlert,
                      null,
                      this.state.error_message,
                  )
                : null,
            React.createElement(
                "div",
                { className: "mt-2 grid grid-cols-3 gap-2" },
                React.createElement(
                    "div",
                    { className: "cols-start-1 col-span-2" },
                    React.createElement(Select, {
                        onChange: this.setCharacterToFight.bind(this),
                        options: this.buildCharacters(),
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
                        value: this.defaultCharacter(),
                    }),
                ),
                React.createElement(
                    "div",
                    { className: "cols-start-3 cols-end-3" },
                    React.createElement(PrimaryButton, {
                        button_label: "Attack",
                        on_click: this.fight.bind(this),
                        disabled:
                            this.props.character.is_automation_running ||
                            this.props.character.is_dead ||
                            this.state.character_id === 0,
                    }),
                ),
            ),
            this.props.characters.length === 0
                ? React.createElement(
                      "p",
                      {
                          className:
                              "mt-4 text-sm text-center text-red-700 dark:text-red-500 w-2/3",
                      },
                      "No one left to fight child. Best be on your way. Click: Leave Fight.",
                  )
                : null,
            React.createElement(
                "div",
                { className: "md:ml-[-160px]" },
                React.createElement(
                    "div",
                    {
                        className: clsx("mt-4 mb-4 text-xs text-center", {
                            hidden: this.attackHidden(),
                        }),
                    },
                    React.createElement(AttackButton, {
                        additional_css: "btn-attack",
                        icon_class: "ra ra-sword",
                        on_click: function () {
                            return _this.attack("attack");
                        },
                        disabled: this.props.character.is_dead,
                    }),
                    React.createElement(AttackButton, {
                        additional_css: "btn-cast",
                        icon_class: "ra ra-burning-book",
                        on_click: function () {
                            return _this.attack("cast");
                        },
                        disabled: this.props.character.is_dead,
                    }),
                    React.createElement(AttackButton, {
                        additional_css: "btn-cast-attack",
                        icon_class: "ra ra-lightning-sword",
                        on_click: function () {
                            return _this.attack("cast_and_attack");
                        },
                        disabled: this.props.character.is_dead,
                    }),
                    React.createElement(AttackButton, {
                        additional_css: "btn-attack-cast",
                        icon_class: "ra ra-lightning-sword",
                        on_click: function () {
                            return _this.attack("attack_and_cast");
                        },
                        disabled: this.props.character.is_dead,
                    }),
                    React.createElement(AttackButton, {
                        additional_css: "btn-defend",
                        icon_class: "ra ra-round-shield",
                        on_click: function () {
                            return _this.attack("defend");
                        },
                        disabled: this.props.character.is_dead,
                    }),
                    React.createElement(
                        "a",
                        {
                            href: "/information/combat",
                            target: "_blank",
                            className: "ml-2",
                        },
                        "Help ",
                        React.createElement("i", {
                            className: "fas fa-external-link-alt",
                        }),
                    ),
                ),
                React.createElement(
                    "div",
                    {
                        className: clsx("mt-1 text-xs text-center ml-[-50px]", {
                            hidden: this.attackHidden(),
                        }),
                    },
                    React.createElement(
                        "span",
                        { className: "w-10 mr-4 ml-4" },
                        "Atk",
                    ),
                    React.createElement(
                        "span",
                        { className: "w-10 ml-6" },
                        "Cast",
                    ),
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
                this.state.defender_max_health > 0 &&
                    this.props.characters.length > 0
                    ? React.createElement(
                          "div",
                          {
                              className: clsx("mb-4 max-w-md m-auto", {
                                  "mt-4": this.attackHidden(),
                              }),
                          },
                          React.createElement(HealthMeters, {
                              is_enemy: true,
                              name: this.defenderName(),
                              current_health: this.state.defender_health,
                              max_health: this.state.defender_max_health,
                          }),
                          React.createElement(HealthMeters, {
                              is_enemy: false,
                              name: this.props.character.name,
                              current_health: this.state.attacker_health,
                              max_health: this.state.attacker_max_health,
                          }),
                          React.createElement(
                              "div",
                              { className: "my-2" },
                              React.createElement(
                                  "p",
                                  {
                                      className:
                                          "text-red-500 dark:text-red-400 text-sm",
                                  },
                                  this.defenderName(),
                                  " Elemental Atonement:",
                                  " ",
                                  this.state.defender_atonement,
                              ),
                              React.createElement(
                                  "p",
                                  {
                                      className:
                                          "text-green-700 dark:text-green-400 text-sm",
                                  },
                                  "Your Elemental Atonement:",
                                  " ",
                                  this.state.attacker_atonement,
                              ),
                          ),
                      )
                    : null,
                this.state.preforming_action
                    ? React.createElement(
                          "div",
                          { className: "w-1/2 ml-auto mr-auto" },
                          React.createElement(LoadingProgressBar, null),
                      )
                    : null,
                React.createElement(
                    "div",
                    { className: "italic text-center my-4" },
                    React.createElement(BattleMesages, {
                        is_small: this.props.is_small,
                        battle_messages: this.state.battle_messages,
                    }),
                ),
                React.createElement(
                    "div",
                    { className: "text-center" },
                    React.createElement(DangerButton, {
                        button_label: "Leave Fight",
                        on_click: this.props.manage_pvp,
                        additional_css: "mr-4",
                        disabled: this.props.character.is_dead,
                    }),
                    this.props.character.is_dead
                        ? React.createElement(PrimaryButton, {
                              button_label: "Revive",
                              on_click: this.revive.bind(this),
                              disabled: !this.props.character.can_attack,
                          })
                        : null,
                ),
            ),
        );
    };
    return DuelPlayer;
})(React.Component);
export default DuelPlayer;
//# sourceMappingURL=duel-player.js.map
