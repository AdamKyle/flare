import InfoSectionData from "./info-section-data";

export default interface InfoManagementState {
    info_sections: InfoSectionData[];
    page_name: string;
    error_message: string | null;
    success_message: string | null;
    loading: boolean;
    posting: boolean;
    posting_index: number;
}
