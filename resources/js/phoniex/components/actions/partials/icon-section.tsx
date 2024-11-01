import React from "react";
import IconContainer from "../components/icon-section/icon-container";
import IconButton from "../../../ui/buttons/icon-button";
import { ButtonVariant } from "../../../ui/buttons/enums/button-variant-enum";
import FloatingCard from "../components/icon-section/floating-card";

export default class IconSection extends React.Component {
    render() {
        return (
            <IconContainer>
                <IconButton
                    label="Character"
                    icon={
                        <i
                            className="fas fa-heart text-sm"
                            aria-hidden="true"
                        ></i>
                    }
                    variant={ButtonVariant.PRIMARY}
                    on_click={() => {}}
                    additional_css="w-full lg:w-auto"
                />
                <IconButton
                    label="Map"
                    icon={
                        <i
                            className="fas fa-shield-alt text-sm"
                            aria-hidden="true"
                        ></i>
                    }
                    variant={ButtonVariant.PRIMARY}
                    on_click={() => {}}
                    additional_css="w-full lg:w-auto"
                />
                <IconButton
                    label="Craft"
                    icon={
                        <i
                            className="fas fa-magic text-sm"
                            aria-hidden="true"
                        ></i>
                    }
                    variant={ButtonVariant.PRIMARY}
                    on_click={() => {}}
                    additional_css="w-full lg:w-auto"
                />

                <FloatingCard title="Floating Card" close_action={() => {}}>
                    <p>
                        This is a simple card content area. It will grow as
                        needed.
                    </p>
                </FloatingCard>
            </IconContainer>
        );
    }
}
