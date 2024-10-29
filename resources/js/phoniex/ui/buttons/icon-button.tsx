import React from "react";
import { match } from "ts-pattern";
import { ButtonVariant } from "./enums/button-variant-enum";
import clsx from "clsx";
import IconButtonProps from "./types/icon-button-props";

export default class IconButton extends React.Component<IconButtonProps> {
    constructor(props: IconButtonProps) {
        super(props);
    }

    baseStyles(): string {
        return (
            "px-4 py-2 text-white rounded-lg shadow focus:outline-none " +
            "focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 " +
            "disabled:opacity-75 disabled:cursor-not-allowed flex flex-col items-center"
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
                aria-label={this.props.aria_lebel || "Icon Button"}
                disabled={this.props.disabled}
                role="button"
                type="button"
            >
                <div className="flex flex-col items-center">
                    {this.props.icon}
                    {this.props.label && (
                        <span className="text-sm mt-1 text-center">
                            {this.props.label}
                        </span>
                    )}
                </div>
            </button>
        );
    }
}
