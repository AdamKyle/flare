import { BatchCraftingStatus } from "../batch-crafting-status-display";

export default interface BatchCraftingStatusDisplayProps {
    status: BatchCraftingStatus;
    character_id: number;
    isSaving: boolean;
    onCancel: () => void;
    onDismiss: () => void;
    onClose?: () => void;
}
