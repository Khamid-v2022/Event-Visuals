<script setup lang="ts">
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from '@lucide/vue';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    page: number;
    lastPage: number;
    loading?: boolean;
}>();

const emit = defineEmits<{
    goToPage: [page: number];
}>();

const pageInput = ref(String(props.page));

watch(
    () => props.page,
    (value) => {
        pageInput.value = String(value);
    },
);

function submitPage() {
    const target = Number.parseInt(pageInput.value, 10);

    if (Number.isNaN(target)) {
        pageInput.value = String(props.page);

        return;
    }

    emit('goToPage', target);
}
</script>

<template>
    <nav
        class="flex flex-wrap items-center justify-center gap-2"
        aria-label="Event pagination"
    >
        <Button
            variant="outline"
            size="icon"
            :disabled="page <= 1 || loading"
            aria-label="First page"
            @click="emit('goToPage', 1)"
        >
            <ChevronsLeft class="size-4" />
        </Button>

        <Button
            variant="outline"
            size="icon"
            :disabled="page <= 1 || loading"
            aria-label="Previous page"
            @click="emit('goToPage', page - 1)"
        >
            <ChevronLeft class="size-4" />
        </Button>

        <form class="flex items-center gap-2" @submit.prevent="submitPage">
            <Input
                v-model="pageInput"
                type="number"
                min="1"
                :max="lastPage"
                inputmode="numeric"
                class="h-9 w-20 bg-background text-center tabular-nums"
                aria-label="Page number"
                :disabled="loading"
            />
            <span class="text-sm text-muted-foreground">/ {{ lastPage.toLocaleString() }}</span>
            <Button type="submit" variant="secondary" size="sm" :disabled="loading">
                Go
            </Button>
        </form>

        <Button
            variant="outline"
            size="icon"
            :disabled="page >= lastPage || loading"
            aria-label="Next page"
            @click="emit('goToPage', page + 1)"
        >
            <ChevronRight class="size-4" />
        </Button>

        <Button
            variant="outline"
            size="icon"
            :disabled="page >= lastPage || loading"
            aria-label="Last page"
            @click="emit('goToPage', lastPage)"
        >
            <ChevronsRight class="size-4" />
        </Button>
    </nav>
</template>
