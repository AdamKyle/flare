export async function runCapitalCityRequestBatches<T>(
    batches: T[],
    maxConcurrent: number,
    processBatch: (batch: T) => Promise<void>,
    onBatchSuccess: (batch: T) => void,
): Promise<void> {
    if (batches.length === 0) {
        return;
    }

    return new Promise<void>((resolve, reject) => {
        let nextIndex = 0;
        let activeCount = 0;
        let failed = false;

        function dispatch(): void {
            while (
                !failed &&
                activeCount < maxConcurrent &&
                nextIndex < batches.length
            ) {
                const batch = batches[nextIndex];
                nextIndex++;
                activeCount++;

                processBatch(batch)
                    .then(() => {
                        activeCount--;
                        onBatchSuccess(batch);

                        if (activeCount === 0 && nextIndex >= batches.length) {
                            resolve();
                        } else {
                            dispatch();
                        }
                    })
                    .catch((error: unknown) => {
                        activeCount--;
                        if (!failed) {
                            failed = true;
                            reject(error);
                        }
                    });
            }
        }

        dispatch();
    });
}
