import React, { ReactNode } from "react";
import ButtonProps from "./types/button-props";
import clsx from "clsx";
import { variantStyles } from "./styles/button/variant-styles";
import { baseStyles } from "./styles/button/base-styles";

const Button = (props: ButtonProps): ReactNode => {
    return (
        <button
            onClick={props.on_click}
            className={clsx(
                baseStyles(),
                variantStyles(props.variant),
                props.additional_css,
            )}
            aria-label={props.aria_lebel || props.label}
            disabled={props.disabled}
            role="button"
            type="button"
        >
            {props.label}
        </button>
    );
};

export default Button;
