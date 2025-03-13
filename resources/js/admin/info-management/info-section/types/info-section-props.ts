import InfoSectionContent from "./info-section-content";

export default interface InfoSectionProps {
    index: number;
    sections_length: number;
    content: InfoSectionContent;
    update_parent_element: (index: number, content: InfoSectionContent) => void;
    remove_section: (index: number) => void;
    add_section: (() => void) | null;
    save_and_finish: () => void;
    update_section: (index: number) => void;
}
