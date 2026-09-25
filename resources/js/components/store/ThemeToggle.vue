<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import type { Appearance } from '@/types';

const { appearance, updateAppearance } = useAppearance();

const options = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
] as const;

function select(value: unknown): void {
    updateAppearance(value as Appearance);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative rounded-full text-current hover:bg-current/10 hover:text-current"
                aria-label="Change colour theme"
            >
                <Sun
                    class="size-[18px] scale-100 rotate-0 transition-transform duration-500 dark:scale-0 dark:-rotate-90"
                />
                <Moon
                    class="absolute size-[18px] scale-0 rotate-90 transition-transform duration-500 dark:scale-100 dark:rotate-0"
                />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-36">
            <DropdownMenuRadioGroup
                :model-value="appearance"
                @update:model-value="select"
            >
                <DropdownMenuRadioItem
                    v-for="option in options"
                    :key="option.value"
                    :value="option.value"
                >
                    <component :is="option.icon" class="size-4" />
                    {{ option.label }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
