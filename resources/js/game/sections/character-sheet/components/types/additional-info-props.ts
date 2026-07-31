import CharacterSheetProps from "../../../../lib/game/character-sheet/types/character-sheet-props";
import { CharacterType } from "../../../../lib/game/character/character-type";
import StatDetailsType from "../../../../components/character-sheet/additional-stats-section/types/stat-details-type";
import ResistanceInfoType from "../../../../components/character-sheet/additional-stats-section/types/resistance-info-type";
import ElementalAtonementType from "../../../../components/character-sheet/additional-stats-section/types/elemental-atonement-type";
import ReincarnationDetailsType from "../../../../components/character-sheet/additional-stats-section/types/reincarnation-details-type";
import ClassRankSpecialtiesPreloadedType from "../../../../components/character-sheet/additional-stats-section/types/class-rank-specialties-preloaded-type";
import ClassRankType from "../../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/deffinitions/class-rank-type";
import ClassRankOfferedType from "../../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/deffinitions/class-rank-offered-type";

export interface AdditionalInfoProps extends CharacterSheetProps {
    is_open: boolean;

    manage_modal: () => void;

    title: string;

    character: CharacterType | null;

    when_tab_changes?: (
        tabIndex: number,
        tabs: { key: string; name: string }[],
    ) => void;

    // When true, renders every additional-details child in a read-only
    // capacity: no authenticated Ajax calls, no mutation controls (equip,
    // unequip, switch class). Used by read-only inspection surfaces (e.g.
    // the Tops character profile).
    read_only?: boolean;

    // Preloaded data supplied by a read-only caller so the relevant child
    // section can initialize from it instead of calling the authenticated
    // Character Sheet endpoint.
    preloaded_stat_details?: StatDetailsType | null;

    preloaded_resistance_info?: ResistanceInfoType | null;

    preloaded_elemental_atonement?: ElementalAtonementType | null;

    preloaded_reincarnation_details?: ReincarnationDetailsType;

    preloaded_class_ranks?: ClassRankType[];

    preloaded_class_ranks_offered?: ClassRankOfferedType[];

    preloaded_class_rank_specialties?: ClassRankSpecialtiesPreloadedType;
}
