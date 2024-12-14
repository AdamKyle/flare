import { useState } from "react";
import TabsProps from "../types/tabs-props";
import TabsState from "../types/tabs-state";

const useTabsState = (props: TabsProps) => {
    const { onChange } = props;

    const [tabState, setTabState] = useState<TabsState>({ selected_index: 0 });

    const handleChange = (index: number) => {
        setTabState({ selected_index: index });
        if (onChange) onChange(index);
    };

    return { tabState, handleChange };
};

export default useTabsState;
