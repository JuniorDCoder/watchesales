export type Option = {
    value: string;
    label: string;
};

export type WatchStatus = 'available' | 'reserved' | 'sold';

export type InquiryChannel = 'chatwoot' | 'whatsapp' | 'email';

export type WatchImage = {
    id: number;
    url: string;
    alt: string | null;
    sort_order: number;
};

export type Watch = {
    id: number;
    name: string;
    slug: string;
    reference: string | null;
    summary: string | null;
    price: number | null;
    status: WatchStatus;
    status_label: string;
    condition: string;
    condition_label: string;
    movement: string | null;
    movement_label: string | null;
    gender: string;
    year: number | null;
    is_featured: boolean;
    is_published: boolean;
    brand?: { id: number; name: string; slug: string };
    category?: { id: number; name: string; slug: string } | null;
    image?: WatchImage | null;
    images?: WatchImage[];
    views_count?: number;
    inquiries_count?: number;
    created_at: string | null;
};

export type WatchDetail = Watch & {
    description: string | null;
    brand_id: number;
    category_id: number | null;
    case_material: string | null;
    case_diameter: number | null;
    water_resistance: number | null;
    dial_color: string | null;
    strap_material: string | null;
    has_box: boolean;
    has_papers: boolean;
    meta_title: string | null;
    meta_description: string | null;
};

export type Category = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    image_url?: string | null;
    sort_order?: number;
    watches_count?: number;
};

export type Brand = {
    id: number;
    name: string;
    slug: string;
    country?: string | null;
    description?: string | null;
    watches_count?: number;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

export type Seo = {
    title: string | null;
    description: string;
    image: string | null;
    url: string;
    type: string;
    robots: string;
    jsonLd: Record<string, unknown>[];
};

export type CatalogueFilters = {
    search: string | null;
    category: string | null;
    brand: string | null;
    condition: string | null;
    movement: string | null;
    gender: string | null;
    min_price: number | null;
    max_price: number | null;
    available: boolean;
    sort: string;
};

export type CatalogueOptions = {
    categories: {
        id: number;
        name: string;
        slug: string;
        watches_count: number;
    }[];
    brands: { id: number; name: string; slug: string; watches_count: number }[];
    conditions: Option[];
    movements: Option[];
    genders: Option[];
    sorts: Option[];
};
