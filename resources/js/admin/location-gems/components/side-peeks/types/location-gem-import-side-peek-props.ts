import SidePeekProps from 'ui/side-peek/types/side-peek-props';

export default interface LocationGemImportSidePeekProps extends SidePeekProps {
  on_imported: () => void;
}
