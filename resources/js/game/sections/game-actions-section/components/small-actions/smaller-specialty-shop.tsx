import React from "react";
import Shop from "../specialty-shops/shop";
import SmallerSpecialtyShopProps
    from "./types/smaller-specialty-shop-props";

export default class SmallerSpecialtyShop extends React.Component<SmallerSpecialtyShopProps, { }> {

    constructor(props: SmallerSpecialtyShopProps) {
        super(props);
    }

    render() {
        return (
            <Shop
                type={this.props.show_hell_forged_section ? 'Hell Forged' : 'Purgatory Chains'}
                character_id={this.props.character.id}
                close_hell_forged={this.props.manage_hell_forged_shop.bind(this)}
                close_purgatory_chains={this.props.manage_purgatory_chain_shop .bind(this)}
            />
        )
    }
}
