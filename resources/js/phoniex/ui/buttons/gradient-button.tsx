import React, { ReactNode } from "react";
import clsx from "clsx";
import GradientButtonProps from "./types/gradient-button-props";
import { baseStyles } from "./styles/gradient-button/base-styles";
import { variantStyles } from "./styles/gradient-button/variant-styles";

const GradientButton = (props: GradientButtonProps): ReactNode => {
    return (
        <button
            onClick={props.on_click}
            className={clsx(
                baseStyles(),
                variantStyles(props.gradient),
                props.additional_css,
            )}
            aria-label={props.aria_lebel || props.label}
            disabled={props.disabled}
            role="button"
            type="button"
        >
            <span className="relative z-10">{props.label}</span>
        </button>
    );
};

export default GradientButton;
