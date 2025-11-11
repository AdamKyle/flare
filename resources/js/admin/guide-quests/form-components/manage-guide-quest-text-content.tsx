import { debounce } from 'lodash';
import React, { useCallback, useEffect, useRef, useState } from 'react';
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
  const [contentBlocks, setContentBlocks] = useState<
    GuideQuestContentBlockDefinition[]
  >([{ id: uuidv4(), content: '', image_url: null }]);

  const latestRefs = useRef({
    on_update_content,
    step,
    field_key,
  });

  useEffect(() => {
    latestRefs.current.on_update_content = on_update_content;
    latestRefs.current.step = step;
    latestRefs.current.field_key = field_key;
  }, [on_update_content, step, field_key]);

  const debouncedEmitRef = useRef(
    debounce((currentBlocks: GuideQuestContentBlockDefinition[]) => {
      const {
        on_update_content: onUpdate,
        step: currentStep,
        field_key: fieldKey,
      } = latestRefs.current;

      const payload = {
        [fieldKey]: currentBlocks,
      } as unknown as Partial<
        Record<typeof field_key, GuideQuestContentBlockDefinition[]>
      >;

      onUpdate(currentStep, payload);
    }, 300)
  );

  useEffect(() => {
    return () => {
      debouncedEmitRef.current.cancel();
    };
  }, []);

  const handleMarkdownChange = useCallback((id: string, markdown: string) => {
    setContentBlocks((previousBlocks) => {
      const nextBlocks = previousBlocks.map((block) =>
        block.id === id ? { ...block, content: markdown ?? '' } : block
      );
      debouncedEmitRef.current(nextBlocks);
      return nextBlocks;
    });
  }, []);

  const handleFileChange = useCallback((id: string, file: File | null) => {
    setContentBlocks((previousBlocks) => {
      const nextBlocks = previousBlocks.map((block) =>
        block.id === id ? { ...block, image_url: file ?? null } : block
      );
      debouncedEmitRef.current(nextBlocks);
      return nextBlocks;
    });
  }, []);

  const handleAddSection = useCallback(() => {
    setContentBlocks((previousBlocks) => {
      const nextBlocks = [
        ...previousBlocks,
        { id: uuidv4(), content: '', image_url: null },
      ];
      debouncedEmitRef.current(nextBlocks);
      return nextBlocks;
    });
  }, []);

  const handleRemoveSection = useCallback((id: string) => {
    setContentBlocks((previousBlocks) => {
      const nextBlocks = previousBlocks.filter((block) => block.id !== id);
      debouncedEmitRef.current(nextBlocks);
      return nextBlocks;
    });
  }, []);

  const renderRemoveButton = (canRemove: boolean, id: string) => {
    if (!canRemove) {
      return null;
    }

    return (
      <Button
        on_click={() => handleRemoveSection(id)}
        label="Remove Section"
        variant={ButtonVariant.DANGER}
        additional_css="ml-2"
      />
    );
  };

  const renderRow = (
    block: GuideQuestContentBlockDefinition,
    canRemove: boolean
  ) => {
    return (
      <div
        key={block.id}
        className="container rounded-md border-1 border-gray-500 p-2 pb-4"
      >
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <ImageUploader
            onFileChange={(file) => handleFileChange(block.id, file)}
          />
          <MarkDownEditor
            on_value_change={(markdown) =>
              handleMarkdownChange(block.id, markdown)
            }
          />
        </div>

        <div className="mt-4 w-full text-right">
          <Button
            on_click={handleAddSection}
            label="Add Another Section"
            variant={ButtonVariant.PRIMARY}
          />
          {renderRemoveButton(canRemove, block.id)}
        </div>
      </div>
    );
  };

  const renderRows = () => {
    if (!contentBlocks.length) {
      return null;
    }

    const canRemove = contentBlocks.length > 1;

    return (
      <div className="space-y-4">
        {contentBlocks.map((block) => renderRow(block, canRemove))}
      </div>
    );
  };

  return <div className="w-full">{renderRows()}</div>;
};

export default ManageGuideQuestsTextContent;
