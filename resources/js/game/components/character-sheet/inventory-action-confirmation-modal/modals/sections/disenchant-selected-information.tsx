import React from "react";
import { SelectedItemsActionInformationProps } from "../../types/modals/sections/selected-items-action-information-props";

export default class DisenchantSelectedInformation extends React.Component<
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
                    Are you sure you want to do this? This action will
                    disenchant all selected items below.{" "}
                    <strong>You cannot undo this action</strong>.
                </p>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <span className="mb-3">
                    <strong>Items to Disenchant</strong>
                </span>
                <div className="max-h-[250px] overflow-y-auto">
                    <ul className="my-3 pl-4 list-disc ml-4">
                        {this.renderSelectedItemNames()}
                    </ul>
                </div>
                <div className="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
                <p className="mt-2">
                    When you disenchant items you will get some{" "}
                    <a href={"/information/currencies"} target="_blank">
                        Gold Dust <i className="fas fa-external-link-alt"></i>
                    </a>{" "}
                    and experience towards{" "}
                    <a href={"/information/disenchanting"} target="_blank">
                        Disenchanting{" "}
                        <i className="fas fa-external-link-alt"></i>
                    </a>{" "}
                    and half XP towards{" "}
                    <a href={"/information/enchanting"} target="_blank">
                        Enchanting <i className="fas fa-external-link-alt"></i>
                    </a>
                    .
                </p>
                <p className="mt-2">
                    Tip for crafters/enchanters: Equip a set that's full
                    enchanting when doing your mass disenchanting, because the
                    XP you get, while only half, can be boosted. For new
                    players, you should be crafting and enchanting and then
                    disenchanting or selling your equipment on the market, if it
                    is not viable for you.
                </p>
            </>
        );
    }
}
