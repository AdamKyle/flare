import React from "react";
import { match } from "ts-pattern";
import { ButtonVariant } from "./enums/button-variant-enum";
import clsx from "clsx";
import LinkButtonProps from "./types/link-button-props";

export default class LinkButton extends React.Component<LinkButtonProps> {
    constructor(props: LinkButtonProps) {
        super(props);
    }

    baseStyles(): string {
        return (
            "inline-flex items-center justify-center px-0 py-0 text-sm font-medium " +
            "transition-colors duration-200 ease-in-out focus:outline-none " +
            "focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 " +
            "disabled:opacity-75 disabled:cursor-not-allowed underline rounded-sm"
        );
    }

    variantStyles(): string {
        return match(this.props.variant)
            .with(
                ButtonVariant.DANGER,
                () =>
                    "text-rose-600 dark:text-rose-500 hover:text-rose-500 dark:hover:text-rose-400 focus:ring-rose-400 dark:focus:ring-rose-600",
            )
            .with(
                ButtonVariant.SUCCESS,
                () =>
                    "text-emerald-600 dark:text-emerald-500 hover:text-emerald-500 dark:hover:text-emerald-400 focus:ring-emerald-400 dark:focus:ring-emerald-600",
            )
            .with(
                ButtonVariant.PRIMARY,
                () =>
                    "text-danube-600 dark:text-danbue-500 hover:text-danube-500 dark:hover:text-danbue-400 focus:ring-danube-400 dark:focus:ring-danube-600",
            )
            .otherwise(() => "");
    }

    render() {
        return (
            <button
                type="button"
                onClick={this.props.on_click}
                className={clsx(
                    this.baseStyles(),
                    this.variantStyles(),
                    this.props.additional_css,
                )}
                disabled={this.props.disabled}
                aria-label={this.props.aria_label || this.props.label}
                role="button"
            >
                {this.props.label}
            </button>
        );
    }
}
