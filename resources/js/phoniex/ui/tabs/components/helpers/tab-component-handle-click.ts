import TabComponentProps from "../../types/components/tab-component-props";

export const handleTableComponentClickEvent = (
    props: TabComponentProps,
): void => {
    props.on_click(props.index);
};
