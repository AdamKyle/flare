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
import { isEqual } from "lodash";
import CritterSelection from "./fight-section/monster-selection";
var MonsterSelection = (function (_super) {
    __extends(MonsterSelection, _super);
    function MonsterSelection(props) {
        var _this = _super.call(this, props) || this;
        _this.state = {
            monster_to_fight: null,
            monsters: [],
        };
        return _this;
    }
    MonsterSelection.prototype.componentDidMount = function () {
        this.setState({
            monsters: this.props.monsters,
        });
    };
    MonsterSelection.prototype.componentDidUpdate = function () {
        if (!isEqual(this.state.monsters, this.props.monsters)) {
            this.setState({
                monster_to_fight: null,
                monsters: this.props.monsters,
            });
        }
    };
    MonsterSelection.prototype.setMonsterToFight = function (data) {
        var monster = this.findMonster(data.value);
        if (monster !== null) {
            this.setState({
                monster_to_fight: monster,
            });
        }
    };
    MonsterSelection.prototype.buildMonsters = function () {
        if (this.props.monsters === null) {
            return [{ label: "", value: 0 }];
        }
        return this.props.monsters.map(function (monster) {
            return { label: monster.name, value: monster.id };
        });
    };
    MonsterSelection.prototype.defaultMonster = function () {
        if (this.state.monster_to_fight !== null) {
            var monster = this.findMonster(this.state.monster_to_fight.id);
            if (monster !== null) {
                return [{ label: monster.name, value: monster.id }];
            }
        }
        return [{ label: "Please select a monster", value: 0 }];
    };
    MonsterSelection.prototype.findMonster = function (monsterId) {
        var foundMonster = this.props.monsters.filter(function (monster) {
            return monster.id === monsterId;
        });
        if (foundMonster.length > 0) {
            return foundMonster[0];
        }
        return null;
    };
    MonsterSelection.prototype.isAttackDisabled = function () {
        if (this.props.character === null) {
            return false;
        }
        return (
            this.props.character.is_dead ||
            this.props.character.is_automation_running ||
            !this.props.character.can_attack ||
            this.state.monster_to_fight === null
        );
    };
    MonsterSelection.prototype.attack = function () {
        this.props.update_monster_to_fight(this.state.monster_to_fight);
    };
    MonsterSelection.prototype.render = function () {
        return React.createElement(CritterSelection, {
            set_monster_to_fight: this.setMonsterToFight.bind(this),
            monsters: this.buildMonsters(),
            default_monster: this.defaultMonster(),
            attack: this.attack.bind(this),
            is_attack_disabled: this.isAttackDisabled(),
            close_monster_section: this.props.close_monster_section,
        });
    };
    return MonsterSelection;
})(React.Component);
export default MonsterSelection;
//# sourceMappingURL=monster-selection.js.map
