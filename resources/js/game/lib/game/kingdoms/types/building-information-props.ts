import BuildingDetails from "../building-details";

export default interface BuildingInformationProps {
    building: BuildingDetails;

    close: (building?: BuildingDetails) => void;
}
