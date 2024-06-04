import Ajax from "../../../ajax/ajax";
import Revive from "../../../../sections/game-actions-section/components/fight-section/revive";
var MonsterActionsManager = (function () {
    function MonsterActionsManager(component) {
        this.component = component;
    }
    MonsterActionsManager.prototype.setSelectedMonster = function (monster) {
        var _a;
        if (this.component instanceof Revive) {
            return;
        }
        var isSameMonster = false;
        if (monster === null) {
            return;
        }
        if (
            monster.id ===
            ((_a = this.component.state.monster_to_fight) === null ||
            _a === void 0
                ? void 0
                : _a.id)
        ) {
            isSameMonster = true;
        }
        this.component.setState({
            monster_to_fight: monster,
            is_same_monster: isSameMonster,
        });
    };
    MonsterActionsManager.prototype.resetSameMonster = function () {
        if (this.component instanceof Revive) {
            return;
        }
        this.component.setState({
            is_same_monster: false,
        });
    };
    MonsterActionsManager.prototype.setAttackTimeOut = function (
        attackTimeOut,
    ) {
        if (this.component instanceof Revive) {
            return;
        }
        this.component.setState({
            attack_time_out: attackTimeOut,
        });
    };
    MonsterActionsManager.prototype.revive = function (characterId, callback) {
        new Ajax().setRoute("battle-revive/" + characterId).doAjaxCall(
            "post",
            function (result) {
                if (typeof callback !== "undefined") {
                    callback();
                }
            },
            function (error) {
                console.error(error);
            },
        );
    };
    MonsterActionsManager.prototype.resetRevived = function () {
        if (this.component instanceof Revive) {
            return;
        }
        this.component.setState({
            character_revived: false,
        });
    };
    return MonsterActionsManager;
})();
export default MonsterActionsManager;
//# sourceMappingURL=monster-actions-manager.js.map
