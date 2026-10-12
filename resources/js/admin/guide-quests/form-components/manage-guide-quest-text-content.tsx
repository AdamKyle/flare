import React, { ReactNode } from 'react';
import { v4 as uuidv4 } from 'uuid';

import ManageGuideQuestTextContentProps from './types/manage-guide-quest-text-content-props';
import { GuideQuestContentBlockDefinition } from '../api/definitions/guide-quest-definition';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import ImageUploader from 'ui/file-upload/image-uploader';
import MarkDownEditor from 'ui/mark-down-editor/mark-down-editor';

const newBlock = (): GuideQuestContentBlockDefinition => ({
  id: uuidv4(),
  content: '',
  image_url: null,
});

const ManageGuideQuestsTextContent = ({
  field_key: fieldKey,
  value,
  on_change: onChange,
}: ManageGuideQuestTextContentProps): ReactNode => {
  const updateBlock = (
    id: string,
    changes: Partial<GuideQuestContentBlockDefinition>
  ): void => {
    onChange(
      fieldKey,
      value.map((block) => (block.id === id ? { ...block, ...changes } : block))
    );
  };

  const addSection = (): void => onChange(fieldKey, [...value, newBlock()]);
  const removeSection = (id: string): void =>
    onChange(
      fieldKey,
      value.filter((block) => block.id !== id)
    );

  return (
    <div className="w-full space-y-4">
      {value.map((block) => (
        <div
          key={block.id}
          className="container rounded-md border-1 border-gray-500 p-2 pb-4"
        >
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <ImageUploader
              initialImageUrl={
                typeof block.image_url === 'string' ? block.image_url : null
              }
              onFileChange={(file) =>
                updateBlock(block.id, { image_url: file })
              }
              onDelete={() => updateBlock(block.id, { image_url: null })}
            />
            <MarkDownEditor
              id={block.id}
              initial_markdown={block.content}
              on_value_change={(content) => updateBlock(block.id, { content })}
            />
          </div>
          <div className="mt-4 flex justify-end gap-2">
            <Button
              on_click={addSection}
              label="Add Another Section"
              variant={ButtonVariant.PRIMARY}
            />
            {value.length > 1 && (
              <Button
                on_click={() => removeSection(block.id)}
                label="Remove Section"
                variant={ButtonVariant.DANGER}
              />
            )}
          </div>
        </div>
      ))}
      {value.length === 0 && (
        <Button
          on_click={addSection}
          label="Add Section"
          variant={ButtonVariant.PRIMARY}
        />
      )}
    </div>
  );
};

export default ManageGuideQuestsTextContent;
