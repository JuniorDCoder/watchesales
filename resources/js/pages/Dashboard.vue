<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    CameraOff,
    Eye,
    EyeOff,
    Hourglass,
    Lightbulb,
    MessagesSquare,
    MousePointerClick,
    Plus,
    SearchX,
    TrendingDown,
    TrendingUp,
    Users,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref } from 'vue';
import BarList from '@/components/admin/BarList.vue';
import DailyChart from '@/components/admin/DailyChart.vue';
import PageHeader from '@/components/admin/PageHeader.vue';
import StatCard from '@/components/admin/StatCard.vue';
import StatusBadge from '@/components/store/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { useSite } from '@/composables/useSite';
import { formatNumber, formatRelativeTime } from '@/lib/format';
import { dashboard } from '@/routes';
import {
    create as createWatch,
    edit as editWatch,
} from '@/routes/admin/watches';
import type { WatchStatus } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

type Comparison = { current: number; previous: number };
type Breakdown = { name: string; visits: number; share: number };
type WatchPerformance = {
    id: number;
    name: string;
    brand: string;
    slug: string;
    imageUrl: string | null;
    price: number | null;
    status: WatchStatus;
    statusLabel: string;
    views: number;
    inquiries: number;
    conversion: number;
};

const props = defineProps<{
    period: { days: number; start: string; end: string; options: number[] };
    kpis: {
        visits: Comparison;
        watchViews: Comparison;
        inquiries: Comparison;
        inquiryRate: Comparison;
    };
    series: {
        date: string;
        visits: number;
        watchViews: number;
        inquiries: number;
    }[];
    sources: Breakdown[];
    devices: Breakdown[];
    channels: {
        channel: string;
        label: string;
        total: number;
        share: number;
    }[];
    watches: WatchPerformance[];
    searches: {
        total: number;
        top: { term: string; count: number; results: number }[];
        unmet: { term: string; count: number }[];
    };
    inventory: {
        total: number;
        available: number;
        reserved: number;
        sold: number;
        unpublished: number;
        availableValue: number;
        soldInPeriod: number;
        soldValueInPeriod: number;
        averageDaysToSell: number | null;
        averageDaysListed: number | null;
    };
    attention: {
        type: string;
        title: string;
        detail: string;
        watchId: number | null;
    }[];
    insights: { tone: 'positive' | 'negative' | 'neutral'; text: string }[];
    recentInquiries: {
        id: number;
        channel: string;
        watch: { id: number; name: string; brand: string } | null;
        created_at: string;
    }[];
}>();

const { price } = useSite();
const loading = ref(false);

const dateFormatter = new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
});
const periodLabel = computed(() => {
    const start = dateFormatter.format(
        new Date(`${props.period.start}T00:00:00`),
    );
    const end = dateFormatter.format(new Date(`${props.period.end}T00:00:00`));

    return `${start} to ${end}, compared with the previous ${props.period.days} days`;
});
/** Human friendly duration for the inventory panel. */
function formatDays(days: number | null, empty: string): string {
    if (days === null) {
        return empty;
    }

    if (days === 0) {
        return 'Same day';
    }

    return days === 1 ? '1 day' : `${days} days`;
}

const comparison = computed(() => `vs previous ${props.period.days} days`);

function changePeriod(days: number): void {
    router.get(
        dashboard.url({ query: { period: days } }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (loading.value = true),
            onFinish: () => (loading.value = false),
        },
    );
}

const visitsSeries = computed(() =>
    props.series.map((day) => ({ date: day.date, total: day.visits })),
);
const inquiriesSeries = computed(() =>
    props.series.map((day) => ({ date: day.date, total: day.inquiries })),
);
const hasTraffic = computed(() => props.kpis.visits.current > 0);

const sourceItems = computed(() =>
    props.sources.map((row) => ({
        name: row.name,
        value: row.visits,
        share: row.share,
    })),
);
const deviceItems = computed(() =>
    props.devices.map((row) => ({
        name: row.name,
        value: row.visits,
        share: row.share,
    })),
);
const channelItems = computed(() =>
    props.channels.map((row) => ({
        name: row.label,
        value: row.total,
        share: row.share,
    })),
);

type SortKey = 'inquiries' | 'views' | 'conversion';
const sortOptions: [SortKey, string][] = [
    ['inquiries', 'Inquiries'],
    ['views', 'Views'],
    ['conversion', 'Inquiry rate'],
];
const sortKey = ref<SortKey>('inquiries');
const sortedWatches = computed(() =>
    [...props.watches]
        .sort(
            (a, b) => b[sortKey.value] - a[sortKey.value] || b.views - a.views,
        )
        .slice(0, 10),
);

const insightIcons: Record<string, Component> = {
    positive: TrendingUp,
    negative: TrendingDown,
    neutral: Lightbulb,
};

const attentionIcons: Record<string, Component> = {
    no_inquiries: MousePointerClick,
    no_photos: CameraOff,
    slow_mover: Hourglass,
    hidden: EyeOff,
};
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex flex-1 flex-col gap-6 p-4 transition-opacity md:p-6"
        :class="{ 'opacity-60': loading }"
    >
        <PageHeader title="Overview" :description="periodLabel">
            <div
                class="inline-flex rounded-lg bg-muted p-1"
                role="group"
                aria-label="Reporting period"
            >
                <button
                    v-for="days in period.options"
                    :key="days"
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        days === period.days
                            ? 'bg-background shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    :aria-pressed="days === period.days"
                    @click="changePeriod(days)"
                >
                    {{ days }} days
                </button>
            </div>
            <Button as-child>
                <Link :href="createWatch()"
                    ><Plus class="size-4" /> Add watch</Link
                >
            </Button>
        </PageHeader>

        <div class="grid gap-4 xl:grid-cols-3">
            <section class="rounded-xl border bg-card p-5 xl:col-span-2">
                <h2 class="font-medium">What stands out</h2>
                <ul class="mt-4 space-y-3">
                    <li
                        v-for="(insight, index) in insights"
                        :key="index"
                        class="flex gap-3 text-sm leading-relaxed"
                    >
                        <span
                            class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full"
                            :class="{
                                'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400':
                                    insight.tone === 'positive',
                                'bg-rose-500/10 text-rose-700 dark:text-rose-400':
                                    insight.tone === 'negative',
                                'bg-muted text-muted-foreground':
                                    insight.tone === 'neutral',
                            }"
                        >
                            <component
                                :is="insightIcons[insight.tone]"
                                class="size-3.5"
                            />
                        </span>
                        {{ insight.text }}
                    </li>
                </ul>
            </section>

            <section class="rounded-xl border bg-card p-5">
                <h2 class="font-medium">Inventory</h2>
                <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-5">
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Available to buy
                        </dt>
                        <dd class="mt-0.5 text-2xl font-semibold tabular-nums">
                            {{ inventory.available }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Value available
                        </dt>
                        <dd class="mt-0.5 text-2xl font-semibold tabular-nums">
                            {{ price(inventory.availableValue) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Sold in period
                        </dt>
                        <dd class="mt-0.5 text-2xl font-semibold tabular-nums">
                            {{ inventory.soldInPeriod }}
                        </dd>
                        <p
                            v-if="inventory.soldInPeriod"
                            class="text-xs text-muted-foreground tabular-nums"
                        >
                            {{ price(inventory.soldValueInPeriod) }} at list
                            price
                        </p>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Average time to sell
                        </dt>
                        <dd class="mt-0.5 text-2xl font-semibold tabular-nums">
                            {{
                                formatDays(
                                    inventory.averageDaysToSell,
                                    'No sales yet',
                                )
                            }}
                        </dd>
                        <p class="text-xs text-muted-foreground">
                            Over the last 12 months
                        </p>
                    </div>
                </dl>
                <p class="mt-5 border-t pt-4 text-xs text-muted-foreground">
                    {{ inventory.total }} watches in total:
                    {{ inventory.reserved }} reserved,
                    {{ inventory.sold }} sold,
                    {{ inventory.unpublished }} hidden.
                    <template v-if="inventory.averageDaysListed">
                        Available watches have been listed for
                        {{ formatDays(inventory.averageDaysListed, '') }} on
                        average.
                    </template>
                </p>
            </section>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                label="Visits"
                :value="formatNumber(kpis.visits.current)"
                :icon="Users"
                :current="kpis.visits.current"
                :previous="kpis.visits.previous"
                :comparison="comparison"
                hint="One person on one day"
                :trend="series.map((day) => day.visits)"
            />
            <StatCard
                label="Watch page views"
                :value="formatNumber(kpis.watchViews.current)"
                :icon="Eye"
                :current="kpis.watchViews.current"
                :previous="kpis.watchViews.previous"
                :comparison="comparison"
                hint="Views of individual watches"
                :trend="series.map((day) => day.watchViews)"
            />
            <StatCard
                label="Inquiries"
                :value="formatNumber(kpis.inquiries.current)"
                :icon="MessagesSquare"
                :current="kpis.inquiries.current"
                :previous="kpis.inquiries.previous"
                :comparison="comparison"
                hint="Chat, WhatsApp and email"
                :trend="series.map((day) => day.inquiries)"
            />
            <StatCard
                label="Inquiry rate"
                :value="`${kpis.inquiryRate.current}%`"
                :icon="MousePointerClick"
                :current="kpis.inquiryRate.current"
                :previous="kpis.inquiryRate.previous"
                :comparison="comparison"
                hint="Visits that led to an inquiry"
            />
        </div>

        <section class="rounded-xl border bg-card p-5">
            <div class="grid gap-8 lg:grid-cols-2">
                <div>
                    <h2 class="font-medium">Visits per day</h2>
                    <p class="text-sm text-muted-foreground">
                        People browsing the storefront, excluding staff and
                        bots.
                    </p>
                    <div class="mt-5">
                        <DailyChart
                            :data="visitsSeries"
                            title="Visits per day"
                            :unit="{ one: 'visit', many: 'visits' }"
                        />
                    </div>
                </div>
                <div>
                    <h2 class="font-medium">Inquiries per day</h2>
                    <p class="text-sm text-muted-foreground">
                        Every contact through chat, WhatsApp or email.
                    </p>
                    <div class="mt-5">
                        <DailyChart
                            :data="inquiriesSeries"
                            title="Inquiries per day"
                            :unit="{ one: 'inquiry', many: 'inquiries' }"
                        />
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-3">
            <section class="rounded-xl border bg-card p-5">
                <h2 class="font-medium">Where visitors come from</h2>
                <p class="text-sm text-muted-foreground">Visits by source.</p>
                <div class="mt-5">
                    <BarList
                        v-if="sourceItems.length"
                        :items="sourceItems"
                        value-label="visits"
                    />
                    <p v-else class="text-sm text-muted-foreground">
                        No visits yet.
                    </p>
                </div>
            </section>
            <section class="rounded-xl border bg-card p-5">
                <h2 class="font-medium">Devices</h2>
                <p class="text-sm text-muted-foreground">
                    What visitors browse on.
                </p>
                <div class="mt-5">
                    <BarList
                        v-if="deviceItems.length"
                        :items="deviceItems"
                        value-label="visits"
                    />
                    <p v-else class="text-sm text-muted-foreground">
                        No visits yet.
                    </p>
                </div>
            </section>
            <section class="rounded-xl border bg-card p-5">
                <h2 class="font-medium">Inquiry channels</h2>
                <p class="text-sm text-muted-foreground">
                    How people chose to get in touch.
                </p>
                <div class="mt-5">
                    <BarList :items="channelItems" value-label="inquiries" />
                </div>
            </section>
        </div>

        <section class="overflow-hidden rounded-xl border bg-card">
            <div
                class="flex flex-wrap items-end justify-between gap-3 p-5 pb-4"
            >
                <div>
                    <h2 class="font-medium">Watch performance</h2>
                    <p class="text-sm text-muted-foreground">
                        Which listings attract attention and which turn it into
                        inquiries.
                    </p>
                </div>
                <div
                    class="inline-flex rounded-lg bg-muted p-1 text-xs"
                    role="group"
                    aria-label="Sort watches by"
                >
                    <button
                        v-for="[key, label] in sortOptions"
                        :key="key"
                        type="button"
                        class="rounded-md px-2.5 py-1 font-medium transition-colors"
                        :class="
                            sortKey === key
                                ? 'bg-background shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        :aria-pressed="sortKey === key"
                        @click="sortKey = key"
                    >
                        {{ label }}
                    </button>
                </div>
            </div>
            <div v-if="sortedWatches.length" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead
                        class="border-y bg-muted/40 text-left text-xs text-muted-foreground"
                    >
                        <tr>
                            <th scope="col" class="px-5 py-2.5 font-medium">
                                Watch
                            </th>
                            <th
                                scope="col"
                                class="hidden px-5 py-2.5 font-medium md:table-cell"
                            >
                                Status
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-2.5 text-right font-medium"
                            >
                                Views
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-2.5 text-right font-medium"
                            >
                                Inquiries
                            </th>
                            <th
                                scope="col"
                                class="px-5 py-2.5 text-right font-medium"
                            >
                                Inquiry rate
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="watch in sortedWatches"
                            :key="watch.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-5 py-3">
                                <Link
                                    :href="editWatch(watch.id)"
                                    class="flex items-center gap-3"
                                >
                                    <img
                                        v-if="watch.imageUrl"
                                        :src="watch.imageUrl"
                                        :alt="watch.name"
                                        class="size-10 rounded-md object-cover"
                                        loading="lazy"
                                    />
                                    <span
                                        v-else
                                        class="size-10 rounded-md bg-muted"
                                    />
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium"
                                            >{{ watch.brand }}
                                            {{ watch.name }}</span
                                        >
                                        <span
                                            class="block text-xs text-muted-foreground tabular-nums"
                                            >{{ price(watch.price) }}</span
                                        >
                                    </span>
                                </Link>
                            </td>
                            <td class="hidden px-5 py-3 md:table-cell">
                                <StatusBadge
                                    :status="watch.status"
                                    :label="watch.statusLabel"
                                />
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                {{ formatNumber(watch.views) }}
                            </td>
                            <td
                                class="px-5 py-3 text-right font-medium tabular-nums"
                            >
                                {{ watch.inquiries }}
                            </td>
                            <td class="px-5 py-3 text-right tabular-nums">
                                <span v-if="watch.views >= 10"
                                    >{{ watch.conversion }}%</span
                                >
                                <span
                                    v-else
                                    class="text-muted-foreground"
                                    title="Needs at least 10 views to be meaningful"
                                    >Too early</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="px-5 pb-6 text-sm text-muted-foreground">
                No watch has been viewed in this period yet.
            </p>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-xl border bg-card">
                <div class="p-5 pb-3">
                    <h2 class="font-medium">Needs attention</h2>
                    <p class="text-sm text-muted-foreground">
                        Listings that could be doing better.
                    </p>
                </div>
                <ul v-if="attention.length" class="divide-y">
                    <li v-for="(item, index) in attention" :key="index">
                        <component
                            :is="item.watchId ? Link : 'div'"
                            :href="
                                item.watchId
                                    ? editWatch(item.watchId)
                                    : undefined
                            "
                            class="flex items-start gap-3 px-5 py-3"
                            :class="{
                                'transition-colors hover:bg-muted/40':
                                    item.watchId,
                            }"
                        >
                            <span
                                class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-400"
                            >
                                <component
                                    :is="attentionIcons[item.type]"
                                    class="size-3.5"
                                />
                            </span>
                            <span class="min-w-0 text-sm">
                                <span class="block font-medium">{{
                                    item.title
                                }}</span>
                                <span class="block text-muted-foreground">{{
                                    item.detail
                                }}</span>
                            </span>
                        </component>
                    </li>
                </ul>
                <p v-else class="px-5 pb-6 text-sm text-muted-foreground">
                    Everything looks healthy.
                </p>
            </section>

            <section class="rounded-xl border bg-card p-5">
                <h2 class="font-medium">What visitors are looking for</h2>
                <p class="text-sm text-muted-foreground">
                    {{ searches.total }}
                    {{ searches.total === 1 ? 'search' : 'searches' }} in this
                    period.
                </p>
                <div
                    v-if="searches.total"
                    class="mt-5 grid gap-6 sm:grid-cols-2"
                >
                    <div>
                        <h3
                            class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            Top searches
                        </h3>
                        <ol class="mt-3 space-y-2 text-sm">
                            <li
                                v-for="search in searches.top"
                                :key="search.term"
                                class="flex justify-between gap-3"
                            >
                                <span class="truncate">{{ search.term }}</span>
                                <span
                                    class="text-muted-foreground tabular-nums"
                                    >{{ search.count }}</span
                                >
                            </li>
                        </ol>
                    </div>
                    <div>
                        <h3
                            class="flex items-center gap-1.5 text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            <SearchX class="size-3.5" /> Searched, not found
                        </h3>
                        <ol
                            v-if="searches.unmet.length"
                            class="mt-3 space-y-2 text-sm"
                        >
                            <li
                                v-for="search in searches.unmet"
                                :key="search.term"
                                class="flex justify-between gap-3"
                            >
                                <span class="truncate">{{ search.term }}</span>
                                <span
                                    class="text-muted-foreground tabular-nums"
                                    >{{ search.count }}</span
                                >
                            </li>
                        </ol>
                        <p v-else class="mt-3 text-sm text-muted-foreground">
                            Every search found a match.
                        </p>
                        <p
                            v-if="searches.unmet.length"
                            class="mt-3 text-xs text-muted-foreground"
                        >
                            Demand you are not meeting yet. Consider sourcing
                            these.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <section class="rounded-xl border bg-card">
            <div class="p-5 pb-3">
                <h2 class="font-medium">Latest inquiries</h2>
                <p class="text-sm text-muted-foreground">
                    The most recent people who got in touch.
                </p>
            </div>
            <ul v-if="recentInquiries.length" class="grid sm:grid-cols-2">
                <li
                    v-for="inquiry in recentInquiries"
                    :key="inquiry.id"
                    class="flex items-start gap-3 border-t px-5 py-3"
                >
                    <span
                        class="mt-1.5 size-2 shrink-0 rounded-full bg-chart-1"
                        aria-hidden="true"
                    />
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="truncate">
                            <span class="font-medium">{{
                                inquiry.channel
                            }}</span>
                            <span class="text-muted-foreground">
                                {{
                                    inquiry.watch
                                        ? ` about the ${inquiry.watch.brand} ${inquiry.watch.name}`
                                        : ' general inquiry'
                                }}
                            </span>
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatRelativeTime(inquiry.created_at) }}
                        </p>
                    </div>
                    <Link
                        v-if="inquiry.watch"
                        :href="editWatch(inquiry.watch.id)"
                        class="text-muted-foreground hover:text-foreground"
                        :aria-label="`Edit ${inquiry.watch.name}`"
                    >
                        <ArrowUpRight class="size-4" />
                    </Link>
                </li>
            </ul>
            <p v-else class="px-5 pb-6 text-sm text-muted-foreground">
                No inquiries yet. They appear here the moment someone gets in
                touch.
            </p>
        </section>

        <p v-if="!hasTraffic" class="text-center text-xs text-muted-foreground">
            Visits are counted from real visitors only. Staff, search engine
            crawlers and link previews are excluded.
        </p>
    </div>
</template>
