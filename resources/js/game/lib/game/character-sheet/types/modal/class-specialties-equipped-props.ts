import {AdditionalInfoModalProps} from "./additional-info-modal-props";
import ClassRankType from "../class-ranks/class-rank-type";

export default interface ClassSpecialtiesEquippedProps extends AdditionalInfoModalProps {

    class_rank: ClassRankType | null;
}
