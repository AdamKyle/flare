import React, { ReactNode } from "react";
import clsx from "clsx";
import IconButtonProps from "./types/icon-button-props";
import { baseStyles } from "./styles/button/base-styles";
import { variantStyles } from "./styles/button/variant-styles";

const IconButton = (props: IconButtonProps): ReactNode => {
    return (
        <button
            onClick={props.on_click}
            className={clsx(
                baseStyles(),
                variantStyles(props.variant),
                props.additional_css,
            )}
            aria-label={props.aria_lebel || "Icon Button"}
            disabled={props.disabled}
            role="button"
            type="button"
        >
            <div className="flex flex-col items-center">
                {props.icon}
                {props.label && (
                    <span className="text-sm mt-1 text-center">
                        {props.label}
                    </span>
                )}
            </div>
        </button>
    );
};

export default IconButton;
