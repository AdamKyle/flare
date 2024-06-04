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
import ServerFight from "./fight-section/server-fight";
import BattleMesages from "./fight-section/battle-mesages";
import Ajax from "../../../lib/ajax/ajax";
import PrimaryLinkButton from "../../../components/ui/buttons/primary-link-button";
var RaidFight = (function (_super) {
    __extends(RaidFight, _super);
    function RaidFight(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            is_attacking: false,
            battle_messages: [],
            character_current_health: 0,
            monster_current_health: 0,
            attacks_left: 5,
            error_message: "",
        };
        _this.attacksLeftUpdate = Echo.private(
            "update-raid-attacks-left-" + _this.props.user_id,
        );
        return _this;
    }
    RaidFight.prototype.componentDidMount = function () {
        var _this = this;
        this.setState({
            character_current_health: this.props.character_current_health,
            monster_current_health: this.props.monster_current_health,
            attacks_left: this.props.initial_attacks_left,
        });
        this.attacksLeftUpdate.listen(
            "Game.Battle.Events.UpdateRaidAttacksLeft",
            function (event) {
                _this.setState({
                    attacks_left: event.attacksLeft,
                });
            },
        );
    };
    RaidFight.prototype.componentDidUpdate = function () {
        var _this = this;
        if (
            this.state.character_current_health !==
                this.props.character_current_health &&
            this.props.revived
        ) {
            this.setState(
                {
                    character_current_health:
                        this.props.character_current_health,
                },
                function () {
                    _this.props.reset_revived();
                },
            );
        }
        if (this.props.update_raid_fight) {
            this.setState(
                {
                    character_current_health:
                        this.props.character_current_health,
                    monster_current_health: this.props.monster_current_health,
                    attacks_left: this.props.initial_attacks_left,
                    battle_messages: [],
                },
                function () {
                    _this.props.reset_update();
                },
            );
        }
    };
    RaidFight.prototype.attack = function (type) {
        var _this = this;
        this.setState(
            {
                is_attacking: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "raid-fight/" +
                            _this.props.character_id +
                            "/" +
                            _this.props.monster_id,
                    )
                    .setParameters({
                        attack_type: type,
                    })
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                character_current_health:
                                    result.data.character_current_health,
                                monster_current_health:
                                    result.data.monster_current_health,
                                battle_messages: result.data.messages,
                                is_attacking: false,
                            });
                        },
                        function (error) {
                            console.error(error);
                            var response = null;
                            if (typeof error.response !== "undefined") {
                                response = error.response;
                            }
                            _this.setState({
                                is_attacking: false,
                                error_message:
                                    response !== null
                                        ? response.data.message
                                        : "Unknown error occured!",
                            });
                        },
                    );
            },
        );
    };
    RaidFight.prototype.canAttack = function () {
        if (
            this.props.is_raid_boss &&
            this.state.attacks_left <= 0 &&
            !this.props.is_dead
        ) {
            return false;
        }
        return this.props.can_attack;
    };
    RaidFight.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            this.props.is_raid_boss
                ? React.createElement(
                      "div",
                      { className: "flex items-center justify-center" },
                      React.createElement(
                          "div",
                          { className: "mt-4 text-center font-bold" },
                          "Attacks Left: ",
                          this.state.attacks_left,
                          "/5",
                          " ",
                          this.state.attacks_left <= 0
                              ? "[You can attack again tomorrow]"
                              : "",
                      ),
                  )
                : null,
            this.state.error_message !== ""
                ? React.createElement(
                      "div",
                      { className: "flex items-center justify-center" },
                      React.createElement(
                          "div",
                          {
                              className:
                                  "mt-4 text-center text-red-700 dark:text-red-500",
                          },
                          this.state.error_message,
                      ),
                  )
                : null,
            React.createElement(
                "div",
                { className: "flex items-center justify-center" },
                React.createElement(
                    "div",
                    { className: " mt-4 mb-4 text-center" },
                    React.createElement(PrimaryLinkButton, {
                        button_label: "Elemental Atonement Info",
                        on_click: this.props.manage_elemental_atonement_modal,
                    }),
                ),
            ),
            React.createElement(
                ServerFight,
                {
                    monster_health: this.state.monster_current_health,
                    character_health: this.state.character_current_health,
                    monster_max_health: this.props.monster_max_health,
                    character_max_health: this.props.character_max_health,
                    monster_name: this.props.monster_name,
                    preforming_action: this.state.is_attacking,
                    character_name: this.props.character_name,
                    is_dead: this.props.is_dead,
                    can_attack: this.canAttack(),
                    monster_id: this.props.monster_id,
                    attack: this.attack.bind(this),
                    revive: this.props.revive,
                },
                React.createElement(BattleMesages, {
                    is_small: this.props.is_small,
                    battle_messages: this.state.battle_messages,
                }),
            ),
        );
    };
    return RaidFight;
})(React.Component);
export default RaidFight;
//# sourceMappingURL=raid-fight.js.map
