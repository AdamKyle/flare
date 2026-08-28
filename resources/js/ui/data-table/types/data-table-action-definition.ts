export default interface DataTableActionDefinition<TRow> {
  key: string;
  label: string;
  on_click: (row: TRow) => void;
  aria_label?: (row: TRow) => string;
}
