import React, { ReactNode } from "react";

import AttackButtonsContainerProps from "./types/attack-buttons-container-props";

const AttackButtonsContainer = (
    props: AttackButtonsContainerProps,
): ReactNode => {
    return (
        <div className="mx-auto mt-4 flex flex-col sm:flex-row items-center justify-center w-full lg:1/4 xl:w-1/3 gap-y-4 gap-x-3 text-lg leading-none">
            {props.children}
        </div>
    );
};

export default AttackButtonsContainer;
