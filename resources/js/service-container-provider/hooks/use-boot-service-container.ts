import { useEffect } from "react";

import { serviceContainer } from "../../service-container/core-container";

export const useBootServiceContainer = () => {
    useEffect(() => {
        serviceContainer();
    }, []);
};
