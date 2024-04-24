import React from "react";
import Shop from "../specialty-shops/shop";
import SmallerSpecialtyShopProps from "./types/smaller-specialty-shop-props";
import DangerAlert from "../../../../components/ui/alerts/simple-alerts/danger-alert";

export default class SmallerSpecialtyShop extends React.Component<
    SmallerSpecialtyShopProps,
    {}
> {
    constructor(props: SmallerSpecialtyShopProps) {
        super(props);
    }

    getTypeOfShop(): string | null {
        if (this.props.show_hell_forged_section) {
            return "Hell Forged";
        }

        if (this.props.show_purgatory_chains_section) {
            return "Purgatory Chains";
        }

        if (this.props.show_twisted_earth_section) {
            return "Twisted Earth";
        }

        return null;
    }

    render() {
        const type = this.getTypeOfShop();

        if (type === null) {
            return (
                <DangerAlert>
                    Unknown type of shop to render. Something is wrong.
                </DangerAlert>
            );
        }

        return (
            <Shop
                type={type}
                character_id={this.props.character.id}
                close_hell_forged={this.props.manage_hell_forged_shop.bind(
                    this,
                )}
                close_purgatory_chains={this.props.manage_purgatory_chain_shop.bind(
                    this,
                )}
            />
        );
    }
}
