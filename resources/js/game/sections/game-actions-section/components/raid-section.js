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
import Ajax from "../../..//lib/ajax/ajax";
import MonsterSelection from "./fight-section/monster-selection";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import RaidFight from "./raid-fight";
import PrimaryButton from "../../../components/ui/buttons/primary-button";
import RaidElementInfo from "./fight-section/modals/raid-elemental-info";
var RaidSection = (function (_super) {
    __extends(RaidSection, _super);
    function RaidSection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            is_loading: false,
            is_fighting: false,
            character_current_health: 0,
            character_max_health: 0,
            monster_current_health: 0,
            monster_max_health: 0,
            selected_raid_monster_id: 0,
            monster_name: "",
            revived: false,
            raid_boss_attacks_left: 0,
            is_raid_boss: false,
            open_elemental_atonement: false,
            elemental_atonement: {},
            highest_element: null,
            update_raid_fight: false,
        };
        _this.updateRaidBosshealth = Echo.join(
            "update-raid-boss-health-attack",
        );
        _this.characterRevive = Echo.private(
            "character-revive-" + _this.props.user_id,
        );
        return _this;
    }
    RaidSection.prototype.componentDidMount = function () {
        var _this = this;
        this.updateRaidBosshealth.listen(
            "Game.Battle.Events.UpdateRaidBossHealth",
            function (event) {
                if (event.raidBossId === _this.state.selected_raid_monster_id) {
                    _this.setState({
                        monster_current_health: event.raidBossHealth,
                    });
                }
            },
        );
        this.characterRevive.listen(
            "Game.Battle.Events.CharacterRevive",
            function (event) {
                _this.setState({
                    character_current_health: event.health,
                });
            },
        );
    };
    RaidSection.prototype.componentDidUpdate = function (prevProps) {
        if (
            this.props.raid_monsters.length !==
                prevProps.raid_monsters.length &&
            this.state.monster_name !== ""
        ) {
            this.setState({
                monster_name: "",
            });
        }
    };
    RaidSection.prototype.buildRaidMonsterSelection = function () {
        if (this.props.raid_monsters.length === 0) {
            return [
                {
                    label: "",
                    value: 0,
                },
            ];
        }
        var raidMonsters = this.props.raid_monsters.map(function (raidMonster) {
            return {
                label: raidMonster.name,
                value: raidMonster.id,
            };
        });
        raidMonsters.unshift({
            label: "Please select raid monster",
            value: 0,
        });
        return raidMonsters;
    };
    RaidSection.prototype.defaultMonsterSelected = function () {
        var _this = this;
        if (this.state.selected_raid_monster_id === 0) {
            return [
                {
                    label: "Please select raid monster",
                    value: 0,
                },
            ];
        }
        var raidMonster = this.props.raid_monsters.find(function (raidMonster) {
            if (raidMonster.id === _this.state.selected_raid_monster_id) {
                return raidMonster;
            }
        });
        if (typeof raidMonster === "undefined") {
            return [
                {
                    label: "Please select raid monster",
                    value: 0,
                },
            ];
        }
        return [
            {
                label: raidMonster.name,
                value: raidMonster.id,
            },
        ];
    };
    RaidSection.prototype.setMonsterToFight = function (data) {
        if (data.value === 0) {
            return;
        }
        this.setState({
            selected_raid_monster_id: data.value,
        });
    };
    RaidSection.prototype.initializeMonsterForAttack = function () {
        var _this = this;
        if (this.state.selected_raid_monster_id === 0) {
            return;
        }
        var self = this;
        this.setState(
            {
                is_loading: true,
            },
            function () {
                new Ajax()
                    .setRoute(
                        "raid-fight-participation/" +
                            _this.props.character_id +
                            "/" +
                            _this.state.selected_raid_monster_id,
                    )
                    .doAjaxCall(
                        "get",
                        function (result) {
                            _this.setState({
                                is_loading: false,
                                character_current_health:
                                    result.data.character_max_health,
                                character_max_health:
                                    result.data.character_current_health,
                                monster_max_health:
                                    result.data.monster_max_health,
                                monster_current_health:
                                    result.data.monster_current_health,
                                monster_name: self.fetchRaidMonsterName(),
                                raid_boss_attacks_left:
                                    result.data.attacks_left,
                                is_raid_boss: result.data.is_raid_boss,
                                elemental_atonement:
                                    result.data.elemental_atonemnt,
                                highest_element: result.data.highest_element,
                                update_raid_fight: true,
                            });
                        },
                        function (error) {
                            _this.setState({
                                is_loading: false,
                            });
                            console.error(error);
                        },
                    );
            },
        );
    };
    RaidSection.prototype.resetUpdate = function () {
        this.setState({
            update_raid_fight: false,
        });
    };
    RaidSection.prototype.fetchRaidMonsterName = function () {
        var _this = this;
        if (this.props.raid_monsters.length <= 0) {
            return "ERROR.";
        }
        var raidMonster = this.props.raid_monsters.find(function (raidMonster) {
            if (raidMonster.id === _this.state.selected_raid_monster_id) {
                return raidMonster;
            }
        });
        if (typeof raidMonster === "undefined") {
            return "ERROR.";
        }
        return raidMonster.name;
    };
    RaidSection.prototype.revive = function () {
        var _this = this;
        this.setState(
            {
                is_fighting: true,
            },
            function () {
                new Ajax()
                    .setRoute("battle-revive/" + _this.props.character_id)
                    .doAjaxCall(
                        "post",
                        function (result) {
                            _this.setState({
                                is_fighting: false,
                                revived: true,
                            });
                        },
                        function (error) {
                            _this.setState({ is_fighting: false });
                            console.error(error);
                        },
                    );
            },
        );
    };
    RaidSection.prototype.resetRevived = function () {
        this.setState({
            revived: false,
        });
    };
    RaidSection.prototype.attackButtonDisabled = function () {
        return (
            this.props.is_dead ||
            !this.props.can_attack ||
            this.state.selected_raid_monster_id === 0
        );
    };
    RaidSection.prototype.manageAtonementModal = function () {
        this.setState({
            open_elemental_atonement: !this.state.open_elemental_atonement,
        });
    };
    RaidSection.prototype.render = function () {
        return React.createElement(
            Fragment,
            null,
            React.createElement(MonsterSelection, {
                set_monster_to_fight: this.setMonsterToFight.bind(this),
                monsters: this.buildRaidMonsterSelection(),
                default_monster: this.defaultMonsterSelected(),
                attack: this.initializeMonsterForAttack.bind(this),
                is_attack_disabled: this.attackButtonDisabled(),
                close_monster_section: this.props.close_monster_section,
            }),
            this.state.is_loading || this.state.is_fighting
                ? React.createElement(
                      "div",
                      { className: "flex items-center justify-center" },
                      React.createElement(
                          "div",
                          { className: "w-[50%]" },
                          React.createElement(LoadingProgressBar, null),
                      ),
                  )
                : null,
            this.props.is_dead && this.state.monster_name === ""
                ? React.createElement(
                      "div",
                      { className: "text-center mr-4 mt-4" },
                      React.createElement(PrimaryButton, {
                          button_label: "Revive",
                          on_click: this.revive.bind(this),
                          disabled: !this.props.can_attack,
                      }),
                  )
                : null,
            this.props.children,
            this.state.monster_name !== ""
                ? React.createElement(RaidFight, {
                      user_id: this.props.user_id,
                      character_current_health:
                          this.state.character_current_health,
                      character_max_health: this.state.character_max_health,
                      monster_current_health: this.state.monster_current_health,
                      monster_max_health: this.state.monster_max_health,
                      can_attack: this.props.can_attack,
                      is_dead: this.props.is_dead,
                      monster_name: this.state.monster_name,
                      monster_id: this.state.selected_raid_monster_id,
                      is_small: this.props.is_small,
                      character_name: this.props.character_name,
                      character_id: this.props.character_id,
                      revive: this.revive.bind(this),
                      reset_revived: this.resetRevived.bind(this),
                      revived: this.state.revived,
                      initial_attacks_left: this.state.raid_boss_attacks_left,
                      is_raid_boss: this.state.is_raid_boss,
                      manage_elemental_atonement_modal:
                          this.manageAtonementModal.bind(this),
                      update_raid_fight: this.state.update_raid_fight,
                      reset_update: this.resetUpdate.bind(this),
                  })
                : null,
            this.state.open_elemental_atonement
                ? React.createElement(RaidElementInfo, {
                      element_atonements: this.state.elemental_atonement,
                      highest_element: this.state.highest_element,
                      monster_name: this.state.monster_name,
                      is_open: this.state.open_elemental_atonement,
                      manage_modal: this.manageAtonementModal.bind(this),
                  })
                : null,
        );
    };
    return RaidSection;
})(React.Component);
export default RaidSection;
//# sourceMappingURL=raid-section.js.map
