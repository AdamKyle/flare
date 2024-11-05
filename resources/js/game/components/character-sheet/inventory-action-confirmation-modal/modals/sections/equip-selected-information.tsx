import React from "react";
import { SelectedItemsActionInformationProps } from "../../types/modals/sections/selected-items-action-information-props";

export default class EquipSelectedInformation extends React.Component<
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
                <p className="mb-3">
                    Below are a list of items you have selected to equip. Each
                    of these items will replace the item of that type in your
                    inventory. Should you have two weapons, shields, spells (of
                    the same type) or rings equipped, and you only choose one of
                    the two things you have equipped, we will choose the first
                    or left hand to replace by default.
                </p>
                <p className="mb-3">
                    For example, lets say you have two weapons equipped, and you
                    select one weapon to equip, we will replace the left hand by
                    default.
                </p>
                <p>
                    If you would like more control over the position to equip,
                    please close this window, select the desired item and click
                    equip.
                </p>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <div className="max-h-[250px] overflow-y-auto">
                    <span className="mb-3">
                        <strong>Items to Equip</strong>
                    </span>
                    <ul className="my-3 pl-4 list-disc ml-4">
                        {this.renderSelectedItemNames()}
                    </ul>
                </div>
            </>
        );
    }
}
