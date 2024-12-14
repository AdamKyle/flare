export default interface ConditionalDataTableRowsStyling {
    when: (row: any) => boolean;

    style: { backgroundColor: string; color: string };
}
