import {CharacterType} from "../../../../../lib/game/character/character-type";

export default interface SmallerSpecialtyShopProps {
    show_hell_forged_section: boolean;

    show_purgatory_chains_section: boolean;

    show_twisted_earth_section: boolean;

    character: CharacterType;

    manage_hell_forged_shop: () => void;

    manage_purgatory_chain_shop: () => void;

    manage_twisted_earth_shop: () => void;
}
