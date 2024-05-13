import React from "react";

export default class SellInformation extends React.Component<{}, {}> {
    constructor(props: any) {
        super(props);
    }

    render() {
        return (
            <>
                <p>
                    Are you sure? You are about to sell all items in your
                    inventory (this does not effect Alchemy, Quest items, Sets,
                    Gems or Equipped items). This action cannot be undone. Also,
                    trinkets cannot be sold to the shop. They can be listed to
                    the market or destroyed.
                </p>
                <p className="mt-2">
                    <strong>Note</strong>: The amount of gold you will get back
                    for items that are enchanted or crafted over the price of
                    two billion gold will never be sold for{" "}
                    <strong>more than</strong> two billion gold. Ie, a 36
                    billion gold item will only sell for two billion gold before
                    taxes.
                </p>
                <p className="mt-2">
                    It is highly recommended you use the market place to sell
                    anything beyond shop gear to make your money back.
                </p>
            </>
        );
    }
}
