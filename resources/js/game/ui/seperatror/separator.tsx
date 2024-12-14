import React, { ReactNode } from "react";
import SeperatorProps from "./types/seperator-props";
import clsx from "clsx";

const Separator = (props: SeperatorProps): ReactNode => {
    return (
        <div
            className={clsx(
                "border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3",
                props.additional_css,
            )}
        ></div>
    );
};

export default Separator;
