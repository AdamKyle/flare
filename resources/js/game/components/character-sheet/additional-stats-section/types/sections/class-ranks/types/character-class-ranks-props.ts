import ClassRankType from "../deffinitions/class-rank-type";
import ClassRankOfferedType from "../deffinitions/class-rank-offered-type";

export default interface CharacterClassRanksProps {
    character: any;

    // When true, renders this component in a read-only capacity: no
    // "Switch To" action column, no class switching calls, and no
    // switching-class success/error alerts or loading bar. Used by
    // read-only inspection surfaces (e.g. Tops character profile).
    read_only?: boolean;

    // When supplied, the component initializes its class ranks from this
    // list instead of fetching `class-ranks/{character.id}` in
    // componentDidMount.
    preloaded_class_ranks?: ClassRankType[];

    // When supplied (alongside `read_only`), enables the additional
    // "Class Ranks" detail summary (progress above level 1 vs. what
    // remains) for the currently selected class.
    preloaded_class_ranks_offered?: ClassRankOfferedType[];

    // When true (alongside `read_only`), renders a weapon-masteries-only
    // view for every class rank instead of the normal class ranks table:
    // levelled weapon masteries and remaining weapon masteries, with no
    // "Switch To" action and no specialties. Used by the public Tops
    // Class Masteries view.
    masteries_only?: boolean;

    [key: string]: any;
}
