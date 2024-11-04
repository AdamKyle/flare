import React, { ReactNode } from "react";
import IconContainer from "../../components/icon-section/icon-container";
import IconButton from "../../../../ui/buttons/icon-button";
import { ButtonVariant } from "../../../../ui/buttons/enums/button-variant-enum";
import CharacterCard from "../floating-cards/character-details/character-card";
import { serviceContainer } from "../../../../service-container/core-container";
import EventSystemDeffintion from "../../../../event-system/deffintions/event-system-deffintion";
import { useManageCharacterCardVisibility } from "../floating-cards/character-details/hooks/use-manage-character-card-visibility";
import { AnimatePresence, motion } from "framer-motion";

const IconSection = (): ReactNode => {
    const eventSystem =
        serviceContainer().fetch<EventSystemDeffintion>("EventSystem");

    const { showCharacterCard, openCharacterCard } =
        useManageCharacterCardVisibility(eventSystem);

    return (
        <IconContainer>
            <IconButton
                label="Character"
                icon={
                    <i className="ra ra-player text-sm" aria-hidden="true"></i>
                }
                variant={ButtonVariant.PRIMARY}
                on_click={openCharacterCard}
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

            <AnimatePresence>
                {showCharacterCard && (
                    <motion.div
                        initial={{ x: -100, opacity: 0 }}
                        animate={{ x: 0, opacity: 1 }}
                        exit={{ x: -100, opacity: 0 }}
                        transition={{ duration: 0.5 }}
                        style={{
                            position: "absolute",
                            top: "0",
                            left: "-1rem",
                            zIndex: 10,
                        }}
                    >
                        <CharacterCard />
                    </motion.div>
                )}
            </AnimatePresence>
        </IconContainer>
    );
};

export default IconSection;
