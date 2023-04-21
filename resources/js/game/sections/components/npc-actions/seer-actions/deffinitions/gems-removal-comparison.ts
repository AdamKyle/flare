import OriginalAtonement, {Atonements} from "../../../../../lib/game/types/core/atonement/definitions/original-atonement";

export interface GemsForComparison {
    gem_id: number;
    name: string;
}

export interface ComparisonData {
    atonement_changes: Atonements[];
    original_atonement: OriginalAtonement
}
