import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface ClassImportSidePeekProps extends SidePeekProps {
  on_imported: () => void;
}
