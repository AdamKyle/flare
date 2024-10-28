import React from "react";
import ButtonProps from "./types/button-props";
import { match } from "ts-pattern";
import { ButtonVariant } from "./enums/button-variant-enum";
import clsx from "clsx";

export default class Button extends React.Component<ButtonProps> {
    constructor(props: ButtonProps) {
        super(props);
    }

    baseStyles(): string {
        return (
            "px-4 py-2 text-white rounded-lg shadow focus:outline-none " +
            "focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 " +
            "disabled:opacity-75 disabled:cursor-not-allowed"
        );
    }

    variantStyles(): string {
        const variant = this.props.variant;

        return match(variant)
            .with(
                ButtonVariant.DANGER,
                () =>
                    "bg-rose-600 hover:bg-rose-500 focus:ring-rose-400 dark:focus:ring-rose-600",
            )
            .with(
                ButtonVariant.SUCCESS,
                () =>
                    "bg-emerald-600 hover:bg-emerald-500 focus:ring-emerald-400 dark:focus:ring-emerald-600",
            )
            .with(
                ButtonVariant.PRIMARY,
                () =>
                    "bg-danube-600 hover:bg-danube-500 focus:ring-danube-400 dark:focus:ring-danube-600",
            )
            .otherwise(() => "");
    }

    render() {
        return (
            <button
                onClick={this.props.on_click}
                className={clsx(
                    this.baseStyles(),
                    this.variantStyles(),
                    this.props.additional_css,
                )}
                aria-label={
                    this.props.aria_lebel
                        ? this.props.aria_lebel
                        : this.props.label
                }
                disabled={this.props.disabled}
            >
                {this.props.label}
            </button>
        );
    }
}
