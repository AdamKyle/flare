import React from "react";
import Button from "../../ui/buttons/button";
import CardWithTitle from "../../ui/cards/card-with-title";
import { TitleSize } from "../../ui/cards/enums/title-size";
import { ButtonVariant } from "../../ui/buttons/enums/button-variant-enum";
import IconButton from "../../ui/buttons/icon-button";
import LinkButton from "../../ui/buttons/link-button";
import GradientButton from "../../ui/buttons/gradient-button";
import { ButtonGradientVarient } from "../../ui/buttons/enums/button-gradient-variant";

export default class UIComponents extends React.Component {
    render() {
        return (
            <CardWithTitle title="UI Components" title_size={TitleSize.H2}>
                <div className="my-4">
                    <h3 className="my-4">Buttons</h3>
                    <div className="my-4">
                        <Button
                            variant={ButtonVariant.DANGER}
                            on_click={() => {}}
                            label={"Sample Danger Button"}
                        />
                        <Button
                            variant={ButtonVariant.SUCCESS}
                            on_click={() => {}}
                            label={"Sample Success Button"}
                            additional_css="ml-4"
                        />
                        <Button
                            variant={ButtonVariant.PRIMARY}
                            on_click={() => {}}
                            label={"Sample Primary Button"}
                            additional_css="ml-4"
                        />
                    </div>
                    <div className="mb-4">
                        <IconButton
                            variant={ButtonVariant.DANGER}
                            on_click={() => {}}
                            icon={<i className="ra ra-muscle-up" />}
                        />
                        <IconButton
                            variant={ButtonVariant.SUCCESS}
                            on_click={() => {}}
                            icon={<i className="ra ra-wooden-sign" />}
                            additional_css="ml-4"
                        />
                        <IconButton
                            variant={ButtonVariant.PRIMARY}
                            on_click={() => {}}
                            icon={<i className="ra ra-flat-hammer" />}
                            additional_css="ml-4"
                        />
                    </div>
                    <div className="mb-4">
                        <LinkButton
                            variant={ButtonVariant.DANGER}
                            on_click={() => {}}
                            label="Danger Link Button"
                        />
                        <LinkButton
                            variant={ButtonVariant.SUCCESS}
                            on_click={() => {}}
                            label="Success Link Button"
                            additional_css="ml-4"
                        />
                        <LinkButton
                            variant={ButtonVariant.PRIMARY}
                            on_click={() => {}}
                            label="Primary Link Button"
                            additional_css="ml-4"
                        />
                    </div>
                    <div className="mb-4">
                        <GradientButton
                            gradient={ButtonGradientVarient.DANGER_TO_PRIMARY}
                            on_click={() => {}}
                            label="Danger To Primary Gradient Button"
                        />
                        <GradientButton
                            gradient={ButtonGradientVarient.PRIMARY_TO_DANGER}
                            on_click={() => {}}
                            label="Primary To Danger Gradient Button"
                            additional_css="ml-4"
                        />
                    </div>
                    <div className="mb-4">
                        <Button
                            variant={ButtonVariant.DANGER}
                            on_click={() => {}}
                            label={"Sample Danger Button Disabled"}
                            disabled={true}
                        />
                        <Button
                            variant={ButtonVariant.SUCCESS}
                            on_click={() => {}}
                            label={"Sample Success Button Disabled"}
                            disabled={true}
                            additional_css="ml-4"
                        />
                        <Button
                            variant={ButtonVariant.PRIMARY}
                            on_click={() => {}}
                            label={"Sample Primary Button Disabled"}
                            disabled={true}
                            additional_css="ml-4"
                        />
                    </div>
                    <div className="mb-4">
                        <IconButton
                            variant={ButtonVariant.DANGER}
                            on_click={() => {}}
                            icon={<i className="ra ra-muscle-up" />}
                            disabled={true}
                        />
                        <IconButton
                            variant={ButtonVariant.SUCCESS}
                            on_click={() => {}}
                            icon={<i className="ra ra-wooden-sign" />}
                            disabled={true}
                            additional_css="ml-4"
                        />
                        <IconButton
                            variant={ButtonVariant.PRIMARY}
                            on_click={() => {}}
                            icon={<i className="ra ra-flat-hammer" />}
                            disabled={true}
                            additional_css="ml-4"
                        />
                    </div>
                    <div className="mb-4">
                        <LinkButton
                            variant={ButtonVariant.DANGER}
                            on_click={() => {}}
                            label="Danger link button disabled"
                            disabled={true}
                        />
                        <LinkButton
                            variant={ButtonVariant.SUCCESS}
                            on_click={() => {}}
                            label="Success link button disabled"
                            disabled={true}
                            additional_css="ml-4"
                        />
                        <LinkButton
                            variant={ButtonVariant.PRIMARY}
                            on_click={() => {}}
                            label="Primary link button disabled"
                            disabled={true}
                            additional_css="ml-4"
                        />
                    </div>
                    <div className="mb-4">
                        <GradientButton
                            gradient={ButtonGradientVarient.DANGER_TO_PRIMARY}
                            on_click={() => {}}
                            label="Danger To Primary Gradient Disabled Button"
                            disabled={true}
                        />
                        <GradientButton
                            gradient={ButtonGradientVarient.PRIMARY_TO_DANGER}
                            on_click={() => {}}
                            label="Primary To Danger Gradient Disabeled Button"
                            additional_css="ml-4"
                            disabled={true}
                        />
                    </div>
                </div>
            </CardWithTitle>
        );
    }
}
