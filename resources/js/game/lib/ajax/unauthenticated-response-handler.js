export function isUnauthenticatedResponse(errorOrResponse) {
    const response = errorOrResponse?.response ?? errorOrResponse;
    const status = response?.status;
    const data = response?.data ?? {};
    const message = String(data.message ?? data.error ?? "").toLowerCase();

    return (
        status === 401 ||
        status === 419 ||
        message === "unauthenticated" ||
        message === "unauthenticated." ||
        message.includes("not logged in") ||
        message.includes("session expired")
    );
}

export function handleUnauthenticatedResponse(errorOrResponse) {
    if (!isUnauthenticatedResponse(errorOrResponse)) {
        return false;
    }

    window.location.reload();

    return true;
}
