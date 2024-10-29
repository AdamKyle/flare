import React from "react";
import { match } from "ts-pattern";
import clsx from "clsx";
import GradientButtonProps from "./types/gradient-button-props";
import { ButtonGradientVarient } from "./enums/button-gradient-variant";

export default class GradientButton extends React.Component<GradientButtonProps> {
    constructor(props: GradientButtonProps) {
        super(props);
    }

    baseStyles(): string {
        return (
            "px-4 py-2 text-white font-bold rounded-lg shadow focus:outline-none " +
            "focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 " +
            "disabled:opacity-75 disabled:cursor-not-allowed shadow-lg"
        );
    }

    getVarientStyle(): string {
        return match(this.props.gradient)
            .with(
                ButtonGradientVarient.DANGER_TO_PRIMARY,
                () =>
                    "bg-gradient-to-b from-rose-600 to-danube-600 hover:from-rose-500 hover:to-danube-500 focus:ring-rose-400 dark:focus:ring-danube-600",
            )
            .with(
                ButtonGradientVarient.PRIMARY_TO_DANGER,
                () =>
                    "bg-gradient-to-b from-danube-600 to-rose-600 hover:from-danube-500 hover:to-rose-500 focus:ring-danube-400 dark:focus:ring-danube-600",
            )
            .otherwise(() => "");
    }

    render() {
        return (
            <button
                onClick={this.props.on_click}
                className={clsx(
                    this.baseStyles(),
                    this.getVarientStyle(),
                    this.props.additional_css,
                )}
                aria-label={this.props.aria_lebel || this.props.label}
                disabled={this.props.disabled}
                role="button"
                type="button"
            >
                <span className="relative z-10">{this.props.label}</span>
            </button>
        );
    }
}
