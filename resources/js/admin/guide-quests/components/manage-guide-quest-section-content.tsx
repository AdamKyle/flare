import React, { useCallback, useState } from 'react';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import ImageUploader from 'ui/file-upload/image-uploader';
import Input from 'ui/input/input';
import MarkDownEditor from 'ui/mark-down-editor/mark-down-editor';

const ManageGuideQuestSectionContent = () => {
  const [sectionTitle, setSectionTitle] = useState<string>('');
  const [contentBlock, setContentBlock] = useState<string>('');
  const [imageFile, setImageFile] = useState<File | null>(null);

  const handleChangeTitle = useCallback((value: string) => {
    setSectionTitle(value);
  }, []);

  const handleFileChange = useCallback((file: File | null) => {
    setImageFile(file);
  }, []);

  const handleMarkdownChange = useCallback((markdown: string) => {
    setContentBlock(markdown);
  }, []);

  const handleAddAnotherSection = useCallback(() => {
    const payload = {
      content_block: contentBlock,
      image: imageFile,
    };

    const formData = new FormData();

    formData.append('content_block', contentBlock ?? '');

    if (imageFile) {
      formData.append('image', imageFile);
    }

    // Useful console output to verify contents:
    // 1) Plain object (easy to read)
    // 2) FormData entries (what will be sent to the backend)
    // Note: Logging FormData directly won't show entries reliably across browsers.
    // Keeping console usage only here, as requested.

    console.log('section payload (object):', payload);

    console.log('section payload (FormData):', Array.from(formData.entries()));
  }, [contentBlock, imageFile]);

  return (
    <div className="container rounded-md border-1 border-gray-500 p-2 pb-4">
      <div className="my-2">
        <Input
          on_change={handleChangeTitle}
          value={sectionTitle}
          place_holder="Section Title"
        />
      </div>

      <div className="grid grid-cols-2 gap-4">
        <ImageUploader onFileChange={handleFileChange} />
        <MarkDownEditor on_value_change={handleMarkdownChange} />
      </div>

      <div className="mt-4 w-full text-right">
        <Button
          on_click={handleAddAnotherSection}
          label="Add Another Section"
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          on_click={() => {}}
          label="Remove Section"
          variant={ButtonVariant.DANGER}
          additional_css="ml-2"
        />
      </div>
    </div>
  );
};

export default ManageGuideQuestSectionContent;
