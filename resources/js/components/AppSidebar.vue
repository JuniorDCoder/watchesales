<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Building2,
    LayoutGrid,
    Settings2,
    Store,
    Tags,
    Watch,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import { index as brandsIndex } from '@/routes/admin/brands';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { edit as editSettings } from '@/routes/admin/settings';
import { index as watchesIndex } from '@/routes/admin/watches';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Watches',
        href: watchesIndex(),
        icon: Watch,
    },
    {
        title: 'Categories',
        href: categoriesIndex(),
        icon: Tags,
    },
    {
        title: 'Brands',
        href: brandsIndex(),
        icon: Building2,
    },
    {
        title: 'Site settings',
        href: editSettings(),
        icon: Settings2,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'View storefront',
        href: home(),
        icon: Store,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
