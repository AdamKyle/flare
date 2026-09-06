export default interface AdminExcelFileDropzoneProps {
  id: string;
  label: string;
  disabled?: boolean;
  error?: string | null;
  on_file_selected: (file: File) => void;
}
