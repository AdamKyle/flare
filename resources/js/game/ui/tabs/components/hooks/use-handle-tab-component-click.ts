import { useCallback } from "react";
import TabComponentProps from "../../types/components/tab-component-props";
import { handleTableComponentClickEvent } from "../helpers/tab-component-handle-click";

export const useHandleTabComponentClick = (props: TabComponentProps) => {
    return useCallback(() => {
        handleTableComponentClickEvent(props);
    }, [props]);
};
