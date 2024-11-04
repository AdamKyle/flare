import React, { ReactNode } from "react";
import IconContainer from "../../components/icon-section/icon-container";
import IconButton from "../../../../ui/buttons/icon-button";
import { ButtonVariant } from "../../../../ui/buttons/enums/button-variant-enum";
import CharacterCard from "../floating-cards/character-details/character-card";

const IconSection = (): ReactNode => {
    return (
        <IconContainer>
            <IconButton
                label="Character"
                icon={
                    <i className="ra ra-player text-sm" aria-hidden="true"></i>
                }
                variant={ButtonVariant.PRIMARY}
                on_click={() => {}}
                additional_css="w-full lg:w-auto"
            />
            <IconButton
                label="Craft"
                icon={
                    <i className="ra ra-anvil text-sm" aria-hidden="true"></i>
                }
                variant={ButtonVariant.PRIMARY}
                on_click={() => {}}
                additional_css="w-full lg:w-auto"
            />
            <IconButton
                label="Map"
                icon={
                    <i className="ra ra-compass text-sm" aria-hidden="true"></i>
                }
                variant={ButtonVariant.PRIMARY}
                on_click={() => {}}
                additional_css="w-full lg:w-auto"
            />
            <IconButton
                label="Chat"
                icon={
                    <i
                        className="far fa-comments text-sm"
                        aria-hidden="true"
                    ></i>
                }
                variant={ButtonVariant.PRIMARY}
                on_click={() => {}}
                additional_css="w-full lg:w-auto"
            />

            <CharacterCard />
        </IconContainer>
    );
};

export default IconSection;
