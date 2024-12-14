import React, { ReactNode } from "react";
import MonsterTopSection from "../components/fight-section/monster-top-section";
import AttackButtonsContainer from "../components/fight-section/attack-buttons-container";
import HealthBarContainer from "../components/fight-section/health-bar-container";
import HealthBar from "../components/fight-section/health-bar";
import { HealthBarType } from "../components/fight-section/enums/health-bar-type";
import Button from "../../../ui/buttons/button";
import { ButtonVariant } from "../../../ui/buttons/enums/button-variant-enum";
import GradientButton from "../../../ui/buttons/gradient-button";
import { ButtonGradientVarient } from "../../../ui/buttons/enums/button-gradient-variant";
import Separator from "../../../ui/seperatror/separator";
import AttackMessages from "../components/fight-section/attack-messages";
import AttackMessageDeffintion from "../components/fight-section/deffinitions/attack-message-deffinition";
import { AttackMessageType } from "../components/fight-section/enums/attack-message-type";

const MonsterSection = (): ReactNode => {
    const messages: AttackMessageDeffintion[] = [
        {
            message: "You Attack for 150,000 Damage!",
            type: AttackMessageType.PLAYER_ATTACK,
        },
        {
            message: "Your spells charge and magic crackles in the air!",
            type: AttackMessageType.REGULAR,
        },
        {
            message:
                "The enemy stops your spells and attack you for 125,000 Damage",
            type: AttackMessageType.ENEMY_ATTACK,
        },
    ];

    return (
        <>
            <MonsterTopSection
                img_src="https://placecats.com/250/250"
                next_action={() => (currentIndex: number) => {}}
                prev_action={() => (currentIndex: number) => {}}
                view_stats_action={() => (monsterId: number) => {}}
                monster_name="Sewer Rat"
            />
            <HealthBarContainer>
                <HealthBar
                    current_health={100}
                    max_health={100}
                    name="Sewer Rat"
                    health_bar_type={HealthBarType.ENEMY}
                />
                <HealthBar
                    current_health={100}
                    max_health={100}
                    name="Credence"
                    health_bar_type={HealthBarType.PLAYER}
                />
            </HealthBarContainer>
            <AttackButtonsContainer>
                <Button
                    label="Attack"
                    variant={ButtonVariant.PRIMARY}
                    additional_css="w-full lg:w-1/3"
                    on_click={() => {}}
                />
                <Button
                    label="Cast"
                    variant={ButtonVariant.PRIMARY}
                    additional_css="w-full lg:w-1/3"
                    on_click={() => {}}
                />
            </AttackButtonsContainer>
            <AttackButtonsContainer>
                <GradientButton
                    label="Atk & Cast"
                    gradient={ButtonGradientVarient.DANGER_TO_PRIMARY}
                    additional_css="w-full lg:w-1/3"
                    on_click={() => {}}
                />
                <GradientButton
                    label="Cast & Atk"
                    gradient={ButtonGradientVarient.PRIMARY_TO_DANGER}
                    additional_css="w-full lg:w-1/3"
                    on_click={() => {}}
                />
            </AttackButtonsContainer>
            <AttackButtonsContainer>
                <Button
                    label="Defend"
                    variant={ButtonVariant.PRIMARY}
                    additional_css="w-full lg:w-1/3"
                    on_click={() => {}}
                />
            </AttackButtonsContainer>
            <Separator additional_css="w-full lg:w-1/5 mx-auto my-6" />
            <AttackMessages messages={messages} />
        </>
    );
};

export default MonsterSection;
