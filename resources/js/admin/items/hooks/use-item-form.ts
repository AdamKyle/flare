import { useEffect, useState } from 'react';

import UseItemFormDefinition from './definitions/use-item-form-definition';
import { useItemForEdit } from '../api/hooks/use-item-for-edit';
import { useItemFormOptions } from '../api/hooks/use-item-form-options';
import { useSaveItem } from '../api/hooks/use-save-item';
import ItemFormErrorsDefinition from '../definitions/item-form-errors-definition';
import ItemFormStateDefinition from '../definitions/item-form-state-definition';
import { buildItemRequestPayload } from '../utils/build-item-request-payload';
import { createItemFormState } from '../utils/create-item-form-state';
import {
  validateAllItemFormSteps,
  validateItemFormStep,
} from '../utils/validate-item-form';

export const useItemForm = (itemId: number | null): UseItemFormDefinition => {
  const {
    form_options: formOptions,
    loading: loadingOptions,
    error: optionsError,
  } = useItemFormOptions();
  const {
    item: existingItem,
    loading: loadingItem,
    error: itemError,
  } = useItemForEdit(itemId);
  const {
    saving,
    error: saveError,
    field_errors: serverFieldErrors,
    save,
    clear_field_error: clearServerFieldError,
  } = useSaveItem();

  const [formState, setFormState] = useState<ItemFormStateDefinition>(() =>
    createItemFormState(null)
  );
  const [errors, setErrors] = useState<ItemFormErrorsDefinition>({});
  const [initialized, setInitialized] = useState(false);

  useEffect(() => {
    if (initialized) {
      return;
    }

    if (itemId !== null && !existingItem) {
      return;
    }

    setFormState(createItemFormState(existingItem));
    setInitialized(true);
  }, [initialized, itemId, existingItem]);

  const updateField: UseItemFormDefinition['update_field'] = (field, value) => {
    setFormState((previous) => ({ ...previous, [field]: value }));
    clearServerFieldError(field);
    setErrors((previous) => {
      if (!(field in previous)) {
        return previous;
      }

      const next = { ...previous };

      delete next[field];

      return next;
    });
  };

  const requestNext = (stepIndex: number): boolean => {
    const stepErrors = validateItemFormStep(stepIndex, formState);

    setErrors((previous) => ({ ...previous, ...stepErrors }));

    return Object.keys(stepErrors).length === 0;
  };

  const submit = async () => {
    const allErrors = validateAllItemFormSteps(formState);

    setErrors(allErrors);

    if (Object.keys(allErrors).length > 0) {
      return null;
    }

    const payload = buildItemRequestPayload(formState);

    return save(itemId, payload);
  };

  return {
    form_state: formState,
    update_field: updateField,
    form_options: formOptions,
    loading: loadingOptions || loadingItem || !initialized,
    load_error: optionsError ?? itemError,
    saving,
    save_error: saveError,
    field_errors: { ...errors, ...serverFieldErrors },
    request_next: requestNext,
    submit,
  };
};
