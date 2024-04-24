import MonsterActions from "../../../../sections/game-actions-section/components/small-actions/monster-actions";
import MonsterType from "../../types/actions/monster/monster-type";
import Ajax from "../../../ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import Revive from "../../../../sections/game-actions-section/components/fight-section/revive";

export default class MonsterActionsManager {
    private component: MonsterActions | Revive;

    constructor(component: MonsterActions | Revive) {
        this.component = component;
    }

    /**
     * Set selected monster.
     *
     * @param monster
     */
    public setSelectedMonster(monster: MonsterType | null) {
        if (this.component instanceof Revive) {
            return;
        }

        let isSameMonster = false;

        if (monster === null) {
            return;
        }

        if (monster.id === this.component.state.monster_to_fight?.id) {
            isSameMonster = true;
        }

        this.component.setState({
            monster_to_fight: monster,
            is_same_monster: isSameMonster,
        });
    }

    /**
     * Reset the is_same_monster state.
     */
    public resetSameMonster() {
        if (this.component instanceof Revive) {
            return;
        }

        this.component.setState({
            is_same_monster: false,
        });
    }

    /**
     * Set the attack time out.
     *
     * @param attackTimeOut
     */
    setAttackTimeOut(attackTimeOut: number) {
        if (this.component instanceof Revive) {
            return;
        }

        this.component.setState({
            attack_time_out: attackTimeOut,
        });
    }

    /**
     * Revive the character.
     *
     * @param characterId
     */
    revive(characterId: number | null, callback?: () => void) {
        new Ajax().setRoute("battle-revive/" + characterId).doAjaxCall(
            "post",
            (result: AxiosResponse) => {
                if (typeof callback !== "undefined") {
                    callback();
                }
            },
            (error: AxiosError) => {
                console.error(error);
            },
        );
    }

    /**
     * Reset the fact the character has revived.
     */
    resetRevived() {
        if (this.component instanceof Revive) {
            return;
        }

        this.component.setState({
            character_revived: false,
        });
    }
}
