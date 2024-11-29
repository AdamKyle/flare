import React from "react";
import { SelectedItemsActionInformationProps } from "../../types/modals/sections/selected-items-action-information-props";

export default class SellSelectedInformation extends React.Component<
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
                    Are you sure? You are about to sell the selected items to
                    the shop. Should an items value go beyond the shop: 2
                    Billion Gold, then the item will only be sold for 2 Billion
                    gold. It is suggested players use the market to sell more
                    valuable items.
                    <strong>This action cannot be undone.</strong>
                </p>

                <div className="max-h-[250px] overflow-y-auto">
                    <ul className="my-3 pl-4 list-disc ml-4">
                        {this.renderSelectedItemNames()}
                    </ul>
                </div>
            </>
        );
    }
}
