import { debounce } from 'lodash';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { v4 as uuidv4 } from 'uuid';

import ManageGuideQuestsTextContentProps from './types/manage-guide-quest-test-content-props';
import { GuideQuestContentBlockDefinition } from '../api/definitions/guide-quest-definition';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import ImageUploader from 'ui/file-upload/image-uploader';
import MarkDownEditor from 'ui/mark-down-editor/mark-down-editor';

const ManageGuideQuestsTextContent = ({
  step,
  field_key,
  on_update_content,
}: ManageGuideQuestsTextContentProps) => {
  const [blocks, set_blocks] = useState<GuideQuestContentBlockDefinition[]>([
    { id: uuidv4(), content: '', image_url: null },
  ]);

  const debounced_emit = useMemo(
    () =>
      debounce((current_blocks: GuideQuestContentBlockDefinition[]) => {
        const payload = { [field_key]: current_blocks } as unknown as Partial<
          Record<typeof field_key, GuideQuestContentBlockDefinition[]>
        >;

        on_update_content(step, payload);
      }, 300),
    [on_update_content, step, field_key]
  );

  useEffect(() => {
    debounced_emit(blocks);

    return () => {
      debounced_emit.cancel();
    };
  }, [blocks, debounced_emit]);

  const handle_markdown_change = useCallback((id: string, markdown: string) => {
    set_blocks((prev) =>
      prev.map((b) => (b.id === id ? { ...b, content: markdown ?? '' } : b))
    );
  }, []);

  const handle_file_change = useCallback((id: string, file: File | null) => {
    set_blocks((prev) =>
      prev.map((b) => (b.id === id ? { ...b, image_url: file ?? null } : b))
    );
  }, []);

  const handle_add_blank = useCallback(() => {
    set_blocks((prev) => [
      ...prev,
      { id: uuidv4(), content: '', image_url: null },
    ]);
  }, []);

  const handle_remove = useCallback((id: string) => {
    set_blocks((prev) => prev.filter((b) => b.id !== id));
  }, []);

  const render_remove_button = (can_remove: boolean, id: string) => {
    if (!can_remove) {
      return null;
    }

    return (
      <Button
        on_click={() => handle_remove(id)}
        label="Remove Section"
        variant={ButtonVariant.DANGER}
        additional_css="ml-2"
      />
    );
  };

  const render_row = (
    block: GuideQuestContentBlockDefinition,
    can_remove: boolean
  ) => {
    return (
      <div
        key={block.id}
        className="container rounded-md border-1 border-gray-500 p-2 pb-4"
      >
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <ImageUploader
            onFileChange={(file) => handle_file_change(block.id, file)}
          />
          <MarkDownEditor
            on_value_change={(md) => handle_markdown_change(block.id, md)}
          />
        </div>

        <div className="mt-4 w-full text-right">
          <Button
            on_click={handle_add_blank}
            label="Add Another Section"
            variant={ButtonVariant.PRIMARY}
          />
          {render_remove_button(can_remove, block.id)}
        </div>
      </div>
    );
  };

  const render_rows = () => {
    if (!blocks.length) {
      return null;
    }

    const can_remove = blocks.length > 1;

    return (
      <div className="space-y-4">
        {blocks.map((block) => render_row(block, can_remove))}
      </div>
    );
  };

  return <div className="w-full">{render_rows()}</div>;
};

export default ManageGuideQuestsTextContent;
