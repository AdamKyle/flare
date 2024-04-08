export default interface EnchantingProps {

    character_id: number;

    user_id: number;

    cannot_craft: boolean;

    remove_crafting: () => {}
}
