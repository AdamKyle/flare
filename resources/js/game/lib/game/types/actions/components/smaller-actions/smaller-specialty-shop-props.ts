import {CharacterType} from "../../../../character/character-type";

export default interface SmallerSpecialtyShopProps {
    show_hell_forged_section: boolean,

    character: CharacterType,

    manage_hell_forged_shop: () => void,

    manage_purgatory_chain_shop: () => void
}
