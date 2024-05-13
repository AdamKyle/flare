import React from "react";
import { SelectedItemsActionInformationProps } from "../../types/modals/sections/selected-items-action-information-props";

export default class DestroySelectedInformation extends React.Component<
    SelectedItemsActionInformationProps,
    {}
> {
    constructor(props: SelectedItemsActionInformationProps) {
        super(props);
    }

    renderSelectedItemNames() {
        return this.props.item_names.map((name) => {
            return <li>{name}</li>;
        });
    }

    render() {
        return (
            <>
                <p>
                    Below are a set of items you have selected to be destroyed.
                    Are you sure you want to do this?{" "}
                    <strong>You cannot undo this action</strong>.
                </p>
                <ul className="my-3 pl-4 list-disc ml-4">
                    {this.renderSelectedItemNames()}
                </ul>
            </>
        );
    }
}
