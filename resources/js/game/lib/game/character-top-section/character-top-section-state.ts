export default interface CharacterTopSectionState {

    hide_top_bar: boolean;

    loading: boolean,

    character: {
        name: string,
        class: string,
        race: string,
        gold: string,
        gold_dust: string,
        shards: string,
        copper_coins: string,
        level: string,
        ac: string,
        attack: string,
        health: string,
        str_modded: string,
        dex_modded: string,
        chr_modded: string,
        dur_modded: string,
        int_modded: string,
        agi_modded: string,
        focus_modded: string,
        max_level: string,
        xp: number,
        xp_nex: number,
        is_dead: boolean,
        can_adventure: boolean,
    } | null;
}
