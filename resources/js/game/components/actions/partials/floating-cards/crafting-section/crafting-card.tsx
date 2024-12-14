import React, { ReactNode } from "react";
import FloatingCard from "../../../components/icon-section/floating-card";
import Button from "../../../../../../ui/buttons/button";
import { ButtonVariant } from "../../../../../../ui/buttons/enums/button-variant-enum";
import { serviceContainer } from "../../../../../../service-container/core-container";
import EventSystemDeffintion from "../../../../../../event-system/deffintions/event-system-deffintion";
import { ActionCardEvents } from "../EventTypes/action-cards";

const CraftingCard = (): ReactNode => {
    const eventSystem =
        serviceContainer().fetch<EventSystemDeffintion>("EventSystem");

    const handleCloseCard = () => {
        const event = eventSystem.getEventEmitter<{ [key: string]: boolean }>(
            ActionCardEvents.CLOSE_CRATING_CARD,
        );

        event.emit(ActionCardEvents.CLOSE_CRATING_CARD, true);
    };

    return (
        <FloatingCard title="Crafting" close_action={handleCloseCard}>
            <Button
                label="Craft"
                on_click={() => {}}
                variant={ButtonVariant.PRIMARY}
                additional_css="w-full my-2"
            />
            <Button
                label="Enchant"
                on_click={() => {}}
                variant={ButtonVariant.PRIMARY}
                additional_css="w-full my-2"
            />
            <Button
                label="Alchemy"
                on_click={() => {}}
                variant={ButtonVariant.PRIMARY}
                additional_css="w-full my-2"
            />
            <Button
                label="Trinketry"
                on_click={() => {}}
                variant={ButtonVariant.PRIMARY}
                additional_css="w-full my-2"
            />
            <Button
                label="Gem Crafting"
                on_click={() => {}}
                variant={ButtonVariant.PRIMARY}
                additional_css="w-full my-2"
            />
            <Button
                label="Queen of Hearts"
                on_click={() => {}}
                variant={ButtonVariant.PRIMARY}
                additional_css="w-full my-2"
            />
            <Button
                label="Seer Camp"
                on_click={() => {}}
                variant={ButtonVariant.PRIMARY}
                additional_css="w-full my-2"
            />
            <Button
                label="Work Bench"
                on_click={() => {}}
                variant={ButtonVariant.PRIMARY}
                additional_css="w-full my-2"
            />
            <Button
                label="Labyrinth Oracle"
                on_click={() => {}}
                variant={ButtonVariant.PRIMARY}
                additional_css="w-full my-2"
            />
        </FloatingCard>
    );
};

export default CraftingCard;
