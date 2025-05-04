export const getUrl = (
  chanelUrl: string,
  params?: Record<string, number>
): string => {
  let url = chanelUrl;
  if (params) {
    for (const [key, val] of Object.entries(params)) {
      url = url.replace(`{${key}}`, String(val));
    }
  }
  return url;
};
