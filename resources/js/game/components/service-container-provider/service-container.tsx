import React, { ReactNode } from "react";
import { useBootServiceContainer } from "./hooks/use-boot-service-container";
import ServiceContainerProps from "./types/service-container-provider-props";

export const SeerviceContainer = (props: ServiceContainerProps): ReactNode => {
    useBootServiceContainer();

    return <>{props.children}</>;
};
