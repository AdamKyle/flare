export function getUrl(
  urlEnum: string,
  params?: Record<string, number>
): string {
  let url = urlEnum;

  if (!params) {
    return url;
  }

  for (const [key, value] of Object.entries(params)) {
    url = url.replace(`{${key}}`, value.toString());
  }

  return url;
}
