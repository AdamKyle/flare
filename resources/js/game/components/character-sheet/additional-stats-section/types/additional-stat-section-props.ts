import { CharacterType } from "../../../../lib/game/character/character-type";
import StatDetailsType from "./stat-details-type";
import ResistanceInfoType from "./resistance-info-type";
import ElementalAtonementType from "./elemental-atonement-type";
import ReincarnationDetailsType from "./reincarnation-details-type";
import ClassRankSpecialtiesPreloadedType from "./class-rank-specialties-preloaded-type";
import ClassRankType from "./sections/class-ranks/deffinitions/class-rank-type";
import ClassRankOfferedType from "./sections/class-ranks/deffinitions/class-rank-offered-type";

export default interface AdditionalStatSectionProps {
    character: CharacterType | null;

    // When true, every child section renders read-only: no authenticated
    // Ajax calls and no mutation controls (equip, unequip, switch class).
    // Used by read-only inspection surfaces (e.g. the Tops character
    // profile).
    read_only?: boolean;

    preloaded_stat_details?: StatDetailsType | null;

    preloaded_resistance_info?: ResistanceInfoType | null;

    preloaded_elemental_atonement?: ElementalAtonementType | null;

    preloaded_reincarnation_details?: ReincarnationDetailsType;

    preloaded_class_ranks?: ClassRankType[];

    preloaded_class_ranks_offered?: ClassRankOfferedType[];

    preloaded_class_rank_specialties?: ClassRankSpecialtiesPreloadedType;
}
