<?php

namespace Database\Seeders;

use App\Enums\WatchCondition;
use App\Enums\WatchGender;
use App\Enums\WatchMovement;
use App\Enums\WatchStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Watch;
use Database\Seeders\Concerns\DownloadsImages;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    use DownloadsImages;

    /**
     * @var list<array{name: string, country: string, description: string}>
     */
    private const BRANDS = [
        ['name' => 'Rolex', 'country' => 'Switzerland', 'description' => 'The benchmark for robust, precise and enduring luxury watches since 1905.'],
        ['name' => 'Omega', 'country' => 'Switzerland', 'description' => 'Official timekeeper of the Olympics and the first watch worn on the Moon.'],
        ['name' => 'Tissot', 'country' => 'Switzerland', 'description' => 'Innovators by tradition, building accessible Swiss watches in Le Locle since 1853.'],
        ['name' => 'Hamilton', 'country' => 'Switzerland', 'description' => 'American spirit and Swiss precision, with deep roots in military and aviation watches.'],
        ['name' => 'Frederique Constant', 'country' => 'Switzerland', 'description' => 'Geneva based watchmaker known for honest, elegant mechanical watches.'],
        ['name' => 'Waltham', 'country' => 'United States', 'description' => 'A pioneer of industrial American watchmaking, founded in Massachusetts in 1850.'],
    ];

    /**
     * @var list<array{name: string, description: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Dive Watches', 'description' => 'Rotating bezels, serious water resistance and legibility built for the deep, and for everyday life.'],
        ['name' => 'Dress Watches', 'description' => 'Refined proportions and considered dials for the moments that call for something special.'],
        ['name' => 'Chronographs', 'description' => 'Stopwatch complications with racing and aviation heritage, from classic pandas to modern tool watches.'],
        ['name' => 'Sport Watches', 'description' => 'Versatile steel watches that move effortlessly from the office to the trail.'],
        ['name' => 'Field Watches', 'description' => 'Honest, legible and tough. Military inspired watches designed to be worn every day.'],
        ['name' => 'Vintage', 'description' => 'Pieces with a story. Carefully serviced timepieces from decades past.'],
    ];

    /**
     * Seed brands, categories and a curated set of watches with photography.
     */
    public function run(): void
    {
        $brands = collect(self::BRANDS)->mapWithKeys(fn (array $brand): array => [
            $brand['name'] => Brand::query()->updateOrCreate(['name' => $brand['name']], $brand),
        ]);

        $categories = collect(self::CATEGORIES)->values()->mapWithKeys(fn (array $category, int $index): array => [
            $category['name'] => Category::query()->updateOrCreate(
                ['name' => $category['name']],
                [...$category, 'sort_order' => $index],
            ),
        ]);

        foreach ($this->watches() as $data) {
            $photos = $data['photos'];

            $watch = Watch::query()->updateOrCreate(
                ['name' => $data['name'], 'brand_id' => $brands[$data['brand']]->id],
                [
                    ...collect($data)->except(['brand', 'category', 'photos'])->all(),
                    'category_id' => $categories[$data['category']]->id,
                ],
            );

            $this->seedPhotos($watch, $data['brand'], $photos);
        }
    }

    /**
     * Download a portrait cover, a close dial detail and a wide shot for each photo.
     *
     * @param  list<string>  $photos
     */
    private function seedPhotos(Watch $watch, string $brand, array $photos): void
    {
        if ($watch->images()->exists()) {
            return;
        }

        $label = "{$brand} {$watch->name}";
        $shots = [];

        foreach ($photos as $index => $photoId) {
            $shots[] = [$photoId, ['w' => 1600, 'h' => 2000], $index === 0 ? "{$label}, front view" : "{$label}, alternate view"];
        }

        $shots[] = [$photos[0], ['w' => 1600, 'h' => 2000, 'crop' => 'focalpoint', 'fp-x' => 0.5, 'fp-y' => 0.5, 'fp-z' => 1.8], "{$label}, dial detail"];
        $shots[] = [$photos[0], ['w' => 2000, 'h' => 1500], "{$label}, wide view"];

        foreach ($shots as $position => [$photoId, $parameters, $alt]) {
            $path = $this->downloadImage(
                $this->unsplashUrl($photoId, $parameters),
                "watches/{$watch->id}/{$watch->slug}-{$position}.jpg",
            );

            if ($path !== null) {
                $watch->images()->create(['path' => $path, 'alt' => $alt, 'sort_order' => $position]);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function watches(): array
    {
        return [
            [
                'brand' => 'Rolex',
                'category' => 'Dive Watches',
                'name' => 'Submariner Date 41',
                'reference' => '126610LN',
                'summary' => 'The definitive dive watch in Oystersteel with a black Cerachrom bezel, full set from 2021.',
                'description' => "The current generation Submariner Date refines a seventy year old icon. The 41 mm Oyster case is slightly slimmer through the lugs than its predecessor, and the calibre 3235 inside delivers a 70 hour power reserve with Superlative Chronometer accuracy of minus two to plus two seconds a day.\n\nThis example has been worn lightly and shows only faint hairlines on the clasp. The bezel action is crisp, the lume is even and the bracelet has no stretch. It comes with its green box, warranty card dated 2021 and all original links.",
                'price' => 14950,
                'status' => WatchStatus::Available,
                'condition' => WatchCondition::PreOwned,
                'movement' => WatchMovement::Automatic,
                'gender' => WatchGender::Men,
                'year' => 2021,
                'case_material' => 'Oystersteel',
                'case_diameter' => 41,
                'water_resistance' => 300,
                'dial_color' => 'Black',
                'strap_material' => 'Oyster bracelet',
                'has_box' => true,
                'has_papers' => true,
                'is_featured' => true,
                'photos' => ['1594534475808-b18fc33b045e', '1526045431048-f857369baa09'],
            ],
            [
                'brand' => 'Rolex',
                'category' => 'Dive Watches',
                'name' => 'Submariner Date Two Tone',
                'reference' => '126613LN',
                'summary' => 'Oystersteel and yellow gold with a black dial. Unworn, with stickers still on the case back.',
                'description' => "Rolesor has been part of the Submariner story since the 1980s, and the black dial version is the understated way to wear it. Gold centre links and a gold bezel frame the dial without overwhelming it.\n\nThis watch is unworn and complete with every accessory, including the hang tag and protective stickers. A rare opportunity to buy one without the waiting list.",
                'price' => 19800,
                'status' => WatchStatus::Available,
                'condition' => WatchCondition::Unworn,
                'movement' => WatchMovement::Automatic,
                'gender' => WatchGender::Men,
                'year' => 2024,
                'case_material' => 'Oystersteel and 18k yellow gold',
                'case_diameter' => 41,
                'water_resistance' => 300,
                'dial_color' => 'Black',
                'strap_material' => 'Oyster bracelet',
                'has_box' => true,
                'has_papers' => true,
                'is_featured' => true,
                'photos' => ['1639006570490-79c0c53f1080'],
            ],
            [
                'brand' => 'Omega',
                'category' => 'Dive Watches',
                'name' => 'Seamaster Planet Ocean 600M',
                'reference' => null,
                'summary' => 'A professional grade diver in steel with a grey dial and orange accents, rated to 600 metres.',
                'description' => "The Planet Ocean is Omega's serious diver, with a helium escape valve, a ceramic bezel and a Master Chronometer movement certified by METAS to resist magnetic fields up to 15,000 gauss.\n\nThe grey dial and bright orange details give it a sporty character that works just as well on land. This example has been fully serviced and comes with a two year warranty from us.",
                'price' => 6950,
                'status' => WatchStatus::Available,
                'condition' => WatchCondition::PreOwned,
                'movement' => WatchMovement::Automatic,
                'gender' => WatchGender::Men,
                'year' => 2019,
                'case_material' => 'Stainless steel',
                'case_diameter' => 43.5,
                'water_resistance' => 600,
                'dial_color' => 'Grey',
                'strap_material' => 'Steel bracelet',
                'has_box' => true,
                'has_papers' => false,
                'is_featured' => true,
                'photos' => ['1548171915-e79a380a2a4b'],
            ],
            [
                'brand' => 'Omega',
                'category' => 'Chronographs',
                'name' => 'Seamaster Planet Ocean Chronograph',
                'reference' => null,
                'summary' => 'A bold blue dial chronograph with a co-axial movement and 600 metres of water resistance.',
                'description' => "Big, confident and beautifully finished. The deep blue dial carries three sub registers with red accents that make elapsed time easy to read at a glance.\n\nThe case shows light signs of wear consistent with careful use, and the chronograph starts, stops and resets perfectly. Supplied with a service receipt from 2024.",
                'price' => 4250,
                'status' => WatchStatus::Available,
                'condition' => WatchCondition::PreOwned,
                'movement' => WatchMovement::Automatic,
                'gender' => WatchGender::Men,
                'year' => 2012,
                'case_material' => 'Stainless steel',
                'case_diameter' => 45.5,
                'water_resistance' => 600,
                'dial_color' => 'Blue',
                'strap_material' => 'Steel bracelet',
                'has_box' => false,
                'has_papers' => false,
                'is_featured' => false,
                'photos' => ['1523170335258-f5ed11844a49'],
            ],
            [
                'brand' => 'Rolex',
                'category' => 'Dress Watches',
                'name' => 'Datejust 36 Chocolate Diamond',
                'reference' => '126231',
                'summary' => 'Everose Rolesor with a chocolate dial, diamond hour markers and a fluted bezel on a Jubilee bracelet.',
                'description' => "Warm, elegant and endlessly wearable. The chocolate sunburst dial shifts from espresso to bronze as it catches the light, and the diamond set markers add sparkle without shouting.\n\nThe 36 mm case is the classic Datejust size and suits almost every wrist. This watch was purchased new in 2022 and is complete with box and papers.",
                'price' => 15900,
                'status' => WatchStatus::Available,
                'condition' => WatchCondition::PreOwned,
                'movement' => WatchMovement::Automatic,
                'gender' => WatchGender::Unisex,
                'year' => 2022,
                'case_material' => 'Oystersteel and 18k Everose gold',
                'case_diameter' => 36,
                'water_resistance' => 100,
                'dial_color' => 'Chocolate',
                'strap_material' => 'Jubilee bracelet',
                'has_box' => true,
                'has_papers' => true,
                'is_featured' => true,
                'photos' => ['1600003014755-ba31aa59c4b6'],
            ],
            [
                'brand' => 'Rolex',
                'category' => 'Sport Watches',
                'name' => 'Explorer 39',
                'reference' => '214270',
                'summary' => 'The mountaineer\'s watch. A black dial with the famous 3, 6 and 9 numerals in a 39 mm case.',
                'description' => "Born from the 1953 Everest expedition, the Explorer is Rolex at its most purposeful. There is no date and no rotating bezel, just a perfectly legible dial and a case built to go anywhere.\n\nThis is the later Mark II dial with full length hands and luminous numerals. Condition is excellent with a recent polish by the Rolex service centre.",
                'price' => 7900,
                'status' => WatchStatus::Available,
                'condition' => WatchCondition::PreOwned,
                'movement' => WatchMovement::Automatic,
                'gender' => WatchGender::Men,
                'year' => 2018,
                'case_material' => 'Oystersteel',
                'case_diameter' => 39,
                'water_resistance' => 100,
                'dial_color' => 'Black',
                'strap_material' => 'Oyster bracelet',
                'has_box' => true,
                'has_papers' => true,
                'is_featured' => false,
                'photos' => ['1587836374828-4dbafa94cf0e'],
            ],
            [
                'brand' => 'Rolex',
                'category' => 'Sport Watches',
                'name' => 'Oyster Perpetual 39 Grey',
                'reference' => '114300',
                'summary' => 'Rolex distilled to its essentials, with a discontinued rhodium grey dial.',
                'description' => "The Oyster Perpetual is the purest expression of the Rolex formula: a waterproof case, a self winding movement and nothing more. The rhodium grey dial was discontinued in 2020 and has become a quiet favourite among collectors.\n\nThe case retains sharp edges and the bracelet is tight. Supplied with its original box.",
                'price' => 7250,
                'status' => WatchStatus::Reserved,
                'condition' => WatchCondition::PreOwned,
                'movement' => WatchMovement::Automatic,
                'gender' => WatchGender::Unisex,
                'year' => 2017,
                'case_material' => 'Oystersteel',
                'case_diameter' => 39,
                'water_resistance' => 100,
                'dial_color' => 'Rhodium grey',
                'strap_material' => 'Oyster bracelet',
                'has_box' => true,
                'has_papers' => false,
                'is_featured' => false,
                'photos' => ['1547996160-81dfa63595aa'],
            ],
            [
                'brand' => 'Tissot',
                'category' => 'Chronographs',
                'name' => 'Heritage 1948 Chronograph',
                'reference' => null,
                'summary' => 'A faithful reissue of a 1948 design with a salmon dial and rose gold tone case.',
                'description' => "Tissot reached into its archive for this one, and the result is a chronograph with genuine mid century charm. The domed crystal, applied numerals and warm salmon dial make it look far more expensive than it is.\n\nBrand new and supplied with the full manufacturer warranty.",
                'price' => 1650,
                'status' => WatchStatus::Available,
                'condition' => WatchCondition::New,
                'movement' => WatchMovement::Automatic,
                'gender' => WatchGender::Unisex,
                'year' => 2025,
                'case_material' => 'Rose gold PVD steel',
                'case_diameter' => 41,
                'water_resistance' => 30,
                'dial_color' => 'Salmon',
                'strap_material' => 'Brown leather',
                'has_box' => true,
                'has_papers' => true,
                'is_featured' => false,
                'photos' => ['1522312346375-d1a52e2b99b3'],
            ],
            [
                'brand' => 'Hamilton',
                'category' => 'Field Watches',
                'name' => 'Khaki Field Mechanical',
                'reference' => 'H69439931',
                'summary' => 'A hand wound military field watch with an 80 hour power reserve. Honest, tough and endlessly wearable.',
                'description' => "Hamilton supplied watches to the United States armed forces for decades, and the Khaki Field Mechanical carries that heritage with pride. The hand wound H-50 movement offers a remarkable 80 hours of power reserve.\n\nThe 38 mm case sits beautifully on the wrist and the tan strap ages wonderfully. A perfect first mechanical watch.",
                'price' => 595,
                'status' => WatchStatus::Available,
                'condition' => WatchCondition::New,
                'movement' => WatchMovement::ManualWind,
                'gender' => WatchGender::Unisex,
                'year' => 2025,
                'case_material' => 'Stainless steel',
                'case_diameter' => 38,
                'water_resistance' => 50,
                'dial_color' => 'Black',
                'strap_material' => 'Tan leather',
                'has_box' => true,
                'has_papers' => true,
                'is_featured' => false,
                'photos' => ['1495856458515-0637185db551'],
            ],
            [
                'brand' => 'Frederique Constant',
                'category' => 'Dress Watches',
                'name' => 'Classics Index Automatic',
                'reference' => null,
                'summary' => 'A navy sunray dial, slim steel case and blue alligator pattern strap. Understated Geneva elegance.',
                'description' => "Frederique Constant built its name on accessible luxury, and the Classics Index is the model that best explains why. Dauphine hands, applied indices and a slim profile give it the proportions of watches costing several times more.\n\nThis watch is new old stock from an authorised dealer and includes the full warranty.",
                'price' => 1395,
                'status' => WatchStatus::Available,
                'condition' => WatchCondition::New,
                'movement' => WatchMovement::Automatic,
                'gender' => WatchGender::Men,
                'year' => 2024,
                'case_material' => 'Stainless steel',
                'case_diameter' => 40,
                'water_resistance' => 50,
                'dial_color' => 'Navy',
                'strap_material' => 'Blue leather',
                'has_box' => true,
                'has_papers' => true,
                'is_featured' => false,
                'photos' => ['1619134778706-7015533a6150'],
            ],
            [
                'brand' => 'Rolex',
                'category' => 'Dress Watches',
                'name' => 'Datejust 36 Silver Jubilee',
                'reference' => '116234',
                'summary' => 'Steel with a white gold fluted bezel, silver stick dial and Jubilee bracelet.',
                'description' => "The quintessential Datejust. The silver dial, fluted white gold bezel and Jubilee bracelet create a watch that looks equally at home with a suit or a t-shirt.\n\nThis example has recently been sold, but we regularly source similar pieces. Get in touch and we will let you know when the next one arrives.",
                'price' => 9400,
                'status' => WatchStatus::Sold,
                'condition' => WatchCondition::PreOwned,
                'movement' => WatchMovement::Automatic,
                'gender' => WatchGender::Unisex,
                'year' => 2014,
                'case_material' => 'Oystersteel and 18k white gold',
                'case_diameter' => 36,
                'water_resistance' => 100,
                'dial_color' => 'Silver',
                'strap_material' => 'Jubilee bracelet',
                'has_box' => true,
                'has_papers' => true,
                'is_featured' => false,
                'photos' => ['1620625515032-6ed0c1790c75'],
            ],
            [
                'brand' => 'Waltham',
                'category' => 'Vintage',
                'name' => 'Open Face Pocket Watch',
                'reference' => null,
                'summary' => 'A 1920s open face pocket watch with Roman numerals and a sub seconds dial. Serviced and running beautifully.',
                'description' => "Before the wristwatch took over, a fine pocket watch was the mark of a well dressed gentleman. This Waltham has a crisp enamel dial with Roman numerals, blued steel hands and a small seconds register at six o'clock.\n\nThe movement has been fully serviced by our watchmaker and keeps excellent time. Pricing on vintage pieces depends on current market conditions, so please get in touch for details.",
                'price' => null,
                'status' => WatchStatus::Available,
                'condition' => WatchCondition::Vintage,
                'movement' => WatchMovement::ManualWind,
                'gender' => WatchGender::Men,
                'year' => 1924,
                'case_material' => 'Sterling silver',
                'case_diameter' => 50,
                'water_resistance' => null,
                'dial_color' => 'White enamel',
                'strap_material' => 'Silver chain',
                'has_box' => false,
                'has_papers' => false,
                'is_featured' => false,
                'photos' => ['1509048191080-d2984bad6ae5'],
            ],
        ];
    }
}
