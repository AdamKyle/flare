import React, { ReactNode } from "react";

import HealthBarContainerProps from "./types/health-bar-container-props";

const HealthBarContainer = (props: HealthBarContainerProps): ReactNode => {
    return (
        <div
            className="
                w-full lg:w-3/4 xl:w-2/3 mx-auto mt-4 flex items-center justify-center
                gap-x-3 text-lg leading-none
            "
        >
            <div className="w-full lg:w-3/4 xl:w-1/3">{props.children}</div>
        </div>
    );
};

export default HealthBarContainer;
