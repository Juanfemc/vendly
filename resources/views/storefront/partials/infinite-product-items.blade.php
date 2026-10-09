@foreach($products as $product)
    @if($storefrontVariant === 'technology')
        @include('storefront.partials.minimal-product-card', ['product' => $product, 'isRecommendation' => false])
    @elseif($storefrontVariant === 'fashion')
        @include('storefront.partials.fashion-product-card')
    @else
        @if(($infiniteContext ?? 'catalog') === 'home')
            @php
                $defaultCategoriesByName = collect($activeCategories ?? [])->keyBy(fn ($category) => $category->name);
                $categoryName = trim((string) ($product->category ?? ''));

                if ($categoryName === '') {
                    $defaultCategoryProduct = '__other';
                    $defaultProductCategoryLabel = 'Otros';
                } else {
                    $category = $defaultCategoriesByName->get($categoryName);

                    if ($category) {
                        $defaultCategorySlugs = collect([$category->slug]);

                        if ($category->parent_id && $category->relationLoaded('parent') && $category->parent) {
                            $defaultCategorySlugs->push($category->parent->slug);
                        }

                        $defaultCategoryProduct = $defaultCategorySlugs->filter()->unique()->implode(',');
                        $defaultProductCategoryLabel = $category->name;
                    } else {
                        $defaultCategoryProduct = \Illuminate\Support\Str::slug($categoryName);
                        $defaultProductCategoryLabel = $categoryName;
                    }
                }

                $defaultProductIndex = ($productIndexOffset ?? 0) + $loop->index;
            @endphp
        @endif
        @include('storefront.partials.product-card', ['cardClass' => $cardClass ?? ''])
    @endif
@endforeach

