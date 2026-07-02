import TopsValue from "../../../../shared/types/tops-value";

export default interface ProfileClassDetailModalProps {
    classRank: Record<string, TopsValue>;
    onClose: () => void;
    onSelectSpecialty: (specialty: Record<string, TopsValue>) => void;
}
