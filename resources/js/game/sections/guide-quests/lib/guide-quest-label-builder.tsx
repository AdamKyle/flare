import React from "react";

const guideQuestLabelBuilder = (
    key: string,
    questData: any
): JSX.Element | string | null => {
    switch (key) {
        case "required_level":
            return "Level your character to";
        case "required_game_map_id":
            return "Get Access to";
        case "required_quest_id":
            return "Complete the quest";
        case "required_quest_item_id":
            return "Get Quest Item";
        case "secondary_quest_item_id":
            return "Get Secondary Quest Item";
        case "required_skill":
            return (
                <span>
                    Get Skill:{" "}
                    {buildLabelLink(
                        questData.skill_name,
                        "required_skill",
                        questData
                    )}{" "}
                    to level
                </span>
            );
        case "required_skill_type":
            return `Get Skill Type: ${questData.skill_type_name} to level`;
        case "required_secondary_skill":
            return (
                <span>
                    Get Secondary Skill:{" "}
                    {buildLabelLink(
                        questData.secondary_skill_name,
                        "required_secondary_skill",
                        questData
                    )}{" "}
                    to level
                </span>
            );
        case "skill_type_name":
            return `Get Skill Type: ${questData.skill_type_name} to level`;
        case "required_faction_id":
            return `Get Faction ${questData.faction_name} to level`;
        case "required_mercenary_type":
            return `Purchase Mercenary: ${questData.mercenary_name}`;
        case "required_secondary_mercenary_type":
            return `Purchase Secondary Mercenary: ${questData.secondary_mercenary_name}`;
        case "required_mercenary_level":
            return `Get Mercenary ${questData.mercenary_name} to level`;
        case "required_secondary_mercenary_level":
            return `Get Secondary Mercenary ${questData.mercenary_name} to level`;
        case "required_class_specials_equipped":
            return "Equip # of Class Specials";
        case "required_class_rank_level":
            return "Level Your Current Class Rank To Level:";
        case "required_kingdoms":
            return "Required Kingdom #";
        case "required_kingdom_level":
            return "Required Buildings Level (Combined)";
        case "required_kingdom_units":
            return "Required Units Amount (Combined)";
        case "required_passive_skill":
            return (
                <span>
                    Get Passive Skill:{" "}
                    {buildLabelLink(
                        questData.passive_name,
                        "required_passive_skill",
                        questData
                    )}{" "}
                    to level
                </span>
            );
        case "required_stats":
            return "Get all stats to";
        case "required_str":
            return "Get STR to";
        case "required_dex":
            return "Get DEX to";
        case "required_agi":
            return "Get AGI to";
        case "required_dur":
            return "Get DUR to";
        case "required_int":
            return "Get INT to";
        case "required_chr":
            return "Get CHR to";
        case "required_focus":
            return "Get Focus to";
        case "required_gold":
            return "Obtain Gold amount";
        case "required_gold_dust":
            return "Obtain Gold Dust amount";
        case "required_shards":
            return "Obtain Shards amount";
        default:
            return null;
    }
};

const buildLabelLink = (
    name: string,
    key: string,
    questData: any
): JSX.Element | string => {
    switch (key) {
        case "required_skill":
            return (
                <a
                    href={"/information/skills/" + questData.required_skill}
                    target="_blank"
                >
                    {name} <i className="fas fa-external-link-alt"></i>
                </a>
            );
        case "required_secondary_skill":
            return (
                <a
                    href={
                        "/information/skills/" +
                        questData.required_secondary_skill
                    }
                    target="_blank"
                >
                    {name} <i className="fas fa-external-link-alt"></i>
                </a>
            );
        case "required_passive_skill":
            return (
                <a
                    href={
                        "/information/passive-skill/" +
                        questData.required_passive_skill
                    }
                    target="_blank"
                >
                    {name} <i className="fas fa-external-link-alt"></i>
                </a>
            );
        default:
            return name;
    }
};

const getRequirementKey = (labelKey: string): string => {
    switch (labelKey) {
        case "required_quest_id":
            return "quest_name";
        case "required_game_map_id":
            return "game_map_name";
        case "required_quest_item_id":
            return "quest_item_name";
        case "secondary_quest_item_id":
            return "secondary_quest_item_name";
        case "required_skill":
            return "required_skill_level";
        case "required_secondary_skill":
            return "required_secondary_skill_level";
        case "required_skill_type":
            return "required_skill_type_level";
        case "required_faction_id":
            return "required_faction_level";
        case "required_mercenary_type":
            return "required_mercenary_level";
        case "required_secondary_mercenary_type":
            return "required_secondary_mercenary_level";
        case "required_passive_skill":
            return "required_passive_level";
        default:
            return labelKey;
    }
};

const buildValueLink = (
    name: string,
    key: string,
    questData: any
): JSX.Element | string | number => {
    switch (key) {
        case "required_quest_id":
            return (
                <a
                    href={"/information/quests/" + questData.required_quest_id}
                    target="_blank"
                >
                    {name} <i className="fas fa-external-link-alt"></i>
                </a>
            );
        case "required_game_map_id":
            return (
                <a
                    href={"/information/map/" + questData.required_game_map_id}
                    target="_blank"
                >
                    {name} <i className="fas fa-external-link-alt"></i>
                </a>
            );
        case "required_quest_item_id":
            return (
                <a
                    href={"/items/" + questData.required_quest_item_id}
                    target="_blank"
                >
                    {name} <i className="fas fa-external-link-alt"></i>
                </a>
            );
        case "secondary_quest_item_id":
            return (
                <a
                    href={"/items/" + questData.secondary_quest_item_id}
                    target="_blank"
                >
                    {name} <i className="fas fa-external-link-alt"></i>
                </a>
            );
        default:
            return name;
    }
};

export { guideQuestLabelBuilder, getRequirementKey, buildValueLink };
