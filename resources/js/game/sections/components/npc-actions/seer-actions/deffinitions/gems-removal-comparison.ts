import OriginalAtonement, {Atonements} from "./original-atonement";

export interface GemsForComparison {
    gem_id: number;
    name: string;
}

export interface ComparisonData {
    atonement_changes: Atonements[];
    original_atonement: OriginalAtonement
}
