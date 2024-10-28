import React from "react";
import Button from "../../ui/buttons/button";
import CardWithTitle from "../../ui/cards/card-with-title";
import { TitleSize } from "../../ui/cards/enums/title-size";
import { ButtonVariant } from "../../ui/buttons/enums/button-variant-enum";
import IconButton from "../../ui/buttons/icon-button";

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
                </div>
            </CardWithTitle>
        );
    }
}
