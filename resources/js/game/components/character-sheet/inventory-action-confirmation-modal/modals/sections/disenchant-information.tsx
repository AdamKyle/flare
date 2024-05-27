import React from "react";

export default class DisenchantInformation extends React.Component<{}, {}> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <>
                <p>
                    Are you sure you want to do this? This action will
                    disenchant all items in your inventory. You cannot undo this
                    action.
                </p>
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
