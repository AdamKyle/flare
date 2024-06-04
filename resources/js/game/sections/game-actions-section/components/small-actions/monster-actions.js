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
import MonsterSelection from "../monster-selection";
import FightSection from "../fight-section";
import MonsterActionsManager from "../../../../lib/game/actions/smaller-actions-components/monster-actions-manager";
import Revive from "../fight-section/revive";
var MonsterActions = (function (_super) {
    __extends(MonsterActions, _super);
    function MonsterActions(props) {
        var _this = _super.call(this, props) || this;
        _this.monsterActionManager = new MonsterActionsManager(_this);
        _this.state = {
            monsters: [],
            monster_to_fight: null,
            is_same_monster: false,
            character_revived: false,
            attack_time_out: 0,
            rank_selected: 1,
        };
        return _this;
    }
    MonsterActions.prototype.componentDidMount = function () {
        this.setState({
            monsters: this.props.monsters,
        });
    };
    MonsterActions.prototype.componentDidUpdate = function () {
        if (this.props.monsters.length > 0 && this.state.monsters.length > 0) {
            var newMonster = this.props.monsters[0];
            var currentMonster = this.state.monsters[0];
            if (newMonster.id !== currentMonster.id) {
                this.setState({
                    monsters: this.props.monsters,
                });
            }
        }
    };
    MonsterActions.prototype.setSelectedMonster = function (monster) {
        this.monsterActionManager.setSelectedMonster(monster);
    };
    MonsterActions.prototype.resetSameMonster = function () {
        this.monsterActionManager.resetSameMonster();
    };
    MonsterActions.prototype.setAttackTimeOut = function (attack_time_out) {
        this.monsterActionManager.setAttackTimeOut(attack_time_out);
    };
    MonsterActions.prototype.resetRevived = function () {
        this.monsterActionManager.resetRevived();
    };
    MonsterActions.prototype.setRank = function (data) {
        this.setState({
            rank_selected: data.value,
        });
    };
    MonsterActions.prototype.render = function () {
        var _this = this;
        return React.createElement(
            "div",
            { className: "relative" },
            React.createElement(MonsterSelection, {
                monsters: this.state.monsters,
                update_monster_to_fight: this.setSelectedMonster.bind(this),
                character: this.props.character,
                close_monster_section: this.props.close_monster_section,
            }),
            React.createElement(Revive, {
                can_attack: this.props.character_statuses.can_attack,
                is_character_dead: this.props.character.is_dead,
                character_id: this.props.character.id,
                revive_call_back: function () {
                    _this.setState({
                        character_revived: true,
                    });
                },
            }),
            this.props.children,
            this.state.monster_to_fight !== null
                ? React.createElement(FightSection, {
                      set_attack_time_out: this.setAttackTimeOut.bind(this),
                      monster_to_fight: this.state.monster_to_fight,
                      character: this.props.character,
                      is_same_monster: this.state.is_same_monster,
                      reset_same_monster: this.resetSameMonster.bind(this),
                      character_revived: this.state.character_revived,
                      reset_revived: this.resetRevived.bind(this),
                      is_small: this.props.is_small,
                  })
                : null,
        );
    };
    return MonsterActions;
})(React.Component);
export default MonsterActions;
//# sourceMappingURL=monster-actions.js.map
