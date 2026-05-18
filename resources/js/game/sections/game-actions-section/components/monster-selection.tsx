import React from "react";
import { isEqual } from "lodash";
import MonsterType from "../../../lib/game/types/actions/monster/monster-type";
import MonsterSelectionProps from "./types/monster-selection-props";
import MonsterSelectionState from "./types/monster-selection-state";
import CritterSelection from "./fight-section/monster-selection";
import WarningAlert from "../../../components/ui/alerts/simple-alerts/warning-alert";
import DangerOutlineButton from "../../../components/ui/buttons/danger-outline-button";
import Ajax from "../../../lib/ajax/ajax";
import { AxiosError, AxiosResponse } from "axios";
import { updateTimers } from "../../../lib/ajax/update-timers";

export default class MonsterSelection extends React.Component<
    MonsterSelectionProps,
    MonsterSelectionState
> {
    constructor(props: MonsterSelectionProps) {
        super(props);

        this.state = {
            monster_to_fight: null,
            monsters: [],
            loading: false,
        };
    }

    componentDidMount() {
        this.setState({
            monsters: this.props.monsters,
        });
    }

    componentDidUpdate() {
        if (!isEqual(this.state.monsters, this.props.monsters)) {
            this.setState({
                monster_to_fight: null,
                monsters: this.props.monsters,
            });
        }
    }

    setMonsterToFight(data: any) {
        const monster: MonsterType | null = this.findMonster(data.value);

        if (monster !== null) {
            this.setState({
                monster_to_fight: monster,
            });
        }
    }

    buildMonsters() {
        if (this.props.monsters === null) {
            return [{ label: "", value: 0 }];
        }

        return this.props.monsters.map((monster: MonsterType) => {
            return { label: monster.name, value: monster.id };
        });
    }

    defaultMonster(): { label: string; value: number }[] {
        if (this.state.monster_to_fight !== null) {
            const monster: MonsterType | null = this.findMonster(
                this.state.monster_to_fight.id,
            );

            if (monster !== null) {
                return [{ label: monster.name, value: monster.id }];
            }
        }

        return [{ label: "Please select a monster", value: 0 }];
    }

    findMonster(monsterId: number): MonsterType | null {
        const foundMonster: MonsterType[] | [] = this.props.monsters.filter(
            (monster: MonsterType) => {
                return monster.id === monsterId;
            },
        );

        if (foundMonster.length > 0) {
            return foundMonster[0];
        }

        return null;
    }

    isAttackDisabled() {
        if (this.props.character === null) {
            return false;
        }

        return (
            this.props.character.is_dead ||
            this.props.character.is_automation_running ||
            this.props.character.is_faction_loyalty_automation_running ||
            this.props.character.is_delve_running ||
            !this.props.character.can_attack ||
            this.state.monster_to_fight === null
        );
    }

    isAnyAutomationRunning(): boolean {
        return (
            this.props.character.is_automation_running ||
            this.props.character.is_faction_loyalty_automation_running ||
            this.props.character.is_delve_running
        );
    }

    automationName(): string {
        if (this.props.character.is_faction_loyalty_automation_running) {
            return "Faction Loyalty Automation";
        }

        if (this.props.character.is_delve_running) {
            return "Delve";
        }

        return "Exploration";
    }

    automationRestrictionMessage(): string {
        if (this.props.character.is_faction_loyalty_automation_running) {
            return "Faction Loyalty Automation is running. You cannot Delve, Explore, manually Fight, or Craft while it is running.";
        }

        if (this.props.character.is_delve_running) {
            return "Delve is running. You cannot Explore, manually Fight, or use Faction Loyalty while it is running.";
        }

        return "Exploration is running. You cannot Delve, use Faction Loyalty, or manually Fight while it is running.";
    }

    automationStopRoute(): string {
        if (this.props.character.is_faction_loyalty_automation_running) {
            return (
                "faction-loyalty-automation/" +
                this.props.character.id +
                "/stop"
            );
        }

        if (this.props.character.is_delve_running) {
            return "delve/" + this.props.character.id + "/stop";
        }

        return "automation/" + this.props.character.id + "/stop";
    }

    stopRunningAutomation() {
        this.setState(
            {
                loading: true,
            },
            () => {
                new Ajax().setRoute(this.automationStopRoute()).doAjaxCall(
                    "post",
                    (result: AxiosResponse) => {
                        this.setState(
                            {
                                loading: false,
                            },
                            () => {
                                updateTimers(this.props.character.id);
                            },
                        );
                    },
                    (error: AxiosError) => {
                        this.setState({
                            loading: false,
                        });
                    },
                );
            },
        );
    }

    renderAutomationWarning() {
        if (!this.isAnyAutomationRunning()) {
            return null;
        }

        return (
            <div className="mx-auto w-full md:w-2/3" aria-live="polite">
                <WarningAlert additional_css={"mt-3"}>
                    <p className="my-2">
                        {this.automationRestrictionMessage()}
                    </p>
                    <DangerOutlineButton
                        button_label={"Stop " + this.automationName()}
                        on_click={this.stopRunningAutomation.bind(this)}
                        disabled={this.state.loading}
                        additional_css={"mt-2"}
                    />
                </WarningAlert>
            </div>
        );
    }

    attack() {
        this.props.update_monster_to_fight(this.state.monster_to_fight);
    }

    render() {
        if (this.isAnyAutomationRunning()) {
            return this.renderAutomationWarning();
        }

        return (
            <CritterSelection
                set_monster_to_fight={this.setMonsterToFight.bind(this)}
                monsters={this.buildMonsters()}
                default_monster={this.defaultMonster()}
                attack={this.attack.bind(this)}
                is_attack_disabled={this.isAttackDisabled()}
                close_monster_section={this.props.close_monster_section}
            />
        );
    }
}
