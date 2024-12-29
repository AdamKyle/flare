// resources/js/dts/vite-env.d.ts
/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_BASE_IMAGE_URL: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}
