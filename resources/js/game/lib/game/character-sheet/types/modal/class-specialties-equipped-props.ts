import {AdditionalInfoProps} from "../../../../../sections/character-sheet/components/types/additional-info-props";
import ClassRankType from "../../../../../components/character-sheet/additional-stats-section/types/sections/class-ranks/deffinitions/class-rank-type";

export default interface ClassSpecialtiesEquippedProps extends AdditionalInfoProps {
    selected_type: string;
}
