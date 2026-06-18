import { ref, watch, type Ref } from 'vue';
import type { VisualEvent } from '@/types/event';

interface ResolveAddressResponse {
    address: string | null;
}

/** Reverse-geocode event coordinates when the detail modal opens. */
export function useEventDetailAddress(
    event: Ref<VisualEvent | null>,
    open: Ref<boolean>,
) {
    const address = ref<string | null>(null);
    const loading = ref(false);
    const error = ref(false);

    let requestId = 0;

    function reset() {
        requestId++;
        address.value = null;
        loading.value = false;
        error.value = false;
    }

    async function fetchAddress(latitude: number, longitude: number) {
        if (latitude === 0 && longitude === 0) {
            reset();

            return;
        }

        const currentRequest = ++requestId;
        loading.value = true;
        error.value = false;
        address.value = null;

        try {
            const params = new URLSearchParams({
                lat: String(latitude),
                lng: String(longitude),
            });
            const response = await fetch(`/events-visual-1/address?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(`Request failed (${response.status})`);
            }

            const payload = (await response.json()) as ResolveAddressResponse;

            if (currentRequest === requestId) {
                address.value = payload.address;
            }
        } catch {
            if (currentRequest === requestId) {
                error.value = true;
            }
        } finally {
            if (currentRequest === requestId) {
                loading.value = false;
            }
        }
    }

    watch(
        [open, () => event.value?.id],
        ([isOpen]) => {
            if (!isOpen || !event.value) {
                reset();

                return;
            }

            fetchAddress(event.value.latitude, event.value.longitude);
        },
        { immediate: true },
    );

    return { address, loading, error };
}
