import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface MapGemImportSidePeekProps extends SidePeekProps {
  on_imported: () => void;
}
